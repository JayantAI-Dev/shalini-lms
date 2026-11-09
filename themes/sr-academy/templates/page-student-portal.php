<?php defined('ABSPATH') || exit; ?>
<main id="main-content" class="sr-student-page sr-auth-page"><?php if (function_exists('sr_lms_render_auth')) { sr_lms_render_auth(); } else { sr_theme_lms_notice(); } ?></main>
