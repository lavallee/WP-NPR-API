<?php

define( 'NPR_API_URL', 'http://api.npr.org/query' );
define( 'OUTPUT_FORMAT', 'JSON' );
define( 'FIELDS', 'all' );
define( 'STORY_ID_META_KEY', 'npr_story_id' );

class NPR_API_Client {
    private $api_key;

    function NPR_API_Client( $api_key ) {
        $this->api_key = $api_key;
    }

    function story_from_id( $id ) {
        $response = $this->_api_request( array( 'id' => $id ) );
        if ( $response ) {
            foreach ( $response->list->story as $story ) {
                if ( $story->id == $id ) {
                    var_dump( $story );
                    return $story;
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
            $json = json_decode( wp_remote_retrieve_body( $response ) );
            if ( $json ) {
                return $json;
            }
            else {
                // XXX: Handle rror
            }
        }
    }


    function update_post_from_story( $story ) {
        $exists = new WP_Query( array( 'meta_key' => STORY_ID_META_KEY, 
                                       'meta_value' => $story->id ) );
        if ( $exists->post_count ) {
            // XXX: might be more than one here;
            $existing = $exists->post;
        }
            
        $text = '$text';

        $args = array(
            'post_title' => $story->title->$text,
            'post_content' => $this->_paragraphs_to_html( $story->textWithHtml ),
            'post_status' => 'draft',
        );
        $metas = array(
            STORY_ID_META_KEY => $story->id,
        );

        /*
         * if ( $story->byline ) {

        }
         */

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

        return $created;
    }


    protected function _paragraphs_to_html( $pgs ) {
        $grafs = array_map( 'text_from_paragraph', $pgs->paragraph );
        return implode( "\n\n", $grafs );
    }
}

function text_from_paragraph( $graf ) {
    $text = '$text';
    return $graf->$text;
}

?>
