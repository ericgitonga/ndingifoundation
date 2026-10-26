<?php
/**
 * Opt-in sibling to seed.php: replaces the local preview's placeholder
 * "Sample …" team members with the real roster pulled live from Sanity.
 * Thin CLI wrapper around the plugin's own ndingi_people_import_run() (see
 * wp-content/plugins/ndingi-wp-content/includes/people-import.php, which
 * also exposes this as a Tools → Ndingi People Import button in wp-admin
 * for hosts with no WP-CLI/SSH access). Not run automatically by
 * setup.sh — seed.php stays offline-friendly by design — but useful
 * whenever a demo/screenshot needs to show real content instead of
 * placeholders:
 *
 *   php .wp-runtime/wp-cli.phar --path=.wp-runtime eval-file local-preview/import-real-people.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ndingi_people_import_run' ) ) {
	echo "The ndingi-wp-content plugin isn't active — activate it first.\n";
	exit( 1 );
}

echo "Fetching people from Sanity and rebuilding Team Members…\n";
$result = ndingi_people_import_run();

if ( ! $result['ok'] ) {
	echo $result['message'] . "\n";
	exit( 1 );
}

echo 'Removed ' . $result['removed'] . " existing team members.\n";
foreach ( $result['imported'] as $name ) {
	echo "  Imported: {$name}\n";
}
echo "Done.\n";
