(() => {
 'use strict';
 document.querySelectorAll('.sr-lms-purchase').forEach(box => {
  const button = box.querySelector('button'), status = box.querySelector('[role="status"]');
  const request = async (route, data) => {
   const response = await fetch(box.dataset.api + route, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','X-WP-Nonce':box.dataset.nonce}, body:JSON.stringify(data)});
   const result = await response.json();
   if (!response.ok) throw new Error(result.message || 'Payment could not be verified. Please contact support.');
   return result;
  };
  button.addEventListener('click', async () => {
   button.disabled = true; let paymentReceived = false; status.textContent = 'Creating secure checkout…';
   try {
    const order = await request('order', {offer:box.dataset.offer});
    if (!window.Razorpay) throw new Error('Checkout could not load. Please reload and try again.');
    const checkout = new window.Razorpay({...order, handler:async result => {
     paymentReceived = true; status.textContent = 'Verifying payment…';
     try {
      const verified = await request('verify', {id:order.id, ...result});
      if(!verified.test){button.hidden=true;const link=document.createElement('a');link.href=box.dataset.dashboard;link.textContent='Open dashboard';link.className='lms-primary';box.appendChild(link);}
      status.textContent = verified.test ? 'Test payment verified. No course enrollment was granted.' : 'Payment verified. Your course is ready in your dashboard.';
     } catch (error) {status.textContent = error.message + ' If charged, do not pay again; contact support with your payment reference.';}
     finally {button.disabled = !order.test;}
    }, modal:{ondismiss:() => {if(paymentReceived)return;button.disabled=false; status.textContent='Checkout closed. You can try again.';}}});
    checkout.on('payment.failed', () => {status.textContent='Payment was unsuccessful. Please check your payment status before retrying.';button.disabled=false;});
    status.textContent = order.test ? 'Test checkout · no enrollment will be granted.' : 'Complete your payment in the secure checkout.';
    checkout.open();
   } catch (error) {status.textContent=error.message;button.disabled=false;}
  });
 });
})();
