<?php
/**
 * Template Name: About Hub
 * Port of ndingi's src/app/about/page.tsx — a hub of link-cards to this
 * page's children (Who We Are, People), each card's icon/blurb coming from
 * the child page's own Card Settings meta box. Per client feedback (closes
 * ndingi#76), no "About" heading/text — just the prominent logo, then the
 * cards.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$children = ndingi_safe_get_child_pages( get_the_ID() );
?>
<div class="wrap">
	<?php get_template_part( 'template-parts/prominent-logo' ); ?>

	<?php if ( get_the_content() ) : ?>
		<div class="prose" style="margin-top:2rem;margin-bottom:2rem;text-align:center;">
			<?php the_content(); ?>
		</div>
	<?php endif; ?>

	<div class="card-grid card-grid--2">
		<?php foreach ( $children as $child ) :
			$icon  = get_post_meta( $child->ID, 'ndingi_card_icon', true );
			$blurb = get_post_meta( $child->ID, 'ndingi_card_blurb', true );
			ndingi_link_card( get_permalink( $child ), $child->post_title, $blurb, $icon );
		endforeach; ?>
	</div>
</div>
<?php get_footer(); ?>
