<?php
/**
 * Project management admin functionality.
 *
 * @package Level_Up_Client_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LUC_Project_Admin {
    const MENU_SLUG = 'lucd-project-management';

    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'wp_ajax_lucd_add_project', array( __CLASS__, 'handle_add_project' ) );
        add_action( 'wp_ajax_lucd_get_projects', array( __CLASS__, 'handle_get_projects' ) );
        add_action( 'wp_ajax_lucd_get_project', array( __CLASS__, 'handle_get_project' ) );
        add_action( 'wp_ajax_lucd_update_project', array( __CLASS__, 'handle_update_project' ) );
        add_action( 'wp_ajax_lucd_archive_project', array( __CLASS__, 'handle_archive_project' ) );
        add_action( 'wp_ajax_lucd_delete_project', array( __CLASS__, 'handle_delete_project' ) );
    }

    /**
     * Register the Project Management admin menu.
     */
    public static function register_menu() {
        add_menu_page(
            __( 'Project Management', 'level-up-client-dashboard' ),
            __( 'Project Management', 'level-up-client-dashboard' ),
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_page' ),
            'dashicons-clipboard',
            60.2
        );
    }

    /**
     * Render the Project Management admin page.
     */
    public static function render_page() {
        $tabs = array(
            'add-project'    => array(
                'label'    => __( 'Add a New Project', 'level-up-client-dashboard' ),
                'callback' => array( __CLASS__, 'render_add_project_tab' ),
            ),
            'manage-project' => array(
                'label'    => __( 'Manage Projects', 'level-up-client-dashboard' ),
                'callback' => array( __CLASS__, 'render_manage_projects_tab' ),
            ),
        );

        Level_Up_Client_Dashboard_Admin::render_management_page( __( 'Project Management', 'level-up-client-dashboard' ), self::MENU_SLUG, $tabs );
    }

    /**
     * Definitions for project fields.
     *
     * @return array
     */
    private static function get_project_fields() {
        return array(
            'project_name'  => array( 'label' => __( 'Project Name', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'project_client'=> array( 'label' => __( 'Client', 'level-up-client-dashboard' ), 'type' => 'text', 'class' => 'lucd-project-client' ),
            'client_id'     => array( 'type' => 'hidden' ),
            'start_date'    => array( 'label' => __( 'Start Date', 'level-up-client-dashboard' ), 'type' => 'date' ),
            'end_date'      => array( 'label' => __( 'End Date', 'level-up-client-dashboard' ), 'type' => 'date' ),
            'status'        => array( 'label' => __( 'Status', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'dev_link'      => array( 'label' => __( 'Dev Link', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'live_link'     => array( 'label' => __( 'Live Link', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'gdrive_link'   => array( 'label' => __( 'Google Drive Link', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'project_type'  => array( 'label' => __( 'Project Type', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'total_cost'    => array( 'label' => __( 'Total Cost', 'level-up-client-dashboard' ), 'type' => 'number', 'step' => '0.01' ),
            'description'   => array( 'label' => __( 'Description', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
            'project_updates'=> array( 'label' => __( 'Project Updates', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
        );
    }

    /**
     * Render project fields.
     *
     * @param array $project Project data.
     */
    public static function render_project_fields( $project = array() ) {
        foreach ( self::get_project_fields() as $field => $data ) {
            $value = isset( $project[ $field ] ) ? $project[ $field ] : '';
            $class = isset( $data['class'] ) ? ' ' . $data['class'] : '';

            if ( 'hidden' === $data['type'] ) {
                printf(
                    '<input type="hidden" id="%1$s" name="%1$s" value="%2$s" class="lucd-project-client-id" />',
                    esc_attr( $field ),
                    esc_attr( $value )
                );
                continue;
            }

            echo '<div class="lucd-field">';
            if ( isset( $data['label'] ) ) {
                echo '<label for="' . esc_attr( $field ) . '">' . esc_html( $data['label'] ) . '</label>';
            }
            if ( 'textarea' === $data['type'] ) {
                printf( '<textarea id="%1$s" name="%1$s"%3$s>%2$s</textarea>', esc_attr( $field ), esc_textarea( $value ), $class ? ' class="' . esc_attr( trim( $class ) ) . '"' : '' );
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
     * Handle AJAX request to add a project.
     */
    public static function handle_add_project() {
        check_ajax_referer( 'lucd_add_project', 'lucd_project_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $fields = self::get_project_fields();
        $data   = array();
        foreach ( $fields as $field => $info ) {
            if ( 'project_client' === $field ) {
                continue;
            }
            $data[ $field ] = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
        }

        if ( empty( $data['client_id'] ) ) {
            wp_send_json_error( __( 'Please select an existing client.', 'level-up-client-dashboard' ) );
        }

        $table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_table() );
        $format = array_fill( 0, count( $data ), '%s' );
        global $wpdb;
        $inserted = $wpdb->insert( $table, $data, $format );

        if ( ! $inserted ) {
            wp_send_json_error( __( 'Failed to add project.', 'level-up-client-dashboard' ) );
        }

        wp_send_json_success( __( 'Project added successfully.', 'level-up-client-dashboard' ) );
    }

    /**
     * Handle AJAX request to get projects for a client.
     */
    public static function handle_get_projects() {
        check_ajax_referer( 'lucd_get_projects', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $client_id = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
        if ( ! $client_id ) {
            wp_send_json_error( __( 'Invalid client ID.', 'level-up-client-dashboard' ) );
        }

        $table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_table() );
        global $wpdb;
        $projects = $wpdb->get_results( $wpdb->prepare( "SELECT project_id, project_name FROM $table WHERE client_id = %d", $client_id ) );

        if ( empty( $projects ) ) {
            wp_send_json_error( __( 'No projects found.', 'level-up-client-dashboard' ) );
        }

        ob_start();
        foreach ( $projects as $project ) {
            echo '<div class="lucd-accordion">';
            echo '<h3 class="lucd-accordion-header" data-action="lucd_get_project" data-nonce="getProjectNonce" data-project-id="' . esc_attr( $project->project_id ) . '">' . esc_html( $project->project_name ) . '</h3>';
            echo '<div class="lucd-accordion-content"></div>';
            echo '</div>';
        }
        wp_send_json_success( ob_get_clean() );
    }

    /**
     * Handle AJAX request to get a project.
     */
    public static function handle_get_project() {
        check_ajax_referer( 'lucd_get_project', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
        if ( ! $project_id ) {
            wp_send_json_error( __( 'Invalid project ID.', 'level-up-client-dashboard' ) );
        }

        $table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_table() );
        global $wpdb;
        $project = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE project_id = %d", $project_id ), ARRAY_A );

        if ( ! $project ) {
            wp_send_json_error( __( 'Project not found.', 'level-up-client-dashboard' ) );
        }

        $project['project_client'] = LUC_Client_Admin::get_client_label( $project['client_id'] );

        ob_start();
        echo '<form class="lucd-edit-project-form">';
        self::render_project_fields( $project );
        echo '<input type="hidden" name="action" value="lucd_update_project" />';
        echo '<input type="hidden" name="project_id" value="' . esc_attr( $project_id ) . '" />';
        wp_nonce_field( 'lucd_update_project', 'lucd_update_project_nonce' );
        wp_nonce_field( 'lucd_archive_project', 'lucd_archive_project_nonce' );
        wp_nonce_field( 'lucd_delete_project', 'lucd_delete_project_nonce' );
        echo '<p>';
        echo '<button type="submit" class="button button-primary">' . esc_html__( 'Update Project', 'level-up-client-dashboard' ) . '</button> ';
        echo '<button type="button" class="button lucd-archive-project">' . esc_html__( 'Archive Project', 'level-up-client-dashboard' ) . '</button> ';
        echo '<button type="button" class="button lucd-delete-project">' . esc_html__( 'Delete Project', 'level-up-client-dashboard' ) . '</button>';
        echo '</p>';
        echo '</form>';
        echo '<div class="lucd-feedback"><span class="spinner"></span><p></p></div>';
        wp_send_json_success( ob_get_clean() );
    }

    /**
     * Handle AJAX request to update a project.
     */
    public static function handle_update_project() {
        check_ajax_referer( 'lucd_update_project', 'lucd_update_project_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
        if ( ! $project_id ) {
            wp_send_json_error( __( 'Invalid project ID.', 'level-up-client-dashboard' ) );
        }

        $fields = self::get_project_fields();
        $data   = array();
        foreach ( $fields as $field => $info ) {
            if ( 'project_client' === $field ) {
                continue;
            }
            $data[ $field ] = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
        }

        if ( empty( $data['client_id'] ) ) {
            wp_send_json_error( __( 'Please select an existing client.', 'level-up-client-dashboard' ) );
        }

        $table   = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_table() );
        $formats = array_fill( 0, count( $data ), '%s' );
        global $wpdb;
        $updated = $wpdb->update( $table, $data, array( 'project_id' => $project_id ), $formats, array( '%d' ) );

        if ( false === $updated ) {
            wp_send_json_error( __( 'Failed to update project.', 'level-up-client-dashboard' ) );
        }

        wp_send_json_success( __( 'Project updated successfully.', 'level-up-client-dashboard' ) );
    }

    /**
     * Handle AJAX request to archive a project.
     */
    public static function handle_archive_project() {
        check_ajax_referer( 'lucd_archive_project', 'lucd_archive_project_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
        if ( ! $project_id ) {
            wp_send_json_error( __( 'Invalid project ID.', 'level-up-client-dashboard' ) );
        }

        global $wpdb;
        $active  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_table() );
        $archive = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_archive_table() );
        $wpdb->query( $wpdb->prepare( "INSERT INTO $archive SELECT * FROM $active WHERE project_id = %d", $project_id ) );
        $wpdb->delete( $active, array( 'project_id' => $project_id ), array( '%d' ) );

        // TODO: Update to handle additional custom tables that reference project_id.
        wp_send_json_success( __( 'Project archived successfully.', 'level-up-client-dashboard' ) );
    }

    /**
     * Handle AJAX request to delete a project.
     */
    public static function handle_delete_project() {
        check_ajax_referer( 'lucd_delete_project', 'lucd_delete_project_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'level-up-client-dashboard' ) );
        }

        $project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
        if ( ! $project_id ) {
            wp_send_json_error( __( 'Invalid project ID.', 'level-up-client-dashboard' ) );
        }

        global $wpdb;
        $active  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_table() );
        $archive = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_archive_table() );
        $wpdb->delete( $active, array( 'project_id' => $project_id ), array( '%d' ) );
        $wpdb->delete( $archive, array( 'project_id' => $project_id ), array( '%d' ) );

        // TODO: Update to handle additional custom tables that reference project_id.
        wp_send_json_success( __( 'Project deleted successfully.', 'level-up-client-dashboard' ) );
    }

    /**
     * Render the Add Project tab.
     */
    public static function render_add_project_tab() {
        include LUCD_PLUGIN_DIR . 'admin/views/project-add.php';
    }

    /**
     * Render the Manage Projects tab.
     */
    public static function render_manage_projects_tab() {
        include LUCD_PLUGIN_DIR . 'admin/views/project-manage.php';
    }
}
