(() => {
  if (!document.querySelector('[data-package-headline]')) return;
  const offers = {
    'comm-package': {
      name: 'Speak with Confidence',
      shortName: 'Speak with Confidence',
      bookletTitle: 'Personalised Communication Progress Report',
      kicker: 'SHALINI RATHOD · COMMUNICATION & CONFIDENCE',
      headline: 'A More Refined Way to <em>Communicate</em>',
      description: 'Private coaching tailored to your needs, whether you want to strengthen your delivery, refine your tone, overcome speaking habits, or feel more at ease in professional settings.',
      duration: '7 sessions · 7 weeks',
      format: 'Private 1:1 Zoom sessions',
      price: '₹39,999',
      priceUSD: 2200,
      originalPrice: '',
      installments: '',
      investmentLabel: 'COMMUNICATION PACKAGE',
      deliverableOne: 'Private 60-minute 1:1 Zoom sessions',
      deliverableTwo: 'Personalized Executive Speaker Playbook',
      visualLabel: 'THE SPEAK WITH CONFIDENCE PROGRAM',
      visualSubtitle: 'Build a voice that feels like yours.',
      sessionBadge: '7 private sessions',
      sessions: '7 private sessions',
      length: '7 weeks',
      sessionDetails: [
        { title: 'Non-Verbal Communication & Body Language', bullets: ['3-point body language audit', 'Eye contact & facial congruence', 'Video-based presence analysis'], duration: '60 mins · 1:1 Video' },
        { title: 'Vocal Dynamics & Tonal Control', bullets: ['5 vocal delivery variables', 'Strategic pauses & emphasis', '3-tone vocal practice'], duration: '60 mins · 1:1 Video' },
        { title: 'Verbal Precision & Articulation', bullets: ['3-part message structuring', 'Filler words & verbal clutter', 'Articulation, diction & sentence economy'], duration: '60 mins · 1:1 Video' },
        { title: 'Conversational Intelligence & Social Fluency', bullets: ['3-step active listening', 'Conversational turn-taking techniques', 'Rapport building & social calibration'], duration: '60 mins · 1:1 Video' },
        { title: 'Assertiveness & Verbal Authority', bullets: ['DESC framework for difficult conversations', 'Hedging & unnecessary apologies', 'Assertive response scenario practice'], duration: '60 mins · 1:1 Video' },
        { title: 'Executive Communication & Speaking Impact', bullets: ['PREP framework for concise answers', 'Rule of Three messaging', 'Impromptu speaking & response drills'], duration: '60 mins · 1:1 Video' },
      ],
    },
    'style-package': {
      name: 'Shalini’s Style Transformation',
      shortName: 'Style Transformation',
      bookletTitle: '📝 Detailed Tailored Style recommendation Booklet',
      kicker: 'PRIVATE IMAGE CONSULTING & STYLING',
      headline: "Let's Curate an Image That's <em>Unmistakably Yours.</em>",
      description: 'Discover what brings out your best through personalised sessions with Shalini, thoughtfully tailored to refine your image and cultivate a style that’s distinctly yours.',
      duration: '6 sessions · 6+ hours',
      format: 'Private 1:1 video consultations',
      price: '₹69,999',
      priceUSD: 2200,
      originalPrice: '',
      installments: '',
      investmentLabel: 'COMPLETE 1:1 PACKAGE (6 SESSIONS + BOOKLET)',
      deliverableOne: 'Video Consultations',
      deliverableTwo: '28-Page Tailored Booklet',
      visualLabel: 'SHALINI’S STYLE TRANSFORMATION',
      visualSubtitle: 'Make your style feel unmistakably you.',
      sessionBadge: '6 private sessions',
      sessions: '6 private sessions',
      length: '6 hours total',
      sessionDetails: [
        { title: 'Face Shape Assessment', bullets: ['Facial shape, proportions & bone structure mapping', 'Flattering necklines, collars & eyewear frames', 'Hairstyle, hair length & parting recommendations'], duration: '60 mins · 1:1 Video' },
        { title: 'Body Shape Analysis', bullets: ['Vertical proportions, torso-to-leg ratio & body structure analysis', 'Optimal waistlines, jacket lengths & hemlines', 'Fabric weight, silhouette balance & garment fit recommendations'], duration: '60 mins · 1:1 Video' },
        { title: 'Lifestyle & Image Analysis', bullets: ['Wardrobe-to-lifestyle allocation audit', 'Repeatable daily outfit formulas', 'Occasion-specific dressing & wardrobe versatility', 'Wardrobe gaps, essentials & strategic investment priorities'], duration: '60 mins · 1:1 Video' },
        { title: 'Makeup & Accessories Styling', bullets: ['Undertone & skin contrast identification', 'Signature everyday & evening makeup shades', 'Jewelry metal selection & focal point balance'], duration: '60 mins · 1:1 Video' },
        { title: 'Curated Styling for Formal Occasions', bullets: ['Dress-code interpretation & occasion-specific style direction', 'Formalwear proportioning, structure & fit refinement', 'Strategic use of statement pieces, textures & finishing details'], duration: '60 mins · 1:1 Video' },
        { title: 'Opening Session for Styling (Anything of your choice)', bullets: ['100% custom agenda tailored to your immediate need', 'Live outfit remixing with clothes you already own', 'Quick verdicts on fit, alterations & pairing'], duration: '60 mins · 1:1 Video' },
      ],
    }
  };

  const params = new URLSearchParams(window.location.search);
  const offerKey = params.get('offer') === 'style-package' ? 'style-package' : 'comm-package';
  const offer = offers[offerKey];
  const contactHref = window.SR_THEME.url('contact.html?type=consultation&offer='+encodeURIComponent(offerKey)+'#academy-contact-form');
  const setText = (selector, value) => document.querySelectorAll(selector).forEach((element) => { element.textContent = value; });
  setText('[data-package-discovery-prompt]', offerKey === 'comm-package' ? 'Unsure where to begin refining the way you express yourself?' : 'Not sure which area of your personal style needs attention first?');
  const formatPrice = (currency) => {
    if (currency === 'USD') {
      return '$' + offer.priceUSD.toLocaleString('en-US');
    }
    return offer.price;
  };
  let selectedCurrency = 'INR';
  const updateCurrency = (currency) => {
    selectedCurrency = currency;
    document.querySelectorAll('[data-currency]').forEach((button) => {
      const active = button.dataset.currency === currency;
      button.setAttribute('aria-pressed', String(active));
    });
    const displayPrice = formatPrice(currency);
    setText('[data-package-price]', displayPrice);
    setText('[data-booking-price]', displayPrice);
    setText('[data-booking-confirm-price]', displayPrice);
    setText('[data-package-installments]', currency === 'INR' ? offer.installments : '');
    const installmentLabel = document.querySelector('[data-package-installments]');
    if (installmentLabel) installmentLabel.hidden = currency === 'USD' || !offer.installments;
  };
  updateCurrency(selectedCurrency);

  document.querySelectorAll('[data-currency]').forEach((button) => {
    button.addEventListener('click', () => updateCurrency(button.dataset.currency));
  });

  document.title = offer.name + ' | Shalini Rathod';
  const packageUrl = window.SR_THEME.url('package-enroll.html?offer='+encodeURIComponent(offerKey));
  const setMeta = (selector, value) => document.querySelector(selector)?.setAttribute('content', value);
  document.querySelector('link[rel="canonical"]')?.setAttribute('href', packageUrl);
  document.querySelector('meta[name="description"]')?.setAttribute('content', offer.description);
  setMeta('meta[property="og:title"]', document.title);
  setMeta('meta[property="og:description"]', offer.description);
  setMeta('meta[property="og:url"]', packageUrl);
  setMeta('meta[name="twitter:title"]', document.title);
  setMeta('meta[name="twitter:description"]', offer.description);
  const packageSchema = document.querySelector('#package-structured-data');
  if (packageSchema) packageSchema.textContent = JSON.stringify({'@context':'https://schema.org','@type':'Service',name:offer.name,description:offer.description,url:packageUrl,provider:{'@type':'Organization',name:'Shalini Rathod Confidence & Image Academy',url:window.SR_THEME.home}});
  document.documentElement.dataset.packageOffer = offerKey;
  const communicationTierCurriculum = document.querySelector('[data-communication-tier-curriculum]');
  if (communicationTierCurriculum) communicationTierCurriculum.hidden = offerKey !== 'comm-package';
  document.querySelectorAll('[data-communication-advanced-card]').forEach((card) => { card.hidden = offerKey !== 'comm-package'; });
  document.querySelectorAll('[data-styling-advanced-card]').forEach((card) => { card.hidden = offerKey !== 'style-package'; });
  document.querySelectorAll('[data-package-testimonial-offer]').forEach((card) => {
    card.hidden = card.dataset.packageTestimonialOffer !== offerKey;
  });
  setText('[data-package-sessions-intro]', offerKey === 'comm-package'
    ? 'Individual 60-minute video coaching across communication, presence and high-stakes presentations.'
    : 'Individual 60-minute styling consultations tailored to your features, wardrobe, lifestyle and personal image.');
  const sessionTrack = document.querySelector('[data-session-slider]');
  const sessionPrev = document.querySelector('[data-session-slider-prev]');
  const sessionNext = document.querySelector('[data-session-slider-next]');
  const sessionStatus = document.querySelector('[data-session-slider-status]');
  if (sessionTrack && sessionPrev && sessionNext && sessionStatus) {
    const visibleSessionCount = () => Array.from(sessionTrack.querySelectorAll('.package-session-card')).filter((card) => !card.hidden).length;
    const cardsPerSlide = () => window.matchMedia('(max-width: 600px)').matches ? 1 : (window.matchMedia('(max-width: 900px)').matches ? 2 : 3);
    const updateSessionSlider = () => {
      const pageSize = cardsPerSlide();
      const totalSlides = Math.max(1, Math.ceil(visibleSessionCount() / pageSize));
      const maxScroll = Math.max(0, sessionTrack.scrollWidth - sessionTrack.clientWidth);
      const currentSlide = Math.min(totalSlides - 1, Math.round(sessionTrack.scrollLeft / Math.max(1, sessionTrack.clientWidth)));
      sessionStatus.textContent = String(currentSlide + 1).padStart(2, '0') + ' / ' + String(totalSlides).padStart(2, '0');
      sessionPrev.disabled = sessionTrack.scrollLeft <= 2;
      sessionNext.disabled = maxScroll - sessionTrack.scrollLeft <= 2;
    };
    sessionPrev.addEventListener('click', () => sessionTrack.scrollBy({ left: -sessionTrack.clientWidth, behavior: 'smooth' }));
    sessionNext.addEventListener('click', () => sessionTrack.scrollBy({ left: sessionTrack.clientWidth, behavior: 'smooth' }));
    sessionTrack.addEventListener('scroll', updateSessionSlider, { passive: true });
    window.addEventListener('resize', updateSessionSlider);
    updateSessionSlider();
  }
  setText('[data-package-name]', offer.shortName);
  setText('#package-booklet-title', offer.bookletTitle);
  setText('[data-package-kicker]', offer.kicker);
  document.querySelector('[data-package-headline]').innerHTML = offer.headline;
  const mobileCover = document.querySelector('[data-package-cover-mobile]');
  const desktopCover = document.querySelector('[data-package-cover]');
  if (mobileCover && desktopCover) {
    mobileCover.src = desktopCover.currentSrc || desktopCover.src;
    mobileCover.alt = '';
  }
  setText('[data-package-description]', offer.description);
  setText('[data-package-duration]', offer.duration);
  setText('[data-package-format]', offer.format);
  setText('[data-package-price]', formatPrice(selectedCurrency));
  setText('[data-package-investment-label]', offer.investmentLabel);
  setText('[data-package-deliverable-one]', offer.deliverableOne);
  setText('[data-package-deliverable-two]', offer.deliverableTwo);
  const originalPrice = document.querySelector('[data-package-original-price]');
  if (originalPrice) { originalPrice.textContent = offer.originalPrice; originalPrice.hidden = !offer.originalPrice; }
  setText('[data-package-installments]', offer.installments);
  const installments = document.querySelector('[data-package-installments]');
  if (installments) installments.hidden = !offer.installments;
  setText('[data-package-visual-label]', offer.visualLabel);
  setText('[data-package-visual-subtitle]', offer.visualSubtitle);
  setText('[data-package-session-badge]', offer.sessionBadge);
  document.querySelectorAll('.package-session-card').forEach((card, index) => {
    const session = offer.sessionDetails[index];
    if (!session) return;
    const heading = card.querySelector('h3');
    const time = card.querySelector('.package-session-time');
    const list = card.querySelector('ul');
    if (heading) heading.textContent = session.title;
    if (time) time.textContent = '◷  ' + session.duration;
    if (list) list.replaceChildren(...session.bullets.map((bullet) => {
      const item = document.createElement('li');
      item.textContent = bullet;
      return item;
    }));
  });
  setText('[data-booking-price]', formatPrice(selectedCurrency));
  setText('[data-booking-confirm-price]', formatPrice(selectedCurrency));
  document.querySelectorAll('[data-package-contact]').forEach((link) => { link.href = contactHref; });

  document.querySelectorAll('[data-comparison-plan], [data-discovery-booking]').forEach(button=>button.addEventListener('click', event=>{event.preventDefault();window.location.href=contactHref;}));
  const heroImage = document.querySelector('[data-package-cover]');
  if (heroImage) {
    heroImage.src = (window.SR_THEME.assetBase + 'assets/hero-seated.webp');
    heroImage.alt = 'Shalini Rathod, image consultant';
  }

  const galleryTrack = document.querySelector('[data-gallery-track]');
  const galleryViewport = document.querySelector('[data-gallery-viewport]');
  const demoKicker = document.querySelector('[data-demo-kicker]');
  const demoTitle = document.querySelector('[data-demo-title]');
  const demoSequence = document.querySelector('[data-demo-sequence]');
  const dotsRoot = document.querySelector('[data-gallery-dots]');
  const editorialPhotoLink = document.querySelector('[data-view-editorial-photo]');
  const packageGallery = document.querySelector('[data-package-gallery]');
  const previousImageButton = document.querySelector('[data-gallery-prev]');
  const nextImageButton = document.querySelector('[data-gallery-next]');
  const demos = [
    {
      kicker: 'DEMO SLIDE 01 · RADAR DIAGNOSTIC', title: 'Radar Diagnostic & Capability Asymmetry',
      reportSlide: {
        sequence: '01 / 05',
        title: 'Radar Diagnostic & Capability Asymmetry',
        author: 'Upendra Bhowmik · Senior Software Engineer',
        name: 'Upendra Bhowmik',
        score: 'Score: 2.55 / 5.0 (B− · Developing)',
        metrics: [
          ['Top Capability:', 'Conversational Trust (3.8)'],
          ['Primary Vulnerability:', 'Assertiveness & Boundaries (1.8)'],
          ['Verbal Clarity:', 'Bottom-up rambling (2.2)']
        ],
        summary: 'Upendra’s executive influence is throttled not by lack of engineering depth, but by boundary anxiety and bottom-up over-explanation under questioning.',
        chart: (window.SR_THEME.assetBase + 'assets/canva/communication/radar-diagnostic-slide-01.png?v=20261028-colorful-chart'),
        chartAlt: 'Radar chart comparing Upendra’s communication skills with the staff benchmark.'
      }
    },    {
      kicker: 'DEMO SLIDE 02 · SIX-DIMENSIONAL SCORECARD', title: 'Six-Dimensional Scorecard Breakdown',
      scorecard: {
        sequence: '02 / 05',
        title: 'Six-Dimensional Scorecard Breakdown',
        author: 'Upendra Bhowmik · Senior Software Engineer',
        heading: 'Six-Dimensional Scorecard',
        subtitle: 'Diagnostic ratings across 18 behavioral sub-criteria (Scale: 1.0 – 5.0)',
        dimensions: [
          {name:'Vocal Presence', facets:'Pace, Tone, Modulation', summary:'Warm in casual chats; accelerates rapidly to 175+ WPM under questioning with flat pitch delivery.', score:'2.5', target:'4.2', gap:'−1.7'},
          {name:'Verbal Clarity', facets:'Articulation, Structure, Conciseness', summary:'High internal grasp, low verbal economy. Details low-level mechanics before stating the decision.', score:'2.2', target:'4.5', gap:'−2.3', open:true, prescription:'Apply PREP structure: Point first, one rationale, one metric, restate point.', criteria:[
            ['Articulation','2.6','Focuses on implementation traces over business impact.','The "Expert’s Curse": Assuming listeners need full mechanics.'],
            ['Top-Down Framing','2.0','Narrates chronological debugging steps rather than outcomes.','Defensive justification before the bottom line.'],
            ['Conciseness','2.0','Frequent hedging: "basically", "kind of", "does that make sense".','Qualifying statements to soften perceived accountability.']
          ]},
          {name:'Non-Verbal Presence', facets:'Posture, Gestures, Eye Contact', summary:'Defensive retraction during debate; camera gaze drifts to side monitors during virtual calls.', score:'2.7', target:'4.1', gap:'−1.4'},
          {name:'Conversational Skills', facets:'Listening, Response Quality, Rapport', summary:'Core interpersonal asset. Highly respected for empathy, active listening, and collegial humility.', score:'3.8', target:'4.3', gap:'−0.5'},
          {name:'Assertiveness', facets:'Boundaries, Confidence, Directness', summary:'Primary bottleneck. Equates boundaries with hostility; absorbs scope creep and under-negotiates.', score:'1.8', target:'4.4', gap:'−2.6', open:true, prescription:'Shift from "No" to the Trade-Off Pivot: "I can prioritize X if we defer Y."', criteria:[
            ['Boundary Setting','1.5','Accepts late scope and off-hours requests without pushback.','Believing refusal destroys working relationships.'],
            ['Self-Advocacy','2.0','Concedes immediately at the first pushback during appraisals.','Expecting technical work to advocate for itself.'],
            ['Direct Feedback','1.8','Softens warnings to the point that other teams ignore them.','Diluting feedback to avoid temporary awkwardness.']
          ]},
          {name:'Speaking Impact', facets:'Structure, Composure, Audience Awareness', summary:'Struggles to synthesize technical depth into executive stakes; loses stakeholder attention.', score:'2.3', target:'4.5', gap:'−2.2'}
        ]
      }
    },
    {
      kicker: 'DEMO SLIDE 03 · SPEECH ANATOMY', title: 'Speech Anatomy & Stakeholder Matrix',
      speechAnalysis: {
        sequence:'03 / 05',
        title:'Speech Anatomy & Stakeholder Matrix',
        author:'Upendra Bhowmik · Senior Software Engineer',
        charts:[
          {title:'Speech Allocation',subtitle:'Verbal airtime in technical syncs',src:(window.SR_THEME.assetBase + 'assets/canva/communication/speech-allocation-donut.svg?v=20261022-charts'),centerValue:'52%',centerLabel:'Technical',legend:[['Technical Mechanics','52%','#2563eb'],['Hedging & Apologies','18%','#e27b00'],['Fillers & Hesitation','16%','#94a3b8'],['Executive Decision','14%','#059669']]},
          {title:'Response Under Scope Pushback',subtitle:'Behavioral pattern when deadlines are accelerated',src:(window.SR_THEME.assetBase + 'assets/canva/communication/scope-pushback-donut.svg?v=20261022-charts'),centerValue:'60%',centerLabel:'Silent Yes',legend:[['Silent Compliance','60%','#e91e4c'],['Hesitant Deflection','24%','#f04b0b'],['Constructive Assertion','16%','#059669']]}
        ],
        matrixTitle:'Exposure Matrix: Confidence by Forum Scale',
        matrixSubtitle:'Behavioral stability as group size and professional stakes increase',
        rows:[
          ['1-on-1 Pair Programming','1 person',88,85,65,"Mild hesitation when correcting a senior's code."],
          ['Daily Standup','5–8 peers',74,70,48,'Chronological Jira recounting over summary.'],
          ['Sprint Demo','15–25 people',52,48,32,'Monotone delivery; rushes past key business wins.'],
          ['Principal Architecture Review','6–10 seniors',40,38,24,'Uptalking conclusions under technical scrutiny.'],
          ['VP / Executive Townhall','50+ leaders',28,26,18,'Avoids voicing systemic risks in public.'],
          ['Compensation Negotiation','1–2 leaders',18,22,14,'Immediate capitulation at the first counter.']
        ]
      }
    },
    {
      kicker: 'DEMO SLIDE 04 · SCRIPT LABORATORY', title: 'Everyday Traps & Script Laboratory',
      scriptLab: {
        sequence:'04 / 05',
        title:'Everyday Traps & Script Laboratory',
        author:'Upendra Bhowmik · Senior Software Engineer',
        cases:[
          {tab:'Case 01: Boundaries',context:'Friday 6:30 PM Scope Creep',reflex:'"Uh, sure, I was closing my laptop, but I’ll see what I can do tonight. Sorry for the delay."',thought:'"If I say no, I won’t be seen as a team player."',outcome:'Works until 1:30 AM; requester learns Upendra absorbs late emergencies.',reframe:'"Builds are locked for the weekend. I can tackle this Monday morning, or if this is a P0 blocker, which Monday deliverable should we drop to make room?"',principle:'Returns scope trade-offs to requester calmly.',why:'Protects recovery time while remaining constructive.',delivery:'Downward inflection · 130 WPM'},
          {tab:'Case 02: Negotiation',context:'Appraisal · Compensation Discussion',reflex:'"I know budgets are tight, whatever the committee decides is fine..."',thought:'Hoping quiet hard work will be recognised without an explicit ask.',outcome:'The compensation conversation ends without a clear request.',reframe:'"My refactor saved $140K in cloud compute. I am seeking market parity at L6."',principle:'Pair a specific impact with a direct request.',why:'Makes the value and the requested next step clear.',delivery:'Calm, direct delivery'},
          {tab:'Case 03: Articulation',context:'Technical Update · Explaining an Incident',reflex:'"The worker pool mutex lock on table 4 triggered a cascade..."',thought:'Proving competence through exhaustive detail rather than synthesis.',outcome:'Listeners have to infer the result from implementation details.',reframe:'"Queue stalled for 12 mins. Resolved with zero data loss; circuit breaker added."',principle:'Lead with the outcome, then add the detail that supports it.',why:'Gives listeners the impact before the technical explanation.',delivery:'Concise, downward inflection'},
          {tab:'Case 04: Personal',context:'Personal · Deep-Work Boundary',reflex:'"Sure, I can run that errand during my deep-work block, it’s fine..."',thought:'Guilt over dedicated focus time; difficulty saying no to peers or family.',outcome:'Focused work time is interrupted and the boundary stays unclear.',reframe:'"I’d love to help! I am in deep focus until 6 PM; let’s connect right after."',principle:'Set a clear limit and offer a specific time to reconnect.',why:'Keeps the response warm while protecting focus time.',delivery:'Warm, clear delivery'}
        ],
        traps:[
          {title:'Preemptive Apology',percent:'88%',category:'workplace',habit:'"Sorry to bother you, just had a small doubt regarding the schema..."',root:'Attempting to minimize perceived burden before legitimate work asks.',antidote:'"Hi Priya, I need your input on the schema to unblock the sprint."'},
          {title:'The Nodding Mirage',percent:'91%',category:'workplace',habit:'[Nodding affirmatively while leadership states an unfeasible deadline]',root:'Conflating politeness with agreement; avoiding group friction.',antidote:'"From an architecture standpoint, that date requires a 40% scope reduction."'},
          {title:'Silent Yes to Burnout',percent:'84%',category:'workplace',habit:'"Sure, I guess I can look into that bug over the weekend..."',root:'Belief that saying no signals lack of dedication.',antidote:'"I can take that on Monday, or swap out the index task today. Which takes priority?"'},
          {title:'The TMI Avalanche',percent:'79%',category:'workplace',habit:'"The worker pool mutex lock on table 4 triggered a cascade..."',root:'Proving competence through exhaustive detail rather than synthesis.',antidote:'"Queue stalled for 12 mins. Resolved with zero data loss; circuit breaker added."'},
          {title:'Hesitant Uptalk',percent:'74%',category:'negotiation',habit:'"We should probably decouple the service...? What do you think...?"',root:'Fear of blame if the recommendation encounters issues.',antidote:'"Decoupling the service reduces latency by 60%. I recommend this path."'},
          {title:'Silent Appraisal Passivity',percent:'86%',category:'negotiation',habit:'"I know budgets are tight, whatever the committee decides is fine..."',root:'Hoping quiet hard work is automatically rewarded.',antidote:'"My refactor saved $140K in cloud compute. I am seeking market parity at L6."'},
          {title:'Personal Boundary Erosion',percent:'82%',category:'personal',habit:'"Sure, I can run that errand during my deep-work block, it’s fine..."',root:'Guilt over dedicated focus time; inability to say no to peers or family.',antidote:'"I’d love to help! I am in deep focus until 6 PM; let’s connect right after."'}
        ]
      }
    },
    {
      kicker: 'DEMO SLIDE 05 · SIX-WEEK ROADMAP', title: 'Six-Week Intervention Roadmap',
      roadmap: {
        sequence:'05 / 05',
        title:'Six-Week Intervention Roadmap',
        author:'Upendra Bhowmik · Senior Software Engineer',
        heading:'Six-Week Action Roadmap',
        subtitle:'Progressive curriculum to transition from passive engineer to authoritative technical lead',
        weeks:[
          ['Pace & Grounding','Calibrating the Internal Metronome','3 daily 60-second updates capped at 135 WPM. Add 2-second pause before speaking.','Speaking pace under surprise questions (<140 WPM).'],
          ['Linguistic Detox','Eliminating Hedging & Apologies','Remove "sorry", "just", "if that makes sense" from all written and verbal updates.','Zero apologies in routine technical requests.'],
          ['The PREP Engine','Bottom-Line First Framing','Structure standups using PREP (Point, Reason, Evidence, Point). 45-sec cap.','Standup update duration (<= 45 seconds).'],
          ['Boundary Protocol','Constructive Trade-Offs','Deploy the Trade-Off Pivot: "I can do X if we defer Y to next week."','Off-hours requests successfully renegotiated.'],
          ['Lens Anchoring','Virtual Camera Mastery','Elevate webcam to eye level. Maintain 4-second steady lens lock during proposals.','Camera contact during proposals (>65%).'],
          ['Negotiation Lab','High-Stakes Composure','Roleplay promotion pitch with an executive coach. Rehearse silence after stating ask.','Zero concessions within 60s of counter.']
        ]
      }
    }
  ];
  const isStyleImageGallery = offerKey === 'style-package';
  if (isStyleImageGallery) {
    const stylePhotoSets = [
      [
        [(window.SR_THEME.assetBase + 'assets/about/shalini-close.webp'), 'Shalini Rathod in a navy blazer portrait', 'center 10%'],
        [(window.SR_THEME.assetBase + 'assets/about/shalini-reading.webp'), 'Shalini Rathod holding her style booklet', 'center 10%'],
        [(window.SR_THEME.assetBase + 'assets/about/shalini-seated.webp'), 'Shalini Rathod seated in a black outfit', 'center 10%']
      ],
      [
        [(window.SR_THEME.assetBase + 'assets/canva/style/slide-01.jpg'), 'Shalini Rathod in a beige full-length styling look', '100% 50%'],
        [(window.SR_THEME.assetBase + 'assets/canva/style/slide-02.jpg'), 'Shalini Rathod in a white top with coffee', '92% 50%'],
        [(window.SR_THEME.assetBase + 'assets/canva/style/slide-03.jpg'), 'Shalini Rathod in a white blazer', '12% 50%']
      ],
      [
        [(window.SR_THEME.assetBase + 'assets/canva/style/slide-08.jpg'), 'Shalini Rathod in a white suit seated portrait', '18% 50%'],
        [(window.SR_THEME.assetBase + 'assets/canva/style/slide-09.jpg'), 'Shalini Rathod in a burgundy outfit seated portrait', '84% 50%'],
        [(window.SR_THEME.assetBase + 'assets/canva/communication/slide-01.jpg'), 'Shalini Rathod in a burgundy blazer at a desk', '93% 50%']
      ],
      [
        [(window.SR_THEME.assetBase + 'assets/canva/communication/slide-04.jpg'), 'Shalini Rathod seated in a blue suit', '66% 50%'],
        [(window.SR_THEME.assetBase + 'assets/canva/communication/slide-05.jpg'), 'Shalini Rathod standing in a navy suit', '69% 50%'],
        [(window.SR_THEME.assetBase + 'assets/canva/communication/slide-07.jpg'), 'Shalini Rathod in a burgundy suit presenting', '32% 50%']
      ],
      [
        [(window.SR_THEME.assetBase + 'assets/canva/communication/slide-09.jpg'), 'Shalini Rathod in a white suit at a desk', '85% 50%'],
        [(window.SR_THEME.assetBase + 'assets/services/styling-consultation.webp'), 'Image consultant presenting a personal styling workshop', '88% 50%'],
        [(window.SR_THEME.assetBase + 'assets/services/power-dressing-cover.webp'), 'Stylist helping a client choose a tailored outfit', '70% 50%']
      ]
    ];
    const styleDemos = [
      ['DEMO SLIDE 01 · IDENTITY MATRIX', 'Style Archetype & Lifestyle Blueprint'],
      ['DEMO SLIDE 02 · SILHOUETTE MATRIX', 'Body & Face Proportion Architecture'],
      ['DEMO SLIDE 03 · CHROMATIC SPECTRUM', '12-Tone Palette & Color Harmony'],
      ['DEMO SLIDE 04 · READY-TO-WEAR', 'Curated Occasion Lookbook'],
      ['DEMO SLIDE 05 · FINISHING TOUCHES', 'Makeup, Accessories & Wishlist']
    ].map(([kicker, title], index) => ({ kicker, title, images: stylePhotoSets[index] }));
    demos.splice(0, demos.length, ...styleDemos);
    setText('.package-booklet-description', 'Explore five style transformation demos featuring Shalini’s portraits.');
  }
  let currentSlide = 0;
  let currentImageIndex = 0;
  let editorialPhotoFocused = false;
  const visibleImageCount = () => editorialPhotoFocused ? 1 : window.matchMedia('(max-width: 650px)').matches ? 1 : window.matchMedia('(max-width: 900px)').matches ? 2 : 3;
  const updateGalleryLayout = () => {
    if (!galleryTrack || !galleryViewport) return;
    const images = [...galleryTrack.children];
    const visibleCount = Math.min(visibleImageCount(), images.length || 1);
    const gap = parseFloat(getComputedStyle(galleryTrack).columnGap) || 0;
    const imageWidth = Math.max(0, (galleryViewport.clientWidth - gap * (visibleCount - 1)) / visibleCount);
    images.forEach((image) => {
      image.style.flexBasis = editorialPhotoFocused ? 'min(100%, 980px)' : imageWidth + 'px';
      image.style.width = editorialPhotoFocused ? 'min(100%, 980px)' : imageWidth + 'px';
    });
    const maxIndex = isStyleImageGallery ? 0 : Math.max(0, images.length - visibleCount);
    currentImageIndex = Math.min(currentImageIndex, maxIndex);
    const step = imageWidth + gap;
    galleryTrack.style.transform = editorialPhotoFocused ? 'translate3d(0, 0, 0)' : `translate3d(${-currentImageIndex * step}px, 0, 0)`;
    if (previousImageButton) previousImageButton.disabled = currentSlide <= 0;
    if (nextImageButton) nextImageButton.disabled = currentSlide >= demos.length - 1;
  };
  const createReportSlide = (report) => {
    const article = document.createElement('article');
    article.className = 'package-report-slide';
    article.setAttribute('aria-label', report.title);

    const header = document.createElement('header');
    header.className = 'package-report-slide-header';
    const sequence = document.createElement('span');
    sequence.className = 'package-report-slide-sequence';
    sequence.textContent = report.sequence;
    const title = document.createElement('h4');
    title.textContent = report.title;
    const author = document.createElement('p');
    author.className = 'package-report-slide-author';
    author.textContent = report.author;
    header.append(sequence, title, author);

    const body = document.createElement('div');
    body.className = 'package-report-slide-body';
    const copy = document.createElement('div');
    copy.className = 'package-report-slide-copy';
    const name = document.createElement('h5');
    name.textContent = report.name;
    const score = document.createElement('p');
    score.className = 'package-report-slide-score';
    score.textContent = report.score;
    const metrics = document.createElement('dl');
    metrics.className = 'package-report-slide-metrics';
    report.metrics.forEach(([label, value]) => {
      const row = document.createElement('div');
      const term = document.createElement('dt');
      const detail = document.createElement('dd');
      term.textContent = label;
      detail.textContent = value;
      row.append(term, detail);
      metrics.append(row);
    });
    const summary = document.createElement('p');
    summary.className = 'package-report-slide-summary';
    summary.textContent = report.summary;
    copy.append(name, score, metrics, summary);

    const chart = document.createElement('img');
    chart.src = report.chart;
    chart.alt = report.chartAlt;
    chart.loading = 'lazy';
    chart.decoding = 'async';
    chart.className = 'package-report-slide-chart';
    body.append(copy, chart);
    article.append(header, body);
    return article;
  };

  const createScorecardSlide = (scorecard) => {
    const article = document.createElement('article');
    article.className = 'package-scorecard-slide';
    article.setAttribute('aria-label', scorecard.title);
    const header = document.createElement('header');
    header.className = 'package-report-slide-header';
    const sequence = document.createElement('span');
    sequence.className = 'package-report-slide-sequence';
    sequence.textContent = scorecard.sequence;
    const title = document.createElement('h4');
    title.textContent = scorecard.title;
    const author = document.createElement('p');
    author.className = 'package-report-slide-author';
    author.textContent = scorecard.author;
    header.append(sequence, title, author);
    const intro = document.createElement('div');
    intro.className = 'package-scorecard-intro';
    const heading = document.createElement('h5');
    heading.textContent = scorecard.heading;
    const subtitle = document.createElement('p');
    subtitle.textContent = scorecard.subtitle;
    intro.append(heading, subtitle);
    const controls = document.createElement('div');
    controls.className = 'package-scorecard-controls';
    const expand = document.createElement('button');
    expand.type = 'button';
    expand.textContent = 'Expand All';
    const slash = document.createElement('span');
    slash.textContent = '/';
    const collapse = document.createElement('button');
    collapse.type = 'button';
    collapse.textContent = 'Collapse All';
    controls.append(expand, slash, collapse);
    intro.append(controls);
    const list = document.createElement('div');
    list.className = 'package-scorecard-list';
    const items = [];
    scorecard.dimensions.forEach((dimension, index) => {
      const section = document.createElement('section');
      section.className = 'package-scorecard-dimension';
      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'package-scorecard-trigger';
      trigger.setAttribute('aria-expanded', String(Boolean(dimension.open)));
      const number = document.createElement('span');
      number.className = 'package-scorecard-number';
      number.textContent = String(index + 1).padStart(2, '0');
      const text = document.createElement('span');
      text.className = 'package-scorecard-label';
      const name = document.createElement('strong');
      name.textContent = dimension.name;
      const facets = document.createElement('span');
      facets.textContent = ` (${dimension.facets})`;
      const summary = document.createElement('span');
      summary.className = 'package-scorecard-summary';
      summary.textContent = dimension.summary;
      text.append(name, facets, summary);
      const rating = document.createElement('span');
      rating.className = 'package-scorecard-rating';
      const stars = document.createElement('span');
      stars.className = 'package-scorecard-stars';
      const val = Number(dimension.score);
      stars.textContent = '★★★★★';
      stars.style.setProperty('--score-fill', `${Math.max(0, Math.min(100, (val / 5) * 100))}%`);
      const score = document.createElement('strong');
      score.textContent = `${dimension.score}/5`;
      const target = document.createElement('span');
      target.className = 'package-scorecard-target';
      target.textContent = `Target: ${dimension.target} (${dimension.gap})`;
      rating.append(stars, score, target);
      const symbol = document.createElement('span');
      symbol.className = 'package-scorecard-symbol';
      symbol.textContent = dimension.open ? '−' : '+';
      trigger.append(number, text, rating, symbol);
      const detail = document.createElement('div');
      detail.className = 'package-scorecard-detail';
      detail.hidden = !dimension.open;
      if (dimension.criteria) {
        const table = document.createElement('div');
        table.className = 'package-scorecard-table';
        ['Sub-Criterion', 'Score', 'Diagnostic Observation', 'Core Habit Trap'].forEach((label) => {
          const cell = document.createElement('span');
          cell.className = 'package-scorecard-th';
          cell.textContent = label;
          table.append(cell);
        });
        dimension.criteria.forEach(([criterion, ratingValue, observation, trap]) => {
          const nameCell = document.createElement('strong');
          nameCell.textContent = criterion;
          const scoreCell = document.createElement('span');
          scoreCell.className = 'package-scorecard-subscore';
          const starsCell = document.createElement('span');
          starsCell.className = 'package-scorecard-stars';
          starsCell.textContent = '★★★★★';
          starsCell.style.setProperty('--score-fill', `${(Number(ratingValue) / 5) * 100}%`);
          const value = document.createElement('strong');
          value.textContent = `${ratingValue}/5`;
          scoreCell.append(starsCell, value);
          const observationCell = document.createElement('span');
          observationCell.textContent = observation;
          const trapCell = document.createElement('span');
          trapCell.textContent = trap;
          table.append(nameCell, scoreCell, observationCell, trapCell);
        });
        detail.append(table);
        const prescription = document.createElement('p');
        prescription.className = 'package-scorecard-prescription';
        const label = document.createElement('strong');
        label.textContent = 'Prescription: ';
        prescription.append(label, document.createTextNode(dimension.prescription));
        detail.append(prescription);
      }
      trigger.addEventListener('click', () => {
        const open = trigger.getAttribute('aria-expanded') !== 'true';
        trigger.setAttribute('aria-expanded', String(open));
        detail.hidden = !open;
        symbol.textContent = open ? '−' : '+';
      });
      section.append(trigger, detail);
      list.append(section);
      items.push({trigger, detail, symbol});
    });
    expand.addEventListener('click', () => items.forEach((item) => {
      item.trigger.setAttribute('aria-expanded', 'true'); item.detail.hidden = false; item.symbol.textContent = '−';
    }));
    collapse.addEventListener('click', () => items.forEach((item) => {
      item.trigger.setAttribute('aria-expanded', 'false'); item.detail.hidden = true; item.symbol.textContent = '+';
    }));
    article.append(header, intro, list);
    return article;
  };

  const createSpeechAnalysisSlide = (analysis) => {
    const article = document.createElement('article');
    article.className = 'package-speech-analysis-slide';
    article.setAttribute('aria-label', analysis.title);
    const header = document.createElement('header');
    header.className = 'package-report-slide-header';
    const sequence = document.createElement('span');
    sequence.className = 'package-report-slide-sequence';
    sequence.textContent = analysis.sequence;
    const title = document.createElement('h4');
    title.textContent = analysis.title;
    const author = document.createElement('p');
    author.className = 'package-report-slide-author';
    author.textContent = analysis.author;
    header.append(sequence, title, author);

    const charts = document.createElement('div');
    charts.className = 'package-speech-chart-grid';
    analysis.charts.forEach((chartData) => {
      const panel = document.createElement('section');
      panel.className = 'package-speech-chart-panel';
      const heading = document.createElement('h5');
      heading.textContent = chartData.title;
      const subtitle = document.createElement('p');
      subtitle.textContent = chartData.subtitle;
      const content = document.createElement('div');
      content.className = 'package-speech-chart-content';
      const figure = document.createElement('figure');
      figure.className = 'package-speech-donut';
      const image = document.createElement('img');
      image.src = chartData.src;
      image.alt = '';
      image.loading = 'lazy';
      image.decoding = 'async';
      const center = document.createElement('figcaption');
      center.className = 'package-speech-donut-center';
      const value = document.createElement('strong');
      value.textContent = chartData.centerValue;
      const label = document.createElement('span');
      label.textContent = chartData.centerLabel;
      center.append(value, label);
      figure.append(image, center);
      const legend = document.createElement('ul');
      legend.className = 'package-speech-chart-legend';
      chartData.legend.forEach(([labelText, percent, color]) => {
        const item = document.createElement('li');
        const key = document.createElement('span');
        key.className = 'package-speech-legend-key';
        key.style.setProperty('--legend-color', color);
        const text = document.createElement('span');
        text.textContent = labelText;
        const amount = document.createElement('strong');
        amount.textContent = percent;
        item.append(key, text, amount);
        legend.append(item);
      });
      content.append(figure, legend);
      panel.append(heading, subtitle, content);
      charts.append(panel);
    });

    const matrix = document.createElement('section');
    matrix.className = 'package-exposure-matrix';
    const matrixHeader = document.createElement('header');
    matrixHeader.className = 'package-exposure-matrix-header';
    const heading = document.createElement('div');
    const matrixTitle = document.createElement('h5');
    matrixTitle.textContent = analysis.matrixTitle;
    const matrixSubtitle = document.createElement('p');
    matrixSubtitle.textContent = analysis.matrixSubtitle;
    heading.append(matrixTitle, matrixSubtitle);
    const scale = document.createElement('span');
    scale.textContent = 'Scale: 0–100%';
    matrixHeader.append(heading, scale);
    const table = document.createElement('div');
    table.className = 'package-exposure-table';
    table.setAttribute('role','table');
    table.setAttribute('aria-label',analysis.matrixTitle);
    const columns = ['Context','Audience','Confidence','Vocal Composure','Boundary Firmness','Observed Tendency'];
    columns.forEach((col) => {
      const cell = document.createElement('div');
      cell.className = 'package-exposure-cell package-exposure-heading';
      cell.setAttribute('role','columnheader');
      cell.textContent = col;
      table.append(cell);
    });
    analysis.rows.forEach(([context,audience,confidence,vocal,boundary,tendency]) => {
      const contextCell = document.createElement('strong');
      contextCell.className='package-exposure-cell';
      contextCell.setAttribute('role','cell');
      contextCell.textContent=context;
      const audienceCell = document.createElement('span');
      audienceCell.className='package-exposure-cell package-exposure-audience';
      audienceCell.setAttribute('role','cell');
      audienceCell.textContent=audience;
      table.append(contextCell,audienceCell);
      [[confidence,'confidence'],[vocal,'vocal'],[boundary,'boundary']].forEach(([score,type]) => {
        const cell=document.createElement('div');
        cell.className='package-exposure-cell package-exposure-meter-cell';
        cell.setAttribute('role','cell');
        const value=document.createElement('strong');
        value.textContent=`${score}%`;
        const track=document.createElement('span');
        track.className=`package-exposure-meter package-exposure-meter-${type}`;
        const fill=document.createElement('span');
        fill.style.width=`${score}%`;
        if(type==='confidence') fill.classList.add(score>=70?'is-high':score>=40?'is-mid':'is-low');
        else if(score<30) fill.classList.add('is-low');
        track.append(fill);
        cell.append(value,track);
        table.append(cell);
      });
      const tendencyCell=document.createElement('span');
      tendencyCell.className='package-exposure-cell package-exposure-tendency';
      tendencyCell.setAttribute('role','cell');
      tendencyCell.textContent=tendency;
      table.append(tendencyCell);
    });
    matrix.append(matrixHeader,table);
    article.append(header,charts,matrix);
    return article;
  };

  const createScriptLabSlide = (lab) => {
    const article=document.createElement('article');
    article.className='package-script-lab-slide';
    article.setAttribute('aria-label',lab.title);
    const header=document.createElement('header');
    header.className='package-report-slide-header';
    const sequence=document.createElement('span');
    sequence.className='package-report-slide-sequence';
    sequence.textContent=lab.sequence;
    const title=document.createElement('h4');
    title.textContent=lab.title;
    const author=document.createElement('p');
    author.className='package-report-slide-author';
    author.textContent=lab.author;
    header.append(sequence,title,author);

    const laboratory=document.createElement('section');
    laboratory.className='package-script-laboratory';
    const labHeader=document.createElement('div');
    labHeader.className='package-script-lab-header';
    const labHeading=document.createElement('div');
    const labTitle=document.createElement('h5');
    labTitle.textContent='Script Laboratory: Verbal Reframing';
    const labSubtitle=document.createElement('p');
    labSubtitle.textContent='Default passive reflex contrasted against assertive executive delivery';
    labHeading.append(labTitle,labSubtitle);
    const caseTabs=document.createElement('div');
    caseTabs.className='package-script-case-tabs';
    caseTabs.setAttribute('role','tablist');
    caseTabs.setAttribute('aria-label','Script cases');
    labHeader.append(labHeading,caseTabs);
    const context=document.createElement('p');
    context.className='package-script-context';
    const compare=document.createElement('div');
    compare.className='package-script-comparison';
    const current=document.createElement('section');
    current.className='package-script-card package-script-current';
    const currentHeader=document.createElement('header');
    const currentTitle=document.createElement('h6');
    currentTitle.textContent='Current Reflex';
    const currentTone=document.createElement('span');
    currentTone.className='package-script-tone';
    currentTone.textContent='Passive';
    currentHeader.append(currentTitle,currentTone);
    const reflex=document.createElement('blockquote');
    reflex.className='package-script-quote';
    const thought=document.createElement('p');
    const outcome=document.createElement('p');
    current.append(currentHeader,reflex,thought,outcome);
    const reframe=document.createElement('section');
    reframe.className='package-script-card package-script-reframe';
    const reframeHeader=document.createElement('header');
    const reframeTitle=document.createElement('h6');
    reframeTitle.textContent='Executive Reframe';
    const principle=document.createElement('span');
    principle.className='package-script-tone';
    reframeHeader.append(reframeTitle,principle);
    const reframeQuote=document.createElement('blockquote');
    reframeQuote.className='package-script-quote';
    const rationale=document.createElement('div');
    rationale.className='package-script-rationale';
    const delivery=document.createElement('footer');
    delivery.className='package-script-delivery';
    const deliveryText=document.createElement('span');
    const copy=document.createElement('button');
    copy.type='button';
    copy.textContent='Copy Script';
    delivery.append(deliveryText,copy);
    reframe.append(reframeHeader,reframeQuote,rationale,delivery);
    compare.append(current,reframe);
    laboratory.append(labHeader,context,compare);

    const trapsSection=document.createElement('section');
    trapsSection.className='package-everyday-traps';
    const trapsHeader=document.createElement('header');
    trapsHeader.className='package-traps-header';
    const trapsHeading=document.createElement('div');
    const trapsTitle=document.createElement('h5');
    trapsTitle.textContent='Everyday Behavioral Traps';
    const trapsSubtitle=document.createElement('p');
    trapsSubtitle.textContent='Common friction habits shared by 90% of engineers and their direct executive antidotes';
    trapsHeading.append(trapsTitle,trapsSubtitle);
    const filters=document.createElement('div');
    filters.className='package-trap-filters';
    filters.setAttribute('role','group');
    filters.setAttribute('aria-label','Filter behavioral traps');
    const trapGrid=document.createElement('div');
    trapGrid.className='package-trap-grid';
    trapsHeader.append(trapsHeading,filters);
    const filterOptions=[['all','All'],['workplace','Workplace'],['negotiation','Negotiation'],['personal','Personal']];
    const trapCards=[];
    lab.traps.forEach((trap) => {
      const card=document.createElement('article');
      card.className='package-trap-card';
      card.dataset.category=trap.category;
      const cardHeader=document.createElement('header');
      const cardTitle=document.createElement('h6');
      cardTitle.textContent=trap.title;
      const percent=document.createElement('span');
      percent.textContent=`${trap.percent} of peers`;
      cardHeader.append(cardTitle,percent);
      const habit=document.createElement('blockquote');
      const label=document.createElement('span');
      label.textContent='Current habit:';
      const quote=document.createElement('em');
      quote.textContent=trap.habit;
      habit.append(label,quote);
      const root=document.createElement('p');
      const rootLabel=document.createElement('strong');
      rootLabel.textContent='Root: ';
      root.append(rootLabel,document.createTextNode(trap.root));
      const antidote=document.createElement('p');
      antidote.className='package-trap-antidote';
      const antidoteLabel=document.createElement('strong');
      antidoteLabel.textContent='Antidote:';
      const antidoteQuote=document.createElement('span');
      antidoteQuote.textContent=trap.antidote;
      antidote.append(antidoteLabel,antidoteQuote);
      card.append(cardHeader,habit,root,antidote);
      trapGrid.append(card);
      trapCards.push(card);
    });
    filterOptions.forEach(([category,label])=>{
      const button=document.createElement('button');
      button.type='button';
      button.textContent=label;
      button.setAttribute('aria-pressed',String(category==='all'));
      button.addEventListener('click',()=>{
        filters.querySelectorAll('button').forEach((other)=>other.setAttribute('aria-pressed',String(other===button)));
        trapCards.forEach((card)=>{card.hidden=category!=='all'&&card.dataset.category!==category;});
      });
      filters.append(button);
    });
    trapsSection.append(trapsHeader,trapGrid);

    const cases=[];
    const renderCase=(data)=>{
      context.replaceChildren();
      const contextLabel=document.createElement('span');
      contextLabel.textContent='Context: ';
      const contextValue=document.createElement('strong');
      contextValue.textContent=data.context;
      context.append(contextLabel,contextValue);
      reflex.textContent=data.reflex;
      thought.replaceChildren();
      const thoughtLabel=document.createElement('strong');
      thoughtLabel.textContent='Internal thought: ';
      thought.append(thoughtLabel,document.createTextNode(data.thought));
      outcome.replaceChildren();
      const outcomeLabel=document.createElement('strong');
      outcomeLabel.textContent='Outcome: ';
      outcome.append(outcomeLabel,document.createTextNode(data.outcome));
      reframeQuote.textContent=data.reframe;
      principle.replaceChildren();
      const principleLabel=document.createElement('strong');
      principleLabel.textContent='Principle: ';
      principle.append(principleLabel,document.createTextNode(data.principle));
      rationale.replaceChildren();
      const whyLabel=document.createElement('strong');
      whyLabel.textContent='Why it works: ';
      rationale.append(
        Object.assign(document.createElement('p'),{textContent:''})
      );
      const whyLine=document.createElement('p');
      whyLine.append(whyLabel,document.createTextNode(data.why));
      rationale.replaceChildren(whyLine);
      deliveryText.textContent=`Delivery: ${data.delivery}`;
      copy.onclick=async()=>{
        try { await navigator.clipboard.writeText(data.reframe); copy.textContent='Copied'; }
        catch { copy.textContent='Select and copy the script'; }
        window.setTimeout(()=>{copy.textContent='Copy Script';},1800);
      };
    };
    lab.cases.forEach((data,index)=>{
      const button=document.createElement('button');
      button.type='button';
      button.id=`package-script-case-${index+1}`;
      button.setAttribute('role','tab');
      button.setAttribute('aria-selected',String(index===0));
      button.tabIndex=index===0?0:-1;
      button.textContent=data.tab;
      button.addEventListener('click',()=>{
        caseTabs.querySelectorAll('[role="tab"]').forEach((other)=>{other.setAttribute('aria-selected',String(other===button));other.tabIndex=other===button?0:-1;});
        renderCase(data);
      });
      button.addEventListener('keydown',(event)=>{
        if(!['ArrowLeft','ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        const next=(index+(event.key==='ArrowRight'?1:-1)+lab.cases.length)%lab.cases.length;
        caseTabs.querySelectorAll('[role="tab"]')[next].focus();
        caseTabs.querySelectorAll('[role="tab"]')[next].click();
      });
      caseTabs.append(button);
    });
    renderCase(lab.cases[0]);
    article.append(header,laboratory,trapsSection);
    return article;
  };

  const createRoadmapSlide = (roadmap) => {
    const article=document.createElement('article');
    article.className='package-roadmap-slide';
    article.setAttribute('aria-label',roadmap.title);
    const header=document.createElement('header');
    header.className='package-report-slide-header';
    const sequence=document.createElement('span');
    sequence.className='package-report-slide-sequence';
    sequence.textContent=roadmap.sequence;
    const title=document.createElement('h4');
    title.textContent=roadmap.title;
    const author=document.createElement('p');
    author.className='package-report-slide-author';
    author.textContent=roadmap.author;
    header.append(sequence,title,author);
    const panel=document.createElement('section');
    panel.className='package-roadmap-panel';
    const panelHeader=document.createElement('header');
    panelHeader.className='package-roadmap-panel-header';
    const text=document.createElement('div');
    const heading=document.createElement('h5');
    heading.textContent=roadmap.heading;
    const subtitle=document.createElement('p');
    subtitle.textContent=roadmap.subtitle;
    text.append(heading,subtitle);
    const sprintCount=document.createElement('span');
    sprintCount.textContent='6 Sprints';
    panelHeader.append(text,sprintCount);
    const list=document.createElement('ol');
    list.className='package-roadmap-list';
    roadmap.weeks.forEach(([name,focus,drill,kpi],index)=>{
      const item=document.createElement('li');
      item.className='package-roadmap-week';
      const marker=document.createElement('span');
      marker.className=`package-roadmap-marker${index===0?' is-complete':''}`;
      marker.setAttribute('aria-label',index===0?'Completed week':'Week '+(index+1));
      marker.textContent=index===0?'✓':String(index+1);
      const content=document.createElement('div');
      content.className='package-roadmap-content';
      const titleline=document.createElement('p');
      titleline.className='package-roadmap-titleline';
      const weekTitle=document.createElement('strong');
      weekTitle.textContent=`Week ${index+1}: ${name}`;
      const dot=document.createElement('span');
      dot.textContent='·';
      const focusText=document.createElement('span');
      focusText.textContent=focus;
      titleline.append(weekTitle,dot,focusText);
      const daily=document.createElement('p');
      daily.className='package-roadmap-drill';
      const dailyLabel=document.createElement('strong');
      dailyLabel.textContent='Daily Drill: ';
      daily.append(dailyLabel,document.createTextNode(drill));
      content.append(titleline,daily);
      const target=document.createElement('p');
      target.className='package-roadmap-kpi';
      const targetLabel=document.createElement('span');
      targetLabel.textContent='KPI: ';
      target.append(targetLabel,document.createTextNode(kpi));
      item.append(marker,content,target);
      list.append(item);
    });
    panel.append(panelHeader,list);
    article.append(header,panel);
    return article;
  };

  const renderGallery = () => {
    const demo = demos[currentSlide];
    const galleryImages = editorialPhotoFocused
      ? [[(window.SR_THEME.assetBase + 'assets/hero-seated.webp'), 'Shalini Rathod in a landscape editorial portrait']]
      : demo.images || [];
    if (galleryTrack) {
      packageGallery?.classList.toggle('is-editorial-focused', editorialPhotoFocused);
      packageGallery?.classList.toggle('is-style-image-gallery', isStyleImageGallery);
      packageGallery?.classList.toggle('is-slide-preview', Boolean(demo.reportSlide || demo.scorecard || demo.speechAnalysis || demo.scriptLab || demo.roadmap) && !editorialPhotoFocused);
      if (!editorialPhotoFocused && (demo.reportSlide || demo.scorecard || demo.speechAnalysis || demo.scriptLab || demo.roadmap)) {
        galleryTrack.replaceChildren(demo.roadmap ? createRoadmapSlide(demo.roadmap) : demo.scriptLab ? createScriptLabSlide(demo.scriptLab) : demo.speechAnalysis ? createSpeechAnalysisSlide(demo.speechAnalysis) : demo.scorecard ? createScorecardSlide(demo.scorecard) : createReportSlide(demo.reportSlide));
      } else {
        galleryTrack.replaceChildren(...galleryImages.map(([src, alt, objectPosition]) => {
          const image = document.createElement('img');
          image.src = src;
          image.alt = alt;
          if (isStyleImageGallery && objectPosition) image.style.objectPosition = objectPosition;
          image.loading = 'lazy';
          image.decoding = 'async';
          return image;
        }));
      }
    }
    updateGalleryLayout();
    if (demoKicker) demoKicker.textContent = demo.kicker;
    if (demoTitle) demoTitle.textContent = demo.title;
    if (demoSequence) {
      demoSequence.textContent = 'Slide ' + String(currentSlide + 1).padStart(2, '0') + ' / ' + String(demos.length).padStart(2, '0');
      demoSequence.hidden = !isStyleImageGallery;
    }
    dotsRoot?.querySelectorAll('button').forEach((button, index) => {
      const active = index === currentSlide;
      button.setAttribute('aria-selected', String(active));
      button.tabIndex = active ? 0 : -1;
    });
  };
  const showDemo = (index) => {
    editorialPhotoFocused = false;
    currentImageIndex = 0;
    currentSlide = (index + demos.length) % demos.length;
    renderGallery();
  };
  editorialPhotoLink?.addEventListener('click', (event) => {
    event.preventDefault();
    editorialPhotoFocused = !editorialPhotoFocused;
    currentImageIndex = 0;
    if (editorialPhotoFocused) currentSlide = 0;
    editorialPhotoLink.textContent = editorialPhotoFocused ? 'Back to Demo Slides' : 'View Editorial Photo';
    renderGallery();
    if (editorialPhotoFocused) galleryTrack?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
  previousImageButton?.addEventListener('click', () => {
    if (currentSlide > 0) showDemo(currentSlide - 1);
  });
  nextImageButton?.addEventListener('click', () => {
    if (currentSlide < demos.length - 1) showDemo(currentSlide + 1);
  });
  if (dotsRoot) {
    const dotCount = demos.length;
    dotsRoot.replaceChildren(...Array.from({ length: dotCount }, (_, index) => {
      const button = document.createElement('button');
      button.className = 'package-gallery-dot';
      button.type = 'button';
      button.setAttribute('role', 'tab');
      button.setAttribute('aria-label', 'Show demo ' + String(index + 1).padStart(2, '0'));
      if (isStyleImageGallery) button.textContent = 'Demo ' + String(index + 1).padStart(2, '0');
      button.setAttribute('aria-controls', 'booklet-demo-panel');
      button.setAttribute('aria-selected', String(index === 0));
      button.tabIndex = index === 0 ? 0 : -1;
      if (!isStyleImageGallery) button.textContent = '';
      button.addEventListener('click', () => showDemo(index));
      return button;
    }));
  }
  window.addEventListener('resize', updateGalleryLayout, { passive: true });
  renderGallery();

})();

// Keep the FAQ accordion exclusive: opening one answer closes any other open answer.
document.querySelectorAll('.package-faq-item').forEach((item) => {
  item.addEventListener('toggle', () => {
    if (!item.open) return;
    document.querySelectorAll('.package-faq-item').forEach((other) => {
      if (other !== item) other.open = false;
    });
  });
});
