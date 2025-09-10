<?php
/**
 * View: Add Support Ticket form.
 *
 * @package Level_Up_Client_Dashboard
 */
?>
<form id="lucd-add-ticket-form">
    <?php LUC_Support_Admin::render_ticket_fields(); ?>
    <input type="hidden" name="action" value="lucd_add_ticket" />
    <?php wp_nonce_field( 'lucd_add_ticket', 'lucd_ticket_nonce' ); ?>
    <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Add Ticket', 'level-up-client-dashboard' ); ?></button></p>
</form>
<div id="lucd-ticket-feedback" class="lucd-feedback"><span class="spinner"></span><p></p></div>
