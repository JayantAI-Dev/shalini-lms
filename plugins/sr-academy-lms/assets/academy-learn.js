(() => {
  'use strict';
  const config = window.SR_LMS;
  if (!config?.state?.course) return;
  const state = config.state, course = state.course, slug = state.slug;
  const $ = id => document.getElementById(id);
  const video = $('lesson-media');
  if (!video || !$('outline-list')) return;
  video.controls = false;
  const videoStage = $('video-stage');
  (videoStage || video).addEventListener('contextmenu', event => event.preventDefault());
  const lessons = (course.modules || []).flatMap((module, week) => module.lessons.map((lesson, moduleLessonIndex) => ({...lesson, week, moduleLessonIndex, weekTitle: module.title})));
  if (!lessons.length) return;
  let progress = state.progress || {}, completion = state.completion;
  const practiceQueued = new Set();
  let current = 0, mode = 'lesson', missing = false, previous = null, previousWall = 0;
  let pending = [], saveTimer = null, saving = false, saveAgain = false, restorePosition = 0;
  const params = new URLSearchParams(location.search);
  const node = (tag, className, text) => { const element = document.createElement(tag); if (className) element.className = className; if (text != null) element.textContent = text; return element; };
  const lessonVideos = lesson => Array.isArray(lesson.videos) && lesson.videos.length ? lesson.videos : (lesson.videoUrl ? [{title: 'Video 1', url: lesson.videoUrl, available: Boolean(lesson.videoAvailable)}] : []);
  const time = value => {value = Number.isFinite(value) ? Math.max(0, Math.floor(value)) : 0; return `${Math.floor(value / 60)}:${String(value % 60).padStart(2, '0')}`;};
  const setText = (id, value) => { if ($(id)) $(id).textContent = value; };
  const status = (message, ready = false) => {setText('completion-note', message); $('completion-note')?.classList.toggle('is-ready', ready);};
  const practice = node('label', 'sr-lms-practice-ack');
  const practiceInput = node('input'); practiceInput.type = 'checkbox'; practiceInput.id = 'sr-lms-practice';
  practice.append(practiceInput, node('span', '', slug === 'power-dressing' ? 'I have completed this lesson’s practice.' : 'I have completed today’s practice challenge.'));
  $('lesson-practice')?.parentElement.append(practice);
  const certificate = node('a', 'sr-lms-certificate', 'Print completion certificate ↗');
  certificate.hidden = true;certificate.href = state.certificateUrl; certificate.target = '_blank'; certificate.rel = 'noopener';
  $('learn-outline').append(certificate);
  function updateCompletion() {
    const lesson = lessons[current], record = progress[lesson.id] || {}, duration = Number(lesson.duration) || 0;
    const watched = (record.ranges || []).reduce((sum, range) => sum + range[1] - range[0], 0);
    const percent = duration ? Math.min(100, Math.floor(watched / duration * 100)) : 0;
    const done = Boolean(record.complete), ready = percent >= 90 && Boolean(record.practice);
    $('complete-and-next').disabled = mode !== 'lesson' || missing || !(done || ready);
    practiceInput.disabled = missing; practiceInput.checked = Boolean(record.practice);
    if (mode !== 'lesson') {status('Additional recording — return to the main lesson to save lesson progress.'); return;}
    if (missing) {status('Recording awaiting upload. This lesson cannot be completed yet.'); setText('complete-label', 'Video required');}
    else if (done) {status('Lesson completed ✓', true);setText('complete-label', current === lessons.length - 1 ? 'Course progress saved ✓' : 'Continue to next lesson');}
    else {status(`${percent}% watched · ${record.practice ? 'practice acknowledged' : 'complete your practice'} · 90% coverage required`, ready);setText('complete-label', ready ? 'Save complete & continue' : 'Watch and practise to continue');}
  }
  function updateOutline() {
    document.querySelectorAll('.learn-day-btn').forEach(button => {
      const index = Number(button.dataset.day), lesson = lessons[index];
      const done = Boolean(progress[lesson.id]?.complete) && lesson.videoAvailable;
      button.classList.toggle('is-completed', done); button.classList.toggle('is-missing-video', !lesson.videoAvailable);
      button.setAttribute('aria-current', mode === 'lesson' && current === index ? 'step' : 'false');
      button.querySelector('.learn-day-bullet').textContent = done ? '✓' : String(index + 1).padStart(2, '0');
      const choices = lessonVideos(lesson), available = choices.filter(item => item.available && item.url).length;
      button.querySelector('.learn-day-lock').textContent = slug === 'power-dressing' && choices.length ? `${available}/${choices.length} videos` : lesson.videoAvailable ? '' : 'No video';
    });
    const percent = completion?.percent || 0, count = completion?.completed || 0, total = completion?.total || lessons.length;
    setText('overall-count', `${count} of ${total} complete`); setText('roadmap-done', `${count} / ${total}`);
    for (const id of ['overall-fill', 'roadmap-fill']) if ($(id)) $(id).style.width = `${percent}%`;
    $('overall-meter')?.setAttribute('aria-valuenow', String(percent)); certificate.hidden = !completion?.eligible;
  }
  function resetTracking() {previous = null; previousWall = 0;}
  function addRange(start, end) {
    if (end <= start) return;
    const lesson = lessons[current].id, last = pending[pending.length - 1];
    if (last && last.lesson === lesson && start <= last.range[1] && end - last.range[0] < 29) last.range[1] = end;
    else pending.push({lesson, range: [start, end]});
  }
  async function save(acknowledge = false) {
    if (acknowledge && mode === 'lesson') {
      const lessonId = lessons[current].id;
      practiceQueued.add(lessonId);
      progress[lessonId] = {...(progress[lessonId] || {}), practice: true};
    }
    if (saving) {saveAgain = true; return false;}
    saving = true;
    let success = true, inFlight = [];
    try {
      const ids = [...new Set([...pending.map(item => item.lesson), ...practiceQueued, ...(mode === 'lesson' ? [lessons[current].id] : [])])];
      for (const id of ids) {
        const entries = pending.filter(item => item.lesson === id).slice(0, 2).map(item => ({lesson: item.lesson, range: [...item.range]}));
        const originalEntries = pending.filter(item => item.lesson === id).slice(0, 2);
        pending = pending.filter(item => !originalEntries.includes(item));
        inFlight = entries;
        const record = progress[id] || {};
        const lesson = lessons.find(item => item.id === id);
        if (!lesson?.videoAvailable) continue;
        const active = mode === 'lesson' && lessons[current].id === id;
        const position = active ? Math.min(Number(lesson.duration), Number(video.currentTime) || 0) : Math.min(Number(lesson.duration), Number(record.position) || 0);
        const practiceSent = Boolean(record.practice) || practiceQueued.has(id);
        const response = await fetch(`${config.restUrl}courses/${encodeURIComponent(slug)}/lessons/${encodeURIComponent(id)}/progress`, {
          method: 'POST', credentials: 'same-origin', cache: 'no-store', keepalive: true,
          headers: {'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce},
          body: JSON.stringify({ranges: entries.map(item => item.range), position, practice: practiceSent})
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Could not save progress.');
        progress[id] = result.lesson; completion = result.completion;inFlight = [];
        if (practiceSent) practiceQueued.delete(id);
        else if (practiceQueued.has(id)) progress[id].practice = true;
        // New playback ranges remain queued while this snapshot is being saved.
      }
      updateCompletion(); updateOutline();
    } catch (error) {
      pending.unshift(...inFlight);success = false; status(`${error.message} Progress will retry. Reload if your session expired.`);
    } finally {
      saving = false;
      if (saveAgain || (success && pending.length)) {saveAgain = false; setTimeout(() => save(), 50);}
    }
    return success;
  }
  function syncPlayback() {
    const playing = !video.paused && !video.ended;
    $('toggle-play')?.setAttribute('aria-label', playing ? 'Pause video' : 'Play video');
    $('play-icon')?.setAttribute('d', playing ? 'M7 5h4v14H7zm6 0h4v14h-4z' : 'M8 4.6a1 1 0 0 1 1.52-.85l10 7.4a1 1 0 0 1 0 1.7l-10 7.4A1 1 0 0 1 8 19.4Z');
    $('large-play').hidden = playing || missing;
  }
  function updatePlayback() {
    setText('elapsed', time(video.currentTime));setText('duration', time(video.duration));
    const percent = Number.isFinite(video.duration) && video.duration ? video.currentTime / video.duration * 100 : 0;
    $('watch-fill').style.width = `${percent}%`; $('watch-meter')?.setAttribute('aria-valuenow', String(Math.round(percent)));
    const now = performance.now(), at = video.currentTime;
    if (mode === 'lesson' && !video.paused && !video.seeking && previous !== null && at > previous && at - previous <= 3 && at - previous <= (now - previousWall) / 1000 * Math.max(1, video.playbackRate) + 0.75) addRange(previous, at);
    if (!video.seeking) {previous = at; previousWall = now;}
  }
  function openVideo(url, nextMode, position = 0) {
    mode = nextMode; resetTracking(); video.pause(); video.removeAttribute('src'); video.load();
    missing = !url; restorePosition = position; video.hidden = missing; $('video-missing').hidden = !missing;
    $('video-stage').classList.toggle('is-missing-video', missing); $('toggle-play').disabled = missing;
    if (url) {video.src = url; video.playbackRate = 1; video.load();}
    updatePlayback();syncPlayback();
  }
  function select(index, requestedVideo = null) {
    if (index < 0 || index >= lessons.length) return;
    save(); current = index;const lesson = lessons[index], day = String(index + 1).padStart(2, '0');
    const choices = lessonVideos(lesson), availableIndex = choices.findIndex(item => item.available && item.url);
    const videoIndex = requestedVideo === null ? (availableIndex >= 0 ? availableIndex : 0) : requestedVideo;
    const activeVideo = choices[videoIndex];
    document.querySelector('.learn')?.classList.remove('is-extra-video');
    $('lesson-practice').closest('.learn-practice').hidden = false;
    $('complete-and-next').closest('.learn-complete').hidden = false;
    $('previous-day').closest('.learn-next-link').hidden = false;
    $('week-tag').hidden = false;
    document.querySelectorAll('.learn-extra-btn').forEach(button => button.setAttribute('aria-current', 'false'));
    setText('day-number', `LESSON ${String(lesson.moduleLessonIndex + 1).padStart(2, '0')} / ${course.modules[lesson.week].lessons.length}`);setText('week-tag', `MODULE ${lesson.week + 1}`);
    setText('challenge-heading-label', slug === 'power-dressing' ? `Lesson ${String(lesson.moduleLessonIndex + 1).padStart(2, '0')} · Styling practice` : `Day ${day} · Today's challenge`);setText('lesson-title', lesson.title.replace(/^Day\s*\d+\s*[·:–-]\s*/i, ''));
    setText('lesson-summary', lesson.summary || (slug === 'power-dressing' ? `Module ${lesson.week + 1} · Lesson ${lesson.moduleLessonIndex + 1}` : 'Your next practice lesson.'));setText('lesson-practice', lesson.practice || (slug === 'power-dressing' ? 'Watch this lesson, then try one idea with your own wardrobe.' : 'Watch this lesson, then apply what you have learned.'));
    setText('worksheet-link-label', lesson.pdfUrl ? `View lesson ${day} worksheet` : 'Worksheet unavailable');
    $('open-worksheet').hidden = !lesson.pdfUrl;$('open-worksheet').disabled = !lesson.pdfUrl;$('watch-example').hidden = !lesson.solutionUrl;
    if (slug === 'power-dressing') {
      const missingCopy = $('video-missing').querySelector('p'); if (missingCopy) missingCopy.textContent = 'This lesson’s recording is not available yet.';
      $('previous-day').textContent = '← Previous lesson';
    }
    if ($('watch-example').querySelector('span:nth-child(2)')) $('watch-example').querySelector('span:nth-child(2)').textContent = 'Watch example video';
    $('previous-day').disabled = index === 0;if ($('skip-video-next')) $('skip-video-next').hidden = index === lessons.length - 1;
    setText('next-hint', index === lessons.length - 1 ? 'Final lesson' : `Up next: lesson ${index + 2}`);
    const skipLabel = $('skip-video-next')?.querySelector('span'); if (skipLabel) skipLabel.textContent = 'Next lesson (keep this one incomplete)';
    document.querySelectorAll('.learn-video-version').forEach(button => button.setAttribute('aria-current', 'false'));
    document.querySelectorAll(`.learn-video-version[data-lesson-index="${index}"]`).forEach(button => {if (Number(button.dataset.videoIndex) === videoIndex) button.setAttribute('aria-current', 'true');});
    const caption = $('lesson-video-caption'); if (caption) {caption.textContent = activeVideo ? `Now playing · ${activeVideo.title}` : '';caption.hidden = !activeVideo;}
    openVideo(activeVideo?.available && activeVideo.url ? activeVideo.url : (!choices.length && lesson.videoAvailable ? lesson.videoUrl : ''), 'lesson', progress[lesson.id]?.position || 0);
    updateCompletion();updateOutline();
    const url = new URL(location.href);url.searchParams.set('course', slug);url.searchParams.set('lesson', String(index + 1));history.replaceState(null, '', url);
  }
  function extra(resource, button) {
    save();document.querySelector('.learn')?.classList.add('is-extra-video');
    document.querySelectorAll('.learn-extra-btn').forEach(item => item.setAttribute('aria-current', String(item === button)));
    setText('day-number', 'COURSE RESOURCE');setText('lesson-title', resource.title);setText('lesson-summary', resource.note || 'Additional course recording');$('lesson-video-caption').hidden = true;
    $('week-tag').hidden = true;$('lesson-practice').closest('.learn-practice').hidden = true;$('complete-and-next').closest('.learn-complete').hidden = true;
    $('open-worksheet').hidden = true;$('watch-example').hidden = true;if ($('skip-video-next')) $('skip-video-next').hidden = false;
    openVideo(resource.url, 'extra');updateCompletion();updateOutline();
  }
  const outline = $('outline-list');outline.replaceChildren();
  const intro = (course.resources || []).find(item => item.type === 'video' && /introduction/i.test(item.title));
  const closing = (course.resources || []).find(item => item.type === 'video' && /closing|end/i.test(item.title));
  function resourceButton(resource, label) {
    const group = node('div', 'learn-week-block learn-extra-block'), head = node('div', 'learn-week-name', label), button = node('button', 'learn-extra-btn', resource.title);
    button.type = 'button';button.addEventListener('click', () => extra(resource, button));group.append(head, button);outline.append(group);return button;
  }
  let introButton = null;if (intro) introButton = resourceButton(intro, 'START HERE');
  (course.modules || []).forEach((module, week) => {
    const group = node('div', 'learn-week-block');group.append(node('div', 'learn-week-name', module.title));
    lessons.forEach((lesson, index) => {
      if (lesson.week !== week) return;
      const button = node('button', 'learn-day-btn');button.type = 'button';button.dataset.day = String(index);
      button.append(node('span', 'learn-day-bullet', String(index + 1).padStart(2, '0')), node('span', 'learn-day-title', lesson.title.replace(/^Day\s*\d+\s*[·:–-]\s*/i, '')), node('span', 'learn-day-lock'));
      button.addEventListener('click', () => select(index));
      const lessonGroup = node('div', 'learn-lesson-group'); lessonGroup.append(button);
      if (slug === 'power-dressing') {
        const choices = lessonVideos(lesson);
        if (choices.length) {
          const versions = node('div', 'learn-lesson-videos');
          choices.forEach((choice, videoIndex) => {
            const label = `${choice.title}${choice.available && choice.url ? '' : ' · not uploaded'}`;
            const option = node('button', 'learn-video-version', label); option.type = 'button'; option.dataset.lessonIndex = String(index); option.dataset.videoIndex = String(videoIndex);
            option.disabled = !choice.available || !choice.url; option.addEventListener('click', () => select(index, videoIndex)); versions.append(option);
          });
          lessonGroup.append(versions);
        }
      }
      group.append(lessonGroup);
    });outline.append(group);
  });
  if (closing) resourceButton(closing, 'AFTER YOUR LESSONS');
  const resources = (course.resources || []).filter(item => item.type !== 'video');
  if (resources.length) {const group = node('div', 'learn-week-block');group.append(node('div', 'learn-week-name', 'STUDY RESOURCES'));for (const item of resources) {const link = node('a', 'sr-lms-resource-link', item.title + (item.available ? '' : ' · unavailable'));if (item.url) {link.href = item.url;link.addEventListener('click', event => {event.preventDefault();setText('worksheet-title', item.title);setText('worksheet-tag', 'STUDY RESOURCE');$('worksheet-frame').src = item.url;$('open-worksheet-tab').href = item.url;$('worksheet-error').hidden = true;$('worksheet-dialog').showModal();});} else link.setAttribute('aria-disabled', 'true');group.append(link);}outline.append(group);}
  const toggle = () => {if (missing) return;if (video.paused) video.play().catch(() => status('Press play to start the recording.'));else video.pause();};
  $('toggle-play').addEventListener('click', toggle);$('large-play').addEventListener('click', toggle);video.addEventListener('click', toggle);
  video.addEventListener('keydown', event => {if (event.code === 'Space' && !event.repeat) {event.preventDefault();toggle();}});
  video.addEventListener('timeupdate', updatePlayback);video.addEventListener('play', () => {resetTracking();syncPlayback();});
  video.addEventListener('pause', () => {syncPlayback();save();resetTracking();});video.addEventListener('ended', () => {save();syncPlayback();});
  video.addEventListener('seeking', resetTracking);video.addEventListener('seeked', resetTracking);
  video.addEventListener('loadedmetadata', () => {if (restorePosition > 0 && restorePosition < video.duration - 1) video.currentTime = restorePosition;restorePosition = 0;updatePlayback();});
  video.addEventListener('error', () => {if (!video.getAttribute('src')) return;missing = true;video.hidden = true;$('video-missing').hidden = false;status('This recording is unavailable. Contact the academy if you expect access.');updateCompletion();syncPlayback();});
  $('volume-toggle').addEventListener('click', () => {video.muted = !video.muted;$('volume-toggle').setAttribute('aria-label', video.muted ? 'Unmute video' : 'Mute video');});
  $('full-screen').addEventListener('click', async () => {try {if (document.fullscreenElement) await document.exitFullscreen();else if (videoStage?.requestFullscreen) await videoStage.requestFullscreen();else video.webkitEnterFullscreen?.();} catch (_) {status('Full screen is unavailable in this browser.');}});
  practiceInput.addEventListener('change', () => {if (practiceInput.checked) save(true);else practiceInput.checked = Boolean(progress[lessons[current].id]?.practice);});
  $('complete-and-next').addEventListener('click', async () => {const index = current;if (await save()) {if (progress[lessons[index].id]?.complete && index < lessons.length - 1) select(index + 1);else updateCompletion();}});
  $('previous-day').addEventListener('click', () => select(current - 1));$('skip-video-next')?.addEventListener('click', () => select(mode === 'extra' ? 0 : current + 1));
  $('watch-example').addEventListener('click', () => {if (mode === 'example') {select(current);return;}save();openVideo(lessons[current].solutionUrl, 'example');status('Example recording — return to your main lesson to complete the day.');$('complete-and-next').disabled = true;const label = $('watch-example').querySelector('span:nth-child(2)');if (label) label.textContent = 'Return to main lesson';});
  $('open-worksheet').addEventListener('click', () => {const lesson = lessons[current];if (!lesson.pdfUrl) return;setText('worksheet-title', lesson.title);setText('worksheet-tag', `WORKSHEET · LESSON ${current + 1}`);$('worksheet-frame').src = lesson.pdfUrl;$('open-worksheet-tab').href = lesson.pdfUrl;$('worksheet-error').hidden = true;$('worksheet-dialog').showModal();});
  const closeWorksheet = () => {$('worksheet-dialog').close();$('worksheet-frame').removeAttribute('src');};$('close-worksheet').addEventListener('click', closeWorksheet);$('worksheet-dialog').addEventListener('click', event => {if (event.target === $('worksheet-dialog')) closeWorksheet();});
  window.addEventListener('pagehide', () => save());document.addEventListener('visibilitychange', () => {if (document.hidden) save();});
  saveTimer = setInterval(() => {if (pending.length || (mode === 'lesson' && !video.paused)) save();}, 8000);
  setText('learn-course-name', course.title);setText('learn-course-mark', course.title.charAt(0));setText('learn-course-label', course.title.toUpperCase());
  const requested = Number(params.get('lesson'));select(Number.isInteger(requested) && requested >= 1 && requested <= lessons.length ? requested - 1 : 0);
  if (!params.has('lesson') && intro && introButton) extra(intro, introButton);
})();
