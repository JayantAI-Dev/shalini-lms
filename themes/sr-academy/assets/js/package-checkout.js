(() => {
  const form = document.querySelector('[data-package-checkout]');
  const config = window.SR_THEME?.consultationCheckout;
  if (!form || !config) return;
  const status = form.querySelector('[data-booking-status]');
  const slots = form.querySelector('[data-slot-options]');
  const dateInput = form.elements.date;
  const dates = form.querySelector('[data-date-options]');
  const packageOptions = Array.from(form.querySelectorAll('[data-plan]'));
  const bookingSection = form.closest('.package-booking-section');
  const packagePriceLabel = bookingSection?.querySelector('[data-booking-price]');
  const startingPrice = Math.min(...packageOptions.map((button) => Number(button.dataset.price)).filter((price) => Number.isFinite(price) && price > 0));
  if (packagePriceLabel && Number.isFinite(startingPrice)) packagePriceLabel.textContent = 'FROM ₹' + startingPrice.toLocaleString('en-IN');
  const packageGroup = form.querySelector('.package-booking-package-group');
  const scheduleGroups = form.querySelectorAll('[data-booking-schedule]');
  const modeTabs = bookingSection?.querySelectorAll('[data-booking-mode]') || [];
  const bookingNote = form.querySelector('[data-booking-note]');
  const submitButton = form.querySelector('[type="submit"]');
  const confirmation = bookingSection?.querySelector('[data-package-confirmation]');
  const confirmationKicker = confirmation?.querySelector('[data-confirmation-kicker]');
  const confirmationTitle = confirmation?.querySelector('[data-confirmation-title]');
  const confirmationPackage = confirmation?.querySelector('[data-confirmation-package]');
  const confirmationMessage = confirmation?.querySelector('[data-confirmation-message]');
  const closeConfirmationButtons = confirmation?.querySelectorAll('[data-confirmation-close]') || [];
  let lastConfirmationTrigger = null;
  const showConfirmation = ({kicker, title, packageName, message}) => {
    if (!confirmation) { say(message); return; }
    lastConfirmationTrigger = document.activeElement;
    if (confirmationKicker) confirmationKicker.textContent = kicker;
    if (confirmationTitle) confirmationTitle.textContent = title;
    if (confirmationPackage) confirmationPackage.textContent = packageName;
    if (confirmationMessage) confirmationMessage.textContent = message;
    confirmation.hidden = false;
    document.body.classList.add('package-confirmation-open');
    confirmation.querySelector('[data-confirmation-close]')?.focus();
  };
  const hideConfirmation = () => {
    if (!confirmation || confirmation.hidden) return;
    confirmation.hidden = true;
    document.body.classList.remove('package-confirmation-open');
    lastConfirmationTrigger?.focus?.();
  };
  closeConfirmationButtons.forEach((button) => button.addEventListener('click', hideConfirmation));
  confirmation?.addEventListener('click', (event) => { if (event.target === confirmation) hideConfirmation(); });
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') hideConfirmation(); });
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
  const clearFormAfterConfirmation = () => {
    form.reset();
    selectedPlan = '';
    selectedTime = '';
    packageOptions.forEach((button) => {
      button.classList.remove('is-selected');
      button.setAttribute('aria-pressed', 'false');
    });
    dateButtons.forEach((button) => {
      button.classList.remove('is-selected');
      button.setAttribute('aria-pressed', 'false');
    });
    slots?.replaceChildren(Object.assign(document.createElement('p'), {textContent:'Select a date to load live availability.'}));
    say('');
  };
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
      const submit = submitButton;
      submit.disabled = true;
      say('Confirming your free discovery call and creating your Zoom meeting…');
      try {
        const details = Object.fromEntries(new FormData(form));
        const confirmed = await api('discovery', {
          name: details.name,
          email: details.email,
          phone: details.phone,
          date: details.date,
          time: selectedTime
        });
        if (!confirmed.confirmed) throw new Error('Your discovery call was not confirmed. Please try another available time.');
        const message = confirmed.email_sent && confirmed.team_notified && confirmed.calendar_invite_sent
          ? `Your free call is confirmed for ${confirmed.date_label}. We emailed your Zoom details and sent a Google Calendar invitation to ${details.email}. The Academy has also been notified.`
          : `Your free call is confirmed for ${confirmed.date_label}. We could not confirm delivery of every email or calendar invitation, so the Academy team will follow up. Please do not submit another booking.`;
        showConfirmation({
          kicker: 'DISCOVERY CALL CONFIRMED',
          title: 'Your free call is booked',
          packageName: confirmed.date_label || '15-minute discovery call',
          message
        });
        clearFormAfterConfirmation();
      } catch (error) {
        say(error.message);
      } finally {
        submit.disabled = false;
      }
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
          submit.disabled = false;
          const selectedPackage = packageOptions.find((button) => button.dataset.plan === selectedPlan);
          const packageName = selectedPackage?.querySelector('span')?.textContent.trim() || confirmed.service || 'Your package';
          const emailMessage = confirmed.email_sent && confirmed.team_notified
            ? `A confirmation email was sent to ${details.email}, and the Academy team was notified. We will contact you within 48 hours to help you get started.`
            : 'Your payment is confirmed, but delivery of every confirmation email could not be verified. The Academy team will follow up. Please do not pay again.';
          showConfirmation({
            kicker: 'PAYMENT CONFIRMED',
            title: 'Thank you for your purchase!',
            packageName,
            message: emailMessage
          });
          clearFormAfterConfirmation();
        } catch (error) { say(error.message); submit.disabled = false; }
      }, modal:{ondismiss:() => { say('Checkout closed. No purchase was confirmed.'); submit.disabled = false; }} });
      checkout.on('payment.failed', () => { say('Test payment failed. No purchase was confirmed.'); submit.disabled = false; }); checkout.open();
    } catch (error) { say(error.message); submit.disabled = false; }
  });
})();
