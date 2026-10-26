<?php
/**
 * "Partner" custom post type + "Partner Category" taxonomy.
 *
 * Allows editors to add, edit, and remove professional advisors (Bankers,
 * Auditors, Tax Consultants, Lawyers) and programme/donor partners without
 * editing raw JSON.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndingi_register_partner_cpt() {
	register_post_type(
		'partner',
		array(
			'labels'              => array(
				'name'          => 'Partners',
				'singular_name' => 'Partner',
				'add_new_item'  => 'Add New Partner',
				'edit_item'     => 'Edit Partner',
				'all_items'     => 'Partners',
				'menu_name'     => 'Partners',
			),
			'public'              => true,
			'has_archive'         => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-networking',
			'supports'            => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
			'rewrite'             => false,
		)
	);
}
add_action( 'init', 'ndingi_register_partner_cpt' );

function ndingi_register_partner_category_taxonomy() {
	register_taxonomy(
		'partner_category',
		'partner',
		array(
			'labels'            => array(
				'name'          => 'Partner Categories',
				'singular_name' => 'Partner Category',
				'all_items'     => 'All Categories',
				'edit_item'     => 'Edit Category',
				'update_item'   => 'Update Category',
				'add_new_item'  => 'Add New Category',
				'menu_name'     => 'Categories',
			),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'ndingi_register_partner_category_taxonomy' );

function ndingi_seed_partner_category_terms() {
	$terms = array(
		'bankers'                  => 'Bankers',
		'auditors'                 => 'Auditors',
		'tax-consultants'          => 'Tax Consultants',
		'lawyers'                  => 'Lawyers',
		'programme-donor-partners' => 'Programme & Donor Partners',
	);
	foreach ( $terms as $slug => $name ) {
		if ( ! term_exists( $slug, 'partner_category' ) ) {
			wp_insert_term( $name, 'partner_category', array( 'slug' => $slug ) );
		}
	}
}
add_action( 'init', 'ndingi_seed_partner_category_terms', 20 );

function ndingi_partner_details_metabox() {
	add_meta_box(
		'ndingi_partner_details',
		'Partner / Advisor Details',
		'ndingi_render_partner_details_metabox',
		'partner',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ndingi_partner_details_metabox' );

function ndingi_render_partner_details_metabox( $post ) {
	wp_nonce_field( 'ndingi_save_partner_meta', 'ndingi_partner_nonce' );

	$address_details = get_post_meta( $post->ID, 'ndingi_partner_details', true );
	$url             = get_post_meta( $post->ID, 'ndingi_partner_url', true );
	$url_label       = get_post_meta( $post->ID, 'ndingi_partner_url_label', true );
	?>
	<p>
		<label for="ndingi_partner_details"><strong>Address / Detail Lines</strong></label><br />
		<textarea id="ndingi_partner_details" name="ndingi_partner_details" rows="3" style="width:100%;" placeholder="e.g. Certified Public Accountants (Kenya)&#10;P.O. Box 2309-00606, Sarit Centre, Nairobi"><?php echo esc_textarea( $address_details ); ?></textarea>
		<span class="description">One detail/address item per line. If left blank, the post excerpt will be used.</span>
	</p>
	<p>
		<label for="ndingi_partner_url"><strong>Website URL</strong></label><br />
		<input type="url" id="ndingi_partner_url" name="ndingi_partner_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://example.com" style="width:100%;" />
	</p>
	<p>
		<label for="ndingi_partner_url_label"><strong>Website Link Label (Optional)</strong></label><br />
		<input type="text" id="ndingi_partner_url_label" name="ndingi_partner_url_label" value="<?php echo esc_attr( $url_label ); ?>" placeholder="e.g. www.example.com or Visit Website" style="width:100%;" />
	</p>
	<?php
}

function ndingi_save_partner_meta( $post_id ) {
	if ( ! isset( $_POST['ndingi_partner_nonce'] ) || ! wp_verify_nonce( $_POST['ndingi_partner_nonce'], 'ndingi_save_partner_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['ndingi_partner_details'] ) ) {
		update_post_meta( $post_id, 'ndingi_partner_details', sanitize_textarea_field( wp_unslash( $_POST['ndingi_partner_details'] ) ) );
	}
	if ( isset( $_POST['ndingi_partner_url'] ) ) {
		update_post_meta( $post_id, 'ndingi_partner_url', esc_url_raw( wp_unslash( $_POST['ndingi_partner_url'] ) ) );
	}
	if ( isset( $_POST['ndingi_partner_url_label'] ) ) {
		update_post_meta( $post_id, 'ndingi_partner_url_label', sanitize_text_field( wp_unslash( $_POST['ndingi_partner_url_label'] ) ) );
	}
}
add_action( 'save_post_partner', 'ndingi_save_partner_meta' );

function ndingi_get_professional_advisors( $post_id = 0 ) {
	$posts = get_posts(
		array(
			'post_type'      => 'partner',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	if ( empty( $posts ) ) {
		return function_exists( 'ndingi_default_professional_advisors' ) ? ndingi_default_professional_advisors() : array();
	}

	$advisor_categories = array( 'Bankers', 'Auditors', 'Tax Consultants', 'Lawyers' );
	$grouped = array();

	foreach ( $posts as $p ) {
		$terms = wp_get_post_terms( $p->ID, 'partner_category', array( 'fields' => 'names' ) );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			continue;
		}

		if ( in_array( 'Programme & Donor Partners', $terms, true ) ) {
			continue;
		}

		$category_name = $terms[0];

		$details_str = get_post_meta( $p->ID, 'ndingi_partner_details', true );
		if ( ! empty( $details_str ) ) {
			$details = array_filter( array_map( 'trim', explode( "\n", $details_str ) ) );
		} elseif ( ! empty( $p->post_excerpt ) ) {
			$details = array_filter( array_map( 'trim', explode( "\n", $p->post_excerpt ) ) );
		} else {
			$details = array();
		}

		$url       = get_post_meta( $p->ID, 'ndingi_partner_url', true );
		$url_label = get_post_meta( $p->ID, 'ndingi_partner_url_label', true ) ?: $url;

		$advisor_item = array(
			'name'   => $p->post_title,
			'detail' => $details,
		);
		if ( ! empty( $url ) ) {
			$advisor_item['link'] = array(
				'href'  => $url,
				'label' => $url_label,
			);
		}

		if ( ! isset( $grouped[ $category_name ] ) ) {
			$grouped[ $category_name ] = array(
				'category' => $category_name,
				'advisors' => array(),
			);
		}
		$grouped[ $category_name ]['advisors'][] = $advisor_item;
	}

	if ( empty( $grouped ) ) {
		return function_exists( 'ndingi_default_professional_advisors' ) ? ndingi_default_professional_advisors() : array();
	}

	$sorted = array();
	foreach ( $advisor_categories as $cat ) {
		if ( isset( $grouped[ $cat ] ) ) {
			$sorted[] = $grouped[ $cat ];
			unset( $grouped[ $cat ] );
		}
	}
	foreach ( $grouped as $group ) {
		$sorted[] = $group;
	}

	return $sorted;
}

function ndingi_get_donor_partners( $post_id = 0 ) {
	$posts = get_posts(
		array(
			'post_type'      => 'partner',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'partner_category',
					'field'    => 'slug',
					'terms'    => 'programme-donor-partners',
				),
			),
		)
	);

	$results = array();
	foreach ( $posts as $p ) {
		$url       = get_post_meta( $p->ID, 'ndingi_partner_url', true );
		$url_label = get_post_meta( $p->ID, 'ndingi_partner_url_label', true ) ?: 'Visit Website';
		$logo_url  = get_the_post_thumbnail_url( $p->ID, 'medium' ) ?: '';
		$details   = get_post_meta( $p->ID, 'ndingi_partner_details', true ) ?: $p->post_excerpt;

		$results[] = array(
			'name'       => $p->post_title,
			'logo_url'   => $logo_url,
			'link'       => $url,
			'link_label' => $url_label,
			'blurb'      => $details,
		);
	}

	return $results;
}
