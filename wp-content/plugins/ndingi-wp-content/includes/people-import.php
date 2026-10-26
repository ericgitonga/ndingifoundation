<?php
/**
 * One-click "Tools → Ndingi People Import" admin page — pulls the real
 * Management Team/Trustees/Board of Management roster (names, roles, bios,
 * photos) live from ndingi-foundation's public Sanity dataset (the same
 * project/dataset the Next.js site reads — see that repo's
 * src/lib/sanity/client.ts) and writes it into this site's own `team_member`
 * post type. Exists so this can be run from wp-admin on a host with no
 * WP-CLI/SSH access, the same reason `page-setup.php`'s admin tool exists
 * (see CHANGELOG 0.4.0).
 *
 * This is a one-time migration aid, not a live integration: once run, the
 * site never talks to Sanity again — everything it wrote lives in this
 * site's own database from then on. Safe to re-run any time the Sanity data
 * changes before the client commits to WordPress: it always deletes every
 * existing `team_member` post first, so it never duplicates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NDINGI_PEOPLE_IMPORT_PROJECT_ID', 'g0i8edrt' );
define( 'NDINGI_PEOPLE_IMPORT_DATASET', 'production' );

function ndingi_people_import_sanity_image_url( $ref, $project_id, $dataset ) {
	// "image-<hash>-<w>x<h>-<ext>" -> https://cdn.sanity.io/images/<project>/<dataset>/<hash>-<w>x<h>.<ext>
	if ( ! preg_match( '/^image-([a-f0-9]+)-(\d+x\d+)-(\w+)$/', $ref, $m ) ) {
		return null;
	}
	return "https://cdn.sanity.io/images/{$project_id}/{$dataset}/{$m[1]}-{$m[2]}.{$m[3]}";
}

function ndingi_people_import_portable_text_to_html( $blocks ) {
	if ( empty( $blocks ) || ! is_array( $blocks ) ) {
		return '';
	}
	$html = '';
	foreach ( $blocks as $block ) {
		if ( ( $block['_type'] ?? '' ) !== 'block' ) {
			continue;
		}
		$text = '';
		foreach ( $block['children'] ?? array() as $child ) {
			$text .= $child['text'] ?? '';
		}
		if ( '' !== trim( $text ) ) {
			$html .= '<p>' . esc_html( $text ) . "</p>\n";
		}
	}
	return $html;
}

/**
 * @return array{ok: bool, message?: string, imported?: string[], removed?: int}
 */
function ndingi_people_import_run() {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$query = '*[_type == "person"] | order(order asc) {_id,name,title,rosters,order,"photoRef": photo.asset._ref, bio}';
	$url   = sprintf(
		'https://%s.apicdn.sanity.io/v2025-01-01/data/query/%s?%s',
		NDINGI_PEOPLE_IMPORT_PROJECT_ID,
		NDINGI_PEOPLE_IMPORT_DATASET,
		http_build_query( array( 'query' => $query ) )
	);

	$response = wp_remote_get( $url, array( 'timeout' => 30 ) );
	if ( is_wp_error( $response ) ) {
		return array( 'ok' => false, 'message' => 'Failed to reach Sanity: ' . $response->get_error_message() );
	}

	$body   = json_decode( wp_remote_retrieve_body( $response ), true );
	$people = $body['result'] ?? array();
	if ( empty( $people ) ) {
		return array( 'ok' => false, 'message' => 'Sanity returned no people — nothing was changed.' );
	}

	$existing = get_posts( array( 'post_type' => 'team_member', 'posts_per_page' => -1, 'post_status' => 'any' ) );
	foreach ( $existing as $post ) {
		wp_delete_post( $post->ID, true );
	}

	$imported = array();
	foreach ( $people as $person ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'team_member',
				'post_title'   => $person['name'],
				'post_content' => ndingi_people_import_portable_text_to_html( $person['bio'] ?? null ),
				'post_status'  => 'publish',
				'menu_order'   => (int) ( $person['order'] ?? 0 ),
			)
		);

		if ( ! empty( $person['title'] ) ) {
			update_post_meta( $post_id, 'ndingi_role', $person['title'] );
		}
		if ( ! empty( $person['rosters'] ) ) {
			wp_set_object_terms( $post_id, $person['rosters'], 'ndingi_roster' );
		}
		if ( ! empty( $person['photoRef'] ) ) {
			$image_url = ndingi_people_import_sanity_image_url( $person['photoRef'], NDINGI_PEOPLE_IMPORT_PROJECT_ID, NDINGI_PEOPLE_IMPORT_DATASET );
			if ( $image_url ) {
				$attachment_id = media_sideload_image( $image_url, $post_id, $person['name'], 'id' );
				if ( ! is_wp_error( $attachment_id ) ) {
					set_post_thumbnail( $post_id, $attachment_id );
				}
			}
		}
		$imported[] = $person['name'];
	}

	return array( 'ok' => true, 'imported' => $imported, 'removed' => count( $existing ) );
}

function ndingi_people_import_admin_menu() {
	add_management_page(
		'Ndingi People Import',
		'Ndingi People Import',
		'manage_options',
		'ndingi-people-import',
		'ndingi_people_import_render_admin_page'
	);
}
add_action( 'admin_menu', 'ndingi_people_import_admin_menu' );

function ndingi_people_import_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$result = null;
	if ( isset( $_POST['ndingi_people_import_run'] ) && check_admin_referer( 'ndingi_people_import' ) ) {
		$result = ndingi_people_import_run();
	}
	?>
	<div class="wrap">
		<h1>Ndingi People Import</h1>
		<p>
			Pulls the real Management Team, Trustees, and Board of Management roster
			(names, roles, bios, and photos) from the Foundation's existing Sanity
			content (the same content the current Vercel site reads) and writes it into
			this site's own Team Members. This is a one-time migration step — once run,
			this site never needs to talk to Sanity again; edit team members from
			<strong>Team Members</strong> in the sidebar from then on.
		</p>
		<p>
			<strong>This replaces every existing Team Member entry</strong> — safe to
			run again later if the Foundation adds someone new on the old site before
			this migration is finalised, but don't run it after you've started editing
			team members directly here, or those edits will be lost.
		</p>
		<?php if ( $result ) : ?>
			<?php if ( $result['ok'] ) : ?>
				<div class="notice notice-success">
					<p>
						<strong>Imported <?php echo count( $result['imported'] ); ?> people</strong>
						(replaced <?php echo (int) $result['removed']; ?> existing entries):
						<?php echo esc_html( implode( ', ', $result['imported'] ) ); ?>
					</p>
				</div>
			<?php else : ?>
				<div class="notice notice-error">
					<p><?php echo esc_html( $result['message'] ); ?></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'ndingi_people_import' ); ?>
			<p><button type="submit" name="ndingi_people_import_run" value="1" class="button button-primary">Import People from Sanity</button></p>
		</form>
	</div>
	<?php
}
