<?php
/**
 * "Team Member" custom post type + "Roster" taxonomy.
 *
 * Maps onto ndingi-foundation's Sanity `person` document: name -> post_title,
 * title/role -> ndingi_role meta, photo -> featured image, bio -> post_content,
 * rosters -> ndingi_roster taxonomy terms, order -> native menu_order.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndingi_register_team_member_cpt() {
	register_post_type(
		'team_member',
		array(
			'labels'             => array(
				'name'          => 'Team Members',
				'singular_name' => 'Team Member',
				'add_new_item'  => 'Add New Team Member',
				'edit_item'     => 'Edit Team Member',
				'all_items'     => 'Team Members',
			),
			'public'              => true,
			'has_archive'         => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-groups',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'rewrite'             => false,
		)
	);
}
add_action( 'init', 'ndingi_register_team_member_cpt' );

function ndingi_register_roster_taxonomy() {
	register_taxonomy(
		'ndingi_roster',
		'team_member',
		array(
			'labels'            => array(
				'name'          => 'Rosters',
				'singular_name' => 'Roster',
			),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'ndingi_register_roster_taxonomy' );

// Seed the three fixed rosters so editors pick from a closed list, same as
// the Sanity schema's ROSTERS options list (management / trustees / board).
function ndingi_seed_roster_terms() {
	$rosters = array(
		'management' => 'Management Team',
		'trustees'   => 'Trustees',
		'board'      => 'Board of Management',
	);
	foreach ( $rosters as $slug => $name ) {
		$term = get_term_by( 'slug', $slug, 'ndingi_roster' );
		if ( ! $term ) {
			wp_insert_term( $name, 'ndingi_roster', array( 'slug' => $slug ) );
		} elseif ( $term->name !== $name ) {
			// Keeps a term already seeded under an old label (e.g. "Board of
			// Directors") in sync with a renamed label here, instead of
			// leaving it stale in the wp-admin dropdown forever.
			wp_update_term( $term->term_id, 'ndingi_roster', array( 'name' => $name ) );
		}
	}
}
add_action( 'init', 'ndingi_seed_roster_terms', 20 );

// "Role" meta box (the person's title/position — the CPT title is their name).
function ndingi_team_member_role_metabox() {
	add_meta_box(
		'ndingi_team_member_role',
		'Role',
		'ndingi_render_team_member_role_metabox',
		'team_member',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ndingi_team_member_role_metabox' );

function ndingi_render_team_member_role_metabox( $post ) {
	wp_nonce_field( 'ndingi_save_team_member_role', 'ndingi_team_member_role_nonce' );
	$role = get_post_meta( $post->ID, 'ndingi_role', true );
	?>
	<label for="ndingi_role" class="screen-reader-text">Role</label>
	<input
		type="text"
		id="ndingi_role"
		name="ndingi_role"
		value="<?php echo esc_attr( $role ); ?>"
		placeholder="e.g. Chief Executive Officer"
		style="width:100%;"
	/>
	<p class="description">Their role or position, shown under their name.</p>
	<?php
}

function ndingi_save_team_member_role( $post_id ) {
	if ( ! isset( $_POST['ndingi_team_member_role_nonce'] ) ||
		! wp_verify_nonce( $_POST['ndingi_team_member_role_nonce'], 'ndingi_save_team_member_role' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['ndingi_role'] ) ) {
		update_post_meta( $post_id, 'ndingi_role', sanitize_text_field( wp_unslash( $_POST['ndingi_role'] ) ) );
	}
}
add_action( 'save_post_team_member', 'ndingi_save_team_member_role' );

/**
 * Fetch team members on a given roster, ordered the same way the Sanity
 * query did (`order asc`, i.e. WP's native menu_order).
 *
 * @param string $roster_slug One of: management, trustees, board.
 * @return WP_Post[]
 */
function ndingi_get_roster( $roster_slug ) {
	return get_posts(
		array(
			'post_type'      => 'team_member',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'ndingi_roster',
					'field'    => 'slug',
					'terms'    => $roster_slug,
				),
			),
		)
	);
}
