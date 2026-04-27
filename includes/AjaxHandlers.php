<?php
namespace O365Calendar;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class AjaxHandlers {

    private $api;

    public function __construct( GraphAPI $api ) {
        $this->api = $api;
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        $namespace = 'o365cal/v1';

        // 1. Hitelesítő kód kérése (Csak bejelentkezett szerkesztőknek)
        register_rest_route( $namespace, '/auth/request-code', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'request_auth_code' ],
            'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
            'args'                => [
                'email' => [ 'required' => true, 'type' => 'string', 'format' => 'email' ]
            ]
        ] );

        // 2. Kód ellenőrzése és naptárak lekérése (Csak bejelentkezett szerkesztőknek)
        register_rest_route( $namespace, '/auth/verify-code', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'verify_auth_code' ],
            'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
            'args'                => [
                'email' => [ 'required' => true, 'type' => 'string', 'format' => 'email' ],
                'code'  => [ 'required' => true, 'type' => 'string' ]
            ]
        ] );

        // 3. Események lekérése a FullCalendar számára (Publikus)
        register_rest_route( $namespace, '/events', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_calendar_events' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'email'       => [ 'required' => true, 'type' => 'string', 'format' => 'email' ],
                'calendar_id' => [ 'required' => true, 'type' => 'string' ],
                'start'       => [ 'required' => true, 'type' => 'string' ], // ISO8601 formátumot küld a FullCalendar
                'end'         => [ 'required' => true, 'type' => 'string' ]
            ]
        ] );
    }

    public function request_auth_code( WP_REST_Request $request ) {
        $email = $request->get_param( 'email' );
        
        // 6 számjegyű kód generálása
        $code = sprintf( "%06d", mt_rand( 1, 999999 ) );
        
        // Elmentjük a kódot transientbe 15 percre
        set_transient( 'o365_auth_' . md5( $email ), $code, 15 * MINUTE_IN_SECONDS );

        // Kód kiküldése
        $result = $this->api->send_verification_email( $email, $code );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => $result->get_error_message() ], 400 );
        }

        return new WP_REST_Response( [ 'success' => true, 'message' => __( 'Kód elküldve.', 'o365-calendar' ) ], 200 );
    }

    public function verify_auth_code( WP_REST_Request $request ) {
        $email = $request->get_param( 'email' );
        $provided_code = $request->get_param( 'code' );
        $transient_key = 'o365_auth_' . md5( $email );
        
        $saved_code = get_transient( $transient_key );

        if ( ! $saved_code || $saved_code !== $provided_code ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Érvénytelen vagy lejárt kód.', 'o365-calendar' ) ], 400 );
        }

        // Ha a kód jó, töröljük a transientet
        delete_transient( $transient_key );

        // Lekérjük az elérhető naptárakat
        $calendars = $this->api->get_calendars( $email );

        if ( is_wp_error( $calendars ) ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => $calendars->get_error_message() ], 400 );
        }

        $formatted_calendars = [];
        if ( isset( $calendars['value'] ) ) {
            foreach ( $calendars['value'] as $cal ) {
                $formatted_calendars[ $cal['id'] ] = $cal['name'];
            }
        }

        return new WP_REST_Response( [ 
            'success' => true, 
            'calendars' => $formatted_calendars 
        ], 200 );
    }

    public function get_calendar_events( WP_REST_Request $request ) {
        $email = $request->get_param( 'email' );
        $calendar_id = $request->get_param( 'calendar_id' );
        $start = $request->get_param( 'start' );
        $end = $request->get_param( 'end' );

        $events = $this->api->get_events( $email, $calendar_id, $start, $end );

        if ( is_wp_error( $events ) ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => $events->get_error_message() ], 400 );
        }

        // A FullCalendar által elvárt JSON struktúra kialakítása
        $fc_events = [];
        foreach ( $events as $event ) {
            $fc_events[] = [
                'id'          => $event['id'],
                'title'       => $event['subject'],
                'start'       => $event['start']['dateTime'],
                'end'         => $event['end']['dateTime'],
                'extendedProps' => [
                    'bodyPreview' => $event['bodyPreview'] ?? '',
                    'body'        => $event['body']['content'] ?? '',
                    'location'    => $event['location']['displayName'] ?? ''
                ]
            ];
        }

        return new WP_REST_Response( $fc_events, 200 );
    }
}