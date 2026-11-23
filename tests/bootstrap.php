<?php
/**
 * Test bootstrap & lightweight WordPress API double for pure unit testing.
 * Provides in-memory options, post meta, options, capabilities, and hashing
 * without requiring a running web server or MySQL connection.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

// Global in-memory storage for test isolation
$GLOBALS['_test_options'] = [];
$GLOBALS['_test_post_meta'] = [];
$GLOBALS['_test_current_user_caps'] = [];
$GLOBALS['_test_passwords_required'] = [];
$GLOBALS['_test_last_redirect'] = null;

function test_reset_wp_state(): void {
    $GLOBALS['_test_options'] = [];
    $GLOBALS['_test_post_meta'] = [];
    $GLOBALS['_test_current_user_caps'] = [];
    $GLOBALS['_test_passwords_required'] = [];
    $GLOBALS['_test_last_redirect'] = null;
}

if (!function_exists('get_option')) {
    function get_option(string $key, $default = false) {
        return $GLOBALS['_test_options'][$key] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $key, $value, $autoload = null): bool {
        $GLOBALS['_test_options'][$key] = $value;
        return true;
    }
}

if (!function_exists('add_option')) {
    function add_option(string $key, $value = '', string $deprecated = '', $autoload = 'yes'): bool {
        if (array_key_exists($key, $GLOBALS['_test_options'])) {
            return false;
        }
        $GLOBALS['_test_options'][$key] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $key): bool {
        if (array_key_exists($key, $GLOBALS['_test_options'])) {
            unset($GLOBALS['_test_options'][$key]);
            return true;
        }
        return false;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability, ...$args): bool {
        return !empty($GLOBALS['_test_current_user_caps'][$capability]);
    }
}

if (!function_exists('test_set_current_user_can')) {
    function test_set_current_user_can(string $capability, bool $can): void {
        $GLOBALS['_test_current_user_caps'][$capability] = $can;
    }
}

if (!function_exists('post_password_required')) {
    function post_password_required($post = null): bool {
        $id = is_object($post) ? ($post->ID ?? 0) : (int)$post;
        return !empty($GLOBALS['_test_passwords_required'][$id]);
    }
}

if (!function_exists('test_set_post_password_required')) {
    function test_set_post_password_required(int $post_id, bool $required): void {
        $GLOBALS['_test_passwords_required'][$post_id] = $required;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta(int $post_id, string $key = '', bool $single = false) {
        if (!isset($GLOBALS['_test_post_meta'][$post_id])) {
            return $single ? '' : [];
        }
        if ($key === '') {
            return $GLOBALS['_test_post_meta'][$post_id];
        }
        if (!isset($GLOBALS['_test_post_meta'][$post_id][$key])) {
            return $single ? '' : [];
        }
        return $single ? $GLOBALS['_test_post_meta'][$post_id][$key] : [$GLOBALS['_test_post_meta'][$post_id][$key]];
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta(int $post_id, string $key, $value, $prev_value = ''): bool {
        if (!isset($GLOBALS['_test_post_meta'][$post_id])) {
            $GLOBALS['_test_post_meta'][$post_id] = [];
        }
        $GLOBALS['_test_post_meta'][$post_id][$key] = $value;
        return true;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string {
        $filtered = strip_tags($str);
        $filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered);
        return trim((string)$filtered);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field(string $str): string {
        return trim(strip_tags($str));
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($val) {
        return is_string($val) ? stripslashes($val) : $val;
    }
}

if (!function_exists('wp_hash')) {
    function wp_hash(string $data, string $scheme = 'auth'): string {
        return hash_hmac('sha256', $data, 'ndingi_test_secret_salt');
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return 1;
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect(string $location, int $status = 302): bool {
        $GLOBALS['_test_last_redirect'] = $location;
        return true;
    }
}

if (!function_exists('home_url')) {
    function home_url(string $path = ''): string {
        return 'https://ndingifoundation.org' . $path;
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $url): string {
        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing): bool {
        return $thing instanceof WP_Error;
    }
}

class WP_Error {
    public string $code;
    public string $message;
    public function __construct(string $code = '', string $message = '') {
        $this->code = $code;
        $this->message = $message;
    }
    public function get_error_message(): string {
        return $this->message;
    }
}

class WP_Post {
    public int $ID = 0;
    public string $post_title = '';
    public string $post_content = '';
    public string $post_status = 'publish';
    public int $post_parent = 0;
    public function __construct(int $id, string $title = '', string $content = '', int $parent = 0) {
        $this->ID = $id;
        $this->post_title = $title;
        $this->post_content = $content;
        $this->post_parent = $parent;
    }
}

if (!function_exists('add_action')) {
    function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook_name, ...$args): void {}
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook_name, $value, ...$args) {
        return $value;
    }
}

if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);
if (!defined('HOUR_IN_SECONDS')) define('HOUR_IN_SECONDS', 3600);
if (!defined('DAY_IN_SECONDS')) define('DAY_IN_SECONDS', 86400);

class Test_WPDB {
    public string $options = 'wp_options';
    public function prepare(string $query, ...$args): string {
        foreach ($args as $arg) {
            $val = is_numeric($arg) ? $arg : "'" . addslashes((string)$arg) . "'";
            $query = preg_replace('/%[sd]/', (string)$val, $query, 1);
        }
        return $query;
    }
    public function query(string $query) {
        if (preg_match("/UPDATE .* SET option_value = option_value \+ 1 WHERE option_name = '([^']+)'/", $query, $m)) {
            $key = $m[1];
            $current = (int)($GLOBALS['_test_options'][$key] ?? 0);
            $GLOBALS['_test_options'][$key] = $current + 1;
        }
        return 1;
    }
    public function get_var(string $query) {
        if (preg_match("/SELECT option_value FROM .* WHERE option_name = '([^']+)'/", $query, $m)) {
            $key = $m[1];
            return $GLOBALS['_test_options'][$key] ?? null;
        }
        return null;
    }
}

global $wpdb;
$wpdb = new Test_WPDB();
