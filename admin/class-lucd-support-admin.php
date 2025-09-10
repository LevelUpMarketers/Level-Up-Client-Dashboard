<?php
/**
 * Support management admin functionality.
 *
 * @package Level_Up_Client_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LUC_Support_Admin {
    const MENU_SLUG = 'lucd-support-management';

    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'wp_ajax_lucd_add_ticket', array( __CLASS__, 'handle_add_ticket' ) );
        add_action( 'wp_ajax_lucd_get_tickets', array( __CLASS__, 'handle_get_tickets' ) );
        add_action( 'wp_ajax_lucd_get_ticket', array( __CLASS__, 'handle_get_ticket' ) );
        add_action( 'wp_ajax_lucd_update_ticket', array( __CLASS__, 'handle_update_ticket' ) );
    }

    /**
     * Register the Support Ticket Management admin menu.
     */
    public static function register_menu() {
        add_menu_page(
            __( 'Support Ticket Management', 'level-up-client-dashboard' ),
            __( 'Support Ticket Management', 'level-up-client-dashboard' ),
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_page' ),
            'dashicons-sos',
            60.3
        );
    }

    /**
     * Render the Support Ticket Management admin page.
     */
    public static function render_page() {
        $tabs = array(
            'add-ticket'  => array(
                'label'    => __( 'Add a New Support Ticket', 'level-up-client-dashboard' ),
                'callback' => array( __CLASS__, 'render_add_ticket_tab' ),
            ),
            'edit-ticket' => array(
                'label'    => __( 'Edit Tickets', 'level-up-client-dashboard' ),
                'callback' => array( __CLASS__, 'render_edit_tickets_tab' ),
            ),
        );

        Level_Up_Client_Dashboard_Admin::render_management_page( __( 'Support Ticket Management', 'level-up-client-dashboard' ), self::MENU_SLUG, $tabs );
    }

    /**
     * Definitions for ticket fields.
     *
     * @return array
     */
    private static function get_ticket_fields() {
        return array(
            'ticket_client'      => array( 'label' => __( 'Client', 'level-up-client-dashboard' ), 'type' => 'text', 'class' => 'lucd-ticket-client' ),
            'client_id'          => array( 'type' => 'hidden', 'class' => 'lucd-ticket-client-id' ),
            'creation_datetime'  => array( 'label' => __( 'Creation Date & Time', 'level-up-client-dashboard' ), 'type' => 'datetime-local' ),
            'start_time'         => array( 'label' => __( 'Start Time', 'level-up-client-dashboard' ), 'type' => 'datetime-local', 'class' => 'lucd-ticket-start' ),
            'end_time'           => array( 'label' => __( 'End Time', 'level-up-client-dashboard' ), 'type' => 'datetime-local', 'class' => 'lucd-ticket-end' ),
            'duration_minutes'   => array( 'label' => __( 'Total Ticket Duration (Minutes)', 'level-up-client-dashboard' ), 'type' => 'number', 'class' => 'lucd-ticket-duration', 'step' => '1' ),
            'status'             => array( 'label' => __( 'Ticket Status', 'level-up-client-dashboard' ), 'type' => 'select', 'options' => array(
                'Not Started'             => __( 'Not Started', 'level-up-client-dashboard' ),
                'In Progress'             => __( 'In Progress', 'level-up-client-dashboard' ),
                'Client Feedback Required' => __( 'Client Feedback Required', 'level-up-client-dashboard' ),
                'Client Assets Required'  => __( 'Client Assets Required', 'level-up-client-dashboard' ),
                'Completed'               => __( 'Completed', 'level-up-client-dashboard' ),
                'No Longer Applicable'    => __( 'No Longer Applicable', 'level-up-client-dashboard' ),
            ) ),
            'initial_description' => array( 'label' => __( 'Initial Description', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
            'ticket_updates'      => array( 'label' => __( 'Ticket Updates', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
        );
    }

    /**
     * Render ticket fields.
     *
     * @param array $ticket Ticket data.
     */
    public static function render_ticket_fields( $ticket = array() ) {
        foreach ( self::get_ticket_fields() as $field => $data ) {
            $value = isset( $ticket[ $field ] ) ? $ticket[ $field ] : '';
            if ( 'creation_datetime' === $field && empty( $value ) ) {
                $value = current_time( 'mysql' );
            }
            if ( 'status' === $field && empty( $value ) ) {
                $value = 'Not Started';
            }
            if ( 'datetime-local' === $data['type'] && ! empty( $value ) ) {
                $value = str_replace( ' ', 'T', substr( $value, 0, 16 ) );
            }
            $class = isset( $data['class'] ) ? ' ' . $data['class'] : '';

            if ( 'hidden' === $data['type'] ) {
                printf(
                    '<input type="hidden" id="%1$s" name="%1$s" value="%2$s"%3$s />',
                    esc_attr( $field ),
                    esc_attr( $value ),
                    $class ? ' class="' . esc_attr( trim( $class ) ) . '"' : ''
                );
                continue;
            }

            echo '<div class="lucd-field">';
            if ( isset( $data['label'] ) ) {
                echo '<label for="' . esc_attr( $field ) . '">' . esc_html( $data['label'] ) . '</label>';
            }
            if ( 'textarea' === $data['type'] ) {
                printf( '<textarea id="%1$s" name="%1$s"%3$s>%2$s</textarea>', esc_attr( $field ), esc_textarea( $value ), $class ? ' class="' . esc_attr( trim( $class ) ) . '"' : '' );
            } elseif ( 'select' === $data['type'] ) {
                printf( '<select id="%1$s" name="%1$s"%2$s>', esc_attr( $field ), $class ? ' class="' . esc_attr( trim( $class ) ) . '"' : '' );
                foreach ( $data['options'] as $opt_value => $label ) {
                    printf( '<option value="%1$s"%3$s>%2$s</option>', esc_attr( $opt_value ), esc_html( $label ), selected( $value, $opt_value, false ) );
                }
                echo '</select>';
            } else {
                $extra = '';
                if ( isset( $data['step'] ) ) {
                    $extra .= ' step="' . esc_attr( $data['step'] ) . '"';
                }
                printf( '<input type="%1$s" id="%2$s" name="%2$s" value="%3$s"%4$s%5$s />', esc_attr( $data['type'] ), esc_attr( $field ), esc_attr( $value ), $class ? ' class="' . esc_attr( trim( $class ) ) . '"' : '', $extra );
            }
            echo '</div>';
        }
    }

    /**
     * Render the Add Ticket tab.
     */
    public static function render_add_ticket_tab() {
        require LUCD_PLUGIN_DIR . 'admin/views/ticket-add.php';
    }

    /**
     * Render the Edit Tickets tab.
     */
    public static function render_edit_tickets_tab() {
        require LUCD_PLUGIN_DIR . 'admin/views/ticket-manage.php';
    }

    /**
     * Handle AJAX request to add a ticket.
     */
    public static function handle_add_ticket() {
        check_ajax_referer( 'lucd_add_ticket', 'lucd_ticket_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $fields = self::get_ticket_fields();
        $data   = array();
        foreach ( $fields as $field => $info ) {
            if ( 'ticket_client' === $field ) {
                continue;
            }
            if ( in_array( $field, array( 'initial_description', 'ticket_updates' ), true ) ) {
                $value = isset( $_POST[ $field ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) : '';
            } elseif ( in_array( $field, array( 'client_id', 'duration_minutes' ), true ) ) {
                $value = isset( $_POST[ $field ] ) ? absint( $_POST[ $field ] ) : 0;
            } else {
                $value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
            }
            if ( in_array( $field, array( 'creation_datetime', 'start_time', 'end_time' ), true ) ) {
                $value = str_replace( 'T', ' ', $value );
            }
            $data[ $field ] = $value;
        }

        if ( empty( $data['client_id'] ) ) {
            wp_send_json_error( __( 'Please select an existing client.', 'level-up-client-dashboard' ) );
        }

        $table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::tickets_table() );
        $format = array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' );
        global $wpdb;
        $inserted = $wpdb->insert( $table, $data, $format );

        if ( ! $inserted ) {
            wp_send_json_error( __( 'Failed to add ticket.', 'level-up-client-dashboard' ) );
        }

        wp_send_json_success( __( 'Ticket added successfully.', 'level-up-client-dashboard' ) );
    }

    /**
     * Handle AJAX request to get tickets for a client.
     */
    public static function handle_get_tickets() {
        check_ajax_referer( 'lucd_get_tickets', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $client_id = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
        if ( ! $client_id ) {
            wp_send_json_error( __( 'Invalid client ID.', 'level-up-client-dashboard' ) );
        }

        $table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::tickets_table() );
        global $wpdb;
        $tickets = $wpdb->get_results( $wpdb->prepare( "SELECT ticket_id, creation_datetime FROM $table WHERE client_id = %d ORDER BY creation_datetime DESC", $client_id ) );

        if ( empty( $tickets ) ) {
            wp_send_json_error( __( 'No tickets found.', 'level-up-client-dashboard' ) );
        }

        ob_start();
        foreach ( $tickets as $ticket ) {
            $label = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ticket->creation_datetime );
            echo '<div class="lucd-accordion">';
            echo '<h3 class="lucd-accordion-header" data-action="lucd_get_ticket" data-nonce="getTicketNonce" data-ticket-id="' . esc_attr( $ticket->ticket_id ) . '">' . esc_html( $label ) . '</h3>';
            echo '<div class="lucd-accordion-content"></div>';
            echo '</div>';
        }
        wp_send_json_success( ob_get_clean() );
    }

    /**
     * Handle AJAX request to get a ticket.
     */
    public static function handle_get_ticket() {
        check_ajax_referer( 'lucd_get_ticket', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
        if ( ! $ticket_id ) {
            wp_send_json_error( __( 'Invalid ticket ID.', 'level-up-client-dashboard' ) );
        }

        $table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::tickets_table() );
        global $wpdb;
        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE ticket_id = %d", $ticket_id ), ARRAY_A );

        if ( ! $ticket ) {
            wp_send_json_error( __( 'Ticket not found.', 'level-up-client-dashboard' ) );
        }

        $ticket['ticket_client'] = LUC_Client_Admin::get_client_label( $ticket['client_id'] );

        ob_start();
        echo '<form class="lucd-edit-ticket-form">';
        self::render_ticket_fields( $ticket );
        echo '<input type="hidden" name="action" value="lucd_update_ticket" />';
        echo '<input type="hidden" name="ticket_id" value="' . esc_attr( $ticket_id ) . '" />';
        wp_nonce_field( 'lucd_update_ticket', 'lucd_update_ticket_nonce' );
        echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Update Ticket', 'level-up-client-dashboard' ) . '</button></p>';
        echo '</form>';
        echo '<div class="lucd-feedback"><span class="spinner"></span><p></p></div>';
        wp_send_json_success( ob_get_clean() );
    }

    /**
     * Handle AJAX request to update a ticket.
     */
    public static function handle_update_ticket() {
        check_ajax_referer( 'lucd_update_ticket', 'lucd_update_ticket_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
        if ( ! $ticket_id ) {
            wp_send_json_error( __( 'Invalid ticket ID.', 'level-up-client-dashboard' ) );
        }

        $fields = self::get_ticket_fields();
        $data   = array();
        foreach ( $fields as $field => $info ) {
            if ( 'ticket_client' === $field ) {
                continue;
            }
            if ( in_array( $field, array( 'initial_description', 'ticket_updates' ), true ) ) {
                $value = isset( $_POST[ $field ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) : '';
            } elseif ( in_array( $field, array( 'client_id', 'duration_minutes' ), true ) ) {
                $value = isset( $_POST[ $field ] ) ? absint( $_POST[ $field ] ) : 0;
            } else {
                $value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
            }
            if ( in_array( $field, array( 'creation_datetime', 'start_time', 'end_time' ), true ) ) {
                $value = str_replace( 'T', ' ', $value );
            }
            $data[ $field ] = $value;
        }

        if ( empty( $data['client_id'] ) ) {
            wp_send_json_error( __( 'Please select an existing client.', 'level-up-client-dashboard' ) );
        }

        $table   = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::tickets_table() );
        $formats = array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' );
        global $wpdb;
        $updated = $wpdb->update( $table, $data, array( 'ticket_id' => $ticket_id ), $formats, array( '%d' ) );

        if ( false === $updated ) {
            wp_send_json_error( __( 'Failed to update ticket.', 'level-up-client-dashboard' ) );
        }

        wp_send_json_success( __( 'Ticket updated successfully.', 'level-up-client-dashboard' ) );
    }
}
