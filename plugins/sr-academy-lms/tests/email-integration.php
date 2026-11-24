<?php
/** Run with wp eval-file tests/email-integration.php. No transport delivery. */
if (!function_exists('sr_lms_email_render')) { throw new RuntimeException('Email renderer missing'); }
$GLOBALS['sr_email_test_count']=0;
function sr_email_assert($ok,$message){$GLOBALS['sr_email_test_count']++;if(!$ok)throw new RuntimeException($message);}
$user=(object)['ID'=>1,'display_name'=>'<script>alert(1)</script>','user_email'=>'fixture@example.test'];
$use_defaults=static fn()=>['templates'=>[]];
add_filter('pre_option_sr_lms_email_settings',$use_defaults);
try {
    foreach (array_keys(sr_lms_email_defaults()['templates']) as $type) {
        $rendered=sr_lms_email_render($type,$user)['message'];
        sr_email_assert(!str_contains($rendered,'\n'),$type.' has no literal newline markers');
        if (in_array($type,['package_purchase_admin','discovery_booking_admin','consultation_booking_admin'],true)) {
            sr_email_assert((bool)preg_match('~<br\s*/?>\s*Phone / WhatsApp:~',$rendered),$type.' puts the phone on its own line');
        }
    }
} finally {
    remove_filter('pre_option_sr_lms_email_settings',$use_defaults);
}
$legacy=static fn()=>['templates'=>['discovery_booking_admin'=>['body'=>'Customer: {name}\r\nAppointment: {appointment}\nMeeting ID: {meeting_id}']]];
add_filter('pre_option_sr_lms_email_settings',$legacy);
try {
    $rendered=sr_lms_email_render('discovery_booking_admin',$user,['appointment'=>'Friday at 11:15','meeting_id'=>'12345'])['message'];
    sr_email_assert(!str_contains($rendered,'\n')&&!str_contains($rendered,'\r'),'Saved template newlines are normalized');
    sr_email_assert((bool)preg_match('~<br\s*/?>\s*Appointment: Friday at 11:15<br\s*/?>\s*Meeting ID: 12345~',$rendered),'Saved template formatting and values are preserved');
} finally {
    remove_filter('pre_option_sr_lms_email_settings',$legacy);
}
$mail=sr_lms_email_render('welcome',$user,['password'=>'SECRET','cta_url'=>'javascript:alert(1)']);
sr_email_assert(!str_contains($mail['message'],'<script>'),'User data must be escaped');
sr_email_assert(!str_contains($mail['message'],'SECRET'),'Never render password');
sr_email_assert(!str_contains($mail['message'],'javascript:'),'Unsafe CTA rejected');
$url=network_site_url('wp-login.php?action=rp&key=abc&login=fixture','login');
$mail=sr_lms_email_render('password_reset',$user,['reset_url'=>$url]);
sr_email_assert(str_contains(html_entity_decode($mail['message'],ENT_QUOTES|ENT_HTML5,'UTF-8'),'key=abc&login=fixture'),'Reset URL escaped exactly once');
sr_email_assert(!str_contains($mail['message'],'&amp;amp;'),'No double escape');
$before=get_option('sr_lms_email_settings');
update_option('sr_lms_email_settings',['templates'=>['welcome'=>['enabled'=>false]]]);
sr_email_assert(sr_lms_send_email('welcome',1)===false,'Disabled mail suppressed');
if($before===false)delete_option('sr_lms_email_settings');else update_option('sr_lms_email_settings',$before);
$bad=sr_lms_email_sanitize_settings(['sender_email'=>"bad@example.test\r\nBcc: thief@example.test",'brand'=>['bad'],'color'=>'invalid']);
sr_email_assert(!str_contains($bad['sender_email'],"\n"),'Header injection rejected');
sr_email_assert(is_string($bad['brand']),'Array settings guarded');
echo "Email assertions passed\n";
$native=['to'=>'fixture@example.test','subject'=>'Native','message'=>'Native reset','headers'=>''];
$reset=apply_filters('retrieve_password_notification_email',$native,'abc','fixture',$user);
sr_email_assert($reset['to']===$native['to'],'Native reset recipient preserved');
sr_email_assert(str_contains(html_entity_decode($reset['message'],ENT_QUOTES|ENT_HTML5,'UTF-8'),'key=abc&login=fixture'),'Native reset filter retains usable link');
sr_email_assert(in_array('Content-Type: text/html; charset=UTF-8',$reset['headers'],true),'Reset HTML header scoped');
$before=get_option('sr_lms_email_settings');
try {
    update_option('sr_lms_email_settings',[]);
    $captured=[];
    $capture=static function($result,$atts)use(&$captured){$captured[]=$atts;return true;};
    add_filter('pre_wp_mail',$capture,10,2);
    $fixture=wp_insert_user(['user_login'=>'sr-email-test-'.wp_generate_password(10,false),'user_email'=>'email-regression@example.test','user_pass'=>wp_generate_password(32),'display_name'=>'Email fixture']);
    sr_email_assert(!is_wp_error($fixture),'Fixture account created');
    try {
        $student=get_userdata((int)$fixture);
        $key=get_password_reset_key($student);
        sr_email_assert(!is_wp_error($key),'Student reset key issued');
        $student_reset=apply_filters('retrieve_password_notification_email',$native,$key,$student->user_login,$student);
        $student_message=html_entity_decode($student_reset['message'],ENT_QUOTES|ENT_HTML5,'UTF-8');
        sr_email_assert(str_contains($student_message,sr_lms_page_url('student-portal',['mode'=>'reset-password'])),'Student reset email opens the Student Portal');
        sr_email_assert(!str_contains($student_message,'wp-login.php?action=rp'),'Student reset email avoids the native reset screen');
        foreach(['welcome','login','enrollment','purchase'] as $type)sr_email_assert(sr_lms_send_email($type,(int)$fixture,['offer_title'=>'<b>Course</b>','amount'=>'INR 499.00','payment_id'=>'pay_fixture','order_id'=>'order_fixture']),'Mail accepted by capture');
        sr_email_assert(count($captured)===4,'Exactly one message per send');
        sr_email_assert(str_contains($captured[2]['message'],'&lt;b&gt;Course&lt;/b&gt;'),'Offer placeholder escaped');
        sr_email_assert(!has_filter('wp_mail_content_type','sr_lms_email_content_type'),'No global mail content type callback');
        $count=count($captured);do_action('sr_lms_enrollment_granted',$fixture,'example-course','pay:123');sr_email_assert(count($captured)===$count,'Payment enrollment does not send duplicate enrollment');
        update_option('sr_lms_email_settings',['templates'=>['password_reset'=>['enabled'=>false]]]);
        sr_email_assert(apply_filters('retrieve_password_notification_email',$native,'abc','fixture',$user)===$native,'Disabling branding preserves native password reset');
    } finally {
        remove_filter('pre_wp_mail',$capture,10);
        require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user((int)$fixture);
    }
} finally {
    if($before===false)delete_option('sr_lms_email_settings');else update_option('sr_lms_email_settings',$before);
}
echo "Email transport and native reset assertions passed\n";

WP_CLI::success("PASS: ".$GLOBALS['sr_email_test_count']." email assertions");
