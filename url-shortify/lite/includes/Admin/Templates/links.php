<?php

use KaizenCoders\URL_Shortify\Helper;

/* @var \KaizenCoders\URL_Shortify\Admin\Links_Table */
$object = Helper::get_data( $template_data, 'object', array() );

$add_new_link = Helper::get_data( $template_data, 'add_new_link', '' );
$export_link_url = Helper::get_data( $template_data, 'export_link', '' );
$import_link_url = Helper::get_action_url(null, 'tools', 'csv');
$title = Helper::get_data( $template_data, 'title', '' );

$current_url = \KaizenCoders\URL_Shortify\Common\Utils::get_current_page_url();

?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<span class="text-2xl font-bold leading-7 text-gray-900 sm:text-2xl sm:leading-9 sm:truncate">
			<?php echo $title; ?>
			<a href="<?php echo $add_new_link; ?>" class="page-title-action">
				<?php _e( 'Add New', 'url-shortify' ); ?>
			</a>
            <a href="<?php echo $import_link_url; ?>" class="page-title-action">
                <?php _e( 'Import Links', 'url-shortify' ); ?>
            </a>
            <?php if ( US()->is_pro() ) { ?>
                <a href="<?php echo $export_link_url; ?>" class="page-title-action">
                    <?php _e( 'Export Links', 'url-shortify' ); ?>
                </a>
            <?php } ?>
		</span>
	</h1>
	<div id="poststuff" class="kc-us-items-lists">
		<div id="post-body" class="metabox-holder column-1">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<form method="get">
                        <input type="hidden" name="page" value="us_links">
						<?php
						$object->prepare_items();
						?>
                    </form>
                    <form method="post">
                        <?php
                        $object->prepare_groups_dropdown();
                        $object->prepare_expiry_datepicker();
						$object->display();
						?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>
</div>
