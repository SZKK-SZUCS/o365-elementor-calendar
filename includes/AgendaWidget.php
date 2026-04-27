<?php
namespace O365Calendar;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) exit;

class AgendaWidget extends Widget_Base {
    public function get_name() { return 'o365_agenda'; }
    public function get_title() { return 'O365 Agenda Lista'; }
    public function get_icon() { return 'eicon-post-list'; }
    public function get_categories() { return [ 'general' ]; }
    public function get_script_depends() { return [ 'o365-calendar-script' ]; }

    protected function register_controls() {
        // --- ADATOK ELŐKÉSZÍTÉSE A TÖBBFIÓKOS RENDSZERBŐL ---
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

        $this->start_controls_section('section_setup', ['label' => 'Adatforrás & Setup']);
        
        $this->add_control('auth_wizard_trigger', [
            'type' => Controls_Manager::RAW_HTML,
            'raw'  => '<button type="button" id="o365-trigger-wizard" class="elementor-button elementor-button-default" style="width:100%; background:#0073aa;"><i class="eicon-cog"></i> Setup Wizard</button>',
        ]);

        $this->add_control('calendar_id', [
            'label' => 'Naptárak kiválasztása',
            'type' => Controls_Manager::SELECT2,
            'options' => $calendar_options,
            'multiple' => true,
            'label_block' => true,
            'separator' => 'before'
        ]);

        $this->add_control('event_limit', [
            'label' => 'Események száma',
            'type' => Controls_Manager::NUMBER,
            'default' => 5,
        ]);

        $this->add_control('category_filter', [
            'label'       => 'Kategória szűrés',
            'type'        => Controls_Manager::SELECT2,
            'options'     => $category_options,
            'multiple'    => true,
            'label_block' => true,
        ]);

        // ÚJ GOMB: Segít az opciók frissítésében hitelesítés után
        $this->add_control('refresh_lists', [
            'type' => Controls_Manager::RAW_HTML,
            'raw' => '<p style="font-size:11px; line-height:1.2; color:#888; margin-top:10px;">Új fiók hozzáadása után kattints a mentésre, majd frissítsd az oldalt a lista frissítéséhez.</p>',
        ]);

        $this->end_controls_section();

        // --- MEGJELENÍTÉS ÉS STÍLUSOK (ugyanaz mint eddig) ---
        $this->start_controls_section('section_display', ['label' => 'Megjelenítés kapcsolók']);
        $this->add_control('show_date', ['label' => 'Dátum', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_time', ['label' => 'Időpont', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_location', ['label' => 'Helyszín', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_desc', ['label' => 'Leírás', 'type' => Controls_Manager::SWITCHER, 'default' => 'no']);
        $this->add_control('show_export', ['label' => 'iCal gomb', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->end_controls_section();

        $this->start_controls_section('section_style_templates', ['label' => 'Design Sablonok', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('design_template', [
            'label' => 'Válassz alap sablont', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'default',
            'options' => ['default' => 'Weboldal stílusa', 'light' => 'Világos', 'dark' => 'Sötét', 'modern' => 'Modern'],
            'prefix_class' => 'o365-agenda-template-',
        ]);
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
             data-show-export="<?php echo esc_attr($settings['show_export']); ?>">
            <div class="o365-agenda-loading"><div class="spinner"></div></div>
        </div>
        <?php
    }
}