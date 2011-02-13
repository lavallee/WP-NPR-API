<?php
require_once( 'story.php' );

define( 'NPR_API_URL', 'http://api.npr.org/' );
define( 'NPR_API_DEFAULT_ACTION', 'query' );
define( 'NPR_OUTPUT_FORMAT', 'JSON' );
define( 'NPR_FIELDS', 'all' );


class NPR_API_Client {
    private $api_key;


    function NPR_API_Client( $api_key ) {
        $this->api_key = $api_key;
    }


    function story_from_id( $id ) {
        $response = $this->_api_request( array( 'id' => $id ) );
        //var_dump( $response );
        if ( $response ) {
            foreach ( $response->list->story as $story ) {
                if ( $story->id == $id ) {
                    $converter = new JSON_Story_Converter();
                    return $converter->convert( $story );
                }
            }
        }
    }
    
    function recent_stories() {
        $response = $this->_api_request();
                
        if ( $response ) {
        
            $stories = array();
            $converter = new JSON_Story_Converter();

            foreach ( $response->list->story as $story ) {
                array_push( $stories, $converter->convert( $story ) );
            }

            return $stories;
        }
    }


    protected function _api_request( $args = array(), $action = null ) {
        $defaults = array(
            'output' => NPR_OUTPUT_FORMAT,
            'fields' => NPR_FIELDS,
            'apiKey' => $this->api_key,
        );
        
        if ( !isset($action) ) {
            $action = NPR_API_DEFAULT_ACTION;
        }
        
        $params = wp_parse_args( $args, $defaults );
        $url = add_query_arg( $params, NPR_API_URL . $action );        
        $response = wp_remote_get( $url );
                
        if ( is_wp_error( $response ) ) {
            // XXX: Handle error
            
        }
        else {
            // XXX: handle errors in JSON payload of successful response
            $resp = wp_remote_retrieve_body( $response );
            return json_decode( $resp );
        }
    }
}

?>
