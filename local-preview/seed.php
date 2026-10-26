<?php
if ( ! defined( 'ABSPATH' ) ) {
	if ( file_exists( __DIR__ . '/../wp-load.php' ) ) {
		require_once __DIR__ . '/../wp-load.php';
	} elseif ( file_exists( __DIR__ . '/../.wp-runtime/wp-load.php' ) ) {
		require_once __DIR__ . '/../.wp-runtime/wp-load.php';
	}
}
/**
 * Seeds sample content so the local preview isn't empty: the real page
 * copy from the original Next.js/Sanity site (mission, vision, programme
 * descriptions, core values) plus clearly-labelled placeholder team
 * members, a news post, and a publication for the client to replace with
 * real ones. Run via: wp eval-file local-preview/seed.php
 */

/**
 * Exact-title lookup across any post type — get_page_by_title() was fully
 * deprecated in WP 6.2 (unreliable LIKE-based matching); WP_Query's exact
 * 'title' parameter is the replacement WordPress itself recommends.
 */
function ndingi_find_by_title( $title, $post_type ) {
	$query = new WP_Query(
		array(
			'post_type'              => $post_type,
			'title'                  => $title,
			'post_status'            => 'any',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	return $query->have_posts() ? $query->posts[0] : null;
}

function ndingi_seed_placeholder_image( $label, $hex_bg = '#c8720f', $hex_fg = '#fdf6e8' ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}

	$size = 400;
	$im   = imagecreatetruecolor( $size, $size );

	list( $r1, $g1, $b1 ) = sscanf( $hex_bg, '#%02x%02x%02x' );
	list( $r2, $g2, $b2 ) = sscanf( $hex_fg, '#%02x%02x%02x' );
	$bg = imagecolorallocate( $im, $r1, $g1, $b1 );
	$fg = imagecolorallocate( $im, $r2, $g2, $b2 );
	imagefilledrectangle( $im, 0, 0, $size, $size, $bg );

	$initials = '';
	foreach ( preg_split( '/\s+/', trim( $label ) ) as $word ) {
		$initials .= mb_substr( $word, 0, 1 );
	}
	$initials = mb_strtoupper( mb_substr( $initials, 0, 2 ) );

	$font_size = 5;
	$text_w    = imagefontwidth( $font_size ) * strlen( $initials );
	$text_h    = imagefontheight( $font_size );
	imagestring( $im, $font_size, (int) ( ( $size - $text_w ) / 2 ), (int) ( ( $size - $text_h ) / 2 ), $initials, $fg );

	$upload = wp_upload_bits( sanitize_title( $label ) . '.png', null, '' );
	imagepng( $im, $upload['file'] );
	imagedestroy( $im );

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => $label,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

	return $attachment_id;
}

/**
 * Attach a real photo (not a generated placeholder) as a page's featured
 * image, by page ID — used for the three programme pages' client-supplied
 * photographs. Takes an ID rather than a slug because these pages are
 * nested under Our Work, so their path isn't just their own slug.
 * Safe to re-run: does nothing once a featured image is set.
 */
function ndingi_attach_programme_photo( $page_id, $file_path, $title ) {
	if ( ! $page_id || has_post_thumbnail( $page_id ) ) {
		return;
	}
	if ( ! file_exists( $file_path ) ) {
		echo "  (skipping photo for page {$page_id} \u{2014} file not found: {$file_path})\n";
		return;
	}

	$filetype = wp_check_filetype( basename( $file_path ) );
	$upload   = wp_upload_bits( basename( $file_path ), null, file_get_contents( $file_path ) );
	if ( ! empty( $upload['error'] ) ) {
		echo "  (upload failed for page {$page_id}: {$upload['error']})\n";
		return;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => $title,
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$page_id
	);
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	set_post_thumbnail( $page_id, $attachment_id );
}

function ndingi_seed_page( $title, $content, $parent_id = 0, $template = '', $meta = array() ) {
	$existing = ndingi_find_by_title( $title, 'page' );
	if ( $existing ) {
		return $existing->ID;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_parent'  => $parent_id,
		)
	);

	if ( $template ) {
		update_post_meta( $page_id, '_wp_page_template', $template );
	}
	foreach ( $meta as $key => $value ) {
		update_post_meta( $page_id, $key, $value );
	}

	return $page_id;
}

echo "Seeding pages…\n";

$home_id = ndingi_seed_page(
	'Home',
	'',
	0,
	'',
	array(
		'ndingi_hero_heading'  => 'Empowering Communities Through Lasting Impact',
		'ndingi_intro_heading' => "Upholding Archbishop Ndingi\u{2019}s Vision",
	)
);
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );

$about_id = ndingi_seed_page( 'About', '', 0, 'page-about.php' );

$who_we_are_content = "<p>The Archbishop Ndingi Mwana\u{2019}a Nzeki Foundation is dedicated to transforming lives across Kenya through education, sustainable community development, and climate-resilient water and ecosystem management.</p>\n<p>Inspired by the legacy of Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki, we work with communities and partners to create opportunities that improve livelihoods, strengthen resilience, and promote inclusive and sustainable development. Our work is delivered through three strategic programme areas and is made possible through the support of individuals, organisations, foundations, and development partners who share our vision of lasting positive impact.</p>\n<h2>Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki</h2>\n<p>Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki was born in Mwala, Machakos County, and ordained a priest on 31 January 1961. He served as National Education Secretary of the Kenya Episcopal Conference before becoming the first Bishop of Machakos (1969\u{2013}71), then Bishop of Nakuru for 25 years.</p>\n<p>He was appointed Coadjutor Archbishop of Nairobi in 1996, and served as Archbishop of Nairobi from 1997 to 2007. His lifelong commitment to education, community development, and stewardship of natural resources continues to guide the Foundation\u{2019}s mission today.</p>";
$who_we_are_id = ndingi_seed_page(
	'Who We Are',
	$who_we_are_content,
	$about_id,
	'page-who-we-are.php',
	array( 'ndingi_card_icon' => 'who-we-are', 'ndingi_card_blurb' => "Our mission, our story, and Archbishop Ndingi's enduring legacy." )
);

ndingi_seed_page(
	'Mission',
	"<p>To honour the legacy of Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki by transforming lives through education, climate-resilient water and ecosystem management, and community development that promotes sustainable livelihoods.</p>",
	$who_we_are_id,
	'',
	array( 'ndingi_card_icon' => 'mission', 'ndingi_card_blurb' => "Read the Foundation's mission statement." )
);

ndingi_seed_page(
	'Vision',
	'<p>To promote thriving communities.</p>',
	$who_we_are_id,
	'',
	array( 'ndingi_card_icon' => 'vision', 'ndingi_card_blurb' => 'Read our vision for the communities we serve.' )
);

ndingi_seed_page(
	'Core Values',
	'',
	$who_we_are_id,
	'page-core-values.php',
	array( 'ndingi_card_icon' => 'core-values', 'ndingi_card_blurb' => 'Explore the five values that guide our work.' )
);

ndingi_seed_page(
	'People',
	'',
	$about_id,
	'page-people.php',
	array( 'ndingi_card_icon' => 'people', 'ndingi_card_blurb' => 'Meet our Management Team, Trustees, and Board of Management.' )
);

$our_work_id = ndingi_seed_page( 'Our Work', '', 0, 'page-our-work.php' );

$education_id = ndingi_seed_page(
	'Education',
	"<p>We honour Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki\u{2019}s legacy by expanding access to education and lifelong learning that empowers individuals and strengthens communities.</p>\n<p>Our work creates opportunities for individuals to access education while equipping communities with practical knowledge and skills that promote self-reliance, sustainable livelihoods, and economic resilience.</p>\n<p>We believe education extends beyond the classroom. By fostering learning, innovation, and skills development, we enable individuals to realise their potential, improve their quality of life, and contribute meaningfully to the development of their communities and the nation.</p>",
	$our_work_id,
	'page-programme.php',
	array( 'ndingi_card_icon' => 'education', 'ndingi_card_blurb' => 'Expanding access to education and lifelong learning that builds self-reliance and economic resilience.' )
);

$livelihoods_id = ndingi_seed_page(
	'Sustainable Livelihoods',
	'<p>We believe lasting transformation is achieved when communities are at the centre of their own development. Our approach fosters meaningful participation, ensuring that communities actively shape, implement, and sustain the initiatives that improve their lives.</p>
<p>Through collaborative initiatives, including community-based livelihood projects such as apiculture, we equip individuals and groups with practical skills, knowledge, and enterprise opportunities that strengthen self-reliance and economic resilience. By combining capacity strengthening, innovation, and local ownership, we enable communities to build sustainable livelihoods while contributing to environmental conservation.</p>
<p>Working alongside communities and partners, we create solutions that deliver lasting social, economic, and environmental impact.</p>',
	$our_work_id,
	'page-programme.php',
	array( 'ndingi_card_icon' => 'sustainable-livelihoods', 'ndingi_card_blurb' => 'Community-led initiatives such as apiculture that strengthen self-reliance and economic resilience.' )
);

$water_id = ndingi_seed_page(
	'Water & Ecosystem Management',
	'<p>We advance integrated approaches to water, land, and ecosystem management that strengthen community resilience and support sustainable livelihoods. Our programme includes agroforestry, ecosystem restoration, improved access to safe water, sustainable irrigation, and climate-smart agriculture.</p>
<p>Through initiatives such as our Longonot water project, tree planting, and the cultivation of high-value crops, we help restore degraded landscapes, improve soil and water resources, enhance food security, and create economic opportunities for communities. By protecting natural resources today, we contribute to a healthier environment and a more resilient future for generations to come.</p>',
	$our_work_id,
	'page-programme.php',
	array( 'ndingi_card_icon' => 'water-ecosystem-management', 'ndingi_card_blurb' => 'Agroforestry, ecosystem restoration, and safe water access, including the Longonot water project.' )
);

echo "Attaching programme photographs…\n";
$programme_media_dir = __DIR__ . '/media/programmes';
ndingi_attach_programme_photo( $education_id, $programme_media_dir . '/education.jpg', 'Education' );
ndingi_attach_programme_photo( $livelihoods_id, $programme_media_dir . '/livelihoods.jpg', 'Sustainable Livelihoods' );
ndingi_attach_programme_photo( $water_id, $programme_media_dir . '/water.jpg', 'Water & Ecosystem Management' );

ndingi_seed_page( 'Partners', '', 0, 'page-partners.php' );

$resources_id = ndingi_seed_page( 'Resources', '', 0, 'page-resources.php' );

ndingi_seed_page(
	'News',
	'',
	$resources_id,
	'page-news.php',
	array( 'ndingi_card_icon' => 'news', 'ndingi_card_blurb' => 'Announcements and updates on our ongoing work.' )
);

ndingi_seed_page(
	'Publications',
	'',
	$resources_id,
	'page-publications.php',
	array( 'ndingi_card_icon' => 'publications', 'ndingi_card_blurb' => 'Reports, materials, and documents from the Foundation.' )
);

ndingi_seed_page( 'Donate', '', 0, 'page-donate.php' );
ndingi_seed_page( 'Contact', '', 0, 'page-contact.php' );
ndingi_seed_page( 'Management Team', '', 0, 'page-team-roster.php' );
ndingi_seed_page( 'Trustees', '', 0, 'page-team-roster.php' );
ndingi_seed_page( 'Board of Management', '', 0, 'page-team-roster.php' );

echo "Seeding core values…\n";
$core_values = array(
	array( 'Empowerment', 'Building knowledge, skills, and opportunity.' ),
	array( 'Sustainability', 'Creating lasting positive impact for people and the environment.' ),
	array( 'Stewardship', 'Protecting and responsibly managing natural resources.' ),
	array( 'Service', 'Putting communities at the center of our work.' ),
	array( 'Integrity', 'Acting with transparency and accountability.' ),
);
foreach ( $core_values as $i => $value ) {
	list( $name, $description ) = $value;
	if ( ndingi_find_by_title( $name, 'core_value' ) ) {
		continue;
	}
	wp_insert_post(
		array(
			'post_type'    => 'core_value',
			'post_title'   => $name,
			'post_excerpt' => $description,
			'post_status'  => 'publish',
			'menu_order'   => $i,
		)
	);
}

echo "Seeding placeholder team members…\n";
$rosters = array(
	'management' => array( 'Sample Chief Executive Officer', 'Sample Finance Manager' ),
	'trustees'   => array( 'Sample Trustee One', 'Sample Trustee Two' ),
	'board'      => array( 'Sample Board Chair', 'Sample Board Treasurer' ),
);
$roles = array(
	'Sample Chief Executive Officer' => 'Chief Executive Officer (placeholder — replace with real bio)',
	'Sample Finance Manager'         => 'Finance Manager (placeholder — replace with real bio)',
	'Sample Trustee One'             => 'Trustee (placeholder — replace with real bio)',
	'Sample Trustee Two'             => 'Trustee (placeholder — replace with real bio)',
	'Sample Board Chair'             => 'Board Chair (placeholder — replace with real bio)',
	'Sample Board Treasurer'         => 'Board Treasurer (placeholder — replace with real bio)',
);
foreach ( $rosters as $roster_slug => $names ) {
	foreach ( $names as $i => $name ) {
		if ( ndingi_find_by_title( $name, 'team_member' ) ) {
			continue;
		}
		$member_id = wp_insert_post(
			array(
				'post_type'    => 'team_member',
				'post_title'   => $name,
				'post_content' => '<p>Placeholder bio — replace with a real biography once the client provides one.</p>',
				'post_status'  => 'publish',
				'menu_order'   => $i,
			)
		);
		update_post_meta( $member_id, 'ndingi_role', $roles[ $name ] );
		wp_set_object_terms( $member_id, $roster_slug, 'ndingi_roster' );
		$image_id = ndingi_seed_placeholder_image( $name );
		if ( $image_id ) {
			set_post_thumbnail( $member_id, $image_id );
		}
	}
}

echo "Seeding a sample news post…\n";
if ( ! ndingi_find_by_title( 'Sample News Post — Replace With Real Content', 'post' ) ) {
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_title'   => 'Sample News Post — Replace With Real Content',
			'post_content' => '<p>This is placeholder news content so the News list and single post template have something to render. Replace it with the Foundation\'s first real announcement.</p>',
			'post_status'  => 'publish',
		)
	);
	$image_id = ndingi_seed_placeholder_image( 'News' );
	if ( $image_id ) {
		set_post_thumbnail( $post_id, $image_id );
	}
}

echo "Seeding a sample publication…\n";
if ( ! ndingi_find_by_title( 'Sample Publication — Replace With Real Content', 'publication' ) ) {
	$pub_id = wp_insert_post(
		array(
			'post_type'    => 'publication',
			'post_title'   => 'Sample Publication — Replace With Real Content',
			'post_excerpt' => 'Placeholder description for a downloadable report or document.',
			'post_status'  => 'publish',
		)
	);
	update_post_meta( $pub_id, 'ndingi_link_type', 'external' );
	update_post_meta( $pub_id, 'ndingi_external_url', 'https://example.com/sample-report.pdf' );
	wp_set_object_terms( $pub_id, 'Annual Report', 'publication_category' );
}

echo "Done.\n";
