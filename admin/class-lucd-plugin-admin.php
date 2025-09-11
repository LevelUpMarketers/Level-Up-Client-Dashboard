<?php
/**
 * Plugin management admin functionality.
 *
 * @package Level_Up_Client_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LUC_Plugin_Admin {
    const MENU_SLUG = 'lucd-plugin-management';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
    }

    public static function register_menu() {
        add_menu_page(
            __( 'Plugin Management', 'level-up-client-dashboard' ),
            __( 'Plugin Management', 'level-up-client-dashboard' ),
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_page' ),
            'dashicons-admin-plugins',
            60.5
        );
    }

    public static function render_page() {
        $tabs = array(
            'overview' => array(
                'label'    => __( 'Overview', 'level-up-client-dashboard' ),
                'callback' => array( 'Level_Up_Client_Dashboard_Admin', 'render_placeholder_tab' ),
            ),
        );
        Level_Up_Client_Dashboard_Admin::render_management_page( __( 'Plugin Management', 'level-up-client-dashboard' ), self::MENU_SLUG, $tabs );
    }
}
