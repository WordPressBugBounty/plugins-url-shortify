<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

?>

<div class="wrap">
    <!-- Success/ Error Message -->
    <div id="kc-us-error-message" style="display: none; background-color: red; color: white;"></div>


    <!-- Link Form-->
    <div id="kc-us-create-link-form">

        <form id="kc-us-create-link" method="post">
            <table class="form-table">
                <tr class="form-field">
                    <td valign="top"><?php esc_html_e( 'Target URL', 'url-shortify' ); ?></td>
                    <td><input type="text" id="kc-us-target-url" name="url" value="">
                </tr>
                <tr>
                    <td valign="top"><?php esc_html_e( 'Short URL', 'url-shortify' ); ?></td>
                    <td> <select class="relative border border-gray-400 shadow-sm form-select" name="domain" id="kc-us-domain">
		                    <?php echo \KaizenCoders\URL_Shortify\Helper::prepare_domains_dropdown_options($default_domain); ?>
                        </select><input type="text" id="kc-us-slug" name="slug" value="<?php echo esc_attr( $slug ); ?>">
                </tr>
            </table>

            <span id="kc-us-dashboard-short-link">
                <input type="hidden" name="us_security" id="kc-us-security" value="<?php echo $action; ?>">
		<input type="submit" name="Submit" value="<?php esc_attr_e( 'Create', 'url-shortify' ); ?>" class="button button-primary" id=""/>
		<p class="kc_us_loading" style="display: none;"><img height="24px" width="24px" src="<?php echo $loading_icon_url; ?>"></p>
		</span>
        </form>
        <div id="kc-us-success-message" style="display: none; margin-top: 10px;"></div>
    </div>
</div>
