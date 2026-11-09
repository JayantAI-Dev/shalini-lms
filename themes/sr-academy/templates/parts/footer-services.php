<?php defined('ABSPATH') || exit; ?>
<footer class="services-footer" aria-labelledby="services-footer-brand">
    <div class="services-footer-inner">
      <div class="services-footer-main">
        <div class="services-footer-brand-block">
          <h2 id="services-footer-brand">Shalini Rathod</h2>
          <p>Image &amp; Communication Consulting</p>
        </div>

        <nav class="services-footer-nav" aria-label="Services footer navigation">
          <a href="#consultations">1:1 Calls</a>
          <a href="#packages">Packages</a>
          <a href="#self-learning">Courses</a>
          <a href="#discovery">Discovery Call</a>
        </nav>

        <?php $sr_email=function_exists('sr_theme_option')?sr_theme_option('sr_contact_email','shalinirathodofficial@gmail.com'):'shalinirathodofficial@gmail.com'; ?><a class="services-footer-email" href="mailto:<?php echo esc_attr($sr_email); ?>">
          <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="5.5" width="14" height="9" rx="1.4"/><path d="m3.8 6.3 5.35 4.05a1.4 1.4 0 0 0 1.7 0l5.35-4.05"/></svg>
          <span><?php echo esc_html($sr_email); ?></span>
        </a>

        <a class="services-footer-book booking-trigger" href="<?php echo esc_url(sr_theme_url('contact.html?type=consultation')); ?>" data-booking-offer="comm-call">Book a Session</a>
      </div>

      <div class="services-footer-divider" aria-hidden="true"></div>

      <div class="services-footer-bottom">
        <p>© 2026 Shalini Rathod.</p>
        <a href="#services-overview">Top <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 15V5M6.5 8.5 10 5l3.5 3.5"/></svg></a>
      </div>
    </div>
  </footer>
