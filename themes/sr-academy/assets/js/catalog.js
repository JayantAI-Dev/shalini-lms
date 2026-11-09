/** Public course presentation only. Authentication, access, media and progress are owned by the LMS plugin. */
(() => {
  'use strict';
  const app = document.querySelector('.lms-shell');
  if (!app) return;
  const data = window.SHALINI_COURSES || {};
  const params = new URLSearchParams(window.location.search);
  if (params.has('course') && !Object.prototype.hasOwnProperty.call(data, params.get('course'))) {
    const title = document.getElementById('lms-detail-title');
    if (title) { title.textContent='Course unavailable'; }
    document.querySelectorAll('#lms-enroll-cta,#lms-curriculum-enroll,#lms-certificate-cta').forEach(button=>{button.hidden=true;});
    const note=document.getElementById('lms-detail-note');
    if (note) { note.textContent='This course could not be found. Please return to the course catalog.'; }
    return;
  }
  const slug = Object.prototype.hasOwnProperty.call(data, params.get('course')) ? params.get('course') : 'speakathon';
  const course = data[slug];
  const $ = selector => document.querySelector(selector);
  const el = (tag, className, content) => {const node=document.createElement(tag);if(className)node.className=className;if(content!=null)node.textContent=content;return node;};
  const flatten = record => (record.modules || []).flatMap((m,i) => m.lessons.map((lesson,j)=>({...lesson,group:i,module:m.title,position:j})));
  const formatMoney = n => '₹' + Number(n).toLocaleString('en-IN');
  const updateUrl = (filename, id) => window.SR_THEME.url(filename+'?course='+encodeURIComponent(id));
  // A few course-specific, honest demo learning outcomes and bonus notes.
  const outcomeCopy = {
    speakathon:['Articulate your thoughts more clearly','Use vocal variety with intention','Communicate with clarity & calm','Handle conversations with greater ease','Leave an authentic, lasting impression'],
    'power-dressing':['Discover your most flattering colour palette and silhouettes.','Cultivate a wardrobe that reflects your individuality.','Master the art of styling what you already own.','Invest in timeless pieces with greater discernment.','Create a cohesive personal image, down to the finest detail.'],
    'dining-etiquette':['Feel at ease at professional and social meals','Understand place settings and cutlery','Make introductions and join conversations comfortably','Host or attend meals with thoughtful manners'],
    'power-bundle':['Work on confident speaking habits','Develop an authentic personal style','Bring clarity to how you present yourself','Practice verbal and visual communication','Build a repeatable growth routine']
  };
  const outcomeEmphasis = {
    speakathon:['Articulate','vocal variety','clarity & calm','Handle conversations','lasting impression'],
    'power-dressing':[
      ['colour palette and silhouettes.'],
      ['Cultivate a wardrobe'],
      ['art of styling'],
      ['timeless pieces'],
      ['Create a cohesive personal image']
    ]
  };
  const bonusCopy = {
    speakathon:[['Personal 1:1 Coaching Session','Complete all 21 challenge assignments and unlock an exclusive one-on-one session to receive personalised guidance on your communication goals.'],['25 Power Moves to Influence Anyone','A practical swipe file of simple communication techniques you can use immediately in conversations, meetings, interviews, and presentations.'],['Blueprint for Unshakable Confidence','A step-by-step framework to help you overcome self-doubt, speak with conviction, and build lasting confidence.'],['Introvert2Influencer Digital Handbook',"Shalini's personal guide packed with actionable insights, exercises, and communication principles to support your journey beyond the 21-day challenge."]],
    'power-dressing':[
      ['Complimentary 1:1 Styling Session','Receive personalised guidance to apply what you learn to your own style.'],
      ['7 Comprehensive Module Workbooks','Reinforce every lesson with structured exercises and practical applications.'],
      ['3 Curated Study Resources','Deepen your understanding with carefully selected supplementary learning materials.'],
      ['Real-Life Styling Demonstrations','Watch Shalini demonstrate techniques discussed throughout the course modules.'],
      ['Early Bird Access to Dining Etiquette','Explore refined dining etiquette before its official release.']
    ],
    'dining-etiquette':[['Dining occasion checklist','A simple prompt to prepare for a professional or social meal.'],['Conversation cues','Suggestions for comfortable introductions and table conversation.'],['Hosting notes','A practical checklist for welcoming guests and planning a meal.']],
    'power-bundle':[['Two learning themes','Explore communication and image-building principles together.'],['Combined practice prompts','Bring spoken confidence and visual presentation into everyday routines.'],['Reflection framework','Review what you have tried and choose the next practical step.']]
  };

  // Catalog: real client-side category filters + text search. Accessible pressed states.
  const grid = $('#lms-catalog-grid');
  if (grid) {
    let selected = 'all';
    const search = $('#lms-course-search');
    const buttons = [...document.querySelectorAll('[data-lms-filter]')];
    const cards = [...grid.querySelectorAll('.lms-course-tile')];
    const filter = () => {
      const q = (search?.value || '').trim().toLocaleLowerCase();
      let visible = 0;
      cards.forEach(card => {
        const categoryOK = selected === 'all' || card.dataset.category === selected;
        const textOK = !q || (card.dataset.search + ' ' + card.textContent).toLocaleLowerCase().includes(q);
        const match = categoryOK && textOK;
        card.hidden = !match;
        if(match) visible++;
      });
      const empty = $('#lms-empty'); if (empty) empty.hidden = visible !== 0;
    };
    buttons.forEach(button => button.addEventListener('click', () => {
      selected = button.dataset.lmsFilter;
      buttons.forEach(b => {b.classList.toggle('is-selected',b===button);b.setAttribute('aria-pressed',String(b===button));});
      filter();
    }));
    search?.addEventListener('input',filter);
    filter();
  }

  // Dedicated course pages share one template; the content is unique per selected course.
  if ($('#lms-detail-title')) {
    if(!course)return;
    const certificateSection=$('#lms-certificate');
    if(certificateSection)certificateSection.hidden=!['speakathon','power-dressing'].includes(slug);
    document.title=course.title+' | Shalini Rathod Academy';
    const canonicalUrl=updateUrl('lms-course.html',slug);
    const courseImagePath=slug==='power-dressing'?(window.SR_THEME.assetBase + 'assets/services/power-dressing-cover.webp'):(slug==='power-bundle'||slug==='dining-etiquette')?(window.SR_THEME.assetBase + 'assets/services/course-bundle.webp'):(window.SR_THEME.assetBase + 'assets/services/communication-workshop.webp');
    const courseImageUrl=new URL(courseImagePath,window.location.href).href;
    const setMeta=(selector,value)=>document.querySelector(selector)?.setAttribute('content',value);
    document.querySelector('link[rel="canonical"]')?.setAttribute('href',canonicalUrl);
    setMeta('meta[name="description"]',course.description);
    setMeta('meta[property="og:title"]',document.title);
    setMeta('meta[property="og:description"]',course.description);
    setMeta('meta[property="og:url"]',canonicalUrl);
    setMeta('meta[property="og:image"]',courseImageUrl);
    setMeta('meta[property="og:image:alt"]',course.title+' course preview');
    setMeta('meta[name="twitter:title"]',document.title);
    setMeta('meta[name="twitter:description"]',course.description);
    setMeta('meta[name="twitter:image"]',courseImageUrl);
    const courseSchema=$('#course-structured-data');
    if(courseSchema)courseSchema.textContent=JSON.stringify({'@context':'https://schema.org','@type':'Course',name:course.title,description:course.description,provider:{'@type':'Organization',name:'Shalini Rathod Confidence & Image Academy',url:window.SR_THEME.home},courseMode:'Online'});
    $('#lms-detail-title').textContent=course.title;
    $('#lms-breadcrumb-name').textContent=slug==='speakathon'?'Communication Course':course.title;
    $('#lms-detail-summary').textContent=course.description;
    $('#lms-detail-price').textContent=formatMoney(course.price);
    const total=flatten(course).length;
    $('#lms-detail-count').textContent=(slug==='speakathon'?'21 guided challenges':total+' course topics');
    const detailRating=$('#lms-detail-rating');
    const detailLearners=$('#lms-detail-learners');
    const detailSession=$('#lms-detail-session');
    const detailMode=$('#lms-detail-mode');
    const detailNote=$('#lms-detail-note');
    if(slug==='speakathon'){
      detailRating.replaceChildren(el('span','lms-rating-star','★'),document.createTextNode(' 4.9 Rating'));
      detailLearners.textContent='100+ learners';
      detailLearners.hidden=false;
      detailSession.textContent='Free 1:1 session included';
      detailSession.hidden=false;
      detailMode.hidden=true;
      detailNote.textContent='Continue to enrollment for available purchase and access options.';
    }
    $('#lms-detail-category').textContent=slug==='speakathon'?'21-DAY COMMUNICATION CHALLENGE':slug==='power-dressing'?'PERSONAL IMAGE & STYLE':slug==='dining-etiquette'?'DINING ETIQUETTE':'COMMUNICATION + STYLE BUNDLE';
    const image=$('#lms-detail-photo');
    image.src=courseImagePath;
    image.alt=course.title+' course preview image';
    $('#lms-enroll-cta').href=updateUrl('lms-auth.html',slug);
    $('#lms-syllabus-player')?.setAttribute('href',updateUrl('lms-learn.html',slug));
    const enrollmentUrl=window.SR_THEME.url('package-enroll.html?course='+encodeURIComponent(slug));
    $('#lms-enroll-cta').href=enrollmentUrl;
    $('#lms-enroll-cta').textContent='Enroll in course';
    $('#lms-curriculum-enroll')?.addEventListener('click',()=>{window.location.href=enrollmentUrl;});
    $('#lms-certificate-cta')?.setAttribute('href',enrollmentUrl);
    if (slug === 'dining-etiquette') {
      const previewSection=$('#lms-previews');
      const previewHeading=previewSection?.querySelector('.lms-section-heading h2');
      const previewDescription=previewSection?.querySelector('.lms-section-heading p');
      const previewGrid=previewSection?.querySelector('.lms-preview-grid');
      if(previewHeading)previewHeading.textContent='A little more ease at every table.';
      if(previewDescription)previewDescription.textContent='Explore the Dining Etiquette course outline. Lesson recordings will be added as they become available.';
      if(previewGrid){
        previewGrid.replaceChildren(...flatten(course).slice(0,2).map((lesson,i)=>{
          const card=el('article','lms-preview-card');
          const visual=el('div','lms-preview-portrait lms-topic-preview',String(i+1).padStart(2,'0'));
          visual.setAttribute('aria-hidden','true');
          const copy=el('div','lms-preview-copy');
          copy.append(el('span','lms-eyebrow','DINING ETIQUETTE · COURSE OUTLINE'),el('h3','',lesson.title.replace(' · Lesson awaiting upload','')),el('p','',lesson.summary),el('div','lms-preview-tags'));
          copy.querySelector('.lms-preview-tags').append(el('span','','Course topic'),el('span','','Self-paced'));
          card.append(visual,copy);
          return card;
        }));
      }
      const firstButton=$('.lms-buttons .lms-secondary');
      if(firstButton)firstButton.textContent='Explore course topics ↓';
      const curriculumSection=$('#lms-curriculum-section');
      const curriculumTitle=curriculumSection?.querySelector('.lms-curriculum-grid h2');
      const curriculumDescription=curriculumSection?.querySelector('.lms-curriculum-grid > div > p');
      const curriculumNumber=curriculumSection?.querySelector('.lms-curriculum-number');
      const curriculumLabel=curriculumSection?.querySelector('.lms-curriculum-aside > span');
      if(curriculumTitle)curriculumTitle.textContent='Your dining etiquette learning path.';
      if(curriculumDescription)curriculumDescription.textContent='Browse the course topics. Lesson recordings will be added as they become available.';
      if(curriculumNumber)curriculumNumber.textContent=String(total);
      if(curriculumLabel)curriculumLabel.textContent='COURSE TOPICS';
    }
    if (slug === 'power-dressing') {
      const detailTitle=$('#lms-detail-title');
      if(detailTitle)detailTitle.textContent=course.title;
      detailTitle?.nextElementSibling?.classList.contains('lms-course-lead')&&detailTitle.nextElementSibling.remove();
      $('#lms-detail-summary').textContent='Discover what complements you and make thoughtful choices that bring lasting ease and elegance to your personal style.';
      const detailBadges=$('.lms-detail-badges');
      if(detailBadges){
        detailBadges.replaceChildren(...[
          '4.9-Star Learner Rating',
          '100+ Learners',
          'Exclusive Study Resources',
          'Complimentary 1:1 Styling Session'
        ].map(text=>el('span','',text)));
      }
      const certificateCopy=certificateSection?.querySelector('.lms-certificate-copy');
      if(certificateCopy){
        const certificateTitle=certificateCopy.querySelector('h2');
        const certificateDescription=certificateCopy.querySelector('p');
        const certificatePoints=certificateCopy.querySelector('.lms-certificate-points');
        if(certificateTitle)certificateTitle.innerHTML='Acknowledging Your Eye for <em>Personal Style</em>';
        if(certificateDescription)certificateDescription.textContent='Complete your learning journey with a certificate recognising your expertise in image styling.';
        if(certificatePoints)certificatePoints.replaceChildren(...['Personalised with your name','Course and completion date included','Print or save as a PDF'].map(text=>el('li','',text)));
      }
      const certificateImage=certificateSection?.querySelector('.lms-certificate-preview');
      if(certificateImage){
        certificateImage.hidden=false;
        const certificateImg=certificateImage.querySelector('img');
        if(certificateImg){
          certificateImg.src=(window.SR_THEME.assetBase + 'assets/lms/power-dressing-certificate.png');
          certificateImg.alt='Power Dressing / Styling certificate of completion from Shalini Rathod Academy';
          certificateImg.width=1483;
          certificateImg.height=1061;
        }
        const certificateGrid=certificateSection.querySelector('.lms-certificate-grid');
        if(certificateGrid)certificateGrid.style.gridTemplateColumns='';
      }
      const certificateCta=$('#lms-certificate-cta');
      if(certificateCta)certificateCta.innerHTML='Start Power Dressing / Styling <span aria-hidden="true">→</span>';
      const previewSection=$('#lms-previews');
      const previewHeading=previewSection?.querySelector('.lms-section-heading h2');
      const previewDescription=previewSection?.querySelector('.lms-section-heading p');
      const previewGrid=previewSection?.querySelector('.lms-preview-grid');
      if(previewHeading)previewHeading.innerHTML='A Glimpse Into Your <em>Learning Experience</em>';
      if(previewDescription)previewDescription.textContent='Explore select lesson previews to experience the practical value of the course.';
      const previews=[
        {title:'Color, Cut & Fit',description:'Explore flattering color palettes, outfit proportions and fit to make more confident style choices.'},
        {title:'Build a Versatile Capsule Wardrobe',description:'Preview how a 30-piece wardrobe can bring together practical outfits for different occasions.'}
      ];
      if(previewGrid){
        previewGrid.replaceChildren(...previews.map((preview,i)=>{
          const card=el('article','lms-preview-card');
          const visual=el('div','lms-preview-portrait');
          const thumbnail=el('img');
          thumbnail.src=(window.SR_THEME.assetBase + 'assets/lms/previews/')+(i===0?'speak-confidently-thumbnail.jpg':'confident-smile-thumbnail.jpg');
          thumbnail.alt=i===0?'Speakathon communication course preview thumbnail':'Communication course confidence preview thumbnail';
          thumbnail.loading='lazy';
          visual.append(thumbnail);
          const copy=el('div','lms-preview-copy');
          copy.append(el('span','lms-eyebrow','POWER DRESSING / STYLING'),el('h3','',preview.title),el('p','',preview.description),el('div','lms-preview-tags'));
          const tags=i===0?['Color Analysis','Right Fit']:['Clear guidance','Practical Tips'];
          copy.querySelector('.lms-preview-tags').append(...tags.map(text=>el('span','',text)));
          card.append(visual,copy);
          return card;
        }));
      }
      const previewButton=$('.lms-buttons .lms-secondary');
      if(previewButton)previewButton.textContent='See course preview ↓';
      const purchaseEyebrow=$('.lms-purchase-content > .lms-eyebrow');
      const purchaseDescription=$('.lms-purchase-content > p');
      if(purchaseEyebrow)purchaseEyebrow.textContent='COURSE PRICE';
      if(purchaseDescription)purchaseDescription.textContent='Build a more versatile wardrobe with practical, self-paced style lessons.';
      $('#lms-enroll-cta').textContent='Enroll in Course';
      detailRating.hidden=true;
      detailNote.textContent='Self-paced course · Lesson recordings appear as they are published.';
      const takeaways=$('.lms-benefits-grid h2');
      const bonusTitle=$('.lms-bonus-panel h3');
      if(takeaways)takeaways.innerHTML='Build a more intentional <em>personal style</em>';
      if(bonusTitle)bonusTitle.textContent='Practical Style Resources';
      const curriculumSection=$('#lms-curriculum-section');
      const curriculumTitle=curriculumSection?.querySelector('.lms-curriculum-grid h2');
      const curriculumDescription=curriculumSection?.querySelector('.lms-curriculum-grid > div > p');
      const curriculumNumber=curriculumSection?.querySelector('.lms-curriculum-number');
      const curriculumLabel=curriculumSection?.querySelector('.lms-curriculum-aside > span');
      const curriculumAsideTitle=curriculumSection?.querySelector('.lms-curriculum-aside h3');
      const curriculumAsideCopy=curriculumSection?.querySelector('.lms-curriculum-aside p');
      if(curriculumTitle)curriculumTitle.innerHTML='Power Dressing / Styling <em>Modules</em>';
      if(curriculumDescription)curriculumDescription.textContent='Explore color, cut, fit, body proportion and capsule wardrobe planning. Lesson recordings appear as they are published.';
      if(curriculumNumber)curriculumNumber.textContent=String(total);
      if(curriculumLabel)curriculumLabel.textContent='STYLE TOPICS';
      if(curriculumAsideTitle)curriculumAsideTitle.textContent='Build a wardrobe with more intention.';
      if(curriculumAsideCopy)curriculumAsideCopy.textContent='Start with practical lessons on color, fit and versatile wardrobe planning.';
      const reviewSection=document.querySelector('.lms-review-grid')?.closest('section');
      const reviewTitle=reviewSection?.querySelector('.lms-section-heading h2');
      if(reviewTitle)reviewTitle.innerHTML='Learner <em>Testimonials</em>';
      const styleReviewPeople=[
        {name:'Ananya S.',image:window.SR_THEME.asset("assets/external/793db49e138bad64.webp")},
        {name:'Sana P.',image:window.SR_THEME.asset("assets/external/fdb930b0b66c5823.webp")},
        {name:'Ishita D.',image:window.SR_THEME.asset("assets/external/91729bce3d0699f3.webp")}
      ];
      reviewSection?.querySelectorAll('.lms-review').forEach((review,i)=>{
        const person=styleReviewPeople[i];
        if(!person)return;
        const image=review.querySelector('.lms-review-person img');
        const name=review.querySelector('.lms-review-person strong');
        if(image){image.src=person.image;image.alt='Portrait of '+person.name;}
        if(name)name.textContent=person.name;
      });
      const styleTestimonials=[
        'Finallyyy… I understand what works for me instead of randomly buying clothes and hoping they look good. Getting dressed for work has become so much easier!',
        'I started paying attention to little things like fit, colours, and accessories, and honestly, they make such a difference. I feel much more put-together now!',
        'Used to think I needed a whole new wardrobe to improve my style, but this course changed that. I’ve learnt to style what I already own in ways that feel much more like me.'
      ];
      reviewSection?.querySelectorAll('.lms-review p').forEach((quote,i)=>{
        if(styleTestimonials[i])quote.textContent=styleTestimonials[i];
      });
    }

    if (slug === 'speakathon') {
      const detailTitle=$('#lms-detail-title');
      if(detailTitle)detailTitle.textContent=course.title;
      [...document.querySelectorAll('.lms-preview-card')].slice(0,2).forEach((card,i)=>{
        const frame=card.querySelector('.lms-preview-portrait');
        if (frame) {
          const video=el('video','lms-preview-real-video');
          video.controls=true;video.playsInline=true;video.preload='none';video.hidden=true;
          const imagePath=(window.SR_THEME.assetBase + 'assets/lms/previews/')+(i===0?'speak-confidently-thumbnail.jpg':'confident-smile-thumbnail.jpg');
          video.poster=imagePath;
          video.setAttribute('aria-label',i===0?'Day 1 excerpt from 00:26 to 01:05':'Day 4 excerpt from 00:05 to 00:19');
          const source=el('source');source.src=(window.SR_THEME.assetBase + 'assets/lms/previews/day-0')+(i===0?'1':'4')+'-excerpt.mp4';source.type='video/mp4';
          video.append(source,document.createTextNode('Your browser does not support HTML video.'));
          const cover=el('button','lms-preview-cover');cover.type='button';
          cover.setAttribute('aria-label',i===0?'Play Speak Confidently on Camera preview':'Play Look More Confident preview');
          const image=el('img');image.src=imagePath;image.alt='';image.loading='lazy';
          const play=el('span','lms-preview-cover-play','▶');play.setAttribute('aria-hidden','true');
          cover.append(image,play);
          cover.addEventListener('click',()=>{cover.remove();video.hidden=false;video.play().catch(()=>{});});
          frame.replaceChildren(video,cover);
        }
        const title=card.querySelector('h3');if(title)title.textContent=i===0?'Speak Confidently in front of the Camera':'The Easiest Way to Look More Confident';
        const desc=card.querySelector('p');if(desc)desc.textContent=i===0?'Learn a simple exercise that helps you stop overthinking before speaking.':'See how a genuine smile can help you build trust, create stronger first impressions, and connect effortlessly.';
        const eyebrow=card.querySelector('.lms-eyebrow');if(eyebrow)eyebrow.textContent=i===0?'PREVIEW LESSON 1 · CHALLENGE 1':'PREVIEW LESSON 2 · CHALLENGE 4';
        const tags=card.querySelector('.lms-preview-tags');if(tags)tags.replaceChildren(...(i===0?[el('span','','3-minute lesson'),el('span','','Actionable Plan included')]:[el('span','','😊 Body language tip'),el('span','','⚡ Easy to apply')]));
        const link=el('a','lms-asset-link lms-asset-primary','View lesson '+(i===0?1:4));
        link.href=updateUrl('lms-course.html',slug)+'#course-purchase';
        card.querySelector('.lms-preview-copy')?.append(link);
      });
    }

    const ul=$('#lms-inclusions');(course.includes||[]).forEach(x=>ul.append(el('li','',x)));
    const benefits=$('#lms-outcomes');(outcomeCopy[slug]||[]).forEach((x,i)=>{
      const item=el('div');const rawPhrases=(outcomeEmphasis[slug]||[])[i];const phrases=Array.isArray(rawPhrases)?rawPhrases:rawPhrases?[rawPhrases]:[];
      let cursor=0;
      phrases.map(phrase=>({phrase,start:x.indexOf(phrase,cursor)})).filter(match=>match.start>=cursor).forEach(({phrase,start})=>{
        item.append(document.createTextNode(x.slice(cursor,start)),el('em','',phrase));
        cursor=start+phrase.length;
      });
      item.append(document.createTextNode(x.slice(cursor)));
      benefits.append(item);
    });
    const bonuses=$('#lms-bonuses');(bonusCopy[slug]||[]).forEach(([title,desc],i)=>{
      const item=el('details');if(i===0)item.open=true;
      item.addEventListener('toggle',()=>{
        if(!item.open)return;
        bonuses.querySelectorAll('details[open]').forEach(other=>{if(other!==item)other.open=false;});
      });
      item.append(el('summary','',title),el('p','',desc));bonuses.append(item);
    });
    const curr=$('#lms-curriculum');
    course.modules.forEach((m,i)=>{
      const block=el('details');block.open=i===0;
      const summary=el('summary');const text=el('span','',m.title);text.append(el('small','',m.lessons.length+' lesson'+(m.lessons.length===1?'':'s')));
      summary.append(text);block.append(summary);
      m.lessons.forEach(lesson=>{
        const line=el('div','lms-lesson-item');
        line.append(el('strong','',lesson.title.replace(/\s*· Lesson awaiting upload$/,'')),el('span','',lesson.summary?'PRACTICE':'OUTLINE'));
        block.append(line);
      });curr.append(block);
    });
    $('#lms-expand-all')?.addEventListener('click',()=>curr.querySelectorAll('details').forEach(d=>d.open=true));
    $('#lms-collapse-all')?.addEventListener('click',()=>curr.querySelectorAll('details').forEach(d=>d.open=false));
  }


})();
