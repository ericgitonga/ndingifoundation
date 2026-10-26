<?php
/**
 * Template Name: Core Values
 * Port of ndingi's src/app/core-values/page.tsx — a grid of core_value
 * CPT entries (title + excerpt) instead of a hardcoded TS array.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="wrap">
	<h1 class="page-title"><?php the_title(); ?></h1>
	<?php if ( get_the_content() ) : ?>
		<div class="prose" style="margin-bottom:2rem;">
			<?php the_content(); ?>
		</div>
	<?php endif; ?>
	<div class="card-grid card-grid--2">
		<?php foreach ( ndingi_safe_get_core_values() as $value ) : ?>
			<div class="advisor-card">
				<h2 style="font-size:1rem;font-weight:600;color:var(--secondary);text-transform:none;letter-spacing:normal;"><?php echo esc_html( $value->post_title ); ?></h2>
				<p style="margin-top:0.25rem;font-size:0.875rem;color:var(--fg-80);"><?php echo esc_html( $value->post_excerpt ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php get_footer(); ?>
