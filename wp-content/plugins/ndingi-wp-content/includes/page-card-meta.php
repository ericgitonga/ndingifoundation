<?php
/**
 * "Card Settings" meta box for Pages — an icon key + a short blurb, used
 * whenever a page is shown as a tile/card from a parent hub page (About's
 * hub, Resources' hub, Our Work's programme grid, Who We Are's detail
 * grid). Equivalent to the `blurb` fields hardcoded in ndingi's
 * src/lib/aboutInfo.ts and src/lib/programmes.ts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndingi_available_icons() {
	return array(
		''                            => '— None —',
		'who-we-are'                 => 'Who We Are',
		'people'                     => 'People',
		'mission'                    => 'Mission',
		'vision'                     => 'Vision',
		'core-values'                => 'Core Values',
		'education'                  => 'Education',
		'sustainable-livelihoods'    => 'Sustainable Livelihoods',
		'water-ecosystem-management' => 'Water & Ecosystem Management',
		'news'                       => 'News',
		'publications'               => 'Publications',
	);
}

function ndingi_page_card_metabox() {
	add_meta_box(
		'ndingi_page_card',
		'Card Settings (shown when linked from a hub page)',
		'ndingi_render_page_card_metabox',
		'page',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'ndingi_page_card_metabox' );

function ndingi_render_page_card_metabox( $post ) {
	wp_nonce_field( 'ndingi_save_page_card', 'ndingi_page_card_nonce' );
	$icon  = get_post_meta( $post->ID, 'ndingi_card_icon', true );
	$blurb = get_post_meta( $post->ID, 'ndingi_card_blurb', true );
	?>
	<p>
		<label for="ndingi_card_icon">Icon</label><br />
		<select id="ndingi_card_icon" name="ndingi_card_icon" style="width:100%;">
			<?php foreach ( ndingi_available_icons() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="ndingi_card_blurb">Blurb</label><br />
		<textarea id="ndingi_card_blurb" name="ndingi_card_blurb" rows="3" style="width:100%;"><?php echo esc_textarea( $blurb ); ?></textarea>
	</p>
	<?php
}

function ndingi_save_page_card( $post_id ) {
	if ( ! isset( $_POST['ndingi_page_card_nonce'] ) ||
		! wp_verify_nonce( $_POST['ndingi_page_card_nonce'], 'ndingi_save_page_card' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['ndingi_card_icon'] ) ) {
		update_post_meta( $post_id, 'ndingi_card_icon', sanitize_key( $_POST['ndingi_card_icon'] ) );
	}
	if ( isset( $_POST['ndingi_card_blurb'] ) ) {
		update_post_meta( $post_id, 'ndingi_card_blurb', sanitize_textarea_field( wp_unslash( $_POST['ndingi_card_blurb'] ) ) );
	}
}
add_action( 'save_post_page', 'ndingi_save_page_card' );

/**
 * Fetch direct child pages of $parent_id as hub/grid cards, ordered by
 * menu_order (same ordering knob WP already gives Pages natively).
 *
 * @return WP_Post[]
 */
function ndingi_get_child_pages( $parent_id ) {
	return get_pages(
		array(
			'child_of'    => $parent_id,
			'parent'      => $parent_id,
			'sort_column' => 'menu_order',
			'sort_order'  => 'ASC',
		)
	);
}
