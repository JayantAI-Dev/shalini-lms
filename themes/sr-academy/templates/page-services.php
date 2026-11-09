<?php defined('ABSPATH') || exit; ?>

  
  

  <main class="services-main" id="services-overview">
    <section class="services-hero" aria-labelledby="services-hero-title">
      <div class="services-hero-copy">
        <p class="services-eyebrow">IMAGE CONSULTING &amp; COMMUNICATION COACHING</p>
        <h1 id="services-hero-title">The Art of Speaking <em>Confidently.</em><br />The Power of Looking Your Best.</h1>
        <p class="services-hero-subtitle">Individual consultations &amp; thoughtfully curated coaching packages to enhance how you communicate, present yourself, and carry your confidence.</p>

        <div class="services-hero-actions" id="book-session">
          <a class="services-primary-btn booking-trigger" href="<?php echo esc_url(sr_theme_url('contact.html?type=consultation')); ?>" data-booking-offer="comm-call">Book a Session</a>
          <a class="services-secondary-btn" href="#consultations">Explore Sessions
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 3.5v12M5.5 11l4.5 4.5 4.5-4.5"/></svg>
          </a>
        </div>

        <div class="services-stats" aria-label="Shalini Rathod service statistics">
          <div class="services-stat">
            <strong>10+</strong>
            <span>Years Industry Experience</span>
          </div>
          <div class="services-stat">
            <strong>15,000+</strong>
            <span>Clients Guided</span>
          </div>
          <div class="services-stat">
            <strong>1,200+</strong>
            <span>Hours of Coaching</span>
          </div>
        </div>
      </div>

      <div class="services-profile-card" aria-label="Shalini Rathod profile">
        <img src="<?php echo esc_url(sr_theme_asset('assets/hero-seated.webp')); ?>" alt="Shalini Rathod" loading="eager" decoding="async" width="1100" height="1531" />
        <div class="services-profile-gradient" aria-hidden="true"></div>
        <div class="services-profile-bar">
          <div>
            <strong>Shalini Rathod <span class="services-verified" aria-label="Verified">✓</span></strong>
            <span>Image &amp; Communication Coach</span>
          </div>
          <span class="services-open-badge">Open for Bookings</span>
        </div>
      </div>

    </section>

    <section class="services-catalog" aria-labelledby="calls-title">
      <div class="services-catalog-inner">
        <header class="services-learning-intro">
          <h2>Personalised Guidance &amp; <em>Flexible Learning</em></h2>
          <p>Choose the guidance, sessions or learning experience that fits your goals.</p>
        </header>
        <div class="services-catalog-toolbar" aria-label="Service filters">
          <div class="services-filter-tabs" role="tablist" aria-label="Filter services">
            <button class="services-filter-tab active" type="button" data-service-filter="all" role="tab" aria-selected="true">
              <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m10 2.5.9 2.65L13.5 6l-2.6 1.05L10 9.8l-.9-2.75L6.5 6l2.6-.85L10 2.5Zm5.5 7.2.65 1.9 1.85.65-1.85.65-.65 1.9-.65-1.9-1.85-.65 1.85-.65.65-1.9ZM4.4 9.9l.75 2.15 2.15.75-2.15.75L4.4 15.7l-.75-2.15-2.15-.75 2.15-.75.75-2.15Z"/></svg>
              <span>All Services</span>
            </button>
            <button class="services-filter-tab" type="button" data-service-filter="calls" role="tab" aria-selected="false">
              <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="6.1" r="2.55"/><path d="M4.5 16.2c.55-3.15 2.45-4.75 5.5-4.75s4.95 1.6 5.5 4.75"/></svg>
              <span>1:1 Calls</span>
            </button>
            <button class="services-filter-tab" type="button" data-service-filter="packages" role="tab" aria-selected="false">
              <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m10 2.4 6 3.2-6 3.2-6-3.2 6-3.2Z"/><path d="m4 9 6 3.2L16 9M4 12.4l6 3.2 6-3.2"/></svg>
              <span>Packages</span>
            </button>
            </div>

          <label class="services-search">
            <span class="sr-only">Search services</span>
            <input id="services-search-input" type="search" placeholder="Search services..." autocomplete="off" />
          </label>
        </div>

        <div class="services-catalog-divider" aria-hidden="true"></div>

        <section class="calls-section" data-service-section="calls">
        <div class="calls-heading-row" id="consultations">
          <div class="calls-heading-copy">
            <p class="calls-kicker">PRIVATE 1:1 SESSIONS</p>
            <h2 id="calls-title">1:1 <em>Calls</em></h2>
            <p>Focused single session consultations to address your immediate speaking or styling<br class="calls-desktop-break" /> priorities.</p>
          </div>

          <div class="calls-heading-controls">
            <div class="calls-duration-toggle" role="group" aria-label="Consultation duration">
              <button type="button" data-duration="30" aria-pressed="false">30 Min</button>
              <button class="active" type="button" data-duration="60" aria-pressed="true">60 Min</button>
            </div>
            <div class="calls-carousel-controls" role="group" aria-label="1:1 call card navigation">
              <button class="carousel-arrow calls-carousel-prev" type="button" aria-controls="calls-card-carousel" aria-label="Previous call cards" disabled>
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m11.75 5.5-4.5 4.5 4.5 4.5"/></svg>
              </button>
              <button class="carousel-arrow calls-carousel-next" type="button" aria-controls="calls-card-carousel" aria-label="Next call cards">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m8.25 5.5 4.5 4.5-4.5 4.5"/></svg>
              </button>
            </div>
          </div>
        </div>

        <div class="calls-grid calls-carousel" id="calls-card-carousel" data-service-category="calls" aria-label="1:1 consultation cards">
          <article class="call-card" data-search="communication confidence speaking vocal cadence body language presentation meeting">
            <div class="call-card-media">
              <img data-fallback="<?php echo esc_url(sr_theme_asset('assets/services/communication-workshop.webp')); ?>" src="<?php echo esc_url(sr_theme_asset('assets/services/communication-workshop.webp')); ?>" alt="Communication and confidence consultation with a professional speaker" loading="lazy" decoding="async" width="1300" height="515" />
              <div class="call-card-media-gradient" aria-hidden="true"></div>
              <span class="call-duration-badge">
                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="6.1"/><path d="M10 6.6v3.8l2.5 1.45"/></svg>
                <span data-duration-label>60min</span>
              </span>
              <h3 data-duration-copy="30" hidden>Communication Refinement</h3>
              <h3 data-duration-copy="60">Communication Mastery</h3>
            </div>

            <div class="call-card-body">
              <p class="call-card-description" data-duration-copy="30" hidden>A focused consultation to identify what’s holding you back and find practical ways to approach your next speaking situation with greater confidence.</p>
              <p class="call-card-description" data-duration-copy="60">A dedicated session to explore your communication patterns, refine your delivery, and practise expressing yourself with greater intention.</p>

              <ul class="call-features" data-duration-list="30" hidden>
                <li><span class="call-check">✓</span><span>Pinpoint the moments when you struggle to express yourself</span></li>
                <li><span class="call-check">✓</span><span>Learn techniques suited to your communication style</span></li>
                <li><span class="call-check">✓</span><span>Identify a clear starting point for improvement</span></li>
              </ul>
              <ul class="call-features" data-duration-list="60">
                <li><span class="call-check">✓</span><span>Explore how your voice, language, and delivery work together</span></li>
                <li><span class="call-check">✓</span><span>Work through practical speaking scenarios</span></li>
                <li><span class="call-check">✓</span><span>Take away a personalised framework for ongoing practice</span></li>
              </ul>

              <p class="call-ideal" data-duration-ideal="30" hidden><em>Ideal for: Quick prep for a key meeting, speech, or interview.</em></p>
              <p class="call-ideal" data-duration-ideal="60"><em>Ideal for: Leaders seeking thorough vocal and presence recalibration.</em></p>

              <div class="call-card-divider" aria-hidden="true"></div>

              <div class="call-card-footer">
                <div class="call-price-wrap">
                  <strong class="call-price" data-price30="₹3,750" data-price60="₹7,999">₹7,999</strong>
                  <span>/ session</span>
                </div>
                <a class="call-book-btn booking-trigger" href="<?php echo esc_url(sr_theme_url('contact.html?type=consultation')); ?>" data-booking-offer="comm-call">
                  <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="5.2" width="13" height="11.2" rx="1.5"/><path d="M6.5 3.5v3.4M13.5 3.5v3.4M3.5 8.1h13"/></svg>
                  <span>Book Call</span>
                </a>
              </div>
            </div>
          </article>

          <article class="call-card styling-card" data-search="image styling confidence wardrobe color palette outfits shopping executive looks">
            <div class="call-card-media call-card-media-style">
              <img data-fallback="<?php echo esc_url(sr_theme_asset('assets/services/styling-consultation.webp')); ?>" src="<?php echo esc_url(sr_theme_asset('assets/services/styling-consultation.webp')); ?>" alt="Personal image styling and confidence consultation" loading="lazy" decoding="async" width="1300" height="688" />
              <div class="call-card-media-gradient" aria-hidden="true"></div>
              <span class="call-duration-badge">
                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="6.1"/><path d="M10 6.6v3.8l2.5 1.45"/></svg>
                <span data-duration-label>60min</span>
              </span>
              <h3 data-duration-copy="30" hidden>Focused Styling Consultation</h3>
              <h3 data-duration-copy="60">Personal Style Consultation</h3>
            </div>

            <div class="call-card-body">
              <p class="call-card-description" data-duration-copy="30" hidden>Make more intentional style choices with personalised advice.</p>
              <p class="call-card-description" data-duration-copy="60">Define your signature style with personalised guidance.</p>

              <ul class="call-features" data-duration-list="30" hidden>
                <li><span class="call-check">✓</span><span>Identify the cuts and proportions that work for you</span></li>
                <li><span class="call-check">✓</span><span>Refine outfit combinations and styling details</span></li>
                <li><span class="call-check">✓</span><span>Make better use of pieces already in your wardrobe</span></li>
              </ul>
              <ul class="call-features" data-duration-list="60">
                <li><span class="call-check">✓</span><span>Establish a cohesive direction for your personal image</span></li>
                <li><span class="call-check">✓</span><span>Explore versatile outfit combinations for different occasions</span></li>
                <li><span class="call-check">✓</span><span>Create a clearer approach to wardrobe additions and accessories</span></li>
              </ul>

              <p class="call-ideal" data-duration-ideal="30" hidden><em>For immediate clarity on your style choices.</em></p>
              <p class="call-ideal" data-duration-ideal="60"><em>For a more cohesive and considered wardrobe.</em></p>

              <div class="call-card-divider" aria-hidden="true"></div>

              <div class="call-card-footer">
                <div class="call-price-wrap">
                  <strong class="call-price" data-price30="₹3,750" data-price60="₹7,999">₹7,999</strong>
                  <span>/ session</span>
                </div>
                <a class="call-book-btn booking-trigger" href="<?php echo esc_url(sr_theme_url('contact.html?type=consultation')); ?>" data-booking-offer="style-call">
                  <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="5.2" width="13" height="11.2" rx="1.5"/><path d="M6.5 3.5v3.4M13.5 3.5v3.4M3.5 8.1h13"/></svg>
                  <span>Book Call</span>
                </a>
              </div>
            </div>
          </article>

          <article class="call-card styling-card" data-available-duration="60" data-search="personalised colour analysis skin tone undertone contrast colour palette power colours jewellery makeup hair colour guide styling shopping">
            <div class="call-card-media">
              <img data-fallback="<?php echo esc_url(sr_theme_asset('assets/services/communication-workshop.webp')); ?>" src="<?php echo esc_url(sr_theme_asset('assets/services/communication-workshop.webp')); ?>" alt="Communication and confidence consultation with a professional speaker" loading="lazy" decoding="async" width="1300" height="515" />
              <div class="call-card-media-gradient" aria-hidden="true"></div>
              <span class="call-duration-badge">
                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="6.1"/><path d="M10 6.6v3.8l2.5 1.45"/></svg>
                <span data-duration-label>60min</span>
              </span>
              <h3 data-duration-copy="30" hidden>Personalised Colour Analysis Consultation</h3>
              <h3 data-duration-copy="60">Personalised Colour Analysis Consultation</h3>
            </div>

            <div class="call-card-body">
              <p class="call-card-description" data-duration-copy="30" hidden>Find the colours that bring out your natural radiance.</p>
              <p class="call-card-description" data-duration-copy="60">Find the colours that bring out your natural radiance.</p>

              <ul class="call-features" data-duration-list="30" hidden>
                <li><span class="call-check">✓</span><span>Skin Tone, Undertone & Contrast Assessment</span></li>
                <li><span class="call-check">✓</span><span>Personalised Colour Palette & Power Colours</span></li>
                <li><span class="call-check">✓</span><span>Jewellery, Makeup & Hair Colour Recommendations</span></li>
                <li><span class="call-check">✓</span><span>Personalised Colour Guide with Styling & Shopping Tips</span></li>
              </ul>
              <ul class="call-features" data-duration-list="60">
                <li><span class="call-check">✓</span><span>Skin Tone, Undertone & Contrast Assessment</span></li>
                <li><span class="call-check">✓</span><span>Personalised Colour Palette & Power Colours</span></li>
                <li><span class="call-check">✓</span><span>Jewellery, Makeup & Hair Colour Recommendations</span></li>
                <li><span class="call-check">✓</span><span>Personalised Colour Guide with Styling & Shopping Tips</span></li>
              </ul>

              <div class="call-card-divider" aria-hidden="true"></div>

              <div class="call-card-footer">
                <div class="call-price-wrap">
                  <strong class="call-price" data-price30="₹3,750" data-price60="₹7,999">₹7,999</strong>
                  <span>/ session</span>
                </div>
                <a class="call-book-btn booking-trigger" href="<?php echo esc_url(sr_theme_url('contact.html?type=consultation')); ?>" data-booking-offer="comm-call">
                  <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="5.2" width="13" height="11.2" rx="1.5"/><path d="M6.5 3.5v3.4M13.5 3.5v3.4M3.5 8.1h13"/></svg>
                  <span>Book Call</span>
                </a>
              </div>
            </div>
          </article>

          <article class="call-card styling-card" data-search="personalised occasion styling outfit silhouette recommendations colour jewellery accessory pairing hair makeup special occasion">
            <div class="call-card-media call-card-media-style">
              <img data-fallback="<?php echo esc_url(sr_theme_asset('assets/services/styling-consultation.webp')); ?>" src="<?php echo esc_url(sr_theme_asset('assets/services/styling-consultation.webp')); ?>" alt="Personal image styling and confidence consultation" loading="lazy" decoding="async" width="1300" height="688" />
              <div class="call-card-media-gradient" aria-hidden="true"></div>
              <span class="call-duration-badge">
                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="6.1"/><path d="M10 6.6v3.8l2.5 1.45"/></svg>
                <span data-duration-label>60min</span>
              </span>
              <h3 data-duration-copy="30" hidden>Personalised Occasion Styling</h3>
              <h3 data-duration-copy="60">Personalised Occasion Styling</h3>
            </div>

            <div class="call-card-body">
              <p class="call-card-description" data-duration-copy="30" hidden>Feel your most confident and beautiful with a look thoughtfully styled just for your special occasion.</p>
              <p class="call-card-description" data-duration-copy="60">Feel your most confident and beautiful with a look thoughtfully styled just for your special occasion.</p>

              <ul class="call-features" data-duration-list="30" hidden>
                <li><span class="call-check">✓</span><span>Personalised Outfit & Silhouette Recommendations</span></li>
                <li><span class="call-check">✓</span><span>Colour, Jewellery & Accessory Pairing</span></li>
                <li><span class="call-check">✓</span><span>Hair & Makeup Direction</span></li>
                <li><span class="call-check">✓</span><span>A Complete Look Curated for Your Occasion</span></li>
              </ul>
              <ul class="call-features" data-duration-list="60">
                <li><span class="call-check">✓</span><span>Personalised Outfit & Silhouette Recommendations</span></li>
                <li><span class="call-check">✓</span><span>Colour, Jewellery & Accessory Pairing</span></li>
                <li><span class="call-check">✓</span><span>Hair & Makeup Direction</span></li>
                <li><span class="call-check">✓</span><span>A Complete Look Curated for Your Occasion</span></li>
              </ul>

              <div class="call-card-divider" aria-hidden="true"></div>

              <div class="call-card-footer">
                <div class="call-price-wrap">
                  <strong class="call-price" data-price30="₹3,750" data-price60="₹7,999">₹7,999</strong>
                  <span>/ session</span>
                </div>
                <a class="call-book-btn booking-trigger" href="<?php echo esc_url(sr_theme_url('contact.html?type=consultation')); ?>" data-booking-offer="style-call">
                  <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="5.2" width="13" height="11.2" rx="1.5"/><path d="M6.5 3.5v3.4M13.5 3.5v3.4M3.5 8.1h13"/></svg>
                  <span>Book Call</span>
                </a>
              </div>
            </div>
          </article>
        </div>
        </section>

        <section class="packages-section" id="packages" data-service-section="packages" aria-labelledby="packages-title">
          <div class="packages-heading">
            <p class="packages-kicker">FLAGSHIP PROGRAMS</p>
            <h2 id="packages-title">Signature <em>Packages</em></h2>
            <p>Private coaching and styling partnerships shaped around your goals, with practical support and tailored materials.</p>
          </div>

          <div class="packages-grid" data-service-category="packages">
            <article class="package-card" data-search="communication skills confidence makeover speaking whatsapp voice feedback speech analysis drills speakathon executive speaker playbook">
              <div class="package-card-media package-card-media-canva">
                <div class="canva-slide-viewport">
                  <div class="confidence-slide-carousel" data-confidence-carousel role="region" aria-label="Speak with Confidence slides" tabindex="0">
                    <img class="confidence-slide-image" data-confidence-slide src="<?php echo esc_url(sr_theme_asset('assets/speak-with-confidence/slide-01.png')); ?>" alt="Slide 1 of 10: Voice Mastery — Speak with Confidence." width="1920" height="1080" fetchpriority="high" decoding="async">
                    <div class="confidence-slide-controls" aria-label="Presentation controls">
                      <button class="confidence-slide-arrow" data-confidence-prev type="button" aria-label="Previous slide" disabled><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M11.75 5.5 7.25 10l4.5 4.5"></path></svg></button>
                      <button class="confidence-slide-arrow" data-confidence-next type="button" aria-label="Next slide"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m8.25 5.5 4.5 4.5-4.5 4.5"></path></svg></button>
                      <button class="confidence-slide-fullscreen" data-confidence-fullscreen type="button" aria-label="View slide fullscreen" title="View slide fullscreen"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6m0-6-8 8M10 20H4v-6m0 6 8-8"></path></svg></button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="package-card-body">
                <p class="package-description">A 7-week private accelerator to articulate complex ideas, speak off the cuff, and present with quiet authority.</p>

                <div class="package-includes">
                  <h4>INCLUDES:</h4>
                  <ul>
                    <li><span class="package-check">✓</span><span>Private 60-minute 1:1 Zoom sessions</span></li>
                    <li><span class="package-check">✓</span><span>WhatsApp voice feedback + support whenever you need it</span></li>
                    <li><span class="package-check">✓</span><span>Video speech analysis &amp; drills</span></li>
                    <li><span class="package-check">✓</span><span>Personalized Executive Speaker Playbook</span></li>
                  </ul>
                </div>

                <div class="package-card-divider" aria-hidden="true"></div>

                <div class="package-price-row">
                  <div>
                    <strong class="package-price">₹39,999</strong>
                  </div>
                  <a class="package-syllabus" href="<?php echo esc_url(sr_theme_url('package-enroll.html?offer=comm-package')); ?>">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 4.5h5.2c.9 0 1.3.35 1.3 1.15V16c0-.9-.75-1.45-1.75-1.45H3.5V4.5Zm13 0h-5.2c-.9 0-1.3.35-1.3 1.15V16c0-.9.75-1.45 1.75-1.45h4.75V4.5Z"/></svg>
                    <span>Syllabus</span>
                    <svg class="icon icon-arrow-right" viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 10h13M11 4.5 16.5 10 11 15.5"/></svg>
                  </a>
                </div>

                <a class="package-enroll-btn" href="<?php echo esc_url(sr_theme_url('package-enroll.html?offer=comm-package')); ?>">
                  <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="5.2" width="13" height="11.2" rx="1.5"/><path d="M6.5 3.5v3.4M13.5 3.5v3.4M3.5 8.1h13"/></svg>
                  <span>Enroll in Package</span>
                </a>
              </div>
            </article>

            <article class="package-card styling-card" data-search="image styling makeover color analysis face shape body proportions closet edits wardrobe audit makeup signature style booklet">
              <div class="package-card-media package-card-media-canva">
                <div class="canva-slide-viewport">
                  <div class="confidence-slide-carousel" data-confidence-carousel data-slide-directory="<?php echo esc_url(sr_theme_asset('assets/style-transformation')); ?>" data-slide-title="Shalini’s Style Transformation" role="region" aria-label="Shalini’s Style Transformation slides" tabindex="0">
                    <img class="confidence-slide-image" data-confidence-slide src="<?php echo esc_url(sr_theme_asset('assets/style-transformation/slide-01.png')); ?>" alt="Shalini’s Style Transformation: slide 1 of 10." width="1920" height="1080" loading="lazy" decoding="async">
                    <div class="confidence-slide-controls" aria-label="Presentation controls">
                      <button class="confidence-slide-arrow" data-confidence-prev type="button" aria-label="Previous slide" disabled><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M11.75 5.5 7.25 10l4.5 4.5"></path></svg></button>
                      <button class="confidence-slide-arrow" data-confidence-next type="button" aria-label="Next slide"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m8.25 5.5 4.5 4.5-4.5 4.5"></path></svg></button>
                      <button class="confidence-slide-fullscreen" data-confidence-fullscreen type="button" aria-label="View slide fullscreen" title="View slide fullscreen"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6m0-6-8 8M10 20H4v-6m0 6 8-8"></path></svg></button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="package-card-body">
                <p class="package-description">A bespoke styling partnership to refine your visual identity through six private video consultations and a tailored 28-page Style Booklet.</p>

                <div class="package-includes">
                  <h4>INCLUDES:</h4>
                  <ul>
                    <li><span class="package-check">✓</span><span>Private video consultations: face shape, body shape, makeup, formal styling, lifestyle, and open session</span></li>
                    <li><span class="package-check">✓</span><span>Personalized 28-page Style Booklet</span></li>
                    <li><span class="package-check">✓</span><span>Guidance tailored to your wardrobe, proportions, and lifestyle</span></li>
                    <li><span class="package-check">✓</span><span>Custom Style Bible with shopping links, outfit formulas &amp; accessory guidance</span></li>
                  </ul>
                </div>

                <div class="package-card-divider" aria-hidden="true"></div>

                <div class="package-price-row">
                  <div>
                    <strong class="package-price">₹69,999</strong>
                  </div>
                  <a class="package-syllabus" href="<?php echo esc_url(sr_theme_url('package-enroll.html?offer=style-package#package-overview-title')); ?>">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 4.5h5.2c.9 0 1.3.35 1.3 1.15V16c0-.9-.75-1.45-1.75-1.45H3.5V4.5Zm13 0h-5.2c-.9 0-1.3.35-1.3 1.15V16c0-.9.75-1.45 1.75-1.45h4.75V4.5Z"/></svg>
                    <span>Syllabus</span>
                    <svg class="icon icon-arrow-right" viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 10h13M11 4.5 16.5 10 11 15.5"/></svg>
                  </a>
                </div>

                <a class="package-enroll-btn" href="<?php echo esc_url(sr_theme_url('package-enroll.html?offer=style-package')); ?>">
                  <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="5.2" width="13" height="11.2" rx="1.5"/><path d="M6.5 3.5v3.4M13.5 3.5v3.4M3.5 8.1h13"/></svg>
                  <span>Enroll in Package</span>
                </a>
              </div>
            </article>
          </div>
        </section>



        


        

        <section class="compare-section" id="compare" aria-labelledby="compare-title">
          <div class="compare-heading">
            <p class="compare-kicker">
              <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m10 2.5.9 2.65L13.5 6l-2.6 1.05L10 9.8l-.9-2.75L6.5 6l2.6-.85L10 2.5Zm5.5 7.2.65 1.9 1.85.65-1.85.65-.65 1.9-.65-1.9-1.85-.65 1.85-.65.65-1.9ZM4.4 9.9l.75 2.15 2.15.75-2.15.75L4.4 15.7l-.75-2.15-2.15-.75 2.15-.75.75-2.15Z"/></svg>
              <span>CLARITY GUIDE</span>
              <span class="compare-kicker-dot" aria-hidden="true">·</span>
              <span>CHOOSE YOUR TRANSFORMATION TRACK</span>
            </p>
            <h2 id="compare-title">Which Offering Is <em>Right for You?</em></h2>
            <p>Compare time investment, mentorship level, and depth to match your current career milestones.</p>
          </div>

          <div class="compare-table-scroll" role="region" aria-label="Compare Shalini Rathod offerings" tabindex="0">
            <div class="compare-table" style="min-width:0" role="table" aria-describedby="compare-title">
              <div style="grid-template-columns:1.05fr 1fr 1.12fr" class="compare-row compare-header-row" role="row">
                <div class="compare-cell compare-scope-header" role="columnheader">FEATURE / SCOPE</div>
                <div class="compare-cell" role="columnheader">
                  <strong>1:1 Strategic Call</strong>
                  <span>1 Single Session (30m or 60m)</span>
                </div>
                <div class="compare-cell compare-featured" role="columnheader">
                  <strong>Flagship Multi-Session Package</strong>
                  <span>7 Weekly 1:1 Sessions (7 Weeks)</span>
                </div>
                
              </div>

              <div style="grid-template-columns:1.05fr 1fr 1.12fr" class="compare-row" role="row">
                <div class="compare-cell compare-label" role="rowheader">Format</div>
                <div class="compare-cell" role="cell">Private Live Zoom</div>
                <div class="compare-cell compare-featured compare-strong" role="cell">7 Private Live Zoom Sessions</div>
                
              </div>

              <div style="grid-template-columns:1.05fr 1fr 1.12fr" class="compare-row" role="row">
                <div class="compare-cell compare-label" role="rowheader">Direct Shalini Mentorship</div>
                <div class="compare-cell compare-check-cell" role="cell"><span class="compare-check compare-check-green">✓</span><span>30 or 60 Minutes Live</span></div>
                <div class="compare-cell compare-featured compare-check-cell compare-strong" role="cell"><span class="compare-check compare-check-rust">✓</span><span>Live sessions + WhatsApp support</span></div>
                
              </div>

              <div style="grid-template-columns:1.05fr 1fr 1.12fr" class="compare-row" role="row">
                <div class="compare-cell compare-label" role="rowheader">Customized Dossier / Playbook</div>
                <div class="compare-cell" role="cell">1-page action summary</div>
                <div class="compare-cell compare-featured compare-strong" role="cell">Action summary + study materials</div>
                
              </div>

              <div style="grid-template-columns:1.05fr 1fr 1.12fr" class="compare-row" role="row">
                <div class="compare-cell compare-label" role="rowheader">Between-Session Voice Support</div>
                <div class="compare-cell" role="cell">None</div>
                <div class="compare-cell compare-featured compare-check-cell compare-strong" role="cell"><span class="compare-check compare-check-rust">✓</span><span>Voice feedback + support whenever you need it</span></div>
                
              </div>

              <div style="grid-template-columns:1.05fr 1fr 1.12fr" class="compare-row compare-ideal-row" role="row">
                <div class="compare-cell compare-label" role="rowheader">Ideal For</div>
                <div class="compare-cell compare-small-copy" role="cell">Immediate prep before an interview, keynote, or outfit review</div>
                <div class="compare-cell compare-featured compare-small-copy compare-strong" role="cell">End-to-end executive rebranding, career promotion, or profound confidence shift</div>
                
              </div>

              <div style="grid-template-columns:1.05fr 1fr 1.12fr" class="compare-row compare-link-row" role="row">
                <div class="compare-cell compare-label" role="cell"></div>
                <div class="compare-cell" role="cell"><a href="#consultations">View 1:1 Calls <svg class="icon icon-arrow-right" viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 10h13M11 4.5 16.5 10 11 15.5"/></svg></a></div>
                <div class="compare-cell compare-featured" role="cell"><a href="#packages">View Multi-Session Packages <svg class="icon icon-arrow-right" viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 10h13M11 4.5 16.5 10 11 15.5"/></svg></a></div>
                
              </div>
            </div>
          </div>

          <div class="compare-mobile" aria-label="Compare Shalini Rathod offerings on mobile">
            <article class="compare-mobile-card">
              <div class="compare-mobile-card-head">
                <span class="compare-mobile-number">01</span>
                <div>
                  <h3>1:1 Strategic Call</h3>
                  <p>1 Single Session (30m or 60m)</p>
                </div>
              </div>
              <dl class="compare-mobile-list">
                <div><dt>Format</dt><dd>Private Live Zoom</dd></div>
                <div><dt>Direct Shalini Mentorship</dt><dd><span class="compare-check compare-check-green">✓</span>30 or 60 Minutes Live</dd></div>
                <div><dt>Customized Dossier / Playbook</dt><dd>1-page action summary</dd></div>
                <div><dt>Between-Session Voice Support</dt><dd class="compare-muted">—</dd></div>
                <div><dt>Ideal For</dt><dd>Immediate prep before an interview, keynote, or outfit review</dd></div>
              </dl>
              <a class="compare-mobile-link" href="#consultations">View 1:1 Calls <svg class="icon icon-arrow-right" viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 10h13M11 4.5 16.5 10 11 15.5"/></svg></a>
            </article>

            <article class="compare-mobile-card compare-mobile-card-featured">
              <div class="compare-mobile-popular">Recommended</div>
              <div class="compare-mobile-card-head">
                <span class="compare-mobile-number">02</span>
                <div>
                  <h3>Flagship Multi-Session Package</h3>
                  <p>7 Weekly 1:1 Sessions (7 Weeks)</p>
                </div>
              </div>
              <dl class="compare-mobile-list">
                <div><dt>Format</dt><dd>7 Private Live Zoom Sessions</dd></div>
                <div><dt>Direct Shalini Mentorship</dt><dd><span class="compare-check compare-check-green">✓</span>Live sessions + WhatsApp support</dd></div>
                <div><dt>Customized Dossier / Playbook</dt><dd>Action summary + study materials</dd></div>
                <div><dt>Between-Session Voice Support</dt><dd><span class="compare-check compare-check-green">✓</span>Voice feedback + support whenever you need it</dd></div>
                <div><dt>Ideal For</dt><dd>End-to-end executive rebranding, career promotion, or profound confidence shift</dd></div>
              </dl>
              <a class="compare-mobile-link" href="#packages">View Multi-Session Packages <svg class="icon icon-arrow-right" viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 10h13M11 4.5 16.5 10 11 15.5"/></svg></a>
            </article>

            
          </div>

        </section>


        <section class="client-results-section" id="client-results" aria-labelledby="client-results-title">
          <div class="client-results-heading">
            <p>CLIENT RESULTS</p>
            <h2 id="client-results-title">Client <em>Experiences</em></h2>
            <span class="client-results-note">Illustrative stock portraits</span>
          </div>

          <div class="client-results-grid">
            <article class="client-result-card">
              <blockquote>“The PREP framework and pacing drills completely changed how I speak in executive meetings. I recently led our annual board presentation with complete ease.”</blockquote>
              <div class="client-result-divider" aria-hidden="true"></div>
              <div class="client-result-meta">
                <div class="client-result-person">
                  <img src="<?php echo esc_url(sr_theme_asset('assets/external/5c24a0aafef478c6.webp')); ?>" alt="Illustrative stock portrait" loading="lazy" decoding="async" />
                  <div>
                  <h3>Priyanka Sen</h3>
                  <p>VP of Product Strategy · Fintech</p>
                  </div>
                </div>
                <span class="client-result-outcome">Promoted to VP; Presents confidently to the board</span>
              </div>
            </article>

            <article class="client-result-card">
              <blockquote>“The 30-piece capsule wardrobe makes dressing effortless every morning. I felt completely aligned and confident during our Series A pitch.”</blockquote>
              <div class="client-result-divider" aria-hidden="true"></div>
              <div class="client-result-meta">
                <div class="client-result-person">
                  <img src="<?php echo esc_url(sr_theme_asset('assets/external/3f8333d8dafa28ad.webp')); ?>" alt="Illustrative stock portrait" loading="lazy" decoding="async" />
                  <div>
                  <h3>Ananya Sharma</h3>
                  <p>Founder · D2C Brand</p>
                  </div>
                </div>
                <span class="client-result-outcome">Replaced an overflowing closet with 30 versatile looks</span>
              </div>
            </article>

            <article class="client-result-card">
              <blockquote>“A single 60-minute session helped me pinpoint vocal habits that weakened my delivery and restructure my keynote opening.”</blockquote>
              <div class="client-result-divider" aria-hidden="true"></div>
              <div class="client-result-meta">
                <div class="client-result-person">
                  <img src="<?php echo esc_url(sr_theme_asset('assets/external/94870d7a18754bdb.webp')); ?>" alt="Illustrative stock portrait" loading="lazy" decoding="async" />
                  <div>
                  <h3>Rohit Kulkarni</h3>
                  <p>Partner · Management Consulting</p>
                  </div>
                </div>
                <span class="client-result-outcome">Delivered keynote to 800+ attendees without anxiety</span>
              </div>
            </article>

            <article class="client-result-card">
              <blockquote>“Doing 10 minutes each morning eliminated my verbal crutches, and finding my color palette made an instant difference on stage.”</blockquote>
              <div class="client-result-divider" aria-hidden="true"></div>
              <div class="client-result-meta">
                <div class="client-result-person">
                  <img src="<?php echo esc_url(sr_theme_asset('assets/external/c22dcd9d2bf5e90c.webp')); ?>" alt="Illustrative stock portrait" loading="lazy" decoding="async" />
                  <div>
                  <h3>Dr. Meera Nambiar</h3>
                  <p>Keynote Speaker · Healthcare</p>
                  </div>
                </div>
                <span class="client-result-outcome">Booked 5 international keynote talks</span>
              </div>
            </article>
          </div>
        </section>

        <section class="discovery-call-section" id="discovery" aria-labelledby="discovery-title">
          <div class="discovery-call-card">
            <div class="discovery-call-badge">
              <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="6.5"/><path d="M10 6.3v4.1l2.7 1.55"/></svg>
              <span>15-MINUTE COMPLIMENTARY CALL</span>
            </div>

            <h2 id="discovery-title">Not sure which service is <em>right for you?</em></h2>
            <p class="discovery-call-copy">Book a 15-minute discovery call directly with Shalini Rathod. We'll discuss<br class="discovery-desktop-break" /> your current goals, assess whether communication coaching, styling, or a<br class="discovery-desktop-break" /> self-learning track fits best, and answer any questions.</p>

            <div class="discovery-benefits" aria-label="Discovery call benefits">
              <div class="discovery-benefit">
                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="m6.2 10.1 2.4 2.45 5.25-5.25"/></svg>
                <span>100% Free · No obligation</span>
              </div>
              <div class="discovery-benefit">
                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="m6.2 10.1 2.4 2.45 5.25-5.25"/></svg>
                <span>Private 1:1 on Zoom</span>
              </div>
              <div class="discovery-benefit">
                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="m6.2 10.1 2.4 2.45 5.25-5.25"/></svg>
                <span>Direct guidance from Shalini</span>
              </div>
            </div>

            <a class="discovery-call-button booking-trigger" href="<?php echo esc_url(sr_theme_url('contact.html?type=consultation')); ?>" data-booking-offer="discovery">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.2 4.4 6.6 3.8c-.9-.35-1.95.1-2.3 1.03l-.75 2c-.3.78-.18 1.65.32 2.32 2.65 3.55 5.55 6.45 9.1 9.1.67.5 1.54.62 2.32.32l2-.75c.93-.35 1.38-1.4 1.03-2.3l-.6-1.6a1.8 1.8 0 0 0-1.8-1.16l-2.2.16a1.75 1.75 0 0 1-1.4-.53l-.75-.75a1.75 1.75 0 0 1-.53-1.4l.16-2.2a1.8 1.8 0 0 0-1.16-1.8Z"/></svg>
              <span>Schedule a Discovery Call</span>
            </a>
          </div>
        </section>

        <p class="services-search-empty" id="services-search-empty" hidden>No matching services found.</p>
      </div>
    </section>

    <section class="services-faq-section" id="faq" aria-labelledby="services-faq-title">
      <div class="services-faq-inner">
        <header class="services-faq-heading">
          <p>FAQ</p>
          <h2 id="services-faq-title">Common <em>Questions</em></h2>
        </header>

        <div class="services-faq-list">
          <article class="services-faq-item is-open">
            <button class="services-faq-question" type="button" aria-expanded="true" aria-controls="faq-answer-1" id="faq-question-1">
              <span>How do the 1:1 sessions work?</span>
              <svg class="services-faq-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="m5.5 12 4.5-4.5 4.5 4.5"/></svg>
            </button>
            <div class="services-faq-answer" id="faq-answer-1" role="region" aria-labelledby="faq-question-1">
              <p>All 1:1 sessions take place over private Zoom. You will receive a short intake questionnaire in advance so we can focus immediately on your goals.</p>
            </div>
          </article>

          <article class="services-faq-item">
            <button class="services-faq-question" type="button" aria-expanded="false" aria-controls="faq-answer-2" id="faq-question-2">
              <span>Should I choose 30 or 60 minutes?</span>
              <svg class="services-faq-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="m5.5 8 4.5 4.5 4.5-4.5"/></svg>
            </button>
            <div class="services-faq-answer" id="faq-answer-2" role="region" aria-labelledby="faq-question-2" hidden>
              <p>Choose 30 minutes for quick feedback on an upcoming meeting, pitch, or 3 outfits. Choose 60 minutes for in-depth vocal drills or a full styling diagnostic.</p>
            </div>
          </article>

          <article class="services-faq-item">
            <button class="services-faq-question" type="button" aria-expanded="false" aria-controls="faq-answer-3" id="faq-question-3">
              <span>How are the sessions scheduled?</span>
              <svg class="services-faq-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="m5.5 8 4.5 4.5 4.5-4.5"/></svg>
            </button>
            <div class="services-faq-answer" id="faq-answer-3" role="region" aria-labelledby="faq-question-3" hidden>
              <p>Sessions are typically scheduled once a week. If you’re working towards an upcoming event or deadline, we can also arrange two sessions per week to help you stay on track.</p>
            </div>
          </article>

          <article class="services-faq-item">
            <button class="services-faq-question" type="button" aria-expanded="false" aria-controls="faq-answer-4" id="faq-question-4">
              <span>Are package or single sessions refundable or transferable?</span>
              <svg class="services-faq-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="m5.5 8 4.5 4.5 4.5-4.5"/></svg>
            </button>
            <div class="services-faq-answer" id="faq-answer-4" role="region" aria-labelledby="faq-question-4" hidden>
              <p>Package fees are non-refundable and non-transferable. Once purchased, a package cannot be refunded or transferred to another person.</p>
            </div>
          </article>

          <article class="services-faq-item">
            <button class="services-faq-question" type="button" aria-expanded="false" aria-controls="faq-answer-5" id="faq-question-5">
              <span>Is there a split-payment option available for package sessions?</span>
              <svg class="services-faq-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="m5.5 8 4.5 4.5 4.5-4.5"/></svg>
            </button>
            <div class="services-faq-answer" id="faq-answer-5" role="region" aria-labelledby="faq-question-5" hidden>
              <p>Currently, split payments are not available for package sessions. The full package fee must be paid upfront at the time of booking.</p>
            </div>
          </article>
        </div>
      </div>
    </section>
  </main>


  


  <div class="booking-modal" id="booking-modal" hidden aria-hidden="true">
    <button class="booking-modal-backdrop" type="button" aria-label="Close booking dialog"></button>
    <section class="booking-dialog" role="dialog" aria-modal="true" aria-labelledby="booking-title">
      <header class="booking-dialog-header">
        <div>
          <p class="booking-step-label">CONSULTATION REQUEST <span aria-hidden="true">·</span> <span id="booking-step-count">STEP 1 OF 3</span></p>
          <h2 id="booking-title">Reserve Your <em>Transformation</em></h2>
        </div>
        <button class="booking-close" type="button" aria-label="Close booking dialog">
          <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15"/></svg>
        </button>
      </header>

      <div class="booking-step booking-step-one" data-booking-step="1">
        <label class="booking-field booking-offering-field">
          <span>SELECT OFFERING:</span>
          <select id="booking-offering">
            <optgroup label="Discovery Call (Complimentary)">
              <option value="discovery">15-Min Discovery Call (Free · Direct with Shalini)</option>
            </optgroup>
            <optgroup label="1:1 Consultations (1 Session)">
              <option value="comm-call" selected>1:1 Communication &amp; Confidence</option>
              <option value="style-call">1:1 Image Styling &amp; Confidence</option>
            </optgroup>
            <optgroup label="Flagship 7-Session Packages">
              <option value="comm-package">Communication Skills &amp; Confidence Makeover (7 Sessions)</option>
              <option value="style-package">1:1 Signature Style Transformation (6 Sessions)</option>
            </optgroup>
            <optgroup label="Additional Coaching Programs"><option value="communication-training">Professional Communication Training (5 Sessions)</option><option value="complete-makeover">Complete Image Transformation (5 Sessions)</option></optgroup>
            <optgroup label="Additional Coaching Programs"><option value="communication-training">Professional Communication Training (5 Sessions)</option><option value="complete-makeover">Complete Image Transformation (5 Sessions)</option></optgroup>
            <optgroup label="Self-Learning Courses">
              <option value="speakathon">21-Day Speakathon Challenge</option>
              <option value="styling-course">Power Dressing / Styling</option>
              <option value="dining-etiquette">Dining Etiquette</option>
              <option value="power-bundle">The Power Bundle</option>
            </optgroup>
          </select>
        </label>

        <div class="booking-duration-wrap" id="booking-duration-wrap">
          <p class="booking-control-label">SESSION DURATION:</p>
          <div class="booking-duration-options" role="group" aria-label="Choose session duration">
            <button class="booking-duration-option" type="button" data-booking-duration="30">
              <span class="booking-duration-top"><strong>30 Minutes</strong><span>₹3,750</span></span>
              <small>Laser Diagnostic Call</small>
            </button>
            <button class="booking-duration-option active" type="button" data-booking-duration="60">
              <span class="booking-duration-top"><strong>60 Minutes</strong><span>₹7,999</span></span>
              <small>Comprehensive Deep Dive</small>
            </button>
            <button class="booking-duration-option" type="button" data-booking-duration="90">
              <span class="booking-duration-top"><strong>90 Minutes</strong><span>₹13,999</span></span>
              <small>Personalised colour analysis</small>
            </button>
          </div>
        </div>

        <div class="booking-schedule-box" id="booking-schedule-box">
          <p class="booking-schedule-title">
            <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3.5" y="5" width="13" height="11" rx="1.6"/><path d="M6.5 3.5v3M13.5 3.5v3M3.5 8h13"/></svg>
            <span id="booking-date-label">SELECT PREFERRED DATE &amp; TIME:</span>
          </p>
          <div class="booking-schedule-grid">
            <label class="booking-field">
              <span>Date:</span>
              <input id="booking-date" type="date" />
            </label>
            <label class="booking-field">
              <span>Time Slot:</span>
              <select id="booking-time">
                <option>10:00 AM - 11:00 AM IST</option>
                <option>11:30 AM - 12:30 PM IST</option>
                <option selected>02:00 PM - 03:00 PM IST</option>
                <option>04:30 PM - 05:30 PM IST</option>
                <option>07:00 PM - 08:00 PM IST (Evening / Global)</option>
              </select>
            </label>
          </div>
          <p class="booking-schedule-note" id="booking-schedule-note">* Private Zoom link provided upon confirmation; reschedule anytime with 24 hours notice.</p>
        </div>

        <div class="booking-step-footer">
          <div class="booking-investment">
            <span>Total Investment</span>
            <strong id="booking-total">₹7,999</strong>
          </div>
          <button class="booking-primary-action" id="booking-continue" type="button">
            <span>Continue to Details</span>
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3.5 10h13M11 4.5 16.5 10 11 15.5"/></svg>
          </button>
        </div>
      </div>

      <div class="booking-step booking-step-two" data-booking-step="2" hidden><?php echo sr_theme_cf7_form('Consultation and service request', 'booking-cf7-form'); ?></div>

      <div class="booking-step booking-step-three" data-booking-step="3" hidden>
        <div class="booking-confirmation-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m7.8 12.1 2.8 2.8 5.7-5.8"/></svg>
        </div>
        <p class="booking-confirmation-kicker">BOOKING REQUEST PENDING</p>
        <h3>Thank you, <span id="booking-confirmation-name">there</span>.</h3>
        <p>We have received your preferred date and time. Our team will confirm the availability and any payment arrangements.</p>
        <div class="booking-confirmation-summary" id="booking-confirmation-summary"></div>
        <a class="booking-primary-action booking-send-request" id="booking-send-request" href="mailto:shalinirathodofficial@gmail.com">Open Email App to Send <svg class="cta-arrow-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12h16m-7-7 7 7-7 7"/></svg></a><button type="button" class="booking-done" id="booking-copy-request">Copy Request</button>
        <button class="booking-done" type="button" id="booking-done">Close</button>
      </div>
    </section>
  </div>

