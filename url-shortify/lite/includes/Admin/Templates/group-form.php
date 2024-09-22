<?php

use KaizenCoders\URL_Shortify\Helper;

$form_data         = Helper::get_data( $template_data, 'form_data', array() );
$title             = Helper::get_data( $template_data, 'title', '' );
$button_text       = Helper::get_data( $template_data, 'button_text', '' );
$form_action       = Helper::get_data( $template_data, 'form_action', '' );

?>

<div class="max-w-full mt-1 font-sans wrap">
	<header class="wp-heading-inline">
		<div class="justify-center md:flex md:items-center md:justify-between">
			<div class="flex-1 min-w-0">
				<h1 class="text-xl leading-7 text-gray-900 sm:text-3xl sm:leading-9 sm:truncate">
					<span class="text-xl font-bold leading-7 text-gray-900 sm:text-xl sm:leading-9 sm:truncate">
						<a href="admin.php?page=us_groups">
							<?php _e( 'Groups ', 'url-shortify' ); ?>
						</a>
					</span>
					<svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" class="inline-block w-4 h-4 align-middle">
						<path d="M9 5l7 7-7 7"></path>
					</svg>
					<?php echo $title; ?>
				</h1>
			</div>
		</div>
	</header>
	<div class="">
		<hr class="wp-header-end">
	</div>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder column-1">
			<div id="post-body-content">
				<div class="bg-white rounded-lg shadow-md meta-box-sortables ui-sortable">
					<form class="flex-row pt-8 mt-2 ml-5 mr-4 text-left item-center" method="post" action="<?php echo $form_action; ?>">
						<div class="flex flex-row border-b border-gray-100">
							<div class="flex w-1/5">
								<div class="pt-6 ml-4">
									<label for="tag-link"><span class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Name', 'url-shortify' ); ?></span></label>
								</div>
							</div>
							<div class="flex w-4/5">
								<div class="w-full h-10 mt-4 mb-4 ml-16 mr-4">
									<div class="relative h-10">
										<input id="" class="block w-2/3 pl-3 pr-12 border-gray-400 shadow-sm form-input  focus:bg-gray-100 sm:text-sm sm:leading-5" placeholder="" name="form_data[name]" value="<?php echo esc_attr( Helper::get_data( $form_data, 'name', '' ) ); ?>" size="30" maxlength="100"/>
									</div>
								</div>
							</div>
						</div>

						<div class="flex flex-row border-b border-gray-100">
							<div class="flex w-1/5">
								<div class="pt-6 ml-4">
									<label for="tag-link"><span class="block pt-1 pb-2 pr-4 ml-4 text-sm font-medium text-gray-600"><?php echo __( 'Description', 'url-shortify' ); ?></span></label>
								</div>
							</div>
							<div class="flex w-4/5 pb-10">
								<div class="w-full h-10 mt-4 mb-4 ml-16 mr-4">
									<div class="relative w-full h-10">
										<textarea id="about" rows="3" class="block w-2/3 transition duration-150 ease-in-out border-gray-400 form-textarea sm:text-sm sm:leading-5" name="form_data[description]"><?php echo esc_attr( Helper::get_data( $form_data, 'description', '' ) ); ?></textarea>
									</div>
								</div>
							</div>
						</div>
						<input type="hidden" name="submitted" value="submitted"/>
						<p class="submit"><input type="submit" name="submit" id="" class="px-4 py-2 ml-6 mr-2 align-middle cursor-pointer kc-us-primary-button" value="<?php echo $button_text; ?>"/><a href="admin.php?page=us_groups" class="px-4 py-2 mx-2 my-2 text-sm font-medium leading-5 align-middle transition duration-150 ease-in-out border border-indigo-600 rounded-md cursor-pointer hover:shadow-md focus:outline-none focus:shadow-outline-indigo">
																																												  <?php 
																																													_e( 'Cancel',
																																													'url-shortify' ); 
																																													?>
									</a></p>
					</form>
				</div>
			</div>
		</div>
	</div>
