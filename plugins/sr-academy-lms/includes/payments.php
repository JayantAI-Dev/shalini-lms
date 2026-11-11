<?php
if (!defined('ABSPATH')) exit;
function sr_lms_payment_install(): void {
 if(get_option('sr_lms_payment_schema')==='2')return;
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
 update_option('sr_lms_payment_schema','2',false);
}
function sr_lms_payment_settings(): array {return (array)get_option('sr_lms_payment_settings',[]);}
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
 if(!preg_match('~^(?:orders(?:/order_[A-Za-z0-9]+/payments)?|payments/pay_[A-Za-z0-9]+(?:/refunds)?)$~D',$path))return new WP_Error('gateway','Invalid gateway resource.');
 $c=sr_lms_payment_credentials($mode);if(empty($c['key'])||empty($c['secret']))return new WP_Error('gateway','Gateway credentials unavailable.');
 $args=['method'=>$method,'timeout'=>20,'redirection'=>0,'headers'=>['Authorization'=>'Basic '.base64_encode($c['key'].':'.$c['secret']),'Content-Type'=>'application/json']];if($data)$args['body']=wp_json_encode($data);
 $r=wp_remote_request('https://api.razorpay.com/v1/'.$path,$args);if(is_wp_error($r))return new WP_Error('gateway','Gateway could not be reached. Please retry.');
 $j=json_decode(wp_remote_retrieve_body($r),true);if(wp_remote_retrieve_response_code($r)<200||wp_remote_retrieve_response_code($r)>=300||!is_array($j))return new WP_Error('gateway','Gateway verification failed. Please retry.');return $j;
}
function sr_lms_payment_permission($r) {if(!is_user_logged_in()||!wp_verify_nonce($r->get_header('X-WP-Nonce'),'wp_rest'))return new WP_Error('auth','Sign in again and reload the page.',['status'=>403]);if(function_exists('sr_lms_email_is_verified')&&!sr_lms_email_is_verified(get_current_user_id()))return new WP_Error('email_verification','Confirm your email address before purchasing a course.',['status'=>403]);return true;}
function sr_lms_payment_order($r) {
 global $wpdb;if(!sr_lms_payment_ready())return new WP_Error('disabled','Checkout is not available.',['status'=>409]);
 $offer=sr_lms_id($r['offer']??'');$o=sr_lms_offers()[$offer]??null;if(!$o||!in_array($offer,(array)(sr_lms_payment_settings()['offers']??[]),true))return new WP_Error('offer','Unknown offer.',['status'=>400]);
 $already=true;foreach($o['courses'] as $course)if(!sr_lms_has_access(get_current_user_id(),$course))$already=false;if($already)return new WP_Error('enrolled','You already have access to this offer.',['status'=>409]);
 $amount=(int)round((float)$o['price']*100);if($amount<100||$amount>1000000000)return new WP_Error('price','This price cannot be purchased online.',['status'=>400]);
 $mode=sr_lms_payment_settings()['mode']??'test';$now=current_time('mysql',true);
 if(!$wpdb->insert(sr_lms_table('payments'),['user_id'=>get_current_user_id(),'offer'=>$offer,'mode'=>$mode,'amount'=>$amount,'targets'=>wp_json_encode($o['courses']),'created_at'=>$now,'updated_at'=>$now]))return new WP_Error('storage','Could not save order.');$id=(int)$wpdb->insert_id;
 $g=sr_lms_payment_api($mode,'orders',['amount'=>$amount,'currency'=>'INR','receipt'=>'sr-lms-'.$id,'notes'=>['local_order'=>(string)$id]],'POST');
 if(is_wp_error($g))return $g;
 if(!preg_match('/^order_[A-Za-z0-9]+$/D',$g['id']??'')||(int)($g['amount']??0)!==$amount||($g['currency']??'')!=='INR')return new WP_Error('gateway','Unexpected gateway order.');
 if($wpdb->update(sr_lms_table('payments'),['gateway_order'=>$g['id']],['id'=>$id])!==1)return new WP_Error('storage','Could not link gateway order.');
 return new WP_REST_Response(['id'=>$id,'order_id'=>$g['id'],'key'=>sr_lms_payment_credentials($mode)['key'],'amount'=>$amount,'currency'=>'INR','name'=>'SR Academy','description'=>$o['title'],'test'=>$mode==='test']);
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
 if($wpdb->query('START TRANSACTION')===false)return new WP_Error('transaction','Could not begin payment transaction.');$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d FOR UPDATE",$id),ARRAY_A);
 if(!$row){$wpdb->query('ROLLBACK');return new WP_Error('transaction','Could not lock payment order.');}
 $refund=max((int)$row['refunded'],(int)($p['amount_refunded']??0));$full=$refund>=(int)$row['amount']||$row['status']==='refunded';$origin='pay:'.$id;$error=null;
 if($row['gateway_payment']&&$row['gateway_payment']!==$payment)$error=new WP_Error('duplicate','A different payment already belongs to this order.');
 if(!$error&&$row['mode']==='live'){
  if($full){if(!sr_lms_revoke((int)$row['user_id'],$origin))$error=new WP_Error('grant','Could not revoke refunded enrollment.');}
  elseif($row['status']!=='paid') {foreach((array)json_decode($row['targets'],true) as $course){$saved=$wpdb->query($wpdb->prepare('INSERT INTO '.sr_lms_table('grants').' (user_id,course,origin,granted_at,granted_by) VALUES (%d,%s,%s,%s,0) ON DUPLICATE KEY UPDATE origin=VALUES(origin)',$row['user_id'],$course,$origin,current_time('mysql',true)));if($saved===false){$error=new WP_Error('grant','Could not save enrollment.');break;}}}
 }
 $status=$full?'refunded':($row['mode']==='test'?'test_paid':'paid');
 if(!$error&&$wpdb->update($t,['status'=>$status,'gateway_payment'=>$payment,'refunded'=>$refund,'updated_at'=>current_time('mysql',true)],['id'=>$id])===false)$error=new WP_Error('storage','Could not save payment.');
 if($error){$wpdb->query('ROLLBACK');return $error;}if($wpdb->query('COMMIT')===false){$wpdb->query('ROLLBACK');return new WP_Error('transaction','Could not commit payment transaction. Please reconcile again.');}
 // Claim with a short database lease; failed delivery can be retried by reconciliation.
 if(!$full&&$row['mode']==='live'&&function_exists('sr_lms_send_email')){
  $lease=time();$claimed=$wpdb->query($wpdb->prepare("UPDATE $t SET email_sent=%d WHERE id=%d AND (email_sent=0 OR (email_sent<0 AND email_sent > %d))",-$lease,$id,-($lease-300)));
  if($claimed===1){$sent=sr_lms_send_email('purchase',(int)$row['user_id'],['offer_title'=>sr_lms_offers()[$row['offer']]['title']??$row['offer'],'cta_url'=>sr_lms_page_url('dashboard'),'amount'=>'INR '.number_format($row['amount']/100,2),'currency'=>'INR','payment_id'=>$payment,'order_id'=>$row['gateway_order'],'mode'=>$row['mode']]);$wpdb->query($wpdb->prepare("UPDATE $t SET email_sent=%d WHERE id=%d AND email_sent=%d",($sent&&!is_wp_error($sent))?1:0,$id,-$lease));}
 }

 return ['status'=>$status,'test'=>$row['mode']==='test'];
}
function sr_lms_payment_verify($r) {
 global $wpdb;$id=absint($r['id']??0);$row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.sr_lms_table('payments').' WHERE id=%d AND user_id=%d',$id,get_current_user_id()),ARRAY_A);
 if(!$row)return new WP_Error('order','Unknown order.',['status'=>404]);$payment=(string)($r['razorpay_payment_id']??'');$c=sr_lms_payment_credentials($row['mode']);
 if(($r['razorpay_order_id']??'')!==$row['gateway_order']||!sr_lms_payment_signature($row['gateway_order'].'|'.$payment,(string)($r['razorpay_signature']??''),$c['secret']))return new WP_Error('signature','Payment signature rejected.',['status'=>400]);
 return sr_lms_payment_reconcile($id,$payment);
}
function sr_lms_payment_webhook($r) {
 global $wpdb;$mode=$r['mode'];$c=sr_lms_payment_credentials($mode);$raw=$r->get_body();if(strlen($raw)>1048576||!sr_lms_payment_signature($raw,$r->get_header('X-Razorpay-Signature'),$c['webhook']??''))return new WP_Error('signature','Webhook signature rejected.',['status'=>400]);
 $j=json_decode($raw,true);if(!is_array($j))return new WP_Error('json','Invalid event.',['status'=>400]);
 if(!in_array($j['event']??'',['payment.captured','refund.processed'],true))return ['ignored'=>true];
 $key=hash('sha256',$mode.'|'.($r->get_header('X-Razorpay-Event-Id')?:$raw));$events=sr_lms_table('payment_events');if($wpdb->get_var($wpdb->prepare("SELECT id FROM $events WHERE event_key=%s",$key)))return ['duplicate'=>true];
 $p=$j['payload']['payment']['entity']??[];$payment=$p['id']??($j['payload']['refund']['entity']['payment_id']??'');
 if(!preg_match('/^pay_[A-Za-z0-9]+$/D',$payment))return new WP_Error('event','Missing payment.',['status'=>400]);
 $p=sr_lms_payment_api($mode,'payments/'.$payment);if(is_wp_error($p))return $p;$id=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.sr_lms_table('payments').' WHERE mode=%s AND gateway_order=%s',$mode,$p['order_id']??''));if(!$id)return ['ignored'=>true];
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
 $targets=sr_lms_offers()[$offer]['courses'];$enrolled=is_user_logged_in();foreach($targets as $course)if(!sr_lms_has_access(get_current_user_id(),$course))$enrolled=false;if($enrolled){echo '<p>You already have access to this offer.</p><a class="lms-primary" href="'.esc_url(sr_lms_page_url('dashboard')).'">Open dashboard</a>';return true;}
 if(!is_user_logged_in()){echo '<p>Sign in to purchase this course.</p><a class="lms-primary" href="'.esc_url(sr_lms_page_url('student-portal',['redirect_to'=>sr_lms_page_url('enroll',['offer'=>$offer])])).'">Sign in to continue</a>';return true;}
 wp_enqueue_script('sr-lms-razorpay','https://checkout.razorpay.com/v1/checkout.js',[],null,true);
 wp_enqueue_script('sr-lms-payments',plugins_url('assets/payments.js',dirname(__DIR__).'/sr-academy-lms.php'),['sr-lms-razorpay'],SR_LMS_VERSION,true);
 echo '<div class="sr-lms-purchase" data-dashboard="'.esc_url(sr_lms_page_url('dashboard')).'" data-offer="'.esc_attr($offer).'" data-api="'.esc_url(rest_url('sr-lms/v1/payments/')).'" data-nonce="'.esc_attr(wp_create_nonce('wp_rest')).'"><button type="button" class="lms-primary">'.((sr_lms_payment_settings()['mode']??'test')==='test'?'Test checkout · no enrollment':'Pay securely with Razorpay').'</button><p role="status" aria-live="polite"></p></div>';if(sr_lms_manual_requests_enabled()){echo '<p>Prefer to request access from the academy?</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="sr_lms_enroll"><input type="hidden" name="offer" value="'.esc_attr($offer).'">';wp_nonce_field('sr_lms_enroll');echo '<button class="lms-primary" type="submit">Request manual enrollment</button></form>';}return true;
}
