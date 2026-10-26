<?php
/**
 * "Core Value" custom post type — a repeatable name+description card, used
 * on /core-values and inside the "Who We Are" detail grid modal. The
 * Next.js site hardcoded these five as a TS array; a CPT lets an editor
 * add, edit, or reorder them without a code change.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndingi_register_core_value_cpt() {
	register_post_type(
		'core_value',
		array(
			'labels'              => array(
				'name'          => 'Core Values',
				'singular_name' => 'Core Value',
				'add_new_item'  => 'Add New Core Value',
				'edit_item'     => 'Edit Core Value',
				'all_items'     => 'Core Values',
			),
			'public'              => true,
			'has_archive'         => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-star-filled',
			'supports'            => array( 'title', 'excerpt', 'page-attributes' ),
			'rewrite'             => false,
		)
	);
}
add_action( 'init', 'ndingi_register_core_value_cpt' );

/**
 * @return WP_Post[] Core values ordered the same way the Sanity-era array
 * was written (Empowerment, Sustainability, Stewardship, Service, Integrity),
 * seeded on activation, but re-orderable from wp-admin via menu_order.
 */
function ndingi_get_core_values() {
	return get_posts(
		array(
			'post_type'      => 'core_value',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);
}
