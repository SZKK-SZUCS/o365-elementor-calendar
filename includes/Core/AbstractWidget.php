<?php
namespace O365Calendar\Core;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Közös ősosztály az összes O365 Elementor Widget számára.
 * Tartalmazza az ismétlődő UI logikákat (API státusz, Naptár választó, Üres állapot stílus).
 */
abstract class AbstractWidget extends Widget_Base {

    public function get_categories() {
        return [ 'general' ];
    }

    public function get_script_depends() {
        return [ 'o365-calendar-script' ];
    }

    protected function get_account_options() {
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

        return [
            'calendars'  => $calendar_options,
            'categories' => $category_options,
        ];
    }

    protected function register_api_and_account_controls( $section_label = 'Adatforrás & Setup', $tab = Controls_Manager::TAB_CONTENT ) {
        $this->start_controls_section('section_setup', [
            'label' => $section_label,
            'tab'   => $tab,
        ]);

        $api = new \O365Calendar\API\GraphAPI();
        $status_color = $api->is_configured() ? '#46b450' : '#dc3232';
        $status_text = $api->is_configured() ? 'API Konfigurálva' : 'API Hiba / Hiányzó adatok';
        $acc_count = count( get_option('o365_accounts', []) );

        $this->add_control('api_status_indicator', [
            'type' => Controls_Manager::RAW_HTML,
            'raw' => "<div style='color:#222; padding:12px; background:#f0f0f1; border-left:4px solid {$status_color}; border-radius:3px; margin-bottom:15px; font-size:12px; line-height:1.5;'>
                        <strong>Graph API:</strong> <span style='color:{$status_color};'>{$status_text}</span><br>
                        <strong>Hitelesített fiókok:</strong> {$acc_count} db
                      </div>",
        ]);

        $this->add_control('auth_wizard_trigger', [
            'type' => Controls_Manager::RAW_HTML,
            'raw'  => '<button type="button" id="o365-trigger-wizard" class="elementor-button elementor-button-default" style="width:100%; background:#0073aa;"><i class="eicon-cog"></i> Setup Wizard (Hitelesítés)</button>',
        ]);

        $options = $this->get_account_options();

        $this->add_control('calendar_id', [
            'label'       => __( 'Választott naptárak', 'o365-elementor-calendar' ),
            'type'        => Controls_Manager::SELECT2,
            'options'     => $options['calendars'],
            'multiple'    => true,
            'label_block' => true,
            'separator'   => 'before',
            'description' => '<strong>Fontos:</strong> Új fiók hitelesítése után frissítsd az oldalt (F5), hogy a naptár megjelenjen ebben a listában!',
        ]);

        $this->add_control('category_filter', [
            'label'       => __( 'Kategória szűrés (Opcionális)', 'o365-elementor-calendar' ),
            'type'        => Controls_Manager::SELECT2,
            'options'     => $options['categories'],
            'multiple'    => true,
            'label_block' => true,
            'description' => 'Az összes hitelesített fiók kategóriáinak összevont listája. Ha üresen hagyod, minden esemény megjelenik.',
        ]);

        $this->add_control('resync_trigger', [
            'type' => Controls_Manager::RAW_HTML,
            'raw'  => '<button type="button" id="o365-resync-btn" class="elementor-button elementor-button-success" style="width:100%; margin-top:10px;"><i class="eicon-sync"></i> Események Frissítése</button>',
        ]);

        $this->end_controls_section();
    }

    protected function register_empty_state_style_controls() {
        $this->start_controls_section('section_style_empty', [
            'label' => __( 'Üres állapot / Maszk stílusa', 'o365-elementor-calendar' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('empty_bg_color', [
            'label'     => __( 'Háttérszín', 'o365-elementor-calendar' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .o365-empty, {{WRAPPER}} .o365-single-mask, {{WRAPPER}} .fc-empty-message' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'empty_typography',
            'label'    => __( 'Szöveg tipográfiája', 'o365-elementor-calendar' ),
            'selector' => '{{WRAPPER}} .o365-empty, {{WRAPPER}} .o365-single-mask, {{WRAPPER}} .fc-empty-message',
        ]);

        $this->add_control('empty_text_color', [
            'label'     => __( 'Szöveg színe', 'o365-elementor-calendar' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .o365-empty, {{WRAPPER}} .o365-single-mask, {{WRAPPER}} .fc-empty-message' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('empty_padding', [
            'label'      => __( 'Belső margó (Padding)', 'o365-elementor-calendar' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'selectors'  => [
                '{{WRAPPER}} .o365-empty, {{WRAPPER}} .o365-single-mask, {{WRAPPER}} .fc-empty-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'empty_border',
            'selector' => '{{WRAPPER}} .o365-empty, {{WRAPPER}} .o365-single-mask, {{WRAPPER}} .fc-empty-message',
        ]);

        $this->add_responsive_control('empty_border_radius', [
            'label'      => __( 'Lekerekítés', 'o365-elementor-calendar' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 50 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .o365-empty, {{WRAPPER}} .o365-single-mask, {{WRAPPER}} .fc-empty-message' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }
}