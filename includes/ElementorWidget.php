<?php
namespace O365Calendar;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;

if ( ! defined( 'ABSPATH' ) ) exit;

class ElementorWidget extends Widget_Base {

    public function get_name() { return 'o365_calendar'; }
    public function get_title() { return __( 'O365 Calendar', 'o365-calendar' ); }
    public function get_icon() { return 'eicon-calendar'; }
    public function get_categories() { return [ 'general' ]; }
    public function get_script_depends() { return [ 'o365-calendar-script' ]; }

    protected function register_controls() {
        // --- 1. ADATOK ELŐKÉSZÍTÉSE A TÖBBFIÓKOS RENDSZERBŐL ---
        $accounts = get_option( 'o365_accounts', [] );
        $calendar_options = [];
        $category_options = [];

        foreach ( $accounts as $acc_email => $data ) {
            if ( !empty($data['calendars']) ) {
                foreach ( $data['calendars'] as $id => $name ) {
                    // Kombinált ID: email|naptár_id (pl: pelda@domain.com|AAMkAG...)
                    $calendar_options["{$acc_email}|{$id}"] = "[{$acc_email}] {$name}";
                }
            }
            if ( !empty($data['categories']) ) {
                foreach ( $data['categories'] as $cat ) {
                    $category_options[$cat] = $cat;
                }
            }
        }

        // --- TARTALOM (CONTENT) FÜL ---
        
        // NAPTÁR KONFIGURÁCIÓ
        $this->start_controls_section('content_section', [
            'label' => __( 'Naptár Konfiguráció', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);
        $api = new \O365Calendar\GraphAPI();
        $status_color = $api->is_configured() ? '#46b450' : '#dc3232';
        $status_text = $api->is_configured() ? 'API Konfigurálva' : 'API Hiba / Hiányzó adatok';
        $acc_count = count( get_option('o365_accounts', []) );
        
        $this->add_control('api_status_indicator', [
            'type' => Controls_Manager::RAW_HTML,
            'raw' => "<div style='padding:12px; background:#f0f0f1; border-left:4px solid {$status_color}; border-radius:3px; margin-bottom:15px; font-size:12px; line-height:1.5;'>
                        <strong>Graph API:</strong> <span style='color:{$status_color};'>{$status_text}</span><br>
                        <strong>Hitelesített fiókok:</strong> {$acc_count} db
                      </div>",
        ]);

        $this->add_control('auth_wizard_trigger', [
            'type' => Controls_Manager::RAW_HTML,
            'raw'  => '<button type="button" id="o365-trigger-wizard" class="elementor-button elementor-button-default" style="width:100%; background:#0073aa;"><i class="eicon-cog"></i> Setup Wizard</button>',
        ]);

        $this->add_control('calendar_id', [
            'label'   => __( 'Választott naptárak', 'o365-calendar' ),
            'type'    => Controls_Manager::SELECT2,
            'options' => $calendar_options, // Az új, többfiókos lista
            'multiple' => true,
            'label_block' => true,
            'separator' => 'before'
        ]);

        $this->add_control('resync_trigger', [
            'type' => Controls_Manager::RAW_HTML,
            'raw'  => '<button type="button" id="o365-resync-btn" class="elementor-button elementor-button-success" style="width:100%; margin-top:10px;"><i class="eicon-sync"></i> Események Frissítése</button>',
        ]);

        $this->end_controls_section();

        // IDŐ ÉS DÁTUM KORLÁTOK
        $this->start_controls_section('limits_section', [
            'label' => __( 'Idő és Dátum Korlátok', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $hours = [];
        for($i = 0; $i <= 24; $i++) {
            $h = str_pad($i, 2, '0', STR_PAD_LEFT);
            $hours["{$h}:00:00"] = "{$h}:00";
        }

        $this->add_control('slot_min_time', [
            'label' => 'Napi kezdés',
            'type' => Controls_Manager::SELECT,
            'default' => '00:00:00',
            'options' => $hours,
        ]);

        $this->add_control('slot_max_time', [
            'label' => 'Napi befejezés',
            'type' => Controls_Manager::SELECT,
            'default' => '24:00:00',
            'options' => $hours,
        ]);

        $this->add_control('calendar_timezone', [
            'label'   => 'Időzóna kényszerítése',
            'type'    => Controls_Manager::SELECT,
            'default' => 'local',
            'options' => [
                'local'           => 'Látogató helyi ideje',
                'Europe/Budapest' => 'Budapest (UTC+1/2)',
                'UTC'             => 'UTC',
            ],
        ]);

        $this->add_control('valid_start', [
            'label' => 'Legkorábbi dátum',
            'type' => Controls_Manager::DATE_TIME,
            'picker_options' => ['enableTime' => false],
        ]);

        $this->add_control('valid_end', [
            'label' => 'Legkésőbbi dátum',
            'type' => Controls_Manager::DATE_TIME,
            'picker_options' => ['enableTime' => false],
        ]);

        $this->end_controls_section();

        // EXTRA FUNKCIÓK (Maszkolás & Kategória szűrés)
        $this->start_controls_section('features_section', [
            'label' => __( 'Extra Funkciók', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('display_event_time', [
            'label'   => 'Időpont mutatása a naptárban',
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('privacy_mode', [
            'label' => 'Privát események',
            'type' => Controls_Manager::SELECT,
            'default' => 'mask',
            'options' => [
                'show' => 'Minden adat látszik',
                'mask' => 'Maszkolás',
                'hide' => 'Teljes elrejtés',
            ],
        ]);

        $this->add_control('privacy_mask_text', [
            'label' => 'Maszkolt esemény szövege',
            'type' => Controls_Manager::TEXT,
            'default' => 'Foglalt',
            'condition' => [ 'privacy_mode' => 'mask' ],
        ]);

        $this->add_control('category_filter', [
            'label'       => __( 'Kategória szűrés', 'o365-calendar' ),
            'type'        => Controls_Manager::SELECT2,
            'options'     => $category_options,
            'multiple'    => true,
            'label_block' => true,
            'separator'   => 'before',
        ]);

        $this->add_control('use_o365_colors', [
            'label' => 'O365 Kategória-színek használata',
            'type' => Controls_Manager::SWITCHER,
            'default' => 'yes',
            'separator' => 'before'
        ]);

        $this->end_controls_section();

        // NÉZETEK
        $this->start_controls_section('views_section', ['label' => __( 'Nézetek (Reszponzív)', 'o365-calendar' ), 'tab' => Controls_Manager::TAB_CONTENT]);
        $view_options = ['dayGridMonth' => 'Havi Naptár', 'timeGridWeek' => 'Heti Naptár', 'listWeek' => 'Heti Lista', 'listMonth' => 'Havi Lista'];
        
        $this->add_control('heading_desktop', ['type' => Controls_Manager::HEADING, 'label' => 'Asztali Nézet']);
        $this->add_control('views_desktop', ['label' => 'Engedélyezett', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'options' => $view_options, 'default' => ['dayGridMonth', 'timeGridWeek', 'listMonth']]);
        $this->add_control('default_desktop', ['label' => 'Alapértelmezett', 'type' => Controls_Manager::SELECT, 'options' => $view_options, 'default' => 'dayGridMonth']);

        $this->add_control('heading_tablet', ['type' => Controls_Manager::HEADING, 'label' => 'Tablet Nézet', 'separator' => 'before']);
        $this->add_control('views_tablet', ['label' => 'Engedélyezett', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'options' => $view_options, 'default' => ['timeGridWeek', 'listMonth']]);
        $this->add_control('default_tablet', ['label' => 'Alapértelmezett', 'type' => Controls_Manager::SELECT, 'options' => $view_options, 'default' => 'timeGridWeek']);

        $this->add_control('heading_mobile', ['type' => Controls_Manager::HEADING, 'label' => 'Mobil Nézet', 'separator' => 'before']);
        $this->add_control('views_mobile', ['label' => 'Engedélyezett', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'options' => $view_options, 'default' => ['listMonth']]);
        $this->add_control('default_mobile', ['label' => 'Alapértelmezett', 'type' => Controls_Manager::SELECT, 'options' => $view_options, 'default' => 'listMonth']);
        $this->end_controls_section();

        // --- STÍLUS (STYLE) FÜL ---
        
        $this->start_controls_section('section_templates', ['label' => __( 'Design Sablonok', 'o365-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('design_template', [
            'label' => 'Válassz alap sablont', 'type' => Controls_Manager::SELECT, 'default' => 'default',
            'options' => ['default' => 'Weboldal stílusa', 'light' => 'Világos', 'dark' => 'Sötét', 'modern' => 'Modern'],
            'prefix_class' => 'o365-template-',
        ]);
        $this->end_controls_section();

        $this->start_controls_section('style_general', ['label' => __( 'Naptár Alapok', 'o365-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('calendar_height', ['label' => 'Magasság', 'type' => Controls_Manager::SLIDER, 'size_units' => [ 'px', 'vh' ], 'default' => ['unit' => 'px', 'size' => 650], 'selectors' => ['{{WRAPPER}}' => '--o365-cal-height: {{SIZE}}{{UNIT}};']]);
        $this->add_group_control(Group_Control_Background::get_type(), ['name' => 'widget_bg', 'selector' => '{{WRAPPER}} .o365-calendar-wrapper']);
        $this->add_control('grid_border_color', ['label' => 'Rácsvonalak színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .fc td, {{WRAPPER}} .fc th' => 'border-color: {{VALUE}} !important;']]);
        $this->end_controls_section();

        // (Itt folytatódnak a korábbi Typography, Header és Modal szekciók...)
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $locale = str_replace('_', '-', get_locale());
        
        // Összegyűjtjük a kiválasztott kombinált ID-kat
        $cal_id = !empty($settings['calendar_id']) ? (is_array($settings['calendar_id']) ? implode(',', $settings['calendar_id']) : $settings['calendar_id']) : '';
        $cat_filter = !empty($settings['category_filter']) ? (is_array($settings['category_filter']) ? implode(',', $settings['category_filter']) : $settings['category_filter']) : '';

        $vd = !empty($settings['views_desktop']) ? implode(',', $settings['views_desktop']) : 'dayGridMonth';
        $vt = !empty($settings['views_tablet']) ? implode(',', $settings['views_tablet']) : 'timeGridWeek';
        $vm = !empty($settings['views_mobile']) ? implode(',', $settings['views_mobile']) : 'listMonth';

        ?>
        <div class="o365-fullcalendar-container" 
             data-calendar-id="<?php echo esc_attr($cal_id); ?>" 
             data-views-desktop="<?php echo esc_attr($vd); ?>"
             data-default-desktop="<?php echo esc_attr($settings['default_desktop'] ?? 'dayGridMonth'); ?>"
             data-views-tablet="<?php echo esc_attr($vt); ?>"
             data-default-tablet="<?php echo esc_attr($settings['default_tablet'] ?? 'timeGridWeek'); ?>"
             data-views-mobile="<?php echo esc_attr($vm); ?>"
             data-default-mobile="<?php echo esc_attr($settings['default_mobile'] ?? 'listMonth'); ?>"
             data-slot-min="<?php echo esc_attr($settings['slot_min_time'] ?? '00:00:00'); ?>"
             data-slot-max="<?php echo esc_attr($settings['slot_max_time'] ?? '24:00:00'); ?>"
             data-timezone="<?php echo esc_attr($settings['calendar_timezone'] ?? 'local'); ?>"
             data-valid-start="<?php echo esc_attr($settings['valid_start'] ?? ''); ?>"
             data-valid-end="<?php echo esc_attr($settings['valid_end'] ?? ''); ?>"
             data-privacy="<?php echo esc_attr($settings['privacy_mode'] ?? 'mask'); ?>"
             data-mask-text="<?php echo esc_attr($settings['privacy_mask_text'] ?? 'Foglalt'); ?>"
             data-category-filter="<?php echo esc_attr($cat_filter); ?>"
             data-use-colors="<?php echo esc_attr($settings['use_o365_colors'] ?? 'yes'); ?>"
             data-display-event-time="<?php echo esc_attr($settings['display_event_time'] ?? 'yes'); ?>"
             data-locale="<?php echo esc_attr($locale); ?>">
        </div>
        <?php
    }
}