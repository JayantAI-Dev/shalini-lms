<?php
defined('ABSPATH') || exit;
$sr_slug = $args['slug'];
$sr_course = $args['course'];
$sr_visuals = [
    'speakathon'=>['communication','','','speakathon-cover.webp','Communication Course'],
    'power-dressing'=>['styling','styling-card','learning-card-media-style','power-dressing-cover.webp','Image Styling Course'],
    'dining-etiquette'=>['etiquette','learning-card-dining','learning-card-media-dining','dining-etiquette-cover.webp','Dining Etiquette'],
    'power-bundle'=>['bundle','learning-card-bundle learning-card-bundle-horizontal','learning-card-media-bundle','power-bundle-cover.webp','Bundle Special'],
];
[$sr_category,$sr_card_class,$sr_media_class,$sr_cover,$sr_badge] = $sr_visuals[$sr_slug] ?? ['communication','','','speakathon-cover.webp','Academy Course'];
$sr_available = sr_theme_course_available($sr_slug, $sr_course);
$sr_detail_url = sr_theme_url('lms-course.html?course=' . rawurlencode($sr_slug));
$sr_request_url = sr_theme_url('package-enroll.html?course=' . rawurlencode($sr_slug));
?>
<article class="learning-card lms-course-tile <?php echo esc_attr($sr_card_class); ?>" data-course="<?php echo esc_attr($sr_slug); ?>" data-category="<?php echo esc_attr($sr_category); ?>" data-search="<?php echo esc_attr($sr_course['title'] . ' ' . $sr_course['description'] . ' ' . implode(' ', $sr_course['tags'] ?? [])); ?>">
    <div class="learning-card-media <?php echo esc_attr($sr_media_class); ?>">
        <img src="<?php echo esc_url(sr_theme_asset('assets/services/' . $sr_cover)); ?>" alt="<?php echo esc_attr($sr_course['title']); ?>" loading="lazy" decoding="async" width="1400" height="788">
        <div class="learning-media-gradient" aria-hidden="true"></div>
        <span class="learning-badge<?php echo !$sr_available ? ' learning-badge-coming-soon' : ($sr_category === 'bundle' ? ' learning-badge-bundle' : ''); ?>"><?php echo esc_html($sr_available ? $sr_badge : 'Currently unavailable'); ?></span>
        <h3><?php echo esc_html($sr_course['title']); ?></h3>
    </div>
    <div class="learning-card-body">
        <p class="learning-description"><?php echo esc_html($sr_course['description']); ?></p>
        <ul class="learning-features"><?php foreach ($sr_course['includes'] ?? [] as $sr_inclusion) { ?><li><span class="learning-check">✓</span><span><?php echo esc_html($sr_inclusion); ?></span></li><?php } ?></ul>
        <div class="learning-divider" aria-hidden="true"></div>
        <div class="learning-price-row"><strong class="learning-price">₹<?php echo esc_html(number_format_i18n((float) ($sr_course['price'] ?? 0), 2)); ?></strong><a class="learning-syllabus" href="<?php echo esc_url($sr_detail_url); ?>"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 4.5h5.2c.9 0 1.3.35 1.3 1.15V16c0-.9-.75-1.45-1.75-1.45H3.5V4.5Zm13 0h-5.2c-.9 0-1.3.35-1.3 1.15V16c0-.9.75-1.45 1.75-1.45h4.75V4.5Z"/></svg><span>Syllabus</span></a></div>
        <?php if ($sr_available) { ?><a class="learning-enroll-btn<?php echo $sr_category === 'bundle' ? ' learning-bundle-btn' : ''; ?>" href="<?php echo esc_url($sr_category === 'bundle' ? $sr_request_url : $sr_detail_url); ?>"><?php echo esc_html($sr_category === 'bundle' ? 'Get The Power Bundle' : 'Explore Course Details'); ?></a><?php } else { ?><button type="button" class="learning-enroll-btn learning-coming-soon-btn" disabled aria-disabled="true">Currently unavailable</button><?php } ?>
    </div>
</article>
