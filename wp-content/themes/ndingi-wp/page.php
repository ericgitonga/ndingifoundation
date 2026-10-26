<?php
/**
 * Generic Page fallback — any Page without a dedicated template in this
 * theme renders here: title + its block-editor content, same "centered
 * column of prose" layout every ndingi page used.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="wrap">
	<h1 class="page-title"><?php the_title(); ?></h1>
	<div class="prose"><?php the_content(); ?></div>
</div>
<?php get_footer(); ?>
