<?php
namespace O365Calendar\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use O365Calendar\Core\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) exit;

class SingleEventWidget extends AbstractWidget {

    public function get_name() { return 'o365_single_event'; }
    public function get_title() { return __( 'O365 Kiemelt Esemény', 'o365-elementor-calendar' ); }
    public function get_icon() { return 'eicon-single-post'; }

    protected function register_controls() {
        
        $this->register_api_and_account_controls( __( 'Adatforrás & Setup', 'o365-elementor-calendar' ) );

        $this->start_controls_section('section_selection', ['label' => __( 'Esemény Kiválasztása', 'o365-elementor-calendar' )]);
        $this->add_control('event_selection', [
            'label'   => 'Kiválasztás módja',
            'type'    => Controls_Manager::SELECT,
            'default' => 'next',
            'options' => [
                'next'    => 'A soron következő legközelebbi',
                'keyword' => 'Keresés kulcsszó alapján',
                'by_id'   => 'Pontos választás listából (ID)',
            ],
            'description' => 'A pontos listás választás garantálja, hogy akkor is ez az esemény fog megjelenni, ha a címe vagy az időpontja módosul.',
        ]);

        $this->add_control('search_keyword', [
            'label'       => 'Keresendő kulcsszó',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => 'pl. Szülői értekezlet',
            'condition'   => ['event_selection' => 'keyword'],
        ]);
        $this->add_control('search_strictness', [
            'label'     => 'Keresés pontossága',
            'type'      => Controls_Manager::SELECT,
            'default'   => 'contains',
            'options'   => [
                'contains'    => 'Tartalmazza (Bárhol)',
                'exact'       => 'Teljes egyezés (Pontos cím)',
                'starts_with' => 'Ezzel kezdődik',
            ],
            'condition' => ['event_selection' => 'keyword'],
        ]);

        $this->add_control('event_picker_btn', [
            'label' => 'Esemény kereső',
            'type' => Controls_Manager::RAW_HTML,
            'raw' => '<button type="button" id="o365-trigger-event-picker" class="elementor-button elementor-button-default" style="width:100%; background:#50adc9;"><i class="eicon-calendar"></i> Kiválasztás listából</button>',
            'condition' => ['event_selection' => 'by_id'],
        ]);

        $this->add_control('event_id', [
            'label'       => 'Kiválasztott Esemény ID',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => 'Kattints a fenti gombra...',
            'label_block' => true,
            'condition'   => ['event_selection' => 'by_id'],
            'description' => 'Ezt az azonosítót a rendszer automatikusan kitölti, ha használtad a fenti gombot.',
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_custom_btns', ['label' => __( 'Egyedi Linkek / Gombok', 'o365-elementor-calendar' )]);
        $this->add_control('btn1_text', ['label' => '1. Gomb Szöveg (pl. Regisztráció)', 'type' => Controls_Manager::TEXT, 'description' => 'Ha üresen hagyod, a gomb nem jelenik meg.']);
        $this->add_control('btn1_url', ['label' => '1. Gomb URL', 'type' => Controls_Manager::URL]);
        $this->add_control('btn2_text', ['label' => '2. Gomb Szöveg (pl. Részletek oldala)', 'type' => Controls_Manager::TEXT, 'separator' => 'before']);
        $this->add_control('btn2_url', ['label' => '2. Gomb URL', 'type' => Controls_Manager::URL]);
        $this->end_controls_section();

        $this->start_controls_section('section_display', ['label' => __( 'Elemek láthatósága', 'o365-elementor-calendar' )]);
        $this->add_control('show_loc', ['label' => 'Helyszín mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_desc', ['label' => 'Leírás mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_export', ['label' => 'Mentés gomb', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_countdown', ['label' => 'Visszaszámláló mutatása', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->end_controls_section();

        $this->start_controls_section('section_expiry', ['label' => __( 'Lejárat / Nincs találat', 'o365-elementor-calendar' )]);
        $this->add_control('after_expiry', [
            'label'   => 'Ha nincs esemény...',
            'type'    => Controls_Manager::SELECT,
            'default' => 'hide',
            'options' => [
                'hide' => 'Widget teljes elrejtése',
                'mask' => 'Egyedi szöveg (Maszk)',
            ],
        ]);
        $this->add_control('mask_text', [
            'label'     => 'Megjelenő szöveg',
            'type'      => Controls_Manager::TEXT,
            'default'   => 'Jelenleg nincs tervezett esemény.',
            'condition' => ['after_expiry' => 'mask'],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_texts', ['label' => __( 'Beépített Szövegek', 'o365-elementor-calendar' )]);
        $this->add_control('text_details', ['label' => 'Lenyíló Részletek gomb', 'type' => Controls_Manager::TEXT, 'default' => 'Részletek']);
        $this->add_control('text_join', ['label' => 'Online Meeting (Teams/Zoom)', 'type' => Controls_Manager::TEXT, 'default' => 'Csatlakozás']);
        $this->add_control('text_link', ['label' => 'Egyéb beágyazott link', 'type' => Controls_Manager::TEXT, 'default' => 'Megnyitás']);
        $this->end_controls_section();

        // --- STÍLUS (STYLE) FÜL ---
        $this->start_controls_section('style_card', ['label' => __( 'Kártya Alapok', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('card_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-single-card' => 'background-color: {{VALUE}};']]);
        
        $this->add_responsive_control('card_radius', [
            'label' => 'Lekerekítés', 
            'type' => Controls_Manager::SLIDER, 
            'size_units' => ['px', 'em', '%'], 
            'range' => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors' => ['{{WRAPPER}} .o365-single-card' => 'border-radius: {{SIZE}}{{UNIT}};']
        ]);
        $this->add_responsive_control('card_padding', ['label' => 'Belső Margó (Felső Sáv)', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em', '%'], 'selectors' => ['{{WRAPPER}} .single-event-main' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'card_shadow', 'selector' => '{{WRAPPER}} .o365-single-card']);
        $this->add_control('card_hover_transform', ['label' => 'Hover Emelkedés', 'type' => Controls_Manager::SLIDER, 'range' => ['px' => ['min' => -10, 'max' => 0]], 'selectors' => ['{{WRAPPER}} .o365-single-card:hover' => 'transform: translateY({{SIZE}}{{UNIT}});']]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'card_hover_shadow', 'label' => 'Hover Árnyék', 'selector' => '{{WRAPPER}} .o365-single-card:hover']);
        $this->end_controls_section();

        $this->start_controls_section('style_badge', ['label' => __( 'Dátum Jelvény', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('badge_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-date-badge' => 'background-color: {{VALUE}};']]);
        
        $this->add_responsive_control('badge_radius', [
            'label' => 'Lekerekítés', 
            'type' => Controls_Manager::SLIDER, 
            'size_units' => ['px', 'em', '%'], 
            'range' => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors' => ['{{WRAPPER}} .single-event-date-badge' => 'border-radius: {{SIZE}}{{UNIT}};']
        ]);
        
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'badge_day_typo', 'label' => 'Nap Tipográfia', 'selector' => '{{WRAPPER}} .single-event-date-badge .day']);
        $this->add_control('badge_day_color', ['label' => 'Nap színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-date-badge .day' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'badge_month_typo', 'label' => 'Hónap Tipográfia', 'selector' => '{{WRAPPER}} .single-event-date-badge .month']);
        $this->add_control('badge_month_color', ['label' => 'Hónap színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-date-badge .month' => 'color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('style_content', ['label' => __( 'Szöveges Tartalom', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'meta_typo', 'label' => 'Dátum/Idő (Meta) Tipográfia', 'selector' => '{{WRAPPER}} .single-event-meta']);
        $this->add_control('meta_color', ['label' => 'Dátum/Idő színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-meta' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'title_typo', 'label' => 'Cím Tipográfia', 'selector' => '{{WRAPPER}} .single-event-title']);
        $this->add_control('title_color', ['label' => 'Cím színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-title' => 'color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('style_countdown', ['label' => __( 'Visszaszámláló', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => ['show_countdown' => 'yes']]);
        $this->add_control('cd_bg', ['label' => 'Háttérszín', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-countdown' => 'background-color: {{VALUE}};']]);
        $this->add_control('cd_border', ['label' => 'Keret színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-countdown' => 'border-color: {{VALUE}};']]);
        
        $this->add_responsive_control('cd_radius', [
            'label' => 'Lekerekítés', 
            'type' => Controls_Manager::SLIDER, 
            'size_units' => ['px', 'em', '%'], 
            'range' => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors' => ['{{WRAPPER}} .single-event-countdown' => 'border-radius: {{SIZE}}{{UNIT}};']
        ]);
        
        $this->add_control('cd_number_color', ['label' => 'Számok Színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .cd-val, {{WRAPPER}} .cd-started' => 'color: {{VALUE}};']]);
        $this->add_control('cd_label_color', ['label' => 'Címkék (nap, ó, p) Színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .cd-label' => 'color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('style_panel', ['label' => __( 'Lenyíló Panel (Részletek)', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('panel_bg', ['label' => 'Panel Háttérszíne', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-panel' => 'background-color: {{VALUE}};']]);
        $this->add_control('panel_border', ['label' => 'Elválasztó vonal (Fent)', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-panel' => 'border-top-color: {{VALUE}};']]);
        $this->add_responsive_control('panel_padding', ['label' => 'Belső Margó (Padding)', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em', '%'], 'selectors' => ['{{WRAPPER}} .single-event-panel-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('panel_loc_color', ['label' => 'Helyszín színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-loc' => 'color: {{VALUE}};']]);
        $this->add_control('panel_loc_icon', ['label' => 'Helyszín ikon színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-loc svg' => 'color: {{VALUE}};']]);
        $this->add_control('panel_desc_color', ['label' => 'Leírás színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-desc' => 'color: {{VALUE}};']]);
        $this->add_control('panel_link_color', ['label' => 'Linkek színe', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .single-event-desc a' => 'color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('style_buttons', ['label' => __( 'Akció Gombok', 'o365-elementor-calendar' ), 'tab' => Controls_Manager::TAB_STYLE]);
        
        $this->add_responsive_control('btn_radius', [
            'label' => 'Lekerekítés', 
            'type' => Controls_Manager::SLIDER, 
            'size_units' => ['px', 'em', '%'], 
            'range' => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors' => ['{{WRAPPER}} .o365-single-toggle-btn, {{WRAPPER}} .o365-single-export, {{WRAPPER}} .o365-meeting-btn, {{WRAPPER}} .o365-custom-action-btn' => 'border-radius: {{SIZE}}{{UNIT}};']
        ]);
        
        $this->start_controls_tabs('tabs_single_btns');
        $this->start_controls_tab('tab_sbtn_normal', ['label' => 'Normál']);
        $this->add_control('toggle_bg', ['label' => 'Részletek Gomb Háttér', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-single-toggle-btn' => 'background-color: {{VALUE}};']]);
        $this->add_control('toggle_color', ['label' => 'Részletek Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-single-toggle-btn' => 'color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('exp_bg', ['label' => 'Export Gomb Háttér', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => ['{{WRAPPER}} .o365-single-export' => 'background-color: {{VALUE}};']]);
        $this->add_control('exp_color', ['label' => 'Export Gomb Ikon', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-single-export' => 'color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('meet_bg', ['label' => 'Meeting Gomb Háttér', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => ['{{WRAPPER}} .o365-meeting-btn, {{WRAPPER}} .o365-custom-action-btn' => 'background-color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('meet_color', ['label' => 'Meeting Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-meeting-btn, {{WRAPPER}} .o365-custom-action-btn' => 'color: {{VALUE}};']]);
        $this->end_controls_tab();
        
        $this->start_controls_tab('tab_sbtn_hover', ['label' => 'Hover']);
        $this->add_control('toggle_h_bg', ['label' => 'Részletek Gomb Háttér', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-single-toggle-btn:hover, {{WRAPPER}} .o365-single-toggle-btn.is-open' => 'background-color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('toggle_h_color', ['label' => 'Részletek Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-single-toggle-btn:hover, {{WRAPPER}} .o365-single-toggle-btn.is-open' => 'color: {{VALUE}};']]);
        $this->add_control('exp_h_bg', ['label' => 'Export Gomb Háttér', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => ['{{WRAPPER}} .o365-single-export:hover' => 'background-color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('exp_h_color', ['label' => 'Export Gomb Ikon', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-single-export:hover' => 'color: {{VALUE}};']]);
        $this->add_control('meet_h_bg', ['label' => 'Meeting Gomb Háttér', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => ['{{WRAPPER}} .o365-meeting-btn:hover, {{WRAPPER}} .o365-custom-action-btn:hover' => 'background-color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('meet_h_color', ['label' => 'Meeting Gomb Szöveg', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .o365-meeting-btn:hover, {{WRAPPER}} .o365-custom-action-btn:hover' => 'color: {{VALUE}};']]);
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
        <div class="o365-single-event-container" 
             data-calendar-id="<?php echo esc_attr($cal_ids); ?>"
             data-category-filter="<?php echo esc_attr($cat_filter); ?>"
             data-event-selection="<?php echo esc_attr($settings['event_selection']); ?>"
             data-event-id="<?php echo esc_attr($settings['event_id'] ?? ''); ?>"
             data-search-keyword="<?php echo esc_attr($settings['search_keyword'] ?? ''); ?>"
             data-search-strictness="<?php echo esc_attr($settings['search_strictness'] ?? 'contains'); ?>"
             data-expiry-mode="<?php echo esc_attr($settings['after_expiry']); ?>"
             data-mask-text="<?php echo esc_attr($settings['mask_text']); ?>"
             data-show-loc="<?php echo esc_attr($settings['show_loc']); ?>"
             data-show-desc="<?php echo esc_attr($settings['show_desc']); ?>"
             data-show-export="<?php echo esc_attr($settings['show_export']); ?>"
             data-show-countdown="<?php echo esc_attr($settings['show_countdown']); ?>"
             data-locale="<?php echo esc_attr($locale); ?>"
             data-text-details="<?php echo esc_attr($settings['text_details']); ?>"
             data-text-join="<?php echo esc_attr($settings['text_join']); ?>"
             data-text-link="<?php echo esc_attr($settings['text_link']); ?>"
             
             data-btn1-text="<?php echo esc_attr($settings['btn1_text'] ?? ''); ?>"
             data-btn1-url="<?php echo esc_url($settings['btn1_url']['url'] ?? ''); ?>"
             data-btn1-target="<?php echo esc_attr(!empty($settings['btn1_url']['is_external']) ? '_blank' : '_self'); ?>"
             data-btn2-text="<?php echo esc_attr($settings['btn2_text'] ?? ''); ?>"
             data-btn2-url="<?php echo esc_url($settings['btn2_url']['url'] ?? ''); ?>"
             data-btn2-target="<?php echo esc_attr(!empty($settings['btn2_url']['is_external']) ? '_blank' : '_self'); ?>">
            <div class="o365-single-loading"><div class="spinner"></div></div>
        </div>
        <?php
    }
}