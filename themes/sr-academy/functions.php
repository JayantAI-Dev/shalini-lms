<?php
/** WordPress integration for the original SR Academy design. */
defined('ABSPATH') || exit;

function sr_theme_pages(): array {
    return ['index.html'=>'home','about.html'=>'about','services.html'=>'services','contact.html'=>'contact','lms.html'=>'courses','lms-course.html'=>'course','lms-auth.html'=>'student-portal','lms-learn.html'=>'learn','package-enroll.html'=>'enroll','dashboard.html'=>'dashboard'];
}

/** Resolve source links without a base tag; preserve queries with either permalink mode. */
function sr_theme_url(string $source_filename): string {
    $parts = wp_parse_url(html_entity_decode($source_filename, ENT_QUOTES, 'UTF-8'));
    if (!is_array($parts)) { return home_url('/'); }
    $filename = basename($parts['path'] ?? 'index.html');
    $slug = sr_theme_pages()[$filename] ?? sanitize_title(pathinfo($filename, PATHINFO_FILENAME));
    if ($slug === 'home') { $url = home_url('/'); }
    elseif (function_exists('sr_lms_page_url')) { $url = sr_lms_page_url($slug); }
    else { $page = get_page_by_path($slug); $url = $page ? get_permalink($page) : add_query_arg('pagename', $slug, home_url('/')); }
    if (!empty($parts['query'])) { parse_str($parts['query'], $args); $url = add_query_arg($args, $url); }
    if (isset($parts['fragment'])) { $url .= '#' . rawurlencode($parts['fragment']); }
    return $url;
}

function sr_theme_asset(string $path): string {
    return trailingslashit(get_template_directory_uri()) . ltrim($path, '/');
}

function sr_theme_slug(): string {
    if (is_front_page()) { return 'home'; }
    return is_page() ? (string) get_post_field('post_name', get_queried_object_id()) : '';
}

function sr_theme_setup(): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    add_theme_support('custom-logo', ['height'=>54,'width'=>190,'flex-height'=>true,'flex-width'=>true]);
    register_nav_menus(['primary'=>'Primary navigation']);
    $GLOBALS['content_width'] = 1160;
}
add_action('after_setup_theme', 'sr_theme_setup');

add_filter('show_admin_bar', function(bool $show): bool {
    return !is_admin() && is_user_logged_in() && !current_user_can('edit_posts') ? false : $show;
});

function sr_theme_course_available(string $slug, array $course): bool {
    if (($course['availability'] ?? 'active') !== 'active') { return false; }
    return !function_exists('sr_lms_offers') || isset(sr_lms_offers()[$slug]);
}

function sr_theme_is_coaching_offer(): bool {
    return !isset($_GET['course']) && isset($_GET['offer']) && is_string($_GET['offer']) && in_array($_GET['offer'], ['comm-package','style-package'], true);
}

function sr_theme_enqueue(): void {
    $version = (string) wp_get_theme()->get('Version');
    $style_file = get_stylesheet_directory() . '/style.css';
    $script_file = get_template_directory() . '/assets/js/site.js';
    $style_version = is_file($style_file) ? (string) filemtime($style_file) : $version;
    $script_version = is_file($script_file) ? (string) filemtime($script_file) : $version;
    wp_enqueue_style('sr-fonts', sr_theme_asset('assets/fonts/fonts.css'), [], $version);
    wp_enqueue_style('sr-academy', get_stylesheet_uri(), ['sr-fonts'], $style_version);
    $site_dependencies = [];
    if (in_array(sr_theme_slug(), ['home','services'], true) && function_exists('sr_lms_payment_ready') && sr_lms_payment_ready()) {
        wp_enqueue_script('sr-lms-razorpay-checkout', 'https://checkout.razorpay.com/v1/checkout.js', [], null, true);
        $site_dependencies[] = 'sr-lms-razorpay-checkout';
    }
    wp_enqueue_script('sr-site', sr_theme_asset('assets/js/site.js'), $site_dependencies, $script_version, true);
    $pages = [];
    foreach (sr_theme_pages() as $filename=>$slug) { $pages[$filename] = sr_theme_url($filename); }
    $config = ['pages'=>$pages,'home'=>home_url('/'),'assetBase'=>trailingslashit(get_template_directory_uri()),'calendarAvailabilityUrl'=>rest_url('sr-lms/v1/calendar-availability')];
    if (in_array(sr_theme_slug(), ['home','services'], true) && function_exists('sr_lms_payment_ready')) {
        $config['consultationCheckout'] = [
            'enabled' => sr_lms_payment_ready() && function_exists('sr_lms_google_calendar_ready') && sr_lms_google_calendar_ready() && function_exists('sr_lms_zoom_ready') && sr_lms_zoom_ready(),
            'api' => rest_url('sr-lms/v1/consultations/'),
            'key' => sr_lms_payment_credentials((string) (sr_lms_payment_settings()['mode'] ?? 'test'))['key'] ?? '',
            'test' => (sr_lms_payment_settings()['mode'] ?? 'test') === 'test',
        ];
    }
    wp_add_inline_script('sr-site', 'window.SR_THEME=' . wp_json_encode($config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) . ';' . <<<'JS'
window.SR_THEME.url=function(source){const input=new URL(source,'https://sr-source.invalid/');const name=input.pathname.split('/').pop();const target=new URL(this.pages[name]||this.home,location.href);input.searchParams.forEach((value,key)=>target.searchParams.set(key,value));target.hash=input.hash;return target.href;};
window.SR_THEME.asset=function(path){return this.assetBase+path.replace(/^\/+/, '');};
JS
    , 'before');
    if (in_array(sr_theme_slug(), ['courses','course'], true)) {
        wp_enqueue_script('sr-catalog', sr_theme_asset('assets/js/catalog.js'), ['sr-site'], $version, true);
        $courses = function_exists('sr_lms_courses') ? sr_lms_courses() : [];
        wp_add_inline_script('sr-catalog', 'window.SHALINI_COURSES=' . wp_json_encode($courses, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) . ';', 'before');
    }
    if (sr_theme_slug() === 'enroll' && sr_theme_is_coaching_offer()) {
        wp_enqueue_script('sr-brochure', sr_theme_asset('assets/js/brochure.js'), ['sr-site'], $version, true);
    }
}
add_action('wp_enqueue_scripts', 'sr_theme_enqueue');

function sr_theme_metadata(): void {
    $descriptions = ['home'=>'Confidence and communication coaching, image consulting and personal styling for individuals and organizations.','about'=>'Learn about Shalini Rathod’s work in communication coaching, image consulting and personal style.','services'=>'Explore private communication coaching, image consulting, styling packages and self-paced courses.','contact'=>'Contact Shalini Rathod about consultations, coaching, styling, workshops and courses.','courses'=>'Explore communication, personal styling and confidence courses with guided learning.','course'=>'Explore course structure, lesson topics and learning outcomes from Shalini Rathod Academy.','enroll'=>'Explore private communication coaching and signature styling packages.'];
    $description = function_exists('sr_theme_field') ? (string) sr_theme_field('sr_seo_description') : '';
    if (!$description) { $description = function_exists('sr_theme_option') ? (string) sr_theme_option('sr_default_seo_description', $descriptions[sr_theme_slug()] ?? '') : ($descriptions[sr_theme_slug()] ?? ''); }
    if (!$description) { return; }
    echo '<meta name="description" content="' . esc_attr($description) . '">';
    echo '<meta property="og:type" content="website"><meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '"><meta property="og:title" content="' . esc_attr(wp_get_document_title()) . '"><meta property="og:description" content="' . esc_attr($description) . '"><meta property="og:image" content="' . esc_url(sr_theme_asset('assets/hero-seated.webp')) . '"><meta name="twitter:card" content="summary_large_image">';
}
add_action('wp_head', 'sr_theme_metadata', 2);
add_filter('wp_robots', function(array $robots): array {
    if (in_array(sr_theme_slug(), ['learn','student-portal','dashboard'], true)) { $robots['noindex'] = true; }
    return $robots;
});

add_filter('body_class', function(array $classes): array {
    $map = ['home'=>'home-page','about'=>'academy-page about-page','services'=>'services-page','contact'=>'academy-page contact-page','courses'=>'academy-page lms-shell','course'=>'academy-page lms-shell','student-portal'=>'academy-page lms-shell','learn'=>'academy-page lms-shell fresh-learn','enroll'=>'academy-page package-enrollment-page','dashboard'=>'academy-page lms-shell'];
    return array_merge($classes, explode(' ', $map[sr_theme_slug()] ?? 'academy-page'));
});

function sr_theme_lms_notice(): void {
    echo '<div class="sr-wp-content"><div class="sr-theme-notice" role="status"><h1>Student services unavailable</h1><p>Please activate the SR Academy LMS plugin to use student accounts, enrollment and your dashboard.</p></div></div>';
}

function sr_theme_navigation(bool $mobile = false): void {
    $locations = get_nav_menu_locations();
    $items = !empty($locations['primary']) ? wp_get_nav_menu_items($locations['primary']) : false;
    if ($items) {
        foreach ($items as $item) {
            if ((int) $item->menu_item_parent !== 0) { continue; }
            sr_theme_navigation_link($item->url, $item->title, $mobile, (int) $item->object_id === get_queried_object_id());
        }
        return;
    }
    $links = [['index.html','Home','home'],['services.html','1-on-1 Consultations','services'],['lms.html','Courses','courses'],['index.html#keynotes','Corporate & Keynotes','keynotes'],['about.html','About','about'],['contact.html','Contact','contact']];
    foreach ($links as [$file,$title,$slug]) {
        $active = sr_theme_slug() === $slug || ($slug === 'courses' && in_array(sr_theme_slug(), ['course','learn'], true));
        sr_theme_navigation_link(sr_theme_url($file), $title, $mobile, $active);
    }
}

function sr_theme_navigation_link(string $url, string $title, bool $mobile, bool $active): void {
    $class = ($mobile ? 'sr-mobile-link' : 'sr-nav-link') . ($active ? ' is-active' : '');
    echo '<a class="' . esc_attr($class) . '" href="' . esc_url($url) . '"' . ($active ? ' aria-current="page"' : '') . '>' . esc_html($title);
    if ($mobile) { echo '<svg class="cta-arrow-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16m-7-7 7 7-7 7"/></svg>'; }
    echo '</a>';
}

/** All fixed marketing templates are allowlisted local PHP files. */
add_filter('template_include', function(string $template): string {
    if (is_front_page()) { return get_template_directory() . '/front-page.php'; }
    if (is_page() && in_array(sr_theme_slug(), array_values(sr_theme_pages()), true)) { return get_template_directory() . '/page.php'; }
    return $template;
});

function sr_theme_cf7_form(string $title, string $class = ''): string {
    if (!shortcode_exists('contact-form-7')) { return '<p class="sr-lms-notice">The inquiry form is temporarily unavailable. Please email <a href="mailto:shalinirathodofficial@gmail.com">shalinirathodofficial@gmail.com</a>.</p>'; }
    $forms = get_posts(['post_type' => 'wpcf7_contact_form', 'title' => $title, 'post_status' => 'publish', 'numberposts' => 1]);
    if (!$forms) { return '<p class="sr-lms-notice">The inquiry form is temporarily unavailable. Please email <a href="mailto:shalinirathodofficial@gmail.com">shalinirathodofficial@gmail.com</a>.</p>'; }
    return do_shortcode(sprintf('[contact-form-7 id="%d" title="%s" html_class="%s"]', (int) $forms[0]->ID, esc_attr($title), esc_attr($class)));
}

