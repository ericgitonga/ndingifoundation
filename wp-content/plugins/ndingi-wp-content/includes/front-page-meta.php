<?php
/**
 * "Homepage Content" meta box — the hero/intro/CTA copy on the front page,
 * so an editor can tweak it without a code change. Assign this same meta
 * box's page as the site's front page under Settings > Reading.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndingi_front_page_fields() {
	return array(
		'ndingi_hero_heading'     => array( 'label' => 'Hero heading', 'type' => 'text' ),
		'ndingi_hero_subtext'     => array( 'label' => 'Hero subtext', 'type' => 'textarea' ),
		'ndingi_hero_btn1_text'   => array( 'label' => 'Hero primary button text (default: Who We Are)', 'type' => 'text' ),
		'ndingi_hero_btn1_url'    => array( 'label' => 'Hero primary button URL (default: /who-we-are/)', 'type' => 'text' ),
		'ndingi_hero_btn2_text'   => array( 'label' => 'Hero secondary button text (default: Our Work)', 'type' => 'text' ),
		'ndingi_hero_btn2_url'    => array( 'label' => 'Hero secondary button URL (default: /our-work/)', 'type' => 'text' ),
		'ndingi_intro_heading'    => array( 'label' => 'Intro heading', 'type' => 'text' ),
		'ndingi_intro_body'       => array( 'label' => 'Intro body (one paragraph per line)', 'type' => 'textarea' ),
		'ndingi_work_title'       => array( 'label' => 'Our Work section heading (default: Our Work)', 'type' => 'text' ),
		'ndingi_work_intro'       => array( 'label' => 'Our Work section intro blurb', 'type' => 'textarea' ),
		'ndingi_cta_heading'      => array( 'label' => 'Legacy CTA heading', 'type' => 'text' ),
		'ndingi_cta_body'         => array( 'label' => 'Legacy CTA body', 'type' => 'textarea' ),
		'ndingi_cta_btn_text'     => array( 'label' => 'Legacy CTA button text (default: Discover More)', 'type' => 'text' ),
		'ndingi_cta_btn_url'      => array( 'label' => 'Legacy CTA button URL (default: /who-we-are/)', 'type' => 'text' ),
		'ndingi_contact_heading'  => array( 'label' => 'Get In Touch section heading (default: Get In Touch)', 'type' => 'text' ),
		'ndingi_contact_body'     => array( 'label' => 'Get In Touch body text', 'type' => 'textarea' ),
		'ndingi_contact_btn_text' => array( 'label' => 'Get In Touch button text (default: Contact Us)', 'type' => 'text' ),
	);
}

function ndingi_front_page_metabox() {
	global $post;
	if ( ! $post || 'page' !== $post->post_type ) {
		return;
	}
	$front_page_id = (int) get_option( 'page_on_front' );
	$is_front      = ( $front_page_id && $front_page_id === $post->ID );
	$is_home_slug  = ( 'home' === $post->post_name );
	if ( ! $is_front && ! $is_home_slug ) {
		return;
	}
	add_meta_box(
		'ndingi_front_page_content',
		'Homepage Content',
		'ndingi_render_front_page_metabox',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ndingi_front_page_metabox' );

function ndingi_render_front_page_metabox( $post ) {
	$front_page_id = (int) get_option( 'page_on_front' );
	$is_front      = ( $front_page_id && $front_page_id === $post->ID );
	$is_home_slug  = ( 'home' === $post->post_name );
	if ( ! $is_front && ! $is_home_slug ) {
		return;
	}
	wp_nonce_field( 'ndingi_save_front_page', 'ndingi_front_page_nonce' );
	foreach ( ndingi_front_page_fields() as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label><br />';
		if ( 'textarea' === $field['type'] ) {
			echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="3" style="width:100%;">' . esc_textarea( $value ) . '</textarea>';
		} else {
			echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" style="width:100%;" />';
		}
		echo '</p>';
	}
}

function ndingi_save_front_page( $post_id ) {
	if ( ! isset( $_POST['ndingi_front_page_nonce'] ) ||
		! wp_verify_nonce( $_POST['ndingi_front_page_nonce'], 'ndingi_save_front_page' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( ndingi_front_page_fields() as $key => $field ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$value = wp_unslash( $_POST[ $key ] );
		update_post_meta( $post_id, $key, 'textarea' === $field['type'] ? sanitize_textarea_field( $value ) : sanitize_text_field( $value ) );
	}
}
add_action( 'save_post_page', 'ndingi_save_front_page' );
