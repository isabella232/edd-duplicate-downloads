<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Duplicate a product link on products list
 */
function edd_duplicate_product_link_row( $actions, $post ) {
	if ( 'download' !== $post->post_type ) {
		return $actions;
	}

	$actions['duplicate'] = sprintf(
		'<a href="%s" rel="permalink">%s</a>',
		esc_url( edd_duplicate_product_get_duplicate_url( $post->ID ) ),
		__( 'Duplicate', 'edd' )
	);

	return $actions;
}
add_filter( 'post_row_actions', 'edd_duplicate_product_link_row', 10, 2 );
add_filter( 'page_row_actions', 'edd_duplicate_product_link_row', 10, 2 );

/**
 *  Duplicate a product link on edit screen
 */
function edd_duplicate_product_post_button() {
	global $post;

	if ( function_exists( 'duplicate_post_plugin_activation' ) ) {
		return;
	}

	if ( ! is_object( $post ) ) {
		return;
	}

	if ( 'download' !== $post->post_type ) {
		return;
	}

	?>
	<div id="duplicate-action"><a class="submitduplicate duplication" href="<?php echo esc_url( edd_duplicate_product_get_duplicate_url( $post->ID ) ); ?>"><?php esc_html_e( 'Duplicate Me!', 'edd' ); ?></a></div>
	<?php
}
add_action( 'post_submitbox_start', 'edd_duplicate_product_post_button' );

/**
 * Includes the duplicate.php file and runs edd_duplicate_product.
 *
 * @deprecated 1.0.2 because the require_once is not needed.
 * @return void
 */
function edd_duplicate_product_action() {
	_deprecated_function( __FUNCTION__, '1.0.2', 'edd_duplicate_product' );
	require_once dirname( __FILE__ ) . '/duplicate.php';
	edd_duplicate_product();
}
add_action( 'admin_action_duplicate_product', 'edd_duplicate_product' );

/**
 * Gets the URL to duplicate a download.
 *
 * @param int $post_id
 * @return string
 */
function edd_duplicate_product_get_duplicate_url( $post_id ) {

	$post_id = (int) $post_id;

	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'duplicate_product',
				'post'   => urlencode( $post_id ),
			),
			admin_url( 'admin.php' )
		),
		"edd-duplicate-product_{$post_id}"
	);
}
