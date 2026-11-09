<?php defined('ABSPATH') || exit; ?>
<footer aria-labelledby="footer-brand" class="site-footer">
<div class="footer-inner">
<div class="footer-grid">
<section class="footer-brand-column">
<h2 id="footer-brand"><?php echo esc_html(function_exists('sr_theme_option')?sr_theme_option('sr_brand_name','SHALINI RATHOD'):'SHALINI RATHOD'); ?></h2>
<p class="footer-brand-role"><?php echo esc_html(function_exists('sr_theme_option')?sr_theme_option('sr_brand_role','2X TEDX SPEAKER | CONFIDENCE & COMMUNICATION COACH | IMAGE CONSULTANT | CELEBRITY STYLIST'):'2X TEDX SPEAKER | CONFIDENCE & COMMUNICATION COACH | IMAGE CONSULTANT | CELEBRITY STYLIST'); ?></p>
<p class="footer-brand-copy"><?php echo esc_html(function_exists('sr_theme_option')?sr_theme_option('sr_brand_copy','Helping more than 15,000 people build confidence, clarity and presence, with an online community of over 600,000 people.'):'Helping more than 15,000 people build confidence, clarity and presence, with an online community of over 600,000 people.'); ?></p>
<div aria-label="Social links" class="footer-socials">
<a aria-label="About Shalini" href="<?php echo esc_url(sr_theme_url('about.html')); ?>">
<svg aria-hidden="true" viewbox="0 0 24 24"><path d="M6.7 8.5v8.8M6.75 5.9a.95.95 0 1 1 0 1.9.95.95 0 0 1 0-1.9Zm4.55 11.4V8.5m0 0v1.2c.7-.92 1.62-1.38 2.74-1.38 2.08 0 3.35 1.36 3.35 4.08v4.9m-6.09-7.6h.02m6.07 7.6v-4.62c0-1.53-.55-2.42-1.8-2.42-1 0-1.61.67-1.87 1.32-.1.23-.12.55-.12.87v4.85"></path></svg>
</a>
<a aria-label="Instagram" href="https://www.instagram.com/shalinimotivates/" rel="noopener" target="_blank">
<svg aria-hidden="true" viewbox="0 0 24 24"><rect height="14.5" rx="4.2" width="14.5" x="4.75" y="4.75"></rect><circle cx="12" cy="12" r="3.45"></circle><circle cx="16.9" cy="7.25" fill="currentColor" r=".85" stroke="none"></circle></svg>
</a>
</div>
</section>
<nav aria-label="Platform navigation" class="footer-column">
<h3>PLATFORM NAVIGATION</h3>
<ul><?php $sr_footer_links=function_exists('sr_theme_option')?sr_theme_option('sr_home_footer_links',[]):[]; if(is_array($sr_footer_links)&&$sr_footer_links): foreach($sr_footer_links as $sr_link): if(!empty($sr_link['label'])&&!empty($sr_link['url'])): ?><li><a href="<?php echo esc_url($sr_link['url']); ?>"><?php echo esc_html($sr_link['label']); ?></a></li><?php endif; endforeach; else: ?>
<li><a href="#home">Homepage</a></li>
<li><a href="#consultations">1-on-1 Consultations (30m &amp; 60m)</a></li>
<li><a href="<?php echo esc_url(sr_theme_url('lms-course.html?course=speakathon')); ?>">21-Day Speakathon Challenge</a></li>
<li><a href="<?php echo esc_url(sr_theme_url('lms-course.html?course=power-dressing')); ?>">Power Dressing / Styling</a></li>
<li><a href="<?php echo esc_url(sr_theme_url('contact.html')); ?>">Corporate Workshops &amp; Offsites</a></li>
<li><a href="<?php echo esc_url(sr_theme_url('about.html')); ?>">About Shalini's 12-Year Journey</a></li>
</ul><?php endif; ?>
</nav>
<section class="footer-column">
<h3>CONSULTING PILLARS</h3>
<ul class="footer-bullets"><?php $sr_pillars=function_exists('sr_theme_option')?sr_theme_option('sr_footer_pillars',[]):[]; if(is_array($sr_pillars)&&$sr_pillars): foreach($sr_pillars as $sr_pillar): if(!empty($sr_pillar['label'])): ?><li><?php echo esc_html($sr_pillar['label']); ?></li><?php endif; endforeach; else: ?>
<li>Vocal Resonance &amp; Tone</li>
<li>Stage &amp; Interview Anxiety</li>
<li>12-Season Color Analysis</li>
<li>Capsule Wardrobe Design</li>
<li>Body Language &amp; Posture</li>
<li>TEDx &amp; Keynote Coaching</li>
</ul><?php endif; ?>
</section>
<section class="footer-column footer-contact-column">
<h3>GET IN TOUCH</h3>
<ul class="footer-contact-list">
<li>
<svg aria-hidden="true" viewbox="0 0 24 24"><path d="M4 7.5h16v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 16.5v-9Z"></path><path d="m4.5 8 7.02 5.46a.8.8 0 0 0 .96 0L19.5 8"></path></svg>
<?php $sr_email=function_exists('sr_theme_option')?sr_theme_option('sr_contact_email','shalinirathodofficial@gmail.com'):'shalinirathodofficial@gmail.com'; ?><a href="mailto:<?php echo esc_attr($sr_email); ?>"><?php echo esc_html($sr_email); ?></a>
</li>
<li>
<svg aria-hidden="true" viewbox="0 0 24 24"><path d="M12 20s5.5-5.5 5.5-9.25A5.5 5.5 0 1 0 6.5 10.75C6.5 14.5 12 20 12 20Z"></path><circle cx="12" cy="10.75" r="1.7"></circle></svg>
<span><?php echo esc_html(function_exists('sr_theme_option')?sr_theme_option('sr_contact_location','Mumbai & Global (Virtual Consultations)'):'Mumbai & Global (Virtual Consultations)'); ?></span>
</li>
</ul>
</section>
</div>
<div class="footer-bottom">
<p>© 2026 Shalini Rathod. All Rights Reserved. Accredited by IMPA.</p>
<a class="footer-back-top" href="#home">Back to Top <svg aria-hidden="true" class="icon icon-arrow-up-right" viewbox="0 0 20 20"><path d="M5 15 15 5M8 5h7v7"></path></svg></a>
</div>
</div>
</footer>
