<?php
require __DIR__ . '/inc/boot.php';
$me = require_role('master');

$st   = in_array($_GET['st'] ?? '', ['pending', 'done', 'all'], true) ? $_GET['st'] : 'pending';
$kf   = g_int('k');
$nf   = g_str('no', 20);
$page = max(1, g_int('p', 1));
$self = 'm_work.php?' . http_build_query(array_filter(['st' => $st, 'k' => $kf ?: null, 'no' => $nf !== '' ? $nf : null, 'p' => $page > 1 ? $page : null]));

if (is_post()) {
    $id = p_int('id');
    $j  = $id ? one('SELECT * FROM jobs WHERE id = ?', [$id]) : null;
    $a  = $_POST['action'] ?? '';
    if (!$j) {
        flash('यह काम नहीं मिला.', 'err');
    } elseif ($a === 'done' && $j['status'] === 'pending') {
        q("UPDATE jobs SET status = 'done', done_at = ? WHERE id = ?", [time(), $id]);
        flash("नंबर {$j['copy_no']} पूरा मार्क किया.");
    } elseif ($a === 'undo' && $j['status'] === 'done' && !$j['payment_id']) {
        q("UPDATE jobs SET status = 'pending', done_at = NULL WHERE id = ?", [$id]);
        flash("नंबर {$j['copy_no']} वापस बाकी में डाला.");
    } elseif ($a === 'move' && $j['status'] === 'pending') {
        $nk = one("SELECT * FROM users WHERE id = ? AND role = 'karigar' AND active = 1", [p_int('to')]);
        if ($nk && (int)$nk['id'] !== (int)$j['karigar_id']) {
            $keepRate = !empty($_POST['keep_rate']);
            $rate = $keepRate ? (int)$j['rate'] : rate_for((int)$nk['id'], (int)$j['item_id']);
            q('UPDATE jobs SET karigar_id = ?, rate = ?, assigned_at = ? WHERE id = ?', [$nk['id'], $rate, time(), $id]);
            flash("नंबर {$j['copy_no']} अब {$nk['name']} को दिया.");
        } else {
            flash('नया कारीगर चुनें.', 'err');
        }
    } elseif ($a === 'rate' && !$j['payment_id']) {
        $r = p_int('rate', -1);
        if ($r >= 0 && $r <= 100000) { q('UPDATE jobs SET rate = ? WHERE id = ?', [$r, $id]); flash('मज़दूरी बदल दी.'); }
    } elseif ($a === 'delete' && !$j['payment_id']) {
        q('DELETE FROM jobs WHERE id = ?', [$id]);
        flash("नंबर {$j['copy_no']} का काम हटा दिया.");
    } else {
        flash('यह बदलाव नहीं हो सकता. भुगतान हो चुके काम नहीं बदलते.', 'err');
    }
    redirect(back_to($self));
}

$where = [];
$args  = [];
if ($st === 'pending') { $where[] = "j.status = 'pending'"; }
if ($st === 'done')    { $where[] = "j.status = 'done'"; }
if ($kf)               { $where[] = 'j.karigar_id = ?'; $args[] = $kf; }
if ($nf !== '')        { $where[] = 'j.copy_no = ?'; $args[] = $nf; }
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$order = $st === 'pending' ? 'j.assigned_at ASC' : ($st === 'done' ? 'j.done_at DESC' : 'j.assigned_at DESC');
$per = 50;
$total = (int)val('SELECT COUNT(*) FROM jobs j' . $w, $args);
$rows = all(JOB_SELECT . $w . " ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per), $args);

$pending = pending_count();
$late = (int)val("SELECT COUNT(*) FROM jobs WHERE status = 'pending' AND assigned_at < ?", [today_start() - 4 * 86400]);
$doneToday = (int)val("SELECT COUNT(*) FROM jobs WHERE status = 'done' AND done_at >= ?", [today_start()]);
$ksAll = karigars(false);
$ksActive = karigars();

page_head('सभी काम', 'm_work.php');
?>
<div class="stats three">
  <?= stat_tile('बाकी', $pending, 'clock', 'c-blue', 'm_work.php') ?>
  <?= stat_tile('आज पूरे', $doneToday, 'checkc', 'c-green', 'm_work.php?st=done') ?>
  <?= stat_tile('5+ दिन', $late, 'alert', $late ? 'c-red' : 'c-gray') ?>
</div>

<form class="card filters" method="get">
  <div class="seg">
    <?php foreach (['pending' => 'बाकी', 'done' => 'पूरे', 'all' => 'सब'] as $k => $n): ?>
      <label><input type="radio" name="st" value="<?= $k ?>" <?= $st === $k ? 'checked' : '' ?> data-autosubmit><span><?= $n ?></span></label>
    <?php endforeach; ?>
  </div>
  <div class="row">
    <div class="field"><label class="lbl" for="no">कॉपी नंबर</label><div class="ifield"><?= icon('search') ?><input id="no" name="no" type="text" inputmode="numeric" value="<?= h($nf) ?>"></div></div>
    <div class="field"><label class="lbl" for="k">कारीगर</label>
      <select id="k" name="k" data-autosubmit><option value="">सभी कारीगर</option>
      <?php foreach ($ksAll as $k): ?><option value="<?= (int)$k['id'] ?>" <?= $kf === (int)$k['id'] ? 'selected' : '' ?>><?= h($k['name']) ?><?= $k['active'] ? '' : ' (हटाया)' ?></option><?php endforeach; ?>
      </select></div>
  </div>
  <div class="row"><button class="btn primary" type="submit"><?= icon('search') ?>खोजें</button><?php if ($kf || $nf !== '' || $st !== 'pending'): ?><a class="btn ghost" href="m_work.php"><?= icon('x') ?>साफ़ करें</a><?php endif; ?></div>
</form>

<section class="list">
  <div class="sec-head"><h2>काम</h2><span class="count"><?= $total ?></span></div>
<?php if (!$rows) { echo empty_box('कोई काम नहीं मिला', 'search'); }
foreach ($rows as $j) {
    $id = (int)$j['id'];
    $hid = csrf_field() . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="back" value="' . h($self) . '">';
    $act = '<details class="more"><summary>विकल्प</summary><div class="more-in">';
    if ($j['status'] === 'pending') {
        $act .= '<form method="post">' . $hid . '<input type="hidden" name="action" value="done"><button class="btn ok sm" type="submit">' . icon('check') . 'पूरा</button></form>';
        $opts = '';
        foreach ($ksActive as $k) { if ((int)$k['id'] !== (int)$j['karigar_id']) { $opts .= '<option value="' . (int)$k['id'] . '">' . h($k['name']) . '</option>'; } }
        if ($opts) {
            $act .= '<form method="post" class="inline">' . $hid . '<input type="hidden" name="action" value="move">'
                  . '<select name="to" aria-label="नया कारीगर" required><option value="">दूसरे कारीगर को…</option>' . $opts . '</select>'
                  . '<button class="btn sm" type="submit">दें</button></form>';
        }
    }
    if ($j['status'] === 'done' && !$j['payment_id']) {
        $act .= '<form method="post">' . $hid . '<input type="hidden" name="action" value="undo"><button class="btn sm" type="submit">वापस बाकी करें</button></form>';
    }
    if (!$j['payment_id']) {
        $act .= '<form method="post" class="inline">' . $hid . '<input type="hidden" name="action" value="rate">'
              . '<label class="sr" for="r' . $id . '">मज़दूरी</label><input id="r' . $id . '" name="rate" type="number" min="0" inputmode="numeric" value="' . (int)$j['rate'] . '">'
              . '<button class="btn sm" type="submit">रेट</button></form>';
        $act .= '<form method="post" data-confirm="नंबर ' . h($j['copy_no']) . ' का ' . h($j['item_name']) . ' हटाना है? यह वापस नहीं आएगा.">' . $hid
              . '<input type="hidden" name="action" value="delete"><button class="btn danger sm" type="submit">हटाएँ</button></form>';
    } else {
        $act .= '<p class="muted small">भुगतान हो चुका है.</p>';
    }
    $act .= '</div></details>';
    echo job_card($j, ['money' => true, 'actions' => $act]);
}
?>
</section>
<?php if ($total > $per): $pages = (int)ceil($total / $per); ?>
<nav class="pager">
  <?php if ($page > 1): ?><a class="btn sm" href="?<?= h(http_build_query(['st' => $st, 'k' => $kf ?: '', 'no' => $nf, 'p' => $page - 1])) ?>">← पिछले</a><?php endif; ?>
  <span class="muted small">पेज <?= $page ?> / <?= $pages ?></span>
  <?php if ($page < $pages): ?><a class="btn sm" href="?<?= h(http_build_query(['st' => $st, 'k' => $kf ?: '', 'no' => $nf, 'p' => $page + 1])) ?>">अगले →</a><?php endif; ?>
</nav>
<?php endif; ?>
<?php page_foot('m_work.php');
