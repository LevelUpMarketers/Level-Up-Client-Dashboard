<?php
/**
 * View: Add Project or Service form.
 *
 * @package Level_Up_Client_Dashboard
 */
?>
<form id="lucd-add-project-form">
    <?php LUC_Project_Admin::render_project_fields(); ?>
    <input type="hidden" name="action" value="lucd_add_project" />
    <?php wp_nonce_field( 'lucd_add_project', 'lucd_project_nonce' ); ?>
    <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Add Project or Service', 'level-up-client-dashboard' ); ?></button></p>
</form>
<div id="lucd-project-feedback" class="lucd-feedback"><span class="spinner"></span><p></p></div>
