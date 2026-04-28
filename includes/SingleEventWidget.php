<?php
namespace O365Calendar;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) exit;

class SingleEventWidget extends Widget_Base {
    public function get_name() { return 'o365_single_event'; }
    public function get_title() { return 'O365 Kiemelt Esemény'; }
    public function get_icon() { return 'eicon-single-post'; }
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

        // --- ADATFORRÁS & KERESÉS ---
        $this->start_controls_section('section_data', ['label' => 'Adatforrás & Keresés']);
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
            'label' => 'Naptár választása',
            'type' => Controls_Manager::SELECT2,
            'options' => $calendar_options,
            'multiple' => true,
            'label_block' => true,
        ]);

        $this->add_control('category_filter', [
            'label' => 'Kategória szűrés (Opcionális)',
            'type' => Controls_Manager::SELECT2,
            'options' => $category_options,
            'multiple' => true,
            'label_block' => true,
        ]);

        $this->add_control('event_selection', [
            'label' => 'Kiválasztás módja',
            'type' => Controls_Manager::SELECT,
            'default' => 'next',
            'options' => [
                'next' => 'A soron következő legközelebbi',
                'keyword' => 'Keresés kulcsszó alapján',
            ],
            'separator' => 'before'
        ]);

        $this->add_control('search_keyword', [
            'label' => 'Keresendő kulcsszó',
            'type' => Controls_Manager::TEXT,
            'placeholder' => 'pl. Szülői értekezlet',
            'condition' => ['event_selection' => 'keyword'],
            'description' => 'A rendszer a legelső olyan jövőbeli eseményt mutatja meg, aminek a címében, helyszínében vagy leírásában szerepel ez a kifejezés.'
        ]);

        $this->add_control('search_strictness', [
            'label' => 'Keresés pontossága',
            'type' => Controls_Manager::SELECT,
            'default' => 'contains',
            'options' => [
                'contains' => 'Tartalmazza (Bárhol a címben/leírásban)',
                'exact' => 'Teljes egyezés (Pontosan ez a címe)',
                'starts_with' => 'Ezzel kezdődik (A cím ezzel kezdődik)',
            ],
            'condition' => ['event_selection' => 'keyword'],
        ]);

        $this->end_controls_section();

        // --- MEGJELENÍTÉS KAPCSOLÓK ---
        $this->start_controls_section('section_display', ['label' => 'Megjelenítés']);
        $this->add_control('show_loc', ['label' => 'Helyszín', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_desc', ['label' => 'Leírás (Preview)', 'type' => Controls_Manager::SWITCHER, 'default' => 'no']);
        $this->add_control('show_export', ['label' => 'Hozzáadás naptárhoz gomb', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_countdown', ['label' => 'Visszaszámláló mutatása','type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'description' => 'Egy másodpercenként frissülő visszaszámlálót tesz az esemény mellé.',]);
        $this->end_controls_section();

        // --- LEJÁRAT UTÁNI VISELKEDÉS ---
        $this->start_controls_section('section_expiry', ['label' => 'Üres állapot / Lejárat']);

        $this->add_control('after_expiry', [
            'label' => 'Ha nincs (több) esemény...',
            'type' => Controls_Manager::SELECT,
            'default' => 'hide',
            'options' => [
                'hide' => 'Widget elrejtése',
                'mask' => 'Egyedi szöveg megjelenítése',
            ],
        ]);

        $this->add_control('mask_text', [
            'label' => 'Megjelenő szöveg',
            'type' => Controls_Manager::TEXT,
            'default' => 'Jelenleg nincs tervezett esemény.',
            'condition' => ['after_expiry' => 'mask'],
        ]);

        $this->end_controls_section();

        // --- STÍLUS SABLONOK ---
        $this->start_controls_section('section_style', ['label' => 'Stílus', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('design_template', [
            'label' => 'Design Sablon',
            'type' => Controls_Manager::SELECT,
            'default' => 'light',
            'options' => ['light' => 'Világos Kártya', 'dark' => 'Sötét Kártya'],
            'prefix_class' => 'o365-single-template-',
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name' => 'title_typo',
            'label' => 'Cím Tipográfia',
            'selector' => '{{WRAPPER}} .single-event-title',
        ]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $locale = str_replace('_', '-', get_locale());
        $cal_id = !empty($settings['calendar_id']) ? (is_array($settings['calendar_id']) ? implode(',', $settings['calendar_id']) : $settings['calendar_id']) : '';
        $cat_filter = !empty($settings['category_filter']) ? (is_array($settings['category_filter']) ? implode(',', $settings['category_filter']) : $settings['category_filter']) : '';

        ?>
        <div class="o365-single-event-container" 
             data-calendar-id="<?php echo esc_attr($cal_id); ?>"
             data-category-filter="<?php echo esc_attr($cat_filter); ?>"
             data-search-keyword="<?php echo esc_attr($settings['event_selection'] === 'keyword' ? $settings['search_keyword'] : ''); ?>"
             data-search-strictness="<?php echo esc_attr($settings['search_strictness'] ?? 'contains'); ?>"
             data-expiry-mode="<?php echo esc_attr($settings['after_expiry']); ?>"
             data-mask-text="<?php echo esc_attr($settings['mask_text']); ?>"
             data-show-loc="<?php echo esc_attr($settings['show_loc']); ?>"
             data-show-desc="<?php echo esc_attr($settings['show_desc']); ?>"
             data-show-export="<?php echo esc_attr($settings['show_export']); ?>"
             data-show-countdown="<?php echo esc_attr($settings['show_countdown'] ?? 'yes'); ?>"
             data-locale="<?php echo esc_attr($locale); ?>">
            <div class="o365-single-loading"><div class="spinner"></div></div>
        </div>
        <?php
    }
}