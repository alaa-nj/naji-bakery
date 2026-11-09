<?php
define('AUTH_PUBLIC', true);
require '../includes/auth.php';

auth_logout(); // clears session data, deletes the cookie, destroys the session
header('Location: login.php');
exit;
