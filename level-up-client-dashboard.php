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
     * Base name for clients table.
     *
     * @var string
     */
    private static $clients_table = 'lucd_clients';

    /**
     * Initialize plugin hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
    }

    /**
     * Get the full clients table name with prefix.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return string
     */
    private static function get_clients_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::$clients_table;
    }

    /**
     * Plugin activation callback to create required tables.
     */
    public static function activate() {
        global $wpdb;

        $table_name      = self::get_clients_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            client_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            mailing_address1 varchar(255) DEFAULT '' NOT NULL,
            mailing_address2 varchar(255) DEFAULT '' NOT NULL,
            mailing_city varchar(100) DEFAULT '' NOT NULL,
            mailing_state varchar(100) DEFAULT '' NOT NULL,
            mailing_postcode varchar(20) DEFAULT '' NOT NULL,
            mailing_country varchar(100) DEFAULT '' NOT NULL,
            company_name varchar(255) NOT NULL,
            company_website varchar(255) DEFAULT '' NOT NULL,
            company_address1 varchar(255) DEFAULT '' NOT NULL,
            company_address2 varchar(255) DEFAULT '' NOT NULL,
            company_city varchar(100) DEFAULT '' NOT NULL,
            company_state varchar(100) DEFAULT '' NOT NULL,
            company_postcode varchar(20) DEFAULT '' NOT NULL,
            company_country varchar(100) DEFAULT '' NOT NULL,
            social_facebook varchar(255) DEFAULT '' NOT NULL,
            social_twitter varchar(255) DEFAULT '' NOT NULL,
            social_instagram varchar(255) DEFAULT '' NOT NULL,
            social_linkedin varchar(255) DEFAULT '' NOT NULL,
            social_yelp varchar(255) DEFAULT '' NOT NULL,
            social_bbb varchar(255) DEFAULT '' NOT NULL,
            client_since date NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (client_id),
            UNIQUE KEY email (email)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
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

register_activation_hook( __FILE__, array( 'Level_Up_Client_Dashboard', 'activate' ) );
Level_Up_Client_Dashboard::init();

