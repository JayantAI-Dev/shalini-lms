<?php
if (!defined('ABSPATH')) exit;

function sr_lms_whatsapp_ready(): bool {
    return defined('SR_LMS_WATI_HOST') && defined('SR_LMS_WATI_TOKEN')
        && SR_LMS_WATI_TOKEN !== ''
        && (bool) preg_match('~^https://[a-z0-9-]+\.wati\.io/?$~iD', SR_LMS_WATI_HOST);
}

function sr_lms_whatsapp_opt_in(): string {
    if (!sr_lms_whatsapp_ready()) return '';
    return '<label><input type="checkbox" name="whatsapp_opt_in" value="1"> Send confirmation for this booking or purchase to my WhatsApp number via WATI.</label>';
}

function sr_lms_queue_whatsapp(string $reference, string $mode, string $phone, string $template, array $fields): void {
    if (!sr_lms_whatsapp_ready()) return;
    $digits = preg_replace('/\D/', '', $phone);
    if (!str_starts_with(trim($phone), '+') && strlen($digits) === 10) $digits = '91' . $digits;
    if (!preg_match('/^[1-9][0-9]{7,14}$/D', $digits)) return;
    // Restrict all traffic to one recipient unless production sending is explicitly enabled.
    $live_enabled = defined('SR_LMS_WATI_LIVE') && SR_LMS_WATI_LIVE === true;
    if (($mode === 'test' || !$live_enabled)
        && (!defined('SR_LMS_WATI_TEST_NUMBER') || $digits !== SR_LMS_WATI_TEST_NUMBER)) return;

    global $wpdb;
    $key = 'sr_lms_wa_' . hash('sha256', $reference);
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
    $response = wp_safe_remote_post(rtrim(SR_LMS_WATI_HOST, '/') . '/api/ext/v3/messageTemplates/send', [
        'timeout'=>15,
        'redirection'=>0,
        'headers'=>['Authorization'=>'Bearer ' . SR_LMS_WATI_TOKEN, 'Content-Type'=>'application/json'],
        'body'=>wp_json_encode([
            'template_name'=>$notice['template'],
            'broadcast_name'=>$notice['template'],
            'recipients'=>[['target'=>$notice['target'], 'local_message_id'=>$key, 'custom_params'=>$params]],
        ]),
    ]);
    $status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
    error_log('[SR Academy] WATI submission ' . $key . ' HTTP ' . (int) $status);
}
add_action('sr_lms_send_whatsapp', 'sr_lms_send_whatsapp');
