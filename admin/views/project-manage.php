<?php
/**
 * View: Manage Projects.
 *
 * @package Level_Up_Client_Dashboard
 */

global $wpdb;
$clients_table  = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::clients_table() );
$projects_table = Level_Up_Client_Dashboard::get_table_name( Level_Up_Client_Dashboard::projects_table() );
$clients = $wpdb->get_results( "SELECT DISTINCT c.client_id, c.company_name, c.first_name, c.last_name FROM $clients_table c INNER JOIN $projects_table p ON c.client_id = p.client_id ORDER BY c.company_name ASC, c.last_name ASC" );

if ( empty( $clients ) ) {
    echo '<p>' . esc_html__( 'No clients found.', 'level-up-client-dashboard' ) . '</p>';
    return;
}

echo '<div id="lucd-manage-projects">';
foreach ( $clients as $client ) {
    $name    = trim( $client->first_name . ' ' . $client->last_name );
    $company = trim( $client->company_name );
    $display = $company ? $company . ' - ' . $name : $name;
    echo '<div class="lucd-accordion">';
    echo '<h3 class="lucd-accordion-header" data-action="lucd_get_projects" data-nonce="getProjectsNonce" data-client-id="' . esc_attr( $client->client_id ) . '">' . esc_html( $display ) . '</h3>';
    echo '<div class="lucd-accordion-content"></div>';
    echo '</div>';
}
echo '</div>';
