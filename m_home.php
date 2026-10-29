<?php
require __DIR__ . '/inc/boot.php';
$me = require_role('master');

$err = '';
if (is_post() && ($_POST['action'] ?? '') === 'assign') {
    $no   = preg_replace('/\s+/', '', p_str('copy_no', 20));
    $iid  = p_int('item_id');
    $kid  = p_int('karigar_id');
    $qty  = p_int('qty', 1);
    $rate = trim((string)($_POST['rate'] ?? ''));
    $note = p_str('note', 200);

    $item = $iid ? one('SELECT * FROM items WHERE id = ? AND active = 1', [$iid]) : null;
    $k    = $kid ? one("SELECT * FROM users WHERE id = ? AND role = 'karigar' AND active = 1", [$kid]) : null;

    if ($no === '' || !preg_match('/^[0-9A-Za-z\-\/]+$/', $no)) { $err = 'कॉपी नंबर डालें.'; }
    elseif (!$item) { $err = 'कपड़ा चुनें.'; }
    elseif (!$k) { $err = 'कारीगर चुनें.'; }
    elseif (!karigar_makes($kid, $iid)) { $err = "{$k['name']} {$item['name']} नहीं बनाते."; }
    elseif ($qty < 1 || $qty > 100) { $err = 'पीस 1 से 100 के बीच होने चाहिए.'; }
    elseif ($rate !== '' && (!ctype_digit($rate) || (int)$rate > 100000)) { $err = 'मज़दूरी सही रुपये में डालें.'; }
    else {
        $r = $rate === '' ? rate_for($kid, $iid) : (int)$rate;
        q('INSERT INTO jobs (copy_no, item_id, qty, karigar_id, rate, note, status, assigned_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
          [$no, $iid, $qty, $kid, $r, $note, 'pending', time(), $me['id']]);
        flash("नंबर $no · {$item['name']} × $qty → {$k['name']}");
        redirect('m_home.php?no=' . rawurlencode($no));
    }
}

$no   = $err ? preg_replace('/\s+/', '', p_str('copy_no', 20)) : g_str('no', 20);
$its  = items();
$ks   = karigars();
$sel  = ['item' => $err ? p_int('item_id') : 0, 'k' => $err ? p_int('karigar_id') : 0, 'qty' => $err ? max(1, p_int('qty', 1)) : 1,
         'rate' => $err ? (string)($_POST['rate'] ?? '') : '', 'note' => $err ? p_str('note', 200) : ''];

$stats = [
    'pending' => pending_count(),
    'given'   => (int)val('SELECT COALESCE(SUM(qty),0) FROM jobs WHERE assigned_at >= ?', [today_start()]),
    'done'    => (int)val("SELECT COALESCE(SUM(qty),0) FROM jobs WHERE status = 'done' AND done_at >= ?", [today_start()]),
    'unpaid'  => (int)val("SELECT COALESCE(SUM(rate*qty),0) FROM jobs WHERE status = 'done' AND payment_id IS NULL"),
];
$sameNo = $no !== '' ? all(JOB_SELECT . ' WHERE j.copy_no = ? ORDER BY j.assigned_at', [$no]) : [];
$today  = all(JOB_SELECT . ' WHERE j.assigned_at >= ? ORDER BY j.assigned_at DESC LIMIT 40', [today_start()]);

page_head('काम दें', 'm_home.php');
?>
<section class="hero">
  <div><p class="hello"><?= greeting() ?>,</p><h1 class="hname"><?= h($me['name']) ?></h1></div>
  <span class="today"><?= icon('calendar') ?><?= hdate(time(), true) ?></span>
</section>

<div class="stats">
  <?= stat_tile('बाकी काम', $stats['pending'], 'clock', 'c-blue', 'm_work.php') ?>
  <?= stat_tile('आज दिए पीस', $stats['given'], 'scissors', 'c-violet') ?>
  <?= stat_tile('आज पूरे पीस', $stats['done'], 'checkc', 'c-green', 'm_work.php?st=done') ?>
  <?= stat_tile('बाकी भुगतान', $stats['unpaid'], 'wallet', 'c-gold', 'm_hisaab.php', true) ?>
</div>

<form class="card assign" method="post" id="assign-form" data-rates="<?= h(rates_json()) ?>">
  <?= csrf_field() ?><input type="hidden" name="action" value="assign">
  <div class="card-h"><span class="ch-ic"><?= icon('plus') ?></span><h2>नया काम</h2></div>
  <?php if ($err): ?><p class="err" role="alert"><?= icon('alert') ?><?= h($err) ?></p><?php endif; ?>

  <div class="field">
    <label class="lbl" for="copy_no">कॉपी नंबर</label>
    <input id="copy_no" name="copy_no" class="big" type="text" inputmode="numeric" autocomplete="off" required value="<?= h($no) ?>">
  </div>

  <fieldset class="field"><legend class="lbl">कारीगर</legend>
  <?php if ($ks): ?>
    <div class="chips people">
    <?php foreach ($ks as $k): ?>
      <label class="chip"><input type="radio" name="karigar_id" value="<?= (int)$k['id'] ?>" required <?= $sel['k'] === (int)$k['id'] ? 'checked' : '' ?>><span><?= avatar($k['name'], 'av xs') ?><?= h($k['name']) ?></span></label>
    <?php endforeach; ?>
    </div>
  <?php else: ?>
    <a class="btn sm" href="m_karigar.php"><?= icon('plus') ?>कारीगर जोड़ें</a>
  <?php endif; ?>
  </fieldset>

  <fieldset class="field"><legend class="lbl">कपड़ा</legend>
    <div class="chips">
    <?php foreach ($its as $i): ?>
      <label class="chip"><input type="radio" name="item_id" value="<?= (int)$i['id'] ?>" required <?= $sel['item'] === (int)$i['id'] ? 'checked' : '' ?>><span><?= h($i['name']) ?></span></label>
    <?php endforeach; ?>
    </div>
  </fieldset>

  <div class="field"><label class="lbl" for="qty">पीस</label>
    <div class="stepper"><button type="button" data-step="-1" aria-label="कम करें">−</button><input id="qty" name="qty" type="number" min="1" max="100" value="<?= (int)$sel['qty'] ?>" inputmode="numeric"><button type="button" data-step="1" aria-label="बढ़ाएँ">+</button></div></div>

  <div class="field"><label class="lbl" for="note">नोट</label>
    <input id="note" name="note" type="text" maxlength="200" value="<?= h($sel['note']) ?>"></div>

  <button class="btn primary block lg" type="submit"><?= icon('scissors') ?>काम दें</button>
</form>

<?php if ($no !== ''): ?>
<section class="list">
  <div class="sec-head"><h2>नंबर <?= h($no) ?></h2><span class="count"><?= count($sameNo) ?></span></div>
  <?= $sameNo ? implode('', array_map(fn($j) => job_card($j), $sameNo)) : empty_box('कोई काम नहीं') ?>
</section>
<?php endif; ?>

<section class="list">
  <div class="sec-head"><h2>आज के काम</h2><a class="link" href="m_work.php?st=all">सभी देखें <?= icon('chev') ?></a></div>
  <?= $today ? implode('', array_map(fn($j) => job_card($j), $today)) : empty_box('आज कोई काम नहीं दिया गया') ?>
</section>
<?php page_foot('m_home.php');
