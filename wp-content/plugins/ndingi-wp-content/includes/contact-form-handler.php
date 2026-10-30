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

/**
 * Compute an HMAC-SHA256 signature binding the form render timestamp and
 * per-render challenge seed to this site's nonce salt.
 */
function ndingi_contact_sign_token( $ts, $seed ) {
	return hash_hmac( 'sha256', (string) $ts . '|' . (string) $seed, wp_salt( 'nonce' ) );
}

/**
 * Compute the expected client-side human-interaction token from the seed and
 * render timestamp (populated by modal.js on focusin/keydown/pointerdown).
 */
function ndingi_contact_expected_js_token( $ts, $seed ) {
	return strrev( (string) $seed ) . '-' . substr( (string) $ts, -4 );
}

/**
 * Retrieve the client IP address for rate-limiting and spam checks.
 */
function ndingi_contact_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Return true if the alphabetic characters in $text (ignoring URLs) are
 * predominantly ALL-CAPS shouting (> 60% uppercase across >= 15 letters).
 */
function ndingi_contact_has_excessive_caps( $text ) {
	$without_urls = preg_replace( '/https?:\/\/\S+|www\.\S+/i', '', (string) $text );
	$letters      = preg_replace( '/[^a-zA-Z]/', '', $without_urls );
	$len          = strlen( $letters );
	if ( $len < 15 ) {
		return false;
	}
	$upper = strlen( preg_replace( '/[^A-Z]/', '', $letters ) );
	return ( $upper / $len ) > 0.60;
}

/**
 * Evaluate heuristic and content-based anti-spam rules against a contact
 * submission. Returns true if the submission should be silently dropped.
 */
function ndingi_is_spam_submission( $name, $email, $subject, $message ) {
	// 1. Field length bounds.
	if ( mb_strlen( $name ) > 100 || mb_strlen( $subject ) > 160 || mb_strlen( $message ) < 5 || mb_strlen( $message ) > 5000 ) {
		return true;
	}

	// 2. Disallow any URLs in Name or Subject, and allow at most 1 URL in Message.
	if ( preg_match( '/https?:\/\/|www\./i', $name . ' ' . $subject ) ) {
		return true;
	}
	if ( preg_match_all( '/https?:\/\/|www\./i', $message ) > 1 ) {
		return true;
	}

	$combined = $subject . "\n" . $message;

	// 3. Block known spam paste/shortener hosts and high-abuse TLDs.
	$spam_domain_pattern = '/\b(?:telegra\.ph|t\.me|telegram\.me|bit\.ly|tinyurl\.com|cutt\.ly|is\.gd|rb\.gy|ow\.ly|tiny\.cc|shorturl\.at|vk\.com|yandex\.[a-z]+|[a-z0-9-]+\.(?:ru|su|xyz|top|click|loan|work|gq|cf|tk|ml|ga|buzz|icu|monster|rest|fit))\b/i';
	if ( preg_match( $spam_domain_pattern, $email . "\n" . $combined ) ) {
		return true;
	}

	// 4. Block ALL-CAPS shouting in Subject or Message.
	if ( ndingi_contact_has_excessive_caps( $subject ) || ndingi_contact_has_excessive_caps( $message ) ) {
		return true;
	}

	// 5. Block bot-generated Name patterns (digits in name, or single-word
	// CamelCase gibberish like "EdwardWaw" produced by XRumer/GSA bots).
	$trimmed_name = trim( $name );
	if ( preg_match( '/\d/', $trimmed_name ) ) {
		return true;
	}
	if ( preg_match( '/^[A-Z][a-z]{2,15}[A-Z][a-z]{1,15}$/', $trimmed_name ) &&
		! preg_match( '/^(?:Mc|Mac|De|Di|Du|La|Le|Van|Von|Fitz)[A-Z]/', $trimmed_name ) ) {
		return true;
	}

	// 6. Block common financial/crypto/SEO/adult scam lures.
	$scam_lure_pattern = '/(?:\bearn\s+\$?\d[\d,.]*|\bmake\s+\$?\d[\d,.]*|\$\d[\d,]*\s*(?:per|a|in\s+a)\s*(?:month|week|day|hour)|\bpassive\s+income\b|\bfinancial\s+freedom\b|\bcrypto(?:currency)?\s+(?:invest|trading|airdrop|giveaway)\b|\bbitcoin\s+(?:invest|mining|generator)\b|\bonline\s+casino\b|\bsports\s+betting\b|\bbuy\s+backlinks\b|\bseo\s+services\b|\bguest\s+post\s+service\b|\bviagra\b|\bcialis\b)/i';
	if ( preg_match( $scam_lure_pattern, $combined ) ) {
		return true;
	}

	// 7. Honour WordPress's native Disallowed Comment Keys list if configured.
	if ( function_exists( 'wp_check_comment_disallowed_list' ) ) {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( wp_check_comment_disallowed_list( $name, $email, '', $combined, ndingi_contact_client_ip(), $ua ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Enforce per-IP (max 2 / 15 min, max 5 / 24 hr) and global (max 15 / hr)
 * rate limits via WordPress transients. Returns true if rate-limited.
 */
function ndingi_contact_is_rate_limited( $ip ) {
	$ip_hash   = substr( hash_hmac( 'sha256', (string) $ip, wp_salt( 'nonce' ) ), 0, 16 );
	$key_15m   = 'ndingi_cf_15m_' . $ip_hash;
	$key_24h   = 'ndingi_cf_24h_' . $ip_hash;
	$key_glob  = 'ndingi_cf_glob_1h';

	$count_15m  = (int) get_transient( $key_15m );
	$count_24h  = (int) get_transient( $key_24h );
	$count_glob = (int) get_transient( $key_glob );

	if ( $count_15m >= 2 || $count_24h >= 5 || $count_glob >= 15 ) {
		return true;
	}

	set_transient( $key_15m, $count_15m + 1, 15 * MINUTE_IN_SECONDS );
	set_transient( $key_24h, $count_24h + 1, DAY_IN_SECONDS );
	set_transient( $key_glob, $count_glob + 1, HOUR_IN_SECONDS );

	return false;
}

function ndingi_handle_contact_submission() {
	$redirect_to = wp_get_referer() ?: home_url( '/contact/' );

	if ( ! isset( $_POST['ndingi_contact_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ndingi_contact_nonce'] ) ), 'ndingi_contact_form' ) ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'error', $redirect_to ) );
		exit;
	}

	// Layer 1 — Multi-field honeypots (checkbox `botcheck` + text `website`).
	// Silently accept rather than flag so bots cannot probe defenses.
	$honeypot_website = isset( $_POST['website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['website'] ) ) ) : '';
	if ( ! empty( $_POST['botcheck'] ) || '' !== $honeypot_website ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'success', $redirect_to ) );
		exit;
	}

	// Layer 2 — Signed timestamp time-trap (4s minimum fill time, 2h max age)
	// and single-use signature replay lock.
	$ts   = isset( $_POST['ndingi_ts'] ) ? sanitize_text_field( wp_unslash( $_POST['ndingi_ts'] ) ) : '';
	$seed = isset( $_POST['ndingi_seed'] ) ? sanitize_text_field( wp_unslash( $_POST['ndingi_seed'] ) ) : '';
	$sig  = isset( $_POST['ndingi_sig'] ) ? sanitize_text_field( wp_unslash( $_POST['ndingi_sig'] ) ) : '';

	if ( ! ctype_digit( $ts ) || ! preg_match( '/^[a-zA-Z0-9]{12,32}$/', $seed ) || ! $sig ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'success', $redirect_to ) );
		exit;
	}

	$expected_sig = ndingi_contact_sign_token( $ts, $seed );
	if ( ! hash_equals( $expected_sig, $sig ) ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'success', $redirect_to ) );
		exit;
	}

	$elapsed = time() - (int) $ts;
	if ( $elapsed < 4 || $elapsed > 7200 ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'success', $redirect_to ) );
		exit;
	}

	$replay_key = 'ndingi_cf_sig_' . substr( $sig, 0, 24 );
	if ( get_transient( $replay_key ) ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'success', $redirect_to ) );
		exit;
	}
	set_transient( $replay_key, 1, 2 * HOUR_IN_SECONDS );

	// Layer 3 — Client-side human interaction proof token (set by modal.js).
	$js_token          = isset( $_POST['ndingi_js_token'] ) ? sanitize_text_field( wp_unslash( $_POST['ndingi_js_token'] ) ) : '';
	$expected_js_token = ndingi_contact_expected_js_token( $ts, $seed );
	if ( ! $js_token || ! hash_equals( $expected_js_token, $js_token ) ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'success', $redirect_to ) );
		exit;
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( ! $name || ! is_email( $email ) || ! $message ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'error', $redirect_to ) );
		exit;
	}

	// Layer 4 — Heuristic & content spam filter.
	if ( ndingi_is_spam_submission( $name, $email, $subject, $message ) ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'success', $redirect_to ) );
		exit;
	}

	// Layer 5 — Per-IP and global rate limiting.
	if ( ndingi_contact_is_rate_limited( ndingi_contact_client_ip() ) ) {
		wp_safe_redirect( add_query_arg( 'ndingi_contact', 'success', $redirect_to ) );
		exit;
	}

	$to = function_exists( 'ndingi_contact_email' ) ? ndingi_contact_email() : '';
	if ( ! $to || ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}

	$safe_reply_name = trim( preg_replace( '/[\r\n<>",;]+/', ' ', $name ) ) ?: 'Website Visitor';
	$mail_subject    = sprintf( '[Contact form] %s', $subject ?: 'New message from ' . $safe_reply_name );
	$body            = "Name: {$name}\nEmail: {$email}\n\n{$message}";
	$headers         = array( 'Reply-To: ' . $safe_reply_name . ' <' . $email . '>' );

	$sent = wp_mail( $to, $mail_subject, $body, $headers );

	wp_safe_redirect( add_query_arg( 'ndingi_contact', $sent ? 'success' : 'error', $redirect_to ) );
	exit;
}
add_action( 'admin_post_ndingi_contact', 'ndingi_handle_contact_submission' );
add_action( 'admin_post_nopriv_ndingi_contact', 'ndingi_handle_contact_submission' );
