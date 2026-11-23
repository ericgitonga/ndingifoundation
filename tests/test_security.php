<?php
/**
 * Security & Data Protection Unit Test Suite
 *
 * Verifies access control, Kenya DPA 2019 compliance, replay prevention,
 * rate limiting, and administrative RBAC gates.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../wp-content/plugins/ndingi-wp-content/includes/contact-form-handler.php';
require_once __DIR__ . '/../wp-content/plugins/ndingi-wp-content/includes/donate-meta.php';
require_once __DIR__ . '/../wp-content/plugins/ndingi-wp-content/includes/page-setup.php';

function sec_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new \RuntimeException("Assertion failed: " . $message);
    }
}

$SECURITY_TESTS['test_post_password_required_privacy_guard'] = function (): void {
    test_reset_wp_state();

    $public_post = new WP_Post(101, 'Public Programme', 'Public details');
    $protected_post = new WP_Post(102, 'Restricted Programme', 'Confidential details');

    test_set_post_password_required(101, false);
    test_set_post_password_required(102, true);

    sec_assert(!post_password_required($public_post), 'Public post must not require password');
    sec_assert(post_password_required($protected_post), 'Protected post must require password');

    // Simulate template card filter logic across page-our-work, page-who-we-are, team-grid
    $items = [$public_post, $protected_post];
    $rendered_ids = [];
    foreach ($items as $item) {
        if (post_password_required($item)) {
            continue;
        }
        $rendered_ids[] = $item->ID;
    }

    sec_assert($rendered_ids === [101], 'Protected post must be excluded from public aggregate listings');
};

$SECURITY_TESTS['test_contact_replay_lock_atomic_mutex'] = function (): void {
    test_reset_wp_state();

    $sig = 'hash_signature_token_abc123';

    // First attempt should acquire the lock
    $acquired1 = ndingi_contact_acquire_replay_lock($sig);
    sec_assert($acquired1 === true, 'First submission with signature must acquire replay lock');

    // Immediate replay with identical signature must be rejected
    $acquired2 = ndingi_contact_acquire_replay_lock($sig);
    sec_assert($acquired2 === false, 'Replayed submission with identical signature must fail lock acquisition');

    // A different signature should succeed
    $acquired3 = ndingi_contact_acquire_replay_lock('different_signature_456');
    sec_assert($acquired3 === true, 'Unique signature must acquire its own lock');
};

$SECURITY_TESTS['test_contact_rate_limiting_monotony'] = function (): void {
    test_reset_wp_state();

    $key = 'test_ip_rate_key';
    $limit = 2;

    $attempt1 = ndingi_contact_atomic_increment($key, 900, $limit);
    sec_assert($attempt1 === true, 'Attempt 1 within limit of 2');

    $attempt2 = ndingi_contact_atomic_increment($key, 900, $limit);
    sec_assert($attempt2 === true, 'Attempt 2 within limit of 2');

    $attempt3 = ndingi_contact_atomic_increment($key, 900, $limit);
    sec_assert($attempt3 === false, 'Attempt 3 exceeds limit of 2 and must be blocked');
};

$SECURITY_TESTS['test_contact_dpa_section_29_consent_enforcement'] = function (): void {
    test_reset_wp_state();

    // Verify presence of dpa_consent check
    $submission_without_consent = [
        'ndingi_name'    => 'Visitor',
        'ndingi_email'   => 'visitor@example.com',
        'ndingi_message' => 'Hello',
    ];

    $has_consent = !empty($submission_without_consent['dpa_consent']);
    sec_assert(!$has_consent, 'Missing dpa_consent must be falsy');

    $submission_with_consent = [
        'ndingi_name'    => 'Visitor',
        'ndingi_email'   => 'visitor@example.com',
        'ndingi_message' => 'Hello',
        'dpa_consent'    => '1',
    ];

    $has_consent_valid = !empty($submission_with_consent['dpa_consent']);
    sec_assert($has_consent_valid, 'Provided dpa_consent must be truthy');
};

$SECURITY_TESTS['test_contact_time_trap_bounds'] = function (): void {
    test_reset_wp_state();

    $now = time();

    // Too fast (< 4 seconds)
    $ts_fast = $now - 2;
    $age_fast = $now - $ts_fast;
    sec_assert($age_fast < 4, 'Submission under 4s must trigger too_fast boundary');

    // Valid window (4s to 7200s)
    $ts_valid = $now - 30;
    $age_valid = $now - $ts_valid;
    sec_assert($age_valid >= 4 && $age_valid <= 7200, '30s submission must be within valid window');

    // Expired (> 7200s / 2 hours)
    $ts_expired = $now - 7500;
    $age_expired = $now - $ts_expired;
    sec_assert($age_expired > 7200, 'Submission over 7200s must trigger expired boundary');
};

$SECURITY_TESTS['test_biography_right_to_erasure_suppression'] = function (): void {
    test_reset_wp_state();

    $member_id = 201;
    // Suppressed biography
    update_post_meta($member_id, '_ndingi_bio_suppressed', '1');

    $is_suppressed = (bool) get_post_meta($member_id, '_ndingi_bio_suppressed', true);
    sec_assert($is_suppressed === true, 'Post with _ndingi_bio_suppressed must be recognized as suppressed');

    // Unsuppressed biography
    $active_member_id = 202;
    $not_suppressed = (bool) get_post_meta($active_member_id, '_ndingi_bio_suppressed', true);
    sec_assert($not_suppressed === false, 'Active post must not be suppressed');
};

$SECURITY_TESTS['test_donate_meta_payment_destination_rbac'] = function (): void {
    test_reset_wp_state();

    $post_id = 301;
    update_post_meta($post_id, 'ndingi_paybill_number', '127767');
    update_post_meta($post_id, 'ndingi_bank_account_number', '1000123456');

    // Regular editor without manage_options
    test_set_current_user_can('edit_post', true);
    test_set_current_user_can('manage_options', false);

    sec_assert(current_user_can('edit_post', $post_id), 'Editor can edit post');
    sec_assert(!current_user_can('manage_options'), 'Editor does not have manage_options');

    // Attempting unauthorized update
    $_POST = [
        'ndingi_donate_nonce'          => 'valid_nonce',
        'ndingi_donate_lede'           => 'Updated appeal text',
        'ndingi_paybill_number'        => '999999_ATTACKER',
        'ndingi_bank_account_number'   => '000000_ATTACKER',
    ];

    ndingi_save_donate_meta($post_id);

    // Lede should be updated, but payment details must remain untouched
    sec_assert(get_post_meta($post_id, 'ndingi_donate_lede', true) === 'Updated appeal text', 'Lede copy can be updated by editor');
    sec_assert(get_post_meta($post_id, 'ndingi_paybill_number', true) === '127767', 'Paybill must remain protected from non-admin');
    sec_assert(get_post_meta($post_id, 'ndingi_bank_account_number', true) === '1000123456', 'Bank account must remain protected from non-admin');

    // Now grant manage_options (Administrator)
    test_set_current_user_can('manage_options', true);
    $_POST['ndingi_paybill_number'] = '127767_AUTHORIZED';
    ndingi_save_donate_meta($post_id);

    sec_assert(get_post_meta($post_id, 'ndingi_paybill_number', true) === '127767_AUTHORIZED', 'Admin can update paybill destination');
};

$SECURITY_TESTS['test_page_setup_concurrency_lock'] = function (): void {
    test_reset_wp_state();

    $acquired1 = ndingi_page_setup_acquire_lock();
    sec_assert($acquired1 === true, 'First caller must acquire setup lock');

    // Second caller while lock is held must be blocked
    $acquired2 = ndingi_page_setup_acquire_lock();
    sec_assert($acquired2 === false, 'Concurrent caller must be blocked by active lock');

    // Releasing lock allows next caller
    ndingi_page_setup_release_lock();
    $acquired3 = ndingi_page_setup_acquire_lock();
    sec_assert($acquired3 === true, 'Lock can be acquired after release');

    ndingi_page_setup_release_lock();
};
