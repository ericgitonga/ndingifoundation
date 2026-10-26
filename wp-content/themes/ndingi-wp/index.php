<?php
/**
 * Fallback template required by WordPress for a theme to be valid.
 * Every actual route on this site is covered by a more specific template
 * (front-page.php, page.php, page-*.php, single.php) — this only renders
 * for something none of those match.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="wrap">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<h1 class="page-title"><?php the_title(); ?></h1>
		<div class="prose"><?php the_content(); ?></div>
	<?php endwhile; else : ?>
		<h1 class="page-title">Nothing found</h1>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
