<?php

define( 'NPR_API_URL', 'http://api.npr.org/query' );
define( 'OUTPUT_FORMAT', 'JSON' );
define( 'FIELDS', 'all' );

class NPR_API_Client {
    private $api_key;

    function NPR_API_Client( $api_key ) {
        $this->api_key = $api_key;
    }

    function story_from_id( $id ) {
        return $this->_api_request( array( 'storyId' => $id ) );

    }

    protected function _api_request( $args ) {
        $defaults = array(
            'output' => OUTPUT_FORMAT,
            'fields' => FIELDS,
            'apiKey' => $this->api_key,
        );
        $params = wp_parse_args( $args, $defaults );

        $url = add_query_arg( $params, NPR_API_URL );
        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            // FUCK
        }
        else {
            return json_decode( wp_remote_retrieve_body( $response ) );
        }
    }

}


?>
