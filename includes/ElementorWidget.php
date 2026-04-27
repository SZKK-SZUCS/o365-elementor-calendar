<?php
namespace O365Calendar;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ElementorWidget extends Widget_Base {

    public function get_name() {
        return 'o365_calendar';
    }

    public function get_title() {
        return __( 'O365 Calendar', 'o365-calendar' );
    }

    public function get_icon() {
        return 'eicon-calendar';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Naptár Beállítások', 'o365-calendar' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'target_email',
            [
                'label'       => __( 'O365 Email Cím', 'o365-calendar' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => 'naptar.tulajdonos@domain.com',
                'description' => __( 'Add meg az email címet, amelynek a naptárát meg akarod jeleníteni.', 'o365-calendar' ),
            ]
        );

        // Rejtett mező a kiválasztott naptár ID-jának tárolására
        $this->add_control(
            'calendar_id',
            [
                'label'   => __( 'Naptár ID (Rejtett)', 'o365-calendar' ),
                'type'    => Controls_Manager::TEXT,
                'classes' => 'elementor-hidden-control', // Ezt az Elementor alapból elrejti
            ]
        );

        // Rejtett mező a naptár nevének tárolására (hogy lássuk, mi van kiválasztva)
        $this->add_control(
            'calendar_name',
            [
                'label'    => __( 'Kiválasztott Naptár', 'o365-calendar' ),
                'type'     => Controls_Manager::TEXT,
                'readonly' => true,
            ]
        );

        // A custom hitelesítő UI és JS logika
        $this->add_control(
            'auth_ui',
            [
                'label' => '',
                'type'  => Controls_Manager::RAW_HTML,
                'raw'   => $this->get_auth_ui_html(),
            ]
        );

        $this->add_control(
            'default_view',
            [
                'label'   => __( 'Alapértelmezett Nézet', 'o365-calendar' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'dayGridMonth',
                'options' => [
                    'dayGridMonth' => __( 'Havi', 'o365-calendar' ),
                    'timeGridWeek' => __( 'Heti', 'o365-calendar' ),
                    'listWeek'     => __( 'Lista (Heti)', 'o365-calendar' ),
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Ez a HTML és JS csak az Elementor szerkesztőjében jelenik meg.
     */
    private function get_auth_ui_html() {
        ob_start();
        ?>
        <div id="o365-auth-wrapper" style="padding: 10px; background: #f1f1f1; border-radius: 4px; margin-top: 10px;">
            <button type="button" id="o365-btn-send-code" class="elementor-button elementor-button-default" style="width: 100%; margin-bottom: 10px;">
                <?php _e( '1. Hitelesítő Kód Küldése', 'o365-calendar' ); ?>
            </button>
            
            <div id="o365-verify-step" style="display: none;">
                <input type="text" id="o365-input-code" placeholder="<?php _e( '6 számjegyű kód', 'o365-calendar' ); ?>" style="width: 100%; margin-bottom: 10px;">
                <button type="button" id="o365-btn-verify" class="elementor-button elementor-button-success" style="width: 100%; margin-bottom: 10px;">
                    <?php _e( '2. Kód Ellenőrzése & Naptárak Lekérése', 'o365-calendar' ); ?>
                </button>
            </div>

            <div id="o365-select-step" style="display: none;">
                <select id="o365-select-calendar" style="width: 100%; margin-bottom: 10px;"></select>
                <button type="button" id="o365-btn-save-calendar" class="elementor-button elementor-button-info" style="width: 100%;">
                    <?php _e( '3. Naptár Kiválasztása', 'o365-calendar' ); ?>
                </button>
            </div>

            <div id="o365-auth-message" style="margin-top: 10px; font-size: 12px; color: #d00;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Elementor panel újrarenderelésekor lefut
            const wrapper = $('#o365-auth-wrapper');
            if (wrapper.length === 0) return;

            const msgBox = $('#o365-auth-message');
            const nonce = '<?php echo wp_create_nonce("wp_rest"); ?>'; // Ha a végpont igényelne nonce-ot, bár a permission_callback a cookie-t nézi

            $('#o365-btn-send-code').on('click', function(e) {
                e.preventDefault();
                // Megkeressük az Elementor által renderelt input mezőt a target_email controlhoz
                const email = $('input[data-setting="target_email"]').val();
                if (!email) {
                    msgBox.text('<?php _e("Kérlek, előbb írj be egy email címet!", "o365-calendar"); ?>');
                    return;
                }

                msgBox.text('<?php _e("Kód küldése folyamatban...", "o365-calendar"); ?>').css('color', '#000');
                
                $.ajax({
                    url: '/wp-json/o365cal/v1/auth/request-code',
                    method: 'POST',
                    data: { email: email },
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce); } // WP globális nonce
                }).done(function(response) {
                    msgBox.text(response.message).css('color', 'green');
                    $('#o365-btn-send-code').hide();
                    $('#o365-verify-step').show();
                }).fail(function(xhr) {
                    msgBox.text(xhr.responseJSON?.message || 'Hiba történt.').css('color', 'red');
                });
            });

            $('#o365-btn-verify').on('click', function(e) {
                e.preventDefault();
                const email = $('input[data-setting="target_email"]').val();
                const code = $('#o365-input-code').val();
                
                msgBox.text('<?php _e("Ellenőrzés...", "o365-calendar"); ?>').css('color', '#000');

                $.ajax({
                    url: '/wp-json/o365cal/v1/auth/verify-code',
                    method: 'POST',
                    data: { email: email, code: code },
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce); }
                }).done(function(response) {
                    msgBox.text('<?php _e("Sikeres azonosítás!", "o365-calendar"); ?>').css('color', 'green');
                    $('#o365-verify-step').hide();
                    
                    const select = $('#o365-select-calendar');
                    select.empty();
                    $.each(response.calendars, function(id, name) {
                        select.append(new Option(name, id));
                    });
                    
                    $('#o365-select-step').show();
                }).fail(function(xhr) {
                    msgBox.text(xhr.responseJSON?.message || 'Hiba történt.').css('color', 'red');
                });
            });

            $('#o365-btn-save-calendar').on('click', function(e) {
                e.preventDefault();
                const selectedId = $('#o365-select-calendar').val();
                const selectedName = $('#o365-select-calendar option:selected').text();
                
                // Beállítjuk a rejtett mezők értékeit és triggereljük a change eventet, hogy az Elementor elmentse
                const idInput = $('input[data-setting="calendar_id"]');
                const nameInput = $('input[data-setting="calendar_name"]');
                
                idInput.val(selectedId).trigger('input').trigger('change');
                nameInput.val(selectedName).trigger('input').trigger('change');
                
                msgBox.text('<?php _e("Naptár kiválasztva. Frissítsd a beállításokat alul!", "o365-calendar"); ?>').css('color', 'green');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Frontend megjelenítés.
     * Csak kiírjuk a konténert a szükséges adatokkal, a FullCalendar JS fogja felépíteni.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        if ( empty( $settings['target_email'] ) || empty( $settings['calendar_id'] ) ) {
            echo '<p>' . __( 'A naptár nincs megfelelően beállítva vagy hitelesítve.', 'o365-calendar' ) . '</p>';
            return;
        }

        ?>
        <div class="o365-fullcalendar-container" 
             data-email="<?php echo esc_attr( $settings['target_email'] ); ?>" 
             data-calendar-id="<?php echo esc_attr( $settings['calendar_id'] ); ?>" 
             data-default-view="<?php echo esc_attr( $settings['default_view'] ); ?>">
        </div>
        <?php
    }
}