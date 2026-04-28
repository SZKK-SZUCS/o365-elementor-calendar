<?php
namespace O365Calendar\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use O365Calendar\Core\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) exit;

class CalendarWidget extends AbstractWidget {

    public function get_name() { return 'o365_calendar'; }
    public function get_title() { return __( 'O365 Naptár (Full)', 'o365-elementor-calendar' ); }
    public function get_icon() { return 'eicon-calendar'; }

    protected function register_controls() {
        
        // --- 1. TARTALOM (CONTENT) FÜL ---
        
        $this->register_api_and_account_controls( __( 'Naptár Forrás & Setup', 'o365-elementor-calendar' ) );

        $this->start_controls_section('section_config', ['label' => __( 'Naptár Beállítások', 'o365-elementor-calendar' )]);
        $hours = [];
        for ( $i = 0; $i <= 24; $i++ ) { $h = str_pad( $i, 2, '0', STR_PAD_LEFT ); $hours["{$h}:00:00"] = "{$h}:00"; }
        $this->add_control('slot_min_time', ['label' => __( 'Napi kezdés', 'o365-elementor-calendar' ), 'type' => Controls_Manager::SELECT, 'default' => '00:00:00', 'options' => $hours]);
        $this->add_control('slot_max_time', ['label' => __( 'Napi befejezés', 'o365-elementor-calendar' ), 'type' => Controls_Manager::SELECT, 'default' => '24:00:00', 'options' => $hours]);
        $this->add_control('display_event_time', ['label' => __( 'Időpont mutatása', 'o365-elementor-calendar' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('privacy_mode', ['label' => __( 'Privát események', 'o365-elementor-calendar' ), 'type' => Controls_Manager::SELECT, 'default' => 'mask', 'options' => ['show' => 'Minden adat látszik', 'mask' => 'Maszkolás', 'hide' => 'Teljes elrejtés']]);
        $this->end_controls_section();

        // ÚJ: Szövegek és Ikonok felülírása a Content fülön
        $this->start_controls_section('section_texts', ['label' => __( 'Szövegek és Címkék', 'o365-elementor-calendar' )]);
        $this->add_control('modal_export_text', ['label' => 'Letöltés gomb szövege', 'type' => Controls_Manager::TEXT, 'default' => 'Mentés naptárba']);
        $this->add_control('modal_link_text', ['label' => 'Link megnyitása szöveg', 'type' => Controls_Manager::TEXT, 'default' => 'Megnyitás']);
        $this->add_control('modal_join_text', ['label' => 'Online Meeting szöveg', 'type' => Controls_Manager::TEXT, 'default' => 'Csatlakozás']);
        $this->add_control('privacy_mask_text', ['label' => 'Maszkolt esemény (Foglalt)', 'type' => Controls_Manager::TEXT, 'default' => 'Foglalt', 'condition' => ['privacy_mode' => 'mask']]);
        $this->end_controls_section();

        $this->start_controls_section('section_views', ['label' => __( 'Nézetek (Reszponzív)', 'o365-elementor-calendar' )]);
        $view_opts = ['dayGridMonth' => 'Havi Naptár', 'timeGridWeek' => 'Heti Naptár', 'listWeek' => 'Heti Lista', 'listMonth' => 'Havi Lista'];
        $this->add_control('views_desktop', ['label' => 'Asztali Nézetek', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'options' => $view_opts, 'default' => ['dayGridMonth', 'timeGridWeek', 'listMonth']]);
        $this->add_control('default_desktop', ['label' => 'Alapértelmezett (Desktop)', 'type' => Controls_Manager::SELECT, 'options' => $view_opts, 'default' => 'dayGridMonth']);
        $this->end_controls_section();

        // --- 2. STÍLUS (STYLE) FÜL ---

        // 2.1 Alapok & Rács
        $this->start_controls_section('style_general', ['label' => __( 'Naptár Alapok & Rács', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('calendar_height', ['label' => 'Max Magasság', 'type' => Controls_Manager::SLIDER, 'size_units' => [ 'px', 'vh' ], 'default' => [ 'unit' => 'px', 'size' => 650 ], 'selectors' => [ '{{WRAPPER}}' => '--o365-cal-height: {{SIZE}}{{UNIT}};' ]]);
        $this->add_group_control(Group_Control_Background::get_type(), ['name' => 'widget_bg', 'selector' => '{{WRAPPER}} .o365-calendar-wrapper']);
        $this->add_control('grid_border_color', ['label' => 'Rácsvonalak színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-theme-standard td, {{WRAPPER}} .fc-theme-standard th' => 'border-color: {{VALUE}} !important;' ]]);
        $this->add_control('header_text_color', ['label' => 'Napok neve (Fejléc) színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-col-header-cell-cushion' => 'color: {{VALUE}} !important;' ]]);
        $this->add_control('day_text_color', ['label' => 'Dátum számok színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-daygrid-day-number' => 'color: {{VALUE}} !important;' ]]);
        $this->add_control('today_bg_color', ['label' => 'Mai nap háttere', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-day-today' => 'background-color: {{VALUE}} !important;' ]]);
        $this->end_controls_section();

        // 2.2 Fejléc & Gombok (Toolbar)
        $this->start_controls_section('style_toolbar', ['label' => __( 'Fejléc (Toolbar) & Gombok', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'toolbar_title_typo', 'label' => 'Cím Tipográfia', 'selector' => '{{WRAPPER}} .fc-toolbar-title']);
        $this->add_control('toolbar_title_color', ['label' => 'Cím színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-toolbar-title' => 'color: {{VALUE}} !important;' ]]);
        
        $this->add_control('toolbar_btn_heading', ['label' => 'Gombok (Ma, Nézetek, Léptetés)', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'toolbar_btn_typo', 'selector' => '{{WRAPPER}} .fc-button-primary']);
        $this->add_control('toolbar_btn_radius', ['label' => 'Gomb Lekerekítés', 'type' => Controls_Manager::SLIDER, 'selectors' => [ '{{WRAPPER}} .fc-button-primary' => 'border-radius: {{SIZE}}{{UNIT}} !important;' ]]);
        
        $this->start_controls_tabs('tabs_toolbar_buttons');
        // Normal State
        $this->start_controls_tab('tab_tb_normal', ['label' => 'Normál']);
        $this->add_control('tb_btn_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-button-primary' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;' ]]);
        $this->add_control('tb_btn_color', ['label' => 'Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-button-primary' => 'color: {{VALUE}} !important;' ]]);
        $this->end_controls_tab();
        // Hover/Active State
        $this->start_controls_tab('tab_tb_hover', ['label' => 'Hover/Aktív']);
        $this->add_control('tb_btn_hover_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-button-primary:hover, {{WRAPPER}} .fc-button-primary:active, {{WRAPPER}} .fc-button-active' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;' ]]);
        $this->add_control('tb_btn_hover_color', ['label' => 'Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-button-primary:hover, {{WRAPPER}} .fc-button-primary:active, {{WRAPPER}} .fc-button-active' => 'color: {{VALUE}} !important;' ]]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        // 2.3 Események (Kártyák a rácsban)
        $this->start_controls_section('style_events', ['label' => __( 'Események (Kártyák)', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'event_typography', 'selector' => '{{WRAPPER}} .fc-event-title, {{WRAPPER}} .fc-event-time']);
        $this->add_control('event_border_radius', ['label' => 'Lekerekítés', 'type' => Controls_Manager::SLIDER, 'selectors' => [ '{{WRAPPER}} .fc-event' => 'border-radius: {{SIZE}}{{UNIT}} !important;' ]]);
        
        $this->start_controls_tabs('tabs_events');
        $this->start_controls_tab('tab_ev_normal', ['label' => 'Normál']);
        $this->add_control('event_bg_color', ['label' => 'Alap Háttérszín (Ha nincs O365 szín)', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-event' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;' ]]);
        $this->add_control('event_text_color', ['label' => 'Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-event-title, {{WRAPPER}} .fc-event-time' => 'color: {{VALUE}} !important;' ]]);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_ev_hover', ['label' => 'Hover']);
        $this->add_control('event_hover_transform', ['label' => 'Hover Emelkedés (Y tengely)', 'type' => Controls_Manager::SLIDER, 'range' => ['px' => ['min' => -10, 'max' => 0]], 'selectors' => [ '{{WRAPPER}} .fc-event:hover' => 'transform: translateY({{SIZE}}{{UNIT}});' ]]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'event_hover_shadow', 'selector' => '{{WRAPPER}} .fc-event:hover']);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        // 2.4 Hover Tooltip (Információs kártya)
        $this->start_controls_section('style_tooltip', ['label' => __( 'Hover Tooltip', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('tooltip_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => [ '.o365-calendar-tooltip' => 'background-color: {{VALUE}};' ]]); // Nem wrapperes, mert a body-hoz fűzi a JS
        $this->add_control('tooltip_text_color', ['label' => 'Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '.o365-calendar-tooltip' => 'color: {{VALUE}};' ]]);
        $this->add_control('tooltip_border_color', ['label' => 'Kiemelő csík színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '.o365-calendar-tooltip' => 'border-left-color: {{VALUE}};' ]]);
        $this->end_controls_section();

        // 2.5 Popup Modal
        $this->start_controls_section('style_modal', ['label' => __( 'Popup Modal (Részletek)', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('modal_overlay_bg', ['label' => 'Háttér sötétítés (Overlay)', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-event-modal-overlay' => 'background-color: {{VALUE}};' ]]);
        $this->add_control('modal_box_bg', ['label' => 'Modal Doboz Háttér', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-event-modal' => 'background-color: {{VALUE}};' ]]);
        $this->add_control('modal_radius', ['label' => 'Modal Lekerekítés', 'type' => Controls_Manager::SLIDER, 'selectors' => [ '{{WRAPPER}} .o365-event-modal' => 'border-radius: {{SIZE}}{{UNIT}};' ]]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'modal_shadow', 'selector' => '{{WRAPPER}} .o365-event-modal']);
        
        $this->add_control('modal_title_heading', ['label' => 'Tipográfia és Színek', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('modal_title_color', ['label' => 'Cím Színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-modal-title' => 'color: {{VALUE}};' ]]);
        $this->add_control('modal_meta_color', ['label' => 'Idő & Helyszín Színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-modal-meta' => 'color: {{VALUE}};' ]]);
        $this->add_control('modal_icon_color', ['label' => 'Ikonok Színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-modal-meta svg' => 'color: {{VALUE}};' ]]);
        $this->add_control('modal_desc_color', ['label' => 'Leírás Színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-modal-desc' => 'color: {{VALUE}};' ]]);
        $this->add_control('modal_link_color', ['label' => 'Leírásban lévő linkek színe', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-modal-desc a' => 'color: {{VALUE}};' ]]);
        $this->end_controls_section();

        // 2.6 Modal Gombok (Export / Meeting)
        $this->start_controls_section('style_modal_buttons', ['label' => __( 'Modal Akciógombok', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'modal_btn_typo', 'selector' => '{{WRAPPER}} .o365-export-ical-btn, {{WRAPPER}} .o365-meeting-btn']);
        $this->add_control('modal_btn_radius', ['label' => 'Gomb Lekerekítés', 'type' => Controls_Manager::SLIDER, 'selectors' => [ '{{WRAPPER}} .o365-export-ical-btn, {{WRAPPER}} .o365-meeting-btn' => 'border-radius: {{SIZE}}{{UNIT}};' ]]);

        $this->start_controls_tabs('tabs_modal_btns');
        // Normál
        $this->start_controls_tab('tab_mb_normal', ['label' => 'Normál']);
        $this->add_control('mb_export_bg', ['label' => 'Export Gomb Háttér', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-export-ical-btn' => 'background-color: {{VALUE}};' ]]);
        $this->add_control('mb_export_color', ['label' => 'Export Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-export-ical-btn' => 'color: {{VALUE}};' ]]);
        $this->add_control('mb_meet_bg', ['label' => 'Meeting Gomb Háttér', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => [ '{{WRAPPER}} .o365-meeting-btn' => 'background-color: {{VALUE}}; border-color: {{VALUE}};' ]]);
        $this->add_control('mb_meet_color', ['label' => 'Meeting Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-meeting-btn' => 'color: {{VALUE}};' ]]);
        $this->end_controls_tab();
        // Hover
        $this->start_controls_tab('tab_mb_hover', ['label' => 'Hover']);
        $this->add_control('mb_export_h_bg', ['label' => 'Export Gomb Háttér', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-export-ical-btn:hover' => 'background-color: {{VALUE}};' ]]);
        $this->add_control('mb_export_h_color', ['label' => 'Export Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-export-ical-btn:hover' => 'color: {{VALUE}};' ]]);
        $this->add_control('mb_meet_h_bg', ['label' => 'Meeting Gomb Háttér', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => [ '{{WRAPPER}} .o365-meeting-btn:hover' => 'background-color: {{VALUE}}; border-color: {{VALUE}};' ]]);
        $this->add_control('mb_meet_h_color', ['label' => 'Meeting Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .o365-meeting-btn:hover' => 'color: {{VALUE}};' ]]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        // 2.7 Üres állapot stílus (Az AbstractWidget-ből)
        $this->register_empty_state_style_controls();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $locale   = str_replace( '_', '-', get_locale() );
        
        $cal_ids    = !empty($settings['calendar_id']) ? implode(',', (array)$settings['calendar_id']) : '';
        $cat_filter = !empty($settings['category_filter']) ? implode(',', (array)$settings['category_filter']) : '';

        // Az összes dinamikus adatot átadjuk a JS-nek
        ?>
        <div class="o365-fullcalendar-container" 
             data-calendar-id="<?php echo esc_attr($cal_ids); ?>" 
             data-views-desktop="<?php echo esc_attr( implode(',', (array)$settings['views_desktop']) ); ?>"
             data-default-desktop="<?php echo esc_attr($settings['default_desktop']); ?>"
             data-slot-min="<?php echo esc_attr($settings['slot_min_time']); ?>"
             data-slot-max="<?php echo esc_attr($settings['slot_max_time']); ?>"
             data-privacy="<?php echo esc_attr($settings['privacy_mode']); ?>"
             data-mask-text="<?php echo esc_attr($settings['privacy_mask_text']); ?>"
             data-category-filter="<?php echo esc_attr($cat_filter); ?>"
             data-locale="<?php echo esc_attr($locale); ?>"
             data-display-event-time="<?php echo esc_attr($settings['display_event_time']); ?>"
             data-text-export="<?php echo esc_attr($settings['modal_export_text']); ?>"
             data-text-join="<?php echo esc_attr($settings['modal_join_text']); ?>"
             data-text-link="<?php echo esc_attr($settings['modal_link_text']); ?>">
        </div>
        <?php
    }
}