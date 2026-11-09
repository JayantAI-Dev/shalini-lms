const assert=require('node:assert/strict');const fs=require('node:fs');const {JSDOM}=require(process.env.SR_LMS_JSDOM_PATH||'jsdom');
async function scenario(test=false,failure=false){
 const d=new JSDOM('<div class="sr-lms-purchase" data-offer="speakathon" data-api="http://lms.local/wp-json/sr-lms/v1/payments/" data-dashboard="http://lms.local/dashboard/" data-nonce="fixture"><button>Pay</button><p role="status"></p></div>',{url:'http://lms.local/enroll/',runScripts:'outside-only'});let options,requests=[];
 d.window.fetch=async(url,args)=>{requests.push([url,JSON.parse(args.body)]);return {ok:!(failure&&url.endsWith('verify')),json:async()=>url.endsWith('order')?{id:7,order_id:'order_fixture',key:'rzp_fixture',amount:100,currency:'INR',test}:{test,message:'Verification unavailable'}}};
 d.window.Razorpay=function(o){options=o;this.on=()=>{};this.open=()=>{}};
 d.window.eval(fs.readFileSync(process.env.SR_LMS_PAYMENT_SCRIPT||__dirname+'/../assets/payments.js','utf8'));let b=d.window.document.querySelector('button');b.click();await new Promise(r=>setImmediate(r));assert.equal(options.order_id,'order_fixture');assert.deepEqual(requests[0][1],{offer:'speakathon'});
 await options.handler({razorpay_payment_id:'pay_fixture',razorpay_order_id:'order_fixture',razorpay_signature:'sig'});const s=d.window.document.querySelector('[role=status]').textContent;options.modal.ondismiss();assert.equal(d.window.document.querySelector('[role=status]').textContent,s,'Dismiss cannot overwrite verified/error state');
 if(test){assert.match(s,/No course enrollment/);assert.equal(b.hidden,false);assert.equal(b.disabled,false)}else if(failure){assert.match(s,/do not pay again/);assert.equal(b.disabled,true,'Verification failure cannot create another charge')}else{assert.equal(b.hidden,true);assert.equal(d.window.document.querySelector('a').href,'http://lms.local/dashboard/')}
 d.window.close();
}
(async()=>{await scenario();await scenario(true);await scenario(false,true);console.log('PASS: checkout DOM success, test and verification-failure scenarios')})().catch(e=>{console.error(e);process.exit(1)});
