<?php
// --- Admin session security ---
// Every admin page includes this file at the very top. It:
//   1. starts the session with safe cookie settings,
//   2. checks the visitor is logged in, from the same browser, and not timed out,
//   3. otherwise destroys the session and sends them to the login page.
//
// Pages that must work WITHOUT being logged in (login.php, logout.php) define
// AUTH_PUBLIC before including this file. AJAX endpoints that answer with JSON
// define AUTH_JSON so a failed check returns a 403 JSON error instead of a redirect.

const SESSION_IDLE_TIMEOUT = 30 * 60;     // log out after 30 minutes of inactivity
const SESSION_MAX_LIFETIME = 8 * 60 * 60; // log out 8 hours after login, no matter what

function start_secure_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['SERVER_PORT'] ?? null) == 443);

    ini_set('session.use_strict_mode', '1');   // reject session IDs we didn't create
    ini_set('session.use_only_cookies', '1');  // never accept the ID from the URL
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', (string)SESSION_MAX_LIFETIME);

    // Separate cookie just for the admin area, only sent to /admin/ pages.
    session_name('NAJI_ADMIN');
    session_set_cookie_params([
        'lifetime' => 0, // deleted when the browser closes
        'path'     => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/',
        'secure'   => $https,
        'httponly' => true,      // JavaScript can't read the cookie
        'samesite' => 'Strict',  // not sent on requests coming from other sites
    ]);
    session_start();
}

// A hash of the browser's User-Agent plus the IP's network (first 3 parts of
// an IPv4 address, first 4 groups of IPv6). A stolen session cookie used from
// another browser or network won't match.
function session_fingerprint() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $subnet = implode('.', array_slice(explode('.', $ip), 0, 3));
    } elseif (($packed = @inet_pton($ip)) !== false) {
        $subnet = bin2hex(substr($packed, 0, 8));
    } else {
        $subnet = '';
    }
    return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . $subnet);
}

// Call right after the password has been verified.
function auth_login(array $admin) {
    session_regenerate_id(true); // fresh session ID on login (prevents session fixation)
    $_SESSION['admin_id']       = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['fingerprint']    = session_fingerprint();
    $_SESSION['login_time']     = time();
    $_SESSION['last_activity']  = time();
}

// Fully destroys the session: data on the server and the cookie in the browser.
function auth_logout() {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'],
        ]);
        session_destroy();
    }
}

// Returns null when the session is valid, otherwise the reason it isn't.
function auth_problem() {
    if (empty($_SESSION['admin_id'])) {
        return 'login';
    }
    if (!isset($_SESSION['fingerprint']) || !hash_equals($_SESSION['fingerprint'], session_fingerprint())) {
        return 'invalid';
    }
    $now = time();
    if ($now - ($_SESSION['last_activity'] ?? 0) > SESSION_IDLE_TIMEOUT
        || $now - ($_SESSION['login_time'] ?? 0) > SESSION_MAX_LIFETIME) {
        return 'expired';
    }
    return null;
}

start_secure_session();

if (!defined('AUTH_PUBLIC')) {
    $problem = auth_problem();
    if ($problem !== null) {
        auth_logout();
        if (defined('AUTH_JSON')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
        } else {
            header('Location: login.php' . ($problem === 'login' ? '' : '?expired=1'));
        }
        exit;
    }
    $_SESSION['last_activity'] = time();
}
