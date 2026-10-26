<?php
/**
 * Template Name: Publications List
 * Port of ndingi's src/app/publications/page.tsx.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$resources = get_posts(
	array(
		'post_type'      => 'publication',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
?>
<div class="wrap">
	<?php ndingi_breadcrumb( array( array( 'label' => 'Resources', 'href' => home_url( '/resources/' ) ), array( 'label' => 'Publications' ) ) ); ?>
	<h1 class="page-title"><?php the_title(); ?></h1>
	<p class="lede">Downloadable reports, materials, and publications from the Foundation&rsquo;s work.</p>

	<?php if ( empty( $resources ) ) : ?>
		<p class="prose">Materials will appear here as they become available.</p>
	<?php else : ?>
		<div class="list-card">
			<?php foreach ( $resources as $resource ) :
				$href       = ndingi_safe_publication_url( $resource->ID );
				$link_type  = get_post_meta( $resource->ID, 'ndingi_link_type', true ) ?: 'file';
				$categories = get_the_terms( $resource->ID, 'publication_category' );
				$category   = ( $categories && ! is_wp_error( $categories ) ) ? $categories[0]->name : '';
				?>
				<div class="list-card__block">
					<div class="list-card__block-head">
						<p class="list-card__title"><?php echo esc_html( $resource->post_title ); ?></p>
						<p class="list-card__meta">
							<?php if ( $category ) : ?><?php echo esc_html( $category ); ?> &middot; <?php endif; ?>
							<?php echo esc_html( ndingi_format_date( $resource->post_date ) ); ?>
						</p>
					</div>
					<?php if ( $resource->post_excerpt ) : ?>
						<p style="margin-top:0.5rem;font-size:0.875rem;color:var(--fg-80);"><?php echo esc_html( $resource->post_excerpt ); ?></p>
					<?php endif; ?>
					<?php if ( $href ) : ?>
						<a class="list-card__link" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo 'file' === $link_type ? 'Download' : 'View'; ?> &rarr;
						</a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
