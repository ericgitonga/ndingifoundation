<?php
/**
 * Template Name: Programme Detail
 * Assigned to Education / Sustainable Livelihoods / Water & Ecosystem
 * Management — port of those three near-identical ndingi route files
 * (src/app/education, /sustainable-livelihoods, /water-ecosystem-management)
 * collapsed into one shared template, since only the parent link and
 * content differ.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$parent = get_post( wp_get_post_parent_id( get_the_ID() ) );
?>
<div class="wrap">
	<?php
	ndingi_breadcrumb(
		array(
			array( 'label' => $parent ? $parent->post_title : 'Our Work', 'href' => $parent ? get_permalink( $parent ) : home_url( '/our-work/' ) ),
			array( 'label' => get_the_title() ),
		)
	);
	?>
	<h1 class="page-title"><?php the_title(); ?></h1>
	<?php if ( has_post_thumbnail() ) : ?>
		<div style="margin:1.5rem 0 2rem;overflow:hidden;border-radius:0.5rem;">
			<?php the_post_thumbnail( 'large', array( 'style' => 'width:100%;height:auto;display:block;' ) ); ?>
		</div>
	<?php endif; ?>
	<div class="prose"><?php the_content(); ?></div>
</div>
<?php get_footer(); ?>
