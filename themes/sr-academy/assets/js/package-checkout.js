(() => {
  const form = document.querySelector('[data-package-checkout]');
  const config = window.SR_THEME?.consultationCheckout;
  if (!form || !config) return;
  const status = form.querySelector('[data-booking-status]');
  const slots = form.querySelector('[data-slot-options]');
  const dateInput = form.elements.date;
  const success = document.querySelector('[data-booking-success]');
  let selectedPlan = '';
  let selectedTime = '';
  document.addEventListener('click', (event) => {
    const choice = event.target.closest('[data-comparison-plan], [data-discovery-booking]');
    if (!choice) return;
    event.preventDefault(); event.stopImmediatePropagation();
    const plan = choice.dataset.comparisonPlan;
    if (plan) {
      const button = Array.from(document.querySelectorAll('[data-plan]')).find((item) => item.textContent.trim().startsWith(plan));
      button?.click();
    }
    document.querySelector('#package-booking')?.scrollIntoView({behavior:'smooth',block:'start'});
  }, true);
  const say = (message) => { status.textContent = message; };
  const api = async (path, body) => {
    const response = await fetch(config.api + path, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'We could not complete your request. Please try again.');
    return data;
  };
  document.querySelectorAll('[data-plan]').forEach((button) => button.addEventListener('click', () => {
    selectedPlan = button.dataset.plan;
    document.querySelectorAll('[data-plan]').forEach((item) => { item.classList.toggle('is-selected', item === button); item.setAttribute('aria-pressed', item === button ? 'true' : 'false'); });
  }));
  dateInput.min = new Date().toISOString().slice(0, 10);
  dateInput.max = new Date(Date.now() + 180 * 86400000).toISOString().slice(0, 10);
  dateInput.addEventListener('change', async () => {
    selectedTime = '';
    slots.replaceChildren(Object.assign(document.createElement('p'), {textContent:'Checking live availability…'}));
    try {
      const url = new URL(window.SR_THEME.calendarAvailabilityUrl);
      url.searchParams.set('date', dateInput.value); url.searchParams.set('duration', '60');
      const response = await fetch(url); const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Calendar availability is unavailable.');
      slots.replaceChildren();
      data.slots.filter((slot) => slot.available).forEach((slot) => {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'package-booking-option'; button.textContent = slot.start + ' IST';
        button.addEventListener('click', () => { selectedTime = slot.start; slots.querySelectorAll('button').forEach((item) => item.classList.toggle('is-selected', item === button)); }); slots.append(button);
      });
      if (!slots.childElementCount) slots.textContent = 'No times are available on this date. Choose another date.';
    } catch (error) { slots.textContent = error.message; }
  });
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!config.enabled || !config.test) { say('Test checkout is unavailable. Please contact the Academy.'); return; }
    if (!form.reportValidity()) return;
    if (!selectedPlan || !selectedTime) { say('Choose a package, date, and available time before continuing.'); return; }
    const submit = form.querySelector('[type="submit"]'); submit.disabled = true; say('Preparing secure test checkout…');
    try {
      const details = Object.fromEntries(new FormData(form));
      const order = await api('order', {offer:selectedPlan, duration:60, name:details.name, email:details.email, phone:details.phone, date:details.date, time:selectedTime});
      if (!order.test || !window.Razorpay) throw new Error('Razorpay test checkout did not initialize. No payment was made.');
      const checkout = new Razorpay({key:order.key, order_id:order.order_id, amount:order.amount, currency:order.currency, name:order.name, description:order.description, prefill:{name:details.name,email:details.email,contact:details.phone}, theme:{color:'#194c3b'}, handler:async (payment) => {
        try {
          say('Verifying your test payment and booking…');
          const confirmed = await api('verify', payment);
          if (!confirmed.confirmed) throw new Error('Payment is not confirmed yet. Please contact the Academy before retrying.');
          form.hidden = true; success.hidden = false; success.querySelector('[data-confirmation-details]').textContent = `${confirmed.service} · ${confirmed.date_label}`; success.scrollIntoView({behavior:'smooth',block:'center'});
        } catch (error) { say(error.message); submit.disabled = false; }
      }, modal:{ondismiss:() => { say('Checkout closed. No booking was confirmed.'); submit.disabled = false; }} });
      checkout.on('payment.failed', () => { say('Test payment failed. No booking was confirmed.'); submit.disabled = false; }); checkout.open();
    } catch (error) { say(error.message); submit.disabled = false; }
  });
})();
