<?php
defined('ABSPATH') || exit;
get_header();
$slug = sr_theme_slug();
if ($slug === 'learn' && !function_exists('sr_lms_courses')) { ?><main id="main-content"><?php sr_theme_lms_notice(); ?></main><?php }
elseif (in_array($slug, array_values(sr_theme_pages()), true)) { if (!function_exists('sr_theme_render_editable_page') || !sr_theme_render_editable_page()) { get_template_part('templates/page', $slug); } }
else { ?>
<main id="main-content" class="sr-wp-content"><?php while (have_posts()) { the_post(); ?><article <?php post_class(); ?>><h1><?php the_title(); ?></h1><?php the_content(); wp_link_pages(); ?></article><?php } ?></main>
<?php }
get_footer();
