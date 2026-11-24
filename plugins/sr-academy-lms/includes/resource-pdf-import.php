<?php
if (!defined('ABSPATH')) exit;

function sr_lms_resource_pdf_import_screen(): void {
    if (!current_user_can('manage_options')) return;
    $root = realpath((string) get_option('sr_lms_private_media_dir', ''));
    $directory = $root ? $root . DIRECTORY_SEPARATOR . 'pdfs' : '';
    $ready = $root && sr_lms_private_directory_allowed($root) && is_dir($directory) && is_writable($directory);
    $items = [
        'templates' => ['Templates.pdf', 'Study Material · Templates'],
        'mastering-posture' => ['Mastering Posture & Body Language.pdf', 'Study Material · Mastering Posture & Body Language'],
        'introvert-to-influencer' => ['Introvert to Influencer.pdf', 'Freebies · Introvert to Influencer'],
        '25-gems' => ['Shalini Brochure-2.pdf', 'Freebies · 25 Gems to Influence Anyone'],
    ];
    echo '<div class="wrap"><h1>Import Speakathon Study Resources</h1>';
    if (isset($_GET['imported'])) echo '<div class="notice notice-success"><p>Study resource PDFs imported into private course storage.</p></div>';
    echo '<p>Upload the four source PDFs. Files are stored outside the public uploads directory and served through the enrolled-course media handler.</p>';
    if (!$ready) {
        echo '<div class="notice notice-error"><p>The configured private PDF directory is unavailable or not writable.</p></div></div>';
        return;
    }
    echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('sr_lms_import_resource_pdfs');
    echo '<input type="hidden" name="action" value="sr_lms_import_resource_pdfs">';
    foreach ($items as $key => [$filename, $label]) {
        echo '<p><label><strong>' . esc_html($label) . '</strong><br><input type="file" name="resource_pdfs[' . esc_attr($key) . ']" accept=".pdf,application/pdf" required></label></p>';
    }
    echo '<p>Each upload must be a valid PDF no larger than 10 MB. Existing destination files are preserved.</p>';
    submit_button('Import study PDFs');
    echo '</form></div>';
}
add_action('admin_menu', static function (): void {
    add_submenu_page('sr-academy-lms', 'Import Study Resources', 'Import Study Resources', 'manage_options', 'sr-lms-import-resources', 'sr_lms_resource_pdf_import_screen');
});

add_action('admin_post_sr_lms_import_resource_pdfs', static function (): void {
    if (!current_user_can('manage_options')) wp_die('Administrator permission is required.', '', ['response' => 403]);
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') wp_die('Use the import form.', '', ['response' => 405]);
    check_admin_referer('sr_lms_import_resource_pdfs');

    $items = [
        'templates' => 'templates.pdf',
        'mastering-posture' => 'mastering-posture.pdf',
        'introvert-to-influencer' => 'introvert-to-influencer.pdf',
        '25-gems' => '25-gems.pdf',
    ];
    $files = $_FILES['resource_pdfs'] ?? null;
    if (!is_array($files) || !isset($files['name'], $files['tmp_name'], $files['error'], $files['size'])) {
        wp_die('Choose all four study PDFs.', '', ['response' => 400]);
    }
    $directory = sr_lms_speakathon_pdf_directory();
    if ($directory === '') wp_die('The LMS private PDF directory is unavailable or not writable.', '', ['response' => 500]);
    if (!class_exists('finfo')) wp_die('PDF validation is unavailable on this server.', '', ['response' => 500]);

    $staged = [];
    foreach ($items as $key => $destination_name) {
        $source = $files['tmp_name'][$key] ?? '';
        $name = $files['name'][$key] ?? '';
        $error = $files['error'][$key] ?? UPLOAD_ERR_NO_FILE;
        $size = $files['size'][$key] ?? 0;
        if ($error !== UPLOAD_ERR_OK || !is_string($source) || !is_uploaded_file($source)
            || !is_string($name) || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf'
            || !is_numeric($size) || (int) $size < 5 || (int) $size > 10 * MB_IN_BYTES
            || (new finfo(FILEINFO_MIME_TYPE))->file($source) !== 'application/pdf') {
            foreach ($staged as $temporary) if (is_file($temporary)) unlink($temporary);
            wp_die('Choose four valid PDF files, each up to 10 MB.', '', ['response' => 400]);
        }
        $destination = $directory . DIRECTORY_SEPARATOR . $destination_name;
        if (file_exists($destination) || is_link($destination)) {
            foreach ($staged as $temporary) if (is_file($temporary)) unlink($temporary);
            wp_die('A destination PDF already exists. Existing files were left unchanged.', '', ['response' => 409]);
        }
        $temporary = tempnam($directory, '.sr-resource-pdf-');
        if ($temporary === false || dirname($temporary) !== $directory || !move_uploaded_file($source, $temporary) || !chmod($temporary, 0640)) {
            if ($temporary !== false && is_file($temporary)) unlink($temporary);
            foreach ($staged as $previous) if (is_file($previous)) unlink($previous);
            wp_die('Could not stage the study PDFs in private storage.', '', ['response' => 500]);
        }
        $staged[$destination_name] = $temporary;
    }

    $created = [];
    foreach ($staged as $destination_name => $temporary) {
        $destination = $directory . DIRECTORY_SEPARATOR . $destination_name;
        if (!link($temporary, $destination)) {
            foreach ($created as $path) if (is_file($path)) unlink($path);
            foreach ($staged as $path) if (is_file($path)) unlink($path);
            wp_die('Could not store the study PDFs. No partial import was kept.', '', ['response' => 500]);
        }
        $created[] = $destination;
    }
    foreach ($staged as $temporary) if (is_file($temporary)) unlink($temporary);
    wp_safe_redirect(add_query_arg(['page' => 'sr-lms-import-resources', 'imported' => '1'], admin_url('admin.php')));
    exit;
});
