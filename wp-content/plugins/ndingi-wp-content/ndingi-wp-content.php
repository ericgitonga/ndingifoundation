<?php
/**
 * Plugin Name: Ndingi Foundation Content Model
 * Description: Custom post types, taxonomies, and meta fields for the Ndingi Foundation site — kept in a plugin rather than the theme so the content model survives a theme change.
 * Version: 0.4.7
 * Author: Ndingi Foundation
 * License: UNLICENSED
 * Text Domain: ndingi-wp-content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NDINGI_CONTENT_DIR', plugin_dir_path( __FILE__ ) );

require_once NDINGI_CONTENT_DIR . 'includes/cpt-team-member.php';
require_once NDINGI_CONTENT_DIR . 'includes/cpt-publication.php';
require_once NDINGI_CONTENT_DIR . 'includes/cpt-core-value.php';
require_once NDINGI_CONTENT_DIR . 'includes/cpt-partner.php';
require_once NDINGI_CONTENT_DIR . 'includes/page-card-meta.php';
require_once NDINGI_CONTENT_DIR . 'includes/front-page-meta.php';
require_once NDINGI_CONTENT_DIR . 'includes/contact-form-handler.php';
require_once NDINGI_CONTENT_DIR . 'includes/page-setup.php';
require_once NDINGI_CONTENT_DIR . 'includes/people-import.php';
require_once NDINGI_CONTENT_DIR . 'includes/donate-meta.php';
require_once NDINGI_CONTENT_DIR . 'includes/partners-meta.php';
