<?php
/**
 * Router for PHP's built-in server (`php -S`), so plain-file requests
 * (images, CSS, JS) are served as-is and everything else falls through to
 * WordPress's own index.php — the built-in server has no rewrite rules of
 * its own. Standard pattern recommended by WP-CLI's own docs for this
 * exact "no Apache/Nginx available" local-preview case.
 */
$path = urldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
$file = __DIR__ . $path;

if ( $path !== '/' && file_exists( $file ) && ! is_dir( $file ) ) {
	return false;
}

chdir( __DIR__ );
require __DIR__ . '/index.php';
