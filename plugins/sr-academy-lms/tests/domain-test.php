<?php
// Standalone tests exercise production access/progress rules without WordPress.
$path = dirname(__DIR__) . '/includes/domain.php';
if (is_file($path)) require $path;
$tests = 0;
function check($condition, $message) { global $tests; $tests++; if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
check(function_exists('sr_lms_grant_targets'), 'access grant rules must exist');
$catalog = ['speakathon'=>[], 'power-dressing'=>[], 'dining-etiquette'=>[], 'power-bundle'=>[]];
check(sr_lms_grant_targets('power-bundle', $catalog) === ['power-bundle','speakathon','power-dressing'], 'bundle expands to constituent courses');
check(sr_lms_grant_targets('unknown', $catalog) === [], 'unknown grants fail closed');
$grants = ['course:speakathon'=>['speakathon'], 'offer:power-bundle'=>['power-bundle','speakathon','power-dressing']];
check(sr_lms_access_from_origins($grants, 'speakathon'), 'independent grant grants access');
unset($grants['offer:power-bundle']);
check(sr_lms_access_from_origins($grants, 'speakathon'), 'revoking bundle preserves independent grant');
check(!sr_lms_access_from_origins($grants, 'power-dressing'), 'revoking bundle removes its exclusive constituent');
check(!sr_lms_access_from_origins($grants, 'unknown'), 'unknown access denied');
$state = sr_lms_merge_progress([], ['ranges'=>[[0,30],[20,50]],'position'=>50,'practice'=>false], 100);
check(abs(sr_lms_coverage($state['ranges'])-50)<0.001, 'overlaps merge without double counting');
check(!sr_lms_lesson_complete($state, 100), 'partial coverage incomplete');
$state = sr_lms_merge_progress($state, ['ranges'=>[[50,80]],'position'=>80,'practice'=>true], 100);
check(!sr_lms_lesson_complete($state, 100), '80 percent plus practice remains incomplete');
$state = sr_lms_merge_progress($state, ['ranges'=>[[80,90]],'position'=>90,'practice'=>true], 100);
check(sr_lms_lesson_complete($state, 100), '90 percent and practice completes');
$withoutPractice=$state; $withoutPractice['practice']=false;
check(!sr_lms_lesson_complete($withoutPractice,100), '90 percent without practice incomplete');
foreach ([['ranges'=>[[-1,2]]], ['ranges'=>[[1,101]]], ['ranges'=>[[2,1]]], ['ranges'=>[[0,61]]], ['position'=>101], ['position'=>INF], ['practice'=>'yes'], ['ranges'=>[['0',10]]]] as $bad) {
    try { sr_lms_merge_progress([], $bad, 100); check(false, 'invalid progress rejected'); } catch (InvalidArgumentException $e) { check(true, 'invalid progress rejected'); }
}
check(!sr_lms_certificate_eligible([], [], []), 'empty course cannot earn certificate');
$lessons=[['id'=>'day-1','duration'=>100],['id'=>'day-2','duration'=>100]];
check(!sr_lms_certificate_eligible($lessons,['day-1'=>$state,'day-2'=>$state],['day-1'=>true,'day-2'=>false]), 'missing media blocks certificate');
check(!sr_lms_certificate_eligible($lessons,['day-1'=>$state],['day-1'=>true,'day-2'=>true]), 'missing lesson state blocks certificate');
check(sr_lms_certificate_eligible($lessons,['day-1'=>$state,'day-2'=>$state],['day-1'=>true,'day-2'=>true]), 'all available required lessons complete');
echo "PASS: $tests domain assertions\n";
check(function_exists('sr_lms_byte_range'), 'byte range parsing must exist');
check(sr_lms_byte_range('bytes=2-5',10)===[2,5], 'closed byte range');
check(sr_lms_byte_range('bytes=6-',10)===[6,9], 'open byte range');
check(sr_lms_byte_range('bytes=-3',10)===[7,9], 'suffix byte range');
foreach(['bytes=10-','bytes=7-3','bytes=0-1,4-5','bad','bytes=-0'] as $range)check(sr_lms_byte_range($range,10)===false,'invalid byte range rejected');
echo "PASS: $tests total assertions\n";
$withGap=sr_lms_merge_progress([],['ranges'=>[[0,10],[10.1,20]]],100);
check(abs(sr_lms_coverage($withGap['ranges'])-19.9)<0.0001,'unwatched gaps are not credited as watched coverage');
echo "PASS: $tests total assertions including gaps\n";
foreach([['practice'=>null],['position'=>null],['ranges'=>null]] as $bad){try{sr_lms_merge_progress([],$bad,100);check(false,'explicit null progress fields rejected');}catch(InvalidArgumentException $e){check(true,'explicit null progress fields rejected');}}
echo "PASS: $tests total assertions including invalid null fields\n";

check(function_exists('sr_lms_course_completion_date'), 'course completion date helper exists');
$dated=['day-1'=>['completed_at'=>'2026-10-01T18:00:00+00:00'],'day-2'=>['completed_at'=>'2026-10-03T04:00:00+00:00'],'unrequired'=>['completed_at'=>'2026-10-25T04:00:00+00:00']];
check(sr_lms_course_completion_date($lessons,$dated,'2026-10-30')==='2026-10-03','certificate uses last required lesson completion, not print date');
check(sr_lms_course_completion_date($lessons,array_reverse($dated,true),'2026-11-01')==='2026-10-03','reprinting and progress order do not change completion date');
unset($dated['day-1']['completed_at']);
check(sr_lms_course_completion_date($lessons,$dated,'2026-10-30')==='2026-10-30','legacy missing completion dates use stable fallback');
echo "PASS: $tests total assertions including certificate dates\n";
