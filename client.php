<?php

define( 'NPR_API_URL', 'http://api.npr.org/query' );
define( 'OUTPUT_FORMAT', 'JSON' );
define( 'FIELDS', 'all' );
define( 'STORY_ID_META_KEY', 'npr_story_id' );

require_once( 'story.php' );

class NPR_API_Client {
    private $api_key;


    function NPR_API_Client( $api_key ) {
        $this->api_key = $api_key;
    }


    function story_from_id( $id ) {
        $response = $this->_api_request( array( 'id' => $id ) );
        var_dump( $response );
        if ( $response ) {
            foreach ( $response->list->story as $story ) {
                if ( $story->id == $id ) {
                    $converter = new JSON_Story_Converter();
                    return $converter->convert( $story );
                }
            }
        }
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
            'post_title' => $story->title,
            'post_content' => $story->content,
            'post_excerpt' => $story->teaser,
            'post_status' => 'draft',
        );
        $metas = array(
            STORY_ID_META_KEY => $story->id,
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
