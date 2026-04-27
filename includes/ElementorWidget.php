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

        // --- 1. NAPTÁR ADATOK ---
        $this->start_controls_section('content_section', [
            'label' => __( 'Naptár Konfiguráció', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('auth_wizard_trigger', [
            'type' => Controls_Manager::RAW_HTML,
            'raw'  => '<button type="button" id="o365-trigger-wizard" class="elementor-button elementor-button-default" style="width:100%; background:#0073aa;"><i class="eicon-cog"></i> Setup Wizard</button>',
        ]);

        $this->add_control('calendar_id', [
            'label'       => __( 'Naptárak Kiválasztása', 'o365-calendar' ),
            'type'        => Controls_Manager::SELECT2,
            'options'     => $calendars,
            'multiple'    => true, // ETTŐL LESZ MULTI-SELECT!
            'label_block' => true,
            'separator'   => 'before'
        ]);

        $this->add_control('resync_trigger', [
            'type' => Controls_Manager::RAW_HTML,
            'raw'  => '<button type="button" id="o365-resync-btn" class="elementor-button elementor-button-success" style="width:100%;"><i class="eicon-sync"></i> ' . __( 'Események Frissítése', 'o365-calendar' ) . '</button>',
        ]);

        $this->end_controls_section();

        // --- 2. NÉZETEK ÉS RESZPONZIVITÁS ---
        $this->start_controls_section('views_section', [
            'label' => __( 'Nézetek (Reszponzív)', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $view_options = [
            'dayGridMonth' => 'Havi Naptár (Rács)',
            'timeGridWeek' => 'Heti Naptár (Oszlopok)',
            'listWeek'     => 'Heti Lista',
            'listMonth'    => 'Havi Lista',
        ];

        // === ASZTALI (DESKTOP) ===
        $this->add_control('heading_desktop', ['type' => Controls_Manager::HEADING, 'label' => '<i class="eicon-device-desktop"></i> Asztali Nézet (Desktop)', 'separator' => 'before']);
        $this->add_control('views_desktop', [
            'label' => 'Engedélyezett nézetek',
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => $view_options,
            'default' => ['dayGridMonth', 'timeGridWeek', 'listMonth'],
        ]);
        $this->add_control('default_desktop', [
            'label' => 'Alapértelmezett (ha több van)',
            'type' => Controls_Manager::SELECT,
            'options' => $view_options,
            'default' => 'dayGridMonth',
        ]);

        // === TABLET ===
        $this->add_control('heading_tablet', ['type' => Controls_Manager::HEADING, 'label' => '<i class="eicon-device-tablet"></i> Tablet Nézet', 'separator' => 'before']);
        $this->add_control('views_tablet', [
            'label' => 'Engedélyezett nézetek',
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => $view_options,
            'default' => ['timeGridWeek', 'listMonth'],
        ]);
        $this->add_control('default_tablet', [
            'label' => 'Alapértelmezett',
            'type' => Controls_Manager::SELECT,
            'options' => $view_options,
            'default' => 'timeGridWeek',
        ]);

        // === MOBIL ===
        $this->add_control('heading_mobile', ['type' => Controls_Manager::HEADING, 'label' => '<i class="eicon-device-mobile"></i> Mobil Nézet', 'separator' => 'before']);
        $this->add_control('views_mobile', [
            'label' => 'Engedélyezett nézetek',
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => $view_options,
            'default' => ['listMonth'],
        ]);
        $this->add_control('default_mobile', [
            'label' => 'Alapértelmezett',
            'type' => Controls_Manager::SELECT,
            'options' => $view_options,
            'default' => 'listMonth',
        ]);

        $this->end_controls_section();

        // --- 3. STÍLUSOK (MAGASSÁG ÉS SZÍNEK) ---
        $this->start_controls_section('style_general', [
            'label' => __( 'Alap Stílus & Magasság', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('reset_styles', [
            'type' => Controls_Manager::RAW_HTML,
            'raw'  => '<button type="button" id="o365-reset-styles" class="elementor-button elementor-button-warning" style="width:100%; border: 1px solid #d63638; color:#d63638; background:transparent;"><i class="eicon-refresh"></i> Stílus Reset</button>',
        ]);

        // MAGASSÁG MEGADÁSA CSS VÁLTOZÓVAL (GARANTÁLJA A SCROLLBART)
        $this->add_responsive_control('calendar_height', [
            'label' => __( 'Naptár Magassága', 'o365-calendar' ),
            'type' => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'vh' ],
            'range' => [
                'px' => [ 'min' => 300, 'max' => 1200, 'step' => 10 ],
                'vh' => [ 'min' => 30, 'max' => 100, 'step' => 1 ],
            ],
            'default' => [ 'unit' => 'px', 'size' => 650 ],
            'selectors' => [ '{{WRAPPER}}' => '--o365-cal-height: {{SIZE}}{{UNIT}};' ],
            'separator' => 'before'
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name' => 'widget_bg',
            'label' => 'Widget Háttere',
            'types' => [ 'classic', 'gradient' ],
            'selector' => '{{WRAPPER}} .o365-calendar-wrapper',
        ]);

        $this->add_control('grid_border_color', [
            'label' => 'Rácsvonalak színe',
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .fc td, {{WRAPPER}} .fc th, {{WRAPPER}} .fc-theme-standard td, {{WRAPPER}} .fc-theme-standard th' => 'border-color: {{VALUE}} !important;' ],
        ]);

        $this->add_control('today_bg', [
            'label' => 'Mai nap kiemelése',
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .fc .fc-day-today' => 'background-color: {{VALUE}} !important;' ],
        ]);

        $this->end_controls_section();

        // FEJLÉC STÍLUSOK
        $this->start_controls_section('style_header', [
            'label' => 'Fejléc és Események',
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('header_bg', [
            'label' => 'Fejléc (Toolbar) háttér',
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .fc-toolbar' => 'background-color: {{VALUE}}; padding: 15px; border-radius: 8px 8px 0 0;' ],
        ]);

        $this->add_control('btn_bg', [
            'label' => 'Gombok színe',
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .fc-button' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;' ],
        ]);

        $this->add_control('btn_active_bg', [
            'label' => 'Aktív gomb színe',
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .fc-button-active' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;' ],
        ]);

        $this->add_control('event_bg', [
            'label' => 'Esemény Színe (Kártya)',
            'type' => Controls_Manager::COLOR,
            'separator' => 'before',
            'selectors' => [ 
                '{{WRAPPER}} .fc-event' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;',
                '{{WRAPPER}} .fc-list-event-dot' => 'border-color: {{VALUE}} !important;'
            ],
        ]);

        $this->end_controls_section();

        // ESEMÉNY MODAL STÍLUSOK
        $this->start_controls_section('style_modal', [
            'label' => __( 'Esemény Modal (Popup)', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('modal_overlay_bg', [
            'label' => 'Háttér sötétítés (Overlay)',
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .o365-event-modal-overlay' => 'background-color: {{VALUE}};' ],
        ]);
        $this->add_control('modal_bg', [
            'label' => 'Ablak Háttere',
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .o365-event-modal' => 'background-color: {{VALUE}};' ],
        ]);
        $this->add_control('modal_title_color', [
            'label' => 'Cím Színe',
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .o365-modal-title' => 'color: {{VALUE}};' ],
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name' => 'modal_title_typo',
            'label' => 'Cím Tipográfia',
            'selector' => '{{WRAPPER}} .o365-modal-title',
        ]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $email = get_option( 'o365_auth_email', '' );
        
        $cal_id = '';
        if ( ! empty( $settings['calendar_id'] ) ) {
            $cal_id = is_array( $settings['calendar_id'] ) ? implode( ',', $settings['calendar_id'] ) : $settings['calendar_id'];
        }

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
             data-default-mobile="<?php echo esc_attr($settings['default_mobile'] ?? 'listMonth'); ?>">
        </div>
        <?php
    }
}