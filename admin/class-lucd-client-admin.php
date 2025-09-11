<?php
/**
 * Client management admin functionality.
 *
 * @package Level_Up_Client_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LUC_Client_Admin {
    const MENU_SLUG = 'lucd-client-management';

    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'wp_ajax_lucd_add_client', array( __CLASS__, 'handle_add_client' ) );
        add_action( 'wp_ajax_lucd_get_client', array( __CLASS__, 'handle_get_client' ) );
        add_action( 'wp_ajax_lucd_update_client', array( __CLASS__, 'handle_update_client' ) );
        add_action( 'wp_ajax_lucd_archive_client', array( __CLASS__, 'handle_archive_client' ) );
        add_action( 'wp_ajax_lucd_delete_client', array( __CLASS__, 'handle_delete_client' ) );
    }

    /**
     * Register the Client Management admin menu.
     */
    public static function register_menu() {
        add_menu_page(
            __( 'Client Management', 'level-up-client-dashboard' ),
            __( 'Client Management', 'level-up-client-dashboard' ),
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_page' ),
            'dashicons-groups',
            60.1
        );
    }

    /**
     * Render the Client Management admin page with tabs.
     */
    public static function render_page() {
        $tabs = array(
            'add-client'  => array(
                'label'    => __( 'Add a New Client', 'level-up-client-dashboard' ),
                'callback' => array( __CLASS__, 'render_add_client_tab' ),
            ),
            'edit-client' => array(
                'label'    => __( 'Edit Existing Clients', 'level-up-client-dashboard' ),
                'callback' => array( __CLASS__, 'render_edit_clients_tab' ),
            ),
        );

        Level_Up_Client_Dashboard_Admin::render_management_page( __( 'Client Management', 'level-up-client-dashboard' ), self::MENU_SLUG, $tabs );
    }


    /**
     * Render client fields.
     *
     * @param array $client Client data.
     */
    public static function render_client_fields( $client = array() ) {
        foreach ( LUC_D_Helpers::get_client_fields() as $field => $data ) {
            $value = isset( $client[ $field ] ) ? $client[ $field ] : '';

            if ( 'company_logo' === $field ) {
                $img_url = $value ? wp_get_attachment_image_url( (int) $value, 'full' ) : '';
                echo '<div class="lucd-field">';
                echo '<label for="company_logo">' . esc_html( $data['label'] ) . '</label>';
                echo '<input type="hidden" id="company_logo" name="company_logo" value="' . esc_attr( $value ) . '" />';
                echo '<button type="button" class="button lucd-upload-logo" data-target="company_logo">' . esc_html__( 'Select Logo', 'level-up-client-dashboard' ) . '</button>';
                $style = $img_url ? ' style="background-image:url(' . esc_url( $img_url ) . ');display:block;"' : ' style="display:none;"';
                echo '<div class="lucd-logo-preview" id="company_logo_preview"' . $style . '></div>';
                echo '</div>';
                continue;
            }

            if ( 'select' === $data['type'] ) {
                $states = $data['options'];
                echo '<div class="lucd-field">';
                echo '<label for="' . esc_attr( $field ) . '">' . esc_html( $data['label'] ) . '</label>';
                echo '<select id="' . esc_attr( $field ) . '" name="' . esc_attr( $field ) . '">';
                echo '<option value="" disabled' . selected( '', $value, false ) . '>' . esc_html__( 'Choose a State...', 'level-up-client-dashboard' ) . '</option>';
                foreach ( $states as $abbr => $name ) {
                    echo '<option value="' . esc_attr( $abbr ) . '"' . selected( $value, $abbr, false ) . '>' . esc_html( $name ) . '</option>';
                }
                echo '</select>';
                echo '</div>';
                continue;
            }

            $extra_attr = '';
            if ( in_array( $field, array( 'mailing_postcode', 'company_postcode' ), true ) ) {
                $extra_attr = ' pattern="\\d{5}(?:-\\d{4})?" maxlength="10"';
            }

            echo '<div class="lucd-field">';
            echo '<label for="' . esc_attr( $field ) . '">' . esc_html( $data['label'] ) . '</label>';
            printf(
                '<input type="%1$s" id="%2$s" name="%2$s" value="%3$s"%4$s />',
                esc_attr( $data['type'] ),
                esc_attr( $field ),
                esc_attr( $value ),
                $extra_attr
            );
            echo '</div>';

            if ( 'email' === $field ) {
                echo '<div class="lucd-field lucd-password-field">';
                echo '<label for="password">' . esc_html__( 'Password', 'level-up-client-dashboard' ) . '</label>';
                echo '<div class="lucd-password-wrap">';
                echo '<input type="password" id="password" name="password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^\\w\\s]).{8,}" autocomplete="new-password" />';
                echo '<button type="button" class="lucd-toggle-password" data-target="password" aria-label="' . esc_attr__( 'Show password', 'level-up-client-dashboard' ) . '"><span class="dashicons dashicons-visibility"></span></button>';
                echo '</div>';
                echo '</div>';
            }
        }
    }

    /**
     * Validate password strength.
     *
     * @param string $password Password to validate.
     * @return bool
     */
    private static function is_strong_password( $password ) {
        if ( strlen( $password ) < 8 ) {
            return false;
        }
        if ( ! preg_match( '/[A-Z]/', $password ) ) {
            return false;
        }
        if ( ! preg_match( '/[a-z]/', $password ) ) {
            return false;
        }
        if ( ! preg_match( '/[0-9]/', $password ) ) {
            return false;
        }
        if ( ! preg_match( '/[^\\w\\s]/', $password ) ) {
            return false;
        }
        return true;
    }


    /**
     * Handle AJAX request to add a client.
     */
    public static function handle_add_client() {
        check_ajax_referer( 'lucd_add_client', 'lucd_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $fields  = LUC_D_Helpers::get_client_fields();
        $data    = array();
        $formats = array();
        foreach ( $fields as $field => $info ) {
            if ( 'company_logo' === $field ) {
                $data[ $field ] = isset( $_POST[ $field ] ) ? absint( wp_unslash( $_POST[ $field ] ) ) : 0;
                $formats[]      = '%d';
                continue;
            }

            $value       = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
            $data[ $field ] = $value;
            $formats[]      = '%s';

            if ( 'email' === $field ) {
                if ( empty( $value ) || ! is_email( $value ) ) {
                    wp_send_json_error( __( 'A valid email is required.', 'level-up-client-dashboard' ) );
                }
            }

            $url_fields = array( 'company_website', 'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin', 'social_yelp', 'social_bbb' );
            if ( in_array( $field, $url_fields, true ) && $value && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
                wp_send_json_error( sprintf( __( '%s must be a valid URL.', 'level-up-client-dashboard' ), $info['label'] ) );
            }

            if ( 'client_since' === $field && $value && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
                wp_send_json_error( __( 'Client Since must be a valid date (YYYY-MM-DD).', 'level-up-client-dashboard' ) );
            }

            if ( in_array( $field, array( 'mailing_postcode', 'company_postcode' ), true ) && $value && ! LUC_D_Helpers::is_valid_zip( $value ) ) {
                wp_send_json_error( sprintf( __( '%s must be a valid U.S. ZIP code.', 'level-up-client-dashboard' ), $info['label'] ) );
            }

            if ( in_array( $field, array( 'mailing_state', 'company_state' ), true ) && $value && ! array_key_exists( $value, LUC_D_Helpers::get_us_states() ) ) {
                wp_send_json_error( sprintf( __( '%s must be a valid U.S. state or territory.', 'level-up-client-dashboard' ), $info['label'] ) );
            }
        }

        foreach ( array( 'first_name', 'last_name' ) as $required ) {
            if ( empty( $data[ $required ] ) ) {
                wp_send_json_error( sprintf( __( '%s is required.', 'level-up-client-dashboard' ), $fields[ $required ]['label'] ) );
            }
        }

        $password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

        if ( ! self::is_strong_password( $password ) ) {
            wp_send_json_error( __( 'Password must be at least 8 characters and include upper and lower case letters, numbers, and special characters.', 'level-up-client-dashboard' ) );
        }

        if ( email_exists( $data['email'] ) ) {
            wp_send_json_error( __( 'Email already exists.', 'level-up-client-dashboard' ) );
        }

        $user_id = wp_insert_user(
            array(
                'user_login' => $data['email'],
                'user_email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'user_pass'  => $password,
                'role'       => 'subscriber',
            )
        );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( $user_id->get_error_message() );
        }

        $data['wp_user_id']  = $user_id;
        $data['client_since'] = $data['client_since'] ? $data['client_since'] : current_time( 'Y-m-d' );

        $table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );

        global $wpdb;
        $inserted = $wpdb->insert( $table, $data, $formats );

        if ( false === $inserted ) {
            $db_error   = $wpdb->last_error;
            $last_query = $wpdb->last_query;
            wp_delete_user( $user_id );
            $error_msg = $db_error ? $db_error : sprintf( __( 'Database insert failed. Query: %s', 'level-up-client-dashboard' ), $last_query );
            wp_send_json_error( $error_msg );
        }

        wp_send_json_success( __( 'Client added successfully.', 'level-up-client-dashboard' ) );
    }

    /**
     * Handle AJAX request to get a client.
     */
    public static function handle_get_client() {
        check_ajax_referer( 'lucd_get_client', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $client_id = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
        if ( ! $client_id ) {
            wp_send_json_error( __( 'Invalid client ID.', 'level-up-client-dashboard' ) );
        }

        $table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        global $wpdb;
        $client = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE client_id = %d", $client_id ), ARRAY_A );

        if ( ! $client ) {
            wp_send_json_error( __( 'Client not found.', 'level-up-client-dashboard' ) );
        }

        ob_start();
        echo '<form class="lucd-edit-client-form">';
        self::render_client_fields( $client );
        echo '<input type="hidden" name="action" value="lucd_update_client" />';
        echo '<input type="hidden" name="client_id" value="' . esc_attr( $client_id ) . '" />';
        wp_nonce_field( 'lucd_update_client', 'lucd_update_nonce' );
        wp_nonce_field( 'lucd_archive_client', 'lucd_archive_nonce' );
        wp_nonce_field( 'lucd_delete_client', 'lucd_delete_nonce' );
        echo '<p>';
        echo '<button type="submit" class="button button-primary">' . esc_html__( 'Update Client', 'level-up-client-dashboard' ) . '</button> ';
        echo '<button type="button" class="button lucd-archive-client">' . esc_html__( 'Archive Client', 'level-up-client-dashboard' ) . '</button> ';
        echo '<button type="button" class="button lucd-delete-client">' . esc_html__( 'Delete Client', 'level-up-client-dashboard' ) . '</button>';
        echo '</p>';
        echo '</form>';
        echo '<div class="lucd-feedback"><span class="spinner"></span><p></p></div>';
        wp_send_json_success( ob_get_clean() );
    }

    /**
     * Handle AJAX request to update a client.
     */
    public static function handle_update_client() {
        check_ajax_referer( 'lucd_update_client', 'lucd_update_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $client_id = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
        if ( ! $client_id ) {
            wp_send_json_error( __( 'Invalid client ID.', 'level-up-client-dashboard' ) );
        }

        $fields  = LUC_D_Helpers::get_client_fields();
        $data    = array();
        $formats = array();
        foreach ( $fields as $field => $info ) {
            if ( 'company_logo' === $field ) {
                $data[ $field ] = isset( $_POST[ $field ] ) ? absint( wp_unslash( $_POST[ $field ] ) ) : 0;
                $formats[]      = '%d';
                continue;
            }

            $value       = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
            $data[ $field ] = $value;
            $formats[]      = '%s';

            if ( 'email' === $field ) {
                if ( empty( $value ) || ! is_email( $value ) ) {
                    wp_send_json_error( __( 'A valid email is required.', 'level-up-client-dashboard' ) );
                }
            }

            $url_fields = array( 'company_website', 'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin', 'social_yelp', 'social_bbb' );
            if ( in_array( $field, $url_fields, true ) && $value && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
                wp_send_json_error( sprintf( __( '%s must be a valid URL.', 'level-up-client-dashboard' ), $info['label'] ) );
            }

            if ( 'client_since' === $field && $value && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
                wp_send_json_error( __( 'Client Since must be a valid date (YYYY-MM-DD).', 'level-up-client-dashboard' ) );
            }

            if ( in_array( $field, array( 'mailing_postcode', 'company_postcode' ), true ) && $value && ! LUC_D_Helpers::is_valid_zip( $value ) ) {
                wp_send_json_error( sprintf( __( '%s must be a valid U.S. ZIP code.', 'level-up-client-dashboard' ), $info['label'] ) );
            }

            if ( in_array( $field, array( 'mailing_state', 'company_state' ), true ) && $value && ! array_key_exists( $value, LUC_D_Helpers::get_us_states() ) ) {
                wp_send_json_error( sprintf( __( '%s must be a valid U.S. state or territory.', 'level-up-client-dashboard' ), $info['label'] ) );
            }
        }

        foreach ( array( 'first_name', 'last_name' ) as $required ) {
            if ( empty( $data[ $required ] ) ) {
                wp_send_json_error( sprintf( __( '%s is required.', 'level-up-client-dashboard' ), $fields[ $required ]['label'] ) );
            }
        }

        $password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

        $table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        global $wpdb;
        $user_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT wp_user_id FROM $table WHERE client_id = %d", $client_id ) );
        if ( ! $user_id ) {
            wp_send_json_error( __( 'Associated user not found.', 'level-up-client-dashboard' ) );
        }

        $userdata = array(
            'ID'         => $user_id,
            'user_email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
        );

        if ( $password ) {
            if ( ! self::is_strong_password( $password ) ) {
                wp_send_json_error( __( 'Password must be at least 8 characters and include upper and lower case letters, numbers, and special characters.', 'level-up-client-dashboard' ) );
            }
            $userdata['user_pass'] = $password;
        }

        $result = wp_update_user( $userdata );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        $updated = $wpdb->update( $table, $data, array( 'client_id' => $client_id ), $formats, array( '%d' ) );

        if ( false === $updated ) {
            $db_error   = $wpdb->last_error;
            $last_query = $wpdb->last_query;
            $error_msg  = $db_error ? $db_error : sprintf( __( 'Database update failed. Query: %s', 'level-up-client-dashboard' ), $last_query );
            wp_send_json_error( $error_msg );
        }

        wp_send_json_success( __( 'Client updated successfully.', 'level-up-client-dashboard' ) );
    }

    /**
     * Handle AJAX request to archive a client.
     */
    public static function handle_archive_client() {
        check_ajax_referer( 'lucd_archive_client', 'lucd_archive_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $client_id = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
        if ( ! $client_id ) {
            wp_send_json_error( __( 'Invalid client ID.', 'level-up-client-dashboard' ) );
        }

        global $wpdb;

        $tables = array(
            Level_Up_Client_Dashboard::clients_table()  => Level_Up_Client_Dashboard::clients_archive_table(),
            Level_Up_Client_Dashboard::projects_table() => Level_Up_Client_Dashboard::projects_archive_table(),
            Level_Up_Client_Dashboard::tickets_table()  => Level_Up_Client_Dashboard::tickets_archive_table(),
            Level_Up_Client_Dashboard::billing_table()  => Level_Up_Client_Dashboard::billing_archive_table(),
            Level_Up_Client_Dashboard::plugins_table()  => Level_Up_Client_Dashboard::plugins_archive_table(),
        );

        foreach ( $tables as $active_base => $archive_base ) {
            $active  = Level_Up_Client_Dashboard::get_table_name( $active_base );
            $archive = Level_Up_Client_Dashboard::get_table_name( $archive_base );
            $wpdb->query( $wpdb->prepare( "INSERT INTO $archive SELECT * FROM $active WHERE client_id = %d", $client_id ) );
            $wpdb->delete( $active, array( 'client_id' => $client_id ), array( '%d' ) );
        }

        // TODO: Update to handle additional custom tables that reference client_id.
        wp_send_json_success( __( 'Client archived successfully.', 'level-up-client-dashboard' ) );
    }

    /**
     * Handle AJAX request to delete a client.
     */
    public static function handle_delete_client() {
        check_ajax_referer( 'lucd_delete_client', 'lucd_delete_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $client_id = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
        if ( ! $client_id ) {
            wp_send_json_error( __( 'Invalid client ID.', 'level-up-client-dashboard' ) );
        }

        global $wpdb;

        $clients_table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        $user_id       = (int) $wpdb->get_var( $wpdb->prepare( "SELECT wp_user_id FROM $clients_table WHERE client_id = %d", $client_id ) );

        $tables = array(
            Level_Up_Client_Dashboard::clients_table()  => Level_Up_Client_Dashboard::clients_archive_table(),
            Level_Up_Client_Dashboard::projects_table() => Level_Up_Client_Dashboard::projects_archive_table(),
            Level_Up_Client_Dashboard::tickets_table()  => Level_Up_Client_Dashboard::tickets_archive_table(),
            Level_Up_Client_Dashboard::billing_table()  => Level_Up_Client_Dashboard::billing_archive_table(),
            Level_Up_Client_Dashboard::plugins_table()  => Level_Up_Client_Dashboard::plugins_archive_table(),
        );

        foreach ( $tables as $active_base => $archive_base ) {
            $active  = Level_Up_Client_Dashboard::get_table_name( $active_base );
            $archive = Level_Up_Client_Dashboard::get_table_name( $archive_base );
            $wpdb->delete( $active, array( 'client_id' => $client_id ), array( '%d' ) );
            $wpdb->delete( $archive, array( 'client_id' => $client_id ), array( '%d' ) );
        }

        if ( $user_id ) {
            wp_delete_user( $user_id );
        }

        // TODO: Update to handle additional custom tables that reference client_id.
        wp_send_json_success( __( 'Client deleted successfully.', 'level-up-client-dashboard' ) );
    }

    /**
     * Render the Add a New Client form.
     */
    public static function render_add_client_tab() {
        include LUCD_PLUGIN_DIR . 'admin/views/client-add.php';
    }

    /**
     * Render the Edit Existing Clients tab.
     */
    public static function render_edit_clients_tab() {
        include LUCD_PLUGIN_DIR . 'admin/views/client-edit.php';
    }

    /**
     * Get a formatted client label.
     *
     * @param int $client_id Client ID.
     * @return string
     */
    public static function get_client_label( $client_id ) {
        global $wpdb;
        $table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        $client = $wpdb->get_row( $wpdb->prepare( "SELECT company_name, first_name, last_name FROM $table WHERE client_id = %d", $client_id ) );
        if ( ! $client ) {
            return '';
        }
        $name    = trim( $client->first_name . ' ' . $client->last_name );
        $company = trim( $client->company_name );
        return $company ? $company . ' - ' . $name : $name;
    }
}
