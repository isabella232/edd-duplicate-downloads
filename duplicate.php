<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function edd_duplicate_product() {

	// Get the original product
	$id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
	if ( empty( $id ) ) {
		$id = filter_input( INPUT_POST, 'post', FILTER_SANITIZE_NUMBER_INT );
	}

	if ( empty( $id ) || ( isset( $_REQUEST['action'] ) && 'duplicate_post_save_as_new_page' === $_REQUEST['action'] ) ) {
		wp_die( __( 'No product to duplicate has been supplied!', 'edd' ) );
	}

	check_admin_referer( 'edd-duplicate-product_' . $id );
	$post = edd_get_product_to_duplicate( $id );

	// Copy the product
	if ( null !== $post ) {
		$new_id = edd_create_duplicate_from_product( $post );

		do_action( 'edd_duplicate_product', $new_id, $post );

		// Redirect to the edit screen for the new draft page
		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
		exit;
	} else {
		wp_die( __( 'Product creation failed, could not find original product:', 'edd' ) . ' ' . $id );
	}
}

/**
 * Gets a product from the database.
 *
 * @param int|string $id The ID of the download to duplicate.
 * @return object|null Returns an object if a download is found; otherwise null.
 */
function edd_get_product_to_duplicate( $id ) {
	global $wpdb;

	$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $id ) );
	if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
		$id   = $post->post_parent;
		$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $id ) );
	}

	return $post;
}

/**
 * Function to create the duplicate
 */
function edd_create_duplicate_from_product( $post, $parent = 0, $post_status = '' ) {
	global $wpdb;

	$new_post_author   = wp_get_current_user();
	$new_post_date     = current_time( 'mysql' );
	$new_post_date_gmt = get_gmt_from_date( $new_post_date );

	if ( $parent > 0 ) {
		$post_parent = $parent;
		$post_status = $post_status ? $post_status : 'publish';
		$suffix      = '';
	} else {
		$post_parent = $post->post_parent;
		$post_status = $post_status ? $post_status : 'draft';
		$suffix      = ' ' . __( "(Copy)", 'edd' );
	}

	$new_post_type         = $post->post_type;
	$post_content          = str_replace( "'", "''", $post->post_content );
	$post_content_filtered = str_replace( "'", "''", $post->post_content_filtered );
	$post_excerpt          = str_replace( "'", "''", $post->post_excerpt );
	$post_title            = str_replace( "'", "''", $post->post_title ) . $suffix;
	$post_name             = str_replace( "'", "''", $post->post_name );
	$comment_status        = str_replace( "'", "''", $post->comment_status );
	$ping_status           = str_replace( "'", "''", $post->ping_status );

	// Insert the new template in the post table
	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$wpdb->posts}
			(post_author, post_date, post_date_gmt, post_content, post_content_filtered, post_title, post_excerpt,  post_status, post_type, comment_status, ping_status, post_password, to_ping, pinged, post_modified, post_modified_gmt, post_parent, menu_order, post_mime_type)
			VALUES
			(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			$new_post_author->ID,
			$new_post_date,
			$new_post_date_gmt,
			$post_content,
			$post_content_filtered,
			$post_title,
			$post_excerpt,
			$post_status,
			$new_post_type,
			$comment_status,
			$ping_status,
			$post->post_password,
			$post->to_ping,
			$post->pinged,
			$new_post_date,
			$new_post_date_gmt,
			$post_parent,
			$post->menu_order,
			$post->post_mime_type
		)
	);

	$new_post_id = $wpdb->insert_id;

	// Copy the taxonomies
	edd_duplicate_post_taxonomies( $post->ID, $new_post_id, $post->post_type );

	// Copy the meta information
	edd_duplicate_post_meta( $post->ID, $new_post_id );

	// Clear Sales Data
	update_post_meta( $new_post_id, '_edd_download_earnings', '0.00' );
	update_post_meta( $new_post_id, '_edd_download_sales', '0' );

	return $new_post_id;
}

/**
 * Copy the taxonomies of a Product to another Product
 */
function edd_duplicate_post_taxonomies( $id, $new_id, $post_type ) {
	$taxonomies = get_object_taxonomies( $post_type ); //array("category", "post_tag");
	foreach ($taxonomies as $taxonomy) {

		$post_terms       = wp_get_object_terms( $id, $taxonomy );
		$post_terms_count = count( $post_terms );

		for ( $i=0; $i<$post_terms_count; $i++ ) {
			wp_set_object_terms( $new_id, $post_terms[ $i ]->slug, $taxonomy, true );
		}

	}
}

/**
 * Copy the meta information of a Product to another Product
 *
 * @param int $id     The original download ID.
 * @param int $new_id The new download ID.
 */
function edd_duplicate_post_meta( $id, $new_id ) {
	global $wpdb;
	$post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $id ) );

	if ( count( $post_meta_infos ) ) {
		$sql_query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) ";
		foreach ( $post_meta_infos as $meta_info ) {
			$meta_key        = $meta_info->meta_key;
			$meta_value      = addslashes( $meta_info->meta_value );
			$sql_query_sel[] = "SELECT {$new_id}, {$meta_key}, {$meta_value}";
		}
		$sql_query .= implode( " UNION ALL ", $sql_query_sel );
		$wpdb->query( $sql_query );
	}
}
