<?php
define('AUTH_PUBLIC', true);
require '../includes/auth.php';
require '../includes/db.php';

$error = '';
$lockSeconds = 0; // > 0 while this visitor is locked out (form is disabled)

const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES    = 15;

// Failed logins are stored in the database (not the session/cookies), so
// clearing cookies or opening a private window can't reset the counter.
db_query("CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username_time (username, attempted_at),
    INDEX idx_ip_time (ip_address, attempted_at)
)");

// Returns how many seconds this username/IP is still locked out for (0 = not locked).
// Locked when the last 5 failures all happened within 15 minutes; the lock
// lasts 15 minutes from the most recent failure. Times are computed in MySQL
// so PHP and MySQL timezones can't disagree.
function lockout_seconds_left(PDO $pdo, $column, $value) {
    // Column names can't be bound as parameters, so only these two fixed names are allowed.
    if (!in_array($column, ['username', 'ip_address'], true)) {
        throw new InvalidArgumentException('Invalid column');
    }
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS c,
               TIMESTAMPDIFF(SECOND, MIN(attempted_at), MAX(attempted_at)) AS span,
               TIMESTAMPDIFF(SECOND, NOW(), MAX(attempted_at) + INTERVAL :minutes MINUTE) AS remaining
        FROM (SELECT attempted_at FROM login_attempts
              WHERE $column = :value ORDER BY attempted_at DESC LIMIT :max) recent
    ");
    $stmt->bindValue(':minutes', LOCKOUT_MINUTES, PDO::PARAM_INT);
    $stmt->bindValue(':value', $value);
    $stmt->bindValue(':max', MAX_LOGIN_ATTEMPTS, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ((int)$row['c'] >= MAX_LOGIN_ATTEMPTS
        && (int)$row['span'] <= LOCKOUT_MINUTES * 60
        && (int)$row['remaining'] > 0) {
        return (int)$row['remaining'];
    }
    return 0;
}

// Checks a password against the stored value. Also accepts old plain-text or
// MD5 passwords so they can be upgraded to password_hash() on the next login.
function check_password($password, $stored, &$needsRehash) {
    $needsRehash = false;
    if (password_get_info($stored)['algo']) {
        if (password_verify($password, $stored)) {
            $needsRehash = password_needs_rehash($stored, PASSWORD_DEFAULT);
            return true;
        }
        return false;
    }
    // Legacy formats
    if (preg_match('/^[a-f0-9]{32}$/i', $stored) && hash_equals(strtolower($stored), md5($password))) {
        $needsRehash = true;
        return true;
    }
    if ($stored !== '' && hash_equals($stored, $password)) {
        $needsRehash = true;
        return true;
    }
    return false;
}

function lockout_message($seconds) {
    $minutes = (int)ceil($seconds / 60);
    return "Too many attempts. Try again in $minutes minute" . ($minutes === 1 ? '' : 's') . '.';
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (filter_input(INPUT_GET, 'expired') === '1' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = 'Your session has expired. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Opening the page: if this IP is already locked, show the form disabled.
    if ($lockSeconds = lockout_seconds_left($pdo, 'ip_address', $ip)) {
        $error = lockout_message($lockSeconds);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Drop old rows so the table doesn't grow forever.
    db_query("DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY");

    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter username and password';
    } elseif ($lockSeconds = max(lockout_seconds_left($pdo, 'username', $username),
                                 lockout_seconds_left($pdo, 'ip_address', $ip))) {
        $error = lockout_message($lockSeconds);
    } else {
        $stmt = db_query("SELECT * FROM admins WHERE username = ?", [$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        $needsRehash = false;
        if ($admin) {
            $ok = check_password($password, $admin['password_hash'], $needsRehash);
        } else {
            // Run a dummy verify so an unknown username takes as long as a wrong password.
            password_verify($password, '$2y$10$2u.CLubKMumT4GRyua2Ar.m7voWonlikLmFjhYVMaF0y64mLNzYam');
            $ok = false;
        }

        if ($ok) {
            if ($needsRehash) {
                $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
            }
            db_query("DELETE FROM login_attempts WHERE username = ? OR ip_address = ?", [$username, $ip]);

            auth_login($admin); // new session ID + browser fingerprint + timers
            header('Location: dashboard.php');
            exit;
        } else {
            db_query("INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)", [mb_substr($username, 0, 50), $ip]);
            $error = 'Invalid username or password';

            // If this failure was the 5th, lock the form right away.
            if ($lockSeconds = max(lockout_seconds_left($pdo, 'username', $username),
                                   lockout_seconds_left($pdo, 'ip_address', $ip))) {
                $error = lockout_message($lockSeconds);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Naji Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap');

        html, body {
            height: 100%;
        }

      body {
    min-height: 100vh;
    background-color: #FDFBF6;
    display: flex;
    flex-direction: column;
}
        .login-wrapper {
            flex: 1;
        }

        .login-card {
            background-color: rgba(255, 255, 255, 0.97);
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .login-card::before {
            content: "";
            position: absolute;
            top: 18px; left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #D8A72C, #F2C94C);
            border-radius: 2px;
        }

        .brand-logo {
            max-width: 130px;
            margin-bottom: 0.75rem;
            margin-top: 0.5rem;
        }

        .brand-tagline {
            font-size: 0.75rem;
            color: #b8935a;
            font-style: italic;
            margin-bottom: 0.25rem;
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.7rem;
            color: #1A1A1A;
            margin-bottom: 0;
            letter-spacing: 0.5px;
        }

        .brand-location {
            font-size: 0.8rem;
            color: #a8a8a8;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .divider-gold {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #F2C94C, transparent);
            margin: 0 auto 1.5rem auto;
        }

        .form-control-wrap {
            position: relative;
        }

        .form-control-wrap i {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            color: #b8a56a;
        }

        .form-control-icon {
            background-color: #fff;
            border: 1.5px solid #e5ddc8;
            padding: 0.6rem 2.5rem 0.6rem 0.9rem;
            border-radius: 10px;
        }

        .form-control-icon:focus {
            border-color: #F2C94C;
            box-shadow: 0 0 0 0.2rem rgba(242, 201, 76, 0.25);
        }

        .btn-gold {
            background: linear-gradient(135deg, #B8860B, #F2C94C);
            color: #1A1A1A;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            padding: 0.65rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .btn-gold:hover {
            filter: brightness(1.08);
            color: #1A1A1A;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(216, 167, 44, 0.4);
        }

        .alert-compact {
            border-radius: 10px;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
        }

        .login-footer {
            text-align: center;
            font-size: 0.78rem;
            color: #b0a68a;
            padding: 1.25rem 0;
        }

        .toggle-password {
    cursor: pointer;
}
    </style>
</head>
<body>

<div class="container login-wrapper d-flex align-items-center justify-content-center">
    <div class="login-card text-center">
        <img src="../uploads/logo.png" alt="Naji Bakery" class="brand-logo" style="max-width: 90px; margin-bottom: 0.5rem;">
<p class="brand-name mb-3">Naji Bakery</p>

<h6 class="text-muted mb-3">Admin Login</h6>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-compact text-start">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <span id="loginError"><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="text-start" id="loginForm">
            <?= csrf_field() ?>
            <fieldset <?= $lockSeconds ? 'disabled' : '' ?>>
            <div class="mb-3">
                <label class="form-label small text-muted">Username</label>
                <div class="form-control-wrap">
                    <input type="text" name="username" class="form-control form-control-icon" placeholder="admin" required autocomplete="off">
                    <i class="bi bi-person-fill"></i>
                </div>
            </div>
          <div class="mb-4">
    <label class="form-label small text-muted">Password</label>
    <div class="form-control-wrap">
        <input type="password" name="password" id="passwordField" class="form-control form-control-icon" placeholder="••••••••" required autocomplete="off">
        <i class="bi bi-eye-fill toggle-password" id="togglePassword"></i>
    </div>
</div>
            <button type="submit" class="btn btn-gold w-100">Login</button>
            </fieldset>
        </form>
    </div>
</div>

<div class="login-footer">
    &copy; 2026 Naji Bakery Management System
</div>
<script>
    const passwordField = document.getElementById('passwordField');
    const togglePassword = document.getElementById('togglePassword');

    togglePassword.addEventListener('click', function () {
        const isHidden = passwordField.type === 'password';
        passwordField.type = isHidden ? 'text' : 'password';
        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });

    // While locked out, count down and re-enable the form when time is up.
    // (The server still enforces the lock — this is only for the user's convenience.)
    let lockSeconds = <?= (int)$lockSeconds ?>;
    if (lockSeconds > 0) {
        const fieldset = document.querySelector('#loginForm fieldset');
        const errorText = document.getElementById('loginError');
        const tick = () => {
            if (lockSeconds <= 0) {
                clearInterval(timer);
                fieldset.disabled = false;
                errorText.closest('.alert').remove();
                return;
            }
            const m = Math.floor(lockSeconds / 60), s = lockSeconds % 60;
            errorText.textContent = 'Too many attempts. Try again in ' + m + ':' + String(s).padStart(2, '0') + '.';
            lockSeconds--;
        };
        const timer = setInterval(tick, 1000);
        tick();
    }
</script>
</body>
</html>