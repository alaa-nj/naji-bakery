<?php
// Database credentials live in config.php (not in git). See config.example.php.
$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    error_log('Missing includes/config.php: copy config.example.php and fill it in.');
    die('Site is not configured yet.');
}
$config = require $configFile;
$host = $config['db_host'];
$dbname = $config['db_name'];
$username = $config['db_user'];
$password = $config['db_pass'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Real server-side prepared statements: values are always sent to MySQL
    // separately from the SQL text, so they can never change the query.
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed.'); // don't show server details to visitors
}

// Runs a query as a prepared statement. ALL queries in the project go through
// prepare() — user input is only ever passed in $params, never put in $sql.
function db_query($sql, array $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Escapes a value for safe output inside HTML (text or attribute values).
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Reads a positive integer ID from GET/POST. Returns null if missing or invalid
// (e.g. "abc", "-3", "5 OR 1=1").
function input_id($type, $name) {
    $id = filter_input($type, $name, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return is_int($id) ? $id : null;
}

// True only for http:// or https:// URLs (blocks javascript: and similar links).
function is_web_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false
        && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true);
}
function containsArabic($text) {
    return preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1;
}

// Returns a cache-busting version number for an asset (CSS/JS file) based on
// when it was last changed on disk. This means browsers always fetch the
// latest version after we edit the file — no more manually typing ?v=9,
// ?v=12, etc. on every page and forgetting to update one of them.
function asset_version($absolutePath) {
    $mtime = @filemtime($absolutePath);
    return $mtime ? $mtime : time();
}

// --- CSRF protection ---
// A CSRF token is a secret random code we put in every admin form. When the
// form is submitted, we check the code matches what we saved for this
// session. That proves the request really came from our own admin pages,
// not from some other website tricking a logged-in admin into clicking something.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Prints a hidden field to drop inside a <form>.
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

// Checks that the token sent with a request matches the one saved in the session.
function csrf_valid() {
    return isset($_POST['csrf_token'])
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}