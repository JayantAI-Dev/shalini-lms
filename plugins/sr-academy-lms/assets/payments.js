function showCourseConfirmation(box,verified,customer,order){
 const dialog=box.closest('.sr-course-checkout-dialog');
 if(!dialog)return;
 const close=dialog.querySelector('.sr-course-checkout-close');
 const view=document.createElement('section');view.className='sr-lms-course-success';view.setAttribute('aria-labelledby','sr-lms-course-success-title');
 const mark=document.createElement('div');mark.className='sr-lms-course-success-mark';mark.setAttribute('aria-hidden','true');mark.textContent='✓';
 const kicker=document.createElement('p');kicker.className='sr-lms-course-success-kicker';kicker.textContent='PAYMENT CONFIRMED';
 const title=document.createElement('h2');title.id='sr-lms-course-success-title';title.textContent=verified.test&&!verified.test_access_granted?'Test payment verified':'Course enrollment complete';
 const course=document.createElement('p');course.className='sr-lms-course-success-course';course.textContent=verified.course_title||order.description||'Your course';
 const message=document.createElement('p');message.className='sr-lms-course-success-message';
 const email=verified.customer_email||customer.email;
 if(verified.test){
  message.textContent=verified.test_access_granted
   ? 'Your test enrollment is complete. '+(verified.signed_in?'You’re signed in and can open your dashboard.':'You’re signed in for 15 minutes and can open your dashboard.')+' '+(verified.email_sent?'We sent the course enrollment email to '+email+'.':'Course email delivery could not be confirmed; contact the Academy if you need help.')+' No live charge was made.'
   : 'Razorpay test payment verified, but this test account is not approved for course access. No live charge was made. Ask the Academy to add the student email to the approved test accounts.';
 }else if(verified.email_sent){
  if(verified.new_account){
   message.textContent='Your course enrollment is complete. You’re signed in for 15 minutes, and we emailed your sign-in email plus a secure password setup link to '+email+'. Set your password from that email to keep access after the temporary session ends.';
  }else if(verified.signed_in){
   message.textContent='Your course enrollment is complete. You’re signed in and can open your dashboard now. We sent the course enrollment email to '+email+'.';
  }else{
   message.textContent='Your course enrollment is complete. We sent the enrollment email to '+email+'. Sign in to the Student Portal to open your dashboard.';
  }
 }else{
  message.textContent=verified.temporary_signin
   ? 'Your course enrollment is complete and you’re signed in for 15 minutes, but delivery of the password setup email could not be confirmed. Please do not pay again; contact the Academy for access help.'
   : 'Your course enrollment is complete, but delivery of the course email to '+email+' could not be confirmed. Please do not pay again; contact the Academy for access help.';
 }
 const link=document.createElement('a');link.href=box.dataset.dashboard;link.className='sr-lms-course-success-link';link.textContent=verified.test&&!verified.test_access_granted?'Go to Student Portal':'Open student dashboard';
 view.append(mark,kicker,title,course,message,link);
 dialog.replaceChildren(...(close?[close]:[]),view);
 view.querySelector('a').focus();
}

(() => {
 'use strict';
 document.querySelectorAll('.sr-lms-purchase').forEach(box => {
  const button=box.querySelector('button[type="submit"]'),status=box.querySelector('[role="status"]');
  const fields=Object.fromEntries(['name','email','phone'].map(name=>[name,box.querySelector(`[name="${name}"]`)]));
  const request=async(route,data)=>{
   const response=await fetch(box.dataset.api+route,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':box.dataset.nonce},body:JSON.stringify(data)});
   const result=await response.json();if(!response.ok)throw new Error(result.message||'Payment could not be verified. Please contact support.');return result;
  };
  box.addEventListener('submit',async event=>{
   event.preventDefault();if(!box.reportValidity())return;
   const customer=Object.fromEntries(Object.entries(fields).map(([name,field])=>[name,field.value.trim()]));
   const phoneDigits=customer.phone.replace(/\D/g,'');
   if(customer.name.length>100||customer.email.length>100||phoneDigits.length<7||phoneDigits.length>15){status.textContent='Check your name, email address, and phone number.';return;}
   button.disabled=true;let paymentReceived=false;status.textContent='Creating secure checkout…';
   try{
    const order=await request('order',{offer:box.dataset.offer,...customer,whatsapp_opt_in:box.elements.whatsapp_opt_in?.checked?'1':'0'});
    if(!window.Razorpay)throw new Error('Checkout could not load. Please reload and try again.');
    const checkout=new window.Razorpay({...order,prefill:order.prefill,theme:{color:'#194c3b'},handler:async payment=>{
     paymentReceived=true;status.textContent='Verifying payment…';
     try{
      const verified=await request('verify',{id:order.id,...payment});
      showCourseConfirmation(box,verified,customer,order);
     }catch(error){status.textContent=error.message+' If charged, do not pay again; contact support with your payment reference.';button.disabled=false;}
    },modal:{ondismiss:()=>{if(paymentReceived)return;button.disabled=false;status.textContent='Checkout closed. You can try again.';}}});
    checkout.on('payment.failed',()=>{status.textContent='Payment was unsuccessful. Please check your payment status before retrying.';button.disabled=false;});
    status.textContent=order.test?'Razorpay test mode · no live charge or course access.':'Complete your payment in the secure checkout.';checkout.open();
   }catch(error){status.textContent=error.message;button.disabled=false;}
  });
 });
})();
