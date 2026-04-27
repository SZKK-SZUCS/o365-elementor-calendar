<?php
namespace O365Calendar;

use WP_Error;

class GraphAPI {

    private $tenant_id;
    private $client_id;
    private $client_secret;
    private $sender_email;

    public function __construct() {
        $this->tenant_id = defined( 'O365_TENANT_ID' ) ? O365_TENANT_ID : '';
        $this->client_id = defined( 'O365_CLIENT_ID' ) ? O365_CLIENT_ID : '';
        $this->client_secret = defined( 'O365_CLIENT_SECRET' ) ? O365_CLIENT_SECRET : '';
        $this->sender_email = defined( 'O365_SENDER_EMAIL' ) ? O365_SENDER_EMAIL : '';
    }

    /**
     * Ellenőrzi, hogy a konfig be van-e állítva
     */
    public function is_configured() {
        return ! empty( $this->tenant_id ) && ! empty( $this->client_id ) && ! empty( $this->client_secret );
    }

    /**
     * Access Token lekérése (vagy cache-ből olvasása)
     */
    public function get_access_token() {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'missing_config', __( 'O365 konfiguráció hiányzik a wp-config.php-ból.', 'o365-calendar' ) );
        }

        $token = get_transient( 'o365_access_token' );
        if ( $token ) {
            return $token;
        }

        $url = "https://login.microsoftonline.com/{$this->tenant_id}/oauth2/v2.0/token";
        $response = wp_remote_post( $url, [
            'body' => [
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'scope'         => 'https://graph.microsoft.com/.default',
                'grant_type'    => 'client_credentials',
            ]
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error_description'] ?? $body['error'] );
        }

        if ( isset( $body['access_token'] ) ) {
            // A lejáratból levonunk 5 percet (300mp) biztonsági tartalékként
            $expires_in = intval( $body['expires_in'] ) - 300;
            set_transient( 'o365_access_token', $body['access_token'], $expires_in );
            return $body['access_token'];
        }

        return new WP_Error( 'unknown_error', __( 'Sikertelen token lekérés.', 'o365-calendar' ) );
    }

    /**
     * API hívás helper
     */
    private function request( $method, $endpoint, $body = null ) {
        $token = $this->get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $url = "https://graph.microsoft.com/v1.0" . $endpoint;
        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Prefer'        => 'outlook.timezone="Europe/Budapest"'
            ],
            'timeout' => 15,
        ];

        if ( $body !== null ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $response_code >= 400 ) {
            $error_msg = $response_body['error']['message'] ?? __( 'Ismeretlen Graph API hiba.', 'o365-calendar' );
            return new WP_Error( 'graph_api_error', $error_msg );
        }

        return $response_body;
    }

    /**
     * Felhasználó naptárainak lekérése
     */
    public function get_calendars( $email ) {
        return $this->request( 'GET', "/users/{$email}/calendars" );
    }

    /**
     * Események lekérése időablak alapján
     */
    public function get_events( $email, $calendar_id, $start_date, $end_date ) {
        // Caching logika, hogy 15 percig ne terheljük az API-t ugyanazzal a kéréssel
        $cache_key = 'o365_ev_' . md5( $email . $calendar_id . $start_date . $end_date );
        $cached_events = get_transient( $cache_key );
        
        if ( $cached_events !== false ) {
            return $cached_events;
        }

        $endpoint = "/users/{$email}/calendars/{$calendar_id}/calendarView?startDateTime={$start_date}&endDateTime={$end_date}&\$select=subject,bodyPreview,body,start,end,location";
        $response = $this->request( 'GET', $endpoint );

        if ( ! is_wp_error( $response ) && isset( $response['value'] ) ) {
            set_transient( $cache_key, $response['value'], 15 * MINUTE_IN_SECONDS );
            return $response['value'];
        }

        return $response;
    }

    /**
     * Validációs kód kiküldése emailben
     */
    public function send_verification_email( $to_email, $code ) {
        if ( empty( $this->sender_email ) ) {
            return new WP_Error( 'missing_sender', __( 'Nincs beállítva küldő email cím (O365_SENDER_EMAIL).', 'o365-calendar' ) );
        }

        $body = [
            'message' => [
                'subject' => __( 'Naptár hitelesítési kód / Calendar Auth Code', 'o365-calendar' ),
                'body' => [
                    'contentType' => 'Text',
                    'content' => sprintf( __( 'A te hitelesítési kódod: %s', 'o365-calendar' ), $code )
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $to_email
                        ]
                    ]
                ]
            ],
            'saveToSentItems' => 'false'
        ];

        return $this->request( 'POST', "/users/{$this->sender_email}/sendMail", $body );
    }
}