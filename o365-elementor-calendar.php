<?php
/**
 * Plugin Name: O365 Elementor Calendar
 * Description: Enterprise Microsoft 365 Calendar integration for Elementor. Displays FullCalendar, Agenda and Single Event widgets.
 * Version: 1.0.0
 * Author: SZKK Szucs
 * Text Domain: o365-elementor-calendar
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Composer Autoloader betöltése
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// 2. Szövegdomain (i18n) betöltése
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'o365-elementor-calendar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
});

// 3. Biztonsági Ellenőrzés: Megvannak-e a szükséges O365 konstansok a wp-config.php-ban?
add_action( 'admin_notices', function() {
    $required_constants = [ 'O365_TENANT_ID', 'O365_CLIENT_ID', 'O365_CLIENT_SECRET', 'O365_SENDER_EMAIL' ];
    $missing = [];

    foreach ( $required_constants as $const ) {
        if ( ! defined( $const ) || empty( constant( $const ) ) ) {
            $missing[] = $const;
        }
    }

    if ( ! empty( $missing ) ) {
        $class = 'notice notice-error';
        $message = sprintf( 
            /* translators: %s: Missing constants comma separated */
            __( 'O365 Elementor Calendar Error: The following required constants are missing or empty in wp-config.php: <strong>%s</strong>', 'o365-elementor-calendar' ), 
            implode( ', ', $missing ) 
        );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
    }
});

// 4. Elementor Widgetek Regisztrálása
add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    if ( ! did_action( 'elementor/loaded' ) ) return;

    $widgets_manager->register( new \O365Calendar\Widgets\CalendarWidget() );
    $widgets_manager->register( new \O365Calendar\Widgets\AgendaWidget() );
    $widgets_manager->register( new \O365Calendar\Widgets\SingleEventWidget() );
});

// 5. Frontend Scriptek & Stílusok Regisztrálása
add_action( 'wp_enqueue_scripts', function() {
    $asset_file = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php' );
    
    wp_register_style(
        'o365-calendar-style',
        plugins_url( 'build/style-index.css', __FILE__ ),
        [],
        $asset_file['version']
    );

    wp_register_script(
        'o365-calendar-script',
        plugins_url( 'build/index.js', __FILE__ ),
        $asset_file['dependencies'],
        $asset_file['version'],
        true
    );

    wp_localize_script( 'o365-calendar-script', 'o365_locales', [
        'locale' => str_replace('_', '-', get_locale())
    ]);

    wp_enqueue_style( 'o365-calendar-style' );
});

// 6. Elementor Editor Scriptek (Admin felület)
add_action( 'elementor/editor/after_enqueue_scripts', function() {
    wp_enqueue_script(
        'o365-editor-script',
        plugins_url( 'assets/js/editor.js', __FILE__ ),
        [ 'jquery', 'wp-api-fetch' ], // A wp-api-fetch beépítve kezeli a nonce-t!
        filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/editor.js' ),
        true
    );
    
    // Biztonsági (Nonce) kulcs átadása, ha az editor.js natív fetch-et használna
    wp_localize_script( 'o365-editor-script', 'o365_editor_globals', [
        'nonce' => wp_create_nonce( 'wp_rest' )
    ]);
});

// 7. REST API Handlerek Inicializálása (Minden esetben fusson le, ne csak adminban!)
new \O365Calendar\API\AjaxHandlers();