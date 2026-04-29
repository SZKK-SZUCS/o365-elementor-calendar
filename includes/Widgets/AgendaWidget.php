<?php
namespace O365Calendar\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use O365Calendar\Core\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) exit;

class AgendaWidget extends AbstractWidget {

    public function get_name() { return 'o365_agenda'; }
    public function get_title() { return __( 'O365 Agenda Lista', 'o365-elementor-calendar' ); }
    public function get_icon() { return 'eicon-post-list'; }

    protected function register_controls() {
        
        $this->register_api_and_account_controls( __( 'Adatforrás & Setup', 'o365-elementor-calendar' ) );

        $this->start_controls_section('section_config', ['label' => __( 'Agenda Beállítások', 'o365-elementor-calendar' )]);
        $this->add_control('event_limit', [
            'label' => __( 'Alapértelmezett elemszám', 'o365-elementor-calendar' ), 
            'type' => Controls_Manager::NUMBER, 
            'default' => 5,
            'description' => 'A kezdetben betöltött események száma. Ha van \'Több betöltése\' gomb, ennyivel növekszik a lista.'
        ]);
        $this->add_control('grouping_mode', [
            'label'   => __( 'Csoportosítás', 'o365-elementor-calendar' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'none',
            'options' => [
                'none'  => __( 'Nincs (Ömlesztett lista)', 'o365-elementor-calendar' ),
                'month' => __( 'Hónapok szerint', 'o365-elementor-calendar' ),
                'day'   => __( 'Napok szerint', 'o365-elementor-calendar' ),
            ],
            'description' => 'Vizuális szekciók és elválasztók beillesztése a listába a könnyebb átláthatóságért.',
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_display', ['label' => __( 'Elemek láthatósága', 'o365-elementor-calendar' )]);
        $this->add_control('show_date', ['label' => 'Dátum mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_time', ['label' => 'Időpont mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_location', ['label' => 'Helyszín mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_desc', ['label' => 'Leírás mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'no']);
        $this->add_control('show_export', ['label' => 'Naptárba mentés gomb', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('enable_modal', ['label' => 'Részletek Modal engedélyezése', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_load_more', ['label' => 'Több betöltése gomb', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'separator' => 'before']);
        $this->end_controls_section();

        $this->start_controls_section('section_texts', ['label' => __( 'Szövegek és Címkék', 'o365-elementor-calendar' )]);
        $this->add_control('load_more_text', ['label' => 'Több betöltése gomb', 'type' => Controls_Manager::TEXT, 'default' => 'További események betöltése', 'condition' => ['show_load_more' => 'yes']]);
        $this->add_control('modal_export_text', ['label' => 'Letöltés gomb szövege (Modal)', 'type' => Controls_Manager::TEXT, 'default' => 'Mentés naptárba']);
        $this->add_control('modal_link_text', ['label' => 'Link megnyitása szöveg', 'type' => Controls_Manager::TEXT, 'default' => 'Megnyitás']);
        $this->add_control('modal_join_text', ['label' => 'Online Meeting szöveg', 'type' => Controls_Manager::TEXT, 'default' => 'Csatlakozás']);
        $this->end_controls_section();

        $this->start_controls_section('style_general', ['label' => __( 'Lista Alapok', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('list_max_height', ['label' => 'Lista max magassága (Görgetéshez)', 'type' => Controls_Manager::SLIDER, 'size_units' => [ 'px', 'vh' ], 'range' => ['px' => ['min' => 200, 'max' => 1000]], 'selectors' => ['{{WRAPPER}} .o365-agenda-list-wrapper' => 'max-height: {{SIZE}}{{UNIT}};']]);
        $this->add_group_control(Group_Control_Background::get_type(), ['name' => 'list_bg', 'selector' => '{{WRAPPER}} .o365-agenda-list']);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'list_border', 'selector' => '{{WRAPPER}} .o365-agenda-list']);
        
        $this->add_responsive_control('list_border_radius', [
            'label' => 'Lekerekítés', 
            'type' => Controls_Manager::SLIDER, 
            'size_units' => ['px', 'em', '%'],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors' => ['{{WRAPPER}} .o365-agenda-list' => 'border-radius: {{SIZE}}{{UNIT}};']
        ]);
        
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'list_shadow', 'selector' => '{{WRAPPER}} .o365-agenda-list']);
        $this->end_controls_section();

        $this->start_controls_section('style_group_header', ['label' => __( 'Csoport Fejléc', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => ['grouping_mode!' => 'none']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'gh_typo', 'selector' => '{{WRAPPER}} .agenda-group-header']);
        $this->add_control('gh_color', ['label' => 'Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-group-header' => 'color: {{VALUE}};']]);
        $this->add_control('gh_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-group-header' => 'background-color: {{VALUE}};']]);
        $this->add_control('gh_border_color', ['label' => 'Alsó/Felső vonal színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-group-header' => 'border-color: {{VALUE}};']]);
        $this->add_responsive_control('gh_padding', ['label' => 'Belső margó (Padding)', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em', '%'], 'selectors' => ['{{WRAPPER}} .agenda-group-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->end_controls_section();

        $this->start_controls_section('style_item', ['label' => __( 'Lista Elemek (Sorok)', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('item_padding', ['label' => 'Belső margó (Padding)', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em', '%'], 'selectors' => ['{{WRAPPER}} .o365-agenda-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('item_border_color', ['label' => 'Elválasztó vonal színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-item' => 'border-bottom-color: {{VALUE}};']]);
        $this->start_controls_tabs('tabs_items');
        $this->start_controls_tab('tab_item_normal', ['label' => 'Normál']);
        $this->add_control('item_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-item' => 'background-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_item_hover', ['label' => 'Hover']);
        $this->add_control('item_hover_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-item:hover' => 'background-color: {{VALUE}};']]);
        $this->add_responsive_control('item_hover_transform', ['label' => 'Emelkedés (Y tengely)', 'type' => Controls_Manager::SLIDER, 'range' => ['px' => ['min' => -10, 'max' => 0]], 'selectors' => ['{{WRAPPER}} .o365-agenda-item:hover' => 'transform: translateY({{SIZE}}{{UNIT}});']]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'item_hover_shadow', 'selector' => '{{WRAPPER}} .o365-agenda-item:hover']);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section('style_meta', ['label' => __( 'Dátum és Időpont', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('meta_width', [
            'label' => 'Dátum oszlop szélessége', 
            'type' => Controls_Manager::SLIDER, 
            'size_units' => ['px', '%', 'em'],
            'range' => ['px' => ['min' => 40, 'max' => 250]], 
            'selectors' => ['{{WRAPPER}} .agenda-meta' => 'min-width: {{SIZE}}{{UNIT}};']
        ]);
        $this->add_control('meta_date_heading', ['label' => 'Dátum', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'date_typo', 'selector' => '{{WRAPPER}} .agenda-date']);
        $this->add_control('date_color', ['label' => 'Dátum színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-date' => 'color: {{VALUE}};']]);
        $this->add_control('meta_time_heading', ['label' => 'Időpont', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'time_typo', 'selector' => '{{WRAPPER}} .agenda-time']);
        $this->add_control('time_color', ['label' => 'Időpont színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-time' => 'color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('style_content', ['label' => __( 'Tartalom (Cím, Hely, Leírás)', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('content_title_heading', ['label' => 'Cím', 'type' => Controls_Manager::HEADING]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'title_typo', 'selector' => '{{WRAPPER}} .agenda-title']);
        $this->add_control('title_color', ['label' => 'Cím színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-title' => 'color: {{VALUE}};']]);
        
        $this->add_control('content_loc_heading', ['label' => 'Helyszín', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'loc_typo', 'selector' => '{{WRAPPER}} .agenda-loc']);
        $this->add_control('loc_color', ['label' => 'Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-loc' => 'color: {{VALUE}};']]);
        $this->add_control('loc_icon_color', ['label' => 'Ikon színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-loc svg' => 'color: {{VALUE}};']]);
        
        $this->add_control('content_desc_heading', ['label' => 'Leírás', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'desc_typo', 'selector' => '{{WRAPPER}} .agenda-desc']);
        $this->add_control('desc_color', ['label' => 'Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-desc' => 'color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('style_inline_buttons', ['label' => __( 'Soron belüli Gombok', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('btn_radius', [
            'label' => 'Lekerekítés', 
            'type' => Controls_Manager::SLIDER, 
            'size_units' => ['px', 'em', '%'],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors' => ['{{WRAPPER}} .o365-agenda-export, {{WRAPPER}} .o365-agenda-meeting-btn' => 'border-radius: {{SIZE}}{{UNIT}};']
        ]);
        $this->start_controls_tabs('tabs_inline_btns');
        $this->start_controls_tab('tab_inbtn_normal', ['label' => 'Normál']);
        $this->add_control('btn_color', ['label' => 'Ikon/Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-export, {{WRAPPER}} .o365-agenda-meeting-btn' => 'color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('btn_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-export, {{WRAPPER}} .o365-agenda-meeting-btn' => 'background-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_inbtn_hover', ['label' => 'Hover']);
        $this->add_control('btn_h_color', ['label' => 'Ikon/Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-export:hover, {{WRAPPER}} .o365-agenda-meeting-btn:hover' => 'color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('btn_h_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-export:hover, {{WRAPPER}} .o365-agenda-meeting-btn:hover' => 'background-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section('style_load_more', ['label' => __( 'Több betöltése gomb', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => ['show_load_more' => 'yes']]);
        $this->add_control('lm_footer_bg', ['label' => 'Footer sáv háttere', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-footer' => 'background-color: {{VALUE}};']]);
        $this->add_control('lm_footer_border', ['label' => 'Footer elválasztó vonal', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-footer' => 'border-top-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'lm_typo', 'selector' => '{{WRAPPER}} .o365-load-more-btn', 'separator' => 'before']);
        
        $this->add_responsive_control('lm_radius', [
            'label' => 'Lekerekítés', 
            'type' => Controls_Manager::SLIDER, 
            'size_units' => ['px', 'em', '%'],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors' => ['{{WRAPPER}} .o365-load-more-btn' => 'border-radius: {{SIZE}}{{UNIT}};']
        ]);
        
        $this->start_controls_tabs('tabs_lm_btns');
        $this->start_controls_tab('tab_lm_normal', ['label' => 'Normál']);
        $this->add_control('lm_color', ['label' => 'Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-load-more-btn' => 'color: {{VALUE}};']]);
        $this->add_control('lm_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-load-more-btn' => 'background-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_lm_hover', ['label' => 'Hover']);
        $this->add_control('lm_h_color', ['label' => 'Szöveg színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-load-more-btn:hover' => 'color: {{VALUE}};']]);
        $this->add_control('lm_h_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-load-more-btn:hover' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'lm_h_shadow', 'selector' => '{{WRAPPER}} .o365-load-more-btn:hover']);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section('style_modal', ['label' => __( 'Popup Modal (Részletek)', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => ['enable_modal' => 'yes']]);
        $this->add_control('modal_overlay_bg', ['label' => 'Háttér sötétítés (Overlay)', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-event-modal-overlay' => 'background-color: {{VALUE}};']]);
        $this->add_control('modal_box_bg', ['label' => 'Modal Doboz Háttér', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-event-modal' => 'background-color: {{VALUE}};']]);
        
        $this->add_responsive_control('modal_radius', [
            'label' => 'Modal Lekerekítés', 
            'type' => Controls_Manager::SLIDER, 
            'size_units' => ['px', 'em', '%'],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors' => ['{{WRAPPER}} .o365-event-modal' => 'border-radius: {{SIZE}}{{UNIT}};']
        ]);
        
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'modal_shadow', 'selector' => '{{WRAPPER}} .o365-event-modal']);
        $this->add_control('modal_title_heading', ['label' => 'Tipográfia és Színek', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('modal_title_color', ['label' => 'Cím Színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-modal-title' => 'color: {{VALUE}};']]);
        $this->add_control('modal_meta_color', ['label' => 'Idő & Helyszín Színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-modal-meta' => 'color: {{VALUE}};']]);
        $this->add_control('modal_icon_color', ['label' => 'Ikonok Színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-modal-meta svg' => 'color: {{VALUE}};']]);
        $this->add_control('modal_desc_color', ['label' => 'Leírás Színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-modal-desc' => 'color: {{VALUE}};']]);
        $this->add_control('modal_link_color', ['label' => 'Leírásban lévő linkek színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-modal-desc a' => 'color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('style_modal_buttons', ['label' => __( 'Modal Akciógombok', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => ['enable_modal' => 'yes']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'modal_btn_typo', 'selector' => '{{WRAPPER}} .o365-export-ical-btn, {{WRAPPER}} .o365-meeting-btn']);
        
        $this->add_responsive_control('modal_btn_radius', [
            'label' => 'Gomb Lekerekítés', 
            'type' => Controls_Manager::SLIDER, 
            'size_units' => ['px', 'em', '%'],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors' => ['{{WRAPPER}} .o365-export-ical-btn, {{WRAPPER}} .o365-meeting-btn' => 'border-radius: {{SIZE}}{{UNIT}};']
        ]);
        
        $this->start_controls_tabs('tabs_modal_btns');
        $this->start_controls_tab('tab_mb_normal', ['label' => 'Normál']);
        $this->add_control('mb_export_bg', ['label' => 'Export Gomb Háttér', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-export-ical-btn' => 'background-color: {{VALUE}};']]);
        $this->add_control('mb_export_color', ['label' => 'Export Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-export-ical-btn' => 'color: {{VALUE}};']]);
        $this->add_control('mb_meet_bg', ['label' => 'Meeting Gomb Háttér', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => ['{{WRAPPER}} .o365-meeting-btn' => 'background-color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('mb_meet_color', ['label' => 'Meeting Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-meeting-btn' => 'color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_mb_hover', ['label' => 'Hover']);
        $this->add_control('mb_export_h_bg', ['label' => 'Export Gomb Háttér', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-export-ical-btn:hover' => 'background-color: {{VALUE}};']]);
        $this->add_control('mb_export_h_color', ['label' => 'Export Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-export-ical-btn:hover' => 'color: {{VALUE}};']]);
        $this->add_control('mb_meet_h_bg', ['label' => 'Meeting Gomb Háttér', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => ['{{WRAPPER}} .o365-meeting-btn:hover' => 'background-color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('mb_meet_h_color', ['label' => 'Meeting Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-meeting-btn:hover' => 'color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->register_empty_state_style_controls();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $locale   = str_replace( '_', '-', get_locale() );
        
        $cal_ids    = !empty($settings['calendar_id']) ? implode(',', (array)$settings['calendar_id']) : '';
        $cat_filter = !empty($settings['category_filter']) ? implode(',', (array)$settings['category_filter']) : '';

        ?>
        <div class="o365-agenda-container" 
             data-calendar-id="<?php echo esc_attr($cal_ids); ?>"
             data-limit="<?php echo esc_attr($settings['event_limit']); ?>"
             data-category-filter="<?php echo esc_attr($cat_filter); ?>"
             data-show-date="<?php echo esc_attr($settings['show_date']); ?>"
             data-show-time="<?php echo esc_attr($settings['show_time']); ?>"
             data-show-loc="<?php echo esc_attr($settings['show_location']); ?>"
             data-show-desc="<?php echo esc_attr($settings['show_desc']); ?>"
             data-show-export="<?php echo esc_attr($settings['show_export']); ?>"
             data-enable-modal="<?php echo esc_attr($settings['enable_modal']); ?>"
             data-grouping="<?php echo esc_attr($settings['grouping_mode']); ?>"
             data-show-load-more="<?php echo esc_attr($settings['show_load_more']); ?>"
             data-load-more-text="<?php echo esc_attr($settings['load_more_text']); ?>"
             data-locale="<?php echo esc_attr($locale); ?>"
             data-text-export="<?php echo esc_attr($settings['modal_export_text']); ?>"
             data-text-join="<?php echo esc_attr($settings['modal_join_text']); ?>"
             data-text-link="<?php echo esc_attr($settings['modal_link_text']); ?>">
            
            <div class="o365-agenda-list-wrapper">
                <div class="o365-agenda-loading"><div class="spinner"></div></div>
            </div>
            
            <div class="o365-agenda-footer"></div>
        </div>
        <?php
    }
}