<?php

class JSON_Story_Converter {
    function convert( $api_story ) {
        $text = '$text'; // HACK to deal with API response keys
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
    //public $audio;
    //public $image;
    //public $related_link;
    //public $pull_quote;


    // Convenience methods
    public $body;
}

?>
