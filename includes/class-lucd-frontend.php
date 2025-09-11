<?php
/**
 * Front-end client dashboard shortcode and AJAX handlers.
 *
 * @package Level_Up_Client_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles front-end dashboard rendering and interactions.
 */
class LUC_Dashboard_Frontend {
    /**
     * Initialize hooks.
     */
    public static function init() {
        add_shortcode( 'lucd_client_dashboard', array( __CLASS__, 'render_dashboard' ) );
        add_action( 'wp_ajax_lucd_load_section', array( __CLASS__, 'load_section' ) );
        add_action( 'wp_ajax_nopriv_lucd_load_section', array( __CLASS__, 'load_section' ) );
        add_action( 'wp_ajax_lucd_save_profile', array( __CLASS__, 'save_profile' ) );
        add_action( 'wp_ajax_nopriv_lucd_save_profile', array( __CLASS__, 'save_profile' ) );
    }

    /**
     * Render the dashboard markup.
     *
     * @return string
     */
    public static function render_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You must be logged in to view this page.', 'lucd' ) . '</p>';
        }

        self::enqueue_assets();

        $buttons = array(
            'overview' => __( 'Overview', 'lucd' ),
            'profile'  => __( 'Profile Info', 'lucd' ),
            'projects' => __( 'Projects & Services', 'lucd' ),
            'tickets'  => __( 'Support Tickets', 'lucd' ),
            'plugins'  => __( 'Your Plugins', 'lucd' ),
            'billing'  => __( 'Billing', 'lucd' ),
        );

        ob_start();
        ?>
        <div id="lucd-dashboard" class="lucd-dashboard">
            <div class="lucd-nav">
                <?php foreach ( $buttons as $key => $label ) : ?>
                    <div class="lucd-nav-item">
                        <button class="lucd-nav-button" data-section="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></button>
                        <div class="lucd-mobile-content"></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="lucd-content" class="lucd-content"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue scripts and styles for the dashboard.
     */
    private static function enqueue_assets() {
        wp_enqueue_style(
            'lucd-dashboard',
            plugins_url( 'assets/css/dashboard.css', LUCD_PLUGIN_FILE ),
            array(),
            '0.1.0'
        );

        wp_enqueue_script(
            'lucd-dashboard',
            plugins_url( 'assets/js/dashboard.js', LUCD_PLUGIN_FILE ),
            array( 'jquery' ),
            '0.1.0',
            true
        );

        wp_localize_script(
            'lucd-dashboard',
            'lucdDashboard',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'lucd_dashboard_nonce' ),
            )
        );
    }

    /**
     * AJAX handler to load dashboard sections.
     */
    public static function load_section() {
        check_ajax_referer( 'lucd_dashboard_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'You are not logged in.', 'lucd' ) );
        }

        $section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
        $user_id = get_current_user_id();

        switch ( $section ) {
            case 'profile':
                $content = self::get_profile_section( $user_id );
                break;
            case 'overview':
                $content = '<p>' . esc_html__( 'Welcome to your dashboard.', 'lucd' ) . '</p>';
                break;
            case 'projects':
                $content = '<p>' . esc_html__( 'Projects & Services coming soon.', 'lucd' ) . '</p>';
                break;
            case 'tickets':
                $content = '<p>' . esc_html__( 'Support Tickets coming soon.', 'lucd' ) . '</p>';
                break;
            case 'plugins':
                $content = '<p>' . esc_html__( 'Your Plugins coming soon.', 'lucd' ) . '</p>';
                break;
            case 'billing':
                $content = '<p>' . esc_html__( 'Billing information coming soon.', 'lucd' ) . '</p>';
                break;
            default:
                wp_send_json_error( __( 'Invalid section.', 'lucd' ) );
        }

        wp_send_json_success( $content );
    }

    /**
     * Get profile information markup for the current user.
     *
     * @param int $user_id WordPress user ID.
     * @return string
     */
    private static function get_profile_section( $user_id ) {
        global $wpdb;

        $table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        $client = $wpdb->get_row( $wpdb->prepare( "SELECT first_name, last_name, email FROM {$table} WHERE wp_user_id = %d", $user_id ), ARRAY_A );

        if ( ! $client ) {
            return '<p>' . esc_html__( 'Client record not found.', 'lucd' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="lucd-profile-view">
            <p><strong><?php esc_html_e( 'First Name:', 'lucd' ); ?></strong> <?php echo esc_html( $client['first_name'] ); ?></p>
            <p><strong><?php esc_html_e( 'Last Name:', 'lucd' ); ?></strong> <?php echo esc_html( $client['last_name'] ); ?></p>
            <p><strong><?php esc_html_e( 'Email:', 'lucd' ); ?></strong> <?php echo esc_html( $client['email'] ); ?></p>
            <button class="lucd-edit-profile"><?php esc_html_e( 'Edit Profile Info', 'lucd' ); ?></button>
        </div>
        <form class="lucd-profile-edit" style="display:none;">
            <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'lucd_save_profile' ) ); ?>" />
            <p>
                <label><?php esc_html_e( 'First Name', 'lucd' ); ?><br />
                    <input type="text" name="first_name" value="<?php echo esc_attr( $client['first_name'] ); ?>" />
                </label>
            </p>
            <p>
                <label><?php esc_html_e( 'Last Name', 'lucd' ); ?><br />
                    <input type="text" name="last_name" value="<?php echo esc_attr( $client['last_name'] ); ?>" />
                </label>
            </p>
            <p>
                <label><?php esc_html_e( 'Email', 'lucd' ); ?><br />
                    <input type="email" name="email" value="<?php echo esc_attr( $client['email'] ); ?>" />
                </label>
            </p>
            <p>
                <button type="submit"><?php esc_html_e( 'Save', 'lucd' ); ?></button>
                <button type="button" class="lucd-cancel-edit"><?php esc_html_e( 'Cancel', 'lucd' ); ?></button>
            </p>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle profile updates via AJAX.
     */
    public static function save_profile() {
        check_ajax_referer( 'lucd_save_profile', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'You are not logged in.', 'lucd' ) );
        }

        $user_id = get_current_user_id();

        $data = array(
            'first_name' => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
            'last_name'  => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
            'email'      => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
        );

        if ( empty( $data['email'] ) || ! is_email( $data['email'] ) ) {
            wp_send_json_error( __( 'Invalid email address.', 'lucd' ) );
        }

        global $wpdb;
        $table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        $updated = $wpdb->update(
            $table,
            $data,
            array( 'wp_user_id' => $user_id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( false === $updated ) {
            wp_send_json_error( __( 'Could not update profile.', 'lucd' ) );
        }

        wp_send_json_success( self::get_profile_section( $user_id ) );
    }
}
