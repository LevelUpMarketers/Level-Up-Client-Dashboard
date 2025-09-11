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

// Define plugin constants.
define( 'LUCD_PLUGIN_FILE', __FILE__ );
define( 'LUCD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once LUCD_PLUGIN_DIR . 'includes/class-level-up-client-dashboard.php';

// Initialize the plugin.
register_activation_hook( __FILE__, array( 'Level_Up_Client_Dashboard', 'activate' ) );
Level_Up_Client_Dashboard::init();
