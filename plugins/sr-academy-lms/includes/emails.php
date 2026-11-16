<?php
/** Scoped, configurable account notifications. Transport remains WordPress wp_mail. */
if (!defined('ABSPATH')) exit;
function sr_lms_email_defaults(): array {
    $templates=[];
    foreach([
        'welcome'=>['Welcome to {brand}','Welcome, {name}','Your student account is ready. Explore your learning dashboard.','Open dashboard'],
        'verification'=>['Confirm your {brand} email','Confirm your email address','Please confirm this email address to activate your student account and access your learning dashboard. This link expires in 48 hours.','Confirm email address'],
        'login'=>['New sign-in to {brand}','You signed in','Your account was just used to sign in. If this was not you, reset your password or contact support.','Open dashboard'],
        'password_reset'=>['Reset your {brand} password','Reset your password','A password reset was requested for your account. Use the button below to choose a new password. If you did not request this, ignore this email.','Reset password'],
        'enrollment'=>['Your course access is ready','You are enrolled','You now have access to {offer_title}. Visit your dashboard to start learning.','Start learning'],
        'purchase'=>['Your {brand} purchase receipt','Thank you for your purchase','Your purchase of {offer_title} is complete. Sign in with {customer_email} to find this course in your student dashboard.','Open dashboard'],
        'course_access'=>['Your course is ready: {offer_title}','Your course purchase is confirmed','Hello {name}, payment for {offer_title} is confirmed. Use the secure link below to set your password. Your student sign-in email is shown below.','Set your password'],
        'course_test_purchase'=>['Test enrollment confirmation: {offer_title}','Test enrollment confirmed','Hello {name}, this is a test confirmation for {offer_title}. The transaction was processed in Razorpay test mode; no live payment was charged. {test_access_message} {test_credentials_message}','Open Student Portal'],
        'package_purchase'=>['Package purchase confirmed: {offer_title}','Thank you for your purchase','Hello {name}, your payment for {offer_title} is confirmed. Our team will contact you within 48 hours to arrange your package.','Contact the Academy'],
        'package_purchase_admin'=>['New package purchase: {offer_title}','A package purchase is confirmed','Customer: {name} ({customer_email})\nPhone / WhatsApp: {customer_phone}\nPackage: {offer_title}\nPlease contact the customer within 48 hours.','View booking details'],
        'discovery_booking'=>['Your free discovery call is confirmed','Your free call is booked','Hello {name}, your complimentary 15-minute discovery call is confirmed for {appointment}. A Google Calendar invitation has been sent to you and the Academy team. Use the button below to join your Zoom call. Meeting ID: {meeting_id}.','Join your Zoom call'],
        'discovery_booking_admin'=>['New free discovery call: {name}','A free discovery call is booked','Customer: {name} ({customer_email})\nPhone / WhatsApp: {customer_phone}\nAppointment: {appointment}\nMeeting ID: {meeting_id}\nA Google Calendar invitation was sent to the customer and Academy team.','Join the Zoom call'],
        'consultation_booking'=>['Your consultation is confirmed: {offer_title}','Your consultation is confirmed','Hello {name}, your payment is confirmed and your consultation is booked for {appointment}. A Google Calendar invitation has been sent to you and the Academy team. Use the button below to join your Zoom call. Meeting ID: {meeting_id}.','Join your Zoom call'],
        'consultation_booking_admin'=>['New paid consultation: {offer_title}','A paid consultation is booked','Customer: {name} ({customer_email})\nPhone / WhatsApp: {customer_phone}\nService: {offer_title}\nAppointment: {appointment}\nMeeting ID: {meeting_id}\nA Google Calendar invitation was sent to the customer and Academy team.','Join the Zoom call'],
    ] as $type=>$values)$templates[$type]=array_combine(['subject','heading','body','cta'],$values)+['enabled'=>true];
    return ['brand'=>'Shalini Rathod Academy','sender_name'=>'Shalini Rathod Academy','sender_email'=>sanitize_email(get_option('admin_email')),'reply_to'=>sanitize_email(get_option('admin_email')),'support_url'=>home_url('/contact/'),'logo_url'=>'','color'=>'#125c45','templates'=>$templates];
}
function sr_lms_email_settings(): array { $saved=get_option('sr_lms_email_settings',[]);return array_replace_recursive(sr_lms_email_defaults(),is_array($saved)?$saved:[]); }
function sr_lms_email_sanitize_settings(array $input): array {
    $old=sr_lms_email_settings();$out=$old;
    foreach(['brand','sender_name','sender_email','reply_to','support_url','logo_url','color'] as $key){
        if(!isset($input[$key])||!is_string($input[$key]))continue;$value=trim(wp_unslash($input[$key]));
        if($value===''&&$key!=='logo_url')continue;
        if(in_array($key,['sender_email','reply_to'],true)){$value=preg_match('/[\r\n]/',$value)?'':sanitize_email($value);if(!is_email($value))continue;}
        elseif(in_array($key,['support_url','logo_url'],true))$value=esc_url_raw($value,['https','http']);
        elseif($key==='color'){$value=sanitize_hex_color($value);if(!$value)continue;}
        else $value=sanitize_text_field($value);
        $out[$key]=$value;
    }
    foreach($old['templates'] as $type=>$template){$row=$input['templates'][$type]??null;if(!is_array($row))continue;$out['templates'][$type]['enabled']=in_array($type,['verification','course_access','enrollment'],true)||(!empty($row['enabled'])&&is_scalar($row['enabled']));foreach(['subject','heading','body','cta'] as $key)if(isset($row[$key])&&is_string($row[$key])&&trim($row[$key])!=='')$out['templates'][$type][$key]=$key==='body'?sanitize_textarea_field(wp_unslash($row[$key])):sanitize_text_field(wp_unslash($row[$key]));}
    return $out;
}
function sr_lms_email_safe_cta(string $url, bool $allow_zoom = false): string {
    $parts=wp_parse_url($url);$home=wp_parse_url(home_url());$network=wp_parse_url(network_site_url());
    $host=strtolower($parts['host']??'');$allowed_hosts=array_map('strtolower',[$home['host']??'',$network['host']??'']);
    $same_site=in_array($host,$allowed_hosts,true);$zoom_host=$allow_zoom&&preg_match('/(^|\.)zoom\.us$/D',$host);
    return $parts&&in_array(strtolower($parts['scheme']??''),['http','https'],true)&&($same_site||$zoom_host)&&empty($parts['user'])&&empty($parts['pass'])?$url:home_url('/dashboard/');
}
function sr_lms_email_payment_summary(array $context): string {
    $rows = [];
    foreach ([
        'amount' => 'Amount paid',
        'payment_id' => 'Payment reference',
        'order_id' => 'Order ID',
    ] as $key => $label) {
        if (!isset($context[$key]) || !is_scalar($context[$key]) || trim((string)$context[$key]) === '') continue;
        $value = esc_html((string)$context[$key]);
        $value_style = $key === 'amount'
            ? 'display:block;margin-top:5px;color:#18382c;font-size:20px;font-weight:700;line-height:1.35'
            : 'display:block;margin-top:6px;padding:10px 12px;border:1px solid #dce8dc;border-radius:6px;background:#fff;color:#18382c;font-family:Consolas,Monaco,monospace;font-size:14px;line-height:1.5;word-break:break-all;overflow-wrap:anywhere';
        $rows[] = '<tr><td style="padding:12px 16px;border-top:1px solid #e3ece2;color:#627268;font-size:13px;line-height:1.4;vertical-align:top"><span style="display:block">'.esc_html($label).'</span><span style="'.$value_style.'">'.$value.'</span></td></tr>';
    }
    if (!$rows) return '';
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;table-layout:fixed;margin:24px 0 8px;border:1px solid #dce8dc;border-radius:10px;background:#f5f8f3"><tr><td style="padding:16px 16px 12px;color:#286447;font-size:12px;font-weight:700;letter-spacing:1px;line-height:1.4;text-transform:uppercase">Payment details</td></tr>'.implode('', $rows).'<tr><td style="height:4px;padding:0;font-size:1px;line-height:4px">&nbsp;</td></tr></table>';
}
function sr_lms_email_render(string $type,object $user,array $context=[]): array {
    $settings=sr_lms_email_settings();if(!isset($settings['templates'][$type]))return [];$template=$settings['templates'][$type];
    if($type==='course_access'){
        $template['subject']='Course access details: {offer_title}';
        $template['heading']='Your course enrollment is confirmed';
        $template['cta']='Set your password';
        $template['body']="Hello {name},\n\nYour enrollment in {offer_title} is confirmed. Use the secure button below to set your password, then sign in with the email address shown below to access your course.";
    }
    if($type==='course_test_purchase'){$template['subject']='Test enrollment confirmation: {offer_title}';$template['heading']='Test enrollment confirmed';$template['body']="Hello {name},\n\nThis is a test confirmation for {offer_title}. The transaction was processed in Razorpay test mode; no live payment was charged.\n\n{test_access_message}\n\n{test_credentials_message}";$template['cta']='Open Student Portal';}
    $values=['{brand}'=>$settings['brand'],'{name}'=>$user->display_name ?? 'there','{offer_title}'=>'your course','{amount}'=>'','{payment_id}'=>'','{order_id}'=>'','{customer_email}'=>'','{customer_phone}'=>'','{appointment}'=>'','{meeting_id}'=>'','{test_access_message}'=>'No course access was activated.'];
    foreach(['name','offer_title','amount','payment_id','order_id','customer_email','customer_password','customer_phone','appointment','meeting_id','test_access_message','test_credentials_message'] as $key)if(isset($context[$key])&&is_scalar($context[$key]))$values['{'.$key.'}']=(string)$context[$key];
    // Payment identifiers are presented in a dedicated, readable details panel below.
    // Remove their old inline form too, including from templates already saved in WP.
    $body_text = (string)$template['body'];
    $body_text = preg_replace('/(?:^|[ \t])(?:Amount:\s*\{amount\}|Payment reference:\s*\{payment_id\}|Order (?:reference|ID):\s*\{order_id\})\.?[ \t]*(?=\R|$)/mi', '', $body_text);
    $body_text = preg_replace('/\s*(?:Amount:\s*\{amount\}|Payment reference:\s*\{payment_id\}|Order (?:reference|ID):\s*\{order_id\})\.?/i', '', $body_text);
    $body_text = preg_replace('/([.!?])(?=[A-Za-z])/', '$1 ', $body_text);
    $body_text = preg_replace('/[ \t]{2,}/', ' ', $body_text);
    $body_text = preg_replace('/\R{3,}/', "\n\n", trim($body_text));
    $subject=sanitize_text_field(strtr($template['subject'],$values));$heading=esc_html(strtr($template['heading'],$values));$body=nl2br(esc_html(strtr($body_text,$values)));
    $credentials_html='';
    if(in_array($type,['course_access','course_test_purchase'],true)&&is_email((string)($context['customer_email']??''))){
        $login_email=esc_html((string)$context['customer_email']);
        $password=(string)($context['customer_password']??'');
        if($type==='course_access'){
            $credentials_html='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;background:#f2f7f1;border:1px solid #dce8db;border-radius:12px"><tr><td style="padding:18px 16px"><p style="margin:0 0 14px;color:#286447;font-size:12px;font-weight:bold;letter-spacing:1.2px">YOUR STUDENT SIGN-IN</p><p style="margin:0 0 6px;color:#52645a;font-size:13px;font-weight:bold">EMAIL ADDRESS</p><div style="padding:13px 15px;border:1px solid #dce8db;border-radius:8px;background:#fff;color:#18382c;font-family:Arial,sans-serif;font-size:16px;line-height:1.5;word-break:break-word">'.$login_email.'</div><p style="margin:12px 0 0;color:#52645a;font-size:14px;line-height:1.6">Set a password using the secure button above, then sign in with this email.</p></td></tr></table>';
        }else{
            $password_value=$password!==''?'<div style="padding:13px 15px;border:1px solid #dce8db;border-radius:8px;background:#fff;color:#18382c;font-family:Arial,sans-serif;font-size:16px;line-height:1.5;word-break:break-word">'.esc_html($password).'</div>':'<p style="margin:0;color:#52645a;font-size:14px;line-height:1.6">Use the secure password setup link above to create your password.</p>';
            $credentials_html='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;background:#f2f7f1;border:1px solid #dce8db;border-radius:12px"><tr><td style="padding:18px 16px"><p style="margin:0 0 14px;color:#286447;font-size:12px;font-weight:bold;letter-spacing:1.2px">STUDENT PORTAL SIGN-IN</p><p style="margin:0 0 6px;color:#52645a;font-size:13px;font-weight:bold">EMAIL ADDRESS</p><div style="margin:0 0 14px;padding:13px 15px;border:1px solid #dce8db;border-radius:8px;background:#fff;color:#18382c;font-family:Arial,sans-serif;font-size:16px;line-height:1.5;word-break:break-word">'.$login_email.'</div><p style="margin:0 0 6px;color:#52645a;font-size:13px;font-weight:bold">PASSWORD</p>'.$password_value.'</td></tr></table>';
        }
    }
    $payment_summary = sr_lms_email_payment_summary($context);
    $url=in_array($type,['password_reset','course_access'],true)&&!empty($context['reset_url'])?($context['reset_url']):($type==='verification'?($context['verify_url']??''):($context['cta_url']??home_url('/dashboard/')));$url=sr_lms_email_safe_cta(is_string($url)?$url:'',str_contains($type,'booking'));
    $logo=$settings['logo_url']?'<img src="'.esc_url($settings['logo_url']).'" alt="'.esc_attr($settings['brand']).'" style="max-width:200px;height:auto;margin-bottom:20px">':'';
    $html='<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><meta charset="UTF-8"></head><body style="margin:0;background:#f2f6f3;font-family:Arial,sans-serif;color:#18382c"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:28px 12px"><table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#fff;border-radius:16px"><tr><td style="padding:36px">'.$logo.'<p style="margin:0 0 18px;font-size:14px;line-height:1.5;color:'.esc_attr($settings['color']).'">'.esc_html($settings['brand']).'</p><h1 style="margin:0 0 20px;font-size:28px;line-height:1.25">'.$heading.'</h1><p style="margin:0;font-size:16px;line-height:1.75">'.$body.'</p>'.$credentials_html.$payment_summary.'<p style="margin:28px 0"><a href="'.esc_url($url).'" style="display:inline-block;padding:15px 24px;border-radius:7px;background:'.esc_attr($settings['color']).';color:#fff;text-decoration:none">'.esc_html($template['cta']).'</a></p><p style="margin:32px 0 0;font-size:12px;line-height:1.7;color:#66766e">Need help? <a href="'.esc_url($settings['support_url']).'" style="color:'.esc_attr($settings['color']).'">Contact support</a><br>'.esc_html($settings['brand']).'<br>This is an account notification. We will never ask you to share your password.</p></td></tr></table></td></tr></table></body></html>';
    return ['subject'=>$subject,'message'=>$html,'headers'=>['Content-Type: text/html; charset=UTF-8','From: '.sanitize_text_field($settings['sender_name']).' <'.$settings['sender_email'].'>','Reply-To: '.$settings['reply_to']]];
}
function sr_lms_send_email(string $type,int $user_id,array $context=[]): bool {
    $user=get_userdata($user_id);if(!$user)return false;return sr_lms_send_email_to($type,(string)$user->user_email,(string)($user->display_name??''),$context);
}
function sr_lms_send_email_to(string $type,string $email,string $name,array $context=[]): bool {
    $settings=sr_lms_email_settings();if(!in_array($type,['verification','course_access','enrollment'],true)&&empty($settings['templates'][$type]['enabled']))return false;$email=sanitize_email($email);if(!is_email($email))return false;$mail=sr_lms_email_render($type,(object)['display_name'=>$name?:'there'],$context);if(!$mail)return false;return wp_mail($email,$mail['subject'],$mail['message'],$mail['headers']);
}
add_action('sr_lms_enrollment_granted',static function($user_id,$offer,$origin){if(str_starts_with((string)$origin,'pay:'))return;$offers=sr_lms_offers();sr_lms_send_email('enrollment',(int)$user_id,['offer_title'=>$offers[$offer]['title']??$offer]);},10,3);
add_filter('retrieve_password_notification_email',static function($email,$key,$login,$user){
    $settings=sr_lms_email_settings();if(empty($settings['templates']['password_reset']['enabled']))return $email;
    $url=network_site_url('wp-login.php?action=rp&key='.rawurlencode($key).'&login='.rawurlencode($login),'login');$mail=sr_lms_email_render('password_reset',$user,['reset_url'=>$url]);return array_merge($email,$mail);
},10,4);
add_action('after_password_reset',static function($user){if($user instanceof WP_User&&get_user_meta((int)$user->ID,'sr_lms_course_password_setup_pending',true)){update_user_meta((int)$user->ID,'sr_lms_email_verified','1');delete_user_meta((int)$user->ID,'sr_lms_course_password_setup_pending');delete_user_meta((int)$user->ID,'sr_lms_email_verify_hash');delete_user_meta((int)$user->ID,'sr_lms_email_verify_expires');delete_user_meta((int)$user->ID,'sr_lms_payment_access_until');}});
add_action('wp_mail_failed',static function($error){update_option('sr_lms_email_last_error',['time'=>gmdate('c'),'code'=>sanitize_key($error->get_error_code())],false);});
add_action('admin_menu',static function(){add_submenu_page('sr-academy-lms','Emails','Emails','manage_options','sr-lms-emails','sr_lms_email_screen');});
function sr_lms_email_screen(): void {
    if(!current_user_can('manage_options'))return;$settings=sr_lms_email_settings();echo '<div class="wrap"><h1>Academy emails</h1><p>Account notifications use WordPress mail. Configure SMTP through your preferred mail plugin if required. Available placeholders: {name}, {brand}, {offer_title}, {amount}, {payment_id}, {order_id}, {customer_email}, {customer_phone}, {appointment}, {meeting_id}. Paid package and booking confirmation emails are transactional and are sent to customers and the booking admin.</p>';
    if(isset($_GET['email_result']))echo '<div class="notice notice-info"><p>'.esc_html(($_GET['email_result']==='saved')?'Settings saved.':(($_GET['email_result']==='sent')?'Test accepted by mail transport.':'Mail was not accepted by transport.')).'</p></div>';
    $error=get_option('sr_lms_email_last_error');if(is_array($error))echo '<p>Last transport failure: '.esc_html($error['time'].' ('.$error['code'].')').'. Check your mail transport configuration.</p>';
    echo '<form action="'.esc_url(admin_url('admin-post.php')).'" method="post"><input type="hidden" name="action" value="sr_lms_email_settings">';wp_nonce_field('sr_lms_email_settings');
    foreach(['brand'=>'Brand name','sender_name'=>'Sender name','sender_email'=>'Sender email','reply_to'=>'Reply-to email','support_url'=>'Support URL','logo_url'=>'Logo URL','color'=>'Brand color'] as $key=>$label)echo '<p><label>'.esc_html($label).' <input class="regular-text" name="settings['.$key.']" value="'.esc_attr($settings[$key]).'"></label></p>';
    foreach($settings['templates'] as $type=>$template){echo '<fieldset style="border:1px solid #ccd0d4;padding:16px;margin:16px 0"><legend><strong>'.esc_html(ucwords(str_replace('_',' ',$type))).'</strong></legend>';if(in_array($type,['verification','course_access','enrollment'],true)){echo '<input type="hidden" name="settings[templates]['.esc_attr($type).'][enabled]" value="1"><p><label><input type="checkbox" checked disabled> Required for student access and enrollment</label></p>';}else echo '<p><label><input type="checkbox" name="settings[templates]['.$type.'][enabled]" value="1" '.checked($template['enabled'],true,false).'> Enable branded notification</label></p>';foreach(['subject','heading','body','cta'] as $key){$name='settings[templates]['.$type.']['.$key.']';echo '<p><label>'.esc_html(ucfirst($key)).'<br>';echo $key==='body'?'<textarea rows="4" cols="90" name="'.$name.'">'.esc_textarea($template[$key]).'</textarea>':'<input class="large-text" name="'.$name.'" value="'.esc_attr($template[$key]).'">';echo '</label></p>';}echo '</fieldset>';}
    echo '<p>Disabling branded password reset preserves WordPress’s native password reset email.</p><button class="button button-primary">Save email settings</button></form><h2>Preview and test</h2><form action="'.esc_url(admin_url('admin-post.php')).'" method="post"><input type="hidden" name="action" value="sr_lms_email_test">';wp_nonce_field('sr_lms_email_test');echo '<select name="type">';foreach(array_keys($settings['templates']) as $type)echo '<option value="'.esc_attr($type).'">'.esc_html($type).'</option>';echo '</select> <input type="email" name="recipient" placeholder="Explicit test recipient"> <button class="button" name="operation" value="preview">Preview</button> <button class="button" name="operation" value="send">Send test email</button></form></div>';
}
add_action('admin_post_sr_lms_email_settings',static function(){if(!current_user_can('manage_options'))wp_die('Administrator permission required.',403);check_admin_referer('sr_lms_email_settings');$input=$_POST['settings']??[];if(!is_array($input))wp_die('Invalid settings');update_option('sr_lms_email_settings',sr_lms_email_sanitize_settings($input));wp_safe_redirect(admin_url('admin.php?page=sr-lms-emails&email_result=saved'));exit;});
add_action('admin_post_sr_lms_email_test',static function(){if(!current_user_can('manage_options'))wp_die('Administrator permission required.',403);check_admin_referer('sr_lms_email_test');$type=isset($_POST['type'])&&is_string($_POST['type'])?sanitize_key($_POST['type']):'';$mail=sr_lms_email_render($type,(object)['display_name'=>'Example student'],['offer_title'=>'Example course','amount'=>'INR 499.00','payment_id'=>'sample-payment','order_id'=>'sample-order','reset_url'=>home_url('/wp-login.php?action=lostpassword')]);if(!$mail)wp_die('Invalid email type');if(($_POST['operation']??'')==='preview'){header('Content-Type: text/html; charset=UTF-8');header("Content-Security-Policy: default-src 'none'; img-src https: http:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'");echo $mail['message'];exit;}$recipient=isset($_POST['recipient'])&&is_string($_POST['recipient'])?trim(wp_unslash($_POST['recipient'])):'';if(preg_match('/[\r\n]/',$recipient)||!is_email($recipient))wp_die('Enter one valid test recipient.');$sent=wp_mail($recipient,$mail['subject'],$mail['message'],$mail['headers']);wp_safe_redirect(admin_url('admin.php?page=sr-lms-emails&email_result='.($sent?'sent':'failed')));exit;});

