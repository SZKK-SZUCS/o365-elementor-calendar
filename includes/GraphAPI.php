<?php
namespace O365Calendar;

use WP_Error;

class GraphAPI {

    private $tenant_id;
    private $client_id;
    private $client_secret;
    private $sender_email;

    public function __construct() {
        $this->tenant_id     = defined( 'O365_TENANT_ID' ) ? O365_TENANT_ID : '';
        $this->client_id     = defined( 'O365_CLIENT_ID' ) ? O365_CLIENT_ID : '';
        $this->client_secret = defined( 'O365_CLIENT_SECRET' ) ? O365_CLIENT_SECRET : '';
        $this->sender_email  = defined( 'O365_SENDER_EMAIL' ) ? O365_SENDER_EMAIL : '';
    }

    public function is_configured() {
        return ! empty( $this->tenant_id ) && ! empty( $this->client_id ) && ! empty( $this->client_secret );
    }

    public function get_access_token() {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'missing_config', __( 'O365 konfiguráció hiányzik.', 'o365-calendar' ) );
        }

        $token = get_transient( 'o365_access_token' );
        if ( $token ) return $token;

        $url = "https://login.microsoftonline.com/{$this->tenant_id}/oauth2/v2.0/token";
        $response = wp_remote_post( $url, [
            'body' => [
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'scope'         => 'https://graph.microsoft.com/.default',
                'grant_type'    => 'client_credentials',
            ]
        ] );

        if ( is_wp_error( $response ) ) return $response;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error_description'] ?? $body['error'] );
        }

        $expires_in = intval( $body['expires_in'] ) - 300;
        set_transient( 'o365_access_token', $body['access_token'], $expires_in );
        return $body['access_token'];
    }

    private function request( $method, $endpoint, $params = [], $body = null ) {
        $token = $this->get_access_token();
        if ( is_wp_error( $token ) ) return $token;

        $url = "https://graph.microsoft.com/v1.0" . $endpoint;
        if ( ! empty( $params ) ) {
            $url = add_query_arg( $params, $url );
        }

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
        if ( is_wp_error( $response ) ) return $response;

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $response_code >= 400 ) {
            return new WP_Error( 'graph_api_error', $response_body['error']['message'] ?? 'Graph API error' );
        }

        return $response_body;
    }

    public function get_calendars( $email ) {
        return $this->request( 'GET', "/users/" . urlencode($email) . "/calendars" );
    }

    public function get_events( $user_email, $calendar_id, $start_date, $end_date ) {
        // A Microsoft a calendarView-nál ISO dátumokat vár, de tiszta formában
        $params = [
            'startDateTime' => $start_date,
            'endDateTime'   => $end_date,
            '$select'       => 'id,subject,start,end,bodyPreview,location,isAllDay',
            '$top'          => 100
        ];

        $endpoint = "/users/" . urlencode($user_email) . "/calendars/" . urlencode($calendar_id) . "/calendarView";
        $result = $this->request( 'GET', $endpoint, $params );

        return ( ! is_wp_error( $result ) && isset( $result['value'] ) ) ? $result['value'] : $result;
    }

    public function send_verification_email( $to_email, $code ) {
        $body = [
            'message' => [
                'subject' => 'Naptár hitelesítés',
                'body' => [ 'contentType' => 'Text', 'content' => "Kódod: {$code}" ],
                'toRecipients' => [[ 'emailAddress' => [ 'address' => $to_email ] ]]
            ]
        ];
        return $this->request( 'POST', "/users/{$this->sender_email}/sendMail", [], $body );
    }
}