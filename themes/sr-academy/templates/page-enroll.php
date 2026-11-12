<?php defined('ABSPATH') || exit;
if (!sr_theme_is_coaching_offer()) { ?><main id="main-content"><?php if (function_exists('sr_lms_render_enrollment')) { sr_lms_render_enrollment(); } else { sr_theme_lms_notice(); } ?></main><?php } else { ?>
<main class="package-page-main" id="main-content">
    <section class="package-hero package-page-grid" aria-labelledby="package-hero-title">
      <div class="package-hero-copy">
        <p class="package-page-kicker"><span class="package-kicker-dot" aria-hidden="true"></span><span data-package-kicker>SHALINI RATHOD · IMAGE CONSULTING &amp; STYLING</span></p>
        <h1 id="package-hero-title" data-package-headline>A More Refined Way to Communicate</h1>
        <div class="package-hero-mobile-visual" aria-hidden="true">
          <div class="package-visual-frame">
            <img data-package-cover-mobile src="<?php echo esc_url(sr_theme_asset('assets/hero-seated.webp')); ?>" alt="" fetchpriority="high" width="1100" height="1531" />
          </div>
        </div>
        <p class="package-hero-description" data-package-description>Private coaching tailored to your needs, whether you want to strengthen your delivery, refine your tone, overcome speaking habits, or feel more at ease in professional settings.</p>
        <div class="package-investment-card">
          <div class="package-investment-price">
            <span class="package-investment-label" data-package-investment-label>COMMUNICATION PACKAGE</span>
            <div class="package-price-line"><strong data-package-price>₹39,999</strong><del data-package-original-price hidden></del></div>
            <span class="package-installments" data-package-installments>or 2 installments of ₹18,500</span>
          </div>
          <div class="package-hero-deliverables"><span data-package-deliverable-one>Private 60-minute 1:1 Zoom sessions</span><span data-package-deliverable-two>Personalized Executive Speaker Playbook</span></div>
        </div>
        <div class="package-hero-actions">
          <a class="package-page-button" href="#package-booking"><svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="5" width="13" height="11" rx="1.5"/><path d="M6.5 3.5v3M13.5 3.5v3M3.5 8.2h13"/></svg><span>BOOK 1:1 PACKAGE</span></a>
          <a class="package-discovery-button" href="#package-booking" data-discovery-booking><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M6 3.5 4.2 4.7c-.8.6-.9 1.7-.5 2.7 1.8 4.2 5 7.3 9.2 9.1 1 .4 2.1.3 2.7-.5l1.2-1.8-3.2-2-1.4 1.5c-2.6-1.2-4.3-2.9-5.5-5.5l1.5-1.4-2.2-3.3Z"/></svg><span>15-MIN DISCOVERY (FREE)</span></a>
        </div>
        <div class="package-hero-proof"><span>500+ Clients Styled</span><i></i><span>4.98/5 Rating</span><i></i><span>Free 24h Reschedule</span></div>
      </div>

      <div class="package-hero-visual">
        <div class="package-visual-frame">
          <img data-package-cover src="<?php echo esc_url(sr_theme_asset('assets/hero-seated.webp')); ?>" alt="Shalini Rathod, image consultant" fetchpriority="high" width="1100" height="1531" />
        </div>
      </div>
    </section>



    <section class="package-overview-section package-sessions-section" aria-labelledby="package-overview-title">
      <div class="package-section-heading">
        <p class="package-page-kicker">INCLUDED IN 1:1 PACKAGE</p>
        <h2 id="package-overview-title">The Video Sessions</h2>
        <p data-package-sessions-intro>Individual 60-minute video coaching across communication, presence and high-stakes presentations.</p>
      </div>
      <div class="package-session-grid" data-session-slider role="region" aria-label="Video session cards" tabindex="0">
        <article class="package-session-card">
          <div class="package-session-meta"><span>SESSION 01</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Non-Verbal Communication &amp; Body Language</h3>
          <ul><li>3-point body language audit</li><li>Eye contact &amp; facial congruence</li><li>Video-based presence analysis</li></ul>
          <div class="package-session-footer"><span>Included in all packages</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card">
          <div class="package-session-meta"><span>SESSION 02</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Vocal Dynamics &amp; Tonal Control</h3>
          <ul><li>5 vocal delivery variables</li><li>Strategic pauses &amp; emphasis</li><li>3-tone vocal practice</li></ul>
          <div class="package-session-footer"><span>Included in all packages</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card">
          <div class="package-session-meta"><span>SESSION 03</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Verbal Precision &amp; Articulation</h3>
          <ul><li>3-part message structuring</li><li>Filler words &amp; verbal clutter</li><li>Articulation, diction &amp; sentence economy</li></ul>
          <div class="package-session-footer"><span>Included in all packages</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card">
          <div class="package-session-meta"><span>SESSION 04</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Conversational Intelligence &amp; Social Fluency</h3>
          <ul><li>3-step active listening</li><li>Conversational turn-taking techniques</li><li>Rapport building &amp; social calibration</li></ul>
          <div class="package-session-footer"><span>Included in all packages</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card">
          <div class="package-session-meta"><span>SESSION 05</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Assertiveness &amp; Verbal Authority</h3>
          <ul><li>DESC framework for difficult conversations</li><li>Hedging &amp; unnecessary apologies</li><li>Assertive response scenario practice</li></ul>
          <div class="package-session-footer"><span>Included in all packages</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card">
          <div class="package-session-meta"><span>SESSION 06</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Executive Communication &amp; Speaking Impact</h3>
          <ul><li>PREP framework for concise answers</li><li>Rule of Three messaging</li><li>Impromptu speaking &amp; response drills</li></ul>
          <div class="package-session-footer"><span>Included in all packages</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-communication-advanced-card>
          <div class="package-session-meta"><span>SESSION 07</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Commanding Stage Presence</h3>
          <ul><li>Confident stage entry and audience engagement</li><li>Composure while speaking to an audience</li><li>Managing nerves with confidence</li></ul>
          <div class="package-session-footer"><span>Powerful Presence Package · Commanding Presence Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-communication-advanced-card>
          <div class="package-session-meta"><span>SESSION 08</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Camera Confidence &amp; Ease</h3>
          <ul><li>Natural eye contact with the camera</li><li>Expressive facial and vocal delivery</li><li>Relaxed, confident on-screen presence</li></ul>
          <div class="package-session-footer"><span>Powerful Presence Package · Commanding Presence Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-communication-advanced-card>
          <div class="package-session-meta"><span>SESSION 09</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Mastering Advanced Body Language</h3>
          <ul><li>Confident eye contact and natural gestures</li><li>Expressive facial movements</li><li>Strong posture and purposeful movement</li></ul>
          <div class="package-session-footer"><span>Powerful Presence Package · Commanding Presence Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-communication-advanced-card>
          <div class="package-session-meta"><span>SESSION 10</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Executive Presence &amp; Impact</h3>
          <ul><li>Clear, authoritative communication</li><li>Confident participation in meetings</li><li>Handling questions with composure</li></ul>
          <div class="package-session-footer"><span>Powerful Presence Package · Commanding Presence Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-communication-advanced-card>
          <div class="package-session-meta"><span>SESSION 11</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Storytelling for Influence</h3>
          <ul><li>Craft compelling stories that resonate</li><li>Structure narratives for greater impact</li><li>Use emotion to inspire action</li></ul>
          <div class="package-session-footer"><span>Commanding Presence Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-communication-advanced-card>
          <div class="package-session-meta"><span>SESSION 12</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>TEDx, Keynote &amp; High-Stakes Presentations</h3>
          <ul><li>Structure powerful, memorable speeches</li><li>Deliver key messages with confidence</li><li>Captivate audiences in high-pressure settings</li></ul>
          <div class="package-session-footer"><span>Commanding Presence Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-styling-advanced-card hidden>
          <div class="package-session-meta"><span>SESSION 07</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Signature Aesthetic Development</h3>
          <ul><li>Define your signature style identity</li><li>Curate a wardrobe of timeless essentials</li><li>Align your appearance with your personal brand</li></ul>
          <div class="package-session-footer"><span>Exclusive Transformation Package · VIP Makeover Experience Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-styling-advanced-card hidden>
          <div class="package-session-meta"><span>SESSION 08</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Personal Colour Profiling</h3>
          <ul><li>Discover your most flattering colours</li><li>Identify your personalised seasonal palette</li><li>Build a cohesive, colour-harmonised wardrobe</li></ul>
          <div class="package-session-footer"><span>Exclusive Transformation Package · VIP Makeover Experience Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-styling-advanced-card hidden>
          <div class="package-session-meta"><span>SESSION 09</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Refined Dining Etiquette</h3>
          <ul><li>Master the art of formal dining</li><li>Navigate fine-dining etiquette and table settings</li><li>Practise polished dining in professional settings</li></ul>
          <div class="package-session-footer"><span>VIP Makeover Experience Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-styling-advanced-card hidden>
          <div class="package-session-meta"><span>SESSION 10</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Sophisticated Social Etiquette</h3>
          <ul><li>Cultivate graceful social presence</li><li>Master introductions and refined social interactions</li><li>Navigate professional settings with poise</li></ul>
          <div class="package-session-footer"><span>VIP Makeover Experience Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-styling-advanced-card hidden>
          <div class="package-session-meta"><span>SESSION 11</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Conversational Excellence</h3>
          <ul><li>Refine articulation and verbal expression</li><li>Master the art of engaging conversation</li><li>Communicate with tact, warmth, and confidence</li></ul>
          <div class="package-session-footer"><span>VIP Makeover Experience Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
        <article class="package-session-card" data-styling-advanced-card hidden>
          <div class="package-session-meta"><span>SESSION 12</span><span class="package-session-time">◷ &nbsp;60 mins · 1:1 Video</span></div>
          <h3>Personal Grooming &amp; Polish</h3>
          <ul><li>Develop a signature grooming routine</li><li>Refine hair, makeup, and finishing touches</li><li>Cultivate a consistently polished appearance</li></ul>
          <div class="package-session-footer"><span>VIP Makeover Experience Package</span><a href="#package-booking">Select <span aria-hidden="true">→</span></a></div>
        </article>
      </div>
      <div class="package-session-slider-controls" aria-label="Video session slider controls">
        <span class="package-session-slider-status" data-session-slider-status aria-live="polite">01 / 04</span>
        <button type="button" data-session-slider-prev aria-label="Show previous sessions" disabled><span aria-hidden="true">←</span></button>
        <button type="button" data-session-slider-next aria-label="Show next sessions"><span aria-hidden="true">→</span></button>
      </div>
      <div class="package-session-discovery">
        <p data-package-discovery-prompt>Not sure which area of your personal style needs attention first?</p>
        <a href="#package-booking" data-discovery-booking>Book a 15-Minute Discovery Call <span aria-hidden="true">→</span></a>
      </div>
    </section>



        <section class="package-comparison-section" aria-labelledby="package-comparison-title">
      <div class="package-comparison-inner">
        <h2 id="package-comparison-title">Select Your Preferred Package</h2>
        <div class="package-comparison-scroll" role="region" aria-label="Communication package inclusions" tabindex="0">
          <table class="package-comparison-table">
            <caption class="package-comparison-caption">Twelve video coaching sessions included across the communication package tiers</caption>
            <thead>
              <tr>
                <th scope="col">Service / Scope</th>
                <th scope="col">Core Communication</th>
                <th scope="col">Powerful Presence</th>
                <th class="package-comparison-commanding" scope="col">Commanding Presence</th>
              </tr>
            </thead>
            <tbody>
              <tr><th scope="row"><span class="package-comparison-topic-title">Non-Verbal Communication &amp; Body Language</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Vocal Dynamics &amp; Tonal Control</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Verbal Precision &amp; Articulation</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Conversational Intelligence &amp; Social Fluency</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Assertiveness &amp; Verbal Authority</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Executive Communication &amp; Speaking Impact</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Commanding Stage Presence</span></th><td><span aria-label="Not included">—</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Camera Confidence &amp; Ease</span></th><td><span aria-label="Not included">—</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Mastering Advanced Body Language</span></th><td><span aria-label="Not included">—</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Executive Presence &amp; Impact</span></th><td><span aria-label="Not included">—</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Storytelling for Influence</span></th><td><span aria-label="Not included">—</span></td><td><span aria-label="Not included">—</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">TEDx, Keynote &amp; High-Stakes Presentations</span></th><td><span aria-label="Not included">—</span></td><td><span aria-label="Not included">—</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr class="package-comparison-price-row"><th scope="row">Price</th><td><button class="package-comparison-price-button" type="button" data-comparison-plan="Core Communication" data-comparison-price="₹49,999" aria-label="Choose Core Communication for ₹49,999">₹49,999</button></td><td><button class="package-comparison-price-button" type="button" data-comparison-plan="Powerful Presence" data-comparison-price="₹74,999" aria-label="Choose Powerful Presence for ₹74,999">₹74,999</button></td><td class="package-comparison-commanding"><button class="package-comparison-price-button" type="button" data-comparison-plan="Commanding Presence" data-comparison-price="₹99,999" aria-label="Choose Commanding Presence for ₹99,999">₹99,999</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="package-comparison-section package-style-comparison-section" aria-labelledby="package-style-comparison-title">
      <div class="package-comparison-inner">
        <h2 id="package-style-comparison-title">Select Your Preferred Package</h2>
        <div class="package-comparison-scroll" role="region" aria-label="Styling package inclusions" tabindex="0">
          <table class="package-comparison-table package-style-comparison-table">
            <caption class="package-comparison-caption">Twelve styling coaching sessions included across the package tiers</caption>
            <thead>
              <tr>
                <th scope="col">Service / Scope</th>
                <th scope="col">Signature Experience</th>
                <th scope="col">Exclusive Transformation</th>
                <th class="package-comparison-commanding" scope="col">VIP Makeover Experience</th>
              </tr>
            </thead>
            <tbody>
              <tr><th scope="row"><span class="package-comparison-topic-title">Face Shape Assessment</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Body Shape Analysis</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Lifestyle &amp; Image Analysis</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Makeup &amp; Accessories Styling</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Curated Styling for Formal Occasions</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Opening Session for Styling (Anything of your choice)</span></th><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Signature Aesthetic Development</span></th><td><span aria-label="Not included">—</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Personal Colour Profiling</span></th><td><span aria-label="Not included">—</span></td><td><span class="package-comparison-check" aria-label="Included">✓</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Refined Dining Etiquette</span></th><td><span aria-label="Not included">—</span></td><td><span aria-label="Not included">—</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Sophisticated Social Etiquette</span></th><td><span aria-label="Not included">—</span></td><td><span aria-label="Not included">—</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Conversational Excellence</span></th><td><span aria-label="Not included">—</span></td><td><span aria-label="Not included">—</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr><th scope="row"><span class="package-comparison-topic-title">Personal Grooming &amp; Polish</span></th><td><span aria-label="Not included">—</span></td><td><span aria-label="Not included">—</span></td><td class="package-comparison-commanding"><span class="package-comparison-check" aria-label="Included">✓</span></td></tr>
              <tr class="package-comparison-price-row"><th scope="row">Price</th><td><button class="package-comparison-price-button" type="button" data-comparison-plan="Signature Experience" data-comparison-price="₹70,000" aria-label="Choose Signature Experience for ₹70,000">₹70,000</button></td><td><button class="package-comparison-price-button" type="button" data-comparison-plan="Exclusive Transformation" data-comparison-price="₹90,000" aria-label="Choose Exclusive Transformation for ₹90,000">₹90,000</button></td><td class="package-comparison-commanding"><button class="package-comparison-price-button" type="button" data-comparison-plan="VIP Makeover Experience" data-comparison-price="₹1,10,000" aria-label="Choose VIP Makeover Experience for ₹1,10,000">₹1,10,000</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="package-booklet-section" aria-labelledby="package-booklet-title">
      <div class="package-booklet-intro">
        <div class="package-section-heading package-booklet-heading">
          <p class="package-page-kicker">CLIENT DELIVERABLE · 28+ PAGES</p>
          <h2 id="package-booklet-title">📝 Detailed Tailored Style recommendation Booklet</h2>
          <p class="package-booklet-description">Authored following your 6 video sessions. Browse the 5 sample demo slides below.</p>
        </div>
        <div class="package-booklet-actions" aria-label="Booklet links">
          <a class="package-booklet-editorial" href="#booklet-demo-panel" data-view-editorial-photo aria-controls="booklet-demo-panel">View Editorial Photo</a>
          <a class="package-booklet-download" href="#booklet-demo-panel"><span aria-hidden="true">⇩</span> DOWNLOAD SAMPLE</a>
        </div>
      </div>
      <div class="package-gallery" data-package-gallery>
        <div class="package-demo-heading">
          <div><p data-demo-kicker>DEMO SLIDE 01 · IDENTITY MATRIX</p><h3 data-demo-title>Style Archetype &amp; Lifestyle Blueprint</h3></div><span class="package-demo-sequence" data-demo-sequence hidden>Slide 01 / 05</span>
        </div>
        <div class="package-gallery-main" role="tabpanel" id="booklet-demo-panel" aria-label="Selected booklet demo images" data-gallery-viewport>
          <div class="package-gallery-track" data-gallery-track></div>
        </div>
        <div class="package-gallery-footer">
          <div class="package-gallery-controls package-gallery-previous" role="group" aria-label="Previous booklet demo">
            <button class="carousel-arrow package-gallery-arrow" type="button" data-gallery-prev aria-label="Previous" aria-controls="booklet-demo-panel">Previous</button>
          </div>
          <div class="package-gallery-dots" data-gallery-dots role="tablist" aria-label="Choose a booklet demo"></div>
          <div class="package-gallery-controls package-gallery-next" role="group" aria-label="Next booklet demo">
            <button class="carousel-arrow package-gallery-arrow" type="button" data-gallery-next aria-label="Next" aria-controls="booklet-demo-panel">Next</button>
          </div>
        </div>
      </div>
    </section>

    <section class="package-testimonials-section" aria-labelledby="package-testimonials-title">
      <div class="package-testimonials-heading">
        <div>
          <p class="package-page-kicker">CLIENT FEEDBACK</p>
          <h2 id="package-testimonials-title">What Clients Say</h2>
          <p class="package-testimonials-note">Portraits are illustrative stock images.</p>
        </div>
        <div class="package-testimonials-rating" aria-label="Rated 4.98 out of 5 by over 210 clients">
          <span aria-hidden="true">★★★★★</span>
          <strong>4.98 / 5.0</strong> <span>(210+ Clients)</span>
        </div>
      </div>
      <div class="package-testimonials-grid">
        <article class="package-testimonial-card" data-package-testimonial-offer="style-package">
          <blockquote>“The Body Shape and Face Shape sessions were revelations. I finally understood why certain blazers made me look boxy. The 28-page Booklet is my permanent reference.”</blockquote>
          <div class="package-testimonial-meta">
            <div class="package-testimonial-person"><img src="<?php echo esc_url(sr_theme_asset('assets/external/c3fe6b388a45fba0.webp')); ?>" alt="Illustrative stock portrait" loading="lazy" decoding="async"><div><strong>Priya Menon</strong><span>VP of Product · Bangalore</span></div></div>
            <span class="package-testimonial-tag">SAVED 25 MIN EVERY MORNING</span>
          </div>
        </article>
        <article class="package-testimonial-card" data-package-testimonial-offer="style-package">
          <blockquote>“In the Open Styling session, Shalini helped me restyle 15 dilemma pieces into 20 fresh outfits without spending a rupee. Invaluable guidance.”</blockquote>
          <div class="package-testimonial-meta">
            <div class="package-testimonial-person"><img src="<?php echo esc_url(sr_theme_asset('assets/external/6b998776885cda90.webp')); ?>" alt="Illustrative stock portrait" loading="lazy" decoding="async"><div><strong>Ananya Sharma</strong><span>Founder · Mumbai</span></div></div>
            <span class="package-testimonial-tag">RESTYLED EXISTING CLOSET</span>
          </div>
        </article>
        <article class="package-testimonial-card" data-package-testimonial-offer="style-package">
          <blockquote>“I used to buy clothes I loved, but often struggled to put together outfits that felt right on me. The sessions helped me understand what actually works for my proportions and personal style.”</blockquote>
          <div class="package-testimonial-meta">
            <div class="package-testimonial-person"><img src="<?php echo esc_url(sr_theme_asset('assets/external/45c01085909285a0.webp')); ?>" alt="Illustrative stock portrait" loading="lazy" decoding="async"><div><strong>Dr. Rohini K.</strong><span>Neurosurgeon &amp; Speaker · Delhi</span></div></div>
            <span class="package-testimonial-tag">EFFORTLESS STAGE PRESENCE</span>
          </div>
        </article>
        <article class="package-testimonial-card" data-package-testimonial-offer="comm-package" hidden>
          <blockquote>“Shalini taught me how to structure my thoughts, handle interruptions, and speak with authority. I now lead meetings with much greater clarity.”</blockquote>
          <div class="package-testimonial-meta">
            <div class="package-testimonial-person"><img src="<?php echo esc_url(sr_theme_asset('assets/external/94870d7a18754bdb.webp')); ?>" alt="Illustrative stock portrait" loading="lazy" decoding="async"><div><strong>Rohan Mehta</strong><span>VP, Strategy &amp; Operations</span></div></div>
            <span class="package-testimonial-tag">LEADERSHIP COMMUNICATION</span>
          </div>
        </article>
        <article class="package-testimonial-card" data-package-testimonial-offer="comm-package" hidden>
          <blockquote>“From investor pitches to difficult negotiations, Shalini helped me with practical techniques for persuasion, storytelling, and handling objections… really wonderful!”</blockquote>
          <div class="package-testimonial-meta">
            <div class="package-testimonial-person"><img src="<?php echo esc_url(sr_theme_asset('assets/external/928a95bbb4dc2f12.webp')); ?>" alt="Illustrative stock portrait" loading="lazy" decoding="async"><div><strong>Arjun Malhotra</strong><span>Founder &amp; CEO</span></div></div>
            <span class="package-testimonial-tag">PITCHING &amp; NEGOTIATION</span>
          </div>
        </article>
        <article class="package-testimonial-card" data-package-testimonial-offer="comm-package" hidden>
          <blockquote>“I learnt to speak spontaneously, eliminate filler words, and make my presentations more impactful! Huge shout-out to her!”</blockquote>
          <div class="package-testimonial-meta">
            <div class="package-testimonial-person"><img src="<?php echo esc_url(sr_theme_asset('assets/external/f0abf997ee4a8d89.webp')); ?>" alt="Illustrative stock portrait" loading="lazy" decoding="async"><div><strong>Karan Bhatia</strong><span>Director, Management Consulting</span></div></div>
            <span class="package-testimonial-tag">PRESENTATION IMPACT</span>
          </div>
        </article>
      </div>
    </section>

    <?php if (($_GET['offer'] ?? '') === 'comm-package') { ?>
    <section class="package-booking-section" id="package-booking" aria-labelledby="package-booking-title">
      <header class="package-booking-heading"><p class="package-page-kicker">SCHEDULE YOUR SESSION</p><h2 id="package-booking-title">Reserve Your Time</h2><div class="package-booking-tabs" role="tablist" aria-label="Booking type"><button type="button" role="tab" aria-selected="true" class="is-selected" data-booking-mode="package">1:1 PACKAGE · FROM ₹49,999</button><button type="button" role="tab" aria-selected="false" data-booking-mode="discovery">15-MIN DISCOVERY (FREE)</button></div></header>
      <form class="package-booking-form" data-package-checkout novalidate>
        <div class="package-booking-fields">
          <label>Full Name *<input name="name" autocomplete="name" required maxlength="120" placeholder="Your name"></label>
          <label>Email Address *<input name="email" type="email" autocomplete="email" required maxlength="190" placeholder="your.email@example.com"></label>
          <label class="package-booking-phone">WhatsApp / Phone *<input name="phone" type="tel" autocomplete="tel" required maxlength="40" placeholder="+91 98765 43210"></label>
        </div>
        <fieldset class="package-booking-package-group"><legend>Choose a package:</legend><div class="package-booking-package-options">
          <button type="button" class="package-booking-package-option" data-plan="comm-core" data-price="49999"><span>Core Communication</span><strong>₹49,999</strong></button>
          <button type="button" class="package-booking-package-option" data-plan="comm-presence" data-price="74999"><span>Powerful Presence</span><strong>₹74,999</strong></button>
          <button type="button" class="package-booking-package-option" data-plan="comm-commanding" data-price="99999"><span>Commanding Presence</span><strong>₹99,999</strong></button>
        </div></fieldset>
        <fieldset class="package-booking-choice-group"><legend>Select Preferred Date:</legend><div class="package-booking-options package-booking-dates" data-date-options></div><input class="package-booking-date-input" id="package-booking-date" name="date" type="date" required tabindex="-1" aria-hidden="true"></fieldset>
        <fieldset class="package-booking-choice-group"><legend>Available Time Slot:</legend><div class="package-booking-options" data-slot-options><p>Select a date to load live availability.</p></div></fieldset>
        <div class="package-booking-submit-row"><p data-booking-note>Razorpay test mode · no live charge</p><button class="package-booking-submit" type="submit">Continue to test payment</button></div>
        <p class="package-booking-status" data-booking-status role="status" aria-live="polite"></p>
      </form>
      <div class="package-booking-success" data-booking-success hidden role="status"><h3>Thank you — your session is confirmed.</h3><p data-confirmation-details></p><p>A confirmation email and calendar invitation have been sent to your email address.</p></div>
    </section>
    <?php } else { ?><section class="package-booking-section" id="package-booking"><?php if (function_exists('sr_lms_render_enrollment')) { sr_lms_render_enrollment(); } else { sr_theme_lms_notice(); } ?></section><?php } ?>

    <section class="package-faq-section" id="package-faq" aria-labelledby="package-faq-title">
      <div class="package-faq-inner">
        <div class="package-faq-heading">
          <p class="package-page-kicker">QUESTIONS</p>
          <h2 id="package-faq-title">Frequently Asked Questions</h2>
        </div>
        <div class="package-faq-list">
          <details class="package-faq-item">
            <summary>How do the 1:1 Video sessions work?</summary>
            <p>Each session is conducted 1-on-1 over private HD Zoom. Shalini conducts optical lighting checks, silhouette mapping, and live wardrobe reviews interactively on camera.</p>
          </details>
          <details class="package-faq-item">
            <summary>What if I have questions before booking?</summary>
            <p>You can book a free 15-minute Discovery Call directly on this page to discuss your goals with Shalini with zero sales pressure.</p>
          </details>
          <details class="package-faq-item">
            <summary>Can I reschedule sessions if my schedule changes?</summary>
            <p>Yes, you have full flexibility to schedule the video sessions, with free rescheduling up to 24 hours in advance.</p>
          </details>
          <details class="package-faq-item">
            <summary>Are package/single sessions refundable or transferable?</summary>
            <p>Package fees are non-refundable and non-transferable. Once purchased, the package cannot be refunded or transferred to another person.</p>
          </details>
        </div>
      </div>
    </section>

  </main>
<?php } ?>
