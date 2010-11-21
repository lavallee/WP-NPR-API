<?php

define( 'NPR_API_URL', 'http://api.npr.org/' );
define( 'NPR_API_DEFAULT_ACTION', 'query' );
define( 'OUTPUT_FORMAT', 'JSON' );
define( 'FIELDS', 'all' );
define( 'STORY_ID_META_KEY', 'npr_story_id' );
define( 'API_LINK_META_KEY', 'npr_api_link' );
define( 'HTML_LINK_META_KEY', 'npr_html_link' );
define( 'SHORT_LINK_META_KEY', 'npr_short_link' );

require_once( 'story.php' );

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
            'output' => OUTPUT_FORMAT,
            'fields' => FIELDS,
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


    function update_post_from_story( $story ) {
        $exists = new WP_Query( array( 'meta_key' => STORY_ID_META_KEY, 
                                       'meta_value' => $story->id ) );
        if ( $exists->post_count ) {
            // XXX: might be more than one here;
            $existing = $exists->post;
        }

        $args = array(
            'post_title'   => $story->title,
            'post_content' => $story->content,
            'post_excerpt' => $story->teaser,
            'post_status'  => 'draft',
        );
        $metas = array(
            STORY_ID_META_KEY   => $story->id,
            API_LINK_META_KEY   => $story->api_link,
            HTML_LINK_META_KEY  => $story->html_link,
            SHORT_LINK_META_KEY => $story->short_link,
        );

        if ( $existing ) {
            $created = false;
            $args[ 'ID' ] = $existing->ID;
        }
        else {
            $created = true;
        }
        $id = wp_insert_post( $args );

        foreach ( $metas as $k => $v ) {
            update_post_meta( $id, $k, $v );
        }

        return array( $created, $id );
    }
}



?>
