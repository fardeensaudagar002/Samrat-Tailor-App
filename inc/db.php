<?php
function is_mysql(): bool
{
    global $CFG;
    return ($CFG['db_driver'] ?? 'sqlite') === 'mysql';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) { return $pdo; }
    global $CFG;
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (is_mysql()) {
        $dsn = 'mysql:host=' . $CFG['mysql_host'] . ';dbname=' . $CFG['mysql_db'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $CFG['mysql_user'], $CFG['mysql_pass'], $opts);
    } else {
        $path = $CFG['sqlite_path'];
        $dir = dirname($path);
        if (!is_dir($dir)) { mkdir($dir, 0770, true); }
        $pdo = new PDO('sqlite:' . $path, null, null, $opts);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void
{
    $my   = is_mysql();
    $pk   = $my ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $tail = $my ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    $pdo->exec("CREATE TABLE IF NOT EXISTS meta (k VARCHAR(50) PRIMARY KEY, v VARCHAR(255) NOT NULL)$tail");
    $ver = (int)($pdo->query("SELECT v FROM meta WHERE k = 'schema'")->fetchColumn() ?: 0);
    if ($ver >= 1) { return; }

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id $pk,
        role VARCHAR(10) NOT NULL,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        mobile VARCHAR(20) NOT NULL DEFAULT '',
        active INT NOT NULL DEFAULT 1,
        created_at INT NOT NULL
    )$tail");

    $pdo->exec("CREATE TABLE IF NOT EXISTS items (
        id $pk,
        name VARCHAR(50) NOT NULL,
        default_rate INT NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        active INT NOT NULL DEFAULT 1
    )$tail");

    $pdo->exec("CREATE TABLE IF NOT EXISTS rates (
        karigar_id INT NOT NULL,
        item_id INT NOT NULL,
        rate INT NOT NULL,
        PRIMARY KEY (karigar_id, item_id)
    )$tail");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id $pk,
        karigar_id INT NOT NULL,
        amount INT NOT NULL,
        pieces INT NOT NULL,
        paid_at INT NOT NULL,
        note VARCHAR(255) NOT NULL DEFAULT '',
        created_by INT NOT NULL
    )$tail");

    $jobIdx = $my ? ', INDEX idx_jobs_k (karigar_id, status), INDEX idx_jobs_no (copy_no), INDEX idx_jobs_pay (payment_id)' : '';
    $pdo->exec("CREATE TABLE IF NOT EXISTS jobs (
        id $pk,
        copy_no VARCHAR(20) NOT NULL,
        item_id INT NOT NULL,
        qty INT NOT NULL DEFAULT 1,
        karigar_id INT NOT NULL,
        rate INT NOT NULL DEFAULT 0,
        note VARCHAR(255) NOT NULL DEFAULT '',
        status VARCHAR(10) NOT NULL DEFAULT 'pending',
        assigned_at INT NOT NULL,
        done_at INT NULL,
        payment_id INT NULL,
        created_by INT NOT NULL
        $jobIdx
    )$tail");

    $laIdx = $my ? ', INDEX idx_la_at (at)' : '';
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id $pk,
        ip VARCHAR(45) NOT NULL,
        username VARCHAR(50) NOT NULL,
        at INT NOT NULL
        $laIdx
    )$tail");

    if (!$my) {
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_jobs_k ON jobs (karigar_id, status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_jobs_no ON jobs (copy_no)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_jobs_pay ON jobs (payment_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_la_at ON login_attempts (at)');
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM items')->fetchColumn() === 0) {
        // शुरुआती कपड़े और रेट (सेटिंग में बदल सकते हैं)
        $seed = [
            ['शर्ट', 150], ['कुर्ता', 180], ['पजामा', 100], ['पैंट', 200], ['कोट', 900],
            ['सफ़ारी', 450], ['जैकेट', 400], ['शेरवानी', 1500], ['ब्लेज़र', 1000],
        ];
        $st = $pdo->prepare('INSERT INTO items (name, default_rate, sort_order, active) VALUES (?, ?, ?, 1)');
        foreach ($seed as $i => [$n, $r]) { $st->execute([$n, $r, $i + 1]); }
    }

    $pdo->exec("REPLACE INTO meta (k, v) VALUES ('schema', '1')");
}
