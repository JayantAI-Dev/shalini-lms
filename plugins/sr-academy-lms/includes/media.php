<?php
if (!defined('ABSPATH')) exit;
function sr_lms_private_directory_allowed(string $path): bool {
    $root=realpath($path);$web=realpath(ABSPATH);
    if(!$root||!$web)return false;
    $root=rtrim($root,DIRECTORY_SEPARATOR);$web=rtrim($web,DIRECTORY_SEPARATOR);
    if($root!==$web&&!str_starts_with($root,$web.DIRECTORY_SEPARATOR))return true;
    if(!defined('WP_CONTENT_DIR'))return false;
    $content=realpath(WP_CONTENT_DIR);
    $expected=$content?realpath($content.DIRECTORY_SEPARATOR.'sr-academy-private-media'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'lms'):false;
    if(!$expected||$root!==rtrim($expected,DIRECTORY_SEPARATOR))return false;
    $guard=dirname(dirname($root)).DIRECTORY_SEPARATOR.'.htaccess';
    if(!is_file($guard)||!is_readable($guard))return false;
    $rules=file_get_contents($guard);
    return is_string($rules)
        &&preg_match('/^\\s*Require\\s+all\\s+denied\\s*$/mi',$rules)===1
        &&preg_match('/^\\s*Deny\\s+from\\s+all\\s*$/mi',$rules)===1;
}
function sr_lms_private_file(string $reference): ?string {
    if(!sr_lms_validate_media_reference($reference))return null;
    $configured=get_option('sr_lms_private_media_dir','');if(!is_string($configured)||$configured==='')return null;
    $root=realpath($configured);$web=realpath(ABSPATH);if(!$root||!$web)return null;
    $root=rtrim($root,DIRECTORY_SEPARATOR);$web=rtrim($web,DIRECTORY_SEPARATOR);
    if(!sr_lms_private_directory_allowed($root))return null;
    $relative=substr($reference,strlen('assets/lms/'));$path=realpath($root.DIRECTORY_SEPARATOR.$relative);
    if(!$path||!str_starts_with($path,$root.DIRECTORY_SEPARATOR)||!is_file($path)||!is_readable($path))return null;
    return $path;
}
function sr_lms_media_url(string $course,string $lesson,string $field='videoUrl',?int $version=null): string {
    $args=['action'=>'sr_lms_media','course'=>$course,'lesson'=>$lesson,'field'=>$field];
    if($version!==null)$args['version']=$version;
    return add_query_arg($args,admin_url('admin-post.php'));
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
        if(!$lesson){status_header(404);exit;}
        if($field==='videoVersion'){
            $version=wp_unslash($_GET['version']??'');
            if(!is_string($version)||!ctype_digit($version)){status_header(404);exit;}
            $reference=$lesson['videos'][(int)$version]['url']??'';
        }else{
            if(!is_string($field)||!in_array($field,['videoUrl','pdfUrl','solutionUrl'],true)){status_header(404);exit;}
            $reference=$lesson[$field]??'';
        }
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
        if(!empty($lesson['videos'])&&is_array($lesson['videos'])){foreach($lesson['videos'] as $index=>&$video){$available=(bool)sr_lms_private_file($video['url']??'');$video['available']=$available;$video['url']=$available?sr_lms_media_url($course,$lesson['id'],'videoVersion',(int)$index):'';if($available&&!empty($lesson['duration']))$lesson['videoAvailable']=true;}unset($video);}
    }unset($module,$lesson);
    foreach($data['resources'] as $index=>&$resource){$resource['available']=(bool)sr_lms_private_file($resource['url']??'');$resource['url']=$resource['available']?sr_lms_resource_url($course,$index):'';}unset($resource);
    $progress=sr_lms_progress($user_id,$course);foreach(sr_lms_lessons($data) as $lesson)if(isset($progress[$lesson['id']]))$progress[$lesson['id']]['complete']=!empty($lesson['videoAvailable'])&&sr_lms_lesson_complete($progress[$lesson['id']],(float)($lesson['duration']??0));
    return ['slug'=>$course,'course'=>$data,'progress'=>$progress,'completion'=>sr_lms_completion($user_id,$course),'certificateUrl'=>add_query_arg(['action'=>'sr_lms_certificate','course'=>$course],admin_url('admin-post.php'))];
}
function sr_lms_certificate_handler(): void {
    sr_lms_private_headers();
    $course=sr_lms_id(wp_unslash($_GET['course']??''));
    $user=get_current_user_id();
    if(!$user||!sr_lms_has_access($user,$course)){status_header(403);exit('Enrollment is required.');}
    $completion=sr_lms_completion($user,$course);
    if(!$completion['eligible']){status_header(403);exit('Complete every required recording (90% watched coverage) and practice acknowledgement before receiving a certificate. Missing recordings block certificates.');}
    $key='sr_lms_certificate_date_'.$course;
    $date=get_user_meta($user,$key,true);
    if(!$date){
        $date=sr_lms_course_completion_date(sr_lms_lessons(sr_lms_catalog()[$course]),sr_lms_progress($user,$course),gmdate('Y-m-d'));
        add_user_meta($user,$key,$date,true);
        $date=get_user_meta($user,$key,true);
    }
    $student=get_userdata($user);
    $student_name=trim((string)$student->first_name.' '.(string)$student->last_name);
    if($student_name==='')$student_name=(string)$student->display_name;
    $title=sr_lms_catalog()[$course]['title'];
    $formatted_date=wp_date(get_option('date_format'),strtotime($date.' 12:00 UTC'));
    $reference='SR-'.$user.'-'.strtoupper(substr(hash('sha256',$user.'|'.$course.'|'.$date),0,10));
    header('Content-Type: text/html; charset=utf-8');
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html($title); ?> · Certificate of Completion</title>
<style>
:root{color-scheme:light;--forest:#174b39;--deep:#12392e;--sage:#78916f;--gold:#bd9b55;--ink:#20362d;--muted:#68786e;--paper:#fffefa}
*{box-sizing:border-box}
body{margin:0;padding:32px 20px;background:#f1f4ee;color:var(--ink);font:16px/1.5 Arial,sans-serif}
.certificate{position:relative;isolation:isolate;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:660px;max-width:1100px;margin:0 auto;padding:68px 8%;overflow:hidden;background:var(--paper);text-align:center;border:1px solid #d9e2d5;box-shadow:0 22px 70px rgba(23,75,57,.12)}
.certificate:before,.certificate:after{position:absolute;z-index:-1;content:"";inset:14px;border:1px solid #d3bd83;pointer-events:none}
.certificate:after{inset:20px;border-color:#e8e3d2}
.corner{position:absolute;width:110px;height:110px;border-color:var(--gold);opacity:.72}
.corner-tl{top:25px;left:25px;border-top:2px solid;border-left:2px solid}
.corner-tr{top:25px;right:25px;border-top:2px solid;border-right:2px solid}
.corner-bl{bottom:25px;left:25px;border-bottom:2px solid;border-left:2px solid}
.corner-br{right:25px;bottom:25px;border-right:2px solid;border-bottom:2px solid}
.brand{margin:0;color:var(--forest);font-size:13px;font-weight:700;letter-spacing:.22em;text-transform:uppercase}
.brand-mark{display:grid;place-items:center;width:42px;height:42px;margin:0 auto 13px;border:1px solid #c6ae70;border-radius:50%;color:var(--gold);font-size:21px}
.eyebrow{margin:34px 0 7px;color:var(--sage);font-size:12px;font-weight:700;letter-spacing:.24em;text-transform:uppercase}
h1{margin:0;color:var(--deep);font-family:Georgia,serif;font-size:clamp(34px,5vw,58px);font-weight:400;line-height:1.1}
.lead{margin:27px 0 0;color:var(--muted);font-family:Georgia,serif;font-size:19px}
.student-name{max-width:100%;margin:7px 0 8px;padding:0 12px;color:var(--forest);font-family:Georgia,serif;font-size:clamp(32px,5vw,54px);font-weight:700;line-height:1.18;overflow-wrap:anywhere}
.rule{width:min(340px,60%);height:1px;margin:8px auto 17px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.course-copy{margin:0;color:var(--muted);font-size:16px}
.course-title{max-width:850px;margin:5px 0 0;color:var(--deep);font-family:Georgia,serif;font-size:clamp(23px,3.5vw,36px);font-weight:700;line-height:1.2}
.completion-seal{display:flex;align-items:center;gap:10px;margin:28px 0 0;padding:10px 17px;border:1px solid #d8e2d1;border-radius:99px;background:#f4f7f1;color:var(--forest);font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
.seal-star{color:var(--gold);font-size:16px}
.footer{display:flex;justify-content:center;gap:70px;width:100%;margin-top:36px}
.footer-item{min-width:160px;color:var(--ink);font-size:14px}
.footer-label{display:block;margin-bottom:5px;color:var(--muted);font-size:10px;font-weight:700;letter-spacing:.16em;text-transform:uppercase}
.signature{font-family:Georgia,serif;font-size:20px;font-style:italic}
.reference{margin:26px 0 0;color:#879187;font-size:10px;letter-spacing:.08em}
.actions{display:flex;justify-content:center;gap:12px;margin:22px auto 0}
.actions a,.actions button{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 21px;border:1px solid var(--forest);border-radius:6px;background:var(--forest);color:white;font:700 14px Arial,sans-serif;text-decoration:none;cursor:pointer}
.actions a{background:transparent;color:var(--forest)}
@media(max-width:600px){body{padding:12px 8px}.certificate{min-height:620px;padding:58px 9%}.corner{width:55px;height:55px}.footer{gap:24px}.footer-item{min-width:0;flex:1}.actions{flex-wrap:wrap}}
@media print{
 @page{size:landscape;margin:10mm}
 body{padding:0;background:#fff}
 .certificate{width:100%;max-width:none;min-height:180mm;height:180mm;padding:35px 8%;border:1px solid #d9e2d5;box-shadow:none;break-inside:avoid}
 .actions{display:none}
 *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
</style>
</head>
<body>
<main class="certificate">
<span class="corner corner-tl" aria-hidden="true"></span><span class="corner corner-tr" aria-hidden="true"></span>
<span class="corner corner-bl" aria-hidden="true"></span><span class="corner corner-br" aria-hidden="true"></span>
<p class="brand"><span class="brand-mark" aria-hidden="true">✦</span>Shalini Rathod · Confidence Academy</p>
<p class="eyebrow">With pride and appreciation</p>
<h1>Certificate<br>of Completion</h1>
<p class="lead">This certificate is proudly presented to</p>
<h2 class="student-name"><?php echo esc_html($student_name); ?></h2>
<div class="rule" aria-hidden="true"></div>
<p class="course-copy">for successfully completing the course</p>
<h2 class="course-title"><?php echo esc_html($title); ?></h2>
<div class="completion-seal"><span class="seal-star" aria-hidden="true">✦</span>All required lessons and practice completed<span class="seal-star" aria-hidden="true">✦</span></div>
<div class="footer">
<div class="footer-item"><span class="footer-label">Awarded on</span><?php echo esc_html($formatted_date); ?></div>
<div class="footer-item"><span class="footer-label">Academy</span><span class="signature">Shalini Rathod</span></div>
</div>
<p class="reference">Certificate reference: <?php echo esc_html($reference); ?></p>
</main>
<div class="actions"><button type="button" onclick="window.print()">Print / Save as PDF</button><a href="<?php echo esc_url(sr_lms_page_url('dashboard',['dashboard_tab'=>'certificates'])); ?>">Back to certificates</a></div>
</body>
</html><?php exit;
}
