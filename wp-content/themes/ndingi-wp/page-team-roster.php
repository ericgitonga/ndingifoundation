<?php
/**
 * Template Name: Team Roster
 * Assigned to Management Team / Trustees / Board of Management — port of
 * those three near-identical ndingi route files, collapsed into one shared
 * template that derives which roster to query from the page's slug.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$slug_to_roster = array(
	'management-team'    => 'management',
	'trustees'           => 'trustees',
	// Both slugs map to the same roster: a page already created under the
	// pre-rename title keeps its original 'board-of-directors' slug even
	// after ndingi_page_setup_run() renames its title (WordPress doesn't
	// regenerate the slug on a title update), while a page created fresh
	// gets the new 'board-of-management' slug.
	'board-of-directors' => 'board',
	'board-of-management' => 'board',
);
$roster  = $slug_to_roster[ get_post_field( 'post_name' ) ] ?? 'management';
$members = ndingi_safe_get_roster( $roster );
?>
<div class="wrap">
	<h1 class="page-title"><?php the_title(); ?></h1>
	<?php get_template_part( 'template-parts/team-grid', null, array( 'members' => $members, 'grid_id' => 'roster-' . $roster ) ); ?>
</div>
<?php get_footer(); ?>
