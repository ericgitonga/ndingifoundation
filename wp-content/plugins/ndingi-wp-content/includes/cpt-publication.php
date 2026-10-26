<?php
/**
 * "Publication" custom post type — maps onto ndingi-foundation's Sanity
 * `resource` document. Title -> post_title, description -> post_excerpt,
 * date added -> native post date, category -> a simple taxonomy,
 * linkType/file/externalUrl -> a "Link" meta box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndingi_register_publication_cpt() {
	register_post_type(
		'publication',
		array(
			'labels'              => array(
				'name'          => 'Publications',
				'singular_name' => 'Publication',
				'add_new_item'  => 'Add New Publication',
				'edit_item'     => 'Edit Publication',
				'all_items'     => 'Publications',
			),
			'public'              => true,
			'has_archive'         => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-media-document',
			'supports'            => array( 'title', 'excerpt' ),
			'rewrite'             => false,
		)
	);
}
add_action( 'init', 'ndingi_register_publication_cpt' );

function ndingi_register_publication_category_taxonomy() {
	register_taxonomy(
		'publication_category',
		'publication',
		array(
			'labels'            => array(
				'name'          => 'Categories',
				'singular_name' => 'Category',
			),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'ndingi_register_publication_category_taxonomy' );

function ndingi_publication_link_metabox() {
	add_meta_box(
		'ndingi_publication_link',
		'Link',
		'ndingi_render_publication_link_metabox',
		'publication',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ndingi_publication_link_metabox' );

function ndingi_render_publication_link_metabox( $post ) {
	wp_nonce_field( 'ndingi_save_publication_link', 'ndingi_publication_link_nonce' );
	$link_type    = get_post_meta( $post->ID, 'ndingi_link_type', true ) ?: 'file';
	$file_id      = get_post_meta( $post->ID, 'ndingi_file_id', true );
	$external_url = get_post_meta( $post->ID, 'ndingi_external_url', true );
	$file_url     = $file_id ? wp_get_attachment_url( $file_id ) : '';
	?>
	<p>
		<label>
			<input type="radio" name="ndingi_link_type" value="file" <?php checked( $link_type, 'file' ); ?> />
			Uploaded file
		</label>
		&nbsp;&nbsp;
		<label>
			<input type="radio" name="ndingi_link_type" value="external" <?php checked( $link_type, 'external' ); ?> />
			External link
		</label>
	</p>

	<p>
		<button type="button" class="button" id="ndingi_upload_file_button">
			<?php echo $file_id ? 'Replace file' : 'Choose file'; ?>
		</button>
		<span id="ndingi_file_name"><?php echo esc_html( $file_url ? basename( $file_url ) : 'No file chosen' ); ?></span>
		<input type="hidden" name="ndingi_file_id" id="ndingi_file_id" value="<?php echo esc_attr( $file_id ); ?>" />
	</p>

	<p>
		<label for="ndingi_external_url">External URL</label><br />
		<input
			type="url"
			id="ndingi_external_url"
			name="ndingi_external_url"
			value="<?php echo esc_attr( $external_url ); ?>"
			placeholder="https://…"
			style="width:100%;"
		/>
	</p>

	<script>
	jQuery(function ($) {
		var button = document.getElementById('ndingi_upload_file_button');
		if (!button) return;
		var frame;
		button.addEventListener('click', function (e) {
			e.preventDefault();
			if (typeof wp === 'undefined' || !wp.media) {
				return;
			}
			if (frame) {
				frame.open();
				return;
			}
			frame = wp.media({
				title: 'Select a file',
				button: { text: 'Use this file' },
				multiple: false
			});
			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				document.getElementById('ndingi_file_id').value = attachment.id;
				document.getElementById('ndingi_file_name').textContent = attachment.filename || attachment.title || attachment.url;
				button.textContent = 'Replace file';
				var fileRadio = document.querySelector('input[name="ndingi_link_type"][value="file"]');
				if (fileRadio) {
					fileRadio.checked = true;
				}
			});
			frame.open();
		});
	});
	</script>
	<?php
}

function ndingi_save_publication_link( $post_id ) {
	if ( ! isset( $_POST['ndingi_publication_link_nonce'] ) ||
		! wp_verify_nonce( $_POST['ndingi_publication_link_nonce'], 'ndingi_save_publication_link' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['ndingi_link_type'] ) ) {
		$link_type = sanitize_text_field( wp_unslash( $_POST['ndingi_link_type'] ) );
		update_post_meta( $post_id, 'ndingi_link_type', in_array( $link_type, array( 'file', 'external' ), true ) ? $link_type : 'file' );
	}
	if ( isset( $_POST['ndingi_file_id'] ) ) {
		update_post_meta( $post_id, 'ndingi_file_id', absint( $_POST['ndingi_file_id'] ) );
	}
	if ( isset( $_POST['ndingi_external_url'] ) ) {
		update_post_meta( $post_id, 'ndingi_external_url', esc_url_raw( wp_unslash( $_POST['ndingi_external_url'] ) ) );
	}
}
add_action( 'save_post_publication', 'ndingi_save_publication_link' );

function ndingi_publication_link_enqueue( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	$screen_post_type = $screen ? $screen->post_type : '';
	global $post_type;
	if ( 'publication' === $screen_post_type || 'publication' === $post_type ) {
		wp_enqueue_media();
	}
}
add_action( 'admin_enqueue_scripts', 'ndingi_publication_link_enqueue' );

/**
 * Resolve a publication's outbound URL the same way ndingi's
 * src/lib/sanity/resources.ts did (fileUrl for "file", externalUrl for "external").
 */
function ndingi_publication_url( $post_id ) {
	$link_type = get_post_meta( $post_id, 'ndingi_link_type', true ) ?: 'file';
	if ( 'file' === $link_type ) {
		$file_id = get_post_meta( $post_id, 'ndingi_file_id', true );
		return $file_id ? wp_get_attachment_url( $file_id ) : '';
	}
	return get_post_meta( $post_id, 'ndingi_external_url', true );
}
