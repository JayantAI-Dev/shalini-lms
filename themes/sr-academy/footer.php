<?php
defined('ABSPATH') || exit;
$slug = sr_theme_slug();
if ($slug !== 'learn') { get_template_part('templates/parts/footer', in_array($slug, ['home','services'], true) ? $slug : 'academy'); }
wp_footer();
?></body></html>
