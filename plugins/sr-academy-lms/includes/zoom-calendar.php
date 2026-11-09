<?php
if (!defined('ABSPATH')) exit;

/** Google Calendar free/busy integration for the existing consultation booking flow. */
const SR_LMS_GOOGLE_CALENDAR_OPTION = 'sr_lms_google_calendar';
const SR_LMS_GOOGLE_CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar.freebusy';

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
    echo '<div class="wrap"><h1>Google Calendar availability</h1><p>Connect the Google account whose calendar is linked in Zoom. The booking form reads only free/busy periods; event names and details remain private. Google Calendar is the availability source; Zoom can continue to be used for the actual meetings.</p>';
    if (isset($_GET['saved'])) echo '<div class="notice notice-success"><p>Google Calendar settings saved.</p></div>';
    if (isset($_GET['connected'])) echo '<div class="notice notice-success"><p>Google Calendar connected successfully.</p></div>';
    if (isset($_GET['tested'])) echo '<div class="notice notice-success"><p>Google Calendar access works and free/busy data was returned for today.</p></div>';
    if (isset($_GET['disconnected'])) echo '<div class="notice notice-success"><p>Google Calendar disconnected.</p></div>';
    if (isset($_GET['google_error'])) echo '<div class="notice notice-error"><p>' . esc_html(sanitize_text_field(wp_unslash($_GET['google_error']))) . '</p></div>';
    echo '<h2>Google Cloud setup</h2><ol><li>Enable the Google Calendar API in the Google Cloud project.</li><li>Create an OAuth client ID for a Web application and add this exact authorized redirect URI:<br><code>' . esc_html($redirect_uri) . '</code></li><li>Set the OAuth consent screen audience/test users so the account connecting the calendar can authorize it.</li><li>Enter the OAuth client credentials and calendar ID below, save, then connect the Google account.</li></ol><p>Only the <code>' . esc_html(SR_LMS_GOOGLE_CALENDAR_SCOPE) . '</code> scope is requested. If Google rejects the <code>.local</code> callback URI, complete authorization on a public HTTPS staging/production hostname configured as the redirect URI.</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('sr_lms_google_save'); echo '<input type="hidden" name="action" value="sr_lms_google_save">';
    echo '<table class="form-table"><tr><th><label for="google-client-id">OAuth Client ID</label></th><td><input class="regular-text" id="google-client-id" name="client_id" value="' . esc_attr($config['client_id']) . '" autocomplete="off"></td></tr>';
    echo '<tr><th><label for="google-client-secret">OAuth Client Secret</label></th><td><input class="regular-text" type="password" id="google-client-secret" name="client_secret" value="" autocomplete="new-password" placeholder="' . ($config['client_secret'] ? 'Saved; leave blank to keep current secret' : 'Enter OAuth client secret') . '"><p class="description">Stored server-side and never sent to the browser. You may instead define <code>SR_LMS_GOOGLE_CLIENT_ID</code> and <code>SR_LMS_GOOGLE_CLIENT_SECRET</code> in wp-config.php.</p></td></tr>';
    echo '<tr><th><label for="google-calendar-id">Calendar ID</label></th><td><input class="regular-text" id="google-calendar-id" name="calendar_id" value="' . esc_attr($config['calendar_id']) . '" placeholder="usually the Google account email"><p class="description">Use the Google Calendar ID from Settings → Integrate calendar. The connected account must have permission to see free/busy for it.</p></td></tr>';
    echo '<tr><th><label for="google-timezone">Timezone</label></th><td><input class="regular-text" id="google-timezone" name="timezone" value="' . esc_attr($config['timezone']) . '"><p class="description">Current site uses India time (Asia/Kolkata).</p></td></tr></table><p><button class="button button-primary">Save settings</button></p></form>';
    echo '<h2>Connection</h2><p>Status: <strong>' . ($connected ? 'Connected' : 'Not connected') . '</strong></p>';
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
    echo '<p><strong>Booking behavior:</strong> available times are checked against current Google Calendar busy periods. Submitting the form remains a request for the team to confirm; it does not reserve the slot or create a Zoom meeting.</p></div>';
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
    delete_transient('sr_lms_google_calendar_access_token');
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
