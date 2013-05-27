<?php
if(!is_admin())
return;

/**
 * Duplicate a product link on products list
 */
add_filter('post_row_actions', 'edd_duplicate_download_link_row',10,2);
add_filter('page_row_actions', 'edd_duplicate_download_link_row',10,2);

function edd_duplicate_download_link_row($actions, $post) {

	if (!($post->post_type=='download')) return $actions;

	$actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'admin.php?action=duplicate_download&amp;post=' . $post->ID ), 'edd-duplicate-download_' . $post->ID ) . '" title="' . __("Make a duplicate from this product", 'edd')
		. '" rel="permalink">' .  __("Duplicate", 'edd') . '</a>';

	return $actions;
}

/**
 *  Duplicate a product link on edit screen
 */
add_action( 'post_submitbox_start', 'edd_duplicate_download_post_button' );

function edd_duplicate_download_post_button() {
	global $post;
	
	if (function_exists('duplicate_post_plugin_activation')) return;

	if( !is_object( $post ) ) return;

	if ($post->post_type!='download') return;

	if ( isset( $_GET['post'] ) ) :
		$notifyUrl = wp_nonce_url( admin_url( "admin.php?action=duplicate_download&post=" . $_GET['post'] ), 'edd-duplicate-download_' . $_GET['post'] );
		?>
		<div id="duplicate-action"><a class="submitduplicate duplication" href="<?php echo esc_url( $notifyUrl ); ?>"><?php _e('Duplicate Me!', 'edd'); ?></a></div>
		<?php
	endif;
}
add_action('admin_action_duplicate_download', 'edd_duplicate_download_action');
function edd_duplicate_download_action() {
	require_once(dirname(__FILE__).'/duplicate.php');
	edd_duplicate_download();
}
?>