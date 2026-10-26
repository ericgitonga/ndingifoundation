<?php
/**
 * Native contact form handler — replaces ndingi's client-side Web3Forms
 * call (src/lib/web3forms.ts) with a first-party wp_mail() submission.
 * No third-party service or API key needed now that mail can be sent
 * directly from the host (or via an SMTP plugin if the host's default
 * mail() is unreliable, common on shared hosting — see README).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndingi_handle_contact_submission() {
	if ( ! isset( $_POST['ndingi_contact_nonce'] ) ||
		! wp_verify_nonce( $_POST['ndingi_contact_nonce'], 'ndingi_contact_form' ) ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'error', wp_get_referer() ?: home_url( '/contact/' ) ) );
		exit;
	}

	// Honeypot: a real visitor never fills this in — same tactic as the
	// original's `botcheck` field, silently accepted rather than flagged.
	if ( ! empty( $_POST['botcheck'] ) ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'success', wp_get_referer() ?: home_url( '/contact/' ) ) );
		exit;
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	$redirect_to = wp_get_referer() ?: home_url( '/contact/' );

	if ( ! $name || ! is_email( $email ) || ! $message ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'error', $redirect_to ) );
		exit;
	}

	$to        = get_option( 'admin_email' );
	$mail_subject = sprintf( '[Contact form] %s', $subject ?: 'New message from ' . $name );
	$body      = "Name: {$name}\nEmail: {$email}\n\n{$message}";
	$headers   = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

	$sent = wp_mail( $to, $mail_subject, $body, $headers );

	wp_safe_redirect( add_query_arg( 'ndingi_contact', $sent ? 'success' : 'error', $redirect_to ) );
	exit;
}
add_action( 'admin_post_ndingi_contact', 'ndingi_handle_contact_submission' );
add_action( 'admin_post_nopriv_ndingi_contact', 'ndingi_handle_contact_submission' );
