<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
// Home and About already show a large logo in the page body
// (template-parts/prominent-logo.php) — the navbar logo would be
// redundant there, so it's shown only on every other page.
$ndingi_show_navbar_logo = ! ( is_front_page() || is_page_template( 'page-about.php' ) );
?>
<header class="site-header<?php echo $ndingi_show_navbar_logo ? '' : ' site-header--no-logo'; ?>">
	<div class="site-header__row">
		<?php if ( $ndingi_show_navbar_logo ) : ?>
			<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img
					src="<?php echo esc_url( get_theme_file_uri( '/assets/images/logo.png' ) ); ?>"
					alt="R S Ndingi Mwana 'a Nzeki Foundation"
					width="280"
					height="88"
				/>
			</a>
		<?php endif; ?>

		<button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="main-navigation" aria-label="Open menu">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" class="card-tile__icon" style="height:1.5rem;width:1.5rem;" aria-hidden="true">
				<path d="M4 7h16M4 12h16M4 17h16" />
			</svg>
		</button>

		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<nav id="main-navigation" class="main-nav" aria-label="Main Navigation">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'items_wrap'     => '%3$s',
						'depth'          => 1,
					)
				);
				?>
				<button type="button" class="nav-link" data-open-modal="ndingi-contact-modal">Contact</button>
				<button type="button" class="btn btn-primary" data-open-modal="ndingi-donate-modal">Donate</button>
			</nav>
		<?php else : ?>
			<nav id="main-navigation" class="main-nav" aria-label="Main Navigation">
				<?php foreach ( ndingi_default_nav_links() as $link ) : ?>
					<a href="<?php echo esc_url( $link['href'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
				<?php endforeach; ?>
				<button type="button" class="nav-link" data-open-modal="ndingi-contact-modal">Contact</button>
				<button type="button" class="btn btn-primary" data-open-modal="ndingi-donate-modal">Donate</button>
			</nav>
		<?php endif; ?>
	</div>
</header>

<?php get_template_part( 'template-parts/modal', 'contact' ); ?>
<?php get_template_part( 'template-parts/modal', 'donate' ); ?>

<main>
