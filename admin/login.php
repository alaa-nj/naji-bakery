<?php
session_start();
require '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
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
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="text-start">
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
</script>
</body>
</html>