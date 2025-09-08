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
        $css = '#lucd-add-client-form .lucd-field{display:inline-block;margin:0 20px 20px 0;}#lucd-feedback{margin-top:20px;}#lucd-feedback .spinner{float:none;margin:0 5px 0 0;}';
        wp_add_inline_style( 'lucd-admin', $css );

        wp_register_script( 'lucd-admin', false, array( 'jquery' ), false, true );
        wp_enqueue_script( 'lucd-admin' );
        $inline_js = <<<'JS'
jQuery(function($){
    $('#lucd-add-client-form').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        $('#lucd-feedback p').text('');
        $('#lucd-feedback .spinner').addClass('is-active');
        $.post(ajaxurl, data, function(response){
            $('#lucd-feedback .spinner').removeClass('is-active');
            $('#lucd-feedback p').text(response.data);
            if(response.success){
                $form[0].reset();
            }
        });
    });
});
JS;
        wp_add_inline_script( 'lucd-admin', $inline_js );
    }

    /**
     * Handle AJAX request to add a new client.
     */
    public static function handle_add_client() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You are not allowed to perform this action.', 'level-up-client-dashboard' ) );
        }

        check_ajax_referer( 'lucd_add_client', 'lucd_nonce' );

        $fields = array(
            'first_name',
            'last_name',
            'email',
            'mailing_address1',
            'mailing_address2',
            'mailing_city',
            'mailing_state',
            'mailing_postcode',
            'mailing_country',
            'company_name',
            'company_website',
            'company_address1',
            'company_address2',
            'company_city',
            'company_state',
            'company_postcode',
            'company_country',
            'social_facebook',
            'social_twitter',
            'social_instagram',
            'social_linkedin',
            'social_yelp',
            'social_bbb',
            'client_since',
        );

        $sanitizers = array(
            'email'            => 'sanitize_email',
            'company_website'  => 'esc_url_raw',
            'social_facebook'  => 'esc_url_raw',
            'social_twitter'   => 'esc_url_raw',
            'social_instagram' => 'esc_url_raw',
            'social_linkedin'  => 'esc_url_raw',
            'social_yelp'      => 'esc_url_raw',
            'social_bbb'       => 'esc_url_raw',
        );

        $client_data   = array();
        $client_format = array();

        foreach ( $fields as $field ) {
            $raw = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
            if ( 'client_since' === $field && ! empty( $raw ) ) {
                $value = date( 'Y-m-d', strtotime( $raw ) );
            } elseif ( isset( $sanitizers[ $field ] ) ) {
                $value = call_user_func( $sanitizers[ $field ], $raw );
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
            wp_send_json_error( __( 'Failed to insert client record.', 'level-up-client-dashboard' ) );
        }

        wp_send_json_success( __( 'New Client Created Successfully!', 'level-up-client-dashboard' ) );
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
     * Render the Add a New Client form.
     */
    private static function render_add_client_tab() {
        ?>
        <form id="lucd-add-client-form">
            <div class="lucd-field">
                <label for="first_name"><?php esc_html_e( 'First Name', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="first_name" name="first_name" required />
            </div>
            <div class="lucd-field">
                <label for="last_name"><?php esc_html_e( 'Last Name', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="last_name" name="last_name" required />
            </div>
            <div class="lucd-field">
                <label for="email"><?php esc_html_e( 'Email', 'level-up-client-dashboard' ); ?></label>
                <input type="email" id="email" name="email" required />
            </div>
            <div class="lucd-field">
                <label for="mailing_address1"><?php esc_html_e( 'Mailing Address 1', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="mailing_address1" name="mailing_address1" />
            </div>
            <div class="lucd-field">
                <label for="mailing_address2"><?php esc_html_e( 'Mailing Address 2', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="mailing_address2" name="mailing_address2" />
            </div>
            <div class="lucd-field">
                <label for="mailing_city"><?php esc_html_e( 'Mailing City', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="mailing_city" name="mailing_city" />
            </div>
            <div class="lucd-field">
                <label for="mailing_state"><?php esc_html_e( 'Mailing State', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="mailing_state" name="mailing_state" />
            </div>
            <div class="lucd-field">
                <label for="mailing_postcode"><?php esc_html_e( 'Mailing Postcode', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="mailing_postcode" name="mailing_postcode" />
            </div>
            <div class="lucd-field">
                <label for="mailing_country"><?php esc_html_e( 'Mailing Country', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="mailing_country" name="mailing_country" />
            </div>
            <div class="lucd-field">
                <label for="company_name"><?php esc_html_e( 'Company Name', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="company_name" name="company_name" required />
            </div>
            <div class="lucd-field">
                <label for="company_website"><?php esc_html_e( 'Company Website', 'level-up-client-dashboard' ); ?></label>
                <input type="url" id="company_website" name="company_website" />
            </div>
            <div class="lucd-field">
                <label for="company_address1"><?php esc_html_e( 'Company Address 1', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="company_address1" name="company_address1" />
            </div>
            <div class="lucd-field">
                <label for="company_address2"><?php esc_html_e( 'Company Address 2', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="company_address2" name="company_address2" />
            </div>
            <div class="lucd-field">
                <label for="company_city"><?php esc_html_e( 'Company City', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="company_city" name="company_city" />
            </div>
            <div class="lucd-field">
                <label for="company_state"><?php esc_html_e( 'Company State', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="company_state" name="company_state" />
            </div>
            <div class="lucd-field">
                <label for="company_postcode"><?php esc_html_e( 'Company Postcode', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="company_postcode" name="company_postcode" />
            </div>
            <div class="lucd-field">
                <label for="company_country"><?php esc_html_e( 'Company Country', 'level-up-client-dashboard' ); ?></label>
                <input type="text" id="company_country" name="company_country" />
            </div>
            <div class="lucd-field">
                <label for="social_facebook"><?php esc_html_e( 'Facebook URL', 'level-up-client-dashboard' ); ?></label>
                <input type="url" id="social_facebook" name="social_facebook" />
            </div>
            <div class="lucd-field">
                <label for="social_twitter"><?php esc_html_e( 'Twitter URL', 'level-up-client-dashboard' ); ?></label>
                <input type="url" id="social_twitter" name="social_twitter" />
            </div>
            <div class="lucd-field">
                <label for="social_instagram"><?php esc_html_e( 'Instagram URL', 'level-up-client-dashboard' ); ?></label>
                <input type="url" id="social_instagram" name="social_instagram" />
            </div>
            <div class="lucd-field">
                <label for="social_linkedin"><?php esc_html_e( 'LinkedIn URL', 'level-up-client-dashboard' ); ?></label>
                <input type="url" id="social_linkedin" name="social_linkedin" />
            </div>
            <div class="lucd-field">
                <label for="social_yelp"><?php esc_html_e( 'Yelp URL', 'level-up-client-dashboard' ); ?></label>
                <input type="url" id="social_yelp" name="social_yelp" />
            </div>
            <div class="lucd-field">
                <label for="social_bbb"><?php esc_html_e( 'BBB URL', 'level-up-client-dashboard' ); ?></label>
                <input type="url" id="social_bbb" name="social_bbb" />
            </div>
            <div class="lucd-field">
                <label for="client_since"><?php esc_html_e( 'Client Since', 'level-up-client-dashboard' ); ?></label>
                <input type="date" id="client_since" name="client_since" required />
            </div>
            <input type="hidden" name="action" value="lucd_add_client" />
            <?php wp_nonce_field( 'lucd_add_client', 'lucd_nonce' ); ?>
            <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Add Client', 'level-up-client-dashboard' ); ?></button></p>
        </form>
        <div id="lucd-feedback"><span class="spinner"></span><p></p></div>
        <?php
    }
}

register_activation_hook( __FILE__, array( 'Level_Up_Client_Dashboard', 'activate' ) );
Level_Up_Client_Dashboard::init();

