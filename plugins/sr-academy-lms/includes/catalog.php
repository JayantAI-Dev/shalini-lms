<?php
if (!defined('ABSPATH')) exit;
function sr_lms_catalog(): array { $data=get_option('sr_lms_catalog',[]); return is_array($data)?$data:[]; }
function sr_lms_id($value): string {return is_string($value)&&preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$value)?$value:'';}
function sr_lms_validate_media_reference($value): bool {return is_string($value)&&preg_match('~^assets/lms/(?:videos|pdfs|resources)/[a-zA-Z0-9_-]+\.(?:mp4|webm|pdf|jpg|jpeg|png|webp)$~D',$value)===1;}
function sr_lms_validate_catalog($data) {
    if(!is_array($data)||!$data||count($data)>100)return new WP_Error('catalog','Provide between 1 and 100 courses.');
    $result=[];
    foreach($data as $slug=>$course){
        if(!sr_lms_id($slug)||!is_array($course)||empty($course['title'])||!is_string($course['title'])||!isset($course['modules'])||!is_array($course['modules'])||count($course['modules'])>100)return new WP_Error('catalog','Each course needs a valid slug, title and modules array.');
        if(isset($course['price'])&&(!is_numeric($course['price'])||!is_finite((float)$course['price'])||(float)$course['price']<0))return new WP_Error('catalog','Course prices must be finite, nonnegative numbers.');
        $out=['title'=>sanitize_text_field($course['title']),'description'=>sanitize_textarea_field($course['description']??''),'price'=>max(0,(float)($course['price']??0)),'availability'=>in_array($course['availability']??'active',['active','unavailable'],true)?($course['availability']??'active'):'unavailable','tags'=>[],'includes'=>[],'modules'=>[],'resources'=>[]];
        foreach(['tags','includes'] as $key)foreach((array)($course[$key]??[]) as $item)if(is_string($item))$out[$key][]=sanitize_text_field($item);
        $ids=[];
        foreach($course['modules'] as $module){
            if(!is_array($module)||!is_string($module['title']??null)||!is_array($module['lessons']??null)||count($module['lessons'])>500)return new WP_Error('catalog','Each module requires a title and lessons array.');
            $m=['title'=>sanitize_text_field($module['title']),'lessons'=>[]];
            foreach($module['lessons'] as $lesson){
                if(!is_array($lesson)||!sr_lms_id($lesson['id']??'')||isset($ids[$lesson['id']])||!is_string($lesson['title']??null))return new WP_Error('catalog','Every lesson requires a unique valid ID and title.');
                $ids[$lesson['id']]=true;$l=['id'=>$lesson['id'],'title'=>sanitize_text_field($lesson['title']),'summary'=>sanitize_textarea_field($lesson['summary']??''),'practice'=>sanitize_textarea_field($lesson['practice']??''),'source'=>sanitize_text_field($lesson['source']??'')];
                foreach(['videoUrl','pdfUrl','solutionUrl'] as $key){if(!empty($lesson[$key])){if(!sr_lms_validate_media_reference($lesson[$key]))return new WP_Error('catalog','Invalid private media reference for '.$slug.'/'.$lesson['id'].'. Use assets/lms/videos, pdfs or resources and a safe filename.');$l[$key]=$lesson[$key];}}
                // Duration is derived from local media metadata; JSON cannot supply a trusted value.
                $m['lessons'][]=$l;
            }$out['modules'][]=$m;
        }
        foreach((array)($course['resources']??[]) as $resource){
            if(!is_array($resource)||!is_string($resource['title']??null)||!sr_lms_validate_media_reference($resource['url']??'')||!in_array($resource['type']??'',['video','pdf','image'],true))return new WP_Error('catalog','Invalid course resource.');
            $out['resources'][]=['title'=>sanitize_text_field($resource['title']),'type'=>$resource['type'],'url'=>$resource['url'],'note'=>sanitize_text_field($resource['note']??'')];
        }$result[$slug]=$out;
    }return $result;
}
function sr_lms_import_catalog(bool $overwrite=false) {
    if(!$overwrite&&sr_lms_catalog())return sr_lms_catalog();
    $data=json_decode(file_get_contents(SR_LMS_DIR.'data/courses.json'),true);
    $clean=sr_lms_validate_catalog($data);if(is_wp_error($clean))return $clean;
    update_option('sr_lms_catalog',$clean,false);sr_lms_refresh_media_metadata();return sr_lms_catalog();
}
function sr_lms_lessons(array $course): array { $all=[];foreach($course['modules']??[] as $mi=>$module)foreach($module['lessons']??[] as $lesson){$lesson['module']=$module['title'];$lesson['moduleIndex']=$mi;$all[]=$lesson;}return $all; }
function sr_lms_lesson(string $course,string $lesson): ?array {foreach(sr_lms_lessons(sr_lms_catalog()[$course]??[]) as $item)if($item['id']===$lesson)return $item;return null;}
function sr_lms_refresh_media_metadata(): array {
    if(!function_exists('wp_read_video_metadata'))require_once ABSPATH.'wp-admin/includes/media.php';
    $catalog=sr_lms_catalog();$found=0;$missing=0;
    foreach($catalog as &$course)foreach($course['modules'] as &$module)foreach($module['lessons'] as &$lesson){
        $lesson['duration']=0;$path=sr_lms_private_file($lesson['videoUrl']??'');
        if($path){$meta=wp_read_video_metadata($path);$length=is_array($meta)?(float)($meta['length']??0):0;if($length>0&&$length<=86400){$lesson['duration']=$length;$found++;}else $missing++;}else $missing++;
    }unset($course,$module,$lesson);update_option('sr_lms_catalog',$catalog,false);return ['recordings'=>$found,'unavailable'=>$missing];
}
/** Public catalog deliberately omits private paths. */
function sr_lms_courses(): array {
    $catalog=sr_lms_catalog();
    foreach($catalog as &$course){foreach($course['modules'] as &$module)foreach($module['lessons'] as &$lesson){$lesson['videoAvailable']=(bool)sr_lms_private_file($lesson['videoUrl']??'');$lesson['pdfAvailable']=(bool)sr_lms_private_file($lesson['pdfUrl']??'');unset($lesson['videoUrl'],$lesson['pdfUrl'],$lesson['solutionUrl']);}unset($module,$lesson);foreach($course['resources'] as &$resource){$resource['available']=(bool)sr_lms_private_file($resource['url']??'');unset($resource['url']);}unset($resource);}unset($course);return $catalog;
}
function sr_lms_offers(): array {
    $offers=[];foreach(sr_lms_catalog() as $slug=>$course)$offers[$slug]=['title'=>$course['title'],'price'=>$course['price'],'courses'=>sr_lms_grant_targets($slug,sr_lms_catalog())];
    $offers['comm-package']=['title'=>'Speak with Confidence · Communication Package','price'=>39999,'courses'=>sr_lms_grant_targets('comm-package',sr_lms_catalog())];
    $offers['style-package']=['title'=>'Style Transformation · Private Package','price'=>69999,'courses'=>sr_lms_grant_targets('style-package',sr_lms_catalog())];
    $catalog=sr_lms_catalog();foreach($offers as $slug=>$offer){if(!$offer['courses']){unset($offers[$slug]);continue;}foreach($offer['courses'] as $target)if(($catalog[$target]['availability']??'active')==='unavailable'){unset($offers[$slug]);break;}}return $offers;
}
function sr_lms_page_url($slug,$args=[]): string {
    $slug=sanitize_title($slug);$page=get_page_by_path($slug);$url=$page?get_permalink($page):home_url('/'.$slug.'/');return $args?add_query_arg($args,$url):$url;
}
