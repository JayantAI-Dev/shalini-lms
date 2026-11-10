<?php
if (!defined('ABSPATH')) exit;

/** Google Calendar availability and confirmed Zoom consultation booking. */
const SR_LMS_GOOGLE_CALENDAR_OPTION = 'sr_lms_google_calendar';
const SR_LMS_GOOGLE_CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar.freebusy https://www.googleapis.com/auth/calendar.events';
const SR_LMS_ZOOM_OPTION = 'sr_lms_zoom_meetings';

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
    if (isset($_GET['disconnected'])) echo '<div class="notice notice-success"><p>Google Calendar disconnected.</p></div>';
    if (isset($_GET['google_error'])) echo '<div class="notice notice-error"><p>' . esc_html(sanitize_text_field(wp_unslash($_GET['google_error']))) . '</p></div>';
    echo '<h2>Google Calendar</h2><p>The existing read-only connection must be reauthorized once to add the <code>calendar.events</code> permission, which lets the site create private events and send attendee invitations. Free/busy access remains enabled.</p><ol><li>Keep the Google Calendar API enabled and the same OAuth redirect URI:<br><code>' . esc_html($redirect_uri) . '</code></li><li>Enter the OAuth client credentials and calendar ID below, save, then reconnect and approve the expanded calendar permissions.</li></ol><p>Requested scopes: <code>' . esc_html(SR_LMS_GOOGLE_CALENDAR_SCOPE) . '</code>.</p>';
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
