<?php
/**
 * Template Name: Contact
 * Port of ndingi's src/app/contact/page.tsx.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="wrap">
	<h1 class="page-title"><?php the_title(); ?></h1>
	<div style="margin-top:1.5rem;">
		<?php get_template_part( 'template-parts/contact-info' ); ?>
	</div>
	<?php get_template_part( 'template-parts/contact-form' ); ?>
</div>
<?php get_footer(); ?>
