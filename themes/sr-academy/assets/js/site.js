// Arrow labels use a vector icon to stay sharp and vertically centered in all browsers.
function setCtaArrowLabel(target, label) {
  if (!target) return;
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('class', 'cta-arrow-icon');
  svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('aria-hidden', 'true');
  svg.setAttribute('focusable', 'false');
  const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  path.setAttribute('d', 'M4 12h16m-7-7 7 7-7 7');
  svg.append(path);
  target.replaceChildren(document.createTextNode(label), svg);
}

// Never leave broken-image icons visible. Any image with data-fallback will
// automatically swap to the local fallback asset if its source cannot load.
document.querySelectorAll('img[data-fallback]').forEach((image) => {
  image.addEventListener('error', () => {
    const fallback = image.dataset.fallback;
    if (fallback && image.getAttribute('src') !== fallback) {
      image.src = fallback;
    }
  }, { once: true });
});

// Guest appearances carousel: arrows, dots, keyboard-friendly native scrolling.
const appearanceCarousel = document.querySelector('.appearance-carousel');
const appearancePrev = document.querySelector('.carousel-prev');
const appearanceNext = document.querySelector('.carousel-next');
const appearanceDots = Array.from(document.querySelectorAll('.carousel-dot'));

if (appearanceCarousel && appearancePrev && appearanceNext && appearanceDots.length) {
  const maxScroll = () => Math.max(0, appearanceCarousel.scrollWidth - appearanceCarousel.clientWidth);

  const setDot = (index) => {
    const safeIndex = Math.max(0, Math.min(index, appearanceDots.length - 1));
    appearanceDots.forEach((dot, i) => dot.classList.toggle('active', i === safeIndex));
  };

  const updateCarouselState = () => {
    const maximum = maxScroll();
    const progress = maximum > 0 ? appearanceCarousel.scrollLeft / maximum : 0;
    const dotIndex = Math.round(progress * (appearanceDots.length - 1));
    setDot(dotIndex);
    appearancePrev.disabled = appearanceCarousel.scrollLeft <= 2;
    appearanceNext.disabled = appearanceCarousel.scrollLeft >= maximum - 2;
  };

  const scrollByCard = (direction) => {
    const card = appearanceCarousel.querySelector('.appearance-card');
    if (!card) return;
    const gap = parseFloat(getComputedStyle(appearanceCarousel).gap) || 0;
    appearanceCarousel.scrollBy({
      left: direction * (card.getBoundingClientRect().width + gap),
      behavior: 'smooth'
    });
  };

  appearancePrev.addEventListener('click', () => scrollByCard(-1));
  appearanceNext.addEventListener('click', () => scrollByCard(1));

  appearanceDots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
      const target = maxScroll() * (index / (appearanceDots.length - 1));
      appearanceCarousel.scrollTo({ left: target, behavior: 'smooth' });
      setDot(index);
    });
  });

  appearanceCarousel.addEventListener('scroll', () => {
    window.requestAnimationFrame(updateCarouselState);
  }, { passive: true });

  window.addEventListener('resize', updateCarouselState);
  updateCarouselState();
}

// Flagship service cards carousel: keep the desktop three-card layout while
// making the fourth card reachable with the same arrows and position dots.
const servicesCarousel = document.querySelector('#flagship-services');
const servicesControls = document.querySelector('.services-carousel-controls');
const servicesPrev = document.querySelector('.services-carousel-prev');
const servicesNext = document.querySelector('.services-carousel-next');
const servicesDotsRoot = document.querySelector('.services-carousel-dots');

if (servicesCarousel && servicesControls && servicesPrev && servicesNext && servicesDotsRoot) {
  const cards = () => Array.from(servicesCarousel.querySelectorAll('.service-card'));
  const maxScroll = () => Math.max(0, servicesCarousel.scrollWidth - servicesCarousel.clientWidth);
  const scrollStep = () => {
    const card = cards()[0];
    if (!card) return servicesCarousel.clientWidth;
    const gap = parseFloat(getComputedStyle(servicesCarousel).gap) || 0;
    return card.getBoundingClientRect().width + gap;
  };
  const stopCount = () => Math.round(maxScroll() / Math.max(1, scrollStep())) + 1;
  const setActiveDot = (index) => {
    Array.from(servicesDotsRoot.children).forEach((dot, i) => {
      dot.setAttribute('aria-current', String(i === index));
    });
  };
  const updateServicesCarousel = () => {
    const maximum = maxScroll();
    const dots = Array.from(servicesDotsRoot.children);
    const active = maximum > 0 && dots.length > 1
      ? Math.round((servicesCarousel.scrollLeft / maximum) * (dots.length - 1))
      : 0;
    setActiveDot(active);
    servicesPrev.disabled = servicesCarousel.scrollLeft <= 10;
    servicesNext.disabled = servicesCarousel.scrollLeft >= maximum - 10;
  };
  const buildServicesDots = () => {
    const count = stopCount();
    servicesDotsRoot.replaceChildren();
    for (let index = 0; index < count; index += 1) {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'services-carousel-dot';
      dot.setAttribute('aria-label', `Show service card group ${index + 1} of ${count}`);
      dot.setAttribute('aria-current', String(index === 0));
      dot.addEventListener('click', () => {
        const maximum = maxScroll();
        servicesCarousel.scrollTo({
          left: count > 1 ? maximum * (index / (count - 1)) : 0,
          behavior: 'smooth'
        });
        setActiveDot(index);
      });
      servicesDotsRoot.appendChild(dot);
    }
    servicesControls.hidden = count <= 1;
    servicesDotsRoot.hidden = count <= 1;
    updateServicesCarousel();
  };
  const scrollServices = (direction) => {
    servicesCarousel.scrollBy({ left: direction * scrollStep(), behavior: 'smooth' });
  };

  servicesPrev.addEventListener('click', () => scrollServices(-1));
  servicesNext.addEventListener('click', () => scrollServices(1));
  servicesCarousel.addEventListener('scroll', () => {
    window.requestAnimationFrame(updateServicesCarousel);
  }, { passive: true });
  window.addEventListener('resize', buildServicesDots);
  buildServicesDots();
}


// Services page 1:1 call cards carousel. Kept scoped to this page.
const callsCarousel = document.querySelector('body.services-page #calls-card-carousel');
const callsCarouselPrev = document.querySelector('body.services-page .calls-carousel-prev');
const callsCarouselNext = document.querySelector('body.services-page .calls-carousel-next');

if (callsCarousel && callsCarouselPrev && callsCarouselNext) {
  const callCardStep = () => {
    const card = callsCarousel.querySelector('.call-card');
    if (!card) return callsCarousel.clientWidth;
    const gap = parseFloat(getComputedStyle(callsCarousel).gap) || 0;
    return card.getBoundingClientRect().width + gap;
  };
  const updateCallCarouselButtons = () => {
    const maximum = Math.max(0, callsCarousel.scrollWidth - callsCarousel.clientWidth);
    callsCarouselPrev.disabled = callsCarousel.scrollLeft <= 4;
    callsCarouselNext.disabled = callsCarousel.scrollLeft >= maximum - 4;
  };

  callsCarouselPrev.addEventListener('click', () => {
    callsCarousel.scrollBy({ left: -callCardStep(), behavior: 'smooth' });
  });
  callsCarouselNext.addEventListener('click', () => {
    callsCarousel.scrollBy({ left: callCardStep(), behavior: 'smooth' });
  });
  callsCarousel.addEventListener('scroll', () => {
    window.requestAnimationFrame(updateCallCarouselButtons);
  }, { passive: true });
  window.addEventListener('resize', updateCallCarouselButtons);
  updateCallCarouselButtons();
}

// Local presentation carousels for the supplied package slides.
document.querySelectorAll('[data-confidence-carousel]').forEach((carousel) => {
  const image = carousel.querySelector('[data-confidence-slide]');
  const previous = carousel.querySelector('[data-confidence-prev]');
  const next = carousel.querySelector('[data-confidence-next]');
  const fullscreen = carousel.querySelector('[data-confidence-fullscreen]');
  const directory = carousel.dataset.slideDirectory || window.SR_THEME.asset('assets/speak-with-confidence');
  const title = carousel.dataset.slideTitle || 'Speak with Confidence';
  const slideCount = 10;
  const slides = Array.from({ length: slideCount }, (_, index) => ({
    src: `${directory}/slide-${String(index + 1).padStart(2, '0')}.png`,
    alt: `${title}: slide ${index + 1} of ${slideCount}.`
  }));
  let currentIndex = 0;
  let touchStart = null;

  const showSlide = (index) => {
    currentIndex = Math.max(0, Math.min(index, slides.length - 1));
    image.src = slides[currentIndex].src;
    image.alt = slides[currentIndex].alt;
    previous.disabled = currentIndex === 0;
    next.disabled = currentIndex === slides.length - 1;
    [currentIndex - 1, currentIndex + 1].forEach((neighbor) => {
      if (neighbor >= 0 && neighbor < slides.length) {
        const preload = new Image();
        preload.src = slides[neighbor].src;
      }
    });
  };

  previous.addEventListener('click', () => showSlide(currentIndex - 1));
  next.addEventListener('click', () => showSlide(currentIndex + 1));
  const updateFullscreenLabel = () => {
    const isFullscreen = document.fullscreenElement === carousel;
    const label = isFullscreen ? 'Exit fullscreen' : 'View slide fullscreen';
    fullscreen.setAttribute('aria-label', label);
    fullscreen.title = label;
  };
  fullscreen.addEventListener('click', async () => {
    try {
      if (document.fullscreenElement === carousel) await document.exitFullscreen();
      else await carousel.requestFullscreen();
    } catch (_) {
      // Fullscreen is optional when the browser does not allow it.
    }
  });
  document.addEventListener('fullscreenchange', updateFullscreenLabel);
  carousel.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      showSlide(currentIndex - 1);
    } else if (event.key === 'ArrowRight') {
      event.preventDefault();
      showSlide(currentIndex + 1);
    }
  });
  carousel.addEventListener('touchstart', (event) => {
    touchStart = event.changedTouches[0]?.clientX ?? null;
  }, { passive: true });
  carousel.addEventListener('touchend', (event) => {
    if (touchStart === null) return;
    const delta = (event.changedTouches[0]?.clientX ?? touchStart) - touchStart;
    touchStart = null;
    if (Math.abs(delta) >= 45) showSlide(currentIndex + (delta < 0 ? 1 : -1));
  }, { passive: true });
  showSlide(0);
  updateFullscreenLabel();
});

// Recognition marquee: pause at the exact current position while any card is hovered.
// Resume from the same point when the pointer leaves. Keyboard focus pauses it too.
const recognitionTrack = document.querySelector('.recognition-track');
const recognitionCards = document.querySelectorAll('.recognition-card');

if (recognitionTrack && recognitionCards.length) {
  const pauseRecognition = () => recognitionTrack.classList.add('is-paused');
  const resumeRecognition = () => recognitionTrack.classList.remove('is-paused');

  recognitionCards.forEach((card) => {
    card.addEventListener('mouseenter', pauseRecognition);
    card.addEventListener('mouseleave', resumeRecognition);
    card.addEventListener('focusin', pauseRecognition);
    card.addEventListener('focusout', resumeRecognition);
  });
}

// Services page catalog: 30/60-minute consultation switching, search, and filters.
const durationButtons = Array.from(document.querySelectorAll('.calls-duration-toggle [data-duration]'));
const callCards = Array.from(document.querySelectorAll('.calls-grid .call-card'));
const homeCallDurationButtons = Array.from(document.querySelectorAll('.home-page .service-toggle [data-duration]'));
let activeCallDuration = '60';
let activeServiceFilter = 'all';
let applyServiceFiltering = () => {};

homeCallDurationButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const card = button.closest('.service-card');
    const duration = button.dataset.duration;
    card?.querySelectorAll('.service-toggle [data-duration]').forEach((option) => {
      const active = option === button;
      option.classList.toggle('active', active);
      option.setAttribute('aria-pressed', String(active));
    });
    const price = card?.querySelector('.service-price');
    const subtitle = card?.querySelector('.service-subtitle');
    const ctaLabel = card?.querySelector('[data-home-booking-label]');
    if (price) price.innerHTML = duration === '30'
      ? (card.dataset.offer30 || price.dataset.price30 || price.innerHTML)
      : (card.dataset.offer60 || price.dataset.price60 || price.innerHTML);
    if (subtitle) {
      const subtitleText = duration === '30'
        ? (card.dataset.subtitle30 ?? '')
        : (card.dataset.subtitle60 || (button.closest('.service-toggle')?.children[1]?.textContent.includes('Makeover') ? 'Signature Image Makeover' : 'Comprehensive Deep Dive'));
      subtitle.textContent = subtitleText;
      subtitle.hidden = subtitleText.trim() === '';
    }
    card?.querySelectorAll('[data-duration-copy]').forEach((copy) => {
      copy.hidden = copy.dataset.durationCopy !== duration;
    });
    card?.querySelectorAll('[data-duration-list]').forEach((list) => {
      list.hidden = list.dataset.durationList !== duration;
    });
    if (ctaLabel) ctaLabel.textContent = `Book ${duration} Mins Call`;
    const durationBadge = card?.querySelector('.home-service-duration');
    if (durationBadge && card?.classList.contains('home-consultation-card')) durationBadge.textContent = `◷  ${duration} min`;
  });
});


if (durationButtons.length && callCards.length) {
  const setDuration = (duration) => {
    activeCallDuration = duration;
    durationButtons.forEach((button) => {
      const active = button.dataset.duration === duration;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });

    callCards.forEach((card) => {
      card.querySelectorAll('[data-duration-copy]').forEach((copy) => {
        copy.hidden = copy.dataset.durationCopy !== duration;
      });

      card.querySelectorAll('[data-duration-label]').forEach((label) => {
        label.textContent = `${duration}min`;
      });

      card.querySelectorAll('[data-duration-list]').forEach((list) => {
        list.hidden = list.dataset.durationList !== duration;
      });

      card.querySelectorAll('[data-duration-ideal]').forEach((ideal) => {
        ideal.hidden = ideal.dataset.durationIdeal !== duration;
      });

      const price = card.querySelector('.call-price');
      const durationPrice = price?.dataset[`price${duration}`];
      if (price && durationPrice) price.textContent = durationPrice;
    });

    applyServiceFiltering();
  };

  durationButtons.forEach((button) => {
    button.addEventListener('click', () => setDuration(button.dataset.duration));
  });

  setDuration('60');
}

const servicesFilterTabs = Array.from(document.querySelectorAll('.services-filter-tab'));
const servicesSearchInput = document.querySelector('#services-search-input');
const servicesSearchEmpty = document.querySelector('#services-search-empty');
const callsSection = document.querySelector('[data-service-section="calls"]');
const packagesSection = document.querySelector('[data-service-section="packages"]');
const coursesSection = document.querySelector('[data-service-section="courses"]');
const packageCards = Array.from(document.querySelectorAll('.packages-grid .package-card'));
const courseCards = Array.from(document.querySelectorAll('.learning-grid .learning-card'));

if (servicesFilterTabs.length && (callsSection || packagesSection || coursesSection)) {
  applyServiceFiltering = () => {
    document.body.classList.toggle('services-filter-packages', activeServiceFilter === 'packages');
    const query = (servicesSearchInput?.value || '').trim().toLowerCase();
    let visibleTotal = 0;

    let visibleCalls = 0;
    callCards.forEach((card) => {
      const categoryMatch = activeServiceFilter === 'all' || activeServiceFilter === 'calls';
      const haystack = `${card.dataset.search || ''} ${card.textContent || ''}`.toLowerCase();
      const queryMatch = !query || haystack.includes(query);
      const durationMatch = !card.dataset.availableDuration || card.dataset.availableDuration === activeCallDuration;
      const show = categoryMatch && queryMatch && durationMatch;
      card.hidden = !show;
      if (show) {
        visibleCalls += 1;
        visibleTotal += 1;
      }
    });
    if (callsSection) callsSection.hidden = visibleCalls === 0;

    let visiblePackages = 0;
    packageCards.forEach((card) => {
      const categoryMatch = activeServiceFilter === 'all' || activeServiceFilter === 'packages';
      const haystack = `${card.dataset.search || ''} ${card.textContent || ''}`.toLowerCase();
      const queryMatch = !query || haystack.includes(query);
      const show = categoryMatch && queryMatch;
      card.hidden = !show;
      if (show) {
        visiblePackages += 1;
        visibleTotal += 1;
      }
    });
    if (packagesSection) packagesSection.hidden = visiblePackages === 0;

    let visibleCourses = 0;
    courseCards.forEach((card) => {
      const categoryMatch = activeServiceFilter === 'all' || activeServiceFilter === 'courses';
      const haystack = `${card.dataset.search || ''} ${card.textContent || ''}`.toLowerCase();
      const queryMatch = !query || haystack.includes(query);
      const show = categoryMatch && queryMatch;
      card.hidden = !show;
      if (show) {
        visibleCourses += 1;
        visibleTotal += 1;
      }
    });
    if (coursesSection) coursesSection.hidden = visibleCourses === 0;

    if (servicesSearchEmpty) servicesSearchEmpty.hidden = visibleTotal !== 0;
  };

  servicesFilterTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      activeServiceFilter = tab.dataset.serviceFilter || 'all';
      servicesFilterTabs.forEach((item) => {
        const active = item === tab;
        item.classList.toggle('active', active);
        item.setAttribute('aria-selected', String(active));
      });
      applyServiceFiltering();
    });
  });

  servicesSearchInput?.addEventListener('input', applyServiceFiltering);
  applyServiceFiltering();
}

// Services FAQ accordion: keep one answer open at a time, matching the reference interaction.
const serviceFaqItems = Array.from(document.querySelectorAll('.services-faq-item'));

if (serviceFaqItems.length) {
  const setFaqItem = (item, open) => {
    const button = item.querySelector('.services-faq-question');
    const answer = item.querySelector('.services-faq-answer');
    const chevronPath = item.querySelector('.services-faq-chevron path');
    if (!button || !answer) return;

    item.classList.toggle('is-open', open);
    button.setAttribute('aria-expanded', String(open));
    answer.hidden = !open;
    if (chevronPath) {
      chevronPath.setAttribute('d', open ? 'm5.5 12 4.5-4.5 4.5 4.5' : 'm5.5 8 4.5 4.5 4.5-4.5');
    }
  };

  serviceFaqItems.forEach((item) => {
    const button = item.querySelector('.services-faq-question');
    if (!button) return;

    button.addEventListener('click', () => {
      const isOpen = button.getAttribute('aria-expanded') === 'true';
      serviceFaqItems.forEach((otherItem) => setFaqItem(otherItem, false));
      if (!isOpen) setFaqItem(item, true);
    });
  });
}

// Services booking modal: multi-step consultation / package / course reservation flow.
const bookingModal = document.querySelector('#booking-modal');

if (bookingModal) {
  const bookingDialog = bookingModal.querySelector('.booking-dialog');
  const bookingBackdrop = bookingModal.querySelector('.booking-modal-backdrop');
  const bookingClose = bookingModal.querySelector('.booking-close');
  const bookingTriggers = Array.from(document.querySelectorAll('.booking-trigger'));
  const bookingSteps = Array.from(bookingModal.querySelectorAll('[data-booking-step]'));
  const bookingStepCount = bookingModal.querySelector('#booking-step-count');
  const offeringSelect = bookingModal.querySelector('#booking-offering');
  const durationWrap = bookingModal.querySelector('#booking-duration-wrap');
  const durationOptions = Array.from(bookingModal.querySelectorAll('[data-booking-duration]'));
  const scheduleBox = bookingModal.querySelector('#booking-schedule-box');
  const scheduleDateLabel = bookingModal.querySelector('#booking-date-label');
  const bookingDate = bookingModal.querySelector('#booking-date');
  const bookingTime = bookingModal.querySelector('#booking-time');
  const timeSlotStarts = Array.from(bookingTime?.options || []).map((option) => option.value);
  const bookingTotal = bookingModal.querySelector('#booking-total');
  const bookingContinue = bookingModal.querySelector('#booking-continue');
  const bookingBack = bookingModal.querySelector('#booking-back');
  const bookingForm = bookingModal.querySelector('.booking-step-two form');
  const bookingName = bookingModal.querySelector('[name="booking_name"]');
  const bookingEmail = bookingModal.querySelector('[name="booking_email"]');
  const bookingPhone = bookingModal.querySelector('[name="booking_phone"]');
  const bookingChallenge = bookingModal.querySelector('[name="booking_challenge"]');
  const summaryTitle = bookingModal.querySelector('#booking-summary-title');
  const summaryDate = bookingModal.querySelector('#booking-summary-date');
  const summaryPrice = bookingModal.querySelector('#booking-summary-price');
  const confirmLabel = bookingModal.querySelector('.booking-cf7-form .wpcf7-submit');
  const confirmationName = bookingModal.querySelector('#booking-confirmation-name');
  const confirmationSummary = bookingModal.querySelector('#booking-confirmation-summary');
  const sendRequest = bookingModal.querySelector('#booking-send-request');
  const bookingDone = bookingModal.querySelector('#booking-done');

  const offerings = {
    discovery: {
      label: '15-Min Discovery Call',
      fullLabel: '15-Min Discovery Call (Free · Direct with Shalini)',
      type: 'discovery',
      price: 0,
      duration: 15,
      needsSchedule: true
    },
    'comm-call': {
      label: '1:1 Call: Communication & Confidence',
      fullLabel: '1:1 Communication & Confidence',
      type: 'call',
      prices: { '30': 3750, '60': 7999 },
      needsSchedule: true
    },
    'style-call': {
      label: '1:1 Call: Image Styling & Confidence',
      fullLabel: '1:1 Image Styling & Confidence',
      type: 'call',
      prices: { '30': 3750, '60': 7999 },
      needsSchedule: true
    },
    'color-call': {
      label: 'Personalised Colour Analysis Consultation',
      fullLabel: 'Personalised Colour Analysis Consultation',
      type: 'call',
      prices: { '60': 7999, '90': 13999 },
      durationLabels: { '90': 'Personalised colour analysis' },
      needsSchedule: true
    },
    'occasion-call': {
      label: 'Personalised Occasion Styling',
      fullLabel: 'Personalised Occasion Styling',
      type: 'call',
      prices: { '30': 3750, '60': 7999 },
      durationLabels: { '30': 'Occasion styling session', '60': 'Occasion styling session' },
      needsSchedule: true
    },
    'comm-package': {
      label: 'Communication Skills & Confidence Makeover',
      fullLabel: 'Communication Skills & Confidence Makeover (7 Sessions)',
      type: 'package',
      price: 34999,
      duration: '7 Sessions',
      needsSchedule: true
    },
    'style-package': {
      label: '1:1 Signature Style Transformation',
      fullLabel: '1:1 Signature Style Transformation (6 Sessions)',
      type: 'package',
      price: 24999,
      duration: '6 Sessions + tailored booklet',
      needsSchedule: true
    },
    speakathon: {
      label: '21-Day Speakathon Challenge',
      fullLabel: '21-Day Speakathon Challenge',
      type: 'course',
      price: 5999,
      duration: 'Lifetime Access',
      needsSchedule: false
    },
    'styling-course': {
      label: 'Power Dressing / Styling',
      fullLabel: 'Power Dressing / Styling',
      type: 'course',
      price: 3499,
      duration: 'Lifetime Access',
      needsSchedule: false
    },
    'dining-etiquette': {
      label: 'Dining Etiquette',
      fullLabel: 'Dining Etiquette Self-Paced Course',
      type: 'course',
      price: 3499,
      duration: 'Lifetime Access',
      needsSchedule: false
    },
    'communication-training': {label: 'Professional Communication Training', fullLabel: '5-Session Professional Communication Training', type: 'package', price: 49999, duration: '5 Sessions', needsSchedule: true},
    'complete-makeover': {label: 'Complete Image Transformation', fullLabel: '5-Session Complete Image Transformation', type: 'package', price: 70000, duration: '5 Sessions', needsSchedule: true},
    'power-bundle': {
      label: 'The Power Bundle',
      fullLabel: 'The Power Bundle',
      type: 'course',
      price: 12999,
      duration: 'Lifetime Access',
      needsSchedule: false
    }
  };

  let selectedDuration = '60';
  let lastBookingTrigger = null;
  let availabilityState = 'unchecked';
  let availabilityRequest = 0;

  const formatMoney = (amount) => amount === 0
    ? 'Free'
    : `₹${Number(amount).toLocaleString('en-IN')}`;

  const getOffering = () => offerings[offeringSelect?.value] || offerings['comm-call'];

  const getPrice = () => {
    const offering = getOffering();
    if (offering.type === 'call') return offering.prices[selectedDuration] ?? offering.prices['60'];
    return offering.price;
  };

  const setBookingStep = (step) => {
    bookingSteps.forEach((panel) => {
      panel.hidden = panel.dataset.bookingStep !== String(step);
    });
    if (bookingStepCount) bookingStepCount.textContent = `STEP ${step} OF 3`;
    if (bookingDialog) bookingDialog.scrollTop = 0;
  };

  const setDuration = (duration) => {
    selectedDuration = String(duration);
    durationOptions.forEach((button) => {
      const active = button.dataset.bookingDuration === selectedDuration;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    updateTimeSlots();
    updateBookingUI();
    if (!bookingModal.hidden && getOffering().needsSchedule) refreshAvailability();
  };

  const toMinutes = (time) => {
    const match = time.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)/i);
    if (!match) return null;
    let hours = Number(match[1]) % 12;
    if (match[3].toUpperCase() === 'PM') hours += 12;
    return hours * 60 + Number(match[2]);
  };
  const formatClock = (minutes) => {
    const hours24 = Math.floor(minutes / 60) % 24;
    const hours12 = hours24 % 12 || 12;
    const suffix = hours24 >= 12 ? 'PM' : 'AM';
    return `${String(hours12).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')} ${suffix}`;
  };
  const updateTimeSlots = () => {
    if (!bookingTime || !timeSlotStarts.length) return;
    const currentStart = bookingTime.value.split(' - ')[0];
    const duration = getOffering().type === 'call' ? Number(selectedDuration) : 60;
    const fragment = document.createDocumentFragment();
    timeSlotStarts.forEach((slot) => {
      const [start, rest = ''] = slot.split(' - ');
      const startMinutes = toMinutes(start);
      const originalEnd = rest.match(/^(\d{1,2}:\d{2}\s*(?:AM|PM))/i)?.[1];
      const end = startMinutes === null ? originalEnd : formatClock(startMinutes + duration);
      const suffix = rest.slice(originalEnd?.length || 0);
      const option = document.createElement('option');
      option.value = `${start} - ${end}${suffix}`;
      option.textContent = option.value;
      fragment.append(option);
    });
    bookingTime.replaceChildren(fragment);
    const matchingOption = Array.from(bookingTime.options).find((option) => option.value.startsWith(`${currentStart} - `));
    if (matchingOption) matchingOption.selected = true;
  };

  const refreshAvailability = async () => {
    if (!bookingDate?.value || !bookingTime || !getOffering().needsSchedule) return;
    const requestId = ++availabilityRequest;
    availabilityState = 'loading';
    const note = bookingModal.querySelector('#booking-schedule-note');
    if (note) note.textContent = 'Checking live calendar availability…';
    bookingContinue?.setAttribute('aria-busy', 'true');
    Array.from(bookingTime.options).forEach((option) => { option.disabled = true; });
    try {
      const endpoint = window.SR_THEME?.calendarAvailabilityUrl;
      if (!endpoint) throw new Error('Live calendar availability is not configured yet.');
      const url = new URL(endpoint, window.location.href);
      url.searchParams.set('date', bookingDate.value);
      url.searchParams.set('duration', String(getOffering().type === 'call' ? selectedDuration : 60));
      const response = await fetch(url, {headers: {'Accept': 'application/json'}});
      // Some Hostinger responses prepend a short server-generated variable
      // line before the REST JSON. Read the body as text and parse from the
      // first object so availability remains usable on that environment.
      const responseText = await response.text();
      const jsonStart = responseText.indexOf('{');
      if (jsonStart === -1) throw new Error('Calendar availability returned an invalid response. Please try again.');
      const payload = JSON.parse(responseText.slice(jsonStart));
      if (!response.ok || !Array.isArray(payload.slots)) throw new Error(payload.message || 'Could not check calendar availability. Please try again.');
      if (requestId !== availabilityRequest) return;
      const available = new Map(payload.slots.map((slot) => [slot.start, Boolean(slot.available)]));
      Array.from(bookingTime.options).forEach((option) => {
        const start = option.value.split(' - ')[0];
        const isAvailable = available.get(start) === true;
        option.disabled = !isAvailable;
        option.textContent = `${option.value}${isAvailable ? '' : ' · Unavailable'}`;
      });
      const selected = bookingTime.selectedOptions[0];
      if (!selected || selected.disabled) {
        const firstAvailable = Array.from(bookingTime.options).find((option) => !option.disabled);
        if (firstAvailable) firstAvailable.selected = true;
      }
      availabilityState = available.size && Array.from(available.values()).some(Boolean) ? 'ready' : 'none';
      if (note) note.textContent = availabilityState === 'ready'
        ? 'Live availability checked with Google Calendar. Paid 1:1 slots are confirmed after successful payment; discovery and package requests are confirmed separately.'
        : 'No available times for this date. Choose another date.';
    } catch (error) {
      if (requestId !== availabilityRequest) return;
      availabilityState = 'error';
      if (note) note.textContent = error.message || 'Live availability could not be checked. Please try again.';
      Array.from(bookingTime.options).forEach((option) => { option.disabled = true; });
    } finally {
      if (requestId === availabilityRequest) bookingContinue?.removeAttribute('aria-busy');
    }
  };

  const updateBookingUI = () => {
    const offering = getOffering();
    const price = getPrice();

    if (durationWrap) durationWrap.hidden = offering.type !== 'call';
    if (offering.type === 'call') {
      if (offering.prices[selectedDuration] === undefined) selectedDuration = '60';
      durationOptions.forEach((button) => {
        const duration = button.dataset.bookingDuration;
        const available = offering.prices[duration] !== undefined;
        button.hidden = !available;
        button.disabled = !available;
        const active = duration === selectedDuration;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', String(active));
        const priceLabel = button.querySelector('.booking-duration-top span:last-child');
        if (available && priceLabel) priceLabel.textContent = formatMoney(offering.prices[duration]);
        const detailLabel = button.querySelector('small');
        if (available && detailLabel && offering.durationLabels?.[duration]) detailLabel.textContent = offering.durationLabels[duration];
      });
    } else {
      durationOptions.forEach((button) => { button.hidden = false; button.disabled = false; });
    }
    if (scheduleBox) scheduleBox.hidden = !offering.needsSchedule;
    if (bookingDate) bookingDate.required = Boolean(offering.needsSchedule);
    if (bookingTime) bookingTime.required = Boolean(offering.needsSchedule);

    if (scheduleDateLabel) {
      scheduleDateLabel.textContent = offering.type === 'package'
        ? 'SELECT PREFERRED START DATE & TIME:'
        : 'SELECT PREFERRED DATE & TIME:';
    }

    if (bookingTotal) bookingTotal.textContent = formatMoney(price);

    const durationText = offering.type === 'call'
      ? `${selectedDuration}min`
      : offering.duration || '';
    const summaryLabel = durationText ? `${offering.label} (${durationText})` : offering.label;

    if (summaryTitle) summaryTitle.textContent = summaryLabel;
    if (summaryPrice) summaryPrice.textContent = formatMoney(price);

    if (confirmLabel) {
      confirmLabel.value = offering.type === 'course'
        ? `Continue Enrollment (${formatMoney(price)})`
        : offering.type === 'call'
          ? `Pay securely · ${formatMoney(price)}`
          : `Send request · ${formatMoney(price)}`;
    }
  };

  const setDefaultDate = () => {
    if (!bookingDate) return;
    const today = new Date();
    const min = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const preferred = new Date(min);
    preferred.setDate(preferred.getDate() + 2);
    const toIso = (date) => {
      const y = date.getFullYear();
      const m = String(date.getMonth() + 1).padStart(2, '0');
      const d = String(date.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    };
    bookingDate.min = toIso(min);
    if (!bookingDate.value || bookingDate.value < bookingDate.min) {
      bookingDate.value = toIso(preferred);
    }
  };

  const openBooking = (offerKey = 'comm-call') => {
    const safeOffer = offerings[offerKey] ? offerKey : 'comm-call';
    if (offeringSelect) offeringSelect.value = safeOffer;

    if (safeOffer === 'color-call') {
      selectedDuration = lastBookingTrigger?.closest('.service-card')?.querySelector('.service-toggle [data-duration].active')?.dataset.duration || '60';
    } else if (safeOffer === 'occasion-call') {
      selectedDuration = lastBookingTrigger?.closest('.service-card')?.querySelector('.service-toggle [data-duration].active')?.dataset.duration || '60';
    } else if (safeOffer === 'comm-call' || safeOffer === 'style-call') {
      if (lastBookingTrigger?.classList.contains('call-book-btn') || lastBookingTrigger?.closest('.service-card')) {
        const homeDuration = lastBookingTrigger.closest('.service-card')?.querySelector('.service-toggle [data-duration].active');
        const activeCallDuration = homeDuration || lastBookingTrigger.closest('.call-card')?.querySelector('.calls-duration-toggle [data-duration].active') || document.querySelector('.calls-duration-toggle [data-duration].active');
        selectedDuration = activeCallDuration?.dataset.duration || '60';
      } else {
        // Generic "Book a Session" buttons follow the reference and start on the 60-minute deep dive.
        selectedDuration = '60';
      }
    } else {
      selectedDuration = '60';
    }

    durationOptions.forEach((button) => {
      const active = button.dataset.bookingDuration === selectedDuration;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });

    setDefaultDate();
    updateTimeSlots();
    updateBookingUI();
    setBookingStep(1);
    bookingModal.hidden = false;
    bookingModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('booking-open');
    window.requestAnimationFrame(() => bookingClose?.focus());
    availabilityState = 'unchecked';
    if (getOffering().needsSchedule) refreshAvailability();
  };

  const closeBooking = () => {
    bookingModal.hidden = true;
    bookingModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('booking-open');
    setBookingStep(1);
    lastBookingTrigger?.focus?.();
  };

  const getReadableDateTime = () => {
    const offering = getOffering();
    if (!offering.needsSchedule) return 'Lifetime access · Start after enrollment';
    const date = bookingDate?.value || '';
    const time = bookingTime?.value || '';
    const shortTime = time.split(' - ')[0] || time;
    return date ? `${date} at ${shortTime}` : shortTime;
  };

  bookingTriggers.forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      lastBookingTrigger = trigger;
      const siteMenu = document.getElementById('sr-mobile-menu');
      const siteMenuToggle = document.querySelector('[data-sr-toggle]');
      if (siteMenu && !siteMenu.hidden) {
        siteMenu.hidden = true;
        siteMenuToggle?.setAttribute('aria-expanded', 'false');
        siteMenuToggle?.setAttribute('aria-label', 'Open navigation');
      }
      openBooking(trigger.dataset.bookingOffer || 'comm-call');
    });
  });

  durationOptions.forEach((button) => {
    button.addEventListener('click', () => setDuration(button.dataset.bookingDuration || '60'));
  });

  offeringSelect?.addEventListener('change', () => {
    updateTimeSlots();
    updateBookingUI();
    availabilityState = 'unchecked';
    if (getOffering().needsSchedule) refreshAvailability();
  });

  bookingDate?.addEventListener('change', () => { availabilityState = 'unchecked'; refreshAvailability(); });

  bookingContinue?.addEventListener('click', () => {
    const offering = getOffering();
    if (offering.needsSchedule && bookingDate && !bookingDate.value) {
      bookingDate.focus();
      bookingDate.showPicker?.();
      return;
    }
    if (offering.needsSchedule && availabilityState !== 'ready') {
      if (availabilityState === 'unchecked' || availabilityState === 'error') refreshAvailability();
      bookingModal.querySelector('#booking-schedule-note')?.scrollIntoView({block: 'nearest'});
      return;
    }
    if (offering.needsSchedule && bookingTime?.selectedOptions[0]?.disabled) {
      bookingModal.querySelector('#booking-schedule-note')?.scrollIntoView({block: 'nearest'});
      return;
    }
    updateBookingUI();
    if (offering.type === 'course') {
      const courseSlug = {speakathon:'speakathon', 'styling-course':'power-dressing', 'dining-etiquette':'dining-etiquette', 'power-bundle':'power-bundle'}[offeringSelect.value];
      if (courseSlug) { window.location.href = window.SR_THEME.url('lms-course.html?course='+encodeURIComponent(courseSlug)); return; }
    }
    if (summaryDate) summaryDate.textContent = getReadableDateTime();
    setBookingStep(2);
    bookingName?.focus();
  });

  bookingBack?.addEventListener('click', () => {
    setBookingStep(1);
    offeringSelect?.focus();
  });

  bookingForm?.addEventListener('submit', (event) => {
    const offering = getOffering();
    if (offering.type === 'call') {
      event.preventDefault();
      event.stopImmediatePropagation();
      if (bookingForm.dataset.checkoutBusy === 'true') return;
      const config = window.SR_THEME?.consultationCheckout;
      const status = bookingForm.querySelector('.booking-payment-status') || (() => {
        const message = document.createElement('p');
        message.className = 'booking-payment-status';
        message.setAttribute('role', 'status');
        message.setAttribute('aria-live', 'polite');
        bookingForm.append(message);
        return message;
      })();
      if (!config?.enabled) {
        status.textContent = 'Secure online booking is unavailable right now. Please contact the Academy team.';
        return;
      }
      if (!window.Razorpay) {
        status.textContent = 'Secure checkout could not load. Please reload and try again.';
        return;
      }
      if (!bookingForm.reportValidity()) return;
      bookingForm.dataset.checkoutBusy = 'true';
      if (confirmLabel) { confirmLabel.disabled = true; confirmLabel.value = 'Preparing secure checkout…'; }
      status.textContent = 'Checking the selected slot and preparing secure checkout…';
      const bookingPayload = {
        offer: offeringSelect?.value || '',
        duration: Number(selectedDuration),
        date: bookingDate?.value || '',
        time: bookingTime?.value || '',
        name: bookingName?.value.trim() || '',
        email: bookingEmail?.value.trim() || '',
        phone: bookingPhone?.value.trim() || '',
        challenge: bookingChallenge?.value.trim() || ''
      };
      const post = async (route, data) => {
        const response = await fetch(config.api + route, {
          method: 'POST', credentials: 'same-origin',
          headers: {'Content-Type':'application/json'}, body: JSON.stringify(data)
        });
        const text = await response.text();
        let result;
        try { result = JSON.parse(text); } catch (_) { throw new Error('The booking service returned an unexpected response. Please retry or contact the Academy.'); }
        if (!response.ok) throw new Error(result.message || 'Checkout could not continue. Please try again.');
        return result;
      };
      post('order', bookingPayload).then((order) => {
        status.textContent = order.test ? 'Test checkout · no live charge will be made.' : 'Complete your payment in Razorpay to confirm this booking.';
        let paymentReceived = false;
        const checkout = new window.Razorpay({
          key: order.key || config.key,
          order_id: order.order_id,
          amount: order.amount,
          currency: order.currency,
          name: order.name,
          description: order.description,
          prefill: {name: bookingPayload.name, email: bookingPayload.email, contact: bookingPayload.phone},
          theme: {color:'#174b38'},
          handler: async (payment) => {
            paymentReceived = true;
            if (confirmLabel) confirmLabel.value = 'Verifying payment…';
            status.textContent = 'Payment received. Confirming the booking and creating your Zoom meeting…';
            try {
              const confirmed = await post('verify', payment);
              if (confirmationName) confirmationName.textContent = confirmed.name || bookingPayload.name;
              if (confirmationSummary) confirmationSummary.textContent = `${confirmed.service || offering.fullLabel} · ${confirmed.date_label || getReadableDateTime()} · confirmed`;
              const kicker = bookingModal.querySelector('.booking-confirmation-kicker');
              if (kicker) kicker.textContent = 'BOOKING CONFIRMED';
              const finalMsg = bookingModal.querySelector('.booking-step-three > p:not(.booking-confirmation-kicker)');
              if (finalMsg) finalMsg.textContent = confirmed.calendar_invite_sent
                ? `Your payment and booking are confirmed. Google Calendar sent an invitation to ${bookingPayload.email}; it includes your Zoom link.`
                : 'Your payment is confirmed. Please contact the Academy if you do not receive your calendar invitation.';
              if (sendRequest) sendRequest.hidden = true;
              const copyBtn = bookingModal.querySelector('#booking-copy-request');
              if (copyBtn) copyBtn.hidden = true;
              setBookingStep(3);
            } catch (error) {
              status.textContent = `${error.message} Do not pay again if your payment was successful; contact the Academy with your Razorpay payment reference.`;
              if (confirmLabel) confirmLabel.value = 'Payment needs assistance';
            }
          },
          modal: {ondismiss: () => {
            if (paymentReceived) return;
            bookingForm.dataset.checkoutBusy = 'false';
            if (confirmLabel) { confirmLabel.disabled = false; confirmLabel.value = `Pay securely · ${formatMoney(getPrice())}`; }
            status.textContent = 'Checkout was closed. Your booking is not confirmed yet.';
          }}
        });
        checkout.on('payment.failed', () => {
          bookingForm.dataset.checkoutBusy = 'false';
          if (confirmLabel) { confirmLabel.disabled = false; confirmLabel.value = `Pay securely · ${formatMoney(getPrice())}`; }
          status.textContent = 'Payment was unsuccessful. Check the payment status before trying again.';
        });
        checkout.open();
      }).catch((error) => {
        bookingForm.dataset.checkoutBusy = 'false';
        if (confirmLabel) { confirmLabel.disabled = false; confirmLabel.value = `Pay securely · ${formatMoney(getPrice())}`; }
        status.textContent = error.message;
      });
      return;
    }
    const offeringDetails = getOffering();
    const hidden = (name, value) => { const field = bookingForm.querySelector(`[name=\"${name}\"]`); if (field) field.value = value; };
    hidden('booking_service', offeringDetails.fullLabel);
    hidden('booking_duration', offeringDetails.type === 'call' ? `${selectedDuration} minutes` : String(offeringDetails.duration || ''));
    hidden('booking_date', bookingDate?.value || 'To arrange');
    hidden('booking_time', bookingTime?.value || 'To arrange');
    hidden('booking_price', formatMoney(getPrice()));
    if (confirmLabel) confirmLabel.value = 'Sending request…';
  }, true);

  bookingModal.addEventListener('wpcf7mailsent', (event) => {
    if (event.target !== bookingForm) return;
    if (confirmationName) confirmationName.textContent = bookingName?.value.trim() || 'there';
    const scheduled = getOffering().needsSchedule;
    if (confirmationSummary) confirmationSummary.textContent = `${getOffering().fullLabel} · ${getReadableDateTime()} · ${scheduled ? 'confirmed' : 'request received'}`;
    const kicker = bookingModal.querySelector('.booking-confirmation-kicker');
    if (kicker) kicker.textContent = scheduled ? 'BOOKING CONFIRMED' : 'REQUEST RECEIVED';
    const finalMsg = bookingModal.querySelector('.booking-step-three > p:not(.booking-confirmation-kicker)');
    if (finalMsg) finalMsg.textContent = scheduled
      ? 'Your Zoom meeting is scheduled. We have emailed your joining link and sent calendar invitations to you and the Academy team.'
      : 'We have emailed you a confirmation. The Academy team will contact you with the next steps.';
    if (sendRequest) sendRequest.hidden = true;
    const copyBtn = bookingModal.querySelector('#booking-copy-request');
    if (copyBtn) copyBtn.hidden = true;
    setBookingStep(3);
  });

  bookingBackdrop?.addEventListener('click', closeBooking);
  bookingClose?.addEventListener('click', closeBooking);
  bookingDone?.addEventListener('click', closeBooking);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !bookingModal.hidden) closeBooking();
  });

  // Keep tab focus inside the open modal.
  bookingModal.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab' || bookingModal.hidden) return;
    const focusable = Array.from(bookingDialog.querySelectorAll(
      'button:not([disabled]):not([hidden]), a[href]:not([hidden]), input:not([disabled]):not([hidden]), select:not([disabled]):not([hidden]), textarea:not([disabled]):not([hidden])'
    )).filter((el) => el.offsetParent !== null);
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  setDefaultDate();
  updateBookingUI();
  const requestedOffer = new URLSearchParams(location.search).get('book');
  if (requestedOffer && offerings[requestedOffer]) {
    lastBookingTrigger = document.querySelector('.services-book-btn');
    openBooking(requestedOffer);
  }
}


// Shared presentation slider for both package cards.
(() => {
  document.querySelectorAll('[data-curated-canva-carousel]').forEach((root) => {
    const base = root.dataset.assets;
    const title = root.dataset.slideTitle || 'Presentation';
    const pages = root.dataset.slidePages.split(',').map(Number);
    const slides = pages.map((page) => ({
      page,
      src: `${base}slide-${String(page).padStart(2, '0')}.jpg`,
    }));
    Promise.all(slides.map((slide) => new Promise((resolve, reject) => {
      const preloader = new Image();
      preloader.onload = () => resolve(slide);
      preloader.onerror = () => reject(new Error('Missing image: ' + slide.src));
      preloader.src = slide.src;
    }))).then(() => {
    const display = root.querySelector('.curated-canva-carousel__image');
    const dots = root.querySelector('.curated-canva-carousel__dots');
    const prev = root.querySelector('.curated-canva-carousel__arrow--prev');
    const next = root.querySelector('.curated-canva-carousel__arrow--next');
    const more = root.querySelector('.curated-canva-carousel__more');
    const fullscreen = root.querySelector('.curated-canva-carousel__fullscreen');
    let active = 0;
    let touchStartX = null;
    let controlsTimer;
    function revealTouchControls() {
      root.classList.add('is-controls-visible');
      clearTimeout(controlsTimer);
      controlsTimer = setTimeout(() => root.classList.remove('is-controls-visible'), 2500);
    }
    slides.forEach((slide, index) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'curated-canva-carousel__dot';
      dot.setAttribute('aria-label', `Show slide ${index + 1} of ${slides.length}`);
      dot.addEventListener('click', () => {
        select(index);
        dots.hidden = true;
        more.setAttribute('aria-expanded', 'false');
      });
      dots.appendChild(dot);
    });
    function select(index) {
      active = (index + slides.length) % slides.length;
      display.src = slides[active].src;
      display.alt = `${title} — slide ${slides[active].page} of ${slides.length}`;
      Array.from(dots.children).forEach((dot, i) => {
        dot.setAttribute('aria-current', String(i === active));
      });
    }
    prev.addEventListener('click', () => select(active - 1));
    next.addEventListener('click', () => select(active + 1));
    more.addEventListener('click', () => {
      dots.hidden = !dots.hidden;
      more.setAttribute('aria-expanded', String(!dots.hidden));
    });
    fullscreen.addEventListener('click', async () => {
      const picture = root.querySelector('.curated-canva-carousel__picture');
      try {
        if (document.fullscreenElement) await document.exitFullscreen();
        else await picture.requestFullscreen();
      } catch (_) {
        // Fullscreen is optional when the browser does not allow it.
      }
    });
    document.addEventListener('fullscreenchange', () => {
      const isFullscreen = root.contains(document.fullscreenElement);
      root.classList.toggle('is-fullscreen', isFullscreen);
      const label = isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen';
      fullscreen.setAttribute('aria-label', label);
      fullscreen.title = label;
    });
    root.tabIndex = 0;
    root.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') { e.preventDefault(); select(active - 1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); select(active + 1); }
    });
    root.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0]?.screenX ?? null;
      revealTouchControls();
    }, { passive: true });
    root.addEventListener('touchend', (e) => {
      if (touchStartX == null) return;
      const delta = (e.changedTouches[0]?.screenX ?? touchStartX) - touchStartX;
      touchStartX = null;
      if (Math.abs(delta) >= 35) select(active + (delta < 0 ? 1 : -1));
    }, { passive: true });
    select(0);
    }).catch(() => {
      // Keep each card's cover visible if a slide asset cannot be loaded.
    });
  });
})();

/* ===== Frontend-only academy: static pages, inline data, mailto, and learning preview ===== */
(() => {
  const menuButton=document.querySelector('[data-academy-menu]');
  const academyNav=document.querySelector('.academy-mobile-nav');
  if(menuButton && academyNav){
    const close=()=>{academyNav.hidden=true;menuButton.setAttribute('aria-expanded','false');menuButton.textContent='☰';};
    menuButton.addEventListener('click',()=>{const show=academyNav.hidden;academyNav.hidden=!show;menuButton.setAttribute('aria-expanded',String(show));menuButton.textContent=show?'×':'☰';});
    academyNav.querySelectorAll('a').forEach(link=>link.addEventListener('click',close));
    document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!academyNav.hidden){close();menuButton.focus();}});
  }
  const params=new URLSearchParams(location.search);
  const catalog=window.SHALINI_COURSES || {};
  const money=value=>'₹'+Number(value).toLocaleString('en-IN');
  const issue=(form,message)=>{const el=form?.querySelector('.academy-form-status');if(el)el.textContent=message;};

  const contact=document.getElementById('academy-contact-form');
  if(contact){
    const type=params.get('type'), sel=contact.querySelector('[name=\"inquiryNature\"]');
    if(sel && type)sel.value=type==='corporate'?'Corporate Workshop':type==='consultation'?'Personal Mentorship':type==='courses'?'Personal Mentorship':'Personal Mentorship';
    const packageOffer=params.get('offer');
    const packageMessages={
      'comm-package':'I would like to enquire about the Speak with Confidence package (7 private sessions). Please share availability and the next steps.',
      'style-package':'I would like to enquire about the 1:1 Signature Style Transformation package (6 private video sessions and a tailored 28-page booklet). Please share availability and the next steps.'
    };
    if(packageMessages[packageOffer]){
      const message=contact.querySelector('textarea[name="message"]');
      if(message&&!message.value)message.value=packageMessages[packageOffer];
    } else if(params.get('course')){
      const message=contact.querySelector('textarea[name="message"]');
      if(message&&!message.value)message.value='I would like to know more about '+(catalog[params.get('course')]?.title||params.get('course'))+'.';
    }

  }

})();


/* Shared screenshot-matched navigation on all frontend pages. */
(function(){
  const toggle=document.querySelector('[data-sr-toggle]');
  const menu=document.getElementById('sr-mobile-menu');
  if(!toggle || !menu)return;
  const close=()=>{menu.hidden=true;toggle.setAttribute('aria-expanded','false');toggle.setAttribute('aria-label','Open navigation');};
  toggle.addEventListener('click',()=>{
    const opening=menu.hidden;
    menu.hidden=!opening;
    toggle.setAttribute('aria-expanded',String(opening));
    toggle.setAttribute('aria-label',opening?'Close navigation':'Open navigation');
  });
  menu.addEventListener('click',e=>{if(e.target.closest('a'))close();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!menu.hidden){close();toggle.focus();}});
  document.addEventListener('click',e=>{if(!menu.hidden&&!e.target.closest('.sr-header'))close();});
  window.addEventListener('resize',()=>{if(window.innerWidth>1380&&!menu.hidden)close();},{passive:true});
})();


/* 21-Day Speakathon: frontend-only, progressive enhancement. No video/payments simulated. */
(function(){
  const root=document.querySelector('.speakathon-page');
  if(!root)return;
  const modal=document.getElementById('sp-preview-dialog');
  const data={
    camera:{title:'Speak Confidently in Front of the Camera',description:'Learn a simple exercise that helps you stop overthinking before speaking.',steps:['Choose one idea you want to share in a single sentence.','Take a short breath and speak to your camera naturally.','Play it back once and notice one thing you did well.','Try again, focusing on one clear improvement.']},
    smile:{title:'The Easiest Way to Look More Confident Without Saying a Word',description:'Practice using a genuine smile to make your first impression warmer and more approachable.',steps:['Relax your shoulders and your facial muscles.','Think of something that naturally makes you smile.','Look toward the camera and notice the difference in your expression.','Repeat the exercise before your next introduction.']}
  };
  let lastTrigger=null;
  root.querySelectorAll('[data-sp-preview]').forEach(btn=>btn.addEventListener('click',()=>{
    const item=data[btn.dataset.spPreview];if(!item||!modal)return;
    lastTrigger=btn;
    document.getElementById('sp-dialog-title').textContent=item.title;
    document.getElementById('sp-dialog-description').textContent=item.description;
    const target=document.getElementById('sp-dialog-steps');target.replaceChildren();
    item.steps.forEach(s=>{const li=document.createElement('li');li.textContent=s;target.append(li)});
    if(typeof modal.showModal==='function')modal.showModal();else modal.setAttribute('open','');
  }));
  const close=()=>{if(!modal)return;if(typeof modal.close==='function')modal.close();else modal.removeAttribute('open');lastTrigger?.focus();};
  modal?.querySelectorAll('[data-sp-close]').forEach(btn=>btn.addEventListener('click',close));
  modal?.addEventListener('click',event=>{if(event.target===modal)close();});
  const expand=document.getElementById('sp-expand-all');
  const weeks=[...root.querySelectorAll('.sp-week')];
  const sync=()=>{if(!expand)return;const all=weeks.every(w=>w.open);expand.setAttribute('aria-expanded',String(all));expand.innerHTML=(all?'Collapse all weeks':'Expand all weeks')+' <span aria-hidden="true">'+(all?'−':'＋')+'</span>';};
  expand?.addEventListener('click',()=>{const next=!weeks.every(w=>w.open);weeks.forEach(w=>w.open=next);sync();});
  weeks.forEach(w=>w.addEventListener('toggle',sync));sync();
})();
