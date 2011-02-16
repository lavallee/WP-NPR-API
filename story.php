<?php
/**
 * A value object representing a single NPR Story from the NPR API.
 */
class NPR_Story {
    // Fields from NPR API
    public $id;
    public $html_link;
    public $short_link;
    public $api_link;
    public $title;
    //public $subtitle;
    //public $shortTitle;
    public $teaser;
    //public $miniTeaser;
    //public $slug;
    //public $thumbnail;
    //public $toenail;
    public $story_date;
    public $pub_date;
    public $last_modified_date;
    public $keywords;
    public $priority_keywords;
    //public $organization;
    public $audio;
    //public $image;
    //public $related_link;
    //public $pull_quote;
    public $byline;

    // Convenience methods
    public $body;
}

/**
 * Converts JSON data structures returned by the NPR API into NPR_Story
 * objects.
 *
 * @todo factor the non-JSON-specific converter methods into a superclass or 
 * mixin.
 */
class JSON_Story_Converter {
    function convert( $api_story ) {
        $text = '$text'; // HACK to deal with $s in API response keys
        $story = new NPR_Story();

        $story->id         = $api_story->id;
        $story->title      = $api_story->title->$text;
        $story->content    = $this->_paragraphs_to_html( 
            $api_story->textWithHtml );
        $story->teaser     = $api_story->teaser->$text;
        $story->html_link  = $this->_get_link_by_type( 'html', $api_story );
        $story->short_link = $this->_get_link_by_type( 'short', $api_story );
        $story->api_link   = $this->_get_link_by_type( 'api', $api_story );
        $story->story_date = strtotime( $api_story->storyDate->$text );
        $story->pub_date   = strtotime( $api_story->pubDate->$text );
        $story->byline     = $this->_handle_byline( $api_story->byline );

        /*
        if ( $api_story->audio ) {
            // XXX: only deal with the primary clip for now
            foreach ( $api_story->audio as $clip ) {
                if ( $clip->type == 'primary' ) {
                    $story->audio = array( 
                        'id'          => $clip->id, 
                        'title'       => $clip->title,
                        'description' => $clip->description,
                        'mp3'         => $clip->format->mp3->$text,
                        'duration'    => $this->_minute_format( $clip->duration->$text ),
                    );
                }
            }
        }
         */

        return $story;
    }


    protected function _paragraphs_to_html( $pgs ) {
        if ( $pgs->paragraph ) {
            $grafs = array_map( 'text_from_paragraph', $pgs->paragraph );
            return implode( "\n\n", $grafs );
        }
    }


    protected function _get_link_by_type( $type, $as ) {
        $text = '$text'; // HACK to deal with $s in API response keys
        foreach ( $as->link as $link ) {
            if ( $link->type == $type ) {
                return $link->$text;
            }
        }
    }


    protected function _handle_byline( $byline ) {
        $text = '$text'; // HACK to deal with $s in API response keys

        $people = array();
        foreach ( $byline as $person ) {
            $people[] = $person->name->$text;
        }
        // @todo add logic for 3 or more authors
        return implode( ' and ', $people );
    }


    protected function _minute_format( $seconds ) {
        $minutes = absint( $seconds ) / 60;
        $remain = absint( $seconds ) % 60;
        return sprintf( '%d:%02d', $minutes, $remain );
    }
}

/**
 * @todo see about putting this in the Coverter class and passing something
 * else to array_map.
 */
function text_from_paragraph( $graf ) {
    $text = '$text'; // HACK to deal with $s in API response keys
    return $graf->$text;
}


class Story_Poster {
    /**
     * Creates or updates a WordPress post with a story object.
     *
     * @param object NPR_Story object.
     * @return array created true|false, WordPress Post ID.
     */
    function update_post_from_story( $story ) {
        $exists = new WP_Query( array( 'meta_key' => NPR_STORY_ID_META_KEY, 
                                       'meta_value' => $story->id ) );
        if ( $exists->post_count ) {
            // XXX: might be more than one here;
            $existing = $exists->post;
        }
        else {
            $existing = null;
        }

        $args = array(
            'post_title'   => $story->title,
            'post_excerpt' => $story->teaser,
            'post_content' => $this->_set_or_update_content($story, $existing ),
        );
        if ( ! $existing ) {
            $args['post_status'] = 'draft';
        }

        $metas = array(
            NPR_STORY_ID_META_KEY      => $story->id,
            NPR_API_LINK_META_KEY      => $story->api_link,
            NPR_HTML_LINK_META_KEY     => $story->html_link,
            NPR_SHORT_LINK_META_KEY    => $story->short_link,
            NPR_STORY_CONTENT_META_KEY => $story->content,
            NPR_BYLINE_META_KEY        => $story->byline,
        );
        /*
        if ( $story->audio ) {
            $metas[ AUDIO_META_KEY ] = serialize( $story->audio );
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

        return array( $created, $id );
    }


    /**
     * Looks for the nprstory shortcode in existing content and
     * appends/inserts as necessary.
     * 
     * @param $story NPR_Story object.
     * @param $existing 
     */
    function _set_or_update_content( $story, $existing ) {
        $story_shortcode = '[nprstory id="' . $story->id . '"]';

        if ( $existing ) {
            // Look for [nprstory] in the content. 
            // If it's there, leave it alone
            $old_content = $existing->post_content;
            if ( preg_match( '/\[\s*nprstory\s+id\s*=\s*"(\d+)"\s*\]/',
                $old_content ) ) {
                    return $old_content;
            }
            // Otherwise, (re)insert it at the end.
            else {
                return $old_content . "\n" . $story_shortcode;
            }
        }
        else {
            return $story_shortcode;
        }
    }
}


?>
