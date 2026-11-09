<?php
/** wp eval-file wp-content/plugins/sr-academy-lms/tests/payment-signatures.php */
if (!defined('WP_CLI') || !WP_CLI) exit;
$secret='fixture_secret';$body='{"event":"payment.captured"}';
if (!sr_lms_payment_signature($body,hash_hmac('sha256',$body,$secret),$secret)) WP_CLI::error('Valid signature rejected');
if (sr_lms_payment_signature($body.' ',hash_hmac('sha256',$body,$secret),$secret)) WP_CLI::error('Altered body accepted');
if (sr_lms_payment_signature($body,'','')) WP_CLI::error('Empty signature accepted');
$sealed=sr_lms_payment_encrypt($secret);
if (is_wp_error($sealed)||$sealed===$secret||sr_lms_payment_decrypt($sealed)!==$secret) WP_CLI::error('Secret encryption failed');
WP_CLI::success('Payment signature and encrypted secret assertions passed.');
