<?php
/**
 * Template Name: People (Accordion)
 * Port of ndingi's src/app/people/page.tsx — an accordion of the three
 * rosters, each its own team grid.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$sections = array(
	'management' => 'Management Team',
	'trustees'   => 'Trustees',
	'board'      => 'Board of Management',
);
?>
<div class="wrap">
	<?php ndingi_breadcrumb( array( array( 'label' => 'About', 'href' => home_url( '/about/' ) ), array( 'label' => 'People' ) ) ); ?>
	<h1 class="page-title"><?php the_title(); ?></h1>

	<div class="roster-section">
		<?php foreach ( $sections as $slug => $label ) : ?>
			<details>
				<summary><?php echo esc_html( $label ); ?></summary>
				<?php get_template_part( 'template-parts/team-grid', null, array( 'members' => ndingi_safe_get_roster( $slug ), 'grid_id' => 'people-' . $slug ) ); ?>
			</details>
		<?php endforeach; ?>
	</div>
</div>
<?php get_footer(); ?>
