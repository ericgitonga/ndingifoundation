<?php
/**
 * Template Name: News List
 * Port of ndingi's src/app/news/page.tsx — lists native WP Posts (the
 * News post type in the old Sanity schema maps onto WP's built-in Post).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$posts = get_posts( array( 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
?>
<div class="wrap">
	<?php ndingi_breadcrumb( array( array( 'label' => 'Resources', 'href' => home_url( '/resources/' ) ), array( 'label' => 'News' ) ) ); ?>
	<h1 class="page-title"><?php the_title(); ?></h1>
	<div class="prose">
		<p>Announcements and updates on the Foundation&rsquo;s ongoing work, including photos from
		our programmes and the communities we work alongside.</p>
	</div>

	<?php if ( empty( $posts ) ) : ?>
		<p class="prose">Posts will appear here as they become available.</p>
	<?php else : ?>
		<div class="list-card">
			<?php foreach ( $posts as $post ) : setup_postdata( $post ); ?>
				<a class="list-card__item" href="<?php the_permalink(); ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( array( 96, 64 ), array( 'alt' => '' ) ); ?>
					<?php endif; ?>
					<div>
						<p class="list-card__title"><?php the_title(); ?></p>
						<p class="list-card__meta"><?php echo esc_html( ndingi_format_date( get_the_date( 'Y-m-d H:i:s' ) ) ); ?></p>
					</div>
				</a>
			<?php endforeach; wp_reset_postdata(); ?>
		</div>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
