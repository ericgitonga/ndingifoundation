<?php
/**
 * A one-click "create the site's page hierarchy" admin tool — the
 * production-safe equivalent of local-preview/seed.php's page creation
 * (same titles, templates, parents, and real copy), without that script's
 * placeholder team members/news/publication. Exists so a page missing on a
 * real host (no WP-CLI/SSH access) can be created from wp-admin instead of
 * by hand, and re-running it is always safe: any page already found by
 * title is left untouched.
 *
 * Functions here are prefixed ndingi_page_setup_* (not ndingi_seed_*) so
 * they never collide with local-preview/seed.php's identically-shaped
 * functions when both load in the same request (e.g. `wp eval-file` against
 * a site with this plugin active).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndingi_page_setup_find_by_title( $title, $post_type ) {
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

function ndingi_page_setup_create_page( $title, $content, $parent_id = 0, $template = '', $meta = array() ) {
	$existing = ndingi_page_setup_find_by_title( $title, 'page' );
	if ( $existing ) {
		return array( $existing->ID, false );
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

	return array( $page_id, true );
}

/**
 * Ensure an image bundled in the theme is registered in the Media Library
 * (matched by title so re-running never creates duplicates) and return its
 * public URL (falling back to the theme asset URI if upload fails).
 */
function ndingi_page_setup_ensure_media( $rel_theme_path, $title, $parent_id = 0 ) {
	$existing = ndingi_page_setup_find_by_title( $title, 'attachment' );
	if ( $existing ) {
		$url = wp_get_attachment_url( $existing->ID );
		if ( $url ) {
			return $url;
		}
	}

	$file_path = get_template_directory() . $rel_theme_path;
	if ( ! file_exists( $file_path ) ) {
		return get_template_directory_uri() . $rel_theme_path;
	}

	$filetype = wp_check_filetype( basename( $file_path ) );
	$upload   = wp_upload_bits( basename( $file_path ), null, file_get_contents( $file_path ) );
	if ( ! empty( $upload['error'] ) ) {
		return get_template_directory_uri() . $rel_theme_path;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => $title,
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$parent_id
	);
	if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
		$url = wp_get_attachment_url( $attachment_id );
		if ( $url ) {
			return $url;
		}
	}

	return get_template_directory_uri() . $rel_theme_path;
}

/**
 * Build the HTML gallery block for Archbishop Ndingi's portraits on Who We Are.
 */
function ndingi_page_setup_founder_gallery_html( $parent_id = 0 ) {
	$portrait_1 = esc_url( ndingi_page_setup_ensure_media( '/assets/images/archbishop-ndingi-portrait.jpg', "Archbishop Raphael Simon Ndingi Mwana'a Nzeki — Seated Portrait", $parent_id ) );
	$portrait_2 = esc_url( ndingi_page_setup_ensure_media( '/assets/images/archbishop-ndingi-star.png', "Archbishop Raphael Simon Ndingi Mwana'a Nzeki — Portrait", $parent_id ) );

	return sprintf(
		"<div class=\"founder-gallery\">\n<figure><img src=\"%s\" alt=\"Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki\" /><figcaption>Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki</figcaption></figure>\n<figure><img src=\"%s\" alt=\"Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki\" /><figcaption>Archbishop Ndingi Mwana\u{2019}a Nzeki (1931\u{2013}2020)</figcaption></figure>\n</div>",
		$portrait_1,
		$portrait_2
	);
}

/**
 * Creates every page ndingi-wp expects (or leaves it alone if already
 * present, matched by title) plus the five real Core Values entries.
 * Deliberately excludes local-preview/seed.php's placeholder team
 * members/news post/publication — those need the client's real content,
 * added via wp-admin, not sample data on a live site.
 *
 * @return array{created: string[], existing: string[]}
 */
function ndingi_page_setup_run() {
	$created  = array();
	$existing = array();
	$note     = function ( $title, $was_created ) use ( &$created, &$existing ) {
		if ( $was_created ) {
			$created[] = $title;
		} else {
			$existing[] = $title;
		}
	};

	list( $home_id, $c ) = ndingi_page_setup_create_page(
		'Home',
		'',
		0,
		'',
		array(
			'ndingi_hero_heading'  => 'Empowering Communities Through Lasting Impact',
			'ndingi_intro_heading' => "Upholding Archbishop Ndingi\u{2019}s Vision",
		)
	);
	$note( 'Home', $c );
	if ( 'page' !== get_option( 'show_on_front' ) || (int) get_option( 'page_on_front' ) !== $home_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
	}

	list( $about_id, $c ) = ndingi_page_setup_create_page( 'About', '', 0, 'page-about.php' );
	$note( 'About', $c );

	$founder_gallery = ndingi_page_setup_founder_gallery_html();
	$who_we_are_content = "<p>The Archbishop Ndingi Mwana\u{2019}a Nzeki Foundation is dedicated to transforming lives across Kenya through education, sustainable community development, and climate-resilient water and ecosystem management.</p>\n<p>Inspired by the legacy of Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki, we work with communities and partners to create opportunities that improve livelihoods, strengthen resilience, and promote inclusive and sustainable development. Our work is delivered through three strategic programme areas and is made possible through the support of individuals, organisations, foundations, and development partners who share our vision of lasting positive impact.</p>\n<h2>Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki</h2>\n{$founder_gallery}\n<p>Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki was born in Mwala, Machakos County, and ordained a priest on 31 January 1961. He served as National Education Secretary of the Kenya Episcopal Conference before becoming the first Bishop of Machakos (1969\u{2013}71), then Bishop of Nakuru for 25 years.</p>\n<p>He was appointed Coadjutor Archbishop of Nairobi in 1996, and served as Archbishop of Nairobi from 1997 to 2007. His lifelong commitment to education, community development, and stewardship of natural resources continues to guide the Foundation\u{2019}s mission today.</p>";
	list( $who_we_are_id, $c ) = ndingi_page_setup_create_page(
		'Who We Are',
		$who_we_are_content,
		$about_id,
		'page-who-we-are.php',
		array( 'ndingi_card_icon' => 'who-we-are', 'ndingi_card_blurb' => "Our mission, our story, and Archbishop Ndingi's enduring legacy." )
	);
	if ( ! $c && $who_we_are_id ) {
		$existing_wwa = get_post( $who_we_are_id );
		if ( $existing_wwa && false === strpos( $existing_wwa->post_content, 'founder-gallery' ) && false === strpos( $existing_wwa->post_content, '<img' ) ) {
			$updated_wwa = preg_replace(
				'/(<h[23][^>]*>Archbishop Raphael Simon Ndingi[^<]*<\/h[23]>)/u',
				"$1\n" . $founder_gallery,
				$existing_wwa->post_content,
				1,
				$replaced
			);
			if ( ! $replaced ) {
				$updated_wwa = $existing_wwa->post_content . "\n" . $founder_gallery;
			}
			wp_update_post(
				array(
					'ID'           => $who_we_are_id,
					'post_content' => $updated_wwa,
				)
			);
		}
	}
	$note( 'Who We Are', $c );

	list( , $c ) = ndingi_page_setup_create_page(
		'Mission',
		"<p>To honour the legacy of Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki by transforming lives through education, climate-resilient water and ecosystem management, and community development that promotes sustainable livelihoods.</p>",
		$who_we_are_id,
		'',
		array( 'ndingi_card_icon' => 'mission', 'ndingi_card_blurb' => "Read the Foundation's mission statement." )
	);
	$note( 'Mission', $c );

	list( , $c ) = ndingi_page_setup_create_page(
		'Vision',
		'<p>To promote thriving communities.</p>',
		$who_we_are_id,
		'',
		array( 'ndingi_card_icon' => 'vision', 'ndingi_card_blurb' => 'Read our vision for the communities we serve.' )
	);
	$note( 'Vision', $c );

	list( , $c ) = ndingi_page_setup_create_page(
		'Core Values',
		'',
		$who_we_are_id,
		'page-core-values.php',
		array( 'ndingi_card_icon' => 'core-values', 'ndingi_card_blurb' => 'Explore the five values that guide our work.' )
	);
	$note( 'Core Values', $c );

	list( , $c ) = ndingi_page_setup_create_page(
		'People',
		'',
		$about_id,
		'page-people.php',
		array( 'ndingi_card_icon' => 'people', 'ndingi_card_blurb' => 'Meet our Management Team, Trustees, and Board of Management.' )
	);
	$note( 'People', $c );

	list( $our_work_id, $c ) = ndingi_page_setup_create_page( 'Our Work', '', 0, 'page-our-work.php' );
	$note( 'Our Work', $c );

	list( , $c ) = ndingi_page_setup_create_page(
		'Education',
		"<p>We honour Archbishop Raphael Simon Ndingi Mwana\u{2019}a Nzeki\u{2019}s legacy by expanding access to education and lifelong learning that empowers individuals and strengthens communities.</p>\n<p>Our work creates opportunities for individuals to access education while equipping communities with practical knowledge and skills that promote self-reliance, sustainable livelihoods, and economic resilience.</p>\n<p>We believe education extends beyond the classroom. By fostering learning, innovation, and skills development, we enable individuals to realise their potential, improve their quality of life, and contribute meaningfully to the development of their communities and the nation.</p>",
		$our_work_id,
		'page-programme.php',
		array( 'ndingi_card_icon' => 'education', 'ndingi_card_blurb' => 'Expanding access to education and lifelong learning that builds self-reliance and economic resilience.' )
	);
	$note( 'Education', $c );

	list( , $c ) = ndingi_page_setup_create_page(
		'Sustainable Livelihoods',
		'<p>We believe lasting transformation is achieved when communities are at the centre of their own development. Our approach fosters meaningful participation, ensuring that communities actively shape, implement, and sustain the initiatives that improve their lives.</p>
<p>Through collaborative initiatives, including community-based livelihood projects such as apiculture, we equip individuals and groups with practical skills, knowledge, and enterprise opportunities that strengthen self-reliance and economic resilience. By combining capacity strengthening, innovation, and local ownership, we enable communities to build sustainable livelihoods while contributing to environmental conservation.</p>
<p>Working alongside communities and partners, we create solutions that deliver lasting social, economic, and environmental impact.</p>',
		$our_work_id,
		'page-programme.php',
		array( 'ndingi_card_icon' => 'sustainable-livelihoods', 'ndingi_card_blurb' => 'Community-led initiatives such as apiculture that strengthen self-reliance and economic resilience.' )
	);
	$note( 'Sustainable Livelihoods', $c );

	list( , $c ) = ndingi_page_setup_create_page(
		'Water & Ecosystem Management',
		'<p>We advance integrated approaches to water, land, and ecosystem management that strengthen community resilience and support sustainable livelihoods. Our programme includes agroforestry, ecosystem restoration, improved access to safe water, sustainable irrigation, and climate-smart agriculture.</p>
<p>Through initiatives such as our Longonot water project, tree planting, and the cultivation of high-value crops, we help restore degraded landscapes, improve soil and water resources, enhance food security, and create economic opportunities for communities. By protecting natural resources today, we contribute to a healthier environment and a more resilient future for generations to come.</p>',
		$our_work_id,
		'page-programme.php',
		array( 'ndingi_card_icon' => 'water-ecosystem-management', 'ndingi_card_blurb' => 'Agroforestry, ecosystem restoration, and safe water access, including the Longonot water project.' )
	);
	$note( 'Water & Ecosystem Management', $c );

	list( , $c ) = ndingi_page_setup_create_page( 'Partners', '', 0, 'page-partners.php' );
	$note( 'Partners', $c );

	list( $resources_id, $c ) = ndingi_page_setup_create_page( 'Resources', '', 0, 'page-resources.php' );
	$note( 'Resources', $c );

	list( , $c ) = ndingi_page_setup_create_page(
		'News',
		'',
		$resources_id,
		'page-news.php',
		array( 'ndingi_card_icon' => 'news', 'ndingi_card_blurb' => 'Announcements and updates on our ongoing work.' )
	);
	$note( 'News', $c );

	list( , $c ) = ndingi_page_setup_create_page(
		'Publications',
		'',
		$resources_id,
		'page-publications.php',
		array( 'ndingi_card_icon' => 'publications', 'ndingi_card_blurb' => 'Reports, materials, and documents from the Foundation.' )
	);
	$note( 'Publications', $c );

	list( , $c ) = ndingi_page_setup_create_page( 'Donate', '', 0, 'page-donate.php' );
	$note( 'Donate', $c );
	list( , $c ) = ndingi_page_setup_create_page( 'Contact', '', 0, 'page-contact.php' );
	$note( 'Contact', $c );
	list( , $c ) = ndingi_page_setup_create_page( 'Management Team', '', 0, 'page-team-roster.php' );
	$note( 'Management Team', $c );
	list( , $c ) = ndingi_page_setup_create_page( 'Trustees', '', 0, 'page-team-roster.php' );
	$note( 'Trustees', $c );
	// Renamed from "Board of Directors" per client feedback (2026-09-15) — if
	// that page was already created under the old title on a host that ran
	// this tool before the rename, update it in place instead of creating a
	// duplicate (ndingi_page_setup_create_page() matches by exact title, so
	// it wouldn't find the old one under the new name).
	$board_page = ndingi_page_setup_find_by_title( 'Board of Directors', 'page' );
	if ( $board_page ) {
		wp_update_post( array( 'ID' => $board_page->ID, 'post_title' => 'Board of Management' ) );
		$note( 'Board of Management', false );
	} else {
		list( , $c ) = ndingi_page_setup_create_page( 'Board of Management', '', 0, 'page-team-roster.php' );
		$note( 'Board of Management', $c );
	}

	$core_values = array(
		array( 'Empowerment', 'Building knowledge, skills, and opportunity.' ),
		array( 'Sustainability', 'Creating lasting positive impact for people and the environment.' ),
		array( 'Stewardship', 'Protecting and responsibly managing natural resources.' ),
		array( 'Service', 'Putting communities at the center of our work.' ),
		array( 'Integrity', 'Acting with transparency and accountability.' ),
	);
	foreach ( $core_values as $i => $value ) {
		list( $name, $description ) = $value;
		if ( ndingi_page_setup_find_by_title( $name, 'core_value' ) ) {
			$existing[] = "Core Value: {$name}";
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
		$created[] = "Core Value: {$name}";
	}

	$default_advisors_data = array(
		array(
			'name'     => 'NIC Bank',
			'category' => 'Bankers',
			'details'  => "The Mall, Westlands Branch, Nairobi",
			'url'      => '',
			'order'    => 0,
		),
		array(
			'name'     => 'Caritas Microfinance Bank',
			'category' => 'Bankers',
			'details'  => "Cardinal Otunga Plaza Branch, Nairobi",
			'url'      => '',
			'order'    => 1,
		),
		array(
			'name'     => 'Kigundu & Company',
			'category' => 'Auditors',
			'details'  => "Certified Public Accountants (Kenya)\nP.O. Box 2309-00606, Sarit Centre, Nairobi",
			'url'      => '',
			'order'    => 2,
		),
		array(
			'name'     => 'Taxwise Africa Consulting LLP',
			'category' => 'Tax Consultants',
			'details'  => "9th Floor Barclays Plaza, Loita Street\nP.O. Box 9539-00100, Nairobi",
			'url'      => 'https://www.taxwise-consulting.com',
			'order'    => 3,
		),
		array(
			'name'     => 'Nyiha, Mukoma & Company Advocates',
			'category' => 'Lawyers',
			'details'  => "Old Mutual Building, 3rd Floor, Kimathi Street\nP.O. Box 28491-00200, Nairobi",
			'url'      => '',
			'order'    => 4,
		),
	);

	foreach ( $default_advisors_data as $adv ) {
		if ( ndingi_page_setup_find_by_title( $adv['name'], 'partner' ) ) {
			$existing[] = "Partner: {$adv['name']}";
			continue;
		}
		$adv_id = wp_insert_post(
			array(
				'post_type'    => 'partner',
				'post_title'   => $adv['name'],
				'post_excerpt' => $adv['details'],
				'post_status'  => 'publish',
				'menu_order'   => $adv['order'],
			)
		);
		if ( $adv_id && ! is_wp_error( $adv_id ) ) {
			update_post_meta( $adv_id, 'ndingi_partner_details', $adv['details'] );
			if ( ! empty( $adv['url'] ) ) {
				update_post_meta( $adv_id, 'ndingi_partner_url', $adv['url'] );
				update_post_meta( $adv_id, 'ndingi_partner_url_label', $adv['url'] );
			}
			wp_set_object_terms( $adv_id, $adv['category'], 'partner_category' );
			$created[] = "Partner: {$adv['name']}";
		}
	}

	// Normalize Trustee & Board of Management titles/names per client feedback:
	// 1. "Bishop Dominic Kimengich" -> "Archbishop Dominic Kimengich"
	// 2. "Mr. James Nyiha" -> "James Nyiha"
	// 3. Ensure "Fr. Antony Mwituria" appears on both the Trustees ('trustees') and Board of Management ('board') rosters.
	$member_renames = array(
		'Bishop Dominic Kimengich' => 'Archbishop Dominic Kimengich',
		'Mr. James Nyiha'          => 'James Nyiha',
	);
	foreach ( $member_renames as $old_name => $new_name ) {
		$member_post = ndingi_page_setup_find_by_title( $old_name, 'team_member' );
		if ( $member_post ) {
			wp_update_post(
				array(
					'ID'         => $member_post->ID,
					'post_title' => $new_name,
					'post_name'  => sanitize_title( $new_name ),
				)
			);
		}
	}

	$fr_antony = ndingi_page_setup_find_by_title( 'Fr. Antony Mwituria', 'team_member' );
	if ( $fr_antony ) {
		wp_set_object_terms( $fr_antony->ID, array( 'trustees', 'board' ), 'ndingi_roster', false );
	}

	// Idempotently backfill missing Trustee & Board of Management biographies
	// recovered from the legacy WordPress database export (#20). Never overwrites
	// any team_member post that already has biographical text in post_content.
	$member_bios = array(
		'Archbishop Dominic Kimengich' => "<p>Archbishop Dominic Kimengich is the current Bishop of Lodwar, Kenya. He was born in 1961 and ordained priest on the 14th of September 1986. He has served as a priest in the Diocese of Nakuru and was the rector at St. Joseph Junior Seminary, a Catholic secondary school for boys in Molo, Kenya. In 2001, he was appointed the Vicar General of the Diocese of Nakuru. From 2006 through 2008 he served as Rector of St. Matthias Mulumba Major Seminary in Tindinyo, overseeing the formation of 170 seminarians. He then served at the Kenya Catholic Secretariat in Nairobi, serving the entire Church in Kenya in canonical issues by offering legal/canonical advice to the bishops, clergy, laity and religious. Prior to his appointment as auxiliary bishop, he was Judge of the Diocesan Ecclesiastical Tribunal of Nakuru and responsible for sourcing money for the Canon Law Office and ensuring the establishment of functional tribunals in the country.</p>\n<p>On March 5, 2011 Pope Benedict XVI appointed him Bishop of the Diocese of Lodwar upon the retirement of Bishop Patrick Harrington. He was installed as Bishop on March 30, 2011 at the Cathedral in Lodwar.</p>",
		'James Nyiha'                  => "<p>James Nyiha is an advocate of the High Court of Kenya admitted to the bar in 1993. He holds a Bachelor of Laws degree from the University of Nairobi with a postgraduate diploma from the Kenya School of Law. In 2009, he attended the prestigious Advanced Management Program organised by the Strathmore Business School together with IESE Business School (Spain) and Lagos Business School (Nigeria) and in 2010 he attended The Master Negotiator Programme organised by Strathmore Business School.</p>\n<p>Whilst at the University of Nairobi, James Nyiha received the Hamilton Harrison &amp; Matthews Award for the Best First Year Law Student and the prestigious Kaplan and Stratton Award for being the best overall student in the law degree programme for his class intake.</p>\n<p>He has special expertise in negotiations, alternative dispute resolution, securities services creation and their realisation, amalgamation and winding up of companies, loan recovery and drafting of commercial and residential leases. In litigation he handles various types of cases ranging from foreclosure suits for banks to disputes arising from contracts and arbitrations arising from building contracts.</p>\n<p>He also serves as the Chief Prosecutor at the Advocates Disciplinary Committee established under the Advocates Act where he prosecutes on behalf of the Law Society of Kenya and as a Public Prosecutor appointed as such by the Honourable Attorney General of Kenya for offences committed under the Advocates Act.</p>",
		'Lawrence Omonyo'              => "<p>Lawrence Omonyo is a Consultant Quantity Surveyor and an arbitrator. He is the Managing Director of Laurez &amp; Associates and has over 10 years\u{2019} experience in Quantity Surveying. He holds Bachelor\u{2019}s Degrees in Building Economics (Quantity Surveying) and Law (LLB) from the University of Nairobi. He also holds a Post-graduate Diploma from Kenya School of Law. He is a member of the Chartered Institute of Arbitrators, London and Kenya Chapter. He is also a corporate member of the Architectural Association of Kenya (AAK) and Institute of Quantity Surveyors of Kenya (IQSK).</p>",
		'Arch. Peter Opon'             => '<p>Architect Peter N. Opon has a track record of successful project management for architectural projects both at the public and private sector levels. His leadership qualities and contract management skills as the project architect for several projects undertaken by the firm have left Clients satisfied with the quality and speed of deliverance prompting them to award the firm subsequent architectural consultancies.</p>',
		'Claire Wanjiru Mwangi'        => "<p>Ms. Claire Mwangi has over 18 years\u{2019} experience in the financial services sector, having worked with Toronto Dominion Bank in NY in project finance, Citibank NA in Kenya and Tanzania within Global Markets, and Stanbic Bank as Executive Director Corporate and Investment Bank, as well as Regional Director Global Markets, covering East Africa and Mauritius.</p>\n<p>She holds a Bachelor\u{2019}s degree from Amherst College, USA and an MBA from the Wharton School, University of Pennsylvania, USA. She is currently a non-executive Director on the boards of Heritage Insurance, CfC Life and Stanlib. She is also on the advisory Committee of 46 Parallels, a pan-African investment partnership. She runs Antennae Holdings, an Agribusiness firm, and is also a Director of Grove Limited.</p>",
		'Francis Ngigi Njoroge'        => "<p>Francis is a graduate of KCA University, having obtained a Bachelor of Commerce in Finance. He is also a Certified Public Accountant and has developed a special interest in tax matters. He worked as an intern at Pacis Insurance Company Limited before joining the Archdiocese of Nairobi where he held various positions in the finance department.</p>\n<p>Francis has the distinction of being the first beneficiary of the Archbishop Ndingi Mwana\u{2019}a Nzeki Foundation and has set the bar very high for future beneficiaries. He has also since become one of the benefactors of the Foundation.</p>\n<p>The board is very proud to have him and is confident that his energy and new ideas will be a significant plus for the foundation.</p>",
		'Vivian Nyambura'              => "<p>Vivian is one of three new board members recently appointed by the board to inject youthful energy and ideas.</p>\n<p>She is a graduate of Alliance Girls High School and is currently pursuing a Bachelor of Commerce Degree at Strathmore University. She is also working towards being a Certified Public Accountant. Vivian got some work experience in the finance department of the Archdiocese of Nairobi and at Crown Paints Kenya Plc.</p>\n<p>A key consideration in her appointment is her participation in extra-curricular activities and the many leadership positions she has held both in school and in her local community. The Board is confident to find in Vivian a positive contribution to the objectives of the Foundation.</p>",
	);
	foreach ( $member_bios as $member_name => $bio_html ) {
		$member_post = ndingi_page_setup_find_by_title( $member_name, 'team_member' );
		if ( ! $member_post ) {
			continue;
		}
		if ( '' === trim( $member_post->post_content ) ) {
			wp_update_post(
				array(
					'ID'           => $member_post->ID,
					'post_content' => wp_slash( $bio_html ),
				)
			);
			$created[] = "Bio: {$member_name}";
		} else {
			$existing[] = "Bio: {$member_name}";
		}
	}

	return array(
		'created'  => $created,
		'existing' => $existing,
	);
}

function ndingi_page_setup_admin_menu() {
	add_management_page(
		'Ndingi Page Setup',
		'Ndingi Page Setup',
		'manage_options',
		'ndingi-page-setup',
		'ndingi_page_setup_render_admin_page'
	);
}
add_action( 'admin_menu', 'ndingi_page_setup_admin_menu' );

function ndingi_page_setup_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$result = null;
	if ( isset( $_POST['ndingi_page_setup_run'] ) && check_admin_referer( 'ndingi_page_setup' ) ) {
		if ( ! function_exists( 'ndingi_get_child_pages' ) ) {
			echo '<div class="notice notice-error"><p>Refusing to run: the "Ndingi Foundation Content" plugin functions aren\'t loaded (is this file being included correctly?).</p></div>';
		} else {
			$result = ndingi_page_setup_run();
		}
	}
	?>
	<div class="wrap">
		<h1>Ndingi Page Setup</h1>
		<p>
			Creates every page this theme expects (About, Our Work, Resources, and all
			their children) with the real Foundation copy and the correct template
			assigned, plus the five Core Values entries and any missing Trustee / Board
			of Management biographies. Safe to run more than once — anything already
			populated is left untouched, nothing is ever overwritten or deleted.
		</p>
		<?php if ( $result ) : ?>
			<div class="notice notice-success">
				<p><strong>Created (<?php echo count( $result['created'] ); ?>):</strong>
					<?php echo $result['created'] ? esc_html( implode( ', ', $result['created'] ) ) : '<em>none — everything already existed</em>'; ?>
				</p>
				<p><strong>Already existed, left alone (<?php echo count( $result['existing'] ); ?>):</strong>
					<?php echo $result['existing'] ? esc_html( implode( ', ', $result['existing'] ) ) : '<em>none</em>'; ?>
				</p>
			</div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'ndingi_page_setup' ); ?>
			<p><button type="submit" name="ndingi_page_setup_run" value="1" class="button button-primary">Create / Repair Site Pages</button></p>
		</form>
	</div>
	<?php
}
