<?php
if (!defined('ABSPATH')) exit;
function sr_lms_private_file(string $reference): ?string {
    if(!sr_lms_validate_media_reference($reference))return null;
    $configured=get_option('sr_lms_private_media_dir','');if(!is_string($configured)||$configured==='')return null;
    $root=realpath($configured);$web=realpath(ABSPATH);if(!$root||!$web)return null;
    $root=rtrim($root,DIRECTORY_SEPARATOR);$web=rtrim($web,DIRECTORY_SEPARATOR);
    if($root===$web||str_starts_with($root,$web.DIRECTORY_SEPARATOR))return null;
    $relative=substr($reference,strlen('assets/lms/'));$path=realpath($root.DIRECTORY_SEPARATOR.$relative);
    if(!$path||!str_starts_with($path,$root.DIRECTORY_SEPARATOR)||!is_file($path)||!is_readable($path))return null;
    return $path;
}
function sr_lms_media_url(string $course,string $lesson,string $field='videoUrl'): string {
    return add_query_arg(['action'=>'sr_lms_media','course'=>$course,'lesson'=>$lesson,'field'=>$field],admin_url('admin-post.php'));
}
function sr_lms_resource_url(string $course,int $index): string {return add_query_arg(['action'=>'sr_lms_media','course'=>$course,'resource'=>$index],admin_url('admin-post.php'));}
function sr_lms_private_headers(): void {nocache_headers();header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');header('Vary: Cookie');header('X-Content-Type-Options: nosniff');header('X-Robots-Tag: noindex, nofollow');}
function sr_lms_media_handler(): void {
    sr_lms_private_headers();
    if(!in_array($_SERVER['REQUEST_METHOD']??'GET',['GET','HEAD'],true)){status_header(405);exit;}
    $course=sr_lms_id(wp_unslash($_GET['course']??''));
    if(!is_user_logged_in()||!sr_lms_has_access(get_current_user_id(),$course)){status_header(403);exit('Enrollment is required.');}
    $catalog=sr_lms_catalog();$reference='';
    if(isset($_GET['resource'])){
        $value=wp_unslash($_GET['resource']);if(!is_string($value)||!ctype_digit($value)){status_header(404);exit;}
        $resource=$catalog[$course]['resources'][(int)$value]??null;$reference=$resource['url']??'';
    }else{
        $lesson=sr_lms_lesson($course,sr_lms_id(wp_unslash($_GET['lesson']??'')));$field=wp_unslash($_GET['field']??'videoUrl');
        if(!is_string($field)||!in_array($field,['videoUrl','pdfUrl','solutionUrl'],true)){status_header(404);exit;}$reference=$lesson[$field]??'';
    }
    $path=sr_lms_private_file($reference);if(!$path){status_header(404);exit('This course resource is unavailable.');}
    $mimes=['mp4'=>'video/mp4','webm'=>'video/webm','pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];$ext=strtolower(pathinfo($path,PATHINFO_EXTENSION));
    if(!isset($mimes[$ext])){status_header(404);exit;}
    $size=filesize($path);$start=0;$end=$size-1;
    if(isset($_SERVER['HTTP_RANGE'])){
        $range=sr_lms_byte_range($_SERVER['HTTP_RANGE'],$size);
        if($range===false){status_header(416);header('Content-Range: bytes */'.$size);exit;}
        [$start,$end]=$range;status_header(206);header("Content-Range: bytes $start-$end/$size");
    }else status_header(200);
    header('Content-Type: '.$mimes[$ext]);header('Accept-Ranges: bytes');header('Content-Length: '.($end-$start+1));header('Content-Disposition: inline; filename="'.basename($path).'"');
    if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;
    while(ob_get_level())ob_end_clean();$handle=fopen($path,'rb');if(!$handle)exit;
    fseek($handle,$start);$remaining=$end-$start+1;
    while($remaining>0&&!feof($handle)&&!connection_aborted()){$chunk=fread($handle,min(1024*1024,$remaining));if($chunk===false||$chunk==='')break;echo $chunk;$remaining-=strlen($chunk);flush();}
    fclose($handle);exit;
}
function sr_lms_student_state(int $user_id,string $course): array {
    if(!sr_lms_has_access($user_id,$course))return [];
    $data=sr_lms_catalog()[$course];
    foreach($data['modules'] as &$module)foreach($module['lessons'] as &$lesson){
        foreach(['videoUrl','pdfUrl','solutionUrl'] as $field){$exists=(bool)sr_lms_private_file($lesson[$field]??'');$lesson[$field]=$exists?sr_lms_media_url($course,$lesson['id'],$field):'';if($field==='videoUrl')$lesson['videoAvailable']=$exists&&!empty($lesson['duration']);}
    }unset($module,$lesson);
    foreach($data['resources'] as $index=>&$resource){$resource['available']=(bool)sr_lms_private_file($resource['url']??'');$resource['url']=$resource['available']?sr_lms_resource_url($course,$index):'';}unset($resource);
    $progress=sr_lms_progress($user_id,$course);foreach(sr_lms_lessons($data) as $lesson)if(isset($progress[$lesson['id']]))$progress[$lesson['id']]['complete']=!empty($lesson['videoAvailable'])&&sr_lms_lesson_complete($progress[$lesson['id']],(float)($lesson['duration']??0));
    return ['slug'=>$course,'course'=>$data,'progress'=>$progress,'completion'=>sr_lms_completion($user_id,$course),'certificateUrl'=>add_query_arg(['action'=>'sr_lms_certificate','course'=>$course],admin_url('admin-post.php'))];
}
function sr_lms_certificate_handler(): void {
    sr_lms_private_headers();$course=sr_lms_id(wp_unslash($_GET['course']??''));$user=get_current_user_id();
    if(!$user||!sr_lms_has_access($user,$course)){status_header(403);exit('Enrollment is required.');}
    $completion=sr_lms_completion($user,$course);if(!$completion['eligible']){status_header(403);exit('Complete every required recording (90% watched coverage) and practice acknowledgement before receiving a certificate. Missing recordings block certificates.');}
    $key='sr_lms_certificate_date_'.$course;$date=get_user_meta($user,$key,true);if(!$date){$date=sr_lms_course_completion_date(sr_lms_lessons(sr_lms_catalog()[$course]),sr_lms_progress($user,$course),gmdate('Y-m-d'));add_user_meta($user,$key,$date,true);$date=get_user_meta($user,$key,true);}
    $student=get_userdata($user);$title=sr_lms_catalog()[$course]['title'];header('Content-Type: text/html; charset=utf-8');
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><title>Certificate of Completion</title><style>body{font:20px Georgia,serif;text-align:center;color:#243026;margin:4rem}.certificate{border:8px double #778a4a;padding:5rem 3rem;max-width:900px;margin:auto}h1{font-size:3rem}h2{font-size:2.5rem}button{margin-top:2rem;padding:1rem 2rem}@media print{button{display:none}body{margin:1rem}.certificate{padding:3rem}}</style></head><body><main class="certificate"><p>SHALINI RATHOD · CONFIDENCE ACADEMY</p><h1>Certificate of Completion</h1><p>This certifies that</p><h2><?php echo esc_html($student->display_name); ?></h2><p>has completed the required lessons and practice for</p><h2><?php echo esc_html($title); ?></h2><p>Completed <?php echo esc_html(wp_date(get_option('date_format'),strtotime($date.' 12:00 UTC'))); ?></p><p>Certificate reference: SR-<?php echo esc_html($user.'-'.strtoupper(substr(hash('sha256',$user.'|'.$course.'|'.$date),0,10))); ?></p></main><button onclick="window.print()">Print / Save PDF</button></body></html><?php exit;
}
