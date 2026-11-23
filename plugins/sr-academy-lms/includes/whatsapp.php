<?php
if (!defined('ABSPATH')) exit;

const SR_LMS_WATI_CALL_TEMPLATE = 'sr_call_confirmation_v2';

function sr_lms_whatsapp_settings(): array {
    return (array) get_option('sr_lms_whatsapp_settings', []);
}

function sr_lms_whatsapp_credentials(): array {
    $settings = sr_lms_whatsapp_settings();
    return [
        'host'=>defined('SR_LMS_WATI_HOST') ? SR_LMS_WATI_HOST : ($settings['host'] ?? ''),
        'token'=>defined('SR_LMS_WATI_TOKEN') ? SR_LMS_WATI_TOKEN : sr_lms_payment_decrypt($settings['token'] ?? ''),
    ];
}

function sr_lms_whatsapp_valid_host(string $host): bool {
    return (bool) preg_match('~^https://[a-z0-9-]+\.wati\.io/?$~iD', $host);
}

function sr_lms_whatsapp_ready(): bool {
    $credentials = sr_lms_whatsapp_credentials();
    return sr_lms_whatsapp_valid_host($credentials['host']) && $credentials['token'] !== '';
}

function sr_lms_whatsapp_opt_in(): string {
    if (!sr_lms_whatsapp_ready()) return '';
    return '<label><input type="checkbox" name="whatsapp_opt_in" value="1"> Send confirmation for this booking or purchase to my WhatsApp number via WATI.</label>';
}

function sr_lms_whatsapp_number(string $phone): string {
    $phone = trim($phone);
    if (!preg_match('/^\+?[0-9\s().-]+$/D', $phone)) return '';
    $digits = preg_replace('/\D/', '', $phone);
    if (str_starts_with($digits, '00') && !str_starts_with($phone, '+')) {
        $digits = substr($digits, 2);
    } elseif (!str_starts_with($phone, '+') && preg_match('/^0?[1-9][0-9]{9}$/D', $digits)) {
        $digits = '91' . substr($digits, -10);
    }
    if (str_starts_with($digits, '91') && strlen($digits) !== 12) return '';
    return preg_match('/^[1-9][0-9]{7,14}$/D', $digits) ? $digits : '';
}

function sr_lms_whatsapp_live_enabled(): bool {
    return defined('SR_LMS_WATI_LIVE') ? SR_LMS_WATI_LIVE === true : !empty(sr_lms_whatsapp_settings()['live']);
}

function sr_lms_queue_whatsapp(string $reference, string $mode, string $phone, string $template, array $fields): void {
    if (!sr_lms_whatsapp_ready()) return;
    $key = 'sr_lms_wa_' . hash('sha256', $reference);
    $digits = sr_lms_whatsapp_number($phone);
    if ($digits === '') {
        error_log('[SR Academy] WATI skipped ' . $key . ' invalid_number');
        return;
    }
    // Restrict test traffic by default, including when production sending is enabled.
    $settings = sr_lms_whatsapp_settings();
    $test_number = defined('SR_LMS_WATI_TEST_NUMBER') ? SR_LMS_WATI_TEST_NUMBER : ($settings['test_number'] ?? '');
    // Staging course and consultation checkouts send sandbox confirmations to opted-in form numbers.
    $public_staging_checkout = $mode === 'test'
        && in_array($template, [SR_LMS_WATI_CALL_TEMPLATE, 'sr_course_confirmation'], true)
        && wp_parse_url(home_url(), PHP_URL_HOST) === 'darkorange-pheasant-779963.hostingersite.com';
    if (!$public_staging_checkout && ($mode === 'test' || !sr_lms_whatsapp_live_enabled()) && $digits !== sr_lms_whatsapp_number((string) $test_number)) {
        error_log('[SR Academy] WATI skipped ' . $key . ' restricted_to_test_number');
        return;
    }

    global $wpdb;
    $notice = ['target'=>$digits, 'template'=>$template, 'fields'=>$fields];
    // Atomically claim this order across checkout callbacks and webhook reconciliation.
    $created = $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,'no')",
        $key,
        maybe_serialize($notice)
    ));
    if ($created !== 1) return;
    wp_cache_delete('notoptions', 'options');
    $scheduled = wp_schedule_single_event(time(), 'sr_lms_send_whatsapp', [$key], true);
    if (is_wp_error($scheduled) || !$scheduled) {
        delete_option($key);
        error_log('[SR Academy] Could not queue WhatsApp confirmation ' . $key);
    }
}

function sr_lms_wati_submission_result(int $status, mixed $result, string $key, string $target): string {
    if ($status === 401 || $status === 403) return 'authorization_rejected';
    if ($status === 429) return 'rate_or_usage_limited';
    if ($status === 0 || $status >= 500) return 'outcome_unknown';
    if ($status >= 400 || ($result['success'] ?? null) === false) return 'request_rejected';
    if (($result['success'] ?? null) !== true) return 'outcome_unknown';

    foreach ((array) ($result['recipients'] ?? []) as $recipient) {
        if (!is_array($recipient)) continue;
        $id = $recipient['local_message_id'] ?? '';
        $number = (string) ($recipient['phone_number'] ?? $recipient['target'] ?? '');
        if ($id !== $key && ($id !== '' || $number !== $target)) continue;
        return empty($recipient['errors']) ? 'accepted' : 'recipient_rejected';
    }
    return 'recipient_unconfirmed';
}

function sr_lms_send_whatsapp(string $key): void {
    if (!sr_lms_whatsapp_ready()) return;
    $notice = get_option($key);
    if (!is_array($notice)) return;
    global $wpdb;
    // Keep the deduplication marker and remove customer data before attempting delivery.
    // An ambiguous provider timeout must not cause a second message to the customer.
    if ($wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->options} SET option_value='attempted' WHERE option_name=%s AND option_value=%s",
        $key,
        maybe_serialize($notice)
    )) !== 1) return;
    wp_cache_delete($key, 'options');

    $params = [];
    foreach ($notice['fields'] as $name => $value) {
        $params[] = ['name'=>$name, 'value'=>trim(preg_replace('/\s+/', ' ', (string) $value))];
    }
    $credentials = sr_lms_whatsapp_credentials();
    $response = wp_safe_remote_post(rtrim($credentials['host'], '/') . '/api/ext/v3/messageTemplates/send', [
        'timeout'=>15,
        'redirection'=>0,
        'headers'=>['Authorization'=>'Bearer ' . $credentials['token'], 'Content-Type'=>'application/json'],
        'body'=>wp_json_encode([
            'template_name'=>$notice['template'],
            'broadcast_name'=>$notice['template'],
            'recipients'=>[['target'=>$notice['target'], 'local_message_id'=>$key, 'custom_params'=>$params]],
        ]),
    ]);
    $status = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
    $result = $status ? json_decode(wp_remote_retrieve_body($response), true) : null;
    $outcome = sr_lms_wati_submission_result($status, $result, $key, $notice['target']);
    error_log('[SR Academy] WATI submission ' . $key . ' HTTP ' . $status . ' ' . $outcome);
}
add_action('sr_lms_send_whatsapp', 'sr_lms_send_whatsapp');

add_action('admin_menu', static function (): void {
    add_submenu_page('sr-academy-lms', 'WhatsApp confirmations', 'WhatsApp', 'manage_options', 'sr-lms-whatsapp', 'sr_lms_whatsapp_admin');
});

function sr_lms_whatsapp_admin(): void {
    if (!current_user_can('manage_options')) return;
    $settings = sr_lms_whatsapp_settings();
    ?>
    <div class="wrap">
        <h1>WhatsApp confirmations</h1>
        <p>Live sending: <strong><?php echo sr_lms_whatsapp_live_enabled() ? 'Enabled' : 'Restricted to the test number'; ?></strong></p>
        <?php if (isset($_GET['updated'])): ?>
            <div class="notice notice-success"><p>WhatsApp settings saved.</p></div>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sr_lms_save_whatsapp">
            <?php wp_nonce_field('sr_lms_save_whatsapp'); ?>
            <p><label>WATI API host
                <input class="regular-text" type="url" name="host" required value="<?php echo esc_attr($settings['host'] ?? ''); ?>">
            </label></p>
            <p><label>WATI API key
                <input class="regular-text" type="password" name="token" autocomplete="new-password">
            </label> <?php echo empty($settings['token']) ? 'Enter a key authorized for WATI V3 template sends.' : 'A key is stored. Leave blank to keep it.'; ?></p>
            <p><label>Test WhatsApp number
                <input type="tel" name="test_number" autocomplete="off" value="<?php echo esc_attr($settings['test_number'] ?? ''); ?>">
            </label> Include the country code.</p>
            <p><label>
                <input type="checkbox" name="live" value="1" <?php checked(!empty($settings['live'])); ?>>
                Send live confirmations to customers who opt in
            </label></p>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('admin_post_sr_lms_save_whatsapp', static function (): void {
    if (!current_user_can('manage_options')) wp_die('Access denied.', '', ['response'=>403]);
    check_admin_referer('sr_lms_save_whatsapp');
    $host = trim((string) wp_unslash($_POST['host'] ?? ''));
    $raw_number = trim((string) wp_unslash($_POST['test_number'] ?? ''));
    $number = sr_lms_whatsapp_number($raw_number);
    if (!sr_lms_whatsapp_valid_host($host) || ($raw_number !== '' && $number === '')) {
        wp_die('Enter an HTTPS WATI API host and a valid international test number.', '', ['response'=>400]);
    }
    $token = trim((string) wp_unslash($_POST['token'] ?? ''));
    $settings = sr_lms_whatsapp_settings();
    if ($token !== '') {
        $encrypted = sr_lms_payment_encrypt($token);
        if (is_wp_error($encrypted)) wp_die('WordPress could not encrypt the WATI API key.');
        $settings['token'] = $encrypted;
    }
    $settings['host'] = rtrim($host, '/');
    $settings['test_number'] = $number;
    $settings['live'] = ($_POST['live'] ?? null) === '1';
    update_option('sr_lms_whatsapp_settings', $settings, false);
    wp_safe_redirect(add_query_arg(['page'=>'sr-lms-whatsapp', 'updated'=>'1'], admin_url('admin.php')));
    exit;
});
