<?php
/**
 * Plugin Name: O365 Elementor Calendar
 * Description: Enterprise Microsoft 365 Calendar integration for Elementor. Displays FullCalendar, Agenda and Single Event widgets.
 * Version: 1.0.3
 * Author: MFÜI - Szurofka Márton
 * Author URI: https://uni.sze.hu
 * Text Domain: o365-elementor-calendar
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// 1. AUTOLOADER ÉS BIZTONSÁGI FÉK (FAIL-SAFE)
// ==========================================
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Ha hiányzik a vendor mappa (sérült vagy nyers ZIP letöltés), leállítjuk a betöltést!
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>O365 Calendar Hiba:</strong> A <code>vendor/</code> mappa hiányzik! Úgy tűnik, a nyers forráskód lett telepítve a lefordított (built) csomag helyett. A bővítmény biztonsági okokból leállt, hogy megelőzze az oldal összeomlását.</p></div>';
    });
    return; // Nincs tovább futás, nincs Fatal Error!
}

add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'o365-elementor-calendar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
});

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=o365-calendar-settings' ) ) . '"><strong>' . __( 'Beállítások', 'o365-elementor-calendar' ) . '</strong></a>';
    array_unshift( $links, $settings_link );
    return $links;
});

add_action( 'admin_post_o365_clear_cache', function() {
    if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'o365_clear_cache_nonce' ) ) {
        wp_die( 'Jogosulatlan hozzáférés.' );
    }
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_o365\_%' OR option_name LIKE '\_transient\_timeout\_o365\_%'" );
    wp_safe_redirect( add_query_arg( 'cache_cleared', '1', admin_url( 'options-general.php?page=o365-calendar-settings' ) ) );
    exit;
});

add_action( 'admin_menu', function() {
    add_options_page(
        __( 'O365 Naptár Beállítások', 'o365-elementor-calendar' ),
        __( 'O365 Naptár', 'o365-elementor-calendar' ),
        'manage_options',
        'o365-calendar-settings',
        'o365_render_settings_page'
    );
});

function o365_sync_accounts( $emails ) {
    if ( empty( $emails ) ) return;
    $emails = (array) $emails;
    $accounts = get_option( 'o365_accounts', [] );
    $api = new \O365Calendar\API\GraphAPI();
    $success_count = 0;

    foreach ( $emails as $email ) {
        if ( ! isset( $accounts[ $email ] ) ) continue;
        
        $cals_response = $api->get_calendars( $email );
        $cats_response = $api->get_master_categories( $email );

        $parsed_cals = [];
        if ( ! is_wp_error( $cals_response ) && isset( $cals_response['value'] ) ) {
            foreach ( $cals_response['value'] as $cal ) {
                $parsed_cals[ $cal['id'] ] = $cal['name'];
            }
        }

        $parsed_cats = [];
        if ( ! is_wp_error( $cats_response ) && isset( $cats_response['value'] ) ) {
            foreach ( $cats_response['value'] as $cat ) {
                $parsed_cats[] = $cat['displayName'];
            }
        }

        if ( ! empty( $parsed_cals ) ) {
            $accounts[ $email ]['calendars'] = $parsed_cals;
            $accounts[ $email ]['categories'] = $parsed_cats;
            $accounts[ $email ]['updated_at'] = current_time( 'mysql' );
            $success_count++;
        }
    }
    update_option( 'o365_accounts', $accounts );
    return $success_count;
}

function o365_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $accounts = get_option( 'o365_accounts', [] );

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['o365_admin_nonce'] ) && wp_verify_nonce( $_POST['o365_admin_nonce'], 'o365_admin_action' ) ) {
        
        if ( isset( $_POST['o365_delete_account'] ) ) {
            $email = sanitize_email( $_POST['o365_delete_account'] );
            unset( $accounts[ $email ] );
            update_option( 'o365_accounts', $accounts );
            echo '<div class="notice notice-success is-dismissible"><p>Fiók törölve.</p></div>';
        }
        elseif ( isset( $_POST['o365_sync_account'] ) ) {
            o365_sync_accounts( $_POST['o365_sync_account'] );
            echo '<div class="notice notice-success is-dismissible"><p>Fiók adatai frissítve.</p></div>';
            $accounts = get_option( 'o365_accounts', [] );
        }
        elseif ( isset( $_POST['o365_sync_all'] ) ) {
            o365_sync_accounts( array_keys( $accounts ) );
            echo '<div class="notice notice-success is-dismissible"><p>Minden fiók szinkronizálva.</p></div>';
            $accounts = get_option( 'o365_accounts', [] );
        }
        elseif ( isset( $_POST['o365_bulk_action'] ) && $_POST['o365_bulk_action'] !== '-1' && !empty( $_POST['o365_bulk_accounts'] ) ) {
            $selected_emails = array_map( 'sanitize_email', $_POST['o365_bulk_accounts'] );
            $action = $_POST['o365_bulk_action'];

            if ( $action === 'delete' ) {
                foreach ( $selected_emails as $email ) { unset( $accounts[ $email ] ); }
                update_option( 'o365_accounts', $accounts );
                echo '<div class="notice notice-success is-dismissible"><p>Kiválasztott fiókok törölve.</p></div>';
            } elseif ( $action === 'sync' ) {
                o365_sync_accounts( $selected_emails );
                echo '<div class="notice notice-success is-dismissible"><p>Kiválasztott fiókok frissítve.</p></div>';
                $accounts = get_option( 'o365_accounts', [] );
            }
        }
    }

    $total_calendars = 0;
    foreach($accounts as $acc) { $total_calendars += count($acc['calendars'] ?? []); }
    global $wpdb;
    $cache_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_o365\_%'" );

    ?>
    <div class="wrap">
        <h1><?php _e( 'O365 Naptár Integráció', 'o365-elementor-calendar' ); ?></h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #0073aa;">
                <div style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Hitelesített Fiókok</div>
                <div style="font-size: 28px; font-weight: bold; margin-top: 5px;"><?php echo count($accounts); ?></div>
            </div>
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #46b450;">
                <div style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Elérhető Naptárak</div>
                <div style="font-size: 28px; font-weight: bold; margin-top: 5px;"><?php echo $total_calendars; ?></div>
            </div>
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #f1c40f;">
                <div style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Aktív Cache elemek</div>
                <div style="font-size: 28px; font-weight: bold; margin-top: 5px;"><?php echo $cache_count; ?></div>
            </div>
        </div>

        <form method="POST">
            <?php wp_nonce_field( 'o365_admin_action', 'o365_admin_nonce' ); ?>
            
            <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                
                <div style="flex: 2; min-width: 450px; background:#fff; border:1px solid #ccd0d4; padding:25px; border-radius:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h2 style="margin:0;">Fiókok kezelése</h2>
                        <div style="display:flex; gap:10px;">
                            <button type="submit" name="o365_sync_all" class="button" onclick="return confirm('Minden fiók metaadatát frissíted a Microsofttól. Folytatod?');">
                                <span class="dashicons dashicons-update" style="margin-top:4px;"></span> Összes frissítése
                            </button>
                            <button type="button" id="o365-trigger-wizard" class="button button-primary"><i class="dashicons dashicons-plus" style="margin-top:4px;"></i> Új fiók hozzáadása</button>
                        </div>
                    </div>

                    <?php if ( empty( $accounts ) ) : ?>
                        <p style="padding:40px; text-align:center; color:#888; border:2px dashed #eee; border-radius:8px;">Még nincs csatlakoztatott fiók.</p>
                    <?php else : ?>
                        
                        <div style="margin-bottom:15px; display:flex; gap:10px; align-items:center;">
                            <select name="o365_bulk_action">
                                <option value="-1">Csoportos művelet</option>
                                <option value="sync">Kiválasztottak frissítése</option>
                                <option value="delete">Kiválasztottak törlése</option>
                            </select>
                            <button type="submit" class="button action">Alkalmaz</button>
                        </div>

                        <table class="wp-list-table widefat fixed striped" style="border:none;">
                            <thead>
                                <tr>
                                    <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></td>
                                    <th>Email</th>
                                    <th>Naptárak</th>
                                    <th>Utolsó szinkron</th>
                                    <th style="width:140px; text-align:right;">Műveletek</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $accounts as $email => $data ) : ?>
                                    <tr>
                                        <th scope="row" class="check-column"><input type="checkbox" name="o365_bulk_accounts[]" value="<?php echo esc_attr( $email ); ?>"></th>
                                        <td><strong><?php echo esc_html( $email ); ?></strong></td>
                                        <td><?php echo count( $data['calendars'] ?? [] ); ?> naptár</td>
                                        <td style="font-size:11px; color:#666;"><?php echo esc_html( $data['updated_at'] ?? '-' ); ?></td>
                                        <td style="text-align:right;">
                                            <div style="display:flex; justify-content:flex-end; gap:10px;">
                                                <button type="submit" name="o365_sync_account" value="<?php echo esc_attr( $email ); ?>" class="button-link" style="color:#46b450; text-decoration:none;">
                                                    Frissítés
                                                </button>
                                                <button type="submit" name="o365_delete_account" value="<?php echo esc_attr( $email ); ?>" class="button-link-delete" style="color:#d63638; cursor:pointer; text-decoration:none;" onclick="return confirm('Biztosan törlöd?');">
                                                    Törlés
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div style="flex: 1; min-width: 300px; display:flex; flex-direction:column; gap:20px;">
                    <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:8px;">
                        <h3 style="margin-top:0;">Rendszerállapot</h3>
                        <ul style="margin-bottom:0;">
                            <?php 
                            $check = [
                                'Tenant ID' => defined('O365_TENANT_ID'),
                                'Client ID' => defined('O365_CLIENT_ID'),
                                'Secret'    => defined('O365_CLIENT_SECRET'),
                                'Sender'    => defined('O365_SENDER_EMAIL'),
                            ];
                            foreach($check as $label => $ok): ?>
                                <li style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span><?php echo $label; ?></span>
                                    <span style="color:<?php echo $ok ? '#46b450':'#d63638'; ?>; font-weight:bold;"><?php echo $ok ? '✔ OK' : '✘ HIÁNYZIK'; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:8px;">
                        <h3 style="margin-top:0;">Gyorsítótár</h3>
                        <p style="font-size:12px; color:#666;">Az események adatait 15 percig tároljuk. Itt kényszerítheted a teljes ürítést.</p>
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=o365_clear_cache' ), 'o365_clear_cache_nonce' ) ); ?>" class="button" style="width:100%; text-align:center;">Cache ürítése</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#cb-select-all-1').on('change', function() {
            $('input[name="o365_bulk_accounts[]"]').prop('checked', this.checked);
        });
    });
    </script>
    <?php
}

add_action( 'admin_enqueue_scripts', function($hook) {
    if ( 'settings_page_o365-calendar-settings' !== $hook ) return;
    wp_enqueue_script( 'o365-editor-script', plugins_url( 'assets/js/editor.js', __FILE__ ), [ 'jquery' ], '1.0.0', true );
    wp_localize_script( 'o365-editor-script', 'o365_editor_globals', [ 'nonce' => wp_create_nonce( 'wp_rest' ) ] );
});

add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    if ( ! did_action( 'elementor/loaded' ) ) return;
    $widgets_manager->register( new \O365Calendar\Widgets\CalendarWidget() );
    $widgets_manager->register( new \O365Calendar\Widgets\AgendaWidget() );
    $widgets_manager->register( new \O365Calendar\Widgets\SingleEventWidget() );
});

add_action( 'wp_enqueue_scripts', function() {
    $asset = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php' );
    wp_register_style( 'o365-calendar-style', plugins_url( 'build/style-index.css', __FILE__ ), [], $asset['version'] );
    wp_register_script( 'o365-calendar-script', plugins_url( 'build/index.js', __FILE__ ), $asset['dependencies'], $asset['version'], true );
    wp_localize_script( 'o365-calendar-script', 'o365_locales', [ 'locale' => str_replace('_', '-', get_locale()) ] );
    wp_enqueue_style( 'o365-calendar-style' );
});

add_action( 'elementor/editor/after_enqueue_scripts', function() {
    wp_enqueue_script( 'o365-editor-script', plugins_url( 'assets/js/editor.js', __FILE__ ), [ 'jquery', 'wp-api-fetch' ], '1.0.0', true );
    wp_localize_script( 'o365-editor-script', 'o365_editor_globals', [ 'nonce' => wp_create_nonce( 'wp_rest' ) ] );
});

new \O365Calendar\API\AjaxHandlers();

if ( class_exists( 'YahnisElsts\PluginUpdateChecker\V5\PucFactory' ) ) {
    $updateChecker = \YahnisElsts\PluginUpdateChecker\V5\PucFactory::buildUpdateChecker( 
        'https://github.com/szkk-szucs/o365-elementor-calendar/', 
        __FILE__, 
        'o365-elementor-calendar' 
    );
    
    // Szigorú szűrés: CSAK azt az assetet töltheti le, aminek a neve 'o365-elementor-calendar.zip'
    $updateChecker->getVcsApi()->enableReleaseAssets('/o365-elementor-calendar\.zip/i');
}