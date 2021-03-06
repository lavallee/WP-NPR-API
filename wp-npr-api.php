<?php
/**
 * Plugin Name: NPR API
 * Description: A collection of tools for reusing content from NPR.org.
 * Version: 0.1
 * Author: Marc Lavallee 
 * License: GPLv2
*/
/*
    Copyright 2011 Marc Lavallee  (email : mlavallee@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define( 'NPR_API_KEY_OPTION', 'npr_api_key' );
define( 'NPR_STORY_ID_META_KEY', 'npr_story_id' );
define( 'NPR_API_LINK_META_KEY', 'npr_api_link' );
define( 'NPR_HTML_LINK_META_KEY', 'npr_html_link' );
define( 'NPR_SHORT_LINK_META_KEY', 'npr_short_link' );
define( 'NPR_STORY_CONTENT_META_KEY', 'npr_story_content' );
define( 'NPR_BYLINE_META_KEY', 'npr_byline' );
define( 'NPR_AUDIO_META_KEY', '_npr_audio_clip' );

require_once( 'client.php' );
require_once( 'embed.php' );
require_once( 'settings.php' );
require_once( 'story.php' );

class NPR_API {
    var $created_message = '';

    function load_page_hook() {
        if ( isset( $_POST ) && isset( $_POST[ 'story_id' ] ) ) {
            $story_id = absint( $_POST[ 'story_id' ] );
            
        }
        else if ( isset( $_GET[ 'create_draft' ] ) && isset( $_GET[ 'story_id' ] ) ) {
            $story_id = absint( $_GET[ 'story_id' ] );
        }
        
        if ( isset( $story_id ) ) {

            // XXX: check that the API key is actually set
            $api = new NPR_API_Client( get_option( NPR_API_KEY_OPTION ) );
            $story = $api->story_from_id( $story_id );
            if ( ! $story ) {
                // XXX: handle error
                return;
            }
            
            $poster = new Story_Poster;
            $resp = $poster->update_post_from_story( $story );
            $created = $resp[0];
            $id = $resp[1];

            if ( $created ) {
                $msg = sprintf( 'Created <a href="%s">%s</a> as a Draft.',  get_edit_post_link( $id ), $story->title );
            }
            else {
                $msg = sprintf( 'Updated <a href="%s">%s</a>.', get_edit_post_link( $id ), $story->title );
            }
            $this->created_message = $msg;
        }
    }

    function get_npr_stories() {
        global $is_IE;
        $api = new NPR_API_Client( get_option( NPR_API_KEY_OPTION ) );
?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Get NPR Stories</h2>
            <?php if ( ! $api ) : ?>
                <div class="error">
                    <p>You don't currently have an API key set.  <a href="<?php menu_page_url( 'npr_api' ); ?>">Set your API key here.</a></p>
                </div>
            <?php endif; 
            if ( ( isset( $_POST ) and isset( $_POST[ 'story_id' ] ) ) || ( isset( $_GET['create_draft'] ) && isset( $_GET['story_id'] ) ) ): ?>
                <div class="updated">
                    <p><?php echo $this->created_message; ?></p>
                </div>
            <?php endif; ?>

            <div style="float: left;">
                <form action="" method="POST">
                    Enter an NPR Story ID: <input type="text" name="story_id" value="" />
                    <input type="submit" value="Create Draft" />
                </form>
            </div>
 

            <div style="float: right; width: 450px;">
            <p>
            <?php // @todo move inline style and javascript somewhere better ?>
                <!-- Thank you to Marco Arment of Instapaper, from where the bookmarklet style and UX have been lifted. -->
                <style>
                    .bookmarklet {
                        display: inline-block;
                        font-family: 'Lucida Grande', Verdana, sans-serif;
                        font-weight: bold;
                        font-size: 11px;
                        -webkit-border-radius: 8px;
                        -moz-border-radius: 8px;
                        border-radius: 8px;
                        color: #fff;
                        background-color: #626262;
                        border: 1px solid #626262;
                        padding: 0px 7px 1px 7px;
                        text-shadow: #3b3b3b 1px 1px 0px;
                        min-width: 62px;
                        text-align: center;
                        text-decoration: none;
                        vertical-align: 2px;
                    }
                </style>
                <script>
                    function explain_bookmarklet() {
                        <?php if ( $is_IE ): ?>
                            alert( 'To use this bookmarklet, right-click on this button and choose "Add to favorites."' );
                        <?php else: ?>
                            alert( 'To use this bookmarklet, drag it to your browser\'s bookmarks bar.' );
                        <?php endif; ?>
                        return false;
                    }
                </script>
                Import stories from NPR.org to your WordPress blog with this bookmarklet. To install:<br /> 
                <strong><?php echo ( $is_IE ) ? 'Right-click this button' : 'Drag this button'; ?></strong>
                <a class="bookmarklet" onClick="return explain_bookmarklet();" title="Import a story from NPR.org to your WordPress blog." href="javascript:(function(){document.body.appendChild(document.createElement('script')).src='<?php echo plugin_dir_url(__FILE__); ?>bookmarklet-handler.php';})();">Add NPR story to WP</a>
                <?php echo ( $is_IE ) ? ' and choose "Add to favorites."' : 'to your browser\'s bookmarks bar.'; ?>
            </p>
            </div>
           
            <?php if ( $api ): 
                $recent_stories = $api->recent_stories();
            ?>
            <div class="tablenav">
                <div class="alignleft actions">
                    <p class="displaying-num">Displaying <?php echo count($recent_stories) ?> recent stories.</p>
                </div>
            </div>
            
            <hr />
            
            <table cellspacing="0" id="install-plugins" class="widefat" style="clear:none;">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Date</th>
                        <th scope="col">Description</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Date</th>
                        <th scope="col">Description</th>
                        <th scope="col">Actions</th>
                    </tr>
                </tfoot>
                <tbody>
                <?php foreach( $recent_stories as $story ): ?>
                        <tr>
                            <td class="name">
                                <strong><a href="<?php echo $story->html_link ?>" title="<?php echo $story->title ?>" target="_blank">
                                    <?php echo $story->title ?>
                                </a></strong>
                            </td>
                            <td class='date'><?php echo strftime('%m/%d/%Y', $story->story_date) ?>
                            <td class='description'><?php echo $story->teaser ?></td>
                            
                            <td class="actions" style="width:100px">
                                <a href="<?php echo add_query_arg( array('story_id' => $story->id, 'create_draft' => 'true' ), menu_page_url( 'get-npr-stories', false ) ) ?>">
                                    Save to Drafts
                                </a>
                            </td>
                        </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
       </div>
        <?php
    }

    function admin_menu() {
        add_posts_page( 'Get NPR Stories', 'Get NPR Stories', 'edit_posts', 'get-npr-stories', array( &$this, 'get_npr_stories' ) );
    }

    function embed_audio_clip() {
        global $post;
        if ( has_meta( $post, AUDIO_META_KEY ) ) {
            $clip = unserialize();
        }
    }

    function NPR_API() {
        if ( ! is_admin() ) {
            //add_action( 'the_content', array( &$this, 'embed_audio_clip' ) );
            return;
        }

        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
        add_action( 'load-posts_page_get-npr-stories', array( &$this, 'load_page_hook' ) );
    }
}

new NPR_API;

/**
 * Shortcode handler for NPR story content.
 *
 * @global $post current WP Post object.
 */
function npr_api_handle_nprstory( $atts ) {
    global $post;

    extract( shortcode_atts( $atts, array() ) );
    $content = get_post_meta( $post->ID, 'npr_story_content', true );
    return apply_filters( 'the_content', $content );
}
add_shortcode( 'nprstory', 'npr_api_handle_nprstory' );


/**
 * Replaces the author line of a post with the appropriate NPR byline.
 *
 * @global $post Current WP Post object.
 */
function npr_api_author_filter( $displayname ) {
    global $post;

    if ( get_post_meta( $post->ID, NPR_BYLINE_META_KEY, true ) ) {
        return get_post_meta( $post->ID, NPR_BYLINE_META_KEY, true );
    }
    else {
        return $displayname;
    }
}
add_filter( 'the_author', 'npr_api_author_filter', 11 );
