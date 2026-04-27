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
        $calendars = get_option( 'o365_cached_calendars', [] );

        // ==========================================
        // 1. TARTALOM (CONTENT) FÜL
        // ==========================================
        
        $this->start_controls_section('content_section', [
            'label' => __( 'Naptár Konfiguráció', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);
        $this->add_control('auth_wizard_trigger', ['type' => Controls_Manager::RAW_HTML, 'raw'  => '<button type="button" id="o365-trigger-wizard" class="elementor-button elementor-button-default" style="width:100%; background:#0073aa;"><i class="eicon-cog"></i> Setup Wizard</button>']);
        $this->add_control('calendar_id', ['label' => __( 'Választott naptárak', 'o365-calendar' ), 'type' => Controls_Manager::SELECT2, 'options' => $calendars, 'multiple' => true, 'label_block' => true, 'separator' => 'before']);
        $this->add_control('resync_trigger', ['type' => Controls_Manager::RAW_HTML, 'raw'  => '<button type="button" id="o365-resync-btn" class="elementor-button elementor-button-success" style="width:100%; margin-top:10px;"><i class="eicon-sync"></i> Események Frissítése</button>']);
        $this->end_controls_section();

        // --- ÚJ SZEKCIÓ: IDŐ ÉS DÁTUM KORLÁTOK ---
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
            'label' => 'Napi kezdés (Heti/Napi nézet)',
            'type' => Controls_Manager::SELECT,
            'default' => '00:00:00',
            'options' => $hours,
        ]);

        $this->add_control('slot_max_time', [
            'label' => 'Napi befejezés (Heti/Napi nézet)',
            'type' => Controls_Manager::SELECT,
            'default' => '24:00:00',
            'options' => $hours,
        ]);

        $this->add_control('valid_start', [
            'label' => 'Legkorábbi dátum (Navigáció)',
            'type' => Controls_Manager::DATE_TIME,
            'picker_options' => ['enableTime' => false],
            'separator' => 'before',
            'description' => 'Ezelőttre nem tud visszalapozni a felhasználó.',
        ]);

        $this->add_control('valid_end', [
            'label' => 'Legkésőbbi dátum (Navigáció)',
            'type' => Controls_Manager::DATE_TIME,
            'picker_options' => ['enableTime' => false],
            'description' => 'Ezutánra nem tud előrelapozni a felhasználó.',
        ]);

        $this->end_controls_section();

        // --- EXTRA FUNKCIÓK ---
        $this->start_controls_section('features_section', [
            'label' => __( 'Extra Funkciók', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('display_event_time', [
            'label'   => 'Időpont mutatása a naptárban',
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
            'description' => 'Ha kikapcsolod, csak az esemény címe fog látszódni a naptár rácsaiban.',
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

        // ÚJ SELECT2 KATEGÓRIA VÁLASZTÓ
        $cached_categories = get_option( 'o365_cached_categories', [] );
        $this->add_control('category_filter', [
            'label'       => __( 'Kategória szűrés', 'o365-calendar' ),
            'type'        => Controls_Manager::SELECT2,
            'options'     => $cached_categories,
            'multiple'    => true,
            'label_block' => true,
            'separator'   => 'before',
            'description' => 'Válaszd ki a kategóriákat. Üresen hagyva minden látszik.'
        ]);

        $this->add_control('use_o365_colors', [
            'label' => 'O365 Kategória-színek használata',
            'type' => Controls_Manager::SWITCHER,
            'default' => 'yes',
            'separator' => 'before'
        ]);

        $this->end_controls_section();

        // --- NÉZETEK ÉS RESZPONZIVITÁS ---
        $this->start_controls_section('views_section', ['label' => __( 'Nézetek (Reszponzív)', 'o365-calendar' ), 'tab' => Controls_Manager::TAB_CONTENT]);
        $view_options = ['dayGridMonth' => 'Havi Naptár', 'timeGridWeek' => 'Heti Naptár', 'listWeek' => 'Heti Lista', 'listMonth' => 'Havi Lista'];
        
        $this->add_control('heading_desktop', ['type' => Controls_Manager::HEADING, 'label' => '<i class="eicon-device-desktop"></i> Asztali Nézet', 'separator' => 'before']);
        $this->add_control('views_desktop', ['label' => 'Engedélyezett', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'options' => $view_options, 'default' => ['dayGridMonth', 'timeGridWeek', 'listMonth']]);
        $this->add_control('default_desktop', ['label' => 'Alapértelmezett', 'type' => Controls_Manager::SELECT, 'options' => $view_options, 'default' => 'dayGridMonth']);

        $this->add_control('heading_tablet', ['type' => Controls_Manager::HEADING, 'label' => '<i class="eicon-device-tablet"></i> Tablet Nézet', 'separator' => 'before']);
        $this->add_control('views_tablet', ['label' => 'Engedélyezett', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'options' => $view_options, 'default' => ['timeGridWeek', 'listMonth']]);
        $this->add_control('default_tablet', ['label' => 'Alapértelmezett', 'type' => Controls_Manager::SELECT, 'options' => $view_options, 'default' => 'timeGridWeek']);

        $this->add_control('heading_mobile', ['type' => Controls_Manager::HEADING, 'label' => '<i class="eicon-device-mobile"></i> Mobil Nézet', 'separator' => 'before']);
        $this->add_control('views_mobile', ['label' => 'Engedélyezett', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'options' => $view_options, 'default' => ['listMonth']]);
        $this->add_control('default_mobile', ['label' => 'Alapértelmezett', 'type' => Controls_Manager::SELECT, 'options' => $view_options, 'default' => 'listMonth']);
        $this->end_controls_section();

        // ==========================================
        // 2. STÍLUS (STYLE) FÜL
        // ==========================================

        $this->start_controls_section('section_templates', ['label' => __( 'Design Sablonok', 'o365-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('design_template', [
            'label' => 'Válassz alap sablont', 'type' => Controls_Manager::SELECT, 'default' => 'default',
            'options' => ['default' => 'Weboldal stílusa (Öröklött)', 'light' => 'Világos (Tahoma & Cyan CTA)', 'dark' => 'Sötét (Deep Blue & Tahoma)', 'modern' => 'Minimal Modern'],
            'prefix_class' => 'o365-template-',
        ]);
        $this->add_control('reset_styles', ['type' => Controls_Manager::RAW_HTML, 'raw' => '<button type="button" id="o365-reset-styles" class="elementor-button elementor-button-warning" style="width:100%; border:1px solid #d63638; color:#d63638; background:transparent;"><i class="eicon-refresh"></i> Stílusok Alaphelyzetbe</button>']);
        $this->end_controls_section();

        $this->start_controls_section('style_general', ['label' => __( 'Naptár Alapok', 'o365-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('calendar_height', ['label' => 'Naptár Magassága', 'type' => Controls_Manager::SLIDER, 'size_units' => [ 'px', 'vh' ], 'range' => ['px' => ['min' => 300, 'max' => 1200, 'step' => 10], 'vh' => ['min' => 30, 'max' => 100, 'step' => 1]], 'default' => ['unit' => 'px', 'size' => 650], 'selectors' => ['{{WRAPPER}}' => '--o365-cal-height: {{SIZE}}{{UNIT}};']]);
        $this->add_group_control(Group_Control_Background::get_type(), ['name' => 'widget_bg', 'label' => 'Widget Háttere', 'types' => [ 'classic', 'gradient' ], 'selector' => '{{WRAPPER}} .o365-calendar-wrapper']);
        $this->add_control('grid_border_color', ['label' => 'Rácsvonalak színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .fc td, {{WRAPPER}} .fc th, {{WRAPPER}} .fc-theme-standard td, {{WRAPPER}} .fc-theme-standard th' => 'border-color: {{VALUE}} !important;']]);
        $this->add_control('today_bg', ['label' => 'Mai nap kiemelése', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .fc .fc-day-today' => 'background-color: {{VALUE}} !important;']]);
        $this->end_controls_section();

        $this->start_controls_section('style_typography', ['label' => 'Tipográfia és Szövegek', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'main_typo', 'label' => 'Általános betűkészlet', 'selector' => '{{WRAPPER}} .o365-calendar-wrapper, {{WRAPPER}} .fc']);
        $this->add_control('title_color', ['label' => 'Fejléc címe (Hónap/Év)', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .fc-toolbar-title' => 'color: {{VALUE}} !important;']]);
        $this->add_control('day_header_color', ['label' => 'Napok nevei', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => ['{{WRAPPER}} .fc-col-header-cell-cushion' => 'color: {{VALUE}} !important;']]);
        $this->end_controls_section();

        $this->start_controls_section('style_header', ['label' => 'Fejléc és Gombok', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('header_bg', ['label' => 'Fejléc háttérszíne', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .fc-toolbar' => 'background-color: {{VALUE}}; padding: 15px; border-radius: 8px 8px 0 0;']]);
        $this->add_control('btn_bg', ['label' => 'Gombok színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .fc-button' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;']]);
        $this->add_control('btn_active_bg', ['label' => 'Aktív gomb színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .fc-button-active' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;']]);
        $this->add_control('event_bg', ['label' => 'Esemény Színe (Kártya)', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => ['{{WRAPPER}} .fc-event' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;', '{{WRAPPER}} .fc-list-event-dot' => 'border-color: {{VALUE}} !important;']]);
        $this->end_controls_section();

        $this->start_controls_section('style_modal_advanced', ['label' => 'Esemény Modal (Popup)', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('modal_overlay_bg', ['label' => 'Háttér sötétítés (Overlay)', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-event-modal-overlay' => 'background-color: {{VALUE}};']]);
        $this->add_control('modal_bg_adv', ['label' => 'Ablak Háttérszíne', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-event-modal' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'modal_title_typo', 'label' => 'Cím Tipográfia', 'selector' => '{{WRAPPER}} .o365-modal-title']);
        $this->add_control('modal_title_color', ['label' => 'Cím Színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-modal-title' => 'color: {{VALUE}};']]);
        $this->add_control('modal_text_color', ['label' => 'Szöveg Színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-modal-meta, {{WRAPPER}} .o365-modal-desc' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'modal_shadow_adv', 'selector' => '{{WRAPPER}} .o365-event-modal']);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $email = get_option( 'o365_auth_email', '' );
        
        $cal_id = !empty($settings['calendar_id']) ? (is_array($settings['calendar_id']) ? implode(',', $settings['calendar_id']) : $settings['calendar_id']) : '';
        $cat_filter = !empty($settings['category_filter']) ? (is_array($settings['category_filter']) ? implode(',', $settings['category_filter']) : $settings['category_filter']) : '';

        $vd = !empty($settings['views_desktop']) ? implode(',', $settings['views_desktop']) : 'dayGridMonth';
        $vt = !empty($settings['views_tablet']) ? implode(',', $settings['views_tablet']) : 'timeGridWeek';
        $vm = !empty($settings['views_mobile']) ? implode(',', $settings['views_mobile']) : 'listMonth';

        ?>
        <div class="o365-fullcalendar-container" 
             data-email="<?php echo esc_attr($email); ?>" 
             data-calendar-id="<?php echo esc_attr($cal_id); ?>" 
             data-views-desktop="<?php echo esc_attr($vd); ?>"
             data-default-desktop="<?php echo esc_attr($settings['default_desktop'] ?? 'dayGridMonth'); ?>"
             data-views-tablet="<?php echo esc_attr($vt); ?>"
             data-default-tablet="<?php echo esc_attr($settings['default_tablet'] ?? 'timeGridWeek'); ?>"
             data-views-mobile="<?php echo esc_attr($vm); ?>"
             data-default-mobile="<?php echo esc_attr($settings['default_mobile'] ?? 'listMonth'); ?>"
             data-slot-min="<?php echo esc_attr($settings['slot_min_time'] ?? '00:00:00'); ?>"
             data-slot-max="<?php echo esc_attr($settings['slot_max_time'] ?? '24:00:00'); ?>"
             data-valid-start="<?php echo esc_attr($settings['valid_start'] ?? ''); ?>"
             data-valid-end="<?php echo esc_attr($settings['valid_end'] ?? ''); ?>"
             data-privacy="<?php echo esc_attr($settings['privacy_mode'] ?? 'mask'); ?>"
             data-mask-text="<?php echo esc_attr($settings['privacy_mask_text'] ?? 'Foglalt'); ?>"
             data-category-filter="<?php echo esc_attr($cat_filter); ?>"
             data-use-colors="<?php echo esc_attr($settings['use_o365_colors'] ?? 'yes'); ?>"
             data-display-event-time="<?php echo esc_attr($settings['display_event_time'] ?? 'yes'); ?>">
        </div>
        <?php
    }
}