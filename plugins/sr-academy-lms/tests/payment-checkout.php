<?php
if(!defined('WP_CLI')||!WP_CLI)exit;
$GLOBALS['sr_checkout_count']=0;
function sr_checkout_assert($ok,$message){$GLOBALS['sr_checkout_count']++;if(!$ok)throw new RuntimeException($message);}
$old=get_option('sr_lms_payment_settings',null);$uid=0;$local=0;$calls=[];$mails=0;
try{
 $uid=wp_insert_user(['user_login'=>'checkout-'.wp_generate_password(10,false),'user_email'=>'checkout-'.wp_generate_password(10,false).'@example.test','user_pass'=>wp_generate_password(30),'role'=>'subscriber']);if(is_wp_error($uid))throw new RuntimeException('Fixture failed');wp_set_current_user($uid);
 update_option('sr_lms_payment_settings',['enabled'=>1,'manual_requests'=>0,'mode'=>'live','offers'=>['speakathon'],'live_key'=>'rzp_live_fixture','live_secret'=>sr_lms_payment_encrypt('secret'),'live_webhook'=>sr_lms_payment_encrypt('hook')],false);
 $price=(int)round(sr_lms_offers()['speakathon']['price']*100);
 $mock=static function($pre,$args,$url)use(&$calls,$price){if(!str_starts_with($url,'https://api.razorpay.com/v1/'))return $pre;$calls[]=[$url,$args];$data=str_ends_with($url,'/orders')?['id'=>'order_checkoutfixture','amount'=>$price,'currency'=>'INR']:['id'=>'pay_checkoutfixture','order_id'=>'order_checkoutfixture','amount'=>$price,'currency'=>'INR','status'=>'captured','amount_refunded'=>0];return ['response'=>['code'=>200],'headers'=>[],'body'=>wp_json_encode($data)];};add_filter('pre_http_request',$mock,10,3);
 $capture=static function()use(&$mails){$mails++;return true;};add_filter('pre_wp_mail',$capture);
 $r=new WP_REST_Request('POST');$r['offer']='speakathon';$r['amount']=1;$r['currency']='USD';$created=sr_lms_payment_order($r);sr_checkout_assert($created instanceof WP_REST_Response,'Gateway order created');$order=$created->get_data();$local=$order['id'];
 $sent=json_decode($calls[0][1]['body'],true);sr_checkout_assert($sent['amount']===$price&&$sent['currency']==='INR','Server price and currency override client values');sr_checkout_assert(!str_contains(wp_json_encode($order),'secret'),'Order response has no secret');sr_checkout_assert(!sr_lms_has_access($uid,'speakathon'),'Created order does not grant enrollment');
 $v=new WP_REST_Request('POST');$v['id']=$local;$v['razorpay_order_id']=$order['order_id'];$v['razorpay_payment_id']='pay_checkoutfixture';$v['razorpay_signature']=hash_hmac('sha256',$order['order_id'].'|pay_checkoutfixture','secret');$verified=sr_lms_payment_verify($v);
 sr_checkout_assert(!is_wp_error($verified)&&$verified['status']==='paid','Valid callback verifies captured payment');sr_checkout_assert(sr_lms_has_access($uid,'speakathon'),'Captured purchase grants enrolled access');sr_checkout_assert($mails===1,'One receipt issued');sr_lms_payment_verify($v);sr_checkout_assert($mails===1,'Duplicate callback does not duplicate receipt');
 sr_checkout_assert(is_wp_error(sr_lms_payment_order($r)),'Already enrolled user cannot purchase again');$r['offer']='power-dressing';sr_checkout_assert(is_wp_error(sr_lms_payment_order($r)),'Non-sale offer rejected');
 $permission=new WP_REST_Request('POST');sr_checkout_assert(is_wp_error(sr_lms_payment_permission($permission)),'Missing nonce rejected');$permission->set_header('X-WP-Nonce',wp_create_nonce('wp_rest'));sr_checkout_assert(sr_lms_payment_permission($permission)===true,'Authenticated nonce accepted');
}finally{
 if(isset($mock))remove_filter('pre_http_request',$mock,10);if(isset($capture))remove_filter('pre_wp_mail',$capture);if($old===null)delete_option('sr_lms_payment_settings');else update_option('sr_lms_payment_settings',$old,false);
 global $wpdb;if($uid&&!is_wp_error($uid)){$wpdb->delete(sr_lms_table('payments'),['user_id'=>$uid]);$wpdb->delete(sr_lms_table('grants'),['user_id'=>$uid]);require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($uid);}
}
WP_CLI::success('PASS: '.$GLOBALS['sr_checkout_count'].' checkout assertions');
