<?php

/**
 * Plugin Name: Envelopes
 */

class ENVELOPES {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_options_menu' ) );
	}
	
	public static function add_options_menu() {
		add_menu_page( 'Envelopes', 'Envelopes', 'publish_posts', 'envelopes', basename(__FILE__), 'dashicons-email', 4 );
	}
}

add_action( 'init', array( 'ENVELOPES', 'init' ) );