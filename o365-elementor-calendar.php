<?php
/**
 * Plugin Name: O365 Elementor Calendar
 * Description: Microsoft 365 Outlook calendar integration for Elementor.
 * Version: 0.0.1
 * Author: Szurofka Márton
 * Text Domain: o365-calendar
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use O365Calendar\GraphAPI;
use O365Calendar\AjaxHandlers;
use O365Calendar\ElementorWidget;

// Alap osztályok betöltése
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'o365-calendar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    
    $graph_api = new GraphAPI();
    new AjaxHandlers( $graph_api );
});

// Elementor Widget regisztrálása
add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    $widgets_manager->register( new ElementorWidget() );
});

// A WP API nonce biztosítása az Elementor editor számára
add_action( 'elementor/editor/before_enqueue_scripts', function() {
    wp_enqueue_script( 'wp-api' );
} );