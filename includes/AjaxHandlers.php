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
        $code = sanitize_text_field( $request->get_param( 'code' ) );
        $saved = get_transient( 'o365_auth_' . md5( $email ) );

        if ( ! $saved || $saved !== $code ) return new WP_REST_Response( [ 'message' => 'Hibás kód.' ], 400 );

        $calendars = $this->api->get_calendars( $email );
        if ( is_wp_error( $calendars ) ) return new WP_REST_Response( [ 'message' => $calendars->get_error_message() ], 400 );

        $list = [];
        foreach ( $calendars['value'] as $cal ) { $list[$cal['id']] = $cal['name']; }

        update_option( 'o365_cached_calendars', $list );
        update_option( 'o365_auth_email', $email );

        return new WP_REST_Response( [ 'calendars' => $list, 'message' => 'Sikeres hitelesítés! Az oldalsáv újratöltődik...' ], 200 );
    }

    public function get_calendar_events( $request ) {
        $email = sanitize_email( $request->get_param( 'email' ) );
        
        // Multi-calendar: Lehet hogy egy ID, lehet hogy vesszővel elválasztva több.
        $calendar_ids_raw = $request->get_param( 'calendar_id' );
        $calendar_ids = explode(',', $calendar_ids_raw);
        
        $start = date('Y-m-d\TH:i:s\Z', strtotime($request->get_param('start')));
        $end   = date('Y-m-d\TH:i:s\Z', strtotime($request->get_param('end')));

        // Szerver-oldali Cache beállítása (15 perc)
        $cache_key = 'o365_events_' . md5( $email . $calendar_ids_raw . $start . $end );
        $cached_events = get_transient( $cache_key );
        
        if ( $cached_events !== false ) {
            return new WP_REST_Response( $cached_events, 200 );
        }

        $all_events = [];

        // Minden kiválasztott naptárból lekérjük az eseményeket
        foreach ( $calendar_ids as $cal_id ) {
            if ( empty( trim($cal_id) ) ) continue;

            $events = $this->api->get_events( $email, trim($cal_id), $start, $end );
            
            if ( is_wp_error( $events ) ) {
                return new WP_REST_Response( [ 'message' => $events->get_error_message() ], 400 );
            }

            foreach ( (array)$events as $event ) {
                $all_events[] = [
                    'id'    => $event['id'],
                    'title' => $event['subject'],
                    'start' => $event['start']['dateTime'],
                    'end'   => $event['end']['dateTime'],
                    'allDay' => $event['isAllDay'],
                    'extendedProps' => [
                        'location' => $event['location']['displayName'] ?? '',
                        'body' => $event['bodyPreview'] ?? ''
                    ]
                ];
            }
        }

        // Elmentjük a cache-be 15 percre
        set_transient( $cache_key, $all_events, 15 * MINUTE_IN_SECONDS );

        return new WP_REST_Response( $all_events, 200 );
    }
}