<?php
/** Run: wp eval-file wp-content/plugins/sr-academy-lms/tests/wp-integration.php */
if(!defined('WP_CLI')||!WP_CLI){http_response_code(404);exit;}
if(!function_exists('sr_lms_catalog'))WP_CLI::error('Activate SR Academy LMS first.');
$GLOBALS['sr_lms_test_assertions']=0;$users=[];$fixture='sr-test-'.strtolower(wp_generate_password(10,false,false));$fixtureFile='';$outside='';$link='';
function sr_test_assert($condition,string $message): void {$GLOBALS['sr_lms_test_assertions']++;if(!$condition)throw new RuntimeException('FAIL: '.$message);}
try {
    sr_lms_install();sr_test_assert(sr_lms_return_url('')===sr_lms_page_url('dashboard'),'empty account return URL defaults to dashboard');
    sr_test_assert(sr_lms_return_url('https://evil.example/')===sr_lms_page_url('dashboard'),'external return URL rejected');
    sr_test_assert(sr_lms_return_url('javascript:alert(1)')===sr_lms_page_url('dashboard'),'script return URL rejected');
    $catalog=sr_lms_catalog();sr_test_assert(isset($catalog['speakathon'],$catalog['power-dressing'],$catalog['power-bundle']),'bundled catalog imported');
    foreach(['a','b'] as $suffix){$id=wp_insert_user(['user_login'=>$fixture.'-'.$suffix,'user_email'=>$fixture.'-'.$suffix.'@example.test','user_pass'=>wp_generate_password(30,true),'role'=>'subscriber']);if(is_wp_error($id))throw new RuntimeException($id->get_error_message());$users[]=(int)$id;}
    [$a,$b]=$users;
    sr_test_assert(!sr_lms_has_access($a,'speakathon'),'student denied before grant');
    sr_test_assert(!is_wp_error(sr_lms_request_enrollment($a,'speakathon')),'request persists');
    sr_test_assert(!sr_lms_has_access($a,'speakathon'),'pending request never grants access');
    sr_test_assert(!is_wp_error(sr_lms_grant($a,'speakathon','course:speakathon')),'independent single-course grant');
    sr_test_assert(!is_wp_error(sr_lms_grant($a,'power-bundle','offer:power-bundle')),'package grant expands');
    sr_test_assert(sr_lms_has_access($a,'power-dressing'),'package constituent access');
    sr_test_assert(!sr_lms_has_access($b,'speakathon'),'other account remains denied');
    sr_test_assert(sr_lms_revoke($a,'offer:power-bundle'),'package origin revoked');
    sr_test_assert(sr_lms_has_access($a,'speakathon'),'independent grant survives package revocation');
    sr_test_assert(!sr_lms_has_access($a,'power-dressing'),'exclusive package constituent revoked');
    sr_test_assert(is_wp_error(sr_lms_grant($a,'unknown-course')),'unknown course cannot be granted');
    sr_test_assert(sr_lms_private_file('assets/lms/../wp-config.php')===null,'traversal rejected');
    $root=realpath(get_option('sr_lms_private_media_dir',''));sr_test_assert($root&&is_dir($root),'private media root configured');
    if(!is_dir($root.'/videos'))mkdir($root.'/videos',0755,true);
    $fixtureFile=$root.'/videos/'.$fixture.'.mp4';file_put_contents($fixtureFile,'SR LMS integration fixture, not course media.');
    $catalog[$fixture]=['title'=>'Integration fixture','description'=>'Temporary automated test','price'=>0,'tags'=>[],'includes'=>[],'resources'=>[],'modules'=>[['title'=>'Fixture','lessons'=>[['id'=>'fixture-lesson','title'=>'Temporary fixture','videoUrl'=>'assets/lms/videos/'.$fixture.'.mp4','duration'=>100]]]]];
    update_option('sr_lms_catalog',$catalog,false);
    sr_test_assert(sr_lms_private_file('assets/lms/videos/'.$fixture.'.mp4')===$fixtureFile,'allowlisted file resolves inside private root');
    $outside=tempnam(sys_get_temp_dir(),'sr-lms-outside-');$link=$root.'/videos/'.$fixture.'-link.mp4';symlink($outside,$link);
    sr_test_assert(sr_lms_private_file('assets/lms/videos/'.$fixture.'-link.mp4')===null,'escaping symlink rejected');
    sr_lms_grant($a,$fixture);sr_lms_grant($b,$fixture);
    foreach([[0,30],[30,60],[60,89]] as $range){$saved=sr_lms_save_progress($a,$fixture,'fixture-lesson',['ranges'=>[$range],'position'=>$range[1],'practice'=>true]);sr_test_assert(!is_wp_error($saved),'valid progress batch persisted');}
    sr_test_assert(!sr_lms_completion($a,$fixture)['eligible'],'89 percent incomplete');
    $saved=sr_lms_save_progress($a,$fixture,'fixture-lesson',['ranges'=>[[89,90]],'position'=>90,'practice'=>true]);
    sr_test_assert(!is_wp_error($saved)&&$saved['complete'],'90 percent plus practice completes');
    sr_test_assert(sr_lms_completion($a,$fixture)['eligible'],'eligible certificate after required lesson complete');
    sr_test_assert(!sr_lms_completion($b,$fixture)['eligible'],'second account has independent progress');
    wp_set_current_user($a);$nonce=wp_create_nonce('wp_rest');
    $request=new WP_REST_Request('GET','/sr-lms/v1/courses/'.$fixture.'/state');$request->set_header('X-WP-Nonce',$nonce);$response=rest_do_request($request);
    sr_test_assert($response->get_status()===200,'authenticated state route');
    sr_test_assert(str_contains($response->get_data()['course']['modules'][0]['lessons'][0]['videoUrl'],'action=sr_lms_media'),'state uses protected handler URL');
    $request=new WP_REST_Request('POST','/sr-lms/v1/courses/'.$fixture.'/lessons/fixture-lesson/progress');$request->set_header('X-WP-Nonce',$nonce);$request->set_header('Content-Type','application/json');$request->set_body(wp_json_encode(['user_id'=>$b,'ranges'=>[[0,10]]]));
    sr_test_assert(rest_do_request($request)->get_status()===400,'client user override rejected');
    $request=new WP_REST_Request('POST','/sr-lms/v1/courses/'.$fixture.'/lessons/unknown/progress');$request->set_header('X-WP-Nonce',$nonce);$request->set_header('Content-Type','application/json');$request->set_body('{}');
    sr_test_assert(rest_do_request($request)->get_status()===404,'unknown lesson route rejected');
    $request=new WP_REST_Request('GET','/sr-lms/v1/courses/'.$fixture.'/state');$request->set_header('X-WP-Nonce','invalid');sr_test_assert(rest_do_request($request)->get_status()===403,'invalid nonce rejected');
    wp_set_current_user($b);$request=new WP_REST_Request('GET','/sr-lms/v1/courses/speakathon/state');$request->set_header('X-WP-Nonce',wp_create_nonce('wp_rest'));sr_test_assert(rest_do_request($request)->get_status()===403,'non-enrolled account cannot retrieve course state');
    unlink($fixtureFile);$fixtureFile='';
    sr_test_assert(!sr_lms_completion($a,$fixture)['eligible'],'removing required media revokes certificate eligibility');
    $saved=sr_lms_save_progress($a,$fixture,'fixture-lesson',['ranges'=>[[90,95]],'position'=>95]);sr_test_assert(is_wp_error($saved)&&$saved->get_error_data()['status']===409,'missing recording rejects progress');
    sr_lms_revoke($a,'offer:'.$fixture);sr_test_assert(!sr_lms_has_access($a,$fixture),'revocation removes access');
    $result='PASS: '.$GLOBALS['sr_lms_test_assertions'].' live WordPress assertions';
} catch(Throwable $e) {$failure=$e->getMessage();}
finally {
    wp_set_current_user(0);$catalog=sr_lms_catalog();unset($catalog[$fixture]);update_option('sr_lms_catalog',$catalog,false);
    foreach([$fixtureFile,$link,$outside] as $file)if($file&&(is_file($file)||is_link($file)))unlink($file);
    global $wpdb;require_once ABSPATH.'wp-admin/includes/user.php';foreach($users as $id){foreach(['grants','requests','progress'] as $table)$wpdb->delete(sr_lms_table($table),['user_id'=>$id],['%d']);wp_delete_user($id);}
}
if(isset($failure))WP_CLI::error($failure);WP_CLI::success($result);
