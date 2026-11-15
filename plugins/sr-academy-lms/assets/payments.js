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
    const order=await request('order',{offer:box.dataset.offer,...customer});
    if(!window.Razorpay)throw new Error('Checkout could not load. Please reload and try again.');
    const checkout=new window.Razorpay({...order,prefill:order.prefill,theme:{color:'#194c3b'},handler:async payment=>{
     paymentReceived=true;status.textContent='Verifying payment…';
     try{
      const verified=await request('verify',{id:order.id,...payment});
      if(verified.test){status.textContent=verified.test_access_granted?'Test payment verified. This approved test account can now see the course in the student portal. No live charge was made.':'Test payment verified. A test confirmation was emailed to '+customer.email+'. No live charge or course access was created.';box.reset();button.disabled=false;return;}
      const done=document.createElement('div');done.className='sr-lms-purchase-confirmation';
      const title=document.createElement('h2');title.textContent='Thank you — your course purchase is confirmed.';
      const course=document.createElement('p');course.textContent=verified.course_title||order.description;
      const details=document.createElement('p');details.textContent=verified.account_setup_required?'We emailed a secure password setup link to '+verified.customer_email+'. After setting your password, sign in to access this course.':'A confirmation email was sent to '+verified.customer_email+'. Sign in to find this course in your dashboard.';
      const link=document.createElement('a');link.href=box.dataset.dashboard;link.textContent='Go to student portal';link.className='lms-primary';done.append(title,course,details,link);box.hidden=true;box.parentNode.insertBefore(done,box.nextSibling);
     }catch(error){status.textContent=error.message+' If charged, do not pay again; contact support with your payment reference.';button.disabled=false;}
    },modal:{ondismiss:()=>{if(paymentReceived)return;button.disabled=false;status.textContent='Checkout closed. You can try again.';}}});
    checkout.on('payment.failed',()=>{status.textContent='Payment was unsuccessful. Please check your payment status before retrying.';button.disabled=false;});
    status.textContent=order.test?'Razorpay test mode · no live charge or course access.':'Complete your payment in the secure checkout.';checkout.open();
   }catch(error){status.textContent=error.message;button.disabled=false;}
  });
 });
})();
