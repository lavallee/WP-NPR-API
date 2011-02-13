<?php
/**
 * Plugin Name: NPR API
 * Description: A collection of tools for reusing content from NPR.org.
 * Version: 0.1
 * Author: Marc Lavallee and Andrew Nacin
 * License: GPLv2
 */

require_once( 'client.php' );
require_once( 'embed.php' );
require_once( 'settings.php' );
require_once( 'story.php' );

define( 'NPR_API_KEY_OPTION', 'npr_api_key' );

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
        // XXX: check to make sure the api key has been installed.
        
        if ( get_option( NPR_API_KEY ) ) { 
            $api = new NPR_API_Client( get_option( NPR_API_KEY_OPTION ) );
            $recent_stories = $api->recent_stories();
        }
        
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <form action="" method="POST">
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
            
            Enter an NPR Story ID: <input type="text" name="story_id" value="" />
            <input type="submit" value="Create Draft" />
            </form>
            
            <?php if ( $api ): ?>
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

function npr_api_handle_nprstory( $atts ) {
    global $post;

    extract( shortcode_atts( $atts, array() ) );
    $content = get_post_meta( $post->ID, 'npr_story_content', true );
    return apply_filters( 'the_content', $content );
}

add_shortcode( 'nprstory', 'npr_api_handle_nprstory' );


function npr_api_authorizer( $displayname ) {
    global $post;

    if ( get_post_meta( $post->ID, NPR_BYLINE_META_KEY, true ) ) {
        return get_post_meta( $post->ID, NPR_BYLINE_META_KEY, true );
    }
    else {
        return $displayname;
    }
}
add_filter( 'the_author', 'npr_api_authorizer', 11 );
