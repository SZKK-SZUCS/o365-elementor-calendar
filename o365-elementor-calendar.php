<?php
/**
 * Plugin Name: O365 Elementor Calendar
 * Description: Microsoft 365 Outlook calendar integration for Elementor.
 * Version: 1.0.0
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

add_action( 'elementor/frontend/after_register_scripts', function() {
    $js_file_path = plugin_dir_path( __FILE__ ) . 'build/index.js';
    $js_file_url  = plugins_url( 'build/index.js', __FILE__ );
    
    $js_version = file_exists( $js_file_path ) ? filemtime( $js_file_path ) : '1.0.0';

    wp_register_script(
        'o365-calendar-script',
        $js_file_url,
        [ 'jquery' ],
        $js_version,
        true
    );
} );

// A WP API nonce biztosítása az Elementor editor számára
add_action( 'elementor/editor/before_enqueue_scripts', function() {
    wp_enqueue_script( 'wp-api' );
} );

add_action( 'elementor/frontend/after_enqueue_styles', function() {
    $css_file_path = plugin_dir_path( __FILE__ ) . 'build/style-index.css'; // A WP default scss kimenet gyakran style-index.css
    $css_file_url  = plugins_url( 'build/style-index.css', __FILE__ );
    
    if ( file_exists( $css_file_path ) ) {
        wp_enqueue_style(
            'o365-calendar-style',
            $css_file_url,
            [],
            filemtime( $css_file_path )
        );
    } else {
        // Fallback, ha index.css néven jönne létre
        $fallback_path = plugin_dir_path( __FILE__ ) . 'build/index.css';
        if ( file_exists( $fallback_path ) ) {
            wp_enqueue_style( 'o365-calendar-style', plugins_url( 'build/index.css', __FILE__ ), [], filemtime( $fallback_path ) );
        }
    }
} );

add_action( 'elementor/editor/after_enqueue_scripts', function() {
    $editor_js_path = plugin_dir_path( __FILE__ ) . 'assets/js/editor.js';
    $editor_js_url  = plugins_url( 'assets/js/editor.js', __FILE__ );
    
    wp_enqueue_script(
        'o365-elementor-editor-script',
        $editor_js_url,
        [ 'jquery', 'elementor-editor' ],
        file_exists( $editor_js_path ) ? filemtime( $editor_js_path ) : '1.0.0',
        true
    );

    // Ez a sor átadja a biztonsági kulcsot a külső JS fájlnak
    wp_localize_script( 'o365-elementor-editor-script', 'o365EditorConfig', [
        'nonce' => wp_create_nonce( 'wp_rest' )
    ] );
});