<?php
if (!defined('ABSPATH')) exit;
function sr_lms_payment_install(): void {
 if(get_option('sr_lms_payment_schema')==='3')return;
 global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$c=$wpdb->get_charset_collate();
 dbDelta('CREATE TABLE '.sr_lms_table('payments')." (
 id bigint unsigned NOT NULL AUTO_INCREMENT,
 user_id bigint unsigned NOT NULL,
 offer varchar(100) NOT NULL,
 mode varchar(8) NOT NULL,
 amount bigint unsigned NOT NULL,
 currency varchar(3) NOT NULL DEFAULT 'INR',
 targets longtext NOT NULL,
 gateway_order varchar(100) DEFAULT NULL,
 gateway_payment varchar(100) DEFAULT NULL,
 status varchar(24) NOT NULL DEFAULT 'created',
 refunded bigint unsigned NOT NULL DEFAULT 0,
 account_created tinyint(1) NOT NULL DEFAULT 0,
 signin_expires bigint unsigned NOT NULL DEFAULT 0,
 email_sent bigint NOT NULL DEFAULT 0,
 created_at datetime NOT NULL,
 updated_at datetime NOT NULL,
 PRIMARY KEY  (id),
 UNIQUE KEY gateway_order (gateway_order),
 UNIQUE KEY gateway_payment (gateway_payment),
 KEY student (user_id)
 ) ENGINE=InnoDB $c;");
 dbDelta('CREATE TABLE '.sr_lms_table('payment_events')." (
 id bigint unsigned NOT NULL AUTO_INCREMENT,
 event_key varchar(100) NOT NULL,
 created_at datetime NOT NULL,
 PRIMARY KEY  (id),
 UNIQUE KEY event_key (event_key)
 ) ENGINE=InnoDB $c;");
 update_option('sr_lms_payment_schema','3',false);
}
function sr_lms_payment_settings(): array {return (array)get_option('sr_lms_payment_settings',[]);}
/** Test purchases may unlock access only for existing, verified accounts explicitly allowlisted by an administrator. */
function sr_lms_payment_test_access_enabled(int $user_id): bool {
 $settings=sr_lms_payment_settings();if(($settings['mode']??'test')!=='test')return false;$user=get_userdata($user_id);if(!$user||!function_exists('sr_lms_email_is_verified')||!sr_lms_email_is_verified($user_id))return false;
 $email=strtolower(sanitize_email((string)$user->user_email));$allowed=array_map(static fn($value)=>strtolower(sanitize_email((string)$value)),(array)($settings['test_access_emails']??[]));return $email!==''&&in_array($email,$allowed,true);
}
function sr_lms_payment_test_access_user_id(array $notes,int $existing_id=0): int {
 $email=strtolower(sanitize_email((string)($notes['customer_email']??'')));if(!$email)return 0;$user=$existing_id?get_userdata($existing_id):get_user_by('email',$email);if(!$user||strtolower((string)$user->user_email)!==$email)return 0;return sr_lms_payment_test_access_enabled((int)$user->ID)?(int)$user->ID:0;
}
function sr_lms_payment_encrypt(string $plain) {
 if(!function_exists('openssl_encrypt'))return new WP_Error('crypto','OpenSSL is required.');
 $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($plain,'aes-256-gcm',hash('sha256',wp_salt('auth'),true),OPENSSL_RAW_DATA,$iv,$tag);
 return $cipher===false?new WP_Error('crypto','Could not protect secret.'):base64_encode($iv.$tag.$cipher);
}
function sr_lms_payment_decrypt(string $sealed): string {
 if(!function_exists('openssl_decrypt'))return '';$raw=base64_decode($sealed,true);if(!$raw||strlen($raw)<29)return '';
 return (string)openssl_decrypt(substr($raw,28),'aes-256-gcm',hash('sha256',wp_salt('auth'),true),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16));
}
function sr_lms_payment_credentials(string $mode): array {
 $s=sr_lms_payment_settings();if(!in_array($mode,['test','live'],true))return [];
 return ['key'=>$s[$mode.'_key']??'','secret'=>sr_lms_payment_decrypt($s[$mode.'_secret']??''),'webhook'=>sr_lms_payment_decrypt($s[$mode.'_webhook']??'')];
}
function sr_lms_payment_ready(): bool {$s=sr_lms_payment_settings();$m=$s['mode']??'test';$c=sr_lms_payment_credentials($m);return !empty($s['enabled'])&&!empty($c['secret'])&&!empty($c['key'])&&str_starts_with($c['key'],'rzp_'.$m.'_');}
function sr_lms_payment_signature(string $raw,string $signature,string $secret): bool {return $secret!==''&&preg_match('/^[a-f0-9]{64}$/D',$signature)&&hash_equals(hash_hmac('sha256',$raw,$secret),$signature);}
function sr_lms_payment_api(string $mode,string $path,array $data=[],$method='GET') {
 if(!preg_match('~^(?:orders(?:/order_[A-Za-z0-9]+(?:/payments)?)?|payments/pay_[A-Za-z0-9]+(?:/refunds)?)$~D',$path))return new WP_Error('gateway','Invalid gateway resource.');
 $c=sr_lms_payment_credentials($mode);if(empty($c['key'])||empty($c['secret']))return new WP_Error('gateway','Gateway credentials unavailable.');
 $args=['method'=>$method,'timeout'=>20,'redirection'=>0,'headers'=>['Authorization'=>'Basic '.base64_encode($c['key'].':'.$c['secret']),'Content-Type'=>'application/json']];if($data)$args['body']=wp_json_encode($data);
 $r=wp_remote_request('https://api.razorpay.com/v1/'.$path,$args);if(is_wp_error($r))return new WP_Error('gateway','Gateway could not be reached. Please retry.');
 $j=json_decode(wp_remote_retrieve_body($r),true);if(wp_remote_retrieve_response_code($r)<200||wp_remote_retrieve_response_code($r)>=300||!is_array($j))return new WP_Error('gateway','Gateway verification failed. Please retry.');return $j;
}
function sr_lms_payment_permission($r) {if(!wp_verify_nonce($r->get_header('X-WP-Nonce'),'wp_rest'))return new WP_Error('auth','Reload the page and try again.',['status'=>403]);if($r->get_route()==='/sr-lms/v1/payments/order'){$ip=sanitize_text_field((string)($_SERVER['REMOTE_ADDR']??''));$key='sr_lms_order_rate_'.substr(hash('sha256',$ip.wp_salt('nonce')),0,32);$count=(int)get_transient($key);if($count>=20)return new WP_Error('rate_limit','Too many checkout attempts. Please try again later.',['status'=>429]);set_transient($key,$count+1,HOUR_IN_SECONDS);}return true;}
function sr_lms_payment_order($r) {
 global $wpdb;if(!sr_lms_payment_ready())return new WP_Error('disabled','Checkout is not available.',['status'=>409]);
 $offer=sr_lms_id($r['offer']??'');$o=sr_lms_offers()[$offer]??null;if(!$o||!in_array($offer,(array)(sr_lms_payment_settings()['offers']??[]),true))return new WP_Error('offer','Unknown offer.',['status'=>400]);
 $customer_name=sanitize_text_field((string)($r['name']??''));$customer_email=sanitize_email((string)($r['email']??''));$customer_phone=sanitize_text_field((string)($r['phone']??''));$phone_digits=preg_replace('/\D/','',$customer_phone);
 if($customer_name===''||strlen($customer_name)>100||!is_email($customer_email)||strlen($customer_email)>100||strlen((string)$phone_digits)<7||strlen((string)$phone_digits)>15)return new WP_Error('customer_details','Enter your name, a valid email address, and a valid phone number.',['status'=>400]);
 $mode=sr_lms_payment_settings()['mode']??'test';$account=get_user_by('email',$customer_email);$user_id=0;
 if(is_user_logged_in()){$current=get_userdata(get_current_user_id());if(!$current||strtolower($current->user_email)!==strtolower($customer_email))return new WP_Error('account_mismatch','Use the email address for your signed-in student account.',['status'=>409]);$user_id=(int)$current->ID;}
 elseif($account&&$mode==='live')return new WP_Error('account_login_required','A student account already uses this email. Please sign in to the Student Portal before purchasing.',['status'=>409]);
 elseif($account)$user_id=(int)$account->ID;
 if($mode!=='test'&&$user_id){$already=true;foreach($o['courses'] as $course)if(!sr_lms_has_access($user_id,$course))$already=false;if($already)return new WP_Error('enrolled','You already have access to this course. Sign in to continue learning.',['status'=>409]);}
 $amount=(int)round((float)$o['price']*100);if($amount<100||$amount>1000000000)return new WP_Error('price','This price cannot be purchased online.',['status'=>400]);
 $now=current_time('mysql',true);
 if(!$wpdb->insert(sr_lms_table('payments'),['user_id'=>$user_id,'offer'=>$offer,'mode'=>$mode,'amount'=>$amount,'targets'=>wp_json_encode($o['courses']),'created_at'=>$now,'updated_at'=>$now]))return new WP_Error('storage','Could not save order.');$id=(int)$wpdb->insert_id;
 $g=sr_lms_payment_api($mode,'orders',['amount'=>$amount,'currency'=>'INR','receipt'=>'sr-lms-'.$id,'notes'=>['local_order'=>(string)$id,'customer_name'=>$customer_name,'customer_email'=>$customer_email,'customer_phone'=>$customer_phone]],'POST');
 if(is_wp_error($g))return $g;
 if(!preg_match('/^order_[A-Za-z0-9]+$/D',$g['id']??'')||(int)($g['amount']??0)!==$amount||($g['currency']??'')!=='INR')return new WP_Error('gateway','Unexpected gateway order.');
 if($wpdb->update(sr_lms_table('payments'),['gateway_order'=>$g['id']],['id'=>$id])!==1)return new WP_Error('storage','Could not link gateway order.');
 return new WP_REST_Response(['id'=>$id,'order_id'=>$g['id'],'key'=>sr_lms_payment_credentials($mode)['key'],'amount'=>$amount,'currency'=>'INR','name'=>'SR Academy','description'=>$o['title'],'prefill'=>['name'=>$customer_name,'email'=>$customer_email,'contact'=>$customer_phone],'customer_email'=>$customer_email,'test'=>$mode==='test']);
}
function sr_lms_payment_customer_account(array $notes,int $existing_id=0,int $payment_id=0) {
 $email=sanitize_email((string)($notes['customer_email']??''));$name=sanitize_text_field((string)($notes['customer_name']??''));$phone=sanitize_text_field((string)($notes['customer_phone']??''));
 if(!is_email($email)||$name==='')return new WP_Error('customer','Verified order is missing valid customer details.');
 $user=$existing_id?get_userdata($existing_id):get_user_by('email',$email);
 if($user&&strtolower($user->user_email)!==strtolower($email))return new WP_Error('customer','Order email does not match the student account.');
 $created=false;
 if(!$user){
  $base=sanitize_user(strstr($email,'@',true),true);if(!$base)$base='student';$login=$base;$suffix=1;while(username_exists($login))$login=$base.'-'.$suffix++;
  $random_password=wp_generate_password(64,true,true);
  $id=wp_insert_user(['user_login'=>$login,'user_email'=>$email,'user_pass'=>$random_password,'display_name'=>$name,'first_name'=>$name,'role'=>'subscriber']);
  if(is_wp_error($id)){$user=get_user_by('email',$email);if(!$user)return new WP_Error('account','Payment is verified, but the student account could not be created. Please contact the Academy before retrying.');}
  else{$user=get_userdata((int)$id);$created=true;update_user_meta((int)$id,'sr_lms_email_verified','0');update_user_meta((int)$id,'sr_lms_course_password_setup_pending','1');if($payment_id){global $wpdb;$wpdb->update(sr_lms_table('payments'),['account_created'=>1],['id'=>$payment_id],['%d'],['%d']);}}
 }
 if(!$user)return new WP_Error('account','Student account could not be found.');
 if($phone!=='')update_user_meta((int)$user->ID,'sr_lms_phone',$phone);
 if(!sr_lms_email_is_verified((int)$user->ID))update_user_meta((int)$user->ID,'sr_lms_course_password_setup_pending','1');
 return ['user_id'=>(int)$user->ID,'needs_password_setup'=>$created||!sr_lms_email_is_verified((int)$user->ID),'account_created'=>$created,'initial_password'=>''];
}
/** API verification precedes transactional fulfillment. Row locks serialize callback/webhook/admin retries. */
function sr_lms_payment_reconcile(int $id,string $payment='') {
 global $wpdb;$t=sr_lms_table('payments');$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d",$id),ARRAY_A);if(!$row||!$row['gateway_order'])return new WP_Error('order','Unknown order.');
 if(!$payment)$payment=$row['gateway_payment'];
 if(!$payment){$list=sr_lms_payment_api($row['mode'],'orders/'.$row['gateway_order'].'/payments');if(is_wp_error($list))return $list;foreach($list['items']??[] as $item)if(in_array($item['status']??'',['captured','refunded'],true)){$payment=$item['id'];break;}}
 if(!preg_match('/^pay_[A-Za-z0-9]+$/D',$payment))return new WP_Error('pending','Payment has not been captured.',['status'=>409]);
 $p=sr_lms_payment_api($row['mode'],'payments/'.$payment);if(is_wp_error($p))return $p;
 if(($p['id']??'')!==$payment||($p['order_id']??'')!==$row['gateway_order']||(int)($p['amount']??0)!==(int)$row['amount']||($p['currency']??'')!==$row['currency'])return new WP_Error('mismatch','Payment does not match this order.',['status'=>400]);
 if(!in_array($p['status']??'',['captured','refunded'],true))return new WP_Error('pending','Payment has not been captured.',['status'=>409]);
 $refundRaw=$p['amount_refunded']??null;if(!is_int($refundRaw)||$refundRaw<0||$refundRaw>(int)$row['amount']||(($p['status']??'')==='refunded'&&$refundRaw!==(int)$row['amount']))return new WP_Error('refund','Gateway refund amount is inconsistent.');
 $customer_order=sr_lms_payment_api($row['mode'],'orders/'.$row['gateway_order']);if(is_wp_error($customer_order))return $customer_order;$notes=(array)($customer_order['notes']??[]);
 if(empty($notes['customer_email'])&&$row['user_id']){$linked=get_userdata((int)$row['user_id']);if($linked){$notes['customer_email']=$linked->user_email;$notes['customer_name']=$linked->display_name;$notes['customer_phone']=get_user_meta((int)$linked->ID,'sr_lms_phone',true);}}
 $payment_full=(int)$refundRaw>=(int)$row['amount']||($p['status']??'')==='refunded';$customer_account=null;$test_access_user_id=$row['mode']==='test'?sr_lms_payment_test_access_user_id($notes,(int)$row['user_id']):0;
 if($row['mode']==='live'&&!$payment_full){$customer_account=sr_lms_payment_customer_account($notes,(int)$row['user_id'],$id);if(is_wp_error($customer_account))return $customer_account;if(!empty($row['account_created'])){$customer_account['account_created']=true;$customer_account['needs_password_setup']=true;}if(!empty($customer_account['account_created']))$row['account_created']=1;}
 if($wpdb->query('START TRANSACTION')===false)return new WP_Error('transaction','Could not begin payment transaction.');$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d FOR UPDATE",$id),ARRAY_A);
 if(!$row){$wpdb->query('ROLLBACK');return new WP_Error('transaction','Could not lock payment order.');}
 $refund=max((int)$row['refunded'],(int)($p['amount_refunded']??0));$full=$refund>=(int)$row['amount']||$row['status']==='refunded';$origin='pay:'.$id;$error=null;
 if($row['gateway_payment']&&$row['gateway_payment']!==$payment)$error=new WP_Error('duplicate','A different payment already belongs to this order.');
 $customer_user_id=(int)$row['user_id'];if(!$error&&$row['mode']==='live'&&!$full&&$customer_account)$customer_user_id=(int)$customer_account['user_id'];elseif(!$error&&$row['mode']==='test'&&!$full&&$test_access_user_id)$customer_user_id=$test_access_user_id;
 if(!$error&&$row['mode']==='live'){
  if($full&&$customer_user_id){if(!sr_lms_revoke($customer_user_id,$origin))$error=new WP_Error('grant','Could not revoke refunded enrollment.');}
  elseif(!$full&&$row['status']!=='paid') {foreach((array)json_decode($row['targets'],true) as $course){$saved=$wpdb->query($wpdb->prepare('INSERT INTO '.sr_lms_table('grants').' (user_id,course,origin,granted_at,granted_by) VALUES (%d,%s,%s,%s,0) ON DUPLICATE KEY UPDATE origin=VALUES(origin)',$customer_user_id,$course,$origin,current_time('mysql',true)));if($saved===false){$error=new WP_Error('grant','Could not save enrollment.');break;}}}
 }
 elseif(!$error&&$row['mode']==='test'&&$test_access_user_id){$customer_user_id=$test_access_user_id;$test_origin='test:'.$id;if($full){$wpdb->delete(sr_lms_table('grants'),['user_id'=>$customer_user_id,'origin'=>$test_origin],['%d','%s']);}else{foreach((array)json_decode($row['targets'],true) as $course){$saved=$wpdb->query($wpdb->prepare('INSERT INTO '.sr_lms_table('grants').' (user_id,course,origin,granted_at,granted_by) VALUES (%d,%s,%s,%s,0) ON DUPLICATE KEY UPDATE origin=VALUES(origin)',$customer_user_id,$course,$test_origin,current_time('mysql',true)));if($saved===false){$error=new WP_Error('grant','Could not save test enrollment.');break;}}}}
 $status=$full?'refunded':($row['mode']==='test'?'test_paid':'paid');
 if(!$error&&$wpdb->update($t,['user_id'=>$customer_user_id,'status'=>$status,'gateway_payment'=>$payment,'refunded'=>$refund,'updated_at'=>current_time('mysql',true)],['id'=>$id])===false)$error=new WP_Error('storage','Could not save payment.');
 if($error){$wpdb->query('ROLLBACK');return $error;}if($wpdb->query('COMMIT')===false){$wpdb->query('ROLLBACK');return new WP_Error('transaction','Could not commit payment transaction. Please reconcile again.');}
 // Claim with a short database lease; failed delivery can be retried by reconciliation.
 if(!$full&&function_exists('sr_lms_send_email')){
  $lease=time();$claimed=$wpdb->query($wpdb->prepare("UPDATE $t SET email_sent=%d WHERE id=%d AND (email_sent=0 OR (email_sent<0 AND email_sent > %d))",-$lease,$id,-($lease-300)));
  if($claimed===1){$offer_title=sr_lms_offers()[$row['offer']]['title']??$row['offer'];$email=sanitize_email((string)($notes['customer_email']??''));$name=sanitize_text_field((string)($notes['customer_name']??''));$context=['offer_title'=>$offer_title,'customer_email'=>$email,'customer_phone'=>(string)($notes['customer_phone']??''),'customer_password'=>(string)($customer_account['initial_password']??''),'name'=>$name,'cta_url'=>sr_lms_page_url('student-portal'),'amount'=>'INR '.number_format($row['amount']/100,2),'currency'=>'INR','payment_id'=>$payment,'order_id'=>$row['gateway_order'],'mode'=>$row['mode'],'test_access_message'=>$test_access_user_id?'Course access has been enabled for your approved test account.':'This test transaction does not grant course access.'];
  if($row['mode']==='test'){
    if($test_access_user_id){$test_password=wp_generate_password(20,true,true);wp_set_password($test_password,$test_access_user_id);$context['customer_password']=$test_password;$context['test_credentials_message']='Use the sign-in details in the Student Portal block below to access your approved test account.';}
    else{$context['test_credentials_message']='Student portal credentials are not issued for unapproved test accounts.';}
    $sent=sr_lms_send_email_to('course_test_purchase',$email,$name,$context);
   }
   elseif(!empty($row['account_created'])||!empty($customer_account['needs_password_setup'])){$user=get_userdata($customer_user_id);$key=$user?get_password_reset_key($user):new WP_Error('account','Student account not found.');if(!is_wp_error($key)){$context['reset_url']=network_site_url('wp-login.php?action=rp&key='.rawurlencode($key).'&login='.rawurlencode($user->user_login),'login');$sent=sr_lms_send_email('course_access',$customer_user_id,$context);}else{$sent=false;}}
   else{$sent=sr_lms_send_email('enrollment',$customer_user_id,['offer_title'=>$offer_title,'cta_url'=>sr_lms_page_url('dashboard')]);}
   $wpdb->query($wpdb->prepare("UPDATE $t SET email_sent=%d WHERE id=%d AND email_sent=%d",($sent&&!is_wp_error($sent))?1:0,$id,-$lease));}
 }

 return ['status'=>$status,'test'=>$row['mode']==='test','test_access_granted'=>$row['mode']==='test'&&!$full&&(bool)$test_access_user_id,'customer_email'=>sanitize_email((string)($notes['customer_email']??'')),'course_title'=>sr_lms_offers()[$row['offer']]['title']??$row['offer'],'account_setup_required'=>$row['mode']==='live'&&!empty($customer_account['needs_password_setup']),'new_account'=>$row['mode']==='live'&&(bool)$wpdb->get_var($wpdb->prepare("SELECT account_created FROM $t WHERE id=%d",$id)),'email_sent'=>(bool)$wpdb->get_var($wpdb->prepare("SELECT email_sent FROM $t WHERE id=%d",$id))];
}
function sr_lms_payment_verify($r) {
 global $wpdb;$id=absint($r['id']??0);$row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.sr_lms_table('payments').' WHERE id=%d',$id),ARRAY_A);
 if(!$row)return new WP_Error('order','Unknown order.',['status'=>404]);$payment=(string)($r['razorpay_payment_id']??'');$c=sr_lms_payment_credentials($row['mode']);
 if(($r['razorpay_order_id']??'')!==$row['gateway_order']||!sr_lms_payment_signature($row['gateway_order'].'|'.$payment,(string)($r['razorpay_signature']??''),$c['secret']))return new WP_Error('signature','Payment signature rejected.',['status'=>400]);
 $result=sr_lms_payment_reconcile($id,$payment);if(is_wp_error($result)||!is_array($result))return $result;
 if(($result['status']??'')==='paid'){
  $fresh=$wpdb->get_row($wpdb->prepare('SELECT user_id,account_created,signin_expires FROM '.sr_lms_table('payments').' WHERE id=%d',$id),ARRAY_A);$user=$fresh?get_userdata((int)$fresh['user_id']):false;
  if($user&&strtolower($user->user_email)===strtolower((string)($result['customer_email']??''))){
   if(!empty($fresh['account_created'])&&(!is_user_logged_in()||get_current_user_id()===(int)$user->ID)){
    if((int)$fresh['signin_expires']===0){$wpdb->query($wpdb->prepare('UPDATE '.sr_lms_table('payments').' SET signin_expires=%d WHERE id=%d AND signin_expires=0',time()+15*MINUTE_IN_SECONDS,$id));$fresh=$wpdb->get_row($wpdb->prepare('SELECT user_id,account_created,signin_expires FROM '.sr_lms_table('payments').' WHERE id=%d',$id),ARRAY_A);}
    $remaining=max(0,(int)($fresh['signin_expires']??0)-time());
    if($remaining>0){update_user_meta((int)$user->ID,'sr_lms_payment_access_until',(int)$fresh['signin_expires']);if(!is_user_logged_in()){$short_session=static fn($expiration,$uid,$remember)=>$uid===(int)$user->ID&&!$remember?$remaining:$expiration;add_filter('auth_cookie_expiration',$short_session,PHP_INT_MAX,3);wp_set_current_user((int)$user->ID);wp_set_auth_cookie((int)$user->ID,false,is_ssl());remove_filter('auth_cookie_expiration',$short_session,PHP_INT_MAX);do_action('wp_login',$user->user_login,$user);$result['temporary_signin']=true;}}
   }
   $result['signed_in']=is_user_logged_in()&&get_current_user_id()===(int)$user->ID;
  }
 }
 return $result;
}
function sr_lms_payment_webhook($r) {
 global $wpdb;$mode=$r['mode'];$c=sr_lms_payment_credentials($mode);$raw=$r->get_body();if(strlen($raw)>1048576||!sr_lms_payment_signature($raw,$r->get_header('X-Razorpay-Signature'),$c['webhook']??''))return new WP_Error('signature','Webhook signature rejected.',['status'=>400]);
 $j=json_decode($raw,true);if(!is_array($j))return new WP_Error('json','Invalid event.',['status'=>400]);
 if(!in_array($j['event']??'',['payment.captured','refund.processed'],true))return ['ignored'=>true];
 $key=hash('sha256',$mode.'|'.($r->get_header('X-Razorpay-Event-Id')?:$raw));$events=sr_lms_table('payment_events');if($wpdb->get_var($wpdb->prepare("SELECT id FROM $events WHERE event_key=%s",$key)))return ['duplicate'=>true];
 $p=$j['payload']['payment']['entity']??[];$payment=$p['id']??($j['payload']['refund']['entity']['payment_id']??'');
 if(!preg_match('/^pay_[A-Za-z0-9]+$/D',$payment))return new WP_Error('event','Missing payment.',['status'=>400]);
 $p=sr_lms_payment_api($mode,'payments/'.$payment);if(is_wp_error($p))return $p;
 $consultation_order=$wpdb->get_var($wpdb->prepare('SELECT gateway_order FROM '.sr_lms_table('consultation_bookings').' WHERE mode=%s AND gateway_order=%s',$mode,$p['order_id']??''));
 if($consultation_order&&function_exists('sr_lms_consultation_verify')){
  $request=new WP_REST_Request('POST','/');$request->set_param('razorpay_order_id',$consultation_order);$request->set_param('razorpay_payment_id',$payment);$request->set_param('razorpay_signature',hash_hmac('sha256',$consultation_order.'|'.$payment,$c['secret']));
  $result=sr_lms_consultation_verify($request);if(is_wp_error($result))return $result;
  if($wpdb->query($wpdb->prepare("INSERT IGNORE INTO $events (event_key,created_at) VALUES (%s,%s)",$key,current_time('mysql',true)))===false)return new WP_Error('storage','Please retry webhook.');return $result;
 }
 $id=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.sr_lms_table('payments').' WHERE mode=%s AND gateway_order=%s',$mode,$p['order_id']??''));if(!$id)return ['ignored'=>true];
 $result=sr_lms_payment_reconcile((int)$id,$payment);if(is_wp_error($result))return $result;
 if($wpdb->query($wpdb->prepare("INSERT IGNORE INTO $events (event_key,created_at) VALUES (%s,%s)",$key,current_time('mysql',true)))===false)return new WP_Error('storage','Please retry webhook.');return $result;
}
add_action('rest_api_init',static function(){
 foreach(['order'=>'sr_lms_payment_order','verify'=>'sr_lms_payment_verify'] as $route=>$callback)register_rest_route('sr-lms/v1','/payments/'.$route,['methods'=>'POST','permission_callback'=>'sr_lms_payment_permission','callback'=>$callback]);
 register_rest_route('sr-lms/v1','/payments/webhook/(?P<mode>test|live)',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>'sr_lms_payment_webhook']);
});
function sr_lms_manual_requests_enabled(): bool {return !empty(sr_lms_payment_settings()['manual_requests']??true);}
function sr_lms_render_purchase(string $offer): bool {
 if(!isset(sr_lms_offers()[$offer]))return false;
 if(!sr_lms_payment_ready()||!in_array($offer,(array)(sr_lms_payment_settings()['offers']??[]),true)){if(sr_lms_manual_requests_enabled())return false;echo '<p>Online checkout is currently unavailable. Please contact the academy for enrollment.</p>';return true;}
 $targets=sr_lms_offers()[$offer]['courses'];$mode=sr_lms_payment_settings()['mode']??'test';$enrolled=is_user_logged_in();foreach($targets as $course)if(!sr_lms_has_access(get_current_user_id(),$course))$enrolled=false;if($enrolled&&$mode!=='test'){echo '<p>You already have access to this course.</p><a class="lms-primary" href="'.esc_url(sr_lms_page_url('dashboard')).'">Open dashboard</a>';return true;}
 wp_enqueue_script('sr-lms-razorpay','https://checkout.razorpay.com/v1/checkout.js',[],null,true);
 wp_enqueue_script('sr-lms-payments',plugins_url('assets/payments.js',dirname(__DIR__).'/sr-academy-lms.php'),['sr-lms-razorpay'],SR_LMS_VERSION,true);
 $current=is_user_logged_in()?get_userdata(get_current_user_id()):false;$full_name=$current?(string)$current->display_name:'';$email=$current?(string)$current->user_email:'';$phone=$current?(string)get_user_meta((int)$current->ID,'sr_lms_phone',true):'';
 echo '<form class="sr-lms-purchase" data-dashboard="'.esc_url(sr_lms_page_url('dashboard')).'" data-offer="'.esc_attr($offer).'" data-api="'.esc_url(rest_url('sr-lms/v1/payments/')).'" data-nonce="'.esc_attr(wp_create_nonce('wp_rest')).'" novalidate><div class="sr-lms-customer-fields"><label>Full name<input name="name" type="text" autocomplete="name" maxlength="100" value="'.esc_attr($full_name).'" required></label><label>Email address<input name="email" type="email" autocomplete="email" maxlength="100" value="'.esc_attr($email).'" required></label><label>WhatsApp / phone<input name="phone" type="tel" autocomplete="tel" minlength="7" maxlength="25" value="'.esc_attr($phone).'" required></label></div><p class="sr-lms-password-note">New to the Academy? After payment, we’ll sign you in for 15 minutes and email your sign-in email plus a secure password setup link. If you already have a student account, sign in before checkout.</p><button type="submit" class="lms-primary">'.($mode==='test'?'Continue to Razorpay test payment':'Continue to Razorpay').'</button><p class="sr-lms-payment-note">'.($mode==='test'?'Razorpay test mode · no live charge or course access':'Secure Razorpay checkout').'</p><p role="status" aria-live="polite"></p></form>';if(sr_lms_manual_requests_enabled()){echo '<p>Prefer to request access from the academy?</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="sr_lms_enroll"><input type="hidden" name="offer" value="'.esc_attr($offer).'">';wp_nonce_field('sr_lms_enroll');echo '<button class="lms-primary" type="submit">Request manual enrollment</button></form>';}return true;
}

