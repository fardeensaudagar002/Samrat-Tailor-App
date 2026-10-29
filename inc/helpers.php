<?php
/* ---------- database shortcuts ---------- */
function q(string $sql, array $p = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st;
}
function one(string $sql, array $p = []): ?array
{
    $r = q($sql, $p)->fetch();
    return $r ?: null;
}
function all(string $sql, array $p = []): array
{
    return q($sql, $p)->fetchAll();
}
function val(string $sql, array $p = [])
{
    return q($sql, $p)->fetchColumn();
}

/* ---------- output ---------- */
function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function inr($n): string
{
    $n = (int)round((float)$n);
    $neg = $n < 0;
    $s = (string)abs($n);
    if (strlen($s) > 3) {
        $last = substr($s, -3);
        $rest = substr($s, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $s = $rest . ',' . $last;
    }
    return ($neg ? '-' : '') . '₹' . $s;
}
function hdate(?int $t, bool $withYear = false): string
{
    if (!$t) { return ''; }
    static $m = ['जन', 'फ़र', 'मार्च', 'अप्रै', 'मई', 'जून', 'जुला', 'अग', 'सित', 'अक्टू', 'नव', 'दिस'];
    $s = date('j', $t) . ' ' . $m[(int)date('n', $t) - 1];
    if ($withYear || date('Y', $t) !== date('Y')) { $s .= ' ' . date('Y', $t); }
    return $s;
}
function htime(?int $t): string
{
    return $t ? date('g:i', $t) . (date('A', $t) === 'AM' ? ' सुबह' : ' शाम') : '';
}
function days_since(int $t): int
{
    $a = strtotime(date('Y-m-d', $t));
    $b = strtotime(date('Y-m-d'));
    return (int)round(($b - $a) / 86400);
}
function today_start(): int { return strtotime(date('Y-m-d')); }
function month_start(): int { return strtotime(date('Y-m-01')); }

/* ---------- request ---------- */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}
function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'][] = [$type, $msg];
}
function take_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}
function check_post(): void
{
    if (!is_post()) { return; }
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        flash('पेज बहुत देर से खुला था. एक बार फिर से करें.', 'err');
        redirect(basename($_SERVER['SCRIPT_NAME']) . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
    }
}
function p_str(string $k, int $max = 255): string
{
    $v = trim((string)($_POST[$k] ?? ''));
    return mb_substr($v, 0, $max);
}
function p_int(string $k, int $def = 0): int
{
    $v = $_POST[$k] ?? '';
    return is_numeric($v) ? (int)$v : $def;
}
function g_str(string $k, int $max = 50): string
{
    return mb_substr(trim((string)($_GET[$k] ?? '')), 0, $max);
}
function g_int(string $k, int $def = 0): int
{
    $v = $_GET[$k] ?? '';
    return is_numeric($v) ? (int)$v : $def;
}
/** Only allow redirects back to our own pages. */
function back_to(string $default): string
{
    $b = (string)($_POST['back'] ?? '');
    return preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%\-\.]*)?$/', $b) ? $b : $default;
}

/* ---------- auth ---------- */
function current_user(): ?array
{
    static $u = false;
    if ($u !== false) { return $u; }
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $u = one('SELECT * FROM users WHERE id = ? AND active = 1', [$_SESSION['uid']]);
        if (!$u) { unset($_SESSION['uid']); }
    }
    return $u;
}
function home_for(array $u): string
{
    return $u['role'] === 'master' ? 'm_home.php' : 'k_home.php';
}
function require_role(string $role): array
{
    $u = current_user();
    if (!$u) { redirect('index.php'); }
    if ($u['role'] !== $role) { redirect(home_for($u)); }
    check_post();
    return $u;
}
function valid_username(string $u): bool
{
    return (bool)preg_match('/^[a-z0-9_.]{3,30}$/', $u);
}

/* ---------- shop data ---------- */
const JOB_SELECT = 'SELECT j.*, i.name AS item_name, u.name AS k_name, (j.rate * j.qty) AS amount
    FROM jobs j JOIN items i ON i.id = j.item_id JOIN users u ON u.id = j.karigar_id';

function karigars(bool $activeOnly = true): array
{
    return all("SELECT * FROM users WHERE role = 'karigar'" . ($activeOnly ? ' AND active = 1' : '') . ' ORDER BY active DESC, name');
}
function items(bool $activeOnly = true): array
{
    return all('SELECT * FROM items' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY sort_order, id');
}
function rate_for(int $kid, int $iid): int
{
    $r = val('SELECT rate FROM rates WHERE karigar_id = ? AND item_id = ?', [$kid, $iid]);
    if ($r !== false) { return (int)$r; }
    return (int)val('SELECT default_rate FROM items WHERE id = ?', [$iid]);
}
/**
 * Items a karigar makes, as [item_id => rate]. A karigar with no rows
 * (added before item selection existed) makes everything at the shop rate.
 */
function karigar_items(int $kid): array
{
    $out = [];
    foreach (all('SELECT item_id, rate FROM rates WHERE karigar_id = ?', [$kid]) as $r) { $out[(int)$r['item_id']] = (int)$r['rate']; }
    if ($out) { return $out; }
    foreach (items() as $i) { $out[(int)$i['id']] = (int)$i['default_rate']; }
    return $out;
}
function karigar_makes(int $kid, int $iid): bool
{
    if (!val('SELECT 1 FROM rates WHERE karigar_id = ? LIMIT 1', [$kid])) { return true; }
    return (bool)val('SELECT 1 FROM rates WHERE karigar_id = ? AND item_id = ?', [$kid, $iid]);
}
/** Read the item picker (make[id], rate[id]) from POST. Returns [item_id => rate] or an error string. */
function posted_items()
{
    $make = (array)($_POST['make'] ?? []);
    $rate = (array)($_POST['rate'] ?? []);
    $out = [];
    foreach (items(false) as $it) {
        $iid = (int)$it['id'];
        if (empty($make[$iid])) { continue; }
        $v = trim((string)($rate[$iid] ?? ''));
        if ($v === '') { $v = (string)$it['default_rate']; }
        if (!ctype_digit($v) || (int)$v > 100000) { return "{$it['name']} का रेट सही रुपये में डालें."; }
        $out[$iid] = (int)$v;
    }
    return $out ?: 'कम से कम एक कपड़ा चुनें.';
}
function save_karigar_items(int $kid, array $map): void
{
    q('DELETE FROM rates WHERE karigar_id = ?', [$kid]);
    foreach ($map as $iid => $r) { q('INSERT INTO rates (karigar_id, item_id, rate) VALUES (?, ?, ?)', [$kid, $iid, $r]); }
}

/** For the assign form: {"d":{item:rate}, "k":{karigar:{item:rate}}} */
function rates_json(): string
{
    $d = [];
    foreach (items(false) as $i) { $d[$i['id']] = (int)$i['default_rate']; }
    $k = [];
    foreach (all('SELECT * FROM rates') as $r) { $k[$r['karigar_id']][$r['item_id']] = (int)$r['rate']; }
    return json_encode(['d' => (object)$d, 'k' => (object)$k]);
}
function pending_count(): int
{
    return (int)val("SELECT COUNT(*) FROM jobs WHERE status = 'pending'");
}
