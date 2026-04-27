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
        return new WP_REST_Response( [ 'calendars' => $list ], 200 );
    }

    public function get_calendar_events( $request ) {
        $email = sanitize_email( $request->get_param( 'email' ) );
        $calendar_id = $request->get_param( 'calendar_id' );
        
        // FullCalendar ISO dátumok tisztítása (Microsoft nem szereti a +02:00 részt néha)
        $start = date('Y-m-d\TH:i:s\Z', strtotime($request->get_param('start')));
        $end   = date('Y-m-d\TH:i:s\Z', strtotime($request->get_param('end')));

        $events = $this->api->get_events( $email, $calendar_id, $start, $end );

        if ( is_wp_error( $events ) ) {
            return new WP_REST_Response( [ 'message' => $events->get_error_message() ], 400 );
        }

        $formatted = [];
        foreach ( (array)$events as $event ) {
            $formatted[] = [
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
        return new WP_REST_Response( $formatted, 200 );
    }
}