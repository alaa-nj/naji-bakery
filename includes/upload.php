<?php
// --- Image uploads (menu item photos and the business logo) ---

const UPLOAD_MAX_BYTES = 1048576; // 1 MB
const UPLOAD_DIR = __DIR__ . '/../uploads/';

// Handles the uploaded file in $_FILES[$field].
// Returns:
//   null            = no file was chosen (nothing to do)
//   'uploads/x.jpg' = saved; the path to store in the database
// On failure it returns null and sets $error to a message for the user.
function upload_image($field, $prefix, &$error) {
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        || $file['size'] > UPLOAD_MAX_BYTES) {
        $error = 'Image is too large. The maximum size is 1 MB.';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'Failed to upload image. Please try again.';
        return null;
    }

    // 1) The file name must end in an allowed extension...
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $error = 'Image must be JPG, JPEG, PNG, or WEBP.';
        return null;
    }

    // 2) ...AND the file's real content must be one of those image types.
    // We don't trust the name or the browser-reported type: finfo reads the
    // file's actual bytes, and getimagesize() confirms it's a readable image.
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!isset($allowedTypes[$mime]) || $imageInfo === false || $imageInfo['mime'] !== $mime) {
        $error = 'Image must be JPG, JPEG, PNG, or WEBP.';
        return null;
    }

    // Random, unguessable file name; the extension comes from the real type.
    $newFileName = $prefix . bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newFileName)) {
        $error = 'Failed to upload image. Please try again.';
        return null;
    }
    return 'uploads/' . $newFileName;
}

// Deletes an image saved by upload_image(). Only files that this code created
// (item_ / business_logo_ prefixes) inside uploads/ are ever deleted — never
// the built-in logo files or anything outside the folder.
function delete_upload($relativePath) {
    if (empty($relativePath)) {
        return;
    }
    $name = basename($relativePath);
    if (!preg_match('/^(item_|business_logo_)[A-Za-z0-9.]+$/', $name)) {
        return;
    }
    $fullPath = UPLOAD_DIR . $name;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

// When a POST is bigger than PHP's post_max_size, PHP silently throws away
// the whole form ($_POST and $_FILES are empty). Detect that so we can show
// "too large" instead of a confusing "session expired" message.
function post_was_too_large() {
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && empty($_POST) && empty($_FILES)
        && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}
