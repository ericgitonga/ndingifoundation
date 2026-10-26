<?php
/**
 * Small render helpers shared across page templates — kept here instead of
 * duplicated per-template, same intent as ndingi's shared components
 * (Breadcrumb.tsx, DetailGrid.tsx/ProgrammeGrid.tsx/TeamGrid.tsx card+dialog
 * markup, formatDate.ts).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param array $items Each item: ['label' => string, 'href' => string|null].
 *                      Matches Breadcrumb.tsx's BreadcrumbItem shape.
 */
function ndingi_breadcrumb( array $items ) {
	echo '<nav class="breadcrumb" aria-label="Breadcrumb">';
	foreach ( $items as $i => $item ) {
		echo '<span class="breadcrumb__item">';
		if ( $i > 0 ) {
			echo '<span class="breadcrumb__sep" aria-hidden="true">/</span>';
		}
		if ( ! empty( $item['href'] ) ) {
			printf( '<a href="%s">%s</a>', esc_url( $item['href'] ), esc_html( $item['label'] ) );
		} else {
			printf( '<span class="breadcrumb__current">%s</span>', esc_html( $item['label'] ) );
		}
		echo '</span>';
	}
	echo '</nav>';
}

/**
 * A link-card tile (used by hub pages like About/Resources) — a plain
 * anchor to another page, not a modal trigger.
 */
function ndingi_link_card( $href, $title, $blurb, $icon_key = '' ) {
	?>
	<a class="card-tile" href="<?php echo esc_url( $href ); ?>">
		<?php ndingi_icon( $icon_key ); ?>
		<span class="card-tile__title"><?php echo esc_html( $title ); ?></span>
		<span class="card-tile__blurb"><?php echo esc_html( $blurb ); ?></span>
	</a>
	<?php
}

/**
 * A modal-trigger card tile (used by Our Work's programme grid and Who We
 * Are's detail grid) — opens a shared dialog populated from a matching
 * <template data-card-content> block. See assets/js/modal.js.
 *
 * When $photo_html is given (a <img> tag, e.g. from get_the_post_thumbnail())
 * the card shows that photo with the title/blurb underneath instead of an
 * icon tile — used by Our Work's programme cards.
 */
function ndingi_modal_card( $key, $title, $blurb, $icon_key = '', $photo_html = '' ) {
	$template_id = 'card-content-' . sanitize_html_class( $key );
	?>
	<button type="button" class="card-tile<?php echo $photo_html ? ' card-tile--photo' : ''; ?>" data-card-trigger="<?php echo esc_attr( $template_id ); ?>" aria-haspopup="dialog">
		<?php if ( $photo_html ) : ?>
			<?php echo $photo_html; // phpcs:ignore -- pre-built <img> markup from get_the_post_thumbnail(). ?>
			<span class="card-tile__body">
				<span class="card-tile__title"><?php echo esc_html( $title ); ?></span>
				<span class="card-tile__blurb"><?php echo esc_html( $blurb ); ?></span>
			</span>
		<?php else : ?>
			<?php ndingi_icon( $icon_key ); ?>
			<span class="card-tile__title"><?php echo esc_html( $title ); ?></span>
			<span class="card-tile__blurb"><?php echo esc_html( $blurb ); ?></span>
		<?php endif; ?>
	</button>
	<?php
}

/**
 * The hidden <template> holding one modal card's title + body, read by
 * assets/js/modal.js when its matching trigger is clicked.
 */
function ndingi_modal_card_content( $key, $title, $body_html ) {
	$template_id = 'card-content-' . sanitize_html_class( $key );
	?>
	<template id="<?php echo esc_attr( $template_id ); ?>">
		<h2 class="section-title"><?php echo esc_html( $title ); ?></h2>
		<div class="dialog-body"><?php echo $body_html; // phpcs:ignore -- pre-rendered trusted template content. ?></div>
	</template>
	<?php
}

/**
 * The single shared <dialog> that all modal-card triggers on a page open
 * into. Only one is needed per page — its content is swapped per click.
 */
function ndingi_render_card_dialog( $grid_id ) {
	?>
	<dialog class="ndingi-dialog" data-card-dialog="<?php echo esc_attr( $grid_id ); ?>">
		<button type="button" class="dialog-close" data-close-modal aria-label="Close">&times;</button>
		<div data-dialog-content></div>
	</dialog>
	<?php
}

/**
 * Wraps the ndingi-wp-content plugin's ndingi_get_child_pages() so a hub
 * page degrades to an empty grid instead of a fatal "critical error" white
 * screen if that plugin is ever deactivated — this exact gap took down
 * About, Our Work, and Resources in production once already.
 */
function ndingi_safe_get_child_pages( $parent_id ) {
	if ( ! function_exists( 'ndingi_get_child_pages' ) ) {
		return array();
	}
	return ndingi_get_child_pages( $parent_id );
}

/**
 * Same fatal-avoidance wrapping as ndingi_safe_get_child_pages() above, for
 * every other ndingi-wp-content plugin function a template calls directly.
 */
function ndingi_safe_get_core_values() {
	return function_exists( 'ndingi_get_core_values' ) ? ndingi_get_core_values() : array();
}

function ndingi_safe_get_roster( $roster_slug ) {
	return function_exists( 'ndingi_get_roster' ) ? ndingi_get_roster( $roster_slug ) : array();
}

function ndingi_safe_publication_url( $post_id ) {
	return function_exists( 'ndingi_publication_url' ) ? ndingi_publication_url( $post_id ) : '';
}

/**
 * Same date formatting as ndingi's formatDate.ts (en-GB, long style).
 */
function ndingi_format_date( $mysql_date ) {
	$timestamp = strtotime( $mysql_date );
	return $timestamp ? date_i18n( 'j F Y', $timestamp ) : '';
}

/**
 * Fetch a Page's rendered content by slug — used to reuse a standalone
 * page's copy inside a modal (e.g. the Mission page's text, shown again
 * inside Who We Are's detail grid) without duplicating it.
 */
function ndingi_page_content_by_slug( $slug ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		return '';
	}
	return apply_filters( 'the_content', $page->post_content );
}
