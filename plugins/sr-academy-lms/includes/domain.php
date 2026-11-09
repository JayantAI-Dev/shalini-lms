<?php
/** Pure, shared access and completion rules. */
function sr_lms_grant_targets(string $offer, array $catalog): array {
    $packages = ['power-bundle'=>['power-bundle','speakathon','power-dressing'], 'comm-package'=>['speakathon'], 'style-package'=>['power-dressing']];
    if (isset($packages[$offer])) return array_values(array_filter($packages[$offer], static function($slug) use ($catalog) { return array_key_exists($slug, $catalog); }));
    return array_key_exists($offer, $catalog) ? [$offer] : [];
}
function sr_lms_access_from_origins(array $origins, string $course): bool {
    foreach ($origins as $courses) if (is_array($courses) && in_array($course, $courses, true)) return true;
    return false;
}
function sr_lms_coverage(array $ranges): float {
    $seconds = 0.0;
    foreach ($ranges as $range) $seconds += max(0, $range[1]-$range[0]);
    return $seconds;
}
function sr_lms_merge_progress(array $state, array $input, float $duration): array {
    if (!is_finite($duration) || $duration <= 0 || $duration > 86400) throw new InvalidArgumentException('Recording duration is unavailable.');
    foreach (['position','practice','ranges'] as $field) {
        if (array_key_exists($field, $input) && $input[$field] === null) throw new InvalidArgumentException('Progress fields cannot be null.');
    }
    $position = $input['position'] ?? ($state['position'] ?? 0);
    if ((!is_int($position) && !is_float($position)) || !is_finite((float)$position) || $position < 0 || $position > $duration + 0.5) throw new InvalidArgumentException('Invalid playback position.');
    if (isset($input['practice']) && !is_bool($input['practice'])) throw new InvalidArgumentException('Practice acknowledgement must be boolean.');
    $new = $input['ranges'] ?? [];
    if (!is_array($new) || count($new)>32) throw new InvalidArgumentException('Too many watched ranges.');
    $added=0;
    foreach ($new as $range) {
        if (!is_array($range) || count($range)!==2 || !isset($range[0],$range[1]) || (!is_int($range[0])&&!is_float($range[0])) || (!is_int($range[1])&&!is_float($range[1])) || !is_finite((float)$range[0]) || !is_finite((float)$range[1]) || $range[0]<0 || $range[1]<=$range[0] || $range[1]>$duration+0.5 || $range[1]-$range[0]>30.5) throw new InvalidArgumentException('Invalid watched range (maximum 30 seconds).');
        $added += $range[1]-$range[0];
    }
    if ($added>60.5) throw new InvalidArgumentException('A progress update may report at most 60 seconds.');
    $all=array_merge($state['ranges']??[], $new);
    usort($all, static function($a,$b){return $a[0]<=>$b[0];});
    $merged=[];
    foreach($all as $range) {
        $range=[round(max(0,(float)$range[0]),3),round(min($duration,(float)$range[1]),3)];
        $last=count($merged)-1;
        if($last>=0 && $range[0]<=$merged[$last][1]) $merged[$last][1]=max($merged[$last][1],$range[1]);
        else $merged[]=$range;
    }
    if(count($merged)>4096) throw new InvalidArgumentException('Too many disconnected watched ranges.');
    return ['ranges'=>$merged,'position'=>min($duration,(float)$position),'practice'=>!empty($state['practice'])||!empty($input['practice'])];
}
function sr_lms_lesson_complete(array $state, float $duration): bool {
    return $duration>0 && !empty($state['practice']) && sr_lms_coverage($state['ranges']??[]) / $duration >= 0.9 - 0.000001;
}
function sr_lms_certificate_eligible(array $lessons, array $progress, array $available): bool {
    if (!$lessons) return false;
    foreach($lessons as $lesson) {
        $id=$lesson['id'];
        if(empty($available[$id]) || !sr_lms_lesson_complete($progress[$id]??[],(float)($lesson['duration']??0))) return false;
    }
    return true;
}
function sr_lms_byte_range(string $header,int $size) {
    if($size<1||!preg_match('/^bytes=(\d*)-(\d*)$/D',$header,$m)||($m[1]===''&&$m[2]===''))return false;
    if($m[1]===''){if((float)$m[2]<=0)return false;$start=max(0,$size-(int)$m[2]);$end=$size-1;}
    else{$start=(int)$m[1];$end=$m[2]===''?$size-1:min($size-1,(int)$m[2]);}
    return $start<0||$start>=$size||$end<$start?false:[$start,$end];
}

/** Last required lesson's first completion date; legacy records retain the supplied fallback. */
function sr_lms_course_completion_date(array $lessons, array $progress, string $fallback): string {
    if (!$lessons) return $fallback;
    $latest = 0;
    foreach ($lessons as $lesson) {
        $value = $progress[$lesson['id']]['completed_at'] ?? '';
        $timestamp = is_string($value) && $value !== '' ? strtotime($value) : false;
        if ($timestamp === false) return $fallback;
        $latest = max($latest, $timestamp);
    }
    return gmdate('Y-m-d', $latest);
}
