(() => {
  const form = document.querySelector('[data-package-checkout]');
  const config = window.SR_THEME?.consultationCheckout;
  if (!form || !config) return;
  const status = form.querySelector('[data-booking-status]');
  const slots = form.querySelector('[data-slot-options]');
  const dateInput = form.elements.date;
  const dates = form.querySelector('[data-date-options]');
  const emailInput = form.elements.email;
  const packageOptions = Array.from(form.querySelectorAll('[data-plan]'));
  const bookingSection = form.closest('.package-booking-section');
  const packagePriceLabel = bookingSection?.querySelector('[data-booking-price]');
  if (packagePriceLabel) packagePriceLabel.textContent = 'FROM ₹49,999';
  const purchasedPackageStorageKey = 'sr-academy-package-purchases-v1';
  let serverPurchasedPackages = new Set();
  const packageGroup = form.querySelector('.package-booking-package-group');
  const scheduleGroups = form.querySelectorAll('[data-booking-schedule]');
  const modeTabs = bookingSection?.querySelectorAll('[data-booking-mode]') || [];
  const bookingNote = form.querySelector('[data-booking-note]');
  const submitButton = form.querySelector('[type="submit"]');
  let bookingMode = 'package';
  let selectedPlan = '';
  let selectedTime = '';
  const readJsonResponse = async (response) => {
    const body = await response.text();
    const firstObject = body.indexOf('{');
    const lastObject = body.lastIndexOf('}');
    if (firstObject < 0 || lastObject < firstObject) throw new Error('The booking service returned an invalid response. Please try again or contact the Academy.');
    try { return JSON.parse(body.slice(firstObject, lastObject + 1)); }
    catch (_) { throw new Error('The booking service returned an invalid response. Please try again or contact the Academy.'); }
  };
  document.addEventListener('click', (event) => {
    const choice = event.target.closest('[data-comparison-plan], [data-discovery-booking]');
    if (!choice) return;
    event.preventDefault(); event.stopImmediatePropagation();
    const plan = choice.dataset.comparisonPlan;
    if (choice.hasAttribute('data-discovery-booking')) setBookingMode('discovery');
    if (plan) {
      const button = Array.from(document.querySelectorAll('[data-plan]')).find((item) => item.textContent.trim().startsWith(plan));
      button?.click();
    }
    document.querySelector('#package-booking')?.scrollIntoView({behavior:'smooth',block:'start'});
  }, true);
  const say = (message) => { status.textContent = message; };
  const setBookingMode = (mode) => {
    bookingMode = mode;
    modeTabs.forEach((tab) => {
      const active = tab.dataset.bookingMode === mode;
      tab.classList.toggle('is-selected', active);
      tab.setAttribute('aria-selected', String(active));
    });
    if (packageGroup) packageGroup.hidden = mode !== 'package';
    scheduleGroups.forEach((group) => { group.hidden = mode !== 'discovery'; });
    dateInput.required = mode === 'discovery';
    if (bookingNote) bookingNote.textContent = mode === 'package' ? 'Razorpay test mode · no live charge' : 'Secure reservation · Free 24h reschedule';
    if (submitButton) submitButton.textContent = mode === 'package' ? 'Continue to test payment' : 'CONFIRM FREE DISCOVERY';
    say('');
    if (mode === 'discovery') {
      if (!dateInput.value) dateButtons[0]?.click();
      else dateInput.dispatchEvent(new Event('change', {bubbles:true}));
    }
  };
  modeTabs.forEach((tab) => tab.addEventListener('click', () => setBookingMode(tab.dataset.bookingMode)));
  const api = async (path, body) => {
    const response = await fetch(config.api + path, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) });
    const data = await readJsonResponse(response);
    if (!response.ok) throw new Error(data.message || 'We could not complete your request. Please try again.');
    return data;
  };
  const readPurchasedPackages = () => {
    try {
      const data = JSON.parse(localStorage.getItem(purchasedPackageStorageKey) || '{}');
      return data && typeof data === 'object' && !Array.isArray(data) ? data : {};
    } catch (_) { return {}; }
  };
  const rememberPurchasedPackage = (email, plan) => {
    const key = email.trim().toLowerCase();
    if (!key || !plan) return;
    try {
      const data = readPurchasedPackages();
      const plans = Array.isArray(data[key]) ? data[key] : [];
      data[key] = Array.from(new Set([...plans, plan]));
      localStorage.setItem(purchasedPackageStorageKey, JSON.stringify(data));
    } catch (_) {}
  };
  const refreshPurchasedPackages = () => {
    const email = emailInput.value.trim().toLowerCase();
    const stored = readPurchasedPackages();
    const purchased = new Set([...(Array.isArray(stored[email]) ? stored[email] : []), ...serverPurchasedPackages]);
    packageOptions.forEach((button) => {
      const isPurchased = Boolean(email) && purchased.has(button.dataset.plan);
      button.disabled = isPurchased;
      let label = button.querySelector('[data-purchased-label]');
      if (isPurchased && !label) {
        label = document.createElement('span');
        label.dataset.purchasedLabel = '';
        label.textContent = 'Purchased';
        button.append(label);
      }
      if (label) label.hidden = !isPurchased;
      if (isPurchased && selectedPlan === button.dataset.plan) {
        selectedPlan = '';
        button.classList.remove('is-selected');
        button.setAttribute('aria-pressed', 'false');
      }
    });
  };
  let packageLookupTimer = 0;
  let packageLookupRequest = 0;
  const lookupPurchasedPackages = () => {
    window.clearTimeout(packageLookupTimer);
    serverPurchasedPackages = new Set();
    refreshPurchasedPackages();
    const email = emailInput.value.trim();
    const phone = form.elements.phone.value.trim();
    const digits = phone.replace(/\D/g, '');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || digits.length < 7) return;
    const requestId = ++packageLookupRequest;
    packageLookupTimer = window.setTimeout(async () => {
      try {
        const data = await api('purchases', {email, phone});
        if (requestId !== packageLookupRequest || emailInput.value.trim().toLowerCase() !== email.toLowerCase() || form.elements.phone.value.trim() !== phone) return;
        serverPurchasedPackages = new Set(Array.isArray(data.offers) ? data.offers : []);
        refreshPurchasedPackages();
      } catch (_) {}
    }, 300);
  };
  emailInput.addEventListener('change', lookupPurchasedPackages);
  form.elements.phone.addEventListener('change', lookupPurchasedPackages);
  lookupPurchasedPackages();
  packageOptions.forEach((button) => button.addEventListener('click', () => {
    if (button.disabled) return;
    selectedPlan = button.dataset.plan;
    document.querySelectorAll('[data-plan]').forEach((item) => { item.classList.toggle('is-selected', item === button); item.setAttribute('aria-pressed', item === button ? 'true' : 'false'); });
    const packageTab = Array.from(modeTabs).find((tab) => tab.dataset.bookingMode === 'package');
    const amount = Number(button.dataset.price);
    if (packageTab && Number.isFinite(amount)) { const priceLabel = packageTab.querySelector('[data-booking-price]'); if (priceLabel) priceLabel.textContent = '₹' + amount.toLocaleString('en-IN'); else packageTab.textContent = '1:1 PACKAGE · ₹' + amount.toLocaleString('en-IN'); }
  }));
  const dateKey = (date) => {
    const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {timeZone:'Asia/Kolkata', year:'numeric', month:'2-digit', day:'2-digit'}).formatToParts(date).map((part) => [part.type, part.value]));
    return `${parts.year}-${parts.month}-${parts.day}`;
  };
  const dateLabel = (date) => new Intl.DateTimeFormat('en-IN', {timeZone:'Asia/Kolkata', weekday:'long', day:'numeric', month:'short'}).format(date);
  const today = new Date();
  const dateButtons = [];
  for (let offset = 1; offset <= 4; offset++) {
    const date = new Date(today.getTime() + offset * 86400000);
    const key = dateKey(date);
    const button = document.createElement('button');
    button.type = 'button'; button.className = 'package-booking-option'; button.textContent = dateLabel(date);
    button.setAttribute('aria-pressed', 'false');
    button.addEventListener('click', () => {
      dateInput.value = key;
      dateButtons.forEach((item) => { item.classList.toggle('is-selected', item === button); item.setAttribute('aria-pressed', item === button ? 'true' : 'false'); });
      dateInput.dispatchEvent(new Event('change', {bubbles:true}));
    });
    dates.append(button); dateButtons.push(button);
  }
  dateInput.min = dateKey(today);
  dateInput.max = dateKey(new Date(today.getTime() + 180 * 86400000));
  dateInput.addEventListener('change', async () => {
    if (bookingMode !== 'discovery') return;
    selectedTime = '';
    slots.replaceChildren(Object.assign(document.createElement('p'), {className:'package-booking-unavailable', textContent:'Checking live availability…'}));
    try {
      const url = new URL(window.SR_THEME.calendarAvailabilityUrl);
      url.searchParams.set('date', dateInput.value); url.searchParams.set('duration', bookingMode === 'discovery' ? '15' : '60');
      const response = await fetch(url);
      const data = await readJsonResponse(response);
      if (!response.ok) throw new Error(data.message || 'Calendar availability is unavailable.');
      slots.replaceChildren();
      data.slots.filter((slot) => slot.available).forEach((slot) => {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'package-booking-option'; button.textContent = slot.start + ' IST';
        button.setAttribute('aria-pressed', 'false');
        button.addEventListener('click', () => { selectedTime = slot.start; slots.querySelectorAll('button').forEach((item) => { item.classList.toggle('is-selected', item === button); item.setAttribute('aria-pressed', item === button ? 'true' : 'false'); }); }); slots.append(button);
      });
      if (!slots.childElementCount) slots.textContent = 'No times are available on this date. Choose another date.';
    } catch (_) { slots.replaceChildren(Object.assign(document.createElement('p'), {className:'package-booking-unavailable', textContent:'Live availability could not be loaded. Please try another date or contact the Academy.'})); }
  });
  setBookingMode('package');
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    if (bookingMode === 'discovery') {
      if (!selectedTime) { say('Choose an available time before continuing.'); return; }
      say('Your free discovery call request is ready. The team will follow up to confirm availability.');
      return;
    }
    if (!config.enabled || !config.test) { say('Test checkout is unavailable. Please contact the Academy.'); return; }
    if (!selectedPlan) { say('Choose a package before continuing.'); return; }
    const submit = submitButton; submit.disabled = true; say('Preparing secure test checkout…');
    try {
      const details = Object.fromEntries(new FormData(form));
      const order = await api('order', {offer:selectedPlan, duration:0, name:details.name, email:details.email, phone:details.phone});
      if (!order.test || !window.Razorpay) throw new Error('Razorpay test checkout did not initialize. No payment was made.');
      const checkout = new Razorpay({key:order.key, order_id:order.order_id, amount:order.amount, currency:order.currency, name:order.name, description:order.description, prefill:{name:details.name,email:details.email,contact:details.phone}, theme:{color:'#194c3b'}, handler:async (payment) => {
        try {
          say('Verifying your test payment…');
          const confirmed = await api('verify', payment);
          if (!confirmed.confirmed) throw new Error('Payment is not confirmed yet. Please contact the Academy before retrying.');
          rememberPurchasedPackage(details.email, selectedPlan);
          refreshPurchasedPackages();
          say('Payment confirmed. This package is marked as purchased. Our team will contact you within 48 hours.');
        } catch (error) { say(error.message); submit.disabled = false; }
      }, modal:{ondismiss:() => { say('Checkout closed. No purchase was confirmed.'); submit.disabled = false; }} });
      checkout.on('payment.failed', () => { say('Test payment failed. No purchase was confirmed.'); submit.disabled = false; }); checkout.open();
    } catch (error) { say(error.message); submit.disabled = false; }
  });
})();
