<?php
/*
 * Plugin Name: NPR API
 * Description: Woo.
 * Version: 0.1-alpha
 * Author: Marc Lavallee and Andrew Nacin
 * License: GPLv2
 */

require_once( 'client.php' );

class NPR_API {
    var $api_key = '';

    function get_npr_stories() {
        // XXX: check to make sure the api key has been installed.
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <form action="" method="POST">
            <h2>Get NPR Stories</h2>
            Enter an NPR Story ID: <input type="text" name="story_id" value="" />
            <input type="submit" value="Create Draft" />
            </form>
       </div>
        <?php
    }

    function load_page_hook() {
        if ( isset( $_POST ) && isset( $_POST[ 'story_id' ] ) ) {
            $story_id = absint( $_POST[ 'story_id' ] );

            // XXX: check that the API key is actually set
            $api = new NPR_API_Client( get_option( 'npr_api_key' ) );
            $story = $api->story_from_id( $story_id );
            if ( ! $story ) {
                // XXX: handle error
                return;
            }
            
            $created = $api->update_post_from_story( $story );
            if ( $created ) {
                echo 'Post created as a draft';
            }
            else {
                echo 'Existing post updated';
            }
        }
    }

    function admin_menu() {
        add_posts_page( 'Get NPR Stories', 'Get NPR Stories', 'edit_posts', 'get-npr-stories', array( &$this, 'get_npr_stories' ) );
    }

    function NPR_API() {
        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
        add_action( 'load-posts_page_get-npr-stories', array( &$this, 'load_page_hook' ) );
    }
}

new NPR_API;
