<?php

class JSON_Story_Converter {
    function convert( $api_story ) {
        $text = '$text'; // HACK to deal with API response keys
        //var_dump( $api_story );
        $story = new NPR_Story();

        $story->id         = $api_story->id;
        $story->title      = $api_story->title->$text;
        $story->content    = $this->_paragraphs_to_html( $api_story->textWithHtml );
        $story->teaser     = $api_story->teaser->$text;
        $story->html_link  = $this->_get_link_by_type( 'html', $api_story );
        $story->short_link = $this->_get_link_by_type( 'short', $api_story );
        $story->api_link   = $this->_get_link_by_type( 'api', $api_story );
        $story->story_date = strtotime($api_story->storyDate->$text);
        $story->pub_date   = strtotime($api_story->pubDate->$text);

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

        return $story;
    }

    protected function _paragraphs_to_html( $pgs ) {
        if ( $pgs->paragraph ) {
            $grafs = array_map( 'text_from_paragraph', $pgs->paragraph );
            return implode( "\n\n", $grafs );
        }
    }

    protected function _get_link_by_type( $type, $as ) {
        $text = '$text';
        foreach ( $as->link as $link ) {
            if ( $link->type == $type ) {
                return $link->$text;
            }
        }
    }

    protected function _minute_format( $seconds ) {
        $minutes = absint( $seconds ) / 60;
        $remain = absint( $seconds ) % 60;
        return sprintf( '%d:%02d', $minutes, $remain );
    }
}

function text_from_paragraph( $graf ) {
    $text = '$text';
    return $graf->$text;
}


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


    // Convenience methods
    public $body;
}

?>
