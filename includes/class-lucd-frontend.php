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
                $content = self::get_projects_section( $user_id );
                break;
            case 'tickets':
                $content = self::get_tickets_section( $user_id );
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

        $project_notes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT project_name, attention_needed, critical_issue FROM {$projects_table} WHERE client_id = %d",
                $client_id
            ),
            ARRAY_A
        );

        $ticket_notes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ticket_id, attention_needed, critical_issue FROM {$tickets_table} WHERE client_id = %d",
                $client_id
            ),
            ARRAY_A
        );

        $profile_critical  = self::filter_notes( array( isset( $client['critical_issue'] ) ? $client['critical_issue'] : '' ) );
        $profile_attention = self::filter_notes( array( isset( $client['attention_needed'] ) ? $client['attention_needed'] : '' ) );

        $projects_critical  = array();
        $projects_attention = array();
        foreach ( $project_notes as $project_note ) {
            $label = isset( $project_note['project_name'] ) ? $project_note['project_name'] : '';

            $critical = self::format_labelled_note( $label, isset( $project_note['critical_issue'] ) ? $project_note['critical_issue'] : '' );
            if ( '' !== $critical ) {
                $projects_critical[] = $critical;
            }

            $attention = self::format_labelled_note( $label, isset( $project_note['attention_needed'] ) ? $project_note['attention_needed'] : '' );
            if ( '' !== $attention ) {
                $projects_attention[] = $attention;
            }
        }

        $tickets_critical  = array();
        $tickets_attention = array();
        foreach ( $ticket_notes as $ticket_note ) {
            $ticket_id = isset( $ticket_note['ticket_id'] ) ? (int) $ticket_note['ticket_id'] : 0;
            $label     = $ticket_id ? sprintf( __( 'Ticket #%d', 'lucd' ), $ticket_id ) : __( 'Ticket', 'lucd' );

            $critical = self::format_labelled_note( $label, isset( $ticket_note['critical_issue'] ) ? $ticket_note['critical_issue'] : '' );
            if ( '' !== $critical ) {
                $tickets_critical[] = $critical;
            }

            $attention = self::format_labelled_note( $label, isset( $ticket_note['attention_needed'] ) ? $ticket_note['attention_needed'] : '' );
            if ( '' !== $attention ) {
                $tickets_attention[] = $attention;
            }
        }

        $profile_status = self::build_card_status(
            $profile_critical,
            $profile_attention,
            __( 'All profile information is clear.', 'lucd' )
        );

        $projects_status = self::build_card_status(
            $projects_critical,
            $projects_attention,
            $projects_count ? __( 'No projects or services currently require attention.', 'lucd' ) : __( 'No projects or services have been added yet.', 'lucd' )
        );

        $tickets_status = self::build_card_status(
            $tickets_critical,
            $tickets_attention,
            $tickets_count ? __( 'No support tickets currently require attention.', 'lucd' ) : __( 'No support tickets have been opened yet.', 'lucd' )
        );

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
                <?php self::render_card( 'profile', __( 'Profile Info', 'lucd' ), $profile_status ); ?>
                <?php self::render_card( 'projects', __( 'Projects & Services', 'lucd' ), $projects_status ); ?>
                <?php self::render_card( 'tickets', __( 'Support Tickets', 'lucd' ), $tickets_status ); ?>
                <?php self::render_card( 'plugins', __( 'Your Plugins', 'lucd' ), array(
                    'class'    => '',
                    'icon'     => 'info',
                    'messages' => array( __( 'Coming soon', 'lucd' ) ),
                ) ); ?>
                <?php self::render_card( 'billing', __( 'Billing', 'lucd' ), array(
                    'class'    => '',
                    'icon'     => 'info',
                    'messages' => array( __( 'Coming soon', 'lucd' ) ),
                ) ); ?>
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
     * @param array  $status  Status data with class, icon, and messages keys.
     */
    private static function render_card( $section, $title, array $status ) {
        $status_class = isset( $status['class'] ) ? (string) $status['class'] : '';
        $icon         = isset( $status['icon'] ) ? (string) $status['icon'] : 'info';
        $messages     = array();

        if ( ! empty( $status['messages'] ) && is_array( $status['messages'] ) ) {
            foreach ( $status['messages'] as $message ) {
                $normalized = self::normalize_note( $message );
                if ( '' !== $normalized ) {
                    $messages[] = $normalized;
                }
            }
        }

        if ( empty( $messages ) ) {
            $messages[] = '';
        }
        ?>
        <div class="lucd-card <?php echo esc_attr( $status_class ); ?>" data-section="<?php echo esc_attr( $section ); ?>">
            <h3><?php echo esc_html( $title ); ?></h3>
            <div class="lucd-card-icon lucd-icon-<?php echo esc_attr( $icon ); ?>"></div>
            <div class="lucd-card-messages">
                <?php foreach ( $messages as $message ) : ?>
                    <p class="lucd-card-message"><?php echo nl2br( esc_html( $message ) ); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Prepare the card status data based on attention and critical notes.
     *
     * @param array  $critical_notes  Critical issue notes.
     * @param array  $attention_notes Attention needed notes.
     * @param string $default_message Default message when no notes are present.
     * @return array
     */
    private static function build_card_status( array $critical_notes, array $attention_notes, $default_message ) {
        $critical  = self::filter_notes( $critical_notes );
        $attention = self::filter_notes( $attention_notes );

        if ( ! empty( $critical ) ) {
            return array(
                'class'    => 'lucd-card-critical',
                'icon'     => 'critical',
                'messages' => self::format_alert_messages( $critical, $attention ),
            );
        }

        if ( ! empty( $attention ) ) {
            return array(
                'class'    => 'lucd-card-attention',
                'icon'     => 'warning',
                'messages' => self::format_alert_messages( array(), $attention ),
            );
        }

        $default_note = self::normalize_note( $default_message );

        return array(
            'class'    => 'lucd-card-good',
            'icon'     => 'check',
            'messages' => array( '' !== $default_note ? $default_note : '' ),
        );
    }

    /**
     * Format alert notes for display with their labels.
     *
     * @param array $critical_notes  Critical issue notes.
     * @param array $attention_notes Attention needed notes.
     * @return array
     */
    private static function format_alert_messages( array $critical_notes, array $attention_notes ) {
        $messages = array();

        foreach ( $critical_notes as $note ) {
            $message = self::build_alert_message( $note, 'critical' );
            if ( '' !== $message ) {
                $messages[] = $message;
            }
        }

        foreach ( $attention_notes as $note ) {
            $message = self::build_alert_message( $note, 'attention' );
            if ( '' !== $message ) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    /**
     * Build a single alert message with its translated label.
     *
     * @param string $note Alert note text.
     * @param string $type Alert type identifier.
     * @return string
     */
    private static function build_alert_message( $note, $type ) {
        $normalized = self::normalize_note( $note );
        if ( '' === $normalized ) {
            return '';
        }

        $label = self::get_alert_label( $type );
        if ( '' === $label ) {
            return $normalized;
        }

        return sprintf(
            /* translators: 1: alert label (e.g. Critical Issue), 2: alert message. */
            __( '%1$s: %2$s', 'lucd' ),
            $label,
            $normalized
        );
    }

    /**
     * Map alert types to their human readable labels.
     *
     * @param string $type Alert type identifier.
     * @return string
     */
    private static function get_alert_label( $type ) {
        switch ( $type ) {
            case 'critical':
                return __( 'Critical Issue', 'lucd' );
            case 'attention':
                return __( 'Attention Needed', 'lucd' );
            default:
                return '';
        }
    }

    /**
     * Prepare alert items for rendering in a notification bar.
     *
     * @param array $critical_notes  Critical issue notes.
     * @param array $attention_notes Attention needed notes.
     * @return array
     */
    private static function prepare_alert_items( array $critical_notes, array $attention_notes ) {
        $items = array();

        foreach ( self::filter_notes( $critical_notes ) as $note ) {
            $items[] = array(
                'type'    => 'critical',
                'message' => $note,
            );
        }

        foreach ( self::filter_notes( $attention_notes ) as $note ) {
            $items[] = array(
                'type'    => 'attention',
                'message' => $note,
            );
        }

        return $items;
    }

    /**
     * Output an info bar with alert messages when needed.
     *
     * @param array $alerts Alert data produced by prepare_alert_items().
     */
    private static function render_alert_bar( array $alerts ) {
        $prepared = array();

        foreach ( $alerts as $alert ) {
            $message = isset( $alert['message'] ) ? self::normalize_note( $alert['message'] ) : '';
            if ( '' === $message ) {
                continue;
            }

            $type = isset( $alert['type'] ) && 'critical' === $alert['type'] ? 'critical' : 'attention';

            $prepared[] = array(
                'type'    => $type,
                'message' => $message,
            );
        }

        if ( empty( $prepared ) ) {
            return;
        }

        echo '<div class="lucd-info-bar lucd-info-bar-alert">';
        foreach ( $prepared as $alert ) {
            $class = 'critical' === $alert['type'] ? 'lucd-info-critical' : 'lucd-info-attention';
            $label = self::get_alert_label( $alert['type'] );

            echo '<div class="' . esc_attr( $class ) . '">';
            if ( '' !== $label ) {
                echo '<strong>' . esc_html( $label ) . ':</strong> ';
            }
            echo nl2br( esc_html( $alert['message'] ) );
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Format a labelled note for display.
     *
     * @param string $label Optional label describing the note subject.
     * @param string $note  The note text.
     * @return string
     */
    private static function format_labelled_note( $label, $note ) {
        $normalized_note = self::normalize_note( $note );
        if ( '' === $normalized_note ) {
            return '';
        }

        $normalized_label = self::normalize_note( $label );
        if ( '' === $normalized_label ) {
            return $normalized_note;
        }

        return sprintf( '%s — %s', $normalized_label, $normalized_note );
    }

    /**
     * Filter notes to remove empty values and normalize whitespace.
     *
     * @param array $notes Collection of note strings.
     * @return array
     */
    private static function filter_notes( array $notes ) {
        $filtered = array();
        foreach ( $notes as $note ) {
            $normalized = self::normalize_note( $note );
            if ( '' !== $normalized ) {
                $filtered[] = $normalized;
            }
        }
        return $filtered;
    }

    /**
     * Normalize note text to a trimmed string.
     *
     * @param mixed $text Raw text value.
     * @return string
     */
    private static function normalize_note( $text ) {
        if ( is_array( $text ) || is_object( $text ) ) {
            return '';
        }

        return trim( (string) $text );
    }

    /**
     * Build Projects & Services section markup.
     *
     * @param int $user_id Current user ID.
     * @return string
     */
    private static function get_projects_section( $user_id ) {
        global $wpdb;

        $clients_table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        $projects_table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_table() );

        $client = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$clients_table} WHERE wp_user_id = %d", $user_id ), ARRAY_A );
        if ( ! $client ) {
            return '<p>' . esc_html__( 'Client record not found.', 'lucd' ) . '</p>';
        }

        $projects = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$projects_table} WHERE client_id = %d", (int) $client['client_id'] ), ARRAY_A );
        if ( empty( $projects ) ) {
            return '<p>' . esc_html__( 'No projects found.', 'lucd' ) . '</p>';
        }

        $fields = LUC_D_Helpers::get_project_fields();

        $client_label = $client['company_name'] ? $client['company_name'] : trim( $client['first_name'] . ' ' . $client['last_name'] );

        $project_critical  = array();
        $project_attention = array();
        foreach ( $projects as $project ) {
            $label               = isset( $project['project_name'] ) ? $project['project_name'] : '';
            $project_critical[]  = self::format_labelled_note( $label, isset( $project['critical_issue'] ) ? $project['critical_issue'] : '' );
            $project_attention[] = self::format_labelled_note( $label, isset( $project['attention_needed'] ) ? $project['attention_needed'] : '' );
        }

        $alerts = self::prepare_alert_items( $project_critical, $project_attention );

        ob_start();
        self::render_alert_bar( $alerts );
        foreach ( $projects as $project ) {
            $project['project_client'] = $client_label;
            echo '<div class="lucd-accordion">';
            echo '<h3 class="lucd-accordion-header">' . esc_html( $project['project_name'] ) . '</h3>';
            echo '<div class="lucd-accordion-content">';
            foreach ( $fields as $field => $info ) {
                if ( in_array( $field, array( 'client_id', 'project_name' ), true ) || 'hidden' === $info['type'] ) {
                    continue;
                }
                $value = isset( $project[ $field ] ) ? $project[ $field ] : '';
                echo '<div class="lucd-field">';
                echo '<label>' . esc_html( $info['label'] ) . '</label>';
                if ( 'url' === $info['type'] && $value ) {
                    echo '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener">' . esc_html( $value ) . '</a>';
                } else {
                    echo '<div class="lucd-field-value">' . nl2br( esc_html( $value ) ) . '</div>';
                }
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        return ob_get_clean();
    }

    /**
     * Build Support Tickets section markup.
     *
     * @param int $user_id Current user ID.
     * @return string
     */
    private static function get_tickets_section( $user_id ) {
        global $wpdb;

        $clients_table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        $tickets_table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::tickets_table() );

        $client = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$clients_table} WHERE wp_user_id = %d", $user_id ), ARRAY_A );
        if ( ! $client ) {
            return '<p>' . esc_html__( 'Client record not found.', 'lucd' ) . '</p>';
        }

        $tickets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$tickets_table} WHERE client_id = %d ORDER BY creation_datetime DESC",
                (int) $client['client_id']
            ),
            ARRAY_A
        );

        if ( empty( $tickets ) ) {
            return '<p>' . esc_html__( 'No support tickets found.', 'lucd' ) . '</p>';
        }

        $fields = LUC_D_Helpers::get_ticket_fields();

        $client_label = $client['company_name'] ? $client['company_name'] : trim( $client['first_name'] . ' ' . $client['last_name'] );

        $ticket_critical  = array();
        $ticket_attention = array();

        foreach ( $tickets as $ticket ) {
            $ticket_number = isset( $ticket['ticket_id'] ) ? (int) $ticket['ticket_id'] : 0;
            $label         = $ticket_number ? sprintf( __( 'Ticket #%d', 'lucd' ), $ticket_number ) : __( 'Ticket', 'lucd' );

            $ticket_critical[]  = self::format_labelled_note( $label, isset( $ticket['critical_issue'] ) ? $ticket['critical_issue'] : '' );
            $ticket_attention[] = self::format_labelled_note( $label, isset( $ticket['attention_needed'] ) ? $ticket['attention_needed'] : '' );
        }

        $alerts = self::prepare_alert_items( $ticket_critical, $ticket_attention );

        ob_start();
        self::render_alert_bar( $alerts );

        foreach ( $tickets as $ticket ) {
            $ticket['ticket_client'] = $client_label;

            $ticket_number = isset( $ticket['ticket_id'] ) ? (int) $ticket['ticket_id'] : 0;
            $header_parts  = array();

            if ( $ticket_number ) {
                $header_parts[] = sprintf( __( 'Ticket #%d', 'lucd' ), $ticket_number );
            }

            $status = isset( $ticket['status'] ) ? self::normalize_note( $ticket['status'] ) : '';
            if ( '' !== $status ) {
                $header_parts[] = sprintf( __( 'Status: %s', 'lucd' ), $status );
            }

            if ( empty( $header_parts ) ) {
                $header_parts[] = __( 'Ticket', 'lucd' );
            }

            echo '<div class="lucd-accordion">';
            echo '<h3 class="lucd-accordion-header">' . esc_html( implode( ' — ', $header_parts ) ) . '</h3>';
            echo '<div class="lucd-accordion-content">';

            foreach ( $fields as $field => $info ) {
                if ( in_array( $field, array( 'client_id' ), true ) || 'hidden' === $info['type'] ) {
                    continue;
                }

                if ( 'ticket_id' === $field && $ticket_number ) {
                    continue;
                }

                $value = isset( $ticket[ $field ] ) ? $ticket[ $field ] : '';

                echo '<div class="lucd-field">';
                echo '<label>' . esc_html( $info['label'] ) . '</label>';
                echo '<div class="lucd-field-value">' . nl2br( esc_html( $value ) ) . '</div>';
                echo '</div>';
            }

            echo '</div>';
            echo '</div>';
        }

        return ob_get_clean();
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
        $client = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE wp_user_id = %d", $user_id ), ARRAY_A );

        if ( ! $client ) {
            return '<p>' . esc_html__( 'Client record not found.', 'lucd' ) . '</p>';
        }

        $fields = LUC_D_Helpers::get_client_fields();

        $profile_alerts = self::prepare_alert_items(
            array( isset( $client['critical_issue'] ) ? $client['critical_issue'] : '' ),
            array( isset( $client['attention_needed'] ) ? $client['attention_needed'] : '' )
        );

        ob_start();
        self::render_alert_bar( $profile_alerts );
        $logo_url = '';
        if ( ! empty( $client['company_logo'] ) ) {
            $logo_url = wp_get_attachment_image_url( (int) $client['company_logo'], 'full' );
        }
        $na_text = __( 'N/A', 'lucd' );
        ?>
        <div class="lucd-profile-view">
            <?php foreach ( $fields as $field => $info ) : ?>
                <?php
                if ( ! empty( $info['admin_only'] ) ) {
                    continue;
                }
                if ( in_array( $field, array( 'client_since', 'company_logo', 'mailing_country', 'company_country' ), true ) ) {
                    continue;
                }
                $value = isset( $client[ $field ] ) ? $client[ $field ] : '';
                if ( in_array( $field, array( 'mailing_state', 'company_state' ), true ) ) {
                    $states = LUC_D_Helpers::get_us_states();
                    $value  = isset( $states[ $value ] ) ? $states[ $value ] : $value;
                }
                $has_value = '' !== trim( (string) $value );
                ?>
                <div class="lucd-field">
                    <label><?php echo esc_html( $info['label'] ); ?></label>
                    <div class="lucd-field-value" data-full-text="<?php echo esc_attr( $has_value ? $value : '' ); ?>">
                        <?php
                        if ( $has_value && 'company_website' === $field ) {
                            $url = esc_url( $value );
                            if ( $url ) {
                                echo '<a href="' . $url . '" target="_blank" rel="noopener">' . esc_html( $value ) . '</a>';
                            } else {
                                echo esc_html( $value );
                            }
                        } elseif ( $has_value ) {
                            echo esc_html( $value );
                        } else {
                            echo esc_html( $na_text );
                        }
                        ?>
                    </div>
                </div>
                <?php if ( 'social_bbb' === $field ) : ?>
                    <div class="lucd-field lucd-field-logo">
                        <label><?php esc_html_e( 'Company Logo', 'lucd' ); ?></label>
                        <?php if ( $logo_url ) : ?>
                            <div class="lucd-logo-preview" style="background-image:url(<?php echo esc_url( $logo_url ); ?>);display:block;"></div>
                        <?php else : ?>
                            <div class="lucd-field-value" data-full-text=""><?php echo esc_html( $na_text ); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <p><button class="lucd-edit-profile"><?php esc_html_e( 'Edit Profile Info', 'lucd' ); ?></button></p>
        </div>
        <form class="lucd-profile-edit" style="display:none;" enctype="multipart/form-data">
            <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'lucd_save_profile' ) ); ?>" />
            <?php foreach ( $fields as $field => $info ) : ?>
                <?php
                if ( ! empty( $info['admin_only'] ) ) {
                    continue;
                }
                if ( in_array( $field, array( 'client_since', 'mailing_country', 'company_country' ), true ) ) {
                    continue;
                }
                $value = isset( $client[ $field ] ) ? $client[ $field ] : '';
                ?>
                <div class="lucd-field">
                    <label for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $info['label'] ); ?></label>
                    <?php if ( 'select' === $info['type'] ) : ?>
                        <select id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>">
                            <option value="" disabled <?php selected( '', $value ); ?>><?php esc_html_e( 'Choose a State...', 'lucd' ); ?></option>
                            <?php foreach ( $info['options'] as $abbr => $name ) : ?>
                                <option value="<?php echo esc_attr( $abbr ); ?>" <?php selected( $value, $abbr ); ?>><?php echo esc_html( $name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ( 'company_logo' === $field ) : ?>
                        <input type="file" name="company_logo" id="company_logo" accept="image/*" />
                        <div class="lucd-logo-preview" id="company_logo_preview" <?php echo $logo_url ? 'style="background-image:url(' . esc_url( $logo_url ) . ');display:block;"' : 'style="display:none;"'; ?>></div>
                    <?php else : ?>
                        <?php
                        $extra = '';
                        if ( in_array( $field, array( 'mailing_postcode', 'company_postcode' ), true ) ) {
                            $extra = ' pattern="\\d{5}(?:-\\d{4})?" maxlength="10"';
                        }
                        ?>
                        <input type="<?php echo esc_attr( $info['type'] ); ?>" id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>"<?php echo $extra; ?> />
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
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

        $fields = LUC_D_Helpers::get_client_fields();
        unset( $fields['client_since'] );

        $data    = array();
        $formats = array();

        foreach ( $fields as $field => $info ) {
            if ( ! empty( $info['admin_only'] ) ) {
                continue;
            }

            if ( in_array( $field, array( 'company_logo', 'mailing_country', 'company_country' ), true ) ) {
                continue;
            }

            $raw = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
            switch ( $info['type'] ) {
                case 'email':
                    $value = sanitize_email( $raw );
                    if ( empty( $value ) || ! is_email( $value ) ) {
                        wp_send_json_error( __( 'Invalid email address.', 'lucd' ) );
                    }
                    break;
                case 'textarea':
                    $value = sanitize_textarea_field( $raw );
                    break;
                case 'url':
                    $value = esc_url_raw( $raw );
                    break;
                default:
                    $value = sanitize_text_field( $raw );
            }

            if ( in_array( $field, array( 'mailing_postcode', 'company_postcode' ), true ) && $value && ! LUC_D_Helpers::is_valid_zip( $value ) ) {
                wp_send_json_error( sprintf( __( '%s must be a valid U.S. ZIP code.', 'lucd' ), $info['label'] ) );
            }

            if ( in_array( $field, array( 'mailing_state', 'company_state' ), true ) && $value && ! array_key_exists( $value, LUC_D_Helpers::get_us_states() ) ) {
                wp_send_json_error( sprintf( __( '%s must be a valid U.S. state or territory.', 'lucd' ), $info['label'] ) );
            }

            $data[ $field ] = $value;
            $formats[]      = '%s';
        }

        foreach ( array( 'first_name', 'last_name' ) as $required ) {
            if ( empty( $data[ $required ] ) ) {
                wp_send_json_error( sprintf( __( '%s is required.', 'lucd' ), $fields[ $required ]['label'] ) );
            }
        }

        if ( ! empty( $_FILES['company_logo']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $upload = wp_handle_upload( $_FILES['company_logo'], array( 'test_form' => false, 'mimes' => array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif' ) ) );
            if ( isset( $upload['error'] ) ) {
                wp_send_json_error( $upload['error'] );
            }
            $attachment_id = wp_insert_attachment( array(
                'post_mime_type' => $upload['type'],
                'post_title'     => sanitize_file_name( $_FILES['company_logo']['name'] ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ), $upload['file'] );
            $attach_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
            wp_update_attachment_metadata( $attachment_id, $attach_data );
            $data['company_logo'] = $attachment_id;
            $formats[]            = '%d';
        }

        $userdata = array(
            'ID'         => $user_id,
            'user_email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
        );
        $result = wp_update_user( $userdata );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        global $wpdb;
        $table   = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
        $updated = $wpdb->update( $table, $data, array( 'wp_user_id' => $user_id ), $formats, array( '%d' ) );

        if ( false === $updated ) {
            wp_send_json_error( __( 'Could not update profile.', 'lucd' ) );
        }

        wp_send_json_success( self::get_profile_section( $user_id ) );
    }
}
