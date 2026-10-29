<?php
/** Small inline line icons (24×24, stroke = currentColor). */
function icon(string $name, string $cls = 'i'): string
{
    static $p = [
        'scissors' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4 8.12 15.88M14.47 14.48 20 20M8.12 8.12 12 12"/>',
        'list'     => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'wallet'   => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        'users'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
        'checkc'   => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'lock'     => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'clock'    => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'alert'    => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4M12 17h.01"/>',
        'rupee'    => '<path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3a5 5 0 0 0 0-10"/>',
        'box'      => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'plus'     => '<path d="M5 12h14M12 5v14"/>',
        'search'   => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'back'     => '<path d="m15 18-6-6 6-6"/>',
        'chev'     => '<path d="m9 18 6-6-6-6"/>',
        'user'     => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'eye'      => '<path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0"/><circle cx="12" cy="12" r="3"/>',
        'x'        => '<path d="M18 6 6 18M6 6l12 12"/>',
        'trend'    => '<path d="M22 7 13.5 15.5 8.5 10.5 2 17"/><path d="M16 7h6v6"/>',
    ];
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         . ($p[$name] ?? '') . '</svg>';
}

/** Avatar with a stable colour per name. */
function avatar(string $name, string $cls = 'av'): string
{
    $hue = [8, 28, 160, 200, 222, 262, 290, 330][abs(crc32($name)) % 8];
    return '<span class="' . $cls . '" style="--h:' . $hue . '">' . h(mb_substr($name, 0, 1)) . '</span>';
}

function greeting(): string
{
    $hr = (int)date('G');
    return $hr < 12 ? 'सुप्रभात' : ($hr < 17 ? 'नमस्ते' : 'शुभ संध्या');
}

function page_head(string $title, string $active = ''): void
{
    global $CFG;
    $u = current_user();
    ?><!doctype html>
<html lang="hi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1E2A6E">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> · <?= h($CFG['shop_name']) ?></title>
<link rel="icon" href="assets/icon.svg?v=2" type="image/svg+xml">
<link rel="manifest" href="manifest.webmanifest">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Mukta:wght@400;500;600;700;800&family=Rozha+One&display=swap">
<link rel="stylesheet" href="assets/style.css?v=5">
</head>
<body class="<?= $u ? 'app' : 'guest' ?>">
<?php if ($u): ?>
<header class="topbar"><div class="in">
  <a class="brandlink" href="<?= h(home_for($u)) ?>">
    <img class="mark" src="assets/icon.svg?v=2" alt="" width="38" height="38">
    <span class="bt"><span class="logo"><?= h($CFG['shop_name']) ?></span>
    <span class="who-tag"><?= $u['role'] === 'master' ? 'मास्टर' : 'कारीगर' ?> · <?= h($u['name']) ?></span></span>
  </a>
  <?= avatar($u['name'], 'av me') ?>
  <form method="post" action="logout.php"><?= csrf_field() ?><button class="iconbtn" type="submit" aria-label="लॉग आउट" title="लॉग आउट"><?= icon('logout') ?></button></form>
</div></header>
<?php endif; ?>
<div class="toasts" aria-live="polite">
<?php foreach (take_flash() as [$t, $m]): ?>
  <div class="flash <?= h($t) ?>" role="status"><?= icon($t === 'err' ? 'alert' : 'checkc') ?><span><?= h($m) ?></span><button type="button" class="x" aria-label="बंद करें"><?= icon('x') ?></button></div>
<?php endforeach; ?>
</div>
<main class="wrap">
<?php
}

function page_foot(string $active = ''): void
{
    $u = current_user();
    echo "</main>\n";
    if ($u) {
        if ($u['role'] === 'master') {
            $pc = pending_count();
            $tabs = [
                ['m_home.php', 'काम दें', 'scissors', ''],
                ['m_work.php', 'सभी काम', 'list', $pc ?: ''],
                ['m_hisaab.php', 'हिसाब', 'wallet', ''],
                ['m_karigar.php', 'कारीगर', 'users', ''],
                ['m_settings.php', 'सेटिंग', 'settings', ''],
            ];
        } else {
            $pc = (int)val("SELECT COUNT(*) FROM jobs WHERE karigar_id = ? AND status = 'pending'", [$u['id']]);
            $tabs = [
                ['k_home.php', 'मेरे काम', 'scissors', $pc ?: ''],
                ['k_done.php', 'पूरे किए', 'checkc', ''],
                ['k_hisaab.php', 'हिसाब', 'wallet', ''],
                ['account.php', 'अकाउंट', 'user', ''],
            ];
        }
        echo '<nav class="nav" aria-label="मेनू"><div class="in">';
        foreach ($tabs as [$href, $label, $ic, $badge]) {
            $cur = $href === $active ? ' aria-current="page"' : '';
            echo '<a href="' . h($href) . '"' . $cur . '><span class="ic">' . icon($ic) . '</span><span class="tl">' . h($label) . '</span>'
               . ($badge !== '' ? '<span class="badge">' . h($badge) . '</span>' : '') . '</a>';
        }
        echo '</div></nav>';
    }
    echo '<script src="assets/app.js?v=6"></script></body></html>';
}

/** Stat tile. $v is the number; $money formats it as rupees. */
function stat_tile(string $label, int $v, string $ic, string $tone = '', string $href = '', bool $money = false, string $sub = ''): string
{
    $tag = $href !== '' ? 'a' : 'div';
    return '<' . $tag . ' class="stat ' . $tone . '"' . ($href !== '' ? ' href="' . h($href) . '"' : '') . '>'
         . '<span class="si">' . icon($ic) . '</span>'
         . '<span class="v" data-count="' . $v . '"' . ($money ? ' data-money' : '') . '>' . ($money ? inr($v) : $v) . '</span>'
         . '<span class="k">' . h($label) . ($sub !== '' ? ' <em>' . h($sub) . '</em>' : '') . '</span></' . $tag . '>';
}

/** One work item card. $actions is extra HTML (forms) shown under the card. */
function job_card(array $j, array $o = []): string
{
    $done = $j['status'] === 'done';
    if ($j['payment_id']) {
        $pill = '<span class="pill paid">' . icon('check') . 'भुगतान हुआ</span>';
        $st = 'paid';
    } elseif ($done) {
        $pill = '<span class="pill done">' . icon('check') . 'पूरा</span>';
        $st = 'done';
    } else {
        $d = days_since((int)$j['assigned_at']);
        $st = $d >= 5 ? 'late' : 'wait';
        $pill = '<span class="pill ' . $st . '">' . icon('clock') . ($d < 1 ? 'आज' : $d . ' दिन') . '</span>';
    }
    $meta = [];
    if (($o['showK'] ?? true)) { $meta[] = '<span class="kn">' . h($j['k_name']) . '</span>'; }
    $meta[] = 'दिया ' . hdate((int)$j['assigned_at']);
    if ($done) { $meta[] = 'पूरा ' . hdate((int)$j['done_at']); }
    if (!empty($o['money'])) { $meta[] = '<b class="money">' . inr($j['amount']) . '</b> <span class="muted">(' . inr($j['rate']) . '×' . (int)$j['qty'] . ')</span>'; }

    $html = '<article class="job s-' . $st . '">'
        . (!empty($o['check']) ? $o['check'] : '')
        . '<div class="tag"><small>नं.</small>' . h($j['copy_no']) . '</div>'
        . '<div class="jb"><div class="jt">' . h($j['item_name']) . ' <span class="qty">× ' . (int)$j['qty'] . '</span></div>'
        . '<div class="jm">' . implode('<i class="dot"></i>', $meta) . '</div>'
        . ($j['note'] !== '' ? '<div class="jn">' . h($j['note']) . '</div>' : '')
        . '</div><div class="jp">' . $pill . '</div>';
    if (!empty($o['actions'])) { $html .= '<div class="acts">' . $o['actions'] . '</div>'; }
    return $html . '</article>';
}

/** Item picker for a karigar: tick what they make, rate box opens beside it. $chosen is [item_id => rate]. */
function item_picker(array $chosen): string
{
    $out = '<div class="skills">';
    foreach (items(false) as $it) {
        $iid = (int)$it['id'];
        $on = isset($chosen[$iid]);
        if (!$it['active'] && !$on) { continue; }
        $out .= '<div class="skill">'
              . '<label class="sk-name"><input type="checkbox" name="make[' . $iid . ']" value="1"' . ($on ? ' checked' : '') . '>'
              . '<span class="box">' . icon('check') . '</span><span>' . h($it['name']) . '</span></label>'
              . '<label class="sk-rate"><span>₹</span><input type="number" name="rate[' . $iid . ']" min="0" max="100000" inputmode="numeric" aria-label="' . h($it['name']) . ' का रेट"'
              . ' value="' . ($on ? (int)$chosen[$iid] : (int)$it['default_rate']) . '"><small>/ पीस</small></label>'
              . '</div>';
    }
    return $out . '</div>';
}

function empty_box(string $msg, string $ic = 'box'): string
{
    return '<div class="empty">' . icon($ic, 'i big') . '<span>' . $msg . '</span></div>';
}

/** Hisaab numbers for one karigar. */
function hisaab_summary(int $kid): array
{
    $u = one("SELECT COALESCE(SUM(rate * qty), 0) AS amt, COALESCE(SUM(qty), 0) AS pcs, COUNT(*) AS n
              FROM jobs WHERE karigar_id = ? AND status = 'done' AND payment_id IS NULL", [$kid]);
    $m = one("SELECT COALESCE(SUM(rate * qty), 0) AS amt, COALESCE(SUM(qty), 0) AS pcs
              FROM jobs WHERE karigar_id = ? AND status = 'done' AND done_at >= ?", [$kid, month_start()]);
    $p = (int)val("SELECT COUNT(*) FROM jobs WHERE karigar_id = ? AND status = 'pending'", [$kid]);
    return ['unpaid' => (int)$u['amt'], 'unpaid_pcs' => (int)$u['pcs'], 'unpaid_n' => (int)$u['n'],
            'month' => (int)$m['amt'], 'month_pcs' => (int)$m['pcs'], 'pending' => $p];
}

function payment_history(int $kid, bool $canUndo): string
{
    $pays = all('SELECT * FROM payments WHERE karigar_id = ? ORDER BY paid_at DESC LIMIT 50', [$kid]);
    if (!$pays) { return '<p class="muted small">कोई भुगतान नहीं.</p>'; }
    $out = '<div class="hist">';
    foreach ($pays as $p) {
        $jobs = all(JOB_SELECT . ' WHERE j.payment_id = ? ORDER BY j.done_at', [$p['id']]);
        $out .= '<details><summary><span class="hd">' . icon('calendar') . '<span>' . hdate((int)$p['paid_at']) . ' · ' . (int)$p['pieces'] . ' पीस'
             . ($p['note'] !== '' ? ' · ' . h($p['note']) : '') . '</span></span><b>' . inr($p['amount']) . '</b></summary><div class="list">';
        foreach ($jobs as $j) { $out .= job_card($j, ['showK' => false, 'money' => true]); }
        if ($canUndo) {
            $out .= '<form method="post" data-confirm="यह भुगतान हटाना है? इसके सारे काम फिर से &quot;भुगतान बाकी&quot; में आ जाएँगे.">'
                 . csrf_field() . '<input type="hidden" name="action" value="unpay"><input type="hidden" name="pid" value="' . (int)$p['id'] . '">'
                 . '<button class="btn danger sm" type="submit">भुगतान हटाएँ</button></form>';
        }
        $out .= '</div></details>';
    }
    return $out . '</div>';
}
