<?php
/**
 * View: Add Client form.
 *
 * @package Level_Up_Client_Dashboard
 */
?>
<form id="lucd-add-client-form">
    <?php LUC_Client_Admin::render_client_fields(); ?>
    <input type="hidden" name="action" value="lucd_add_client" />
    <?php wp_nonce_field( 'lucd_add_client', 'lucd_nonce' ); ?>
    <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Add Client', 'level-up-client-dashboard' ); ?></button></p>
</form>
<div id="lucd-feedback" class="lucd-feedback"><span class="spinner"></span><p></p></div>
