<?php
/**
 * Core plugin class for Level Up Client Dashboard.
 *
 * @package Level_Up_Client_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Level_Up_Client_Dashboard {
    /**
     * Base names for custom tables.
     *
     * @var string
     */
    private static $clients_table  = 'lucd_clients';
    private static $projects_table = 'lucd_projects';
    private static $tickets_table  = 'lucd_tickets';
    private static $billing_table  = 'lucd_billing';
    private static $plugins_table  = 'lucd_plugins';

    /**
     * Initialize the plugin.
     */
    public static function init() {
        if ( is_admin() ) {
            require_once LUCD_PLUGIN_DIR . 'admin/class-lucd-admin.php';
            require_once LUCD_PLUGIN_DIR . 'admin/class-lucd-client-admin.php';
            require_once LUCD_PLUGIN_DIR . 'admin/class-lucd-project-admin.php';
            require_once LUCD_PLUGIN_DIR . 'admin/class-lucd-support-admin.php';
            require_once LUCD_PLUGIN_DIR . 'admin/class-lucd-billing-admin.php';
            require_once LUCD_PLUGIN_DIR . 'admin/class-lucd-plugin-admin.php';

            Level_Up_Client_Dashboard_Admin::init();
            LUC_Client_Admin::init();
            LUC_Project_Admin::init();
            LUC_Support_Admin::init();
            LUC_Billing_Admin::init();
            LUC_Plugin_Admin::init();
        }
    }

    /**
     * Get the full table name with prefix.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     * @param string $base Base table name.
     * @return string
     */
    public static function get_table_name( $base ) {
        global $wpdb;
        return $wpdb->prefix . $base;
    }

    /** Get clients table base name. */
    public static function clients_table() { return self::$clients_table; }

    /** Get projects table base name. */
    public static function projects_table() { return self::$projects_table; }

    /** Get tickets table base name. */
    public static function tickets_table() { return self::$tickets_table; }

    /** Get billing table base name. */
    public static function billing_table() { return self::$billing_table; }

    /** Get plugins table base name. */
    public static function plugins_table() { return self::$plugins_table; }

    /**
     * Plugin activation callback to create required tables.
     */
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $clients_table  = self::get_table_name( self::$clients_table );
        $projects_table = self::get_table_name( self::$projects_table );
        $tickets_table  = self::get_table_name( self::$tickets_table );
        $billing_table  = self::get_table_name( self::$billing_table );
        $plugins_table  = self::get_table_name( self::$plugins_table );

        $clients_sql = "CREATE TABLE $clients_table (
            client_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) unsigned NOT NULL,
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
            UNIQUE KEY email (email),
            UNIQUE KEY wp_user_id (wp_user_id)
        ) $charset_collate;";

        $projects_sql = "CREATE TABLE $projects_table (
            project_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_id bigint(20) unsigned NOT NULL,
            project_name varchar(255) NOT NULL,
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            status varchar(100) DEFAULT '' NOT NULL,
            dev_link varchar(255) DEFAULT '' NOT NULL,
            live_link varchar(255) DEFAULT '' NOT NULL,
            gdrive_link varchar(255) DEFAULT '' NOT NULL,
            project_type varchar(100) DEFAULT '' NOT NULL,
            total_cost decimal(10,2) DEFAULT 0 NOT NULL,
            description text DEFAULT '' NOT NULL,
            project_updates longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (project_id),
            KEY client_id (client_id)
        ) $charset_collate;";

        $tickets_sql = "CREATE TABLE $tickets_table (
            ticket_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_id bigint(20) unsigned NOT NULL,
            subject varchar(255) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (ticket_id),
            KEY client_id (client_id)
        ) $charset_collate;";

        $billing_sql = "CREATE TABLE $billing_table (
            billing_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_id bigint(20) unsigned NOT NULL,
            invoice_number varchar(100) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (billing_id),
            KEY client_id (client_id)
        ) $charset_collate;";

        $plugins_sql = "CREATE TABLE $plugins_table (
            plugin_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_id bigint(20) unsigned NOT NULL,
            plugin_name varchar(255) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (plugin_id),
            KEY client_id (client_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $clients_sql );
        dbDelta( $projects_sql );
        dbDelta( $tickets_sql );
        dbDelta( $billing_sql );
        dbDelta( $plugins_sql );
    }
}
