<?php
if (!defined('ABSPATH')) exit;

/** Save TEDx coaching enquiries in the LMS admin and notify both parties. */
add_action('init', static function (): void {
    register_post_type('sr_tedx_lead', [
        'labels' => [
            'name' => 'TEDx Coaching',
            'singular_name' => 'TEDx enquiry',
            'menu_name' => 'TEDx Coaching',
            'add_new_item' => 'Add TEDx enquiry',
            'edit_item' => 'View TEDx enquiry',
            'search_items' => 'Search TEDx enquiries',
            'not_found' => 'No TEDx enquiries found.',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'sr-academy-lms',
        'show_in_admin_bar' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'has_archive' => false,
        'rewrite' => false,
        'supports' => ['title', 'editor'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-microphone',
    ]);
});

add_filter('manage_sr_tedx_lead_posts_columns', static function (array $columns): array {
    return [
        'cb' => $columns['cb'],
        'title' => 'Name',
        'sr_tedx_email' => 'Email',
        'sr_tedx_phone' => 'Phone / WhatsApp',
        'date' => 'Received',
    ];
});
add_action('manage_sr_tedx_lead_posts_custom_column', static function (string $column, int $post_id): void {
    if ($column === 'sr_tedx_email') {
        $email = (string) get_post_meta($post_id, '_sr_tedx_email', true);
        echo $email ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '—';
    } elseif ($column === 'sr_tedx_phone') {
        echo esc_html((string) get_post_meta($post_id, '_sr_tedx_phone', true) ?: '—');
    }
}, 10, 2);
add_action('add_meta_boxes_sr_tedx_lead', static function (): void {
    add_meta_box('sr-tedx-enquiry-details', 'Contact details', static function (WP_Post $post): void {
        $email = (string) get_post_meta($post->ID, '_sr_tedx_email', true);
        $phone = (string) get_post_meta($post->ID, '_sr_tedx_phone', true);
        echo '<p><strong>Email:</strong> ' . ($email ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '—') . '</p>';
        echo '<p><strong>Phone / WhatsApp:</strong> ' . esc_html($phone ?: '—') . '</p>';
        echo '<p><strong>Customer confirmation:</strong> ' . (get_post_meta($post->ID, '_sr_tedx_user_email_sent', true) ? 'Sent to mail transport' : 'Not sent') . '</p>';
        echo '<p><strong>Academy notification:</strong> ' . (get_post_meta($post->ID, '_sr_tedx_admin_email_sent', true) ? 'Sent to mail transport' : 'Not sent') . '</p>';
    }, 'sr_tedx_lead', 'side', 'high');
});

function sr_lms_tedx_enquiry_admin_email(): string {
    $candidates = [];
    if (function_exists('sr_theme_option')) $candidates[] = sr_theme_option('sr_contact_email', '');
    if (function_exists('sr_lms_zoom_config')) $candidates[] = sr_lms_zoom_config()['admin_email'] ?? '';
    if (function_exists('sr_lms_email_settings')) $candidates[] = sr_lms_email_settings()['reply_to'] ?? '';
    $candidates[] = get_option('admin_email');
    foreach ($candidates as $email) {
        $email = sanitize_email((string) $email);
        if (is_email($email)) return $email;
    }
    return '';
}

function sr_lms_tedx_email_headers(string $reply_to = ''): array {
    $settings = function_exists('sr_lms_email_settings') ? sr_lms_email_settings() : [];
    $sender_name = sanitize_text_field((string) ($settings['sender_name'] ?? 'Shalini Rathod Academy'));
    $sender_email = sanitize_email((string) ($settings['sender_email'] ?? get_option('admin_email')));
    if (!is_email($sender_email)) $sender_email = sanitize_email((string) get_option('admin_email'));
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    if (is_email($sender_email)) $headers[] = 'From: ' . $sender_name . ' <' . $sender_email . '>';
    if (is_email($reply_to)) $headers[] = 'Reply-To: ' . $reply_to;
    return $headers;
}

function sr_lms_tedx_email_html(string $heading, string $intro, array $details = []): string {
    $settings = function_exists('sr_lms_email_settings') ? sr_lms_email_settings() : [];
    $brand = sanitize_text_field((string) ($settings['brand'] ?? 'Shalini Rathod Academy'));
    $color = sanitize_hex_color((string) ($settings['color'] ?? '#125c45')) ?: '#125c45';
    $rows = '';
    foreach ($details as $label => $value) {
        $rows .= '<tr><td style="padding:10px 12px;border-top:1px solid #e2ebe1;color:#627268;font-size:14px;vertical-align:top;width:150px">' . esc_html((string) $label) . '</td><td style="padding:10px 12px;border-top:1px solid #e2ebe1;color:#20372d;font-size:14px;line-height:1.6;white-space:pre-wrap;overflow-wrap:anywhere">' . esc_html((string) $value) . '</td></tr>';
    }
    $detail_table = $rows ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:1px solid #dce8dc;border-radius:10px;background:#f5f8f3">' . $rows . '</table>' : '';
    return '<!doctype html><html><body style="margin:0;background:#f2f6f3;font-family:Arial,sans-serif;color:#20372d"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:28px 12px"><table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#fff;border-radius:16px"><tr><td style="padding:36px"><p style="margin:0 0 18px;color:' . esc_attr($color) . ';font-size:15px;line-height:1.5">' . esc_html($brand) . '</p><h1 style="margin:0 0 20px;color:#20372d;font-size:28px;line-height:1.25">' . esc_html($heading) . '</h1><p style="margin:0;color:#52645a;font-size:16px;line-height:1.75">' . nl2br(esc_html($intro)) . '</p>' . $detail_table . '<p style="margin:28px 0 0;color:#66766e;font-size:13px;line-height:1.7">Warmly,<br>' . esc_html($brand) . '</p></td></tr></table></td></tr></table></body></html>';
}

add_action('rest_api_init', static function (): void {
    register_rest_route('sr-lms/v1', '/tedx-enquiry', [
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => 'sr_lms_tedx_enquiry_rest_submit',
    ]);
});

function sr_lms_tedx_enquiry_rest_submit(WP_REST_Request $request) {
    $result = sr_lms_tedx_process_enquiry((array) $request->get_json_params());
    if (is_wp_error($result)) return $result;
    $response = new WP_REST_Response(['success' => true, 'data' => $result]);
    $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    return $response;
}

function sr_lms_tedx_process_enquiry(array $input) {
    if (!empty($input['website'])) return new WP_Error('invalid_submission', 'We could not submit this request. Please try again.', ['status' => 400]);

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $rate_key = 'sr_tedx_rate_' . hash('sha256', $ip);
    if ($ip && get_transient($rate_key)) return new WP_Error('rate_limited', 'Please wait a moment before sending another enquiry.', ['status' => 429]);

    $name = sanitize_text_field((string) ($input['name'] ?? ''));
    $email = sanitize_email((string) ($input['email'] ?? ''));
    $phone = sanitize_text_field((string) ($input['phone'] ?? ''));
    $message = sanitize_textarea_field((string) ($input['message'] ?? ''));
    if ($name === '' || strlen($name) > 100 || !is_email($email) || strlen($email) > 190 || $phone === '' || strlen($phone) > 40 || $message === '' || strlen($message) > 3000) {
        return new WP_Error('invalid_fields', 'Please enter your name, a valid email, phone number, and a short message.', ['status' => 422]);
    }

    $lead_id = wp_insert_post([
        'post_type' => 'sr_tedx_lead',
        'post_status' => 'private',
        'post_title' => $name,
        'post_content' => $message,
    ], true);
    if (is_wp_error($lead_id)) return new WP_Error('save_failed', 'We could not save your enquiry. Please try again or email the Academy.', ['status' => 500]);
    update_post_meta($lead_id, '_sr_tedx_email', $email);
    update_post_meta($lead_id, '_sr_tedx_phone', $phone);
    update_post_meta($lead_id, '_sr_tedx_received_at', current_time('mysql'));
    if ($ip) set_transient($rate_key, '1', 30);

    $brand = function_exists('sr_lms_email_settings') ? (string) (sr_lms_email_settings()['brand'] ?? 'Shalini Rathod Academy') : 'Shalini Rathod Academy';
    $admin_email = sr_lms_tedx_enquiry_admin_email();
    $user_html = sr_lms_tedx_email_html('Thank you for your TEDx coaching enquiry', 'Hello ' . $name . ",\n\nThank you for reaching out about TEDx preparation and coaching. The Academy has received your details and will get back to you shortly.");
    $user_sent = wp_mail($email, 'We received your TEDx coaching enquiry | ' . $brand, $user_html, sr_lms_tedx_email_headers($admin_email));
    $admin_html = sr_lms_tedx_email_html('New TEDx coaching enquiry', 'A prospective client has requested TEDx preparation or coaching.', [
        'Name' => $name,
        'Email' => $email,
        'Phone / WhatsApp' => $phone,
        'Coaching needs' => $message,
        'Received' => current_time('mysql'),
    ]);
    $admin_sent = $admin_email ? wp_mail($admin_email, 'New TEDx coaching enquiry from ' . $name, $admin_html, sr_lms_tedx_email_headers($email)) : false;
    update_post_meta($lead_id, '_sr_tedx_user_email_sent', $user_sent ? '1' : '0');
    update_post_meta($lead_id, '_sr_tedx_admin_email_sent', $admin_sent ? '1' : '0');

    return ['message' => 'Thank you, ' . $name . '. Your TEDx coaching enquiry has been received. We’ll get back to you shortly.'];
}
