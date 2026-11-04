<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NDINGI_THEME_VERSION', wp_get_theme()->get( 'Version' ) );

function ndingi_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'script', 'style' ) );

	register_nav_menus(
		array(
			'primary' => 'Primary Navigation',
		)
	);
}
add_action( 'after_setup_theme', 'ndingi_theme_setup' );

function ndingi_enqueue_assets() {
	$theme_uri = get_stylesheet_directory_uri();

	wp_enqueue_style( 'ndingi-main', $theme_uri . '/assets/css/main.css', array(), NDINGI_THEME_VERSION );
	wp_enqueue_script( 'ndingi-nav', $theme_uri . '/assets/js/nav.js', array(), NDINGI_THEME_VERSION, true );
	wp_enqueue_script( 'ndingi-modal', $theme_uri . '/assets/js/modal.js', array(), NDINGI_THEME_VERSION, true );
	wp_enqueue_script( 'ndingi-cookie-consent', $theme_uri . '/assets/js/cookie-consent.js', array(), NDINGI_THEME_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'ndingi_enqueue_assets' );

/**
 * Ensure WordPress always uses the Ndingi Foundation emblem instead of the
 * built-in WordPress "W" fallback icon (w-logo-gray-white-bg.png) in search results.
 */
function ndingi_default_site_icon_url( $url, $size, $blog_id ) {
	if ( empty( $url ) || false !== strpos( $url, 'w-logo-' ) ) {
		return get_theme_file_uri( '/assets/images/icon.png' );
	}
	return $url;
}
add_filter( 'get_site_icon_url', 'ndingi_default_site_icon_url', 10, 3 );

/**
 * Serve the theme's favicon.ico directly on /favicon.ico requests when no custom
 * DB site_icon is configured, avoiding WordPress's 302 redirect to w-logo-gray-white-bg.png.
 */
function ndingi_serve_favicon_ico() {
	if ( (int) get_option( 'site_icon' ) > 0 ) {
		return;
	}
	$ico_path = get_theme_file_path( '/assets/images/favicon.ico' );
	if ( file_exists( $ico_path ) ) {
		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: image/x-icon' );
			header( 'Content-Length: ' . (string) filesize( $ico_path ) );
			header( 'Cache-Control: public, max-age=86400' );
		}
		readfile( $ico_path );
		exit;
	}
	wp_safe_redirect( get_theme_file_uri( '/assets/images/icon.png' ), 302 );
	exit;
}
add_action( 'do_faviconico', 'ndingi_serve_favicon_ico' );

/**
 * Output Organization + WebSite JSON-LD structured data and OpenGraph image tags
 * so search engines associate the Ndingi Foundation emblem and logo with the domain.
 */
function ndingi_output_brand_schema_and_meta() {
	$site_name = "R S Ndingi Mwana 'a Nzeki Foundation";
	$site_url  = home_url( '/' );
	$icon_url  = get_theme_file_uri( '/assets/images/icon.png' );
	$logo_url  = get_theme_file_uri( '/assets/images/logo.png' );
	$schema    = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type' => 'Organization',
				'@id'   => $site_url . '#organization',
				'name'  => $site_name,
				'url'   => $site_url,
				'logo'  => array(
					'@type'      => 'ImageObject',
					'@id'        => $site_url . '#logo',
					'url'        => $icon_url,
					'contentUrl' => $icon_url,
					'width'      => 512,
					'height'     => 512,
					'caption'    => $site_name,
				),
				'image' => $logo_url,
			),
			array(
				'@type'     => 'WebSite',
				'@id'       => $site_url . '#website',
				'url'       => $site_url,
				'name'      => $site_name,
				'publisher' => array(
					'@id' => $site_url . '#organization',
				),
			),
		),
	);
	?>
	<meta property="og:site_name" content="<?php echo esc_attr( $site_name ); ?>" />
	<meta property="og:image" content="<?php echo esc_url( $icon_url ); ?>" />
	<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
	<?php
}
add_action( 'wp_head', 'ndingi_output_brand_schema_and_meta', 5 );

require get_template_directory() . '/inc/icons.php';
require get_template_directory() . '/inc/template-helpers.php';

/**
 * Default nav links used when no "primary" menu has been assigned yet —
 * matches ndingi's hardcoded Header.tsx NAV_LINKS.
 */
function ndingi_default_nav_links() {
	return array(
		array( 'href' => home_url( '/' ), 'label' => 'Home' ),
		array( 'href' => home_url( '/about/' ), 'label' => 'About' ),
		array( 'href' => home_url( '/our-work/' ), 'label' => 'Our Work' ),
		array( 'href' => home_url( '/partners/' ), 'label' => 'Partners' ),
		array( 'href' => home_url( '/resources/' ), 'label' => 'Resources' ),
	);
}

/**
 * The site footer's contact email — was hardcoded in ndingi's Footer.tsx.
 * Filterable so it can move to a Customizer/option field later without a
 * template change.
 */
function ndingi_contact_email() {
	return apply_filters( 'ndingi_contact_email', 'admin@ndingifoundation.org' );
}

/**
 * About, Our Work, Resources, Who We Are, People, Core Values, and
 * Publications all call functions the ndingi-wp-content plugin defines —
 * if that plugin isn't active those pages degrade to an empty grid (see
 * ndingi_safe_get_child_pages() etc. in inc/template-helpers.php) rather
 * than crashing, but the site is still missing most of its real content.
 * Surface that loudly in wp-admin instead of leaving it to be found by a
 * visitor hitting a blank/empty page.
 */
function ndingi_check_required_plugin_notice() {
	if ( function_exists( 'ndingi_get_child_pages' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p>
			<strong>Ndingi Foundation Content</strong> plugin is not active. Most of this
			theme's pages (About, Our Work, Resources, Who We Are, People, Core Values,
			Publications) depend on it and will show as empty until it's activated —
			see <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">Plugins</a>.
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'ndingi_check_required_plugin_notice' );
