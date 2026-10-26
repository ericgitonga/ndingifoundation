<?php
/**
 * Template Name: Our Work Hub
 * Port of ndingi's src/app/our-work/page.tsx + ProgrammeGrid.tsx — each
 * programme page (child of this one) opens in a modal showing its own
 * content, rather than navigating away.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$children = ndingi_safe_get_child_pages( get_the_ID() );
?>
<div class="wrap-4xl">
	<h1 class="page-title"><?php the_title(); ?></h1>
	<div class="prose" style="max-width:42rem;">
		<?php if ( get_the_content() ) : ?>
			<?php the_content(); ?>
		<?php else : ?>
			<p>Three programme areas guided by Archbishop Ndingi&rsquo;s enduring legacy. Select a
			programme to learn more.</p>
		<?php endif; ?>
	</div>

	<div class="card-grid card-grid--3" id="our-work-grid">
		<?php foreach ( $children as $child ) :
			$icon  = get_post_meta( $child->ID, 'ndingi_card_icon', true );
			$blurb = get_post_meta( $child->ID, 'ndingi_card_blurb', true );
			$photo = has_post_thumbnail( $child ) ? get_the_post_thumbnail( $child, array( 400, 267 ), array( 'alt' => '' ) ) : '';
			ndingi_modal_card( $child->post_name, $child->post_title, $blurb, $icon, $photo );
		endforeach; ?>
	</div>

	<?php foreach ( $children as $child ) :
		ndingi_modal_card_content( $child->post_name, $child->post_title, apply_filters( 'the_content', $child->post_content ) );
	endforeach; ?>

	<?php ndingi_render_card_dialog( 'our-work-grid' ); ?>
</div>
<?php get_footer(); ?>
