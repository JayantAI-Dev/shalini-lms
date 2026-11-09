<?php defined('ABSPATH') || exit; get_header(); ?>
<main id="main-content" class="sr-wp-content">
<?php if (is_search()) { ?><h1><?php printf(esc_html__('Search results for “%s”', 'sr-academy'), esc_html(get_search_query())); ?></h1><?php } elseif (is_archive()) { the_archive_title('<h1>', '</h1>'); the_archive_description(); }
if (have_posts()) { while (have_posts()) { the_post(); ?><article <?php post_class(); ?>><?php if (is_singular()) { ?><h1><?php the_title(); ?></h1><?php the_content(); wp_link_pages(); } else { ?><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); } ?></article><?php } the_posts_pagination(); } else { ?><h1>Nothing found</h1><p>Try another search or return to the home page.</p><?php get_search_form(); } ?>
</main><?php get_footer();
