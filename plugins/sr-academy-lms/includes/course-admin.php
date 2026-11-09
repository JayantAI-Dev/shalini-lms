<?php
if(!defined('ABSPATH'))exit;
add_action('admin_menu',static function(){add_submenu_page('sr-academy-lms','Courses and prices','Courses & prices','manage_options','sr-lms-course-prices','sr_lms_price_screen');});
function sr_lms_price_screen(): void {
 if(!current_user_can('manage_options'))return;
 echo '<div class="wrap"><h1>Courses and prices</h1><p>Set course prices in INR. Existing payment orders retain the price agreed at checkout. Enable individual offers for checkout under Razorpay settings.</p>';
 if(isset($_GET['saved']))echo '<div class="notice notice-success"><p>Course prices saved.</p></div>';
 echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="sr_lms_prices">';wp_nonce_field('sr_lms_prices');
 echo '<table class="widefat striped"><thead><tr><th>Course</th><th>Price (INR)</th><th>Content availability</th></tr></thead><tbody>';
 foreach(sr_lms_catalog() as $slug=>$course){$lessons=sr_lms_lessons($course);$available=count(array_filter($lessons,static fn($lesson)=>(bool)sr_lms_private_file($lesson['videoUrl']??'')));echo '<tr><td><strong>'.esc_html($course['title']).'</strong><br><code>'.esc_html($slug).'</code></td><td><input type="number" name="prices['.esc_attr($slug).']" step="0.01" min="0" max="10000000" required value="'.esc_attr(number_format((float)$course['price'],2,'.','')).'"></td><td>'.esc_html($available.' of '.count($lessons).' lesson recordings available').'</td></tr>';}
 echo '</tbody></table><p>Paid checkout requires a price of at least INR 1.00. Zero-priced courses are not charged through Razorpay. Use manual grants for complimentary access. Edit lesson modules and descriptions in the main LMS catalog.</p><button class="button button-primary">Save prices</button></form></div>';
}
add_action('admin_post_sr_lms_prices',static function(){
 if(!current_user_can('manage_options'))wp_die('Administrator permission required.','Forbidden',['response'=>403]);
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST')wp_die('Use the prices form.','Invalid method',['response'=>405]);check_admin_referer('sr_lms_prices');
 $prices=$_POST['prices']??null;if(!is_array($prices))wp_die('Provide course prices.','Invalid prices',['response'=>400]);$catalog=sr_lms_catalog();
 foreach($prices as $slug=>$value){if(!isset($catalog[$slug])||!is_string($value)||!preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?$/D',$value)||(float)$value>10000000)wp_die('Provide valid INR prices with at most two decimal places.','Invalid price',['response'=>400]);$catalog[$slug]['price']=(float)$value;}
 update_option('sr_lms_catalog',$catalog,false);wp_safe_redirect(admin_url('admin.php?page=sr-lms-course-prices&saved=1'));exit;
});
