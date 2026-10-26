<?php
/**
 * Team grid + shared modal — port of ndingi's TeamGrid.tsx. Expects
 * $args = ['members' => WP_Post[], 'grid_id' => string].
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$members = $args['members'] ?? array();
$grid_id = $args['grid_id'] ?? 'team-grid';
?>
<div class="team-grid" id="<?php echo esc_attr( $grid_id ); ?>">
	<?php foreach ( $members as $member ) :
		$role = get_post_meta( $member->ID, 'ndingi_role', true );
		?>
		<button type="button" class="team-card" data-card-trigger="<?php echo esc_attr( 'team-content-' . $member->ID ); ?>" aria-haspopup="dialog">
			<?php echo get_the_post_thumbnail( $member, array( 112, 112 ), array( 'class' => 'team-card__photo', 'alt' => esc_attr( $member->post_title ) ) ); ?>
			<span class="team-card__name"><?php echo esc_html( $member->post_title ); ?></span>
			<?php if ( $role ) : ?>
				<span class="team-card__title"><?php echo esc_html( $role ); ?></span>
			<?php endif; ?>
		</button>
	<?php endforeach; ?>
</div>

<?php foreach ( $members as $member ) :
	$role = get_post_meta( $member->ID, 'ndingi_role', true );
	?>
	<template id="team-content-<?php echo esc_attr( $member->ID ); ?>">
		<div class="dialog-header">
			<?php echo get_the_post_thumbnail( $member, array( 80, 80 ), array( 'alt' => esc_attr( $member->post_title ) ) ); ?>
			<div>
				<h2><?php echo esc_html( $member->post_title ); ?></h2>
				<?php if ( $role ) : ?>
					<p><?php echo esc_html( $role ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( $member->post_content ) : ?>
			<div class="dialog-body"><?php echo apply_filters( 'the_content', $member->post_content ); ?></div>
		<?php endif; ?>
	</template>
<?php endforeach; ?>

<?php ndingi_render_card_dialog( $grid_id ); ?>
