<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kolkata');
mb_internal_encoding('UTF-8');

define('BASE_DIR', dirname(__DIR__));
$CFG = require BASE_DIR . '/config.php';

// Sessions are stored inside data/ so shared hosting does not clear them early.
$sessDir = BASE_DIR . '/data/sessions';
if (!is_dir($sessDir)) { @mkdir($sessDir, 0770, true); }
if (is_dir($sessDir) && is_writable($sessDir)) { session_save_path($sessDir); }

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 30));
session_name('samrat_sid');
session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 30,   // 30 दिन तक लॉगिन रहेगा
    'path'     => '/',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/layout.php';

if (($CFG['reset_master_password'] ?? '') !== '' && mb_strlen($CFG['reset_master_password']) >= 6) {
    q("UPDATE users SET password_hash = ?, active = 1 WHERE role = 'master'", [password_hash($CFG['reset_master_password'], PASSWORD_DEFAULT)]);
}
