<?php
/**
 * Inline SVG icons — direct port of ndingi's src/components/icons.tsx.
 * Same viewBox/stroke attributes, same path data, keyed the same way so
 * ndingi_card_icon meta values (see the plugin's page-card-meta.php) line
 * up one-to-one with the original ICONS/PROGRAMME_ICONS/ABOUT_ICONS maps.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndingi_icon_paths() {
	return array(
		'education'                  => '<path d="M12 3 2 8l10 5 10-5-10-5Z" /><path d="M6 10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5" />',
		'sustainable-livelihoods'    => '<path d="M12 21c4-3 7-6.5 7-10.5A7 7 0 0 0 12 3a7 7 0 0 0-7 7.5C5 14.5 8 18 12 21Z" /><path d="M12 21V10" />',
		'water-ecosystem-management' => '<path d="M12 2.5s6.5 7.4 6.5 12a6.5 6.5 0 0 1-13 0c0-4.6 6.5-12 6.5-12Z" />',
		'mission'                    => '<circle cx="12" cy="12" r="8.5" /><circle cx="12" cy="12" r="4.5" /><circle cx="12" cy="12" r="0.75" fill="currentColor" stroke="none" />',
		'vision'                     => '<path d="M1.5 12S5.5 5.5 12 5.5 22.5 12 22.5 12 18.5 18.5 12 18.5 1.5 12 1.5 12Z" /><circle cx="12" cy="12" r="3" />',
		'core-values'                => '<path d="M12 20.5S3.5 15 3.5 8.75A4.25 4.25 0 0 1 12 7.5a4.25 4.25 0 0 1 8.5 1.25c0 6.25-8.5 11.75-8.5 11.75Z" />',
		'who-we-are'                 => '<circle cx="12" cy="8" r="3.5" /><path d="M4.5 20c1.2-4 4-6 7.5-6s6.3 2 7.5 6" />',
		'people'                     => '<circle cx="9" cy="8" r="3" /><circle cx="16" cy="9" r="2.5" /><path d="M3 20c0.8-3.4 3-5.5 6-5.5s5.2 2.1 6 5.5" /><path d="M15 14.8c2.4 0.3 4 2.1 4.6 5.2" />',
		'news'                       => '<path d="M4 5.5h13a2.5 2.5 0 0 1 2.5 2.5v10.5a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 18.5V6.5A1 1 0 0 1 4 5.5Z" /><path d="M7 9.5h7" /><path d="M7 12.5h7" /><path d="M7 15.5h4" />',
		'publications'               => '<path d="M12 6.5c-1.5-1-4-1.5-6-1.5v13c2 0 4.5 0.5 6 1.5" /><path d="M12 6.5c1.5-1 4-1.5 6-1.5v13c-2 0-4.5 0.5-6 1.5" /><path d="M12 6.5v13" />',
	);
}

/**
 * Echo an icon's <svg>. Falls back to nothing for an unknown/empty key so a
 * page without a card icon set just omits it, same as the original leaving
 * `Icon` undefined would have.
 */
function ndingi_icon( $key, $class = 'card-tile__icon' ) {
	$paths = ndingi_icon_paths();
	if ( empty( $key ) || ! isset( $paths[ $key ] ) ) {
		return;
	}
	printf(
		'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="%s" aria-hidden="true">%s</svg>',
		esc_attr( $class ),
		$paths[ $key ] // phpcs:ignore -- fixed, developer-controlled SVG path data, not user input.
	);
}
