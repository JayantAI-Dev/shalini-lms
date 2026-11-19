<?php
if (!defined('ABSPATH')) exit;

function sr_lms_speakathon_pdf_directory(): string {
    $root = realpath((string) get_option('sr_lms_private_media_dir', ''));
    if (!$root || !sr_lms_private_directory_allowed($root)) return '';

    $directory = $root . DIRECTORY_SEPARATOR . 'pdfs';
    if (!is_dir($directory) && !wp_mkdir_p($directory)) return '';
    return realpath($directory) === $directory && is_writable($directory) ? $directory : '';
}

function sr_lms_speakathon_pdf_restore_screen(): void {
    if (!current_user_can('manage_options')) return;
    $restored = 0;
    for ($day = 1; $day <= 21; $day++) {
        if (sr_lms_private_file(sprintf('assets/lms/pdfs/day-%02d.pdf', $day))) $restored++;
    }
    ?>
    <div class="wrap">
        <h1>Restore Speakathon PDFs</h1>
        <p><?php echo esc_html("$restored of 21 worksheets available"); ?></p>
        <p>Upload one Speakathon worksheet at a time. Existing files are preserved.</p>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sr_lms_restore_speakathon_pdf">
            <?php wp_nonce_field('sr_lms_restore_speakathon_pdf'); ?>
            <label>Worksheet PDF <input type="file" name="worksheet" accept=".pdf,application/pdf" required></label>
            <?php submit_button('Restore worksheet'); ?>
        </form>
    </div>
    <?php
}

add_action('admin_menu', static function (): void {
    add_submenu_page('sr-academy-lms', 'Restore Speakathon PDFs', 'Restore Speakathon PDFs', 'manage_options', 'sr-lms-restore-pdfs', 'sr_lms_speakathon_pdf_restore_screen');
});

add_action('admin_post_sr_lms_restore_speakathon_pdf', static function (): void {
    if (!current_user_can('manage_options')) wp_die('Administrator permission is required.', '', ['response'=>403]);
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') wp_die('Use the restore form.', '', ['response'=>405]);
    check_admin_referer('sr_lms_restore_speakathon_pdf');

    $file = $_FILES['worksheet'] ?? [];
    $name = $file['name'] ?? '';
    $source = $file['tmp_name'] ?? '';
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || !is_string($name) || !preg_match('/^day-(?:0[1-9]|1[0-9]|2[01])\.pdf$/D', $name)
        || !is_string($source) || !is_uploaded_file($source)
        || filesize($source) > 10 * MB_IN_BYTES
        || !class_exists('finfo')
        || (new finfo(FILEINFO_MIME_TYPE))->file($source) !== 'application/pdf') {
        wp_die('Choose a valid Speakathon worksheet PDF up to 10 MB.', '', ['response'=>400]);
    }

    $directory = sr_lms_speakathon_pdf_directory();
    if ($directory === '') wp_die('The LMS private PDF directory is unavailable or not writable.', '', ['response'=>500]);
    $destination = $directory . DIRECTORY_SEPARATOR . $name;
    if (file_exists($destination) || is_link($destination)) wp_die('This worksheet already exists.', '', ['response'=>409]);

    $temporary = tempnam($directory, '.sr-pdf-');
    if ($temporary === false || dirname($temporary) !== $directory) {
        if ($temporary !== false) unlink($temporary);
        wp_die('Could not prepare private storage.', '', ['response'=>500]);
    }
    $stored = false;
    try {
        $stored = move_uploaded_file($source, $temporary) && chmod($temporary, 0640) && link($temporary, $destination);
    } finally {
        unlink($temporary);
    }
    if (!$stored) wp_die('Could not store the worksheet in private storage.', '', ['response'=>500]);

    wp_safe_redirect(add_query_arg('page', 'sr-lms-restore-pdfs', admin_url('admin.php')));
    exit;
});
