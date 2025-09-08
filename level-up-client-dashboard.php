<?php
/**
 * Plugin Name:       Level Up Client Dashboard
 * Description:       Client management dashboard for Level Up Marketers.
 * Version:           0.1.0
 * Author:            Level Up Marketers
 *
 * @package Level_Up_Client_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Level_Up_Client_Dashboard {

    /**
     * Admin menu slug.
     *
     * @var string
     */
    private static $menu_slug = 'lucd-client-management';

    /**
     * Initialize plugin hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
    }

    /**
     * Register the Client Management admin menu.
     */
    public static function register_admin_menu() {
        $capability = 'manage_options';

        add_menu_page(
            __( 'Client Management', 'level-up-client-dashboard' ),
            __( 'Client Management', 'level-up-client-dashboard' ),
            $capability,
            self::$menu_slug,
            array( __CLASS__, 'render_client_management_page' ),
            'dashicons-groups',
            26
        );
    }

    /**
     * Render the Client Management admin page with tabs.
     */
    public static function render_client_management_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'level-up-client-dashboard' ) );
        }

        $tabs = array(
            'add-client' => __( 'Add a New Client', 'level-up-client-dashboard' ),
        );

        $current_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? $_GET['tab'] : 'add-client';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Client Management', 'level-up-client-dashboard' ) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $tab => $label ) {
            $class = $tab === $current_tab ? ' nav-tab-active' : '';
            echo '<a class="nav-tab' . esc_attr( $class ) . '" href="' . esc_url( admin_url( 'admin.php?page=' . self::$menu_slug . '&tab=' . $tab ) ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</h2>';

        switch ( $current_tab ) {
            case 'add-client':
            default:
                self::render_add_client_tab();
                break;
        }

        echo '</div>';
    }

    /**
     * Render placeholder content for Add a New Client tab.
     */
    private static function render_add_client_tab() {
        echo '<p>' . esc_html__( 'Placeholder for client creation form.', 'level-up-client-dashboard' ) . '</p>';
    }
}

Level_Up_Client_Dashboard::init();

