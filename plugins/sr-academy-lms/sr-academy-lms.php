<?php
/**
 * Plugin Name: SR Academy LMS
 * Description: Student accounts, Razorpay purchases, branded emails, protected lessons, progress and certificates.
 * Version: 1.1.0
 * Requires PHP: 8.0
 */
if (!defined('ABSPATH')) exit;

/**
 * Temporary containment for a stray PHP-like payload currently appearing in
 * the hosted site's HTML response. This only removes the exact displayed text;
 * it does not replace locating and removing its source from the hosting account.
 */
if (!function_exists('sr_lms_filter_known_injected_text')) {
    function sr_lms_filter_known_injected_text($buffer) {
        if (!is_string($buffer) || stripos($buffer, '<') === false) {
            return $buffer;
        }

        return preg_replace(
            '/\s*\$__aevatdzjjo\s*=\s*[\'\"]bwsgupymfq[\'\"]\s*;\s*\$__dywkserrlj\s*=\s*4568\s*;\s*/',
            '',
            $buffer
        );
    }
}
ob_start('sr_lms_filter_known_injected_text');

define('SR_LMS_VERSION','1.1.0');
define('SR_LMS_DIR',plugin_dir_path(__FILE__));
define('SR_LMS_URL',plugin_dir_url(__FILE__));
require_once SR_LMS_DIR.'includes/domain.php';
require_once SR_LMS_DIR.'includes/catalog.php';
require_once SR_LMS_DIR.'includes/storage.php';
require_once SR_LMS_DIR.'includes/media.php';
require_once SR_LMS_DIR.'includes/frontend.php';
require_once SR_LMS_DIR.'includes/admin.php';
require_once SR_LMS_DIR.'includes/emails.php';
require_once SR_LMS_DIR.'includes/payments.php';
require_once SR_LMS_DIR.'includes/payment-admin.php';
require_once SR_LMS_DIR.'includes/course-admin.php';
require_once SR_LMS_DIR.'includes/content-management.php';
require_once SR_LMS_DIR.'includes/zoom-calendar.php';
add_action('init','sr_lms_payment_install');
register_activation_hook(__FILE__,'sr_lms_install');
add_action('admin_init',static function(){if(get_option('sr_lms_version')!==SR_LMS_VERSION)sr_lms_install();});
add_action('rest_api_init','sr_lms_register_routes');
add_action('template_redirect','sr_lms_guard_pages',1);
add_action('wp_enqueue_scripts','sr_lms_enqueue');
add_action('admin_post_sr_lms_auth','sr_lms_auth_handler');
add_action('admin_post_nopriv_sr_lms_auth','sr_lms_auth_handler');
add_action('admin_post_sr_lms_verify_email','sr_lms_verify_email_handler');
add_action('admin_post_nopriv_sr_lms_verify_email','sr_lms_verify_email_handler');
add_action('admin_post_sr_lms_profile','sr_lms_profile_handler');
add_action('admin_post_sr_lms_enroll','sr_lms_enroll_handler');
add_action('admin_post_nopriv_sr_lms_enroll','sr_lms_enroll_handler');
add_action('admin_post_sr_lms_media','sr_lms_media_handler');
add_action('admin_post_nopriv_sr_lms_media','sr_lms_media_handler');
add_action('admin_post_sr_lms_certificate','sr_lms_certificate_handler');
add_action('admin_post_nopriv_sr_lms_certificate','sr_lms_certificate_handler');
add_action('admin_menu','sr_lms_admin_menu',5);
add_action('admin_post_sr_lms_admin','sr_lms_admin_handler');
