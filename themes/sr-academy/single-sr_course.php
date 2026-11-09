<?php
/** Public companion view for an LMS course CPT entry. */
defined('ABSPATH') || exit;
get_header();
while (have_posts()) : the_post();
$key=(string)get_post_meta(get_the_ID(),'sr_lms_course_key',true);
$course=function_exists('sr_lms_catalog')?(sr_lms_catalog()[$key]??[]):[];
$modules=function_exists('sr_theme_field')?sr_theme_field('sr_course_modules',get_the_ID()):[];
$includes=function_exists('sr_theme_field')?sr_theme_field('sr_course_includes',get_the_ID()):[];
$faqs=function_exists('sr_theme_field')?sr_theme_field('sr_course_faqs',get_the_ID()):[];
$price=(float)get_post_meta(get_the_ID(),'sr_course_price',true);
$cta=function_exists('sr_lms_page_url')?sr_lms_page_url('course',['course'=>$key]):home_url('/course/?course='.rawurlencode($key));
?>
<main id="main-content" class="sr-course-cpt"><section class="sr-course-cpt-hero"><div><p class="section-eyebrow"><?php echo esc_html(function_exists('sr_theme_field')?sr_theme_field('sr_course_kicker',get_the_ID()):'COURSE'); ?></p><h1><?php the_title(); ?></h1><p class="section-intro"><?php echo esc_html(get_post_meta(get_the_ID(),'sr_course_short_description',true)?:get_the_excerpt()); ?></p><a class="lms-primary" href="<?php echo esc_url($cta); ?>">View course &amp; enroll <span aria-hidden="true">→</span></a></div><?php if(has_post_thumbnail())the_post_thumbnail('large',['class'=>'sr-course-cpt-image']); ?></section>
<section class="sr-course-cpt-details"><article><h2>About this course</h2><div class="sr-dynamic-body"><?php the_content(); ?></div><?php if($includes): ?><h2>What’s included</h2><ul><?php foreach($includes as $row)if(!empty($row['text']))echo '<li>'.esc_html($row['text']).'</li>'; ?></ul><?php endif; ?></article><aside><span class="section-eyebrow">COURSE INVESTMENT</span><strong><?php echo esc_html('₹'.number_format($price,0)); ?></strong><p><?php echo esc_html(count(sr_lms_lessons($course)).' guided lessons'); ?></p><a class="lms-primary full" href="<?php echo esc_url($cta); ?>">Explore course <span aria-hidden="true">→</span></a></aside></section>
<?php if(is_array($modules)&&$modules): ?><section class="sr-course-cpt-section"><p class="section-eyebrow">YOUR LEARNING ROADMAP</p><h2>Course curriculum</h2><?php foreach($modules as $module): ?><details class="sr-course-cpt-module"><summary><?php echo esc_html($module['title']??'Module'); ?></summary><ul><?php foreach((array)($module['lessons']??[]) as $lesson): ?><li><strong><?php echo esc_html($lesson['title']??''); ?></strong><?php if(!empty($lesson['summary']))echo '<p>'.esc_html($lesson['summary']).'</p>'; ?></li><?php endforeach; ?></ul></details><?php endforeach; ?></section><?php endif; ?>
<?php if(is_array($faqs)&&$faqs): ?><section class="sr-course-cpt-section"><h2>Common questions</h2><?php foreach($faqs as $faq): ?><details class="sr-dynamic-faq"><summary><?php echo esc_html($faq['question']??''); ?></summary><div><?php echo wp_kses_post($faq['answer']??''); ?></div></details><?php endforeach; ?></section><?php endif; ?></main>
<?php endwhile; get_footer();
