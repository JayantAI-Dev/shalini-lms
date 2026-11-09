<?php
if (!defined('ABSPATH')) exit;

/** Register an editable Course post type without changing the LMS catalog identifiers. */
function sr_lms_register_course_post_type(): void {
    register_post_type('sr_course', [
        'labels' => ['name'=>'Courses','singular_name'=>'Course','add_new_item'=>'Add Course','edit_item'=>'Edit Course','menu_name'=>'Courses'],
        'public'=>true,'show_in_rest'=>true,'has_archive'=>false,'rewrite'=>['slug'=>'academy-course','with_front'=>false],
        'menu_icon'=>'dashicons-welcome-learn-more','supports'=>['title','editor','excerpt','thumbnail','revisions'],
        'capability_type'=>'post','map_meta_cap'=>true,
    ]);
}
add_action('init','sr_lms_register_course_post_type',4);

/** SCF/ACF field groups are registered in code so they remain version controlled. */
function sr_lms_register_content_fields(): void {
    if (!function_exists('acf_add_local_field_group')) return;
    if (function_exists('acf_add_options_page')) acf_add_options_page(['page_title'=>'SR Academy Content','menu_title'=>'Site Content','menu_slug'=>'sr-academy-content','capability'=>'edit_pages','redirect'=>false,'position'=>59]);
    $page_tabs = [
        ['key'=>'page_tab_mode','label'=>'Publishing mode','type'=>'tab','placement'=>'top'],
        ['key'=>'page_layout_mode','label'=>'Page layout','name'=>'sr_page_layout_mode','type'=>'select','choices'=>['legacy'=>'Keep the current designed layout','editable'=>'Use the editable sections below'],'default_value'=>'legacy','instructions'=>'Keep the current layout until you have rebuilt all of its content in the sections below.'],
        ['key'=>'tab_hero','label'=>'Hero','type'=>'tab','placement'=>'top'],
        ['key'=>'hero_eyebrow','label'=>'Eyebrow','name'=>'sr_hero_eyebrow','type'=>'text'],
        ['key'=>'hero_title','label'=>'Headline','name'=>'sr_hero_title','type'=>'textarea','rows'=>2],
        ['key'=>'hero_intro','label'=>'Intro','name'=>'sr_hero_intro','type'=>'textarea','rows'=>3],
        ['key'=>'hero_image','label'=>'Hero image','name'=>'sr_hero_image','type'=>'image','return_format'=>'array','preview_size'=>'medium'],
        ['key'=>'hero_ctas','label'=>'Hero links','name'=>'sr_hero_ctas','type'=>'repeater','layout'=>'table','button_label'=>'Add link','sub_fields'=>[
            ['key'=>'cta_label','label'=>'Label','name'=>'label','type'=>'text'],['key'=>'cta_url','label'=>'URL','name'=>'url','type'=>'url'],['key'=>'cta_style','label'=>'Style','name'=>'style','type'=>'select','choices'=>['primary'=>'Primary','secondary'=>'Secondary']],
        ]],
        ['key'=>'tab_sections','label'=>'Page sections','type'=>'tab','placement'=>'top'],
        ['key'=>'sections','label'=>'Sections','name'=>'sr_page_sections','type'=>'repeater','layout'=>'block','button_label'=>'Add section','sub_fields'=>[
            ['key'=>'section_tab','label'=>'Section content','type'=>'tab','placement'=>'top'],
            ['key'=>'section_key','label'=>'Section key','name'=>'section_key','type'=>'text'],
            ['key'=>'section_eyebrow','label'=>'Eyebrow','name'=>'eyebrow','type'=>'text'],
            ['key'=>'section_title','label'=>'Title','name'=>'title','type'=>'text'],
            ['key'=>'section_intro','label'=>'Intro','name'=>'intro','type'=>'textarea','rows'=>3],
            ['key'=>'section_body','label'=>'Body','name'=>'body','type'=>'wysiwyg','tabs'=>'all','toolbar'=>'basic','media_upload'=>false],
            ['key'=>'section_image','label'=>'Image','name'=>'image','type'=>'image','return_format'=>'array','preview_size'=>'medium'],
            ['key'=>'items_tab','label'=>'Repeater items','type'=>'tab','placement'=>'top'],
            ['key'=>'items','label'=>'Cards / items','name'=>'items','type'=>'repeater','layout'=>'row','button_label'=>'Add item','sub_fields'=>[
                ['key'=>'item_title','label'=>'Title','name'=>'title','type'=>'text'],['key'=>'item_text','label'=>'Description','name'=>'text','type'=>'textarea','rows'=>3],
                ['key'=>'item_image','label'=>'Image','name'=>'image','type'=>'image','return_format'=>'array','preview_size'=>'thumbnail'],['key'=>'item_url','label'=>'Link URL','name'=>'url','type'=>'url'],['key'=>'item_label','label'=>'Link label','name'=>'link_label','type'=>'text'],
            ]],
            ['key'=>'faq_tab','label'=>'FAQs','type'=>'tab','placement'=>'top'],
            ['key'=>'faqs','label'=>'Questions','name'=>'faqs','type'=>'repeater','layout'=>'table','button_label'=>'Add question','sub_fields'=>[
                ['key'=>'faq_question','label'=>'Question','name'=>'question','type'=>'text'],['key'=>'faq_answer','label'=>'Answer','name'=>'answer','type'=>'textarea','rows'=>3],
            ]],
        ]],
        ['key'=>'tab_seo','label'=>'SEO','type'=>'tab','placement'=>'top'],
        ['key'=>'seo_description','label'=>'Search description','name'=>'sr_seo_description','type'=>'textarea','rows'=>3,'maxlength'=>160],
    ];
    acf_add_local_field_group(['key'=>'group_sr_page_content','title'=>'SR Academy · Page Content','fields'=>$page_tabs,'location'=>[[['param'=>'post_type','operator'=>'==','value'=>'page']]],'position'=>'normal','style'=>'seamless','active'=>true]);

    acf_add_local_field_group(['key'=>'group_sr_course_content','title'=>'Course Details','fields'=>[
        ['key'=>'course_tab_overview','label'=>'Overview','type'=>'tab','placement'=>'top'],
        ['key'=>'course_catalog_key','label'=>'LMS course key','name'=>'sr_lms_course_key','type'=>'text','instructions'=>'Must match the existing LMS catalog key. Leave unchanged for existing courses.'],
        ['key'=>'course_kicker','label'=>'Eyebrow','name'=>'sr_course_kicker','type'=>'text'],
        ['key'=>'course_short','label'=>'Short description','name'=>'sr_course_short_description','type'=>'textarea','rows'=>3],
        ['key'=>'course_tab_curriculum','label'=>'Curriculum','type'=>'tab','placement'=>'top'],
        ['key'=>'course_modules','label'=>'Modules','name'=>'sr_course_modules','type'=>'repeater','layout'=>'block','button_label'=>'Add module','instructions'=>'Lesson wording is synced to the LMS while module and lesson counts stay the same, preserving lesson IDs, learner progress, and private media. For structural changes, use the LMS catalog tools.','sub_fields'=>[
            ['key'=>'module_title','label'=>'Module title','name'=>'title','type'=>'text'],['key'=>'module_lessons','label'=>'Lessons','name'=>'lessons','type'=>'repeater','layout'=>'table','button_label'=>'Add lesson','sub_fields'=>[
                ['key'=>'lesson_title','label'=>'Lesson title','name'=>'title','type'=>'text'],['key'=>'lesson_summary','label'=>'Summary','name'=>'summary','type'=>'textarea','rows'=>2],['key'=>'lesson_practice','label'=>'Practice','name'=>'practice','type'=>'textarea','rows'=>2],
            ]],
        ]],
        ['key'=>'course_tab_includes','label'=>'Includes & outcomes','type'=>'tab','placement'=>'top'],
        ['key'=>'course_includes','label'=>'What is included','name'=>'sr_course_includes','type'=>'repeater','layout'=>'table','button_label'=>'Add inclusion','sub_fields'=>[['key'=>'include_text','label'=>'Item','name'=>'text','type'=>'text']]],
        ['key'=>'course_outcomes','label'=>'Learning outcomes','name'=>'sr_course_outcomes','type'=>'repeater','layout'=>'table','button_label'=>'Add outcome','sub_fields'=>[['key'=>'outcome_text','label'=>'Outcome','name'=>'text','type'=>'text']]],
        ['key'=>'course_tab_faq','label'=>'FAQs','type'=>'tab','placement'=>'top'],
        ['key'=>'course_faqs','label'=>'Questions','name'=>'sr_course_faqs','type'=>'repeater','layout'=>'table','button_label'=>'Add question','sub_fields'=>[['key'=>'course_faq_q','label'=>'Question','name'=>'question','type'=>'text'],['key'=>'course_faq_a','label'=>'Answer','name'=>'answer','type'=>'textarea','rows'=>3]]],
        ['key'=>'course_tab_purchase','label'=>'Purchase','type'=>'tab','placement'=>'top'],
        ['key'=>'course_offer_key','label'=>'Razorpay offer key','name'=>'sr_course_offer_key','type'=>'text','instructions'=>'Existing offer key in LMS → Payments. Payment configuration remains in the LMS settings.'],
        ['key'=>'course_price','label'=>'Price (INR)','name'=>'sr_course_price','type'=>'number','min'=>0,'step'=>0.01],
        ['key'=>'course_availability','label'=>'Course availability','name'=>'sr_course_availability','type'=>'select','choices'=>['active'=>'Available','unavailable'=>'Unavailable'],'default_value'=>'active'],
        ['key'=>'course_sort','label'=>'Display order','name'=>'sr_course_order','type'=>'number','min'=>0,'step'=>1],
    ],'location'=>[[['param'=>'post_type','operator'=>'==','value'=>'sr_course']]],'position'=>'normal','style'=>'seamless','active'=>true]);

    acf_add_local_field_group(['key'=>'group_sr_site_content','title'=>'Site-wide Content','fields'=>[
        ['key'=>'site_tab_banner','label'=>'Announcement bar','type'=>'tab','placement'=>'top'],
        ['key'=>'site_banner_enabled','label'=>'Show announcement','name'=>'sr_banner_enabled','type'=>'true_false','ui'=>1,'default_value'=>1],
        ['key'=>'site_banner_label','label'=>'Badge','name'=>'sr_banner_label','type'=>'text','default_value'=>'NEW'],
        ['key'=>'site_banner_text','label'=>'Message','name'=>'sr_banner_text','type'=>'text','default_value'=>'Become confident before 2027'],
        ['key'=>'site_banner_link_label','label'=>'Link label','name'=>'sr_banner_link_label','type'=>'text','default_value'=>'Find out areas you can work on'],
        ['key'=>'site_banner_url','label'=>'Link','name'=>'sr_banner_url','type'=>'url'],
        ['key'=>'site_tab_brand','label'=>'Brand & contact','type'=>'tab','placement'=>'top'],
        ['key'=>'site_brand_name','label'=>'Brand name','name'=>'sr_brand_name','type'=>'text','default_value'=>'Shalini Rathod'],
        ['key'=>'site_brand_role','label'=>'Brand descriptor','name'=>'sr_brand_role','type'=>'text'],
        ['key'=>'site_brand_copy','label'=>'Brand description','name'=>'sr_brand_copy','type'=>'textarea','rows'=>3],
        ['key'=>'site_footer_tagline','label'=>'Compact footer tagline','name'=>'sr_footer_tagline','type'=>'text','default_value'=>'Confidence, communication and image consulting.'],
        ['key'=>'site_brand_copy','label'=>'Brand description','name'=>'sr_brand_copy','type'=>'textarea','rows'=>3],
        ['key'=>'site_email','label'=>'Contact email','name'=>'sr_contact_email','type'=>'email'],
        ['key'=>'site_location','label'=>'Location','name'=>'sr_contact_location','type'=>'text'],
        ['key'=>'site_tab_footer','label'=>'Footer','type'=>'tab','placement'=>'top'],
        ['key'=>'site_footer_links','label'=>'Footer links','name'=>'sr_footer_links','type'=>'repeater','layout'=>'table','button_label'=>'Add link','sub_fields'=>[
            ['key'=>'footer_link_label','label'=>'Label','name'=>'label','type'=>'text'],['key'=>'footer_link_url','label'=>'URL','name'=>'url','type'=>'url'],
        ]],
        ['key'=>'site_home_footer_links','label'=>'Home footer links','name'=>'sr_home_footer_links','type'=>'repeater','layout'=>'table','button_label'=>'Add home link','sub_fields'=>[
            ['key'=>'home_footer_link_label','label'=>'Label','name'=>'label','type'=>'text'],['key'=>'home_footer_link_url','label'=>'URL','name'=>'url','type'=>'url'],
        ]],
        ['key'=>'site_footer_pillars','label'=>'Consulting pillars','name'=>'sr_footer_pillars','type'=>'repeater','layout'=>'table','button_label'=>'Add pillar','sub_fields'=>[['key'=>'footer_pillar','label'=>'Pillar','name'=>'label','type'=>'text']]],
        ['key'=>'site_tab_meta','label'=>'Site metadata','type'=>'tab','placement'=>'top'],
        ['key'=>'site_default_description','label'=>'Default search description','name'=>'sr_default_seo_description','type'=>'textarea','rows'=>3,'maxlength'=>160],
    ],'location'=>[[['param'=>'options_page','operator'=>'==','value'=>'sr-academy-content']]],'position'=>'normal','style'=>'seamless','active'=>true]);
}
add_action('acf/init','sr_lms_register_content_fields',20);

/** Dynamic template helpers; empty fields deliberately fall back to the legacy template copy. */
function sr_theme_field(string $name, $post_id = false) {
    return function_exists('get_field') ? get_field($name,$post_id) : null;
}
function sr_theme_option(string $name, $fallback = '') {
    if (!function_exists('get_field')) return $fallback;
    $value=get_field($name,'option');
    return ($value===null || $value==='') ? $fallback : $value;
}
function sr_theme_render_page_section(string $key): bool {
    $sections=sr_theme_field('sr_page_sections');
    if (!is_array($sections)) return false;
    foreach ($sections as $section) {
        if (($section['section_key']??'') !== $key) continue;
        $title=(string)($section['title']??''); $eyebrow=(string)($section['eyebrow']??'');
        echo '<section class="sr-dynamic-section" data-section="'.esc_attr($key).'">';
        if ($eyebrow!=='') echo '<p class="section-eyebrow">'.esc_html($eyebrow).'</p>';
        if ($title!=='') echo '<h2>'.wp_kses_post($title).'</h2>';
        if (!empty($section['intro'])) echo '<p class="section-intro">'.esc_html($section['intro']).'</p>';
        if (!empty($section['body'])) echo '<div class="sr-dynamic-body">'.wp_kses_post($section['body']).'</div>';
        if (!empty($section['image']['url'])) echo '<img src="'.esc_url($section['image']['url']).'" alt="'.esc_attr($section['image']['alt']??'').'">';
        if (!empty($section['items'])&&is_array($section['items'])) { echo '<div class="sr-dynamic-items">'; foreach($section['items'] as $item) { echo '<article>'; if(!empty($item['image']['url'])) echo '<img src="'.esc_url($item['image']['url']).'" alt="'.esc_attr($item['image']['alt']??'').'">'; if(!empty($item['title'])) echo '<h3>'.esc_html($item['title']).'</h3>'; if(!empty($item['text'])) echo '<p>'.esc_html($item['text']).'</p>'; if(!empty($item['url'])) echo '<a href="'.esc_url($item['url']).'">'.esc_html($item['link_label']?:'Learn more').' →</a>'; echo '</article>'; } echo '</div>'; }
        if (!empty($section['faqs'])&&is_array($section['faqs'])) { echo '<div class="sr-dynamic-faqs">'; foreach($section['faqs'] as $faq) echo '<details><summary>'.esc_html($faq['question']??'').'</summary><div>'.wp_kses_post($faq['answer']??'').'</div></details>'; echo '</div>'; }
        echo '</section>'; return true;
    }
    return false;
}

/** Render page-level SCF content when editors provide it; preserve the original template otherwise. */
function sr_theme_render_editable_page(): bool {
    if (!function_exists('get_field')) return false;
    if (!in_array(sr_theme_slug(),['home','about','services','contact','courses'],true)) return false;
    if (sr_theme_field('sr_page_layout_mode') !== 'editable') return false;
    $hero_title=sr_theme_field('sr_hero_title');
    $sections=sr_theme_field('sr_page_sections');
    if (!$hero_title && empty($sections)) return false;
    $slug=sr_theme_slug();
    echo '<main id="main-content" class="sr-editable-page sr-editable-page-'.esc_attr($slug).'">';
    if ($hero_title || sr_theme_field('sr_hero_intro') || sr_theme_field('sr_hero_eyebrow')) {
        echo '<section class="sr-editable-hero"><div class="sr-editable-hero-copy">';
        if ($eyebrow=sr_theme_field('sr_hero_eyebrow')) echo '<p class="section-eyebrow">'.esc_html($eyebrow).'</p>';
        if ($hero_title) echo '<h1>'.wp_kses_post(nl2br(esc_html($hero_title))).'</h1>';
        if ($intro=sr_theme_field('sr_hero_intro')) echo '<p class="section-intro">'.esc_html($intro).'</p>';
        $ctas=sr_theme_field('sr_hero_ctas'); if(is_array($ctas)) foreach($ctas as $cta) if(!empty($cta['label'])&&!empty($cta['url'])) echo '<a class="sr-editable-cta '.esc_attr($cta['style']??'primary').'" href="'.esc_url($cta['url']).'">'.esc_html($cta['label']).' →</a>';
        echo '</div>'; $image=sr_theme_field('sr_hero_image'); if(is_array($image)&&!empty($image['url'])) echo '<img class="sr-editable-hero-image" src="'.esc_url($image['url']).'" alt="'.esc_attr($image['alt']??'').'">'; echo '</section>';
    }
    if (is_array($sections)) foreach($sections as $section) {
        $key=sanitize_title($section['section_key']??'section');
        echo '<section class="sr-dynamic-section" id="'.esc_attr($key).'">';
        if (!empty($section['eyebrow'])) echo '<p class="section-eyebrow">'.esc_html($section['eyebrow']).'</p>';
        if (!empty($section['title'])) echo '<h2>'.wp_kses_post($section['title']).'</h2>';
        if (!empty($section['intro'])) echo '<p class="section-intro">'.esc_html($section['intro']).'</p>';
        if (!empty($section['body'])) echo '<div class="sr-dynamic-body">'.wp_kses_post($section['body']).'</div>';
        $image=$section['image']??null; if(is_array($image)&&!empty($image['url'])) echo '<img class="sr-dynamic-image" src="'.esc_url($image['url']).'" alt="'.esc_attr($image['alt']??'').'">';
        if (!empty($section['items'])&&is_array($section['items'])) { echo '<div class="sr-dynamic-items">'; foreach($section['items'] as $item) { echo '<article>'; if(!empty($item['image']['url'])) echo '<img src="'.esc_url($item['image']['url']).'" alt="'.esc_attr($item['image']['alt']??'').'">'; if(!empty($item['title'])) echo '<h3>'.esc_html($item['title']).'</h3>'; if(!empty($item['text'])) echo '<p>'.esc_html($item['text']).'</p>'; if(!empty($item['url'])) echo '<a href="'.esc_url($item['url']).'">'.esc_html($item['link_label']?:'Learn more').' →</a>'; echo '</article>'; } echo '</div>'; }
        if (!empty($section['faqs'])&&is_array($section['faqs'])) foreach($section['faqs'] as $faq) { echo '<details class="sr-dynamic-faq"><summary>'.esc_html($faq['question']??'').'</summary><div>'.wp_kses_post($faq['answer']??'').'</div></details>'; }
        echo '</section>';
    }
    echo '</main>'; return true;
}

/** Seed editable Course entries once, retaining LMS IDs, media references, and payment amounts. */
function sr_lms_migrate_catalog_to_course_posts(): array {
    if (!function_exists('sr_lms_catalog')) return ['created'=>0,'updated'=>0];
    $created=0; $updated=0;
    foreach (sr_lms_catalog() as $slug=>$course) {
        $posts=get_posts(['post_type'=>'sr_course','post_status'=>['publish','draft','private'],'numberposts'=>1,'meta_key'=>'sr_lms_course_key','meta_value'=>$slug]);
        $post_id=$posts ? (int)$posts[0]->ID : 0;
        $post_data=['post_type'=>'sr_course','post_status'=>'publish','post_title'=>$course['title'],'post_name'=>$slug,'post_excerpt'=>$course['description'],'post_content'=>wpautop(esc_html($course['description']))];
        if ($post_id) { $post_data['ID']=$post_id; $saved=wp_update_post($post_data,true); if(!is_wp_error($saved))$updated++; }
        else { $post_id=wp_insert_post($post_data,true); if(is_wp_error($post_id))continue; $created++; }
        update_post_meta($post_id,'sr_lms_course_key',$slug);
        update_post_meta($post_id,'sr_course_offer_key',$slug);
        update_post_meta($post_id,'sr_course_order',array_search($slug,array_keys(sr_lms_catalog()),true));
        update_post_meta($post_id,'sr_course_price',(float)$course['price']);
        update_post_meta($post_id,'sr_course_availability',$course['availability']??'active');
        update_post_meta($post_id,'sr_course_includes',array_map(static fn($text)=>['text'=>$text],(array)($course['includes']??[])));
        update_post_meta($post_id,'sr_course_modules',array_map(static function($module){return ['title'=>$module['title'],'lessons'=>array_map(static fn($lesson)=>['title'=>$lesson['title'],'summary'=>$lesson['summary']??'','practice'=>$lesson['practice']??''],$module['lessons']??[])];},(array)($course['modules']??[])));
        update_post_meta($post_id,'sr_course_short_description',$course['description']);
    }
    update_option('sr_lms_course_cpt_migrated','1',false);
    return compact('created','updated');
}
function sr_lms_course_post_sync(int $post_id, WP_Post $post, bool $update): void {
    if (wp_is_post_revision($post_id)||wp_is_post_autosave($post_id)||$post->post_status==='auto-draft'||!function_exists('sr_lms_catalog')) return;
    $slug=sr_lms_id((string)get_post_meta($post_id,'sr_lms_course_key',true)); $catalog=sr_lms_catalog();
    if (!$slug||!isset($catalog[$slug])) return;
    $catalog[$slug]['title']=sanitize_text_field($post->post_title);
    $short=(string)get_post_meta($post_id,'sr_course_short_description',true);
    $catalog[$slug]['description']=sanitize_textarea_field($short!==''?$short:wp_strip_all_tags($post->post_content));
    $price=get_post_meta($post_id,'sr_course_price',true);
    if(is_numeric($price)&&(float)$price>=0&&(float)$price<=10000000)$catalog[$slug]['price']=(float)$price;
    $availability=get_post_meta($post_id,'sr_course_availability',true);
    if(in_array($availability,['active','unavailable'],true))$catalog[$slug]['availability']=$availability;
    // Preserve lesson IDs/progress/media. Copy lesson wording only when repeater shape matches the live course.
    $modules=get_post_meta($post_id,'sr_course_modules',true); $shape_ok=is_array($modules)&&count($modules)===count($catalog[$slug]['modules']);
    if ($shape_ok) foreach($modules as $mi=>$module) if(!isset($module['lessons'])||count($module['lessons'])!==count($catalog[$slug]['modules'][$mi]['lessons'])){$shape_ok=false;break;}
    if ($shape_ok) foreach($modules as $mi=>$module) { $catalog[$slug]['modules'][$mi]['title']=sanitize_text_field($module['title']??$catalog[$slug]['modules'][$mi]['title']); foreach($module['lessons'] as $li=>$lesson) { $catalog[$slug]['modules'][$mi]['lessons'][$li]['title']=sanitize_text_field($lesson['title']??''); $catalog[$slug]['modules'][$mi]['lessons'][$li]['summary']=sanitize_textarea_field($lesson['summary']??''); $catalog[$slug]['modules'][$mi]['lessons'][$li]['practice']=sanitize_textarea_field($lesson['practice']??''); } }
    update_option('sr_lms_catalog',$catalog,false);
}
add_action('save_post_sr_course','sr_lms_course_post_sync',20,3);
