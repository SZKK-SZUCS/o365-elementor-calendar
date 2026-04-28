<?php
namespace O365Calendar;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

if ( ! defined( 'ABSPATH' ) ) exit;

class AgendaWidget extends Widget_Base {
    public function get_name() { return 'o365_agenda'; }
    public function get_title() { return 'O365 Agenda Lista'; }
    public function get_icon() { return 'eicon-post-list'; }
    public function get_categories() { return [ 'general' ]; }
    public function get_script_depends() { return [ 'o365-calendar-script' ]; }

    protected function register_controls() {
        $accounts = get_option( 'o365_accounts', [] );
        $calendar_options = [];
        $category_options = [];

        foreach ( $accounts as $acc_email => $data ) {
            if ( !empty($data['calendars']) ) {
                foreach ( $data['calendars'] as $id => $name ) {
                    $calendar_options["{$acc_email}|{$id}"] = "[{$acc_email}] {$name}";
                }
            }
            if ( !empty($data['categories']) ) {
                foreach ( $data['categories'] as $cat ) {
                    $category_options[$cat] = $cat;
                }
            }
        }

        // --- TARTALOM FÜL ---
        $this->start_controls_section('section_setup', ['label' => 'Adatforrás & Setup']);
        $this->add_control('auth_wizard_trigger', ['type' => Controls_Manager::RAW_HTML, 'raw' => '<button type="button" id="o365-trigger-wizard" class="elementor-button elementor-button-default" style="width:100%; background:#0073aa;"><i class="eicon-cog"></i> Setup Wizard</button>']);
        $this->add_control('calendar_id', ['label' => 'Naptárak', 'type' => Controls_Manager::SELECT2, 'options' => $calendar_options, 'multiple' => true, 'label_block' => true, 'separator' => 'before']);
        $this->add_control('event_limit', ['label' => 'Események száma', 'type' => Controls_Manager::NUMBER, 'default' => 5]);
        $this->add_control('category_filter', ['label' => 'Kategória szűrés', 'type' => Controls_Manager::SELECT2, 'options' => $category_options, 'multiple' => true, 'label_block' => true]);
        $this->end_controls_section();

        $this->start_controls_section('section_display', ['label' => 'Elemek láthatósága']);
        $this->add_control('grouping_mode', ['label' => 'Csoportosítás', 'type' => Controls_Manager::SELECT, 'default' => 'none', 'options' => [ 'none' => 'Nincs (Ömlesztett lista)', 'month' => 'Hónapok szerint', 'day' => 'Napok szerint',]]);
        $this->add_control('show_date', ['label' => 'Dátum mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_time', ['label' => 'Időpont mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_location', ['label' => 'Helyszín mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_desc', ['label' => 'Leírás mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'no']);
        $this->add_control('show_export', ['label' => 'iCal Letöltés gomb', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('enable_modal', ['label' => 'Részletek Modal ablak', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'description' => 'Kattinthatóvá teszi az eseményt a részletekért.']);
        $this->add_control('show_load_more', [
            'label' => 'Több betöltése gomb',
            'type' => Controls_Manager::SWITCHER,
            'default' => 'yes',
            'separator' => 'before'
        ]);

        $this->add_control('load_more_text', [
            'label' => 'Gomb felirata',
            'type' => Controls_Manager::TEXT,
            'default' => 'További események betöltése',
            'condition' => ['show_load_more' => 'yes'],
        ]);
        $this->end_controls_section();

        // --- STÍLUS FÜL ---
        $this->start_controls_section('style_general', ['label' => 'Általános lista stílus', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Background::get_type(), ['name' => 'list_bg', 'selector' => '{{WRAPPER}} .o365-agenda-list']);
        $this->add_control('item_padding', ['label' => 'Elemek belső margója', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .o365-agenda-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('item_border_color', ['label' => 'Elválasztó vonal színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-item' => 'border-bottom-color: {{VALUE}};']]);
        $this->add_control('item_hover_bg', ['label' => 'Hover háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-item:hover' => 'background-color: {{VALUE}};']]);
        $this->add_responsive_control('list_max_height', [
            'label' => 'Lista maximális magassága',
            'type' => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'vh' ],
            'range' => [
                'px' => [ 'min' => 200, 'max' => 1000 ],
            ],
            'selectors' => [
                '{{WRAPPER}} .o365-agenda-list-wrapper' => 'max-height: {{SIZE}}{{UNIT}};',
            ],
            'description' => 'Ha a lista hosszabb ennél, belső görgetősáv jelenik meg.',
        ]);
        $this->end_controls_section();

        $this->start_controls_section('style_title', ['label' => 'Esemény Címe', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'title_typo', 'selector' => '{{WRAPPER}} .agenda-title']);
        $this->add_control('title_color', ['label' => 'Cím színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-title' => 'color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('style_meta', ['label' => 'Időpont & Dátum', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('date_color', ['label' => 'Dátum színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-date' => 'color: {{VALUE}};']]);
        $this->add_control('time_color', ['label' => 'Időpont színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .agenda-time' => 'color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('style_export_btn', ['label' => 'iCal Gomb', 'tab' => Controls_Manager::TAB_STYLE, 'condition' => ['show_export' => 'yes']]);
        $this->add_control('btn_color', ['label' => 'Ikon színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-export' => 'color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('btn_hover_color', ['label' => 'Hover szín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-agenda-export:hover' => 'background-color: {{VALUE}}; color: #fff; border-color: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $cal_id = !empty($settings['calendar_id']) ? (is_array($settings['calendar_id']) ? implode(',', $settings['calendar_id']) : $settings['calendar_id']) : '';
        $cat_filter = !empty($settings['category_filter']) ? (is_array($settings['category_filter']) ? implode(',', $settings['category_filter']) : $settings['category_filter']) : '';

        ?>
        <div class="o365-agenda-container" 
             data-calendar-id="<?php echo esc_attr($cal_id); ?>"
             data-limit="<?php echo esc_attr($settings['event_limit']); ?>"
             data-category-filter="<?php echo esc_attr($cat_filter); ?>"
             data-show-date="<?php echo esc_attr($settings['show_date']); ?>"
             data-show-time="<?php echo esc_attr($settings['show_time']); ?>"
             data-show-loc="<?php echo esc_attr($settings['show_location']); ?>"
             data-show-desc="<?php echo esc_attr($settings['show_desc']); ?>"
             data-show-export="<?php echo esc_attr($settings['show_export']); ?>"
             data-enable-modal="<?php echo esc_attr($settings['enable_modal']); ?>"
             data-grouping="<?php echo esc_attr($settings['grouping_mode'] ?? 'none'); ?>"
             data-show-load-more="<?php echo esc_attr($settings['show_load_more'] ?? 'yes'); ?>"
             data-load-more-text="<?php echo esc_attr($settings['load_more_text'] ?? 'További események betöltése'); ?>">
            
            <div class="o365-agenda-list-wrapper">
                <div class="o365-agenda-loading"><div class="spinner"></div></div>
            </div>
            
            <div class="o365-agenda-footer"></div>
        </div>
        <?php
    }
}