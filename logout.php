<?php
require __DIR__ . '/inc/boot.php';

if (is_post() && hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}
redirect('index.php');
