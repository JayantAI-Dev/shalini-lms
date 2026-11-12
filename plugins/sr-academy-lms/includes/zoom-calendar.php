<?php
if (!defined('ABSPATH')) exit;

/** Google Calendar availability and confirmed Zoom consultation booking. */
const SR_LMS_GOOGLE_CALENDAR_OPTION = 'sr_lms_google_calendar';
const SR_LMS_GOOGLE_CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar.freebusy https://www.googleapis.com/auth/calendar.events';
const SR_LMS_ZOOM_OPTION = 'sr_lms_zoom_meetings';

function sr_lms_consultation_install(): void {
    if (version_compare((string) get_option('sr_lms_consultation_schema', '0'), '2', '>=')) return;
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();
    dbDelta('CREATE TABLE ' . sr_lms_table('consultation_bookings') . " (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        gateway_order varchar(100) NOT NULL,
        gateway_payment varchar(100) DEFAULT NULL,
        mode varchar(8) NOT NULL,
        amount bigint unsigned NOT NULL,
        currency varchar(3) NOT NULL DEFAULT 'INR',
        status varchar(24) NOT NULL DEFAULT 'created',
        offer varchar(40) NOT NULL,
        duration smallint unsigned NOT NULL DEFAULT 0,
        booking_date date DEFAULT NULL,
        booking_time varchar(40) DEFAULT NULL,
        customer_name varchar(190) NOT NULL,
        customer_email varchar(190) NOT NULL,
        customer_phone varchar(40) NOT NULL,
        challenge text NOT NULL,
        zoom_meeting varchar(80) DEFAULT NULL,
        google_event varchar(190) DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY gateway_order (gateway_order),
        UNIQUE KEY gateway_payment (gateway_payment),
        KEY customer_email (customer_email),
        KEY status (status)
    ) ENGINE=InnoDB $charset;");
    update_option('sr_lms_consultation_schema', '2', false);
}
add_action('init', 'sr_lms_consultation_install', 12);

function sr_lms_google_calendar_config(): array {
    $saved = get_option(SR_LMS_GOOGLE_CALENDAR_OPTION, []);
    $saved = is_array($saved) ? $saved : [];
    return [
        'client_id' => defined('SR_LMS_GOOGLE_CLIENT_ID') ? SR_LMS_GOOGLE_CLIENT_ID : (string) ($saved['client_id'] ?? ''),
        'client_secret' => defined('SR_LMS_GOOGLE_CLIENT_SECRET') ? SR_LMS_GOOGLE_CLIENT_SECRET : (string) ($saved['client_secret'] ?? ''),
        'calendar_id' => defined('SR_LMS_GOOGLE_CALENDAR_ID') ? SR_LMS_GOOGLE_CALENDAR_ID : (string) ($saved['calendar_id'] ?? ''),
        'refresh_token' => (string) ($saved['refresh_token'] ?? ''),
        'timezone' => sanitize_text_field($saved['timezone'] ?? 'Asia/Kolkata'),
    ];
}

function sr_lms_zoom_config(): array {
    $saved = get_option(SR_LMS_ZOOM_OPTION, []);
    $saved = is_array($saved) ? $saved : [];
    return [
        'account_id' => (string) ($saved['account_id'] ?? ''),
        'client_id' => (string) ($saved['client_id'] ?? ''),
        'client_secret' => (string) ($saved['client_secret'] ?? ''),
        'host_email' => sanitize_email($saved['host_email'] ?? ''),
        'admin_email' => sanitize_email($saved['admin_email'] ?? get_option('admin_email')),
    ];
}

function sr_lms_zoom_ready(): bool {
    $config = sr_lms_zoom_config();
    return (bool) ($config['account_id'] && $config['client_id'] && $config['client_secret'] && is_email($config['host_email']));
}

function sr_lms_google_calendar_redirect_uri(): string {
    return admin_url('admin-post.php?action=sr_lms_google_oauth_callback');
}

function sr_lms_google_calendar_ready(): bool {
    $config = sr_lms_google_calendar_config();
    return (bool) ($config['client_id'] && $config['client_secret'] && $config['calendar_id'] && $config['refresh_token']);
}

function sr_lms_google_access_token() {
    $cached = get_transient('sr_lms_google_calendar_access_token');
    if ($cached) return $cached;
    $config = sr_lms_google_calendar_config();
    if (!$config['client_id'] || !$config['client_secret'] || !$config['refresh_token']) {
        return new WP_Error('google_not_connected', 'Google Calendar is not connected. An administrator must finish the Google Calendar setup.');
    }
    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'timeout' => 15,
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'refresh_token' => $config['refresh_token'],
            'grant_type' => 'refresh_token',
        ],
    ]);
    if (is_wp_error($response)) return $response;
    $data = json_decode(wp_remote_retrieve_body($response), true);
    $token = is_array($data) ? (string) ($data['access_token'] ?? '') : '';
    if (wp_remote_retrieve_response_code($response) !== 200 || !$token) {
        $reason = is_array($data) ? sanitize_text_field((string) ($data['error_description'] ?? $data['error'] ?? '')) : '';
        return new WP_Error('google_auth_failed', 'Google authorization failed. Reconnect the Google account.' . ($reason ? ' ' . $reason : ''));
    }
    set_transient('sr_lms_google_calendar_access_token', $token, max(60, (int) ($data['expires_in'] ?? 3600) - 120));
    return $token;
}

function sr_lms_google_freebusy(string $calendar_id, DateTimeImmutable $start, DateTimeImmutable $end, string $timezone) {
    $token = sr_lms_google_access_token();
    if (is_wp_error($token)) return $token;
    $request = static function (string $access_token) use ($calendar_id, $start, $end, $timezone) {
        return wp_remote_post('https://www.googleapis.com/calendar/v3/freeBusy', [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'timeMin' => $start->format(DateTimeInterface::RFC3339),
                'timeMax' => $end->format(DateTimeInterface::RFC3339),
                'timeZone' => $timezone,
                'items' => [['id' => $calendar_id]],
            ]),
        ]);
    };
    $response = $request($token);
    if (is_wp_error($response)) return $response;
    if (wp_remote_retrieve_response_code($response) === 401) {
        delete_transient('sr_lms_google_calendar_access_token');
        $token = sr_lms_google_access_token();
        if (is_wp_error($token)) return $token;
        $response = $request($token);
        if (is_wp_error($response)) return $response;
    }
    $status = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($status !== 200) {
        $message = is_array($data) ? sanitize_text_field((string) ($data['error']['message'] ?? '')) : '';
        return new WP_Error('google_freebusy_failed', 'Google Calendar returned HTTP ' . $status . ($message ? ': ' . $message : '.'));
    }
    $calendar = is_array($data) ? ($data['calendars'][$calendar_id] ?? null) : null;
    if (!is_array($calendar)) return new WP_Error('google_calendar_missing', 'Google did not return the configured calendar. Check the Calendar ID and its access.');
    if (!empty($calendar['errors'])) {
        $error = reset($calendar['errors']);
        $reason = is_array($error) ? sanitize_text_field((string) ($error['reason'] ?? '')) : '';
        return new WP_Error('google_calendar_access', 'Google Calendar could not read free/busy for this calendar.' . ($reason ? ' ' . $reason : ' Check the Calendar ID and sharing permissions.'));
    }
    return is_array($calendar['busy'] ?? null) ? $calendar['busy'] : [];
}

function sr_lms_zoom_access_token() {
    $cached = get_transient('sr_lms_zoom_access_token');
    if ($cached) return $cached;
    $config = sr_lms_zoom_config();
    if (!sr_lms_zoom_ready()) return new WP_Error('zoom_not_configured', 'Zoom meeting credentials are not configured.');
    $response = wp_remote_post(add_query_arg(['grant_type'=>'account_credentials','account_id'=>$config['account_id']], 'https://zoom.us/oauth/token'), [
        'timeout' => 20,
        'headers' => ['Authorization'=>'Basic '.base64_encode($config['client_id'].':'.$config['client_secret'])],
    ]);
    if (is_wp_error($response)) return $response;
    $data = json_decode(wp_remote_retrieve_body($response), true);
    $token = is_array($data) ? (string) ($data['access_token'] ?? '') : '';
    if (wp_remote_retrieve_response_code($response) !== 200 || !$token) {
        $message = is_array($data) ? sanitize_text_field((string) ($data['reason'] ?? $data['error'] ?? '')) : '';
        return new WP_Error('zoom_auth_failed', 'Zoom authorization failed.'.($message ? ' '.$message : ' Check the Server-to-Server OAuth credentials and scopes.'));
    }
    set_transient('sr_lms_zoom_access_token', $token, max(60, (int) ($data['expires_in'] ?? 3600) - 90));
    return $token;
}

function sr_lms_zoom_api(string $method, string $path, ?array $body = null) {
    $token = sr_lms_zoom_access_token();
    if (is_wp_error($token)) return $token;
    $request = static function (string $access_token) use ($method, $path, $body) {
        $args = ['method'=>$method,'timeout'=>20,'headers'=>['Authorization'=>'Bearer '.$access_token,'Content-Type'=>'application/json']];
        if ($body !== null) $args['body'] = wp_json_encode($body);
        return wp_remote_request('https://api.zoom.us/v2'.$path, $args);
    };
    $response = $request($token);
    if (is_wp_error($response)) return $response;
    if (wp_remote_retrieve_response_code($response) === 401) {
        delete_transient('sr_lms_zoom_access_token');
        $token = sr_lms_zoom_access_token();
        if (is_wp_error($token)) return $token;
        $response = $request($token);
        if (is_wp_error($response)) return $response;
    }
    $status = wp_remote_retrieve_response_code($response);
    if ($status < 200 || $status >= 300) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $message = is_array($data) ? sanitize_text_field((string) ($data['message'] ?? $data['reason'] ?? '')) : '';
        return new WP_Error('zoom_api_failed', 'Zoom API returned HTTP '.$status.($message ? ': '.$message : '.'));
    }
    return json_decode(wp_remote_retrieve_body($response), true) ?: [];
}

function sr_lms_google_create_calendar_event(array $event) {
    $token = sr_lms_google_access_token();
    if (is_wp_error($token)) return $token;
    $config = sr_lms_google_calendar_config();
    $url = add_query_arg('sendUpdates', 'all', 'https://www.googleapis.com/calendar/v3/calendars/'.rawurlencode($config['calendar_id']).'/events');
    $response = wp_remote_post($url, ['timeout'=>20,'headers'=>['Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'],'body'=>wp_json_encode($event)]);
    if (is_wp_error($response)) return $response;
    $status = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($status < 200 || $status >= 300 || empty($data['id'])) {
        $message = is_array($data) ? sanitize_text_field((string) ($data['error']['message'] ?? '')) : '';
        return new WP_Error('google_event_failed', 'Google Calendar event creation failed (HTTP '.$status.').'.($message ? ' '.$message : ' Reconnect Google with calendar.events permission.'));
    }
    return $data;
}

function sr_lms_google_delete_calendar_event(string $event_id) {
    $token = sr_lms_google_access_token();
    if (is_wp_error($token)) return $token;
    $config = sr_lms_google_calendar_config();
    $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($config['calendar_id']) . '/events/' . rawurlencode($event_id);
    $response = wp_remote_request($url, ['method'=>'DELETE','timeout'=>20,'headers'=>['Authorization'=>'Bearer '.$token]]);
    if (is_wp_error($response)) return $response;
    $status = wp_remote_retrieve_response_code($response);
    if ($status < 200 || $status >= 300) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $message = is_array($data) ? sanitize_text_field((string) ($data['error']['message'] ?? '')) : '';
        return new WP_Error('google_event_delete_failed', 'Google Calendar test-event cleanup failed (HTTP '.$status.').'.($message ? ' '.$message : ''));
    }
    return true;
}

function sr_lms_booking_slot_from_request(array $data) {
    $date = sanitize_text_field((string) ($data['booking_date'] ?? ''));
    $time = sanitize_text_field((string) ($data['booking_time'] ?? ''));
    $duration = absint($data['booking_duration'] ?? 0);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !in_array($duration, [15,30,60,90], true) || !preg_match('/^\s*(\d{1,2}):(\d{2})\s*(AM|PM)/i', $time, $matches)) {
        return new WP_Error('invalid_booking_slot', 'Please select a valid date and time slot.');
    }
    $config = sr_lms_google_calendar_config();
    $timezone = $config['timezone'] ?: 'Asia/Kolkata';
    try { $zone = new DateTimeZone($timezone); } catch (Exception $e) { return new WP_Error('invalid_booking_timezone', 'The booking timezone is not configured correctly.'); }
    $hour = (int) $matches[1]; $minute = (int) $matches[2]; $ampm = strtoupper($matches[3]);
    if ($hour < 1 || $hour > 12 || $minute > 59) return new WP_Error('invalid_booking_slot', 'Please select a valid time slot.');
    if ($ampm === 'AM') { if ($hour === 12) $hour = 0; } else { if ($hour !== 12) $hour += 12; }
    if (!in_array($hour * 60 + $minute, [600,690,840,990,1140], true)) return new WP_Error('invalid_booking_slot', 'Please choose one of the available time slots.');
    $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date.' '.sprintf('%02d:%02d',$hour,$minute), $zone);
    if (!$start || $start->format('Y-m-d') !== $date || $start <= new DateTimeImmutable('now', $zone) || $start > (new DateTimeImmutable('now', $zone))->modify('+180 days')) {
        return new WP_Error('invalid_booking_slot', 'Please choose a future date within the next six months.');
    }
    return ['start'=>$start,'end'=>$start->modify('+'.$duration.' minutes'),'duration'=>$duration,'timezone'=>$timezone];
}

function sr_lms_process_consultation_booking($contact_form, &$abort, $submission): void {
    if (!is_object($contact_form) || !method_exists($contact_form, 'title') || $contact_form->title() !== 'Consultation and service request') return;
    $data = $submission->get_posted_data();
    // The same CF7 form also accepts course/package enquiries without a scheduled slot.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($data['booking_date'] ?? ''))) return;
    // Paid coaching sessions must go through Razorpay's verified checkout endpoint.
    // Do not allow a direct CF7 POST to create a meeting without payment.
    $service_check = strtolower(trim((string) ($data['booking_service'] ?? '')));
    $paid_services = array_map('strtolower', ['1:1 Communication & Confidence','1:1 Image Styling & Confidence','Personalised Colour Analysis Consultation','Personalised Occasion Styling']);
    if (in_array($service_check, $paid_services, true)) {
        $abort = true;
        $submission->set_response($contact_form->filter_message('Please complete the secure checkout to confirm this scheduled session.'));
        return;
    }
    $fail = static function (string $message) use ($contact_form, $submission, &$abort): void {
        $abort = true;
        $submission->set_response($contact_form->filter_message($message));
    };
    $email = sanitize_email((string) ($data['booking_email'] ?? ''));
    $name = sanitize_text_field((string) ($data['booking_name'] ?? ''));
    $service = sanitize_text_field((string) ($data['booking_service'] ?? 'Consultation'));
    if (!is_email($email) || !$name) { $fail('Please enter your name and a valid email address.'); return; }
    $slot = sr_lms_booking_slot_from_request($data);
    if (is_wp_error($slot)) { $fail($slot->get_error_message()); return; }
    if (!sr_lms_google_calendar_ready() || !sr_lms_zoom_ready()) { $fail('Online booking is not fully configured yet. Please contact the Academy team.'); return; }

    // Use an atomic option lock so two simultaneous submissions cannot book the same slot.
    $lock_name = 'sr_lms_slot_'.md5($slot['start']->format('c'));
    $existing_lock = absint(get_option($lock_name, 0));
    if ($existing_lock && time() - $existing_lock > 12 * HOUR_IN_SECONDS) delete_option($lock_name);
    if (!add_option($lock_name, time(), '', false)) { $fail('This time is being booked right now. Please choose another available slot.'); return; }
    $event = null;
    try {
        $busy = sr_lms_google_freebusy(sr_lms_google_calendar_config()['calendar_id'], $slot['start'], $slot['end'], $slot['timezone']);
        if (is_wp_error($busy)) { $fail('We could not verify this time slot. Please try another slot or contact the Academy team.'); return; }
        foreach ($busy as $period) {
            $busy_start = strtotime($period['start'] ?? ''); $busy_end = strtotime($period['end'] ?? '');
            if ($busy_start && $busy_end && $slot['start']->getTimestamp() < $busy_end && $slot['end']->getTimestamp() > $busy_start) { $fail('That time has just become unavailable. Please choose another slot.'); return; }
        }

        $zoom = sr_lms_zoom_api('POST', '/users/'.rawurlencode(sr_lms_zoom_config()['host_email']).'/meetings', [
            'topic' => $service.' — '.$name,
            'type' => 2,
            'start_time' => $slot['start']->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'duration' => $slot['duration'],
            'timezone' => $slot['timezone'],
            'agenda' => 'Consultation booked through Shalini Rathod Academy.',
            'settings' => ['waiting_room'=>true,'join_before_host'=>false,'approval_type'=>2,'meeting_authentication'=>false],
        ]);
        if (is_wp_error($zoom)) { error_log('[SR Academy] Zoom meeting creation failed: '.$zoom->get_error_message()); $fail('We could not create the Zoom meeting. Please try again or contact the Academy team.'); return; }
        $join_url = esc_url_raw((string) ($zoom['join_url'] ?? ''));
        $meeting_id = sanitize_text_field((string) ($zoom['id'] ?? ''));
        if (!$join_url || !$meeting_id) {
            if ($meeting_id) sr_lms_zoom_api('DELETE','/meetings/'.rawurlencode($meeting_id));
            $fail('Zoom did not return meeting details. Please try again or contact the Academy team.');
            return;
        }

        $zoom_config = sr_lms_zoom_config();
        $attendees = [['email'=>$email,'displayName'=>$name]];
        if (is_email($zoom_config['admin_email']) && strtolower($zoom_config['admin_email']) !== strtolower($email)) $attendees[] = ['email'=>$zoom_config['admin_email']];
        $event = sr_lms_google_create_calendar_event([
            'summary' => $service.' — '.$name,
            'description' => "Consultation with {$name}.\n\nZoom meeting link: {$join_url}\nMeeting ID: {$meeting_id}",
            'start' => ['dateTime'=>$slot['start']->format(DateTimeInterface::RFC3339),'timeZone'=>$slot['timezone']],
            'end' => ['dateTime'=>$slot['end']->format(DateTimeInterface::RFC3339),'timeZone'=>$slot['timezone']],
            'attendees' => $attendees,
            'visibility' => 'private',
            'guestsCanModify' => false,
            'guestsCanInviteOthers' => false,
        ]);
        if (is_wp_error($event)) { error_log('[SR Academy] Google Calendar event creation failed: '.$event->get_error_message()); sr_lms_zoom_api('DELETE','/meetings/'.rawurlencode($meeting_id)); $fail('We could not add this booking to the Academy calendar. No booking was made; please try again or contact the Academy team.'); return; }

        $date_label = wp_date('j F Y', $slot['start']->getTimestamp(), $slot['start']->getTimezone());
        $time_label = $slot['start']->format('g:i A').' – '.$slot['end']->format('g:i A').' '.$slot['timezone'];
        $subject = 'Your consultation is booked: '.$service;
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $html = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:auto;color:#20372d"><h2>Consultation confirmed</h2><p>Hello '.esc_html($name).',</p><p>Your consultation has been scheduled.</p><p><strong>'.esc_html($service).'</strong><br>'.esc_html($date_label).' · '.esc_html($time_label).'</p><p><a href="'.esc_url($join_url).'" style="display:inline-block;background:#174b38;color:#fff;padding:14px 22px;border-radius:8px;text-decoration:none">Join your Zoom meeting</a></p><p>Calendar invitations have also been sent to you and the Academy team.</p><p>Warmly,<br>Shalini Rathod Academy</p></div>';
        wp_mail($email, $subject, $html, $headers);
        if (is_email($zoom_config['admin_email'])) wp_mail($zoom_config['admin_email'], 'New consultation booked: '.$service, str_replace('Hello '.esc_html($name).',','A consultation has been booked by '.esc_html($name).' ('.esc_html($email).').',$html), $headers);
    } finally {
        if (empty($event) || is_wp_error($event)) delete_option($lock_name);
    }
}
add_action('wpcf7_before_send_mail', 'sr_lms_process_consultation_booking', 10, 3);

/** Authoritative pricing for one-session consultation checkout (amounts in paise). */
function sr_lms_consultation_price(string $offer, int $duration): int {
    $prices = [
        'comm-call' => [30 => 375000, 60 => 799900],
        'comm-core' => [60 => 4999900],
        'comm-presence' => [60 => 7499900],
        'comm-commanding' => [60 => 9999900],
        'style-signature' => [60 => 7000000],
        'style-exclusive' => [60 => 9000000],
        'style-vip' => [60 => 11000000],
        'style-call' => [30 => 375000, 60 => 799900],
        'color-call' => [60 => 799900, 90 => 1399900],
        'occasion-call' => [30 => 375000, 60 => 799900],
    ];
    return (int) ($prices[$offer][$duration] ?? 0);
}

function sr_lms_consultation_normalize_phone(string $phone): string {
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) > 10 && str_starts_with($digits, '91')) $digits = substr($digits, 2);
    return $digits;
}

function sr_lms_consultation_purchased_offers(string $email, string $phone, string $mode): array {
    global $wpdb;
    $wanted_phone = sr_lms_consultation_normalize_phone($phone);
    if (!$wanted_phone) return [];
    $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT offer, customer_phone FROM ' . sr_lms_table('consultation_bookings') . " WHERE LOWER(customer_email)=LOWER(%s) AND mode=%s AND status='confirmed' AND booking_time='PACKAGE'",
        $email, $mode
    ), ARRAY_A);
    $package_offers = ['comm-core', 'comm-presence', 'comm-commanding', 'style-signature', 'style-exclusive', 'style-vip'];
    $purchased = [];
    foreach ((array)$rows as $row) {
        if (sr_lms_consultation_normalize_phone((string)($row['customer_phone'] ?? '')) !== $wanted_phone) continue;
        $offer = sanitize_key((string)($row['offer'] ?? ''));
        if (in_array($offer, $package_offers, true)) $purchased[] = $offer;
    }
    return array_values(array_unique($purchased));
}

function sr_lms_consultation_purchases(WP_REST_Request $request) {
    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $rate_key = 'sr_lms_package_lookup_rate_' . hash('sha256', $ip);
    $hits = (int)get_transient($rate_key);
    if ($hits >= 30) return new WP_Error('rate_limited', 'Please wait before checking package availability again.', ['status'=>429]);
    set_transient($rate_key, $hits + 1, HOUR_IN_SECONDS);
    $email = sanitize_email((string)$request->get_param('email'));
    $phone = sanitize_text_field((string)$request->get_param('phone'));
    $digits = sr_lms_consultation_normalize_phone($phone);
    if (!is_email($email) || strlen($digits) < 7 || strlen($digits) > 15) return new WP_Error('purchase_lookup_details', 'Enter your email and phone number to check past package purchases.', ['status'=>400]);
    $mode = (string)(sr_lms_payment_settings()['mode'] ?? 'test');
    $response = new WP_REST_Response(['offers'=>sr_lms_consultation_purchased_offers($email, $phone, $mode)]);
    $response->header('Cache-Control', 'private, no-store');
    return $response;
}
function sr_lms_consultation_order(WP_REST_Request $request) {
    if (!sr_lms_payment_ready()) return new WP_Error('checkout_unavailable', 'Secure checkout is not available right now.', ['status'=>503]);
    $offer = sanitize_key((string) $request->get_param('offer'));
    $package_offers = ['comm-core','comm-presence','comm-commanding','style-signature','style-exclusive','style-vip'];
    $is_package = in_array($offer, $package_offers, true);
    if ($is_package && (sr_lms_payment_settings()['mode'] ?? 'test') !== 'test') return new WP_Error('test_mode_required', 'Package checkout is currently available in test mode only.', ['status'=>503]);
    if (!$is_package && (!sr_lms_google_calendar_ready() || !sr_lms_zoom_ready())) return new WP_Error('booking_unavailable', 'Online booking is not fully configured yet.', ['status'=>503]);
    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $rate_key = 'sr_lms_consult_order_rate_' . hash('sha256', $ip);
    $hits = (int) get_transient($rate_key);
    if ($hits >= 10) return new WP_Error('rate_limited', 'Please wait before starting another checkout.', ['status'=>429]);
    set_transient($rate_key, $hits + 1, HOUR_IN_SECONDS);
    $duration = $is_package ? 60 : absint($request->get_param('duration'));
    $amount = sr_lms_consultation_price($offer, $duration);
    $name = sanitize_text_field((string) $request->get_param('name'));
    $email = sanitize_email((string) $request->get_param('email'));
    $phone = sanitize_text_field((string) $request->get_param('phone'));
    $challenge = sanitize_textarea_field((string) $request->get_param('challenge'));
    $date = $is_package ? '' : sanitize_text_field((string) $request->get_param('date'));
    $time = $is_package ? '' : sanitize_text_field((string) $request->get_param('time'));
    $service_names = [
        'comm-call'=>'1:1 Communication & Confidence',
        'comm-core'=>'Core Communication Package',
        'comm-presence'=>'Powerful Presence Package',
        'comm-commanding'=>'Commanding Presence Package',
        'style-signature'=>'Signature Experience Package',
        'style-exclusive'=>'Exclusive Transformation Package',
        'style-vip'=>'VIP Makeover Experience Package',
        'style-call'=>'1:1 Image Styling & Confidence',
        'color-call'=>'Personalised Colour Analysis Consultation',
        'occasion-call'=>'Personalised Occasion Styling',
    ];
    if (!$amount || !$name || !is_email($email) || !$phone || !isset($service_names[$offer])) return new WP_Error('booking_details', 'Please complete all required booking details.', ['status'=>400]);
    if (!$is_package) {
        $slot = sr_lms_booking_slot_from_request(['booking_date'=>$date,'booking_time'=>$time,'booking_duration'=>$duration]);
        if (is_wp_error($slot)) return new WP_Error($slot->get_error_code(), $slot->get_error_message(), ['status'=>400]);
        $busy = sr_lms_google_freebusy(sr_lms_google_calendar_config()['calendar_id'], $slot['start'], $slot['end'], $slot['timezone']);
        if (is_wp_error($busy)) return new WP_Error('availability_unavailable', 'We could not verify this time slot. Please try again.', ['status'=>503]);
        foreach ($busy as $period) {
            $busy_start = strtotime($period['start'] ?? ''); $busy_end = strtotime($period['end'] ?? '');
            if ($busy_start && $busy_end && $slot['start']->getTimestamp() < $busy_end && $slot['end']->getTimestamp() > $busy_start) return new WP_Error('slot_unavailable', 'That time has just become unavailable. Please choose another slot.', ['status'=>409]);
        }
    }
    $mode = (string) (sr_lms_payment_settings()['mode'] ?? 'test');
    $gateway = sr_lms_payment_api($mode, 'orders', [
        'amount'=>$amount, 'currency'=>'INR', 'receipt'=>($is_package ? 'package-' : 'consult-') . wp_generate_password(12, false, false),
        'notes'=>['service'=>$offer, 'session_minutes'=>$is_package ? '0' : (string)$duration],
    ], 'POST');
    if (is_wp_error($gateway)) return $gateway;
    if (!preg_match('/^order_[A-Za-z0-9]+$/D', (string)($gateway['id'] ?? '')) || (int)($gateway['amount'] ?? 0) !== $amount || ($gateway['currency'] ?? '') !== 'INR') return new WP_Error('gateway_order', 'Secure checkout could not be started. Please retry.', ['status'=>502]);
    $payload = [
        'order_id'=>$gateway['id'], 'mode'=>$mode, 'amount'=>$amount, 'offer'=>$offer, 'duration'=>$is_package ? 0 : $duration,
        'name'=>$name, 'email'=>$email, 'phone'=>$phone, 'challenge'=>$challenge, 'date'=>$date, 'time'=>$time,
        'service'=>$service_names[$offer], 'package'=>$is_package, 'created'=>time(), 'status'=>'pending',
    ];
    global $wpdb;
    $now = current_time('mysql', true);
    $saved = $wpdb->insert(sr_lms_table('consultation_bookings'), [
        'gateway_order'=>$gateway['id'], 'mode'=>$mode, 'amount'=>$amount, 'status'=>'created', 'offer'=>$offer,
        'duration'=>$is_package ? 0 : $duration, 'booking_date'=>$is_package ? '2099-01-01' : $date, 'booking_time'=>$is_package ? 'PACKAGE' : $time,
        'customer_name'=>$name, 'customer_email'=>$email, 'customer_phone'=>$phone, 'challenge'=>$challenge,
        'created_at'=>$now, 'updated_at'=>$now,
    ]);
    if (!$saved) { error_log('[SR Academy] Purchase request storage failed: ' . sanitize_text_field((string)$wpdb->last_error)); return new WP_Error('booking_storage', 'Could not save the purchase request. Please retry before paying.', ['status'=>500]); }
    set_transient('sr_lms_consultation_' . $gateway['id'], $payload, DAY_IN_SECONDS);
    return new WP_REST_Response([
        'order_id'=>$gateway['id'], 'key'=>sr_lms_payment_credentials($mode)['key'], 'amount'=>$amount, 'currency'=>'INR',
        'name'=>'Shalini Rathod Academy', 'description'=>$service_names[$offer] . ($is_package ? ' · Package' : ' · ' . $duration . ' minutes'),
        'test'=>$mode==='test',
    ]);
}

/** Creates Zoom + Google Calendar records after verified payment. */
function sr_lms_create_paid_consultation(array $booking) {
    $slot = sr_lms_booking_slot_from_request(['booking_date'=>$booking['date'],'booking_time'=>$booking['time'],'booking_duration'=>$booking['duration']]);
    if (is_wp_error($slot)) return $slot;
    $busy = sr_lms_google_freebusy(sr_lms_google_calendar_config()['calendar_id'], $slot['start'], $slot['end'], $slot['timezone']);
    if (is_wp_error($busy)) return $busy;
    foreach ($busy as $period) {
        $busy_start = strtotime($period['start'] ?? ''); $busy_end = strtotime($period['end'] ?? '');
        if ($busy_start && $busy_end && $slot['start']->getTimestamp() < $busy_end && $slot['end']->getTimestamp() > $busy_start) return new WP_Error('slot_unavailable', 'The selected time has just been booked.');
    }
    $lock = 'sr_lms_slot_' . md5($slot['start']->format('c'));
    if (!add_option($lock, time(), '', false)) return new WP_Error('slot_unavailable', 'The selected time is currently being booked.');
    $meeting_id = ''; $event_id = '';
    try {
        $zoom = sr_lms_zoom_api('POST', '/users/' . rawurlencode(sr_lms_zoom_config()['host_email']) . '/meetings', [
            'topic' => $booking['service'] . ' — ' . $booking['name'], 'type' => 2,
            'start_time' => $slot['start']->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'duration' => $slot['duration'], 'timezone' => $slot['timezone'],
            'agenda' => 'Consultation booked through Shalini Rathod Academy.',
            'settings' => ['waiting_room'=>true,'join_before_host'=>false,'approval_type'=>2,'meeting_authentication'=>false],
        ]);
        if (is_wp_error($zoom)) return $zoom;
        $meeting_id = sanitize_text_field((string) ($zoom['id'] ?? ''));
        $join_url = esc_url_raw((string) ($zoom['join_url'] ?? ''));
        if (!$meeting_id || !$join_url) return new WP_Error('zoom_meeting', 'Zoom did not return meeting details.');
        $config = sr_lms_zoom_config();
        $attendees = [['email'=>$booking['email'],'displayName'=>$booking['name']]];
        if (is_email($config['admin_email']) && strtolower($config['admin_email']) !== strtolower($booking['email'])) $attendees[] = ['email'=>$config['admin_email']];
        $event = sr_lms_google_create_calendar_event([
            'summary' => $booking['service'] . ' — ' . $booking['name'],
            'description' => "Consultation with {$booking['name']}.\n\nZoom meeting link: {$join_url}\nMeeting ID: {$meeting_id}",
            'start' => ['dateTime'=>$slot['start']->format(DateTimeInterface::RFC3339),'timeZone'=>$slot['timezone']],
            'end' => ['dateTime'=>$slot['end']->format(DateTimeInterface::RFC3339),'timeZone'=>$slot['timezone']],
            'attendees' => $attendees, 'visibility'=>'private', 'guestsCanModify'=>false, 'guestsCanInviteOthers'=>false,
        ]);
        if (is_wp_error($event)) return $event;
        $event_id = (string) ($event['id'] ?? '');
        $when = wp_date('j F Y', $slot['start']->getTimestamp(), $slot['start']->getTimezone()) . ' · ' . $slot['start']->format('g:i A') . ' – ' . $slot['end']->format('g:i A') . ' ' . $slot['timezone'];
        $subject = 'Consultation confirmed: ' . $booking['service'];
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $html = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:auto;color:#20372d"><h2>Consultation confirmed</h2><p>Hello '.esc_html($booking['name']).',</p><p>Your payment is confirmed and your consultation is scheduled.</p><p><strong>'.esc_html($booking['service']).'</strong><br>'.esc_html($when).'</p><p><a href="'.esc_url($join_url).'" style="display:inline-block;background:#174b38;color:#fff;padding:14px 22px;border-radius:8px;text-decoration:none">Join your Zoom meeting</a></p><p>A Google Calendar invitation has been sent to you and the Academy team.</p><p>Warmly,<br>Shalini Rathod Academy</p></div>';
        $sent_user = wp_mail($booking['email'], $subject, $html, $headers);
        $sent_admin = is_email($config['admin_email']) && strtolower($config['admin_email']) !== strtolower($booking['email']) && wp_mail($config['admin_email'], 'New paid consultation: ' . $booking['service'], '<p>Booking for '.esc_html($booking['name']).' ('.esc_html($booking['email']).') · '.esc_html($when).'</p><p>Payment: INR '.esc_html(number_format($booking['amount']/100,2)).' · '.esc_html($booking['order_id']).'</p><p>Zoom: <a href="'.esc_url($join_url).'">Join meeting</a></p>', $headers);
        if (!$sent_user || !$sent_admin) error_log('[SR Academy] Paid consultation email delivery failed for order ' . sanitize_text_field($booking['order_id']));
        return ['meeting_id'=>$meeting_id,'event_id'=>$event_id,'date_label'=>$when,'join_url'=>$join_url,'calendar_invite_sent'=>!empty($attendees),'email_sent'=>(bool)$sent_user,'team_notified'=>(bool)$sent_admin];
    } finally {
        if (!$event_id) { if ($meeting_id) sr_lms_zoom_api('DELETE','/meetings/'.rawurlencode($meeting_id)); delete_option($lock); }
    }
}

function sr_lms_send_package_confirmation(array $purchase): array {
    $settings = sr_lms_email_settings();
    $template_enabled = !empty($settings['templates']['package_purchase']['enabled']);
    $user = (object)['display_name'=>(string)$purchase['name']];
    $context = [
        'offer_title'=>(string)$purchase['service'],
        'amount'=>'INR ' . number_format(((int)$purchase['amount']) / 100, 2),
        'payment_id'=>(string)($purchase['payment_id'] ?? ''),
        'order_id'=>(string)$purchase['order_id'],
        'cta_url'=>home_url('/contact/'),
    ];
    $mail = function_exists('sr_lms_email_render') ? sr_lms_email_render('package_purchase', $user, $context) : [];
    $sent_user = !empty($purchase['email_sent']) ? true : ($template_enabled && !empty($mail) && is_email($purchase['email'])
        ? wp_mail($purchase['email'], $mail['subject'], $mail['message'], $mail['headers'])
        : false);

    $config = sr_lms_zoom_config();
    $admin_email = sanitize_email($config['admin_email'] ?? '');
    $admin_html = '<div style="font-family:Arial,sans-serif;color:#20372d"><h2>New package purchase</h2><p><strong>' . esc_html($purchase['service']) . '</strong></p><p>Name: ' . esc_html($purchase['name']) . '<br>Email: ' . esc_html($purchase['email']) . '<br>Phone: ' . esc_html($purchase['phone']) . '</p><p>Amount: INR ' . esc_html(number_format(((int)$purchase['amount'])/100, 2)) . '<br>Razorpay order: ' . esc_html($purchase['order_id']) . '<br>Payment: ' . esc_html($purchase['payment_id'] ?? '') . '</p><p>Please contact the customer within 48 hours.</p></div>';
    $admin_headers = !empty($mail['headers']) ? $mail['headers'] : ['Content-Type: text/html; charset=UTF-8'];
    $sent_admin = !empty($purchase['team_notified']) ? true : (
        is_email($admin_email)
        && strtolower($admin_email) !== strtolower($purchase['email'])
        && wp_mail($admin_email, 'New package purchase: ' . sanitize_text_field($purchase['service']), $admin_html, $admin_headers)
    );

    if (!$sent_user || !$sent_admin) error_log('[SR Academy] Package purchase email delivery failed for order ' . sanitize_text_field($purchase['order_id']));
    return ['package'=>true, 'email_sent'=>(bool)$sent_user, 'team_notified'=>(bool)$sent_admin];
}

function sr_lms_consultation_verify(WP_REST_Request $request) {
    global $wpdb;
    $order_id = sanitize_text_field((string)$request->get_param('razorpay_order_id'));
    $payment_id = sanitize_text_field((string)$request->get_param('razorpay_payment_id'));
    $signature = (string)$request->get_param('razorpay_signature');
    if (!preg_match('/^order_[A-Za-z0-9]+$/D', $order_id) || !preg_match('/^pay_[A-Za-z0-9]+$/D', $payment_id)) return new WP_Error('payment_reference', 'Payment reference is invalid.', ['status'=>400]);
    $key = 'sr_lms_consultation_' . $order_id;
    $booking = get_transient($key);
    if (!is_array($booking)) {
        $stored = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . sr_lms_table('consultation_bookings') . ' WHERE gateway_order=%s', $order_id), ARRAY_A);
        $service_names = ['comm-call'=>'1:1 Communication & Confidence','comm-core'=>'Core Communication Package','comm-presence'=>'Powerful Presence Package','comm-commanding'=>'Commanding Presence Package','style-signature'=>'Signature Experience Package','style-exclusive'=>'Exclusive Transformation Package','style-vip'=>'VIP Makeover Experience Package','style-call'=>'1:1 Image Styling & Confidence','color-call'=>'Personalised Colour Analysis Consultation','occasion-call'=>'Personalised Occasion Styling'];
        if ($stored && isset($service_names[$stored['offer']])) {
            $is_package = in_array($stored['offer'], ['comm-core','comm-presence','comm-commanding','style-signature','style-exclusive','style-vip'], true);
            $booking = ['order_id'=>$order_id,'mode'=>$stored['mode'],'amount'=>(int)$stored['amount'],'offer'=>$stored['offer'],'duration'=>(int)$stored['duration'],'name'=>$stored['customer_name'],'email'=>$stored['customer_email'],'phone'=>$stored['customer_phone'],'challenge'=>$stored['challenge'],'date'=>$stored['booking_date'],'time'=>$stored['booking_time'],'service'=>$service_names[$stored['offer']],'package'=>$is_package,'status'=>$stored['status']==='confirmed'?'complete':'pending'];
            if ($booking['status']==='complete' && !$is_package) {
                $slot = sr_lms_booking_slot_from_request(['booking_date'=>$booking['date'],'booking_time'=>$booking['time'],'booking_duration'=>$booking['duration']]);
                if (!is_wp_error($slot)) $booking['date_label'] = wp_date('j F Y', $slot['start']->getTimestamp(), $slot['start']->getTimezone()) . ' · ' . $slot['start']->format('g:i A') . ' – ' . $slot['end']->format('g:i A') . ' ' . $slot['timezone'];
            }
            set_transient($key, $booking, DAY_IN_SECONDS);
        }
    }
    if (!is_array($booking) || ($booking['order_id'] ?? '') !== $order_id) return new WP_Error('booking_expired', 'This checkout expired. If you were charged, contact the Academy before paying again.', ['status'=>410]);
    $is_package = !empty($booking['package']) || in_array($booking['offer'] ?? '', ['comm-core','comm-presence','comm-commanding','style-signature','style-exclusive','style-vip'], true);
    $credentials = sr_lms_payment_credentials((string)$booking['mode']);
    if (!sr_lms_payment_signature($order_id . '|' . $payment_id, $signature, $credentials['secret'])) return new WP_Error('payment_signature', 'Payment could not be verified. Contact the Academy if you were charged.', ['status'=>400]);
    if (($booking['status'] ?? '') === 'complete') {
        if ($is_package && (empty($booking['email_sent']) || empty($booking['team_notified']))) {
            $booking['payment_id'] = $payment_id;
            $mail_result = sr_lms_send_package_confirmation($booking);
            $booking['email_sent'] = !empty($mail_result['email_sent']);
            $booking['team_notified'] = !empty($mail_result['team_notified']);
            set_transient($key, $booking, DAY_IN_SECONDS);
        }
        $response = ['confirmed'=>true,'name'=>$booking['name'],'service'=>$booking['service']];
        if ($is_package) { $response['follow_up'] = 'Our team will contact you within 48 hours.'; $response['email_sent'] = !empty($booking['email_sent']); $response['team_notified'] = !empty($booking['team_notified']); }
        else { $response['date_label'] = $booking['date_label'] ?? ''; $response['calendar_invite_sent'] = !empty($booking['calendar_invite_sent']); $response['email_sent'] = !empty($booking['email_sent']); $response['team_notified'] = !empty($booking['team_notified']); }
        return $response;
    }
    $payment = sr_lms_payment_api((string)$booking['mode'], 'payments/' . $payment_id);
    if (is_wp_error($payment)) return $payment;
    if (($payment['order_id'] ?? '') !== $order_id || (int)($payment['amount'] ?? 0) !== (int)$booking['amount'] || ($payment['currency'] ?? '') !== 'INR') return new WP_Error('payment_mismatch', 'Payment details do not match this booking.', ['status'=>400]);
    if (($payment['status'] ?? '') !== 'captured') return new WP_Error('payment_pending', 'Payment is not captured yet. Please wait and retry verification.', ['status'=>409]);
    $table = sr_lms_table('consultation_bookings');
    $now = current_time('mysql', true);
    $claimed = $wpdb->query($wpdb->prepare("UPDATE $table SET gateway_payment=%s,status='processing',updated_at=%s WHERE gateway_order=%s AND (status IN ('created','paid') OR (status='processing' AND updated_at < %s))", $payment_id, $now, $order_id, gmdate('Y-m-d H:i:s', time()-300)));
    if ($claimed !== 1) {
        $current = $wpdb->get_row($wpdb->prepare("SELECT status FROM $table WHERE gateway_order=%s", $order_id), ARRAY_A);
        if (($current['status'] ?? '') === 'confirmed') return ['confirmed'=>true,'name'=>$booking['name'],'service'=>$booking['service'],'follow_up'=>$is_package ? 'Our team will contact you within 48 hours.' : '', 'email_sent'=>!empty($booking['email_sent']), 'team_notified'=>!empty($booking['team_notified']), 'calendar_invite_sent'=>!empty($booking['calendar_invite_sent']), 'date_label'=>$booking['date_label'] ?? ''];
        return new WP_Error('booking_processing', 'Your payment is verified and the request is being completed. Please wait a moment before retrying.', ['status'=>409]);
    }
    $booking['processing'] = true;
    set_transient($key, $booking, DAY_IN_SECONDS);
    if ($is_package) $booking['payment_id'] = $payment_id;
    $result = $is_package ? sr_lms_send_package_confirmation($booking) : sr_lms_create_paid_consultation($booking);
    if (is_wp_error($result)) {
        $booking['processing'] = false;
        set_transient($key, $booking, DAY_IN_SECONDS);
        error_log('[SR Academy] Paid consultation fulfillment failed for order ' . $order_id . ': ' . $result->get_error_message());
        $refund = sr_lms_payment_api((string)$booking['mode'], 'payments/' . $payment_id . '/refunds', ['amount'=>(int)$booking['amount']], 'POST');
        if (is_wp_error($refund)) {
            $wpdb->update($table, ['status'=>'refund_required','updated_at'=>current_time('mysql',true)], ['gateway_order'=>$order_id]);
            $admin_email = sr_lms_zoom_config()['admin_email'];
            if (is_email($admin_email)) wp_mail($admin_email, 'Action required: consultation refund', 'A verified consultation payment could not be booked or refunded automatically. Order: '.esc_html($order_id).'; payment: '.esc_html($payment_id).'. Please review it in Razorpay and contact the customer.');
            return new WP_Error('booking_refund_required', 'Payment was received, but the slot could not be confirmed or refunded automatically. Do not pay again; the Academy team has been alerted to arrange your refund.', ['status'=>502]);
        }
        $booking['status'] = 'refund_requested';
        set_transient($key, $booking, DAY_IN_SECONDS);
        $wpdb->update($table, ['status'=>'refund_requested','updated_at'=>current_time('mysql',true)], ['gateway_order'=>$order_id]);
        return new WP_Error('booking_refunded', 'The selected time could not be confirmed, so a full refund has been requested. Please choose another slot.', ['status'=>409]);
    }
    $booking['status'] = 'complete';
    $booking['processing'] = false;
    if ($is_package) { $booking['email_sent'] = !empty($result['email_sent']); $booking['team_notified'] = !empty($result['team_notified']); }
    else { $booking['date_label'] = $result['date_label']; $booking['calendar_invite_sent'] = !empty($result['calendar_invite_sent']); $booking['email_sent'] = !empty($result['email_sent']); $booking['team_notified'] = !empty($result['team_notified']); }
    set_transient($key, $booking, DAY_IN_SECONDS);
    $wpdb->update($table, ['status'=>'confirmed','zoom_meeting'=>$result['meeting_id'] ?? null,'google_event'=>$result['event_id'] ?? null,'updated_at'=>current_time('mysql',true)], ['gateway_order'=>$order_id]);
    $response = ['confirmed'=>true,'name'=>$booking['name'],'service'=>$booking['service']];
    if ($is_package) { $response['follow_up'] = 'Our team will contact you within 48 hours.'; $response['email_sent'] = !empty($booking['email_sent']); $response['team_notified'] = !empty($booking['team_notified']); }
    else { $response['date_label'] = $result['date_label']; $response['calendar_invite_sent'] = !empty($booking['calendar_invite_sent']); $response['email_sent'] = !empty($booking['email_sent']); $response['team_notified'] = !empty($booking['team_notified']); }
    return $response;
}

add_action('rest_api_init', static function (): void {
    register_rest_route('sr-lms/v1', '/consultations/purchases', ['methods'=>'POST','permission_callback'=>'__return_true','callback'=>'sr_lms_consultation_purchases']);
    register_rest_route('sr-lms/v1', '/consultations/order', ['methods'=>'POST','permission_callback'=>'__return_true','callback'=>'sr_lms_consultation_order']);
    register_rest_route('sr-lms/v1', '/consultations/verify', ['methods'=>'POST','permission_callback'=>'__return_true','callback'=>'sr_lms_consultation_verify']);
});

function sr_lms_google_calendar_slots(WP_REST_Request $request) {
    $date = (string) $request->get_param('date');
    $duration = absint($request->get_param('duration'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !in_array($duration, [15, 30, 60, 90], true)) {
        return new WP_Error('invalid_schedule', 'Choose a valid date and session length.', ['status' => 400]);
    }
    $config = sr_lms_google_calendar_config();
    $timezone = $config['timezone'] ?: 'Asia/Kolkata';
    try { $zone = new DateTimeZone($timezone); } catch (Exception $e) { $zone = new DateTimeZone('Asia/Kolkata'); $timezone = 'Asia/Kolkata'; }
    $local_start = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $zone);
    $today = new DateTimeImmutable('today', $zone);
    if (!$local_start || $local_start->format('Y-m-d') !== $date || $local_start < $today || $local_start > $today->modify('+180 days')) {
        return new WP_Error('invalid_schedule', 'Choose a date within the next six months.', ['status' => 400]);
    }
    if (!sr_lms_google_calendar_ready()) return new WP_Error('availability_unavailable', 'Live Google Calendar availability is not configured yet.', ['status' => 503]);
    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $rate_key = 'sr_lms_calendar_rate_' . hash('sha256', $ip);
    $hits = (int) get_transient($rate_key);
    if ($hits >= 60) return new WP_Error('rate_limited', 'Please wait a moment before checking availability again.', ['status' => 429]);
    set_transient($rate_key, $hits + 1, HOUR_IN_SECONDS);

    $day_start = $local_start->setTime(0, 0);
    $busy = sr_lms_google_freebusy($config['calendar_id'], $day_start, $day_start->modify('+1 day'), $timezone);
    if (is_wp_error($busy)) return new WP_Error('availability_unavailable', 'Google Calendar did not return availability. Please try again later.', ['status' => 503, 'calendar_detail' => $busy->get_error_message()]);
    $busy_periods = array_map(static function ($period) {
        return [strtotime($period['start'] ?? ''), strtotime($period['end'] ?? '')];
    }, $busy);
    $starts = [600, 690, 840, 990, 1140];
    $slots = [];
    foreach ($starts as $minutes) {
        $slot_start = $local_start->setTime(intdiv($minutes, 60), $minutes % 60);
        $slot_end = $slot_start->modify('+' . $duration . ' minutes');
        $start_ts = $slot_start->getTimestamp(); $end_ts = $slot_end->getTimestamp();
        $available = !($date === $today->format('Y-m-d') && $start_ts <= time());
        foreach ($busy_periods as [$busy_start, $busy_end]) {
            if ($busy_start && $busy_end && $start_ts < $busy_end && $end_ts > $busy_start) { $available = false; break; }
        }
        $slots[] = ['start' => $slot_start->format('h:i A'), 'end' => $slot_end->format('h:i A'), 'available' => $available];
    }
    return rest_ensure_response(['date' => $date, 'duration' => $duration, 'timezone' => $timezone, 'slots' => $slots]);
}

add_action('rest_api_init', static function () {
    register_rest_route('sr-lms/v1', '/calendar-availability', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'sr_lms_google_calendar_slots',
        'permission_callback' => '__return_true',
        'args' => [
            'date' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'duration' => ['required' => true, 'sanitize_callback' => 'absint'],
        ],
    ]);
});

add_action('admin_menu', static function () {
    add_submenu_page('sr-academy-lms', 'Google Calendar', 'Google Calendar', 'manage_options', 'sr-lms-zoom-calendar', 'sr_lms_google_calendar_admin_screen');
});
add_action('admin_post_sr_lms_google_save', 'sr_lms_google_calendar_save');
add_action('admin_post_sr_lms_google_connect', 'sr_lms_google_calendar_connect');
add_action('admin_post_sr_lms_google_oauth_callback', 'sr_lms_google_calendar_callback');
add_action('admin_post_sr_lms_google_disconnect', 'sr_lms_google_calendar_disconnect');
add_action('admin_post_sr_lms_google_test', 'sr_lms_google_calendar_test');

function sr_lms_google_calendar_admin_screen(): void {
    if (!current_user_can('manage_options')) return;
    $config = sr_lms_google_calendar_config();
    $connected = !empty($config['refresh_token']);
    $redirect_uri = sr_lms_google_calendar_redirect_uri();
    $zoom_config = sr_lms_zoom_config();
    echo '<div class="wrap"><h1>Bookings: Zoom &amp; Google Calendar</h1><p>Available slots are checked against the admin calendar. When a visitor submits a consultation booking, the site creates a Zoom meeting and a private Google Calendar event, then emails the meeting details to the visitor and admin.</p>';
    if (isset($_GET['saved'])) echo '<div class="notice notice-success"><p>Google Calendar settings saved.</p></div>';
    if (isset($_GET['connected'])) echo '<div class="notice notice-success"><p>Google Calendar connected successfully.</p></div>';
    if (isset($_GET['tested'])) echo '<div class="notice notice-success"><p>Google Calendar access works and free/busy data was returned for today.</p></div>';
    if (isset($_GET['flow_tested'])) echo '<div class="notice notice-success"><p>Full booking integration verified: Google free/busy and event write passed, and Zoom meeting create/delete passed. Temporary test records were removed.</p></div>';
    if (isset($_GET['flow_error'])) echo '<div class="notice notice-error"><p>' . esc_html(sanitize_text_field(wp_unslash($_GET['flow_error']))) . '</p></div>';
    if (isset($_GET['disconnected'])) echo '<div class="notice notice-success"><p>Google Calendar disconnected.</p></div>';
    if (isset($_GET['google_error'])) echo '<div class="notice notice-error"><p>' . esc_html(sanitize_text_field(wp_unslash($_GET['google_error']))) . '</p></div>';
    echo '<h2>Google Calendar</h2><p>The connected Google account is used to check availability and create private calendar events for confirmed bookings. Requested scopes: <code>' . esc_html(SR_LMS_GOOGLE_CALENDAR_SCOPE) . '</code>.</p><p>OAuth redirect URI (must match your Google Cloud OAuth client):<br><code>' . esc_html($redirect_uri) . '</code></p>';
    echo '<form id="sr-lms-google-settings" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('sr_lms_google_save'); echo '<input type="hidden" name="action" value="sr_lms_google_save">';
    echo '<table class="form-table"><tr><th><label for="google-client-id">OAuth Client ID</label></th><td><input class="regular-text" id="google-client-id" name="client_id" value="' . esc_attr($config['client_id']) . '" autocomplete="off"></td></tr>';
    echo '<tr><th><label for="google-client-secret">OAuth Client Secret</label></th><td><input class="regular-text" type="password" id="google-client-secret" name="client_secret" value="" autocomplete="new-password" placeholder="' . ($config['client_secret'] ? 'Saved; leave blank to keep current secret' : 'Enter OAuth client secret') . '"><p class="description">Stored server-side and never sent to the browser. You may instead define <code>SR_LMS_GOOGLE_CLIENT_ID</code> and <code>SR_LMS_GOOGLE_CLIENT_SECRET</code> in wp-config.php.</p></td></tr>';
    echo '<tr><th><label for="google-calendar-id">Calendar ID</label></th><td><input class="regular-text" id="google-calendar-id" name="calendar_id" value="' . esc_attr($config['calendar_id']) . '" placeholder="usually the Google account email"><p class="description">The connected account must own this calendar or have permission to create events on it.</p></td></tr>';
    echo '<tr><th><label for="google-timezone">Timezone</label></th><td><input class="regular-text" id="google-timezone" name="timezone" value="' . esc_attr($config['timezone']) . '"><p class="description">Current site uses India time (Asia/Kolkata).</p></td></tr></table><p><button class="button button-primary">Save settings</button></p></form>';
    echo '<h2>Google connection</h2><p>Status: <strong>' . ($connected ? 'Connected' : 'Not connected') . '</strong></p>';
    if ($config['client_id'] && $config['client_secret'] && $config['calendar_id']) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'; wp_nonce_field('sr_lms_google_connect');
        echo '<input type="hidden" name="action" value="sr_lms_google_connect"><p><button class="button button-secondary">' . ($connected ? 'Reconnect Google account' : 'Connect Google account') . '</button></p></form>';
    } else echo '<p>Save the client ID, client secret and calendar ID before connecting.</p>';
    if ($connected) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'; wp_nonce_field('sr_lms_google_test');
        echo '<input type="hidden" name="action" value="sr_lms_google_test"><p><button class="button button-secondary">Test calendar access</button></p></form>';
    }
    if ($connected && sr_lms_zoom_ready()) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'Run a full integration check? It briefly creates and then removes a private Google Calendar test event and a Zoom test meeting. No invitations are sent.\')">';
        wp_nonce_field('sr_lms_booking_flow_test');
        echo '<input type="hidden" name="action" value="sr_lms_booking_flow_test"><p><button class="button button-primary">Verify full booking setup</button> <span class="description">Checks free/busy, Google event write access, and Zoom meeting create/delete. Temporary test items are removed immediately.</span></p></form>';
    }
    if ($connected) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'; wp_nonce_field('sr_lms_google_disconnect');
        echo '<input type="hidden" name="action" value="sr_lms_google_disconnect"><p><button class="button">Disconnect Google account</button></p></form>';
    }
    echo '<hr><h2>Zoom Server-to-Server OAuth</h2><p>Create or use a Server-to-Server OAuth app in the Zoom App Marketplace under the account that will host consultations. Add the granular scope <code>meeting:write:meeting:admin</code>, activate the app, then copy Account ID, Client ID and Client Secret from App Credentials into this form. Select the licensed Zoom user email that should host meetings. Credentials remain on the server.</p><p>Zoom setup status: <strong>' . (sr_lms_zoom_ready() ? 'Credentials saved' : 'Needs credentials') . '</strong></p>';
    echo '<table class="form-table"><tr><th><label for="zoom-account-id">Account ID</label></th><td><input class="regular-text" form="sr-lms-google-settings" id="zoom-account-id" name="zoom_account_id" value="' . esc_attr($zoom_config['account_id']) . '"></td></tr>';
    echo '<tr><th><label for="zoom-client-id">Client ID</label></th><td><input class="regular-text" form="sr-lms-google-settings" id="zoom-client-id" name="zoom_client_id" value="' . esc_attr($zoom_config['client_id']) . '" autocomplete="off"></td></tr>';
    echo '<tr><th><label for="zoom-client-secret">Client Secret</label></th><td><input class="regular-text" form="sr-lms-google-settings" type="password" id="zoom-client-secret" name="zoom_client_secret" value="" autocomplete="new-password" placeholder="' . ($zoom_config['client_secret'] ? 'Saved; leave blank to keep current secret' : 'Enter Zoom client secret') . '"></td></tr>';
    echo '<tr><th><label for="zoom-host-email">Zoom host email</label></th><td><input class="regular-text" form="sr-lms-google-settings" type="email" id="zoom-host-email" name="zoom_host_email" value="' . esc_attr($zoom_config['host_email']) . '" placeholder="host account email"></td></tr>';
    echo '<tr><th><label for="booking-admin-email">Booking notification email</label></th><td><input class="regular-text" form="sr-lms-google-settings" type="email" id="booking-admin-email" name="booking_admin_email" value="' . esc_attr($zoom_config['admin_email']) . '"><p class="description">This admin receives the booking email and Google Calendar invitation.</p></td></tr></table><p><button class="button button-primary" form="sr-lms-google-settings">Save all settings</button></p>';
    echo '<p><strong>Booking behavior:</strong> the chosen slot is rechecked before booking. A successful booking creates a Zoom meeting and a private Google Calendar event with invitations sent to the attendee and admin.</p></div>';
}

function sr_lms_google_calendar_save(): void {
    if (!current_user_can('manage_options')) wp_die('Administrator permission required.', 'Forbidden', ['response' => 403]);
    check_admin_referer('sr_lms_google_save');
    $old = sr_lms_google_calendar_config();
    $client_id = defined('SR_LMS_GOOGLE_CLIENT_ID') ? SR_LMS_GOOGLE_CLIENT_ID : sanitize_text_field(wp_unslash($_POST['client_id'] ?? ''));
    $client_secret = defined('SR_LMS_GOOGLE_CLIENT_SECRET') ? SR_LMS_GOOGLE_CLIENT_SECRET : (trim((string) wp_unslash($_POST['client_secret'] ?? '')) ?: $old['client_secret']);
    $calendar_id = defined('SR_LMS_GOOGLE_CALENDAR_ID') ? SR_LMS_GOOGLE_CALENDAR_ID : sanitize_text_field(wp_unslash($_POST['calendar_id'] ?? ''));
    $timezone = sanitize_text_field(wp_unslash($_POST['timezone'] ?? 'Asia/Kolkata'));
    try { new DateTimeZone($timezone); } catch (Exception $e) { $timezone = 'Asia/Kolkata'; }
    if (!$client_id || !$client_secret || !$calendar_id) wp_die('Enter the Google OAuth client ID, client secret and calendar ID.', 'Incomplete settings', ['response' => 400]);
    update_option(SR_LMS_GOOGLE_CALENDAR_OPTION, ['client_id' => $client_id, 'client_secret' => $client_secret, 'calendar_id' => $calendar_id, 'refresh_token' => $old['refresh_token'], 'timezone' => $timezone], false);
    $old_zoom = sr_lms_zoom_config();
    $zoom_account_id = sanitize_text_field(wp_unslash($_POST['zoom_account_id'] ?? $old_zoom['account_id']));
    $zoom_client_id = sanitize_text_field(wp_unslash($_POST['zoom_client_id'] ?? $old_zoom['client_id']));
    $zoom_client_secret = trim((string) wp_unslash($_POST['zoom_client_secret'] ?? '')) ?: $old_zoom['client_secret'];
    $zoom_host_email = sanitize_email(wp_unslash($_POST['zoom_host_email'] ?? $old_zoom['host_email']));
    $booking_admin_email = sanitize_email(wp_unslash($_POST['booking_admin_email'] ?? $old_zoom['admin_email']));
    update_option(SR_LMS_ZOOM_OPTION, ['account_id'=>$zoom_account_id,'client_id'=>$zoom_client_id,'client_secret'=>$zoom_client_secret,'host_email'=>$zoom_host_email,'admin_email'=>$booking_admin_email ?: get_option('admin_email')], false);
    delete_transient('sr_lms_google_calendar_access_token');
    delete_transient('sr_lms_zoom_access_token');
    wp_safe_redirect(admin_url('admin.php?page=sr-lms-zoom-calendar&saved=1')); exit;
}

function sr_lms_google_calendar_connect(): void {
    if (!current_user_can('manage_options')) wp_die('Administrator permission required.', 'Forbidden', ['response' => 403]);
    check_admin_referer('sr_lms_google_connect');
    if (!sr_lms_google_calendar_config()['client_id'] || !sr_lms_google_calendar_config()['client_secret'] || !sr_lms_google_calendar_config()['calendar_id']) {
        wp_safe_redirect(admin_url('admin.php?page=sr-lms-zoom-calendar&google_error=' . rawurlencode('Save the Google OAuth credentials and Calendar ID first.'))); exit;
    }
    $state = wp_generate_password(40, false, false);
    set_transient('sr_lms_google_oauth_state_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS);
    $url = add_query_arg([
        'client_id' => sr_lms_google_calendar_config()['client_id'],
        'redirect_uri' => sr_lms_google_calendar_redirect_uri(),
        'response_type' => 'code',
        'scope' => SR_LMS_GOOGLE_CALENDAR_SCOPE,
        'access_type' => 'offline',
        'prompt' => 'consent',
        'include_granted_scopes' => 'true',
        'state' => $state,
    ], 'https://accounts.google.com/o/oauth2/v2/auth');
    wp_redirect($url); exit;
}

function sr_lms_google_calendar_callback(): void {
    if (!current_user_can('manage_options')) wp_die('Administrator permission required.', 'Forbidden', ['response' => 403]);
    $page = admin_url('admin.php?page=sr-lms-zoom-calendar');
    if (!empty($_GET['error'])) {
        wp_safe_redirect(add_query_arg('google_error', rawurlencode('Google authorization was not completed.'), $page)); exit;
    }
    $state = sanitize_text_field(wp_unslash($_GET['state'] ?? ''));
    $expected = get_transient('sr_lms_google_oauth_state_' . get_current_user_id());
    delete_transient('sr_lms_google_oauth_state_' . get_current_user_id());
    if (!$expected || !$state || !hash_equals((string) $expected, $state)) wp_die('Google authorization state did not match. Restart the connection flow.', 'Invalid OAuth state', ['response' => 400]);
    $code = sanitize_text_field(wp_unslash($_GET['code'] ?? ''));
    if (!$code) wp_die('Google did not return an authorization code.', 'Authorization failed', ['response' => 400]);
    $config = sr_lms_google_calendar_config();
    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'timeout' => 20,
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => sr_lms_google_calendar_redirect_uri(),
            'grant_type' => 'authorization_code',
        ],
    ]);
    if (is_wp_error($response)) {
        wp_safe_redirect(add_query_arg('google_error', rawurlencode('Could not contact Google to finish authorization.'), $page)); exit;
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    $refresh = is_array($data) ? (string) ($data['refresh_token'] ?? '') : '';
    $refresh = $refresh ?: (string) $config['refresh_token'];
    if (wp_remote_retrieve_response_code($response) !== 200 || !$refresh) {
        $reason = is_array($data) ? sanitize_text_field((string) ($data['error_description'] ?? $data['error'] ?? '')) : '';
        wp_safe_redirect(add_query_arg('google_error', rawurlencode('Google did not provide a refresh token. Retry Connect Google account and approve access.' . ($reason ? ' ' . $reason : '')), $page)); exit;
    }
    $config['refresh_token'] = $refresh;
    update_option(SR_LMS_GOOGLE_CALENDAR_OPTION, $config, false);
    delete_transient('sr_lms_google_calendar_access_token');
    wp_safe_redirect(add_query_arg('connected', '1', $page)); exit;
}

function sr_lms_google_calendar_disconnect(): void {
    if (!current_user_can('manage_options')) wp_die('Administrator permission required.', 'Forbidden', ['response' => 403]);
    check_admin_referer('sr_lms_google_disconnect');
    $config = sr_lms_google_calendar_config();
    $config['refresh_token'] = '';
    update_option(SR_LMS_GOOGLE_CALENDAR_OPTION, $config, false);
    delete_transient('sr_lms_google_calendar_access_token');
    wp_safe_redirect(admin_url('admin.php?page=sr-lms-zoom-calendar&disconnected=1')); exit;
}

function sr_lms_google_calendar_test(): void {
    if (!current_user_can('manage_options')) wp_die('Administrator permission required.', 'Forbidden', ['response' => 403]);
    check_admin_referer('sr_lms_google_test');
    $config = sr_lms_google_calendar_config();
    if (!sr_lms_google_calendar_ready()) {
        wp_safe_redirect(add_query_arg('google_error', rawurlencode('Connect Google and save a Calendar ID before testing.'), admin_url('admin.php?page=sr-lms-zoom-calendar'))); exit;
    }
    $zone = new DateTimeZone($config['timezone'] ?: 'Asia/Kolkata');
    $today = new DateTimeImmutable('today', $zone);
    $result = sr_lms_google_freebusy($config['calendar_id'], $today, $today->modify('+1 day'), $zone->getName());
    if (is_wp_error($result)) {
        wp_safe_redirect(add_query_arg('google_error', rawurlencode($result->get_error_message()), admin_url('admin.php?page=sr-lms-zoom-calendar'))); exit;
    }
    wp_safe_redirect(admin_url('admin.php?page=sr-lms-zoom-calendar&tested=1')); exit;
}

function sr_lms_booking_flow_test(): void {
    if (!current_user_can('manage_options')) wp_die('Administrator permission required.', 'Forbidden', ['response' => 403]);
    check_admin_referer('sr_lms_booking_flow_test');
    $page = admin_url('admin.php?page=sr-lms-zoom-calendar');
    if (!sr_lms_google_calendar_ready() || !sr_lms_zoom_ready()) {
        wp_safe_redirect(add_query_arg('flow_error', rawurlencode('Save and connect both Google Calendar and Zoom before running the full check.'), $page)); exit;
    }

    $config = sr_lms_google_calendar_config();
    $zone = new DateTimeZone($config['timezone'] ?: 'Asia/Kolkata');
    $start = (new DateTimeImmutable('now', $zone))->modify('+1 day')->setTime(12, 0, 0);
    $end = $start->modify('+15 minutes');
    $busy = sr_lms_google_freebusy($config['calendar_id'], $start, $end, $zone->getName());
    if (is_wp_error($busy)) {
        wp_safe_redirect(add_query_arg('flow_error', rawurlencode('Google availability check failed: '.$busy->get_error_message()), $page)); exit;
    }

    $zoom = sr_lms_zoom_api('POST', '/users/'.rawurlencode(sr_lms_zoom_config()['host_email']).'/meetings', [
        'topic' => 'Temporary LMS integration check',
        'type' => 2,
        'start_time' => $start->format('Y-m-d\TH:i:s'),
        'duration' => 15,
        'timezone' => $zone->getName(),
        'agenda' => 'Temporary test meeting; automatically removed.',
        'settings' => ['waiting_room'=>true,'join_before_host'=>false,'approval_type'=>2,'meeting_authentication'=>false],
    ]);
    if (is_wp_error($zoom)) {
        wp_safe_redirect(add_query_arg('flow_error', rawurlencode('Zoom meeting creation failed: '.$zoom->get_error_message()), $page)); exit;
    }
    $meeting_id = sanitize_text_field((string) ($zoom['id'] ?? ''));
    if (!$meeting_id) {
        wp_safe_redirect(add_query_arg('flow_error', rawurlencode('Zoom did not return a test meeting ID, so meeting-write permission could not be verified.'), $page)); exit;
    }

    $google_event = sr_lms_google_create_calendar_event([
        'summary' => 'Temporary LMS integration check',
        'description' => 'Temporary private test event; automatically removed.',
        'start' => ['dateTime'=>$start->format(DateTimeInterface::RFC3339),'timeZone'=>$zone->getName()],
        'end' => ['dateTime'=>$end->format(DateTimeInterface::RFC3339),'timeZone'=>$zone->getName()],
        'visibility' => 'private',
        'transparency' => 'transparent',
    ]);
    if (is_wp_error($google_event)) {
        $zoom_cleanup = sr_lms_zoom_api('DELETE', '/meetings/'.rawurlencode($meeting_id));
        $message = 'Google event creation failed: '.$google_event->get_error_message();
        if (is_wp_error($zoom_cleanup)) $message .= ' Temporary Zoom test meeting may remain; remove meeting ID '.$meeting_id.' manually.';
        wp_safe_redirect(add_query_arg('flow_error', rawurlencode($message), $page)); exit;
    }

    $cleanup_errors = [];
    $google_cleanup = sr_lms_google_delete_calendar_event(sanitize_text_field((string) ($google_event['id'] ?? '')));
    if (is_wp_error($google_cleanup)) $cleanup_errors[] = 'Google test event '.$google_event['id'].' could not be removed.';
    $zoom_cleanup = sr_lms_zoom_api('DELETE', '/meetings/'.rawurlencode($meeting_id));
    if (is_wp_error($zoom_cleanup)) $cleanup_errors[] = 'Zoom test meeting '.$meeting_id.' could not be removed.';
    if ($cleanup_errors) {
        wp_safe_redirect(add_query_arg('flow_error', rawurlencode('Access checks passed, but cleanup needs attention: '.implode(' ', $cleanup_errors)), $page)); exit;
    }
    wp_safe_redirect(add_query_arg('flow_tested', '1', $page)); exit;
}
add_action('admin_post_sr_lms_booking_flow_test', 'sr_lms_booking_flow_test');
