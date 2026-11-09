<?php
if (!defined('ABSPATH')) exit;
function sr_lms_admin_menu(): void {add_menu_page('SR Academy LMS','SR Academy LMS','manage_options','sr-academy-lms','sr_lms_admin_screen','dashicons-welcome-learn-more',30);}
function sr_lms_admin_form(string $operation): void {echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('sr_lms_admin');echo '<input type="hidden" name="action" value="sr_lms_admin"><input type="hidden" name="operation" value="'.esc_attr($operation).'">';}
function sr_lms_admin_screen(): void {
    if(!current_user_can('manage_options'))return;global $wpdb;$catalog=sr_lms_catalog();$offers=sr_lms_offers();
    echo '<div class="wrap"><h1>SR Academy LMS</h1><p>Manage course requests, access grants and private media. Configure Razorpay under Payments, notification templates under Emails, and prices under Courses & prices.</p>';
    if(isset($_GET['saved']))echo '<div class="notice notice-success"><p>Changes saved.</p></div>';
    echo '<h2>Enrollment requests</h2><table class="widefat striped"><thead><tr><th>Student</th><th>Requested offer</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    $requests=$wpdb->get_results('SELECT * FROM '.sr_lms_table('requests').' ORDER BY updated_at DESC LIMIT 200',ARRAY_A);
    foreach($requests as $request){$user=get_userdata($request['user_id']);echo '<tr><td>'.esc_html($user?$user->display_name.' ('.$user->user_email.')':'Deleted account').'</td><td>'.esc_html($offers[$request['offer']]['title']??$request['offer']).'</td><td>'.esc_html($request['status']).'</td><td>';if($user&&$request['status']!=='approved'){sr_lms_admin_form('approve');echo '<input type="hidden" name="request_id" value="'.esc_attr($request['id']).'"><button class="button button-primary">Approve access</button></form>';}echo '</td></tr>';}
    if(!$requests)echo '<tr><td colspan="4">No enrollment requests yet.</td></tr>';echo '</tbody></table><h2>Grant access directly</h2>';
    sr_lms_admin_form('grant');echo '<p><label>Student email <input name="student_email" type="email" required></label> <label>Course or package <select name="offer">';foreach($offers as $slug=>$offer)echo '<option value="'.esc_attr($slug).'">'.esc_html($offer['title']).'</option>';echo '</select></label> <button class="button button-primary">Grant access</button></p><p>Direct single-course grants have an independent origin. Revoking a package preserves those independent course grants.</p></form>';
    echo '<h2>Current grants</h2><table class="widefat striped"><thead><tr><th>Student</th><th>Origin</th><th>Course access</th><th>Action</th></tr></thead><tbody>';
    $rows=$wpdb->get_results('SELECT user_id,origin,GROUP_CONCAT(course ORDER BY course SEPARATOR ", ") AS courses FROM '.sr_lms_table('grants').' GROUP BY user_id,origin ORDER BY user_id DESC LIMIT 300',ARRAY_A);
    foreach($rows as $row){$user=get_userdata($row['user_id']);echo '<tr><td>'.esc_html($user?$user->display_name.' ('.$user->user_email.')':'Deleted account').'</td><td>'.esc_html($row['origin']).'</td><td>'.esc_html($row['courses']).'</td><td>';sr_lms_admin_form('revoke');echo '<input type="hidden" name="student_id" value="'.esc_attr($row['user_id']).'"><input type="hidden" name="origin" value="'.esc_attr($row['origin']).'"><button class="button">Revoke this origin</button></form></td></tr>';}
    if(!$rows)echo '<tr><td colspan="4">No enrollment grants yet.</td></tr>';echo '</tbody></table><h2>Setup and student registration</h2>';sr_lms_admin_form('settings');echo '<p><label>Private media directory <input style="width: min(850px, 100%)" name="private_path" value="'.esc_attr(get_option('sr_lms_private_media_dir','')).'" placeholder="/persistent/path/sr-academy-private-media/assets/lms"></label></p><p>This must be a readable directory outside the public WordPress root, containing videos/, pdfs/ and resources/. Symlinks outside this root and unsupported media paths are refused.</p><p><label><input type="checkbox" name="allow_registration" value="1" '.checked((bool)get_option('users_can_register'),true,false).'> Enable student registration (subscriber accounts only)</label></p><button class="button button-primary">Save settings and scan media</button></form>';
    sr_lms_admin_form('scan');echo '<p><button class="button">Rescan recording availability and trusted durations</button></p></form>';
    if(!$catalog){sr_lms_admin_form('import');echo '<p><button class="button button-primary">Import bundled catalog</button></p></form>';}
    echo '<h2>Editable course catalog</h2><p>Edit the JSON to update titles, descriptions, course prices, availability, ordered modules, lesson IDs, practice prompts and media references. Lesson IDs must remain stable to retain student progress. Media paths must use assets/lms/videos/, pdfs/ or resources/ and safe filenames. Durations are always read from the actual files; client supplied durations are ignored. Missing recordings prevent completion.</p>';
    sr_lms_admin_form('catalog');echo '<textarea name="catalog" rows="25" style="width:100%;font-family:monospace" spellcheck="false">'.esc_textarea(wp_json_encode($catalog,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'</textarea><p><button class="button button-primary">Validate and save catalog</button></p></form></div>';
}
function sr_lms_admin_handler(): void {
    if(!current_user_can('manage_options'))wp_die('Administrator permission is required.','Forbidden',['response'=>403]);
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST')wp_die('Use the administration form.','Invalid request',['response'=>405]);check_admin_referer('sr_lms_admin');global $wpdb;$operation=sr_lms_input('operation','POST');$result=true;
    if($operation==='approve'){
        $request=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.sr_lms_table('requests').' WHERE id=%d',absint(sr_lms_input('request_id','POST'))),ARRAY_A);
        if(!$request)wp_die('Enrollment request not found.','Not found',['response'=>404]);
        $result=sr_lms_grant((int)$request['user_id'],$request['offer'],'offer:'.$request['offer']);
        if(!is_wp_error($result))$wpdb->update(sr_lms_table('requests'),['status'=>'approved','updated_at'=>current_time('mysql',true)],['id'=>$request['id']]);
    }elseif($operation==='grant'){
        $user=get_user_by('email',sanitize_email(sr_lms_input('student_email','POST')));$offer=sr_lms_id(sr_lms_input('offer','POST'));
        if(!$user)wp_die('No account has this email address. Ask the student to register first.','Student not found',['response'=>400]);
        $prefix=in_array($offer,['power-bundle','comm-package','style-package'],true)?'offer:':'course:';$result=sr_lms_grant($user->ID,$offer,$prefix.$offer);
    }elseif($operation==='revoke'){
        $student=absint(sr_lms_input('student_id','POST'));$origin=sr_lms_input('origin','POST');$result=sr_lms_revoke($student,$origin);
        if(str_starts_with($origin,'offer:'))$wpdb->update(sr_lms_table('requests'),['status'=>'revoked','updated_at'=>current_time('mysql',true)],['user_id'=>$student,'offer'=>substr($origin,6)]);
    }elseif($operation==='settings'){
        $path=trim(sr_lms_input('private_path','POST'));$real=$path?realpath($path):false;$web=realpath(ABSPATH);
        if($path&&(!$real||!is_dir($real)||!is_readable($real)||$real===$web||str_starts_with($real,rtrim($web,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)))wp_die('Use an existing readable media directory outside the public WordPress root.','Invalid private directory',['response'=>400]);
        update_option('sr_lms_private_media_dir',$real?:'',false);update_option('users_can_register',!empty($_POST['allow_registration'])?1:0);sr_lms_refresh_media_metadata();
    }elseif($operation==='catalog'){
        $raw=sr_lms_input('catalog','POST');$decoded=json_decode($raw,true);if(json_last_error()!==JSON_ERROR_NONE)wp_die('Invalid JSON: '.esc_html(json_last_error_msg()),'Catalog validation',['response'=>400]);
        $result=sr_lms_validate_catalog($decoded);if(!is_wp_error($result)){update_option('sr_lms_catalog',$result,false);sr_lms_refresh_media_metadata();}
    }elseif($operation==='import')$result=sr_lms_import_catalog(false);
    elseif($operation==='scan')$result=sr_lms_refresh_media_metadata();
    else wp_die('Unknown administration action.','Invalid request',['response'=>400]);
    if(is_wp_error($result))wp_die(esc_html($result->get_error_message()),'Unable to save',['response'=>400]);if($result===false)wp_die('The change could not be saved.','Unable to save',['response'=>500]);wp_safe_redirect(admin_url('admin.php?page=sr-academy-lms&saved=1'));exit;
}
