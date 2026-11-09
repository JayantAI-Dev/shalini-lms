<?php
if (!defined('ABSPATH')) exit;
function sr_lms_table(string $name): string {global $wpdb;return $wpdb->prefix.'sr_lms_'.$name;}
function sr_lms_install(): void {
    global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$charset=$wpdb->get_charset_collate();
    dbDelta('CREATE TABLE '.sr_lms_table('grants')." (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint unsigned NOT NULL,
        course varchar(100) NOT NULL,
        origin varchar(120) NOT NULL,
        granted_at datetime NOT NULL,
        granted_by bigint unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY entitlement (user_id,course,origin),
        KEY student_course (user_id,course)
    ) $charset;");
    dbDelta('CREATE TABLE '.sr_lms_table('requests')." (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint unsigned NOT NULL,
        offer varchar(100) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY student_offer (user_id,offer),
        KEY request_status (status)
    ) $charset;");
    dbDelta('CREATE TABLE '.sr_lms_table('progress')." (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint unsigned NOT NULL,
        course varchar(100) NOT NULL,
        lesson varchar(100) NOT NULL,
        state longtext NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY student_lesson (user_id,course,lesson)
    ) $charset;");
    update_option('sr_lms_version',SR_LMS_VERSION,false);
}
function sr_lms_has_access($user_id,$course): bool {
    global $wpdb;$user_id=absint($user_id);$course=sr_lms_id($course);
    if(!$user_id||!$course||!array_key_exists($course,sr_lms_catalog()))return false;
    if(user_can($user_id,'manage_options'))return true;
    return (bool)$wpdb->get_var($wpdb->prepare('SELECT id FROM '.sr_lms_table('grants').' WHERE user_id=%d AND course=%s LIMIT 1',$user_id,$course));
}
function sr_lms_grant(int $user_id,string $offer,string $origin='') {
    global $wpdb;$targets=sr_lms_grant_targets($offer,sr_lms_catalog());
    if(!get_userdata($user_id)||!$targets)return new WP_Error('grant','Unknown student or course/package.');
    $origin=$origin?:'offer:'.$offer;
    if(!preg_match('/^(?:(?:offer|course):[a-z0-9-]+|pay:[1-9][0-9]*)$/D',$origin))return new WP_Error('grant','Invalid grant origin.');
    $new_grants=false;
    foreach($targets as $course){$result=$wpdb->query($wpdb->prepare('INSERT INTO '.sr_lms_table('grants').' (user_id,course,origin,granted_at,granted_by) VALUES (%d,%s,%s,%s,%d) ON DUPLICATE KEY UPDATE origin=VALUES(origin)',$user_id,$course,$origin,current_time('mysql',true),get_current_user_id()));if($result===false)return new WP_Error('grant','Could not save enrollment grant.');if($result>0)$new_grants=true;}
    if($new_grants)do_action('sr_lms_enrollment_granted',$user_id,$offer,$origin);
    return $targets;
}
function sr_lms_revoke(int $user_id,string $origin): bool {global $wpdb;if(!preg_match('/^(?:(?:offer|course):[a-z0-9-]+|pay:[1-9][0-9]*)$/D',$origin))return false;return $wpdb->delete(sr_lms_table('grants'),['user_id'=>$user_id,'origin'=>$origin],['%d','%s'])!==false;}
function sr_lms_request_enrollment(int $user_id,string $offer) {
    global $wpdb;if(!get_userdata($user_id)||!isset(sr_lms_offers()[$offer]))return new WP_Error('offer','Unknown enrollment offer.');
    $now=current_time('mysql',true);$sql=$wpdb->prepare('INSERT INTO '.sr_lms_table('requests').' (user_id,offer,status,created_at,updated_at) VALUES (%d,%s,%s,%s,%s) ON DUPLICATE KEY UPDATE status="pending",updated_at=VALUES(updated_at)',$user_id,$offer,'pending',$now,$now);
    if($wpdb->query($sql)===false)return new WP_Error('request','Could not save enrollment request.');return true;
}
function sr_lms_progress(int $user_id,string $course): array {
    global $wpdb;$rows=$wpdb->get_results($wpdb->prepare('SELECT lesson,state FROM '.sr_lms_table('progress').' WHERE user_id=%d AND course=%s',$user_id,$course),ARRAY_A);$progress=[];
    foreach($rows as $row){$state=json_decode($row['state'],true);if(is_array($state))$progress[$row['lesson']]=$state;}return $progress;
}
function sr_lms_save_progress(int $user_id,string $course,string $lesson_id,array $input) {
    global $wpdb;if(!sr_lms_has_access($user_id,$course))return new WP_Error('forbidden','Enrollment is required.',['status'=>403]);
    $lesson=sr_lms_lesson($course,$lesson_id);
    if(!$lesson)return new WP_Error('lesson','Unknown lesson.',['status'=>404]);
    if(!sr_lms_private_file($lesson['videoUrl']??'')||empty($lesson['duration']))return new WP_Error('unavailable','This recording or its verified duration is unavailable.',['status'=>409]);
    $table=sr_lms_table('progress');
    // Compare-and-swap avoids losing watched ranges when two tabs save together.
    for($attempt=0;$attempt<5;$attempt++){
        $old=$wpdb->get_var($wpdb->prepare("SELECT state FROM $table WHERE user_id=%d AND course=%s AND lesson=%s",$user_id,$course,$lesson_id));
        $state=$old?json_decode($old,true):[];if(!is_array($state))$state=[];
        try{$next=sr_lms_merge_progress($state,$input,(float)$lesson['duration']);}catch(InvalidArgumentException $e){return new WP_Error('progress',$e->getMessage(),['status'=>400]);}
        $next['updated_at']=gmdate('c');$next['complete']=sr_lms_lesson_complete($next,(float)$lesson['duration']);
        if($next['complete'])$next['completed_at']=$state['completed_at']??gmdate('c');
        $json=wp_json_encode($next);$now=current_time('mysql',true);
        if($old!==null){$saved=$wpdb->query($wpdb->prepare("UPDATE $table SET state=%s,updated_at=%s WHERE user_id=%d AND course=%s AND lesson=%s AND BINARY state=BINARY %s",$json,$now,$user_id,$course,$lesson_id,$old));if($saved===1||$json===$old)return $next;}
        else {$saved=$wpdb->query($wpdb->prepare("INSERT IGNORE INTO $table (user_id,course,lesson,state,updated_at) VALUES (%d,%s,%s,%s,%s)",$user_id,$course,$lesson_id,$json,$now));if($saved===1)return $next;}
    }return new WP_Error('conflict','Progress changed in another window. Please retry.',['status'=>409]);
}
function sr_lms_completion(int $user_id,string $course): array {
    $record=sr_lms_catalog()[$course]??[];$lessons=sr_lms_lessons($record);$progress=sr_lms_progress($user_id,$course);$available=[];$complete=0;
    foreach($lessons as $lesson){$available[$lesson['id']]=(bool)sr_lms_private_file($lesson['videoUrl']??'')&&!empty($lesson['duration']);if($available[$lesson['id']]&&sr_lms_lesson_complete($progress[$lesson['id']]??[],(float)($lesson['duration']??0)))$complete++;}
    $eligible=sr_lms_certificate_eligible($lessons,$progress,$available);
    return ['completed'=>$complete,'total'=>count($lessons),'percent'=>count($lessons)?(int)round(100*$complete/count($lessons)):0,'eligible'=>$eligible];
}
function sr_lms_rest_permission(WP_REST_Request $request) {
    if(!is_user_logged_in())return new WP_Error('auth','Please sign in.',['status'=>401]);
    if(function_exists('sr_lms_email_is_verified')&&!sr_lms_email_is_verified(get_current_user_id()))return new WP_Error('email_verification','Confirm your email address to use your student account.',['status'=>403]);
    if(!wp_verify_nonce($request->get_header('X-WP-Nonce'),'wp_rest'))return new WP_Error('nonce','Your session has expired. Reload this page.',['status'=>403]);
    $course=sr_lms_id($request['course']??'');if(!$course||!isset(sr_lms_catalog()[$course]))return new WP_Error('course','Unknown course.',['status'=>404]);
    return sr_lms_has_access(get_current_user_id(),$course)?true:new WP_Error('enrollment','Course enrollment is required.',['status'=>403]);
}
function sr_lms_register_routes(): void {
    register_rest_route('sr-lms/v1','/courses/(?P<course>[a-z0-9-]+)/state',['methods'=>'GET','permission_callback'=>'sr_lms_rest_permission','callback'=>static function($request){$response=new WP_REST_Response(sr_lms_student_state(get_current_user_id(),$request['course']));$response->header('Cache-Control','private, no-store');$response->header('Vary','Cookie');return $response;}]);
    register_rest_route('sr-lms/v1','/courses/(?P<course>[a-z0-9-]+)/lessons/(?P<lesson>[a-z0-9-]+)/progress',['methods'=>'POST','permission_callback'=>'sr_lms_rest_permission','callback'=>static function($request){$input=$request->get_json_params();if(!is_array($input))return new WP_Error('json','Provide a JSON object.',['status'=>400]);foreach(array_keys($input) as $key)if(!in_array($key,['ranges','position','practice'],true))return new WP_Error('field','Unsupported progress field.',['status'=>400]);$result=sr_lms_save_progress(get_current_user_id(),$request['course'],$request['lesson'],$input);if(is_wp_error($result))return $result;$response=new WP_REST_Response(['lesson'=>$result,'completion'=>sr_lms_completion(get_current_user_id(),$request['course'])]);$response->header('Cache-Control','private, no-store');return $response;}]);
}
