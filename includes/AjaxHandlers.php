<?php
namespace O365Calendar;

use WP_REST_Request;
use WP_REST_Response;

class AjaxHandlers {
    private $api;

    public function __construct( GraphAPI $api ) {
        $this->api = $api;
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        $namespace = 'o365cal/v1';
        register_rest_route( $namespace, '/auth/request-code', [
            'methods' => 'POST',
            'callback' => [ $this, 'request_auth_code' ],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        register_rest_route( $namespace, '/auth/verify-code', [
            'methods' => 'POST',
            'callback' => [ $this, 'verify_auth_code' ],
            'permission_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        register_rest_route( $namespace, '/events', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_calendar_events' ],
            'permission_callback' => '__return_true'
        ]);
    }

    public function request_auth_code( $request ) {
        $email = sanitize_email( $request->get_param( 'email' ) );
        $code = sprintf( "%06d", mt_rand( 1, 999999 ) );
        set_transient( 'o365_auth_' . md5( $email ), $code, 15 * MINUTE_IN_SECONDS );
        
        $result = $this->api->send_verification_email( $email, $code );
        if ( is_wp_error( $result ) ) return new WP_REST_Response( [ 'message' => $result->get_error_message() ], 400 );
        
        return new WP_REST_Response( [ 'message' => 'Kód elküldve!' ], 200 );
    }

    public function verify_auth_code( $request ) {
        $email = sanitize_email( $request->get_param( 'email' ) );
        $code  = sanitize_text_field( $request->get_param( 'code' ) );
        $saved = get_transient( 'o365_auth_' . md5( $email ) );

        if ( ! $saved || $saved !== $code ) return new \WP_REST_Response( [ 'message' => 'Hibás kód.' ], 400 );

        // 1. NAPTÁRAK LEKÉRÉSE ÉS MENTÉSE
        $calendars = $this->api->get_calendars( $email );
        if ( is_wp_error( $calendars ) ) return new \WP_REST_Response( [ 'message' => $calendars->get_error_message() ], 400 );

        $cal_list = [];
        foreach ( $calendars['value'] as $cal ) { $cal_list[$cal['id']] = $cal['name']; }
        update_option( 'o365_cached_calendars', $cal_list );

        // 2. KATEGÓRIÁK LEKÉRÉSE ÉS MENTÉSE (ÚJ RÉSZ)
        $categories = $this->api->get_master_categories( $email );
        $cat_list = [];
        if ( ! is_wp_error( $categories ) && isset($categories['value']) ) {
            foreach ( $categories['value'] as $cat ) {
                $cat_list[ $cat['displayName'] ] = $cat['displayName'];
            }
        }
        update_option( 'o365_cached_categories', $cat_list );
        update_option( 'o365_auth_email', $email );

        return new \WP_REST_Response( [ 'message' => 'Sikeres hitelesítés! Az oldalsáv újratöltődik...' ], 200 );
    }

    public function get_calendar_events( $request ) {
        $email = sanitize_email( $request->get_param( 'email' ) );
        $calendar_ids_raw = $request->get_param( 'calendar_id' );
        $calendar_ids = explode(',', $calendar_ids_raw);
        
        $start = date('Y-m-d\TH:i:s\Z', strtotime($request->get_param('start')));
        $end   = date('Y-m-d\TH:i:s\Z', strtotime($request->get_param('end')));

        $use_colors = $request->get_param( 'use_colors' ) === 'yes';
        $privacy    = $request->get_param( 'privacy' ) ?: 'mask';
        
        // ÚJ ADATOK FELDOLGOZÁSA
        $mask_text       = sanitize_text_field( $request->get_param( 'mask_text' ) ?: 'Foglalt' );
        $category_filter = sanitize_text_field( $request->get_param( 'category_filter' ) ?: '' );

        $cache_key = 'o365_events_' . md5( $email . $calendar_ids_raw . $start . $end . $use_colors . $privacy . $mask_text . $category_filter );
        $cached_events = get_transient( $cache_key );
        
        if ( $cached_events !== false ) {
            return new \WP_REST_Response( $cached_events, 200 );
        }

        // Szín-térkép felépítése (ha kell)
        $color_map = [];
        if ( $use_colors ) {
            $cat_cache_key = 'o365_categories_' . md5($email);
            $color_map = get_transient( $cat_cache_key );
            if ( false === $color_map ) {
                $color_map = [];
                $cat_response = $this->api->get_master_categories( $email );
                if ( ! is_wp_error( $cat_response ) && isset($cat_response['value']) ) {
                    foreach ( $cat_response['value'] as $cat ) {
                        $color_map[ $cat['displayName'] ] = $this->map_preset_color( $cat['color'] );
                    }
                }
                set_transient( $cat_cache_key, $color_map, 12 * HOUR_IN_SECONDS );
            }
        }

        $all_events = [];

        // Szűrő tömb előkészítése kisbetűssé és whitespace mentessé
        $filter_arr = [];
        if ( ! empty( $category_filter ) ) {
            $filter_arr = array_filter( array_map( 'trim', array_map( 'strtolower', explode( ',', $category_filter ) ) ) );
        }

        foreach ( $calendar_ids as $cal_id ) {
            if ( empty( trim($cal_id) ) ) continue;
            $events = $this->api->get_events( $email, trim($cal_id), $start, $end );
            if ( is_wp_error( $events ) ) return new \WP_REST_Response( [ 'message' => $events->get_error_message() ], 400 );

            foreach ( (array)$events as $event ) {
                
                // 1. KATEGÓRIA SZŰRÉS
                $event_categories = $event['categories'] ?? [];
                if ( ! empty( $filter_arr ) ) {
                    $event_cat_arr = array_map( 'strtolower', (array) $event_categories );
                    $intersect = array_intersect( $filter_arr, $event_cat_arr );
                    
                    // Ha az eseménynek nincs egyetlen olyan kategóriája sem, amit beállítottunk, ugrunk a következőre.
                    if ( empty( $intersect ) ) {
                        continue; 
                    }
                }

                // 2. PRIVÁT ESEMÉNYEK KEZELÉSE
                $sensitivity = $event['sensitivity'] ?? 'normal';
                $is_private = in_array( strtolower($sensitivity), ['private', 'confidential'] );

                if ( $is_private && $privacy === 'hide' ) continue;

                $title = $event['subject'] ?? '';
                $location = $event['location']['displayName'] ?? '';
                $body = $event['bodyPreview'] ?? '';

                // A felhasználó által megadott egyedi maszkoló szöveget használjuk
                if ( $is_private && $privacy === 'mask' ) {
                    $title = $mask_text;
                    $location = '';
                    $body = '';
                }

                $fc_event = [
                    'id'    => $event['id'],
                    'title' => $title,
                    'start' => $event['start']['dateTime'],
                    'end'   => $event['end']['dateTime'],
                    'allDay' => $event['isAllDay'],
                    'extendedProps' => [
                        'location' => $location,
                        'body' => $body,
                        'isPrivate' => ($is_private && $privacy === 'mask')
                    ]
                ];

                if ( $use_colors && !empty($event_categories) ) {
                    $first_cat = $event_categories[0];
                    if ( !empty($color_map[$first_cat]) ) {
                        $fc_event['backgroundColor'] = $color_map[$first_cat];
                        $fc_event['borderColor'] = $color_map[$first_cat];
                    }
                }

                $all_events[] = $fc_event;
            }
        }

        set_transient( $cache_key, $all_events, 15 * MINUTE_IN_SECONDS );
        return new \WP_REST_Response( $all_events, 200 );
    }

    // A Microsoft "presetX" színeinek lefordítása igazi HEX kódokra
    private function map_preset_color( $preset ) {
        $colors = [
            'preset0' => '#e74c3c', 'preset1' => '#e67e22', 'preset2' => '#d35400',
            'preset3' => '#f1c40f', 'preset4' => '#2ecc71', 'preset5' => '#1abc9c',
            'preset6' => '#27ae60', 'preset7' => '#3498db', 'preset8' => '#9b59b6',
            'preset9' => '#c0392b', 'preset10' => '#95a5a6', 'preset11' => '#7f8c8d',
            'preset12' => '#bdc3c7', 'preset13' => '#34495e', 'preset14' => '#2c3e50',
            'preset15' => '#641E16', 'preset16' => '#78281F', 'preset17' => '#BA4A00',
            'preset18' => '#D35400', 'preset19' => '#F39C12', 'preset20' => '#1D8348',
            'preset21' => '#117A65', 'preset22' => '#196F3D', 'preset23' => '#21618C',
            'preset24' => '#5B2C6F'
        ];
        return $colors[$preset] ?? '';
    }
}