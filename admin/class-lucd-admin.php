<?php
/**
 * Shared admin functionality for Level Up Client Dashboard.
 *
 * @package Level_Up_Client_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Level_Up_Client_Dashboard_Admin {
    /**
     * Initialize shared admin features.
     */
    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Enqueue admin scripts and styles for plugin pages.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_assets( $hook ) {
        if ( false === strpos( $hook, 'lucd-' ) ) {
            return;
        }

        wp_enqueue_style( 'lucd-admin', plugins_url( 'assets/css/admin.css', LUCD_PLUGIN_FILE ) );
        wp_enqueue_script( 'lucd-admin', plugins_url( 'assets/js/admin.js', LUCD_PLUGIN_FILE ), array( 'jquery', 'jquery-ui-autocomplete' ), false, true );

        global $wpdb;
        $client_table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        $client_rows  = $wpdb->get_results( "SELECT client_id, company_name, first_name, last_name FROM $client_table ORDER BY company_name ASC, last_name ASC", ARRAY_A );
        $clients      = array();
        foreach ( $client_rows as $row ) {
            $name    = trim( $row['first_name'] . ' ' . $row['last_name'] );
            $company = trim( $row['company_name'] );
            $label   = $company ? $company . ' - ' . $name : $name;
            $clients[] = array(
                'id'    => (int) $row['client_id'],
                'label' => $label,
            );
        }

        wp_localize_script( 'lucd-admin', 'lucdAdmin', array(
            'getClientNonce'   => wp_create_nonce( 'lucd_get_client' ),
            'getProjectsNonce' => wp_create_nonce( 'lucd_get_projects' ),
            'getProjectNonce'  => wp_create_nonce( 'lucd_get_project' ),
            'getTicketsNonce'  => wp_create_nonce( 'lucd_get_tickets' ),
            'getTicketNonce'   => wp_create_nonce( 'lucd_get_ticket' ),
            'clients'          => $clients,
            'i18n'             => array(
                'selectClient'       => __( 'Please select an existing client.', 'level-up-client-dashboard' ),
                'confirmDeleteClient' => __( 'Are you sure you want to permanently delete this client?', 'level-up-client-dashboard' ),
                'confirmDeleteProject' => __( 'Are you sure you want to permanently delete this project?', 'level-up-client-dashboard' ),
                'confirmDeleteTicket'  => __( 'Are you sure you want to permanently delete this ticket?', 'level-up-client-dashboard' ),
            ),
        ) );
    }

    /**
     * Render a management page with tabs.
     *
     * @param string $title Page title.
     * @param string $slug  Page slug.
     * @param array  $tabs  Tab definitions.
     */
    public static function render_management_page( $title, $slug, $tabs ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'level-up-client-dashboard' ) );
        }

        $current_tab = isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ? $_GET['tab'] : array_key_first( $tabs );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html( $title ) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $tab => $data ) {
            $class = $tab === $current_tab ? ' nav-tab-active' : '';
            echo '<a class="nav-tab' . esc_attr( $class ) . '" href="' . esc_url( admin_url( 'admin.php?page=' . $slug . '&tab=' . $tab ) ) . '">' . esc_html( $data['label'] ) . '</a>';
        }
        echo '</h2>';

        if ( is_callable( $tabs[ $current_tab ]['callback'] ) ) {
            call_user_func( $tabs[ $current_tab ]['callback'] );
        }

        echo '</div>';
    }

    /**
     * Placeholder tab content.
     */
    public static function render_placeholder_tab() {
        echo '<p>' . esc_html__( 'Functionality coming soon.', 'level-up-client-dashboard' ) . '</p>';
    }
}
