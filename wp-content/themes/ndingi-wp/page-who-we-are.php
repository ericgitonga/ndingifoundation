<?php
/**
 * Template Name: Who We Are
 * Port of ndingi's src/app/who-we-are/page.tsx — intro/bio copy (editable
 * as this page's own content) plus a detail grid of its child pages
 * (Mission, Vision, Core Values), each opening in a modal.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$children = ndingi_safe_get_child_pages( get_the_ID() );
?>
<div class="wrap">
	<?php ndingi_breadcrumb( array( array( 'label' => 'About', 'href' => home_url( '/about/' ) ), array( 'label' => 'Who We Are' ) ) ); ?>
	<h1 class="page-title"><?php the_title(); ?></h1>

	<div class="prose"><?php the_content(); ?></div>

	<div class="card-grid card-grid--3" id="who-we-are-grid" style="margin-top:2.5rem;">
		<?php foreach ( $children as $child ) :
			$icon  = get_post_meta( $child->ID, 'ndingi_card_icon', true );
			$blurb = get_post_meta( $child->ID, 'ndingi_card_blurb', true );
			ndingi_modal_card( $child->post_name, $child->post_title, $blurb, $icon );
		endforeach; ?>
	</div>

	<?php foreach ( $children as $child ) :
		if ( 'core-values' === $child->post_name ) {
			ob_start();
			foreach ( ndingi_safe_get_core_values() as $value ) {
				printf(
					'<div><p style="font-weight:500;color:var(--foreground);margin:0;">%s</p><p style="font-size:0.875rem;color:var(--fg-80);margin:0;">%s</p></div>',
					esc_html( $value->post_title ),
					esc_html( $value->post_excerpt )
				);
			}
			$body_html = '<div style="display:flex;flex-direction:column;gap:0.75rem;">' . ob_get_clean() . '</div>';
		} else {
			$body_html = apply_filters( 'the_content', $child->post_content );
		}
		ndingi_modal_card_content( $child->post_name, $child->post_title, $body_html );
	endforeach; ?>

	<?php ndingi_render_card_dialog( 'who-we-are-grid' ); ?>
</div>
<?php get_footer(); ?>
