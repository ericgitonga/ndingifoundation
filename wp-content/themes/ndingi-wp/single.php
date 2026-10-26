<?php
/**
 * Single News post — port of ndingi's src/app/news/[slug]/page.tsx.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="wrap">
		<?php
		ndingi_breadcrumb(
			array(
				array( 'label' => 'Resources', 'href' => home_url( '/resources/' ) ),
				array( 'label' => 'News', 'href' => home_url( '/news/' ) ),
				array( 'label' => get_the_title() ),
			)
		);
		?>
		<h1 class="page-title"><?php the_title(); ?></h1>
		<p class="list-card__meta" style="margin-top:0.5rem;"><?php echo esc_html( ndingi_format_date( get_the_date( 'Y-m-d H:i:s' ) ) ); ?></p>

		<?php if ( has_post_thumbnail() ) : ?>
			<div style="margin-top:1.5rem;">
				<?php the_post_thumbnail( 'large', array( 'style' => 'width:100%;border-radius:0.5rem;object-fit:cover;' ) ); ?>
			</div>
		<?php endif; ?>

		<div class="prose"><?php the_content(); ?></div>
	</div>
	<?php
endwhile;
get_footer();
