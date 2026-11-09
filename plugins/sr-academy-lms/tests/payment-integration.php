<?php
if(!defined('WP_CLI')||!WP_CLI)exit;
$original=sr_lms_payment_settings();$uid=0;$ids=[];$GLOBALS['sr_pay_test_count']=0;
function sr_pay_assert($ok,$label){$GLOBALS['sr_pay_test_count']++;if(!$ok)throw new RuntimeException($label);}
try {
 sr_lms_payment_install();$uid=wp_insert_user(['user_login'=>'payfixture'.wp_generate_password(8,false,false),'user_email'=>'payfixture'.wp_generate_password(8,false,false).'@example.test','user_pass'=>wp_generate_password(30),'role'=>'subscriber']);if(is_wp_error($uid))throw new RuntimeException('Fixture user failed');wp_set_current_user($uid);
 update_option('sr_lms_payment_settings',['enabled'=>1,'mode'=>'live','offers'=>['speakathon'],'live_key'=>'rzp_live_fixture','live_secret'=>sr_lms_payment_encrypt('fixture'),'live_webhook'=>sr_lms_payment_encrypt('hook')],false);
 $GLOBALS['sr_pay_fixture']=['id'=>'pay_fixture','order_id'=>'order_fixture','amount'=>10000,'currency'=>'INR','status'=>'captured','amount_refunded'=>0];
 $mock=static function($pre,$args,$url){if(!str_starts_with($url,'https://api.razorpay.com/v1/'))return $pre;return ['headers'=>[],'response'=>['code'=>200],'body'=>wp_json_encode($GLOBALS['sr_pay_fixture'])];};add_filter('pre_http_request',$mock,10,3);
 $mail=static function(){return true;};add_filter('pre_wp_mail',$mail);
 global $wpdb;$now=current_time('mysql',true);$wpdb->insert(sr_lms_table('payments'),['user_id'=>$uid,'offer'=>'speakathon','mode'=>'live','amount'=>10000,'targets'=>'["speakathon"]','gateway_order'=>'order_fixture','created_at'=>$now,'updated_at'=>$now]);$id=(int)$wpdb->insert_id;$ids[]=$id;
 $r=new WP_REST_Request('POST');$r['id']=$id;$r['razorpay_order_id']='order_fixture';$r['razorpay_payment_id']='pay_fixture';$r['razorpay_signature']='bad';sr_pay_assert(is_wp_error(sr_lms_payment_verify($r)),'Bad callback signature rejected');
 $GLOBALS['sr_pay_fixture']['amount']=1;sr_pay_assert(is_wp_error(sr_lms_payment_reconcile($id,'pay_fixture')),'Wrong amount rejected');sr_pay_assert(!sr_lms_has_access($uid,'speakathon'),'Mismatch grants no access');
 $GLOBALS['sr_pay_fixture']['amount']=10000;$GLOBALS['sr_pay_fixture']['status']='authorized';sr_pay_assert(is_wp_error(sr_lms_payment_reconcile($id,'pay_fixture')),'Authorization alone rejected');
 $GLOBALS['sr_pay_fixture']['status']='captured';
 foreach(['START TRANSACTION','FOR UPDATE','COMMIT'] as $failpoint){
  $breakQuery=static function($sql)use($failpoint){return str_contains($sql,$failpoint)?'SR_LMS_FIXTURE_INVALID_SQL':$sql;};
  $oldSuppress=$wpdb->suppress_errors(true);add_filter('query',$breakQuery);$failed=sr_lms_payment_reconcile($id,'pay_fixture');remove_filter('query',$breakQuery);$wpdb->suppress_errors($oldSuppress);
  sr_pay_assert(is_wp_error($failed),'Transaction failure rejected: '.$failpoint);sr_pay_assert(!sr_lms_has_access($uid,'speakathon'),'Transaction failure grants no access: '.$failpoint);
 }
 $GLOBALS['sr_pay_fixture']['status']='refunded';unset($GLOBALS['sr_pay_fixture']['amount_refunded']);sr_pay_assert(is_wp_error(sr_lms_payment_reconcile($id,'pay_fixture')),'Refund status without amount fails closed');
 $GLOBALS['sr_pay_fixture']['status']='captured';$GLOBALS['sr_pay_fixture']['amount_refunded']=0;sr_pay_assert(!is_wp_error(sr_lms_payment_reconcile($id,'pay_fixture')),'Capture reconciled');sr_pay_assert(sr_lms_has_access($uid,'speakathon'),'Captured live payment grants access');
 sr_lms_revoke($uid,'pay:'.$id);sr_lms_payment_reconcile($id,'pay_fixture');sr_pay_assert(!sr_lms_has_access($uid,'speakathon'),'Duplicate callback never restores administrator revoked grant');
 sr_lms_grant($uid,'speakathon','pay:'.$id);$GLOBALS['sr_pay_fixture']['amount_refunded']=5000;sr_lms_payment_reconcile($id,'pay_fixture');sr_pay_assert(sr_lms_has_access($uid,'speakathon'),'Partial refund retains access');
 sr_lms_grant($uid,'speakathon','course:speakathon');$GLOBALS['sr_pay_fixture']['amount_refunded']=10000;sr_lms_payment_reconcile($id,'pay_fixture');sr_pay_assert(sr_lms_has_access($uid,'speakathon'),'Independent grant survives full refund');sr_lms_revoke($uid,'course:speakathon');sr_pay_assert(!sr_lms_has_access($uid,'speakathon'),'Refund removed only purchase grant');
 $GLOBALS['sr_pay_fixture']['amount_refunded']=0;sr_lms_payment_reconcile($id,'pay_fixture');sr_pay_assert(!sr_lms_has_access($uid,'speakathon'),'Stale capture cannot restore refunded access');
 $event=new WP_REST_Request('POST');$event['mode']='live';$event->set_body('{"event":"payment.captured","payload":{"payment":{"entity":{"id":"pay_fixture"}}}}');$event->set_header('X-Razorpay-Signature',hash_hmac('sha256',$event->get_body(),'hook'));$event->set_header('X-Razorpay-Event-Id','fixture-'.$id);sr_pay_assert(!is_wp_error(sr_lms_payment_webhook($event)),'Signed raw webhook accepted');sr_pay_assert(!empty(sr_lms_payment_webhook($event)['duplicate']),'Webhook deduplicated');
 $event['mode']='test';sr_pay_assert(is_wp_error(sr_lms_payment_webhook($event)),'Wrong mode webhook rejected');
 $r['razorpay_signature']=hash_hmac('sha256','order_fixture|pay_fixture','fixture');wp_set_current_user(0);sr_pay_assert(is_wp_error(sr_lms_payment_verify($r)),'Cross-user callback rejected');
 $sealed=sr_lms_payment_settings()['live_secret'];sr_pay_assert($sealed!=='fixture'&&sr_lms_payment_decrypt($sealed)==='fixture','Secret encrypted at rest');
 $wpdb->insert(sr_lms_table('payments'),['user_id'=>$uid,'offer'=>'speakathon','mode'=>'test','amount'=>10000,'targets'=>'["speakathon"]','gateway_order'=>'order_testfixture','created_at'=>$now,'updated_at'=>$now]);$testid=(int)$wpdb->insert_id;$ids[]=$testid;
 $settings=sr_lms_payment_settings();$settings['test_key']='rzp_test_fixture';$settings['test_secret']=sr_lms_payment_encrypt('testfixture');update_option('sr_lms_payment_settings',$settings,false);$GLOBALS['sr_pay_fixture']['order_id']='order_testfixture';$GLOBALS['sr_pay_fixture']['id']='pay_testfixture';wp_set_current_user($uid);
 sr_pay_assert(!is_wp_error(sr_lms_payment_reconcile($testid,'pay_testfixture')),'Test capture reconciles');sr_pay_assert(!sr_lms_has_access($uid,'speakathon'),'Test capture never grants course access');sr_pay_assert((int)$wpdb->get_var($wpdb->prepare('SELECT email_sent FROM '.sr_lms_table('payments').' WHERE id=%d',$testid))===0,'Test capture sends no purchase receipt');
 $result='PASS: '.$GLOBALS['sr_pay_test_count'].' payment integration assertions';
}catch(Throwable $e){$failure=$e->getMessage();}
finally {
 if(isset($mock))remove_filter('pre_http_request',$mock,10);if(isset($mail))remove_filter('pre_wp_mail',$mail);update_option('sr_lms_payment_settings',$original,false);wp_set_current_user(0);
 if($uid&&!is_wp_error($uid)){foreach(['payments','grants'] as $t)$wpdb->delete(sr_lms_table($t),['user_id'=>$uid]);require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($uid);}
 foreach($ids as $id)$wpdb->delete(sr_lms_table('payment_events'),['event_key'=>hash('sha256','live|fixture-'.$id)]);
}
if(isset($failure))WP_CLI::error($failure);WP_CLI::success($result);
