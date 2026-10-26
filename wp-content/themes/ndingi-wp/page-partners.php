<?php
/**
 * Template Name: Partners
 * Port of ndingi's src/app/partners/page.tsx. Displays page content,
 * dynamic Professional Advisors, and Programme & Donor Partners.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$partners_id = get_the_ID();
$advisors_intro = get_post_meta( $partners_id, 'ndingi_advisors_intro', true ) ?: "The professional service providers who support the Foundation\u{2019}s governance and operations.";
$donors_content = get_post_meta( $partners_id, 'ndingi_donor_partners_content', true );

$default_advisors = array(
	array(
		'category' => 'Bankers',
		'advisors' => array(
			array( 'name' => 'NIC Bank', 'detail' => array( 'The Mall, Westlands Branch, Nairobi' ) ),
			array( 'name' => 'Caritas Microfinance Bank', 'detail' => array( 'Cardinal Otunga Plaza Branch, Nairobi' ) ),
		),
	),
	array(
		'category' => 'Auditors',
		'advisors' => array(
			array(
				'name'   => 'Kigundu & Company',
				'detail' => array( 'Certified Public Accountants (Kenya)', 'P.O. Box 2309-00606, Sarit Centre, Nairobi' ),
			),
		),
	),
	array(
		'category' => 'Tax Consultants',
		'advisors' => array(
			array(
				'name'   => 'Taxwise Africa Consulting LLP',
				'detail' => array( '9th Floor Barclays Plaza, Loita Street', 'P.O. Box 9539-00100, Nairobi' ),
				'link'   => array( 'href' => 'https://www.taxwise-consulting.com', 'label' => 'www.taxwise-consulting.com' ),
			),
		),
	),
	array(
		'category' => 'Lawyers',
		'advisors' => array(
			array(
				'name'   => 'Nyiha, Mukoma & Company Advocates',
				'detail' => array( 'Old Mutual Building, 3rd Floor, Kimathi Street', 'P.O. Box 28491-00200, Nairobi' ),
			),
		),
	),
);

$professional_advisors = function_exists( 'ndingi_get_professional_advisors' )
	? ndingi_get_professional_advisors( $partners_id )
	: $default_advisors;
if ( empty( $professional_advisors ) ) {
	$professional_advisors = $default_advisors;
}

$donor_partners = function_exists( 'ndingi_get_donor_partners' )
	? ndingi_get_donor_partners( $partners_id )
	: array();
?>
<div class="wrap">
	<h1 class="page-title"><?php the_title(); ?></h1>

	<?php if ( get_the_content() ) : ?>
		<div class="prose" style="margin-top:1.5rem;margin-bottom:2rem;">
			<?php the_content(); ?>
		</div>
	<?php endif; ?>

	<section style="margin-top:2rem;">
		<h2 class="subsection-title" style="margin-top:0;">Professional Advisors</h2>
		<p style="margin-top:0.5rem;"><?php echo esc_html( $advisors_intro ); ?></p>

		<div class="advisor-grid">
			<?php foreach ( $professional_advisors as $group ) : ?>
				<div class="advisor-card">
					<h3><?php echo esc_html( $group['category'] ?? '' ); ?></h3>
					<ul>
						<?php foreach ( ( $group['advisors'] ?? array() ) as $advisor ) : ?>
							<li>
								<p class="advisor-name"><?php echo esc_html( $advisor['name'] ?? '' ); ?></p>
								<?php foreach ( ( $advisor['detail'] ?? array() ) as $line ) : ?>
									<p class="advisor-detail"><?php echo esc_html( $line ); ?></p>
								<?php endforeach; ?>
								<?php if ( ! empty( $advisor['link'] ) ) : ?>
									<a href="<?php echo esc_url( $advisor['link']['href'] ?? '' ); ?>"><?php echo esc_html( $advisor['link']['label'] ?? ( $advisor['link']['href'] ?? '' ) ); ?></a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section style="margin-top:2.5rem;">
		<h2 class="subsection-title" style="margin-top:0;">Programme &amp; Donor Partners</h2>
		<?php if ( $donors_content ) : ?>
			<div class="prose" style="margin-top:0.5rem;">
				<?php echo wp_kses_post( wpautop( $donors_content ) ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $donor_partners ) ) : ?>
			<div class="card-grid card-grid--3" style="margin-top:1.5rem;">
				<?php foreach ( $donor_partners as $partner ) :
					$p_name  = $partner['name'] ?? '';
					$p_logo  = $partner['logo_url'] ?? '';
					$p_link  = $partner['link'] ?? '';
					$p_blurb = $partner['blurb'] ?? '';
					?>
					<div class="advisor-card" style="display:flex;flex-direction:column;align-items:flex-start;">
						<?php if ( $p_logo ) : ?>
							<img src="<?php echo esc_url( $p_logo ); ?>" alt="<?php echo esc_attr( $p_name ); ?>" style="max-height:48px;max-width:100%;height:auto;margin-bottom:0.75rem;object-fit:contain;" />
						<?php endif; ?>
						<h3 style="margin-top:0;"><?php echo esc_html( $p_name ); ?></h3>
						<?php if ( $p_blurb ) : ?>
							<p style="margin-top:0.5rem;font-size:0.875rem;color:var(--fg-80);"><?php echo esc_html( $p_blurb ); ?></p>
						<?php endif; ?>
						<?php if ( $p_link ) : ?>
							<a href="<?php echo esc_url( $p_link ); ?>" style="margin-top:auto;padding-top:0.5rem;" target="_blank" rel="noopener noreferrer"><?php echo esc_html( ! empty( $partner['link_label'] ) ? $partner['link_label'] : 'Visit Website &rarr;' ); ?></a>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
</div>
<?php get_footer(); ?>
