<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Duplicate a product link on products list
 */
function edd_duplicate_product_link_row($actions, $post) {
	if (!($post->post_type=='download')) return $actions;

	$actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'admin.php?action=duplicate_product&amp;post=' . $post->ID ), 'edd-duplicate-product_' . $post->ID ) . '" title="' . __("Make a duplicate from this product", 'edd-duplicate-downloads')
		. '" rel="permalink">' .  __("Duplicate", 'edd-duplicate-downloads') . '</a>';

	return $actions;
}
add_filter('post_row_actions', 'edd_duplicate_product_link_row',10,2);
add_filter('page_row_actions', 'edd_duplicate_product_link_row',10,2);

/**
 *  Duplicate a product link on edit screen
 */
function edd_duplicate_product_post_button() {
	global $post;

	if (function_exists('duplicate_post_plugin_activation')) return;

	if( !is_object( $post ) ) return;

	if ($post->post_type!='download') return;

	if ( isset( $_GET['post'] ) ) :
		$notifyUrl = wp_nonce_url( admin_url( "admin.php?action=duplicate_product&post=" . $_GET['post'] ), 'edd-duplicate-product_' . $_GET['post'] );
		?>
		<div id="duplicate-action"><a class="submitduplicate duplication" href="<?php echo esc_url( $notifyUrl ); ?>"><?php _e('Duplicate Me!', 'edd-duplicate-downloads'); ?></a></div>
		<?php
	endif;
}
add_action( 'post_submitbox_start', 'edd_duplicate_product_post_button' );

function edd_duplicate_product_action() {
	require_once(dirname(__FILE__).'/duplicate.php');
	edd_duplicate_product();
}
add_action('admin_action_duplicate_product', 'edd_duplicate_product_action');
