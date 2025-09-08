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
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_lucd_add_client', array( __CLASS__, 'handle_add_client' ) );
        add_action( 'wp_ajax_lucd_get_client', array( __CLASS__, 'handle_get_client' ) );
        add_action( 'wp_ajax_lucd_update_client', array( __CLASS__, 'handle_update_client' ) );
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
     * Enqueue admin scripts and styles for plugin pages.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_' . self::$menu_slug !== $hook ) {
            return;
        }

        wp_register_style( 'lucd-admin', false );
        wp_enqueue_style( 'lucd-admin' );
        $css = '.lucd-field{display:inline-block;margin:0 20px 20px 0;vertical-align:top;}.lucd-field label{display:block;margin-bottom:4px;}.lucd-feedback{margin-top:20px;}.lucd-feedback .spinner{float:none;margin:0 5px 0 0;}.lucd-accordion-header{cursor:pointer;margin:0;padding:10px;background:#f0f0f1;border:1px solid #dcdcde;}.lucd-accordion-content{display:none;border:1px solid #dcdcde;border-top:none;padding:10px;}';
        wp_add_inline_style( 'lucd-admin', $css );

        wp_register_script( 'lucd-admin', false, array( 'jquery' ), false, true );
        wp_enqueue_script( 'lucd-admin' );
        wp_localize_script( 'lucd-admin', 'lucdAdmin', array(
            'getClientNonce' => wp_create_nonce( 'lucd_get_client' ),
        ) );
        $inline_js = <<<'JS'
jQuery(function($){
    $('#lucd-add-client-form').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        var $feedback = $('#lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        $.post(ajaxurl, data, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
            if(response.success){
                $form[0].reset();
            }
        });
    });

    $(document).on('click', '.lucd-accordion-header', function(){
        var $header = $(this);
        var $content = $header.next('.lucd-accordion-content');
        $content.toggle();
        if(!$content.data('loaded')){
            $content.html('<span class="spinner is-active"></span>');
            $.post(ajaxurl, {
                action: 'lucd_get_client',
                client_id: $header.data('client-id'),
                nonce: lucdAdmin.getClientNonce
            }, function(response){
                if(response.success){
                    $content.html(response.data).data('loaded', true);
                } else {
                    $content.html('<p>'+response.data+'</p>');
                }
            });
        }
    });

    $(document).on('submit', '.lucd-edit-client-form', function(e){
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        var $feedback = $form.next('.lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        $.post(ajaxurl, data, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
        });
    });
});
JS;
        wp_add_inline_script( 'lucd-admin', $inline_js );
    }

    /**
     * Get definitions for client fields.
     *
     * @return array
     */
    private static function get_client_fields() {
        return array(
            'first_name'       => array( 'label' => __( 'First Name', 'level-up-client-dashboard' ), 'type' => 'text', 'required' => true ),
            'last_name'        => array( 'label' => __( 'Last Name', 'level-up-client-dashboard' ), 'type' => 'text', 'required' => true ),
            'email'            => array( 'label' => __( 'Email', 'level-up-client-dashboard' ), 'type' => 'email', 'required' => true, 'sanitize' => 'sanitize_email' ),
            'mailing_address1' => array( 'label' => __( 'Mailing Address 1', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'mailing_address2' => array( 'label' => __( 'Mailing Address 2', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'mailing_city'     => array( 'label' => __( 'Mailing City', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'mailing_state'    => array( 'label' => __( 'Mailing State', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'mailing_postcode' => array( 'label' => __( 'Mailing Postcode', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'mailing_country'  => array( 'label' => __( 'Mailing Country', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_name'     => array( 'label' => __( 'Company Name', 'level-up-client-dashboard' ), 'type' => 'text', 'required' => true ),
            'company_website'  => array( 'label' => __( 'Company Website', 'level-up-client-dashboard' ), 'type' => 'url', 'sanitize' => 'esc_url_raw' ),
            'company_address1' => array( 'label' => __( 'Company Address 1', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_address2' => array( 'label' => __( 'Company Address 2', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_city'     => array( 'label' => __( 'Company City', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_state'    => array( 'label' => __( 'Company State', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_postcode' => array( 'label' => __( 'Company Postcode', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_country'  => array( 'label' => __( 'Company Country', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'social_facebook'  => array( 'label' => __( 'Facebook URL', 'level-up-client-dashboard' ), 'type' => 'url', 'sanitize' => 'esc_url_raw' ),
            'social_twitter'   => array( 'label' => __( 'Twitter URL', 'level-up-client-dashboard' ), 'type' => 'url', 'sanitize' => 'esc_url_raw' ),
            'social_instagram' => array( 'label' => __( 'Instagram URL', 'level-up-client-dashboard' ), 'type' => 'url', 'sanitize' => 'esc_url_raw' ),
            'social_linkedin'  => array( 'label' => __( 'LinkedIn URL', 'level-up-client-dashboard' ), 'type' => 'url', 'sanitize' => 'esc_url_raw' ),
            'social_yelp'      => array( 'label' => __( 'Yelp URL', 'level-up-client-dashboard' ), 'type' => 'url', 'sanitize' => 'esc_url_raw' ),
            'social_bbb'       => array( 'label' => __( 'BBB URL', 'level-up-client-dashboard' ), 'type' => 'url', 'sanitize' => 'esc_url_raw' ),
            'client_since'     => array( 'label' => __( 'Client Since', 'level-up-client-dashboard' ), 'type' => 'date', 'required' => true ),
        );
    }

    /**
     * Render client fields.
     *
     * @param array $client Client data.
     */
    private static function render_client_fields( $client = array() ) {
        foreach ( self::get_client_fields() as $name => $field ) {
            $value    = isset( $client[ $name ] ) ? $client[ $name ] : '';
            $required = ! empty( $field['required'] ) ? ' required' : '';
            printf(
                '<div class="lucd-field"><label for="%1$s">%2$s</label><input type="%3$s" id="%1$s" name="%1$s" value="%4$s"%5$s /></div>',
                esc_attr( $name ),
                esc_html( $field['label'] ),
                esc_attr( $field['type'] ),
                esc_attr( $value ),
                $required
            );
        }
    }

    /**
     * Handle AJAX request to add a new client.
     */
    public static function handle_add_client() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You are not allowed to perform this action.', 'level-up-client-dashboard' ) );
        }

        check_ajax_referer( 'lucd_add_client', 'lucd_nonce' );

        $fields        = self::get_client_fields();
        $client_data   = array();
        $client_format = array();

        foreach ( $fields as $field => $args ) {
            $raw = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
            if ( 'date' === $args['type'] && ! empty( $raw ) ) {
                $value = date( 'Y-m-d', strtotime( $raw ) );
            } elseif ( ! empty( $args['sanitize'] ) ) {
                $value = call_user_func( $args['sanitize'], $raw );
            } else {
                $value = sanitize_text_field( $raw );
            }
            $client_data[ $field ] = $value;
            $client_format[]       = '%s';
        }

        $email = $client_data['email'];
        if ( empty( $email ) ) {
            wp_send_json_error( __( 'Invalid email address.', 'level-up-client-dashboard' ) );
        }

        if ( email_exists( $email ) ) {
            wp_send_json_error( __( 'A user with that email already exists.', 'level-up-client-dashboard' ) );
        }

        $username = sanitize_user( current( explode( '@', $email ) ), true );
        if ( username_exists( $username ) ) {
            $username .= '_' . wp_generate_password( 4, false );
        }

        $password = wp_generate_password( 12, true );
        $user_id  = wp_insert_user(
            array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass'  => $password,
                'first_name' => $client_data['first_name'],
                'last_name'  => $client_data['last_name'],
                'role'       => 'subscriber',
            )
        );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( __( 'Failed to create user.', 'level-up-client-dashboard' ) );
        }

        $client_data['wp_user_id'] = $user_id;
        $client_format[]           = '%d';

        $table_name = self::get_clients_table_name();
        global $wpdb;
        $inserted = $wpdb->insert( $table_name, $client_data, $client_format );

        if ( false === $inserted ) {
            wp_delete_user( $user_id );
            $error_message = $wpdb->last_error ? $wpdb->last_error : __( 'unknown database error', 'level-up-client-dashboard' );
            wp_send_json_error( sprintf( __( 'Failed to insert client record: %s', 'level-up-client-dashboard' ), esc_html( $error_message ) ) );
        }

        wp_send_json_success( __( 'New Client Created Successfully!', 'level-up-client-dashboard' ) );
    }

    /**
     * Handle AJAX request to fetch a client's data.
     */
    public static function handle_get_client() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You are not allowed to perform this action.', 'level-up-client-dashboard' ) );
        }

        check_ajax_referer( 'lucd_get_client', 'nonce' );

        $client_id = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
        if ( ! $client_id ) {
            wp_send_json_error( __( 'Invalid client ID.', 'level-up-client-dashboard' ) );
        }

        global $wpdb;
        $table  = self::get_clients_table_name();
        $client = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE client_id = %d", $client_id ), ARRAY_A );

        if ( ! $client ) {
            wp_send_json_error( __( 'Client not found.', 'level-up-client-dashboard' ) );
        }

        ob_start();
        ?>
        <form class="lucd-edit-client-form">
            <?php self::render_client_fields( $client ); ?>
            <input type="hidden" name="action" value="lucd_update_client" />
            <input type="hidden" name="client_id" value="<?php echo esc_attr( $client_id ); ?>" />
            <?php wp_nonce_field( 'lucd_update_client', 'lucd_update_nonce' ); ?>
            <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Update Client', 'level-up-client-dashboard' ); ?></button></p>
        </form>
        <div class="lucd-feedback"><span class="spinner"></span><p></p></div>
        <?php
        $form = ob_get_clean();

        wp_send_json_success( $form );
    }

    /**
     * Handle AJAX request to update a client.
     */
    public static function handle_update_client() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You are not allowed to perform this action.', 'level-up-client-dashboard' ) );
        }

        check_ajax_referer( 'lucd_update_client', 'lucd_update_nonce' );

        $client_id = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
        if ( ! $client_id ) {
            wp_send_json_error( __( 'Invalid client ID.', 'level-up-client-dashboard' ) );
        }

        global $wpdb;
        $table  = self::get_clients_table_name();
        $client = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE client_id = %d", $client_id ) );
        if ( ! $client ) {
            wp_send_json_error( __( 'Client not found.', 'level-up-client-dashboard' ) );
        }

        $fields        = self::get_client_fields();
        $client_data   = array();
        $client_format = array();

        foreach ( $fields as $field => $args ) {
            $raw = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
            if ( 'date' === $args['type'] && ! empty( $raw ) ) {
                $value = date( 'Y-m-d', strtotime( $raw ) );
            } elseif ( ! empty( $args['sanitize'] ) ) {
                $value = call_user_func( $args['sanitize'], $raw );
            } else {
                $value = sanitize_text_field( $raw );
            }
            $client_data[ $field ] = $value;
            $client_format[]       = '%s';
        }

        $email = $client_data['email'];
        if ( empty( $email ) ) {
            wp_send_json_error( __( 'Invalid email address.', 'level-up-client-dashboard' ) );
        }

        $existing_user = get_user_by( 'email', $email );
        if ( $existing_user && intval( $existing_user->ID ) !== intval( $client->wp_user_id ) ) {
            wp_send_json_error( __( 'A user with that email already exists.', 'level-up-client-dashboard' ) );
        }

        $user_update = wp_update_user(
            array(
                'ID'         => $client->wp_user_id,
                'user_email' => $email,
                'first_name' => $client_data['first_name'],
                'last_name'  => $client_data['last_name'],
            )
        );

        if ( is_wp_error( $user_update ) ) {
            wp_send_json_error( __( 'Failed to update user.', 'level-up-client-dashboard' ) );
        }

        $updated = $wpdb->update( $table, $client_data, array( 'client_id' => $client_id ), $client_format, array( '%d' ) );

        if ( false === $updated ) {
            wp_send_json_error( __( 'Failed to update client record.', 'level-up-client-dashboard' ) );
        }

        wp_send_json_success( __( 'Client updated successfully.', 'level-up-client-dashboard' ) );
    }

    /**
     * Render the Client Management admin page with tabs.
     */
    public static function render_client_management_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'level-up-client-dashboard' ) );
        }

        $tabs = array(
            'add-client'  => __( 'Add a New Client', 'level-up-client-dashboard' ),
            'edit-client' => __( 'Edit Existing Clients', 'level-up-client-dashboard' ),
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
            case 'edit-client':
                self::render_edit_clients_tab();
                break;
            case 'add-client':
            default:
                self::render_add_client_tab();
                break;
        }

        echo '</div>';
    }

    /**
     * Render the Add a New Client form.
     */
    private static function render_add_client_tab() {
        ?>
        <form id="lucd-add-client-form">
            <?php self::render_client_fields(); ?>
            <input type="hidden" name="action" value="lucd_add_client" />
            <?php wp_nonce_field( 'lucd_add_client', 'lucd_nonce' ); ?>
            <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Add Client', 'level-up-client-dashboard' ); ?></button></p>
        </form>
        <div id="lucd-feedback" class="lucd-feedback"><span class="spinner"></span><p></p></div>
        <?php
    }

    /**
     * Render the Edit Existing Clients tab.
     */
    private static function render_edit_clients_tab() {
        global $wpdb;
        $table   = self::get_clients_table_name();
        $clients = $wpdb->get_results( "SELECT client_id, first_name, last_name FROM $table ORDER BY last_name ASC" );

        if ( empty( $clients ) ) {
            echo '<p>' . esc_html__( 'No clients found.', 'level-up-client-dashboard' ) . '</p>';
            return;
        }

        echo '<div id="lucd-edit-clients">';
        foreach ( $clients as $client ) {
            $name = trim( $client->first_name . ' ' . $client->last_name );
            echo '<div class="lucd-accordion">';
            echo '<h3 class="lucd-accordion-header" data-client-id="' . esc_attr( $client->client_id ) . '">' . esc_html( $name ) . '</h3>';
            echo '<div class="lucd-accordion-content"></div>';
            echo '</div>';
        }
        echo '</div>';
    }
}

register_activation_hook( __FILE__, array( 'Level_Up_Client_Dashboard', 'activate' ) );
Level_Up_Client_Dashboard::init();

