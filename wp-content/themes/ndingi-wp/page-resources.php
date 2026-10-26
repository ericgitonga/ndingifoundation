<?php
/**
 * Template Name: Resources Hub
 * Port of ndingi's src/app/resources/page.tsx.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$children = ndingi_safe_get_child_pages( get_the_ID() );
?>
<div class="wrap">
	<h1 class="page-title"><?php the_title(); ?></h1>
	<div class="prose">
		<p>Updates, publications, and materials from the Foundation&rsquo;s work. Select a section below.</p>
	</div>

	<div class="card-grid card-grid--2">
		<?php foreach ( $children as $child ) :
			$icon  = get_post_meta( $child->ID, 'ndingi_card_icon', true );
			$blurb = get_post_meta( $child->ID, 'ndingi_card_blurb', true );
			ndingi_link_card( get_permalink( $child ), $child->post_title, $blurb, $icon );
		endforeach; ?>
	</div>
</div>
<?php get_footer(); ?>
