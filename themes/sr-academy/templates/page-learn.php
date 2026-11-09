<?php defined('ABSPATH') || exit; ?>


  
<main class="learn" id="main-content">
  <div class="learn-container">
    <div class="learn-top">
      <a href="<?php echo esc_url(sr_theme_url('lms.html')); ?>" class="learn-back" aria-label="Back to courses"><span aria-hidden="true">←</span> All courses</a>
      <div class="learn-course-brand"><span class="learn-course-mark" id="learn-course-mark" aria-hidden="true">S</span><span id="learn-course-name">Communication Course</span></div>
      <div class="learn-overall">
        <span id="overall-count">0 of 21 complete</span>
        <div class="learn-overall-track" role="progressbar" id="overall-meter" aria-label="Course completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="overall-fill"></span></div>
      </div>
    </div>

    <div class="learn-layout learn-tablet-layout learn-mobile-layout">
      <section class="learn-video-column" aria-label="Video lesson">
        <div class="learn-video" id="video-stage">
          <video id="lesson-media" tabindex="0" playsinline preload="metadata" disablepictureinpicture controlslist="nodownload noplaybackrate noremoteplayback" aria-label="Current Speakathon lesson video. Focus this player and press Space to play or pause."></video>
          <button id="large-play" class="learn-big-play" type="button" aria-label="Play video"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 4.6a1 1 0 0 1 1.52-.85l10 7.4a1 1 0 0 1 0 1.7l-10 7.4A1 1 0 0 1 8 19.4Z"/></svg></button>
          <div id="video-missing" class="learn-media-missing" hidden><span aria-hidden="true">▶</span><strong>Video not uploaded</strong><p>This day's recording is not on the website yet.</p></div>
        <div class="learn-video-bar" role="group" aria-label="Video controls">
          <button id="toggle-play" type="button" class="learn-icon-button" aria-label="Play video" title="Play or pause">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path id="play-icon" d="M8 4.6a1 1 0 0 1 1.52-.85l10 7.4a1 1 0 0 1 0 1.7l-10 7.4A1 1 0 0 1 8 19.4Z"/></svg>
          </button>
          <div class="learn-duration"><span id="elapsed">0:00</span><span class="learn-duration-divider">/</span><span id="duration">0:00</span></div>
          <div class="learn-playback-progress" role="progressbar" id="watch-meter" aria-label="Lesson playback progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="watch-fill"></span></div>
          <button id="volume-toggle" class="learn-icon-button" type="button" aria-label="Mute video" title="Mute or unmute"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4V5ZM15.5 8a5 5 0 0 1 0 8"/></svg></button>
          <button id="full-screen" class="learn-icon-button" type="button" aria-label="Full screen" title="Full screen"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg></button>
        </div>
        </div>

      </section>

      <section class="learn-lesson" aria-label="Current challenge">
        <div class="learn-lesson-top"><span id="day-number" class="learn-eyebrow">DAY 01 / 21</span><span id="week-tag" class="learn-week-tag">WEEK 1</span></div>
        <h1 id="lesson-title">Get comfortable speaking on camera</h1>
        <p id="lesson-summary" class="learn-summary">Build confidence through short daily practice.</p>
        <div class="learn-practice">
          <h2><span class="learn-practice-icon" aria-hidden="true">✦</span> Day <span id="challenge-day">01</span> · Today's challenge</h2>
          <p id="lesson-practice"></p>
        </div>
        <button id="open-worksheet" class="learn-pdf-link" type="button">
          <span class="learn-pdf-icon" aria-hidden="true">▤</span><span id="worksheet-link-label">View Day 01 worksheet</span><span class="learn-pdf-arrow" aria-hidden="true">→</span>
        </button>
        <button id="watch-example" class="learn-pdf-link" type="button" hidden>
          <span class="learn-pdf-icon" aria-hidden="true">▶</span><span>Watch example video</span><span class="learn-pdf-arrow" aria-hidden="true">→</span>
        </button>
        <div class="learn-complete" aria-live="polite">
          <span id="completion-note" class="learn-completion-note">Watch the full video to continue.</span>
          <button id="complete-and-next" class="learn-complete-btn" type="button" disabled><span id="complete-label">Finish video to continue</span><span aria-hidden="true">→</span></button>
        </div>
        
        <div class="learn-next-link">
          <button id="previous-day" type="button" aria-label="Go to previous lesson">← Previous day</button>
          <span id="next-hint">Up next: Day 2</span>
        </div>
      </section>

      <aside class="learn-outline" id="learn-outline" aria-label="21-day course outline">
        <div class="learn-outline-header">
          <div><span class="learn-eyebrow" id="learn-course-label">COMMUNICATION COURSE</span><h2>Course lessons</h2></div>
        </div>
        <div class="learn-roadmap-progress"><span>YOUR PROGRESS</span><strong id="roadmap-done">0 / 21</strong><div class="learn-roadmap-track"><span id="roadmap-fill"></span></div></div>
        <nav class="learn-outline-list" id="outline-list" aria-label="Course lessons"></nav>
      </aside>
      </div>
    </div>
  </div>
</main>
<dialog id="worksheet-dialog" class="learn-pdf-dialog" aria-labelledby="worksheet-title">
  <div class="learn-dialog-head"><div><span class="learn-eyebrow" id="worksheet-tag">WORKSHEET · DAY 01</span><h2 id="worksheet-title">Practice worksheet</h2></div><button type="button" id="close-worksheet" aria-label="Close PDF viewer">×</button></div>
  <iframe id="worksheet-frame" title="Day challenge PDF" loading="lazy"></iframe>
  <a id="open-worksheet-tab" class="learn-pdf-open-tab" target="_blank" rel="noopener noreferrer">PDF not showing? Open in a new tab ↗</a>
  <div id="worksheet-error" class="learn-pdf-error" hidden>This PDF isn't available on the website yet.</div>
</dialog>



