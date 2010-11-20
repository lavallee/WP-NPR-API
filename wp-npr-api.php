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
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Get NPR Stories</h2>
                URL or Story ID: <input type="text" name="url_or_story_id" value="" />
                <input type="submit" value="Create Draft" />


        <?php 
        //global $current_screen;
        //var_dump( $current_screen );
        ?>
        </div>
        <?php
    }

    function admin_menu() {
        add_posts_page( 'Get NPR Stories', 'Get NPR Stories', 'edit_posts', 'get-npr-stories', array( &$this, 'get_npr_stories' ) );
    }

    function NPR_API() {
        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
    }
}

new NPR_API;
