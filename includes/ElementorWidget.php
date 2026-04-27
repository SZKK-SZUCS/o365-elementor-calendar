<?php
namespace O365Calendar;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ElementorWidget extends Widget_Base {

    public function get_name() { 
        return 'o365_calendar'; 
    }
    
    public function get_title() { 
        return __( 'O365 Calendar', 'o365-calendar' ); 
    }
    
    public function get_icon() { 
        return 'eicon-calendar'; 
    }
    
    public function get_categories() { 
        return [ 'general' ]; 
    }
    
    public function get_script_depends() { 
        return [ 'o365-calendar-script' ]; 
    }

    protected function register_controls() {

        // --- CONTENT TAB ---
        $this->start_controls_section('content_section', [
            'label' => __( 'Naptár Adatok', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        // A gomb, amire a külső editor.js reagál
        $this->add_control('auth_wizard_trigger', [
            'type' => Controls_Manager::RAW_HTML,
            'raw'  => '<button type="button" id="o365-trigger-wizard" class="elementor-button elementor-button-default" style="width:100%; background:#0073aa;"><i class="eicon-cog"></i> ' . __( 'Setup Wizard Indítása', 'o365-calendar' ) . '</button>',
        ]);

        $this->add_control('calendar_name', [
            'label'       => __( 'Kiválasztott Naptár', 'o365-calendar' ),
            'type'        => Controls_Manager::TEXT,
            'readonly'    => true, // Csak olvasható státusz adat
            'placeholder' => __( 'Nincs naptár beállítva', 'o365-calendar' ),
            'separator'   => 'before',
        ]);

        $this->add_control('target_email', [ 
            'type' => Controls_Manager::HIDDEN 
        ]);
        
        $this->add_control('calendar_id', [ 
            'type' => Controls_Manager::HIDDEN 
        ]);

        $this->add_control('default_view', [
            'label'   => __( 'Alapértelmezett Nézet', 'o365-calendar' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'dayGridMonth',
            'options' => [
                'dayGridMonth' => __( 'Havi', 'o365-calendar' ),
                'timeGridWeek' => __( 'Heti', 'o365-calendar' ),
                'listWeek'     => __( 'Lista (Heti)', 'o365-calendar' ),
            ],
            'separator' => 'before',
        ]);

        $this->end_controls_section();

        // --- STYLE TAB ---
        $this->start_controls_section('style_calendar', [
            'label' => __( 'Naptár Stílusa', 'o365-calendar' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('header_bg', [
            'label' => __( 'Fejléc Háttér', 'o365-calendar' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [ 
                '{{WRAPPER}} .fc-toolbar' => 'background-color: {{VALUE}}; padding: 15px; border-radius: 8px 8px 0 0;' 
            ],
        ]);

        $this->add_control('event_bg', [
            'label' => __( 'Esemény Színe', 'o365-calendar' ),
            'type' => Controls_Manager::COLOR,
            'default' => '#0073aa',
            'selectors' => [ 
                '{{WRAPPER}} .fc-event' => 'background-color: {{VALUE}} !important; border-color: {{VALUE}} !important;',
                '{{WRAPPER}} .fc-list-event-dot' => 'border-color: {{VALUE}} !important;'
            ],
        ]);

        $this->add_control('text_color', [
            'label' => __( 'Esemény Szöveg Színe', 'o365-calendar' ),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
            'selectors' => [ 
                '{{WRAPPER}} .fc-event-title, {{WRAPPER}} .fc-event-time' => 'color: {{VALUE}} !important;' 
            ],
        ]);

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'cal_typography',
                'label' => __( 'Naptár Tipográfia', 'o365-calendar' ),
                'selector' => '{{WRAPPER}} .fc',
            ]
        );

        $this->add_control('border_color', [
            'label' => __( 'Rács Színe', 'o365-calendar' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [ 
                '{{WRAPPER}} .fc-theme-standard td, {{WRAPPER}} .fc-theme-standard th' => 'border-color: {{VALUE}} !important;' 
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $email  = $settings['target_email'] ?? '';
        $cal_id = $settings['calendar_id'] ?? '';
        $view   = $settings['default_view'] ?? 'dayGridMonth';

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() && ( empty( $email ) || empty( $cal_id ) ) ) {
            echo '<div style="padding:40px; text-align:center; background:#f9f9f9; border:2px dashed #ccc; border-radius:8px; color:#777;">';
            echo '<i class="eicon-calendar" style="font-size:35px; margin-bottom:15px; display:inline-block; color:#0073aa;"></i>';
            echo '<p style="margin:0;">A naptár megjelenítéséhez futtasd le a Setup Wizardot az oldalsávban!</p>';
            echo '</div>';
            return;
        }

        if ( empty( $email ) || empty( $cal_id ) ) {
            return;
        }

        ?>
        <div class="o365-fullcalendar-container" 
             data-email="<?php echo esc_attr( $email ); ?>" 
             data-calendar-id="<?php echo esc_attr( $cal_id ); ?>" 
             data-default-view="<?php echo esc_attr( $view ); ?>">
        </div>
        <?php
    }
}