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
        add_action( 'wp', array( __CLASS__, 'maybe_hide_admin_bar' ) );
    }

    /**
     * Render the dashboard markup.
     *
     * @return string
     */
    public static function render_dashboard() {
        self::enqueue_assets();

        $logged_in = is_user_logged_in();

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
                        <button type="button" class="lucd-nav-button" data-section="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></button>
                        <div class="lucd-mobile-content"></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="lucd-content" class="lucd-content">
                <?php
                if ( ! $logged_in ) {
                    wp_login_form();
                }
                ?>
            </div>
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

        if ( is_user_logged_in() ) {
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
    }

    /**
     * Hide the admin toolbar for subscribers on dashboard pages.
     */
    public static function maybe_hide_admin_bar() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'subscriber', (array) $user->roles, true ) ) {
            return;
        }

        $post = get_post();
        if ( $post && has_shortcode( $post->post_content, 'lucd_client_dashboard' ) ) {
            show_admin_bar( false );
        }
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
                $content = self::get_overview_section( $user_id );
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
     * Build overview section markup.
     *
     * @param int $user_id Current user ID.
     * @return string
     */
    private static function get_overview_section( $user_id ) {
        global $wpdb;

        $clients_table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        $projects_table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_table() );
        $tickets_table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::tickets_table() );
        $plugins_table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::plugins_table() );

        $client = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$clients_table} WHERE wp_user_id = %d", $user_id ), ARRAY_A );
        if ( ! $client ) {
            return '<p>' . esc_html__( 'Client record not found.', 'lucd' ) . '</p>';
        }

        $client_id      = (int) $client['client_id'];
        $projects_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$projects_table} WHERE client_id = %d", $client_id ) );
        $plugins_count  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$plugins_table} WHERE client_id = %d", $client_id ) );
        $tickets_count  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tickets_table} WHERE client_id = %d", $client_id ) );

        $required_fields   = array( 'first_name', 'last_name', 'email', 'mailing_address1', 'mailing_city', 'mailing_state', 'mailing_postcode', 'mailing_country' );
        $profile_complete = true;
        foreach ( $required_fields as $field ) {
            if ( empty( $client[ $field ] ) ) {
                $profile_complete = false;
                break;
            }
        }

        $projects_needing_attention = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$projects_table} WHERE client_id = %d AND status NOT IN ('Completed','Cancelled')", $client_id ) );
        $tickets_needing_attention  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tickets_table} WHERE client_id = %d AND status NOT IN ('Completed','No Longer Applicable')", $client_id ) );

        ob_start();
        ?>
        <div class="lucd-overview">
            <div class="lucd-info-bar">
                <div><?php printf( esc_html__( 'Client for %s', 'lucd' ), esc_html( human_time_diff( strtotime( $client['client_since'] ), current_time( 'timestamp' ) ) ) ); ?></div>
                <div><?php printf( esc_html__( '%d Projects & Services', 'lucd' ), $projects_count ); ?></div>
                <div><?php printf( esc_html__( '%d Plugins', 'lucd' ), $plugins_count ); ?></div>
                <div><?php printf( esc_html__( '%d Support Tickets', 'lucd' ), $tickets_count ); ?></div>
            </div>
            <div class="lucd-cards">
                <?php self::render_card( 'profile', __( 'Profile Info', 'lucd' ), $profile_complete, $profile_complete ? __( 'All Good!', 'lucd' ) : __( 'Your profile info needs attention!', 'lucd' ), $profile_complete ? 'check' : 'warning' ); ?>
                <?php self::render_card( 'projects', __( 'Projects & Services', 'lucd' ), ! $projects_needing_attention, $projects_needing_attention ? __( 'A project or service needs your attention!', 'lucd' ) : sprintf( __( 'Your %d Projects & Services are all good to go!', 'lucd' ), $projects_count ), $projects_needing_attention ? 'warning' : 'check' ); ?>
                <?php self::render_card( 'tickets', __( 'Support Tickets', 'lucd' ), ! $tickets_needing_attention, $tickets_needing_attention ? __( 'A support ticket needs your attention!', 'lucd' ) : __( 'Your support tickets are all resolved!', 'lucd' ), $tickets_needing_attention ? 'warning' : 'check' ); ?>
                <?php self::render_card( 'plugins', __( 'Your Plugins', 'lucd' ), null, __( 'Coming soon', 'lucd' ), 'info' ); ?>
                <?php self::render_card( 'billing', __( 'Billing', 'lucd' ), null, __( 'Coming soon', 'lucd' ), 'info' ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Output a dashboard card.
     *
     * @param string $section Section identifier.
     * @param string $title   Card title.
     * @param bool|null $good Whether the card shows a good state. Null for neutral.
     * @param string $message Message displayed under the icon.
     * @param string $icon    Icon type (check|warning|info).
     */
    private static function render_card( $section, $title, $good, $message, $icon ) {
        $status_class = '';
        if ( true === $good ) {
            $status_class = 'lucd-card-good';
        } elseif ( false === $good ) {
            $status_class = 'lucd-card-attention';
        }
        ?>
        <div class="lucd-card <?php echo esc_attr( $status_class ); ?>" data-section="<?php echo esc_attr( $section ); ?>">
            <h3><?php echo esc_html( $title ); ?></h3>
            <div class="lucd-card-icon lucd-icon-<?php echo esc_attr( $icon ); ?>"></div>
            <p class="lucd-card-message"><?php echo esc_html( $message ); ?></p>
        </div>
        <?php
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
