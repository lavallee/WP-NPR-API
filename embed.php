<?php
/*
 * Plugin Name: Embed for the NPR API
 * Description: Woo.
 * Version: 0.1-alpha
 * Author: Marc Lavallee and Andrew Nacin
 * License: GPLv2
 */

class NPR_API_Embed {
	function NPR_API_Embed() {
		add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
	}

	function plugins_loaded() {
		if ( ! defined( 'NPR_API_KEY_OPTION' ) )
			return;
		// http://www.npr.org/2010/11/20/131472499/hours-so-early-holiday-shoppers-stay-up-late
		wp_embed_register_handler( 'npr_api', '#http://www.npr.org/\d{4}/\d{1,2}/\d{1,2}/(\d{7,})/#i', array( &$this, 'embed_callback' ) );
		add_action( 'save_post', array( &$this, 'save_post' ), 10, 2 );
	}

	function save_post( $post_ID, $post ) {
		$post_metas = get_post_custom_keys( $post->ID );
		if ( empty( $post_metas ) )
			return;
		foreach ( $post_metas as $post_meta_key ) {
			if ( 0 === strpos( $post_meta_key, '_npr_api_' ) )
				delete_post_meta( $post->ID, $post_meta_key );
		}
	}

	function embed_callback( $matches, $attr, $url, $rawattr ) {
		$url = trim( $url );
		global $post;
		$story_object = get_post_meta( $post->ID, '_npr_api_' . md5( $url ), true );
		if ( empty( $story_object ) ) {
			$api = new NPR_API_Client( get_option( NPR_API_KEY_OPTION ) );
			$story_object = $api->story_from_id( absint( $matches[1] ) );
			update_post_meta( $post->ID, '_npr_api_' . md5( $url ), $story_object );
		}
		return 'From <a href="' . esc_url( $story_object->html_link ) . '">' . $story_object->title . '</a>:' . 
			"\n\n<blockquote>" . $story_object->teaser . "\n<cite><a href='" . esc_url( $story_object->short_link ) . "'>" . $story_object->short_link . '</a></cite></blockquote>';
	}
}
new NPR_API_Embed;
