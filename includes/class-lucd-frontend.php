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
            $critical_note = isset( $project_note['critical_issue'] ) ? $project_note['critical_issue'] : '';
            if ( '' !== self::normalize_note( $critical_note ) ) {
                $projects_critical[] = $critical_note;
            }

            $attention_note = isset( $project_note['attention_needed'] ) ? $project_note['attention_needed'] : '';
            if ( '' !== self::normalize_note( $attention_note ) ) {
                $projects_attention[] = $attention_note;
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
        $has_alert    = false;

        if ( ! empty( $status['messages'] ) && is_array( $status['messages'] ) ) {
            foreach ( $status['messages'] as $message ) {
                $type = 'info';
                $text = '';

                if ( is_array( $message ) ) {
                    $type = isset( $message['type'] ) ? (string) $message['type'] : 'info';
                    $text = isset( $message['message'] ) ? $message['message'] : '';
                } else {
                    $text = $message;
                }

                $normalized = self::normalize_note( $text );
                if ( '' === $normalized ) {
                    continue;
                }

                if ( in_array( $type, array( 'critical', 'attention' ), true ) ) {
                    $has_alert = true;
                }

                $messages[] = array(
                    'type'    => $type,
                    'message' => $normalized,
                );
            }
        }

        if ( empty( $messages ) ) {
            $messages[] = array(
                'type'    => 'info',
                'message' => '',
            );
        }
        ?>
        <div class="lucd-card <?php echo esc_attr( $status_class ); ?>" data-section="<?php echo esc_attr( $section ); ?>">
            <h3><?php echo esc_html( $title ); ?></h3>
            <?php if ( ! $has_alert ) : ?>
                <div class="lucd-card-icon lucd-icon-<?php echo esc_attr( $icon ); ?>"></div>
            <?php endif; ?>
            <div class="lucd-card-messages">
                <?php foreach ( $messages as $message ) : ?>
                    <?php
                    $type       = isset( $message['type'] ) ? (string) $message['type'] : 'info';
                    $text       = isset( $message['message'] ) ? $message['message'] : '';
                    $classes    = array( 'lucd-card-message' );
                    $icon_class = '';
                    $label      = '';

                    if ( in_array( $type, array( 'critical', 'attention' ), true ) ) {
                        $icon_class = 'critical' === $type ? 'lucd-icon-critical' : 'lucd-icon-warning';
                        $label      = self::get_alert_label( $type );
                        $classes[]  = 'lucd-card-message-alert';
                    }
                    ?>
                    <div class="<?php echo esc_attr( implode( ' ', array_unique( $classes ) ) ); ?>">
                        <span class="lucd-card-message-text">
                            <?php if ( '' !== $icon_class ) : ?>
                                <span class="lucd-card-message-icon <?php echo esc_attr( $icon_class ); ?>" aria-hidden="true"></span>
                                <?php if ( '' !== $label ) : ?>
                                    <span class="lucd-visually-hidden"><?php echo esc_html( $label ); ?>:</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ( '' === $text ) : ?>
                                &nbsp;
                            <?php else : ?>
                                <?php echo nl2br( esc_html( $text ) ); ?>
                            <?php endif; ?>
                        </span>
                    </div>
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

        if ( '' === $default_note ) {
            return array(
                'class'    => 'lucd-card-good',
                'icon'     => 'check',
                'messages' => array(),
            );
        }

        return array(
            'class'    => 'lucd-card-good',
            'icon'     => 'check',
            'messages' => array(
                array(
                    'type'    => 'info',
                    'message' => $default_note,
                ),
            ),
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
            if ( $message ) {
                $messages[] = $message;
            }
        }

        foreach ( $attention_notes as $note ) {
            $message = self::build_alert_message( $note, 'attention' );
            if ( $message ) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    /**
     * Build a single alert message with its alert type metadata.
     *
     * @param string $note Alert note text.
     * @param string $type Alert type identifier.
     * @return array|null
     */
    private static function build_alert_message( $note, $type ) {
        $normalized = self::normalize_note( $note );
        if ( '' === $normalized ) {
            return null;
        }

        $alert_type = in_array( $type, array( 'critical', 'attention' ), true ) ? $type : 'info';

        return array(
            'type'    => $alert_type,
            'message' => $normalized,
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
                echo '<span class="lucd-alert-label">' . esc_html( $label ) . ':</span>';
            }
            echo '<span class="lucd-alert-text">' . nl2br( esc_html( $alert['message'] ) ) . '</span>';
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

        return sprintf( '%s - %s', $normalized_label, $normalized_note );
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
     * Determine whether a field should be displayed for the current record.
     *
     * @param string $field Field key.
     * @param mixed  $value Field value.
     * @return bool
     */
    private static function should_display_field( $field, $value ) {
        if ( in_array( $field, array( 'attention_needed', 'critical_issue' ), true ) ) {
            return '' !== self::normalize_note( $value );
        }

        return true;
    }

    /**
     * Prepare text for use within field value containers.
     *
     * @param mixed $value Raw field value.
     * @return string
     */
    private static function prepare_field_text( $value ) {
        if ( is_object( $value ) ) {
            if ( method_exists( $value, '__toString' ) ) {
                $value = (string) $value;
            } else {
                return '';
            }
        }

        if ( is_array( $value ) ) {
            return '';
        }

        return trim( (string) $value );
    }

    /**
     * Format field text for truncated display.
     *
     * @param string $text Prepared field text.
     * @return string
     */
    private static function format_field_display_text( $text ) {
        if ( '' === $text ) {
            return '';
        }

        $display = str_replace( array( "\r\n", "\r", "\n" ), ' ', $text );
        $display = preg_replace( '/\s{2,}/', ' ', $display );

        return trim( $display );
    }

    /**
     * Determine whether a field value should allow multi-line display before truncation.
     *
     * @param string $text Prepared display text.
     * @param string $type Field type identifier.
     * @return bool
     */
    private static function should_allow_multiline_value( $text, $type ) {
        if ( '' === $text ) {
            return false;
        }

        if ( in_array( $type, array( 'url', 'email' ), true ) ) {
            return false;
        }

        return (bool) preg_match( '/\s/', $text );
    }

    /**
     * Apply contextual formatting for field values prior to display.
     *
     * @param string $text  Raw field text.
     * @param string $field Field name.
     * @param array  $info  Field metadata definition.
     * @return string
     */
    private static function transform_field_display_value( $text, $field, array $info ) {
        $value = self::format_currency_value( $text, $field, $info );
        $value = self::format_support_time_value( $value, $field );
        $value = self::format_duration_value( $value, $field );

        return self::format_datetime_for_display( $value, $field, $info );
    }

    /**
     * Format numeric currency values with a US dollar prefix.
     *
     * @param string $text  Raw field text.
     * @param string $field Field name.
     * @param array  $info  Field metadata definition.
     * @return string
     */
    private static function format_currency_value( $text, $field, array $info ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return '';
        }

        if ( ! self::is_currency_field( $field, $info ) ) {
            return $text;
        }

        $normalized = preg_replace( '/[^0-9\.\-]/', '', $text );

        if ( '' === $normalized || ! is_numeric( $normalized ) ) {
            $trimmed = trim( $text );
            if ( '' === $trimmed || 0 === strpos( $trimmed, '$' ) ) {
                return $trimmed;
            }

            return $trimmed;
        }

        $value = (float) $normalized;
        $sign  = $value < 0 ? '-' : '';

        $formatted_number = self::format_number( abs( $value ), 2 );

        return $sign . '$' . $formatted_number;
    }

    /**
     * Determine whether a field should be treated as currency.
     *
     * @param string $field Field name.
     * @param array  $info  Field metadata definition.
     * @return bool
     */
    private static function is_currency_field( $field, array $info ) {
        $currency_fields = array( 'total_one_time_cost', 'mrr', 'arr' );
        if ( in_array( $field, $currency_fields, true ) ) {
            return true;
        }

        if ( empty( $info['class'] ) ) {
            return false;
        }

        $classes = preg_split( '/\s+/', $info['class'] );
        if ( ! $classes || ! is_array( $classes ) ) {
            return false;
        }

        foreach ( $classes as $class ) {
            if ( 'lucd-currency' === $class ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Append an hours label to monthly support time values.
     *
     * @param string $text  Raw field text.
     * @param string $field Field name.
     * @return string
     */
    private static function format_support_time_value( $text, $field ) {
        if ( 'monthly_support_time' !== $field ) {
            return $text;
        }

        $text = trim( (string) $text );
        if ( '' === $text ) {
            return '';
        }

        $normalized = preg_replace( '/[^0-9\.\-]/', '', $text );
        if ( '' === $normalized || ! is_numeric( $normalized ) ) {
            return $text;
        }

        $value      = (float) $normalized;
        $precision  = self::get_decimal_precision( $normalized, 2 );
        $number     = self::format_number( $value, $precision );
        $abs_value  = abs( $value );
        $is_singular = abs( $abs_value - 1 ) < 0.00001;

        $label = $is_singular ? __( 'Hour', 'lucd' ) : __( 'Hours', 'lucd' );

        return trim( $number . ' ' . $label );
    }

    /**
     * Convert minute durations into hour and minute strings.
     *
     * @param string $text  Raw field text.
     * @param string $field Field name.
     * @return string
     */
    private static function format_duration_value( $text, $field ) {
        if ( 'duration_minutes' !== $field ) {
            return $text;
        }

        $text = trim( (string) $text );
        if ( '' === $text ) {
            return '';
        }

        $normalized = preg_replace( '/[^0-9\-]/', '', $text );
        if ( '' === $normalized || '-' === $normalized || ! is_numeric( $normalized ) ) {
            return $text;
        }

        $minutes     = (int) $normalized;
        $abs_minutes = abs( $minutes );
        $hours       = (int) floor( $abs_minutes / 60 );
        $remaining   = $abs_minutes % 60;
        $parts       = array();

        if ( $hours > 0 ) {
            $parts[] = sprintf( _n( '%d Hour', '%d Hours', $hours, 'lucd' ), $hours );
        }

        if ( $remaining > 0 || 0 === $hours ) {
            $parts[] = sprintf( _n( '%d Minute', '%d Minutes', $remaining, 'lucd' ), $remaining );
        }

        $formatted = implode( ', ', $parts );

        if ( $minutes < 0 ) {
            return '-' . ltrim( $formatted );
        }

        return $formatted;
    }

    /**
     * Determine decimal precision based on a numeric string.
     *
     * @param string $number_string Numeric string to evaluate.
     * @param int    $max_decimals  Maximum decimals allowed.
     * @return int
     */
    private static function get_decimal_precision( $number_string, $max_decimals = 2 ) {
        $number_string = (string) $number_string;
        $decimal_pos   = strpos( $number_string, '.' );

        if ( false === $decimal_pos ) {
            return 0;
        }

        $fraction = substr( $number_string, $decimal_pos + 1 );
        $fraction = rtrim( $fraction, '0' );

        if ( '' === $fraction ) {
            return 0;
        }

        return min( $max_decimals, strlen( $fraction ) );
    }

    /**
     * Format numbers using WordPress helpers when available.
     *
     * @param float $number    Number to format.
     * @param int   $decimals  Number of decimal places.
     * @return string
     */
    private static function format_number( $number, $decimals = 0 ) {
        if ( function_exists( 'number_format_i18n' ) ) {
            return number_format_i18n( $number, $decimals );
        }

        return number_format( $number, $decimals, '.', ',' );
    }

    /**
     * Get the placeholder label for unknown date values.
     *
     * @return string
     */
    private static function get_tbd_label() {
        return __( 'TBD', 'lucd' );
    }

    /**
     * Check whether a datetime string represents a zero value.
     *
     * @param string $value Raw datetime string.
     * @return bool
     */
    private static function is_zero_date_value( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return false;
        }

        return (bool) preg_match( '/^0{4}-0{2}-0{2}(?:[ T]0{2}:0{2}(?::0{2})?(?:\.0+)?)?$/', $value );
    }

    /**
     * Format field text as a human-readable date or time when appropriate.
     *
     * @param string $text  Raw field text.
     * @param string $field Field name.
     * @param array  $info  Field metadata definition.
     * @return string
     */
    private static function format_datetime_for_display( $text, $field, array $info ) {
        $text = trim( (string) $text );
        if ( '' === $text ) {
            return '';
        }

        $type            = isset( $info['type'] ) ? $info['type'] : 'text';
        $datetime_fields = array(
            'client_since',
            'start_date',
            'end_date',
            'creation_datetime',
            'start_time',
            'end_time',
        );

        $should_format = 'date' === $type || in_array( $field, $datetime_fields, true );

        if ( ! $should_format ) {
            return $text;
        }

        if ( self::is_zero_date_value( $text ) ) {
            return self::get_tbd_label();
        }

        $formatted = self::format_human_readable_datetime( $text );

        if ( '' === $formatted ) {
            return $text;
        }

        return $formatted;
    }

    /**
     * Convert a stored datetime string into a human-friendly representation.
     *
     * @param string $value Raw date or time string.
     * @return string
     */
    private static function format_human_readable_datetime( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return '';
        }

        $normalized = str_replace( 'T', ' ', $value );
        $has_date   = (bool) preg_match( '/\d{4}-\d{2}-\d{2}/', $normalized );
        $has_time   = (bool) preg_match( '/\d{1,2}:\d{2}/', $normalized );

        $timestamp = strtotime( $normalized );
        if ( false === $timestamp ) {
            return '';
        }

        if ( $has_date && $has_time ) {
            return self::format_timestamp_value( $timestamp, 'n/j/Y - g:i A' );
        }

        if ( $has_time && ! $has_date ) {
            return self::format_timestamp_value( $timestamp, 'g:i A' );
        }

        if ( $has_date ) {
            return self::format_timestamp_value( $timestamp, 'n/j/Y' );
        }

        return '';
    }

    /**
     * Format a timestamp using WordPress internationalization helpers when available.
     *
     * @param int    $timestamp Unix timestamp.
     * @param string $format    Desired format string.
     * @return string
     */
    private static function format_timestamp_value( $timestamp, $format ) {
        if ( function_exists( 'date_i18n' ) ) {
            return date_i18n( $format, $timestamp );
        }

        return date( $format, $timestamp );
    }

    /**
     * Retrieve the placeholder markup for empty field values.
     *
     * @return string
     */
    private static function get_empty_placeholder_markup() {
        static $markup = null;

        if ( null !== $markup ) {
            return $markup;
        }

        $image_url = plugins_url( 'assets/img/letter-x.svg', LUCD_PLUGIN_FILE );
        $markup    = sprintf(
            '<span class="lucd-field-empty"><img src="%1$s" alt="%2$s" /></span>',
            esc_url( $image_url ),
            esc_attr__( 'Not available', 'lucd' )
        );

        return $markup;
    }

    /**
     * Build markup for displaying a field value.
     *
     * @param string $field Field key.
     * @param mixed  $value Field value.
     * @param array  $info  Field metadata.
     * @return string
     */
    private static function get_field_value_markup( $field, $value, array $info ) {
        $raw_text = self::prepare_field_text( $value );
        $type     = isset( $info['type'] ) ? $info['type'] : 'text';

        $classes = array( 'lucd-field-value' );
        if ( ! empty( $info['class'] ) ) {
            $extra_classes = preg_split( '/\s+/', $info['class'] );
            if ( $extra_classes && is_array( $extra_classes ) ) {
                foreach ( $extra_classes as $extra_class ) {
                    $sanitized = sanitize_html_class( $extra_class );
                    if ( '' !== $sanitized ) {
                        $classes[] = $sanitized;
                    }
                }
            }
        }

        if ( '' === $raw_text ) {
            $attributes = sprintf(
                'class="%1$s" data-full-text=""',
                esc_attr( implode( ' ', array_unique( $classes ) ) )
            );

            return '<div ' . $attributes . '>' . self::get_empty_placeholder_markup() . '</div>';
        }

        $display_source = self::transform_field_display_value( $raw_text, $field, $info );
        $display_text   = self::format_field_display_text( $display_source );

        if ( self::should_allow_multiline_value( $display_text, $type ) ) {
            $classes[] = 'lucd-field-multiline';
        }

        $attributes = sprintf(
            'class="%1$s" data-full-text="%2$s"',
            esc_attr( implode( ' ', array_unique( $classes ) ) ),
            esc_attr( $display_text )
        );

        $output = '<div ' . $attributes . '>';

        switch ( $type ) {
            case 'url':
                $url = esc_url( $raw_text );
                if ( $url ) {
                    $output .= '<a href="' . $url . '" target="_blank" rel="noopener">' . esc_html( $display_text ) . '</a>';
                } else {
                    $output .= esc_html( $display_text );
                }
                break;
            case 'email':
                $email = sanitize_email( $raw_text );
                if ( $email ) {
                    $mailto = antispambot( $email );
                    $output .= '<a href="mailto:' . esc_attr( $mailto ) . '">' . esc_html( $display_text ) . '</a>';
                } else {
                    $output .= esc_html( $display_text );
                }
                break;
            default:
                $output .= esc_html( $display_text );
                break;
        }

        $output .= '</div>';

        return $output;
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

        $project_critical  = array();
        $project_attention = array();
        foreach ( $projects as $project ) {
            $critical_note = isset( $project['critical_issue'] ) ? $project['critical_issue'] : '';
            if ( '' !== self::normalize_note( $critical_note ) ) {
                $project_critical[] = $critical_note;
            }

            $attention_note = isset( $project['attention_needed'] ) ? $project['attention_needed'] : '';
            if ( '' !== self::normalize_note( $attention_note ) ) {
                $project_attention[] = $attention_note;
            }
        }

        $alerts = self::prepare_alert_items( $project_critical, $project_attention );

        ob_start();
        self::render_alert_bar( $alerts );
        foreach ( $projects as $project ) {
            echo '<div class="lucd-accordion">';
            echo '<h3 class="lucd-accordion-header">' . esc_html( $project['project_name'] ) . '</h3>';
            echo '<div class="lucd-accordion-content">';
            foreach ( $fields as $field => $info ) {
                if ( in_array( $field, array( 'client_id', 'project_name', 'project_client' ), true ) || 'hidden' === $info['type'] ) {
                    continue;
                }
                $value = isset( $project[ $field ] ) ? $project[ $field ] : '';
                if ( ! self::should_display_field( $field, $value ) ) {
                    continue;
                }

                echo '<div class="lucd-field">';
                echo '<label>' . esc_html( $info['label'] ) . '</label>';
                echo self::get_field_value_markup( $field, $value, $info );
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
            $ticket_number = isset( $ticket['ticket_id'] ) ? (int) $ticket['ticket_id'] : 0;
            $header_parts  = array();

            if ( $ticket_number ) {
                $header_title = sprintf( __( 'Ticket #%d', 'lucd' ), $ticket_number );
            } else {
                $header_title = __( 'Ticket', 'lucd' );
            }

            $created_at     = isset( $ticket['creation_datetime'] ) ? trim( (string) $ticket['creation_datetime'] ) : '';
            $formatted_date = '';
            if ( '' !== $created_at ) {
                $formatted_date = self::format_human_readable_datetime( $created_at );

                if ( '' === $formatted_date ) {
                    $formatted_date = self::format_field_display_text( $created_at );
                }
            }

            $header_parts[] = $header_title;

            if ( '' !== $formatted_date ) {
                $header_parts[] = $formatted_date;
            }

            $status = isset( $ticket['status'] ) ? self::normalize_note( $ticket['status'] ) : '';
            if ( '' !== $status ) {
                $header_parts[] = sprintf( __( 'Status: %s', 'lucd' ), $status );
            }

            if ( empty( $header_parts ) ) {
                $header_parts[] = __( 'Ticket', 'lucd' );
            }

            echo '<div class="lucd-accordion">';
            echo '<h3 class="lucd-accordion-header">' . esc_html( implode( ' - ', $header_parts ) ) . '</h3>';
            echo '<div class="lucd-accordion-content">';

            foreach ( $fields as $field => $info ) {
                if ( in_array( $field, array( 'client_id', 'ticket_client' ), true ) || 'hidden' === $info['type'] ) {
                    continue;
                }

                if ( 'ticket_id' === $field && $ticket_number ) {
                    continue;
                }

                $value = isset( $ticket[ $field ] ) ? $ticket[ $field ] : '';

                if ( ! self::should_display_field( $field, $value ) ) {
                    continue;
                }

                echo '<div class="lucd-field">';
                echo '<label>' . esc_html( $info['label'] ) . '</label>';
                echo self::get_field_value_markup( $field, $value, $info );
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
                ?>
                <div class="lucd-field">
                    <label><?php echo esc_html( $info['label'] ); ?></label>
                    <?php echo self::get_field_value_markup( $field, $value, $info ); ?>
                </div>
                <?php if ( 'social_bbb' === $field ) : ?>
                    <div class="lucd-field lucd-field-logo">
                        <label><?php esc_html_e( 'Company Logo', 'lucd' ); ?></label>
                        <?php if ( $logo_url ) : ?>
                            <div class="lucd-logo-preview" style="background-image:url(<?php echo esc_url( $logo_url ); ?>);display:block;"></div>
                        <?php else : ?>
                            <?php echo self::get_field_value_markup( 'company_logo', '', array() ); ?>
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
