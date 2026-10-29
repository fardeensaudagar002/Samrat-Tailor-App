<?php
require __DIR__ . '/inc/boot.php';
$me = require_role('master');

$kid = g_int('k');
$k = $kid ? one("SELECT * FROM users WHERE id = ? AND role = 'karigar'", [$kid]) : null;

if (is_post() && $k) {
    $a = $_POST['action'] ?? '';
    if ($a === 'pay') {
        $ids = array_values(array_unique(array_map('intval', (array)($_POST['jobs'] ?? []))));
        if (!$ids) {
            flash('कम से कम एक काम चुनें.', 'err');
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $in = implode(',', array_fill(0, count($ids), '?'));
                $rows = all("SELECT id, rate, qty FROM jobs WHERE id IN ($in) AND karigar_id = ? AND status = 'done' AND payment_id IS NULL",
                            array_merge($ids, [$k['id']]));
                if ($rows) {
                    $amt = 0; $pcs = 0;
                    foreach ($rows as $r) { $amt += (int)$r['rate'] * (int)$r['qty']; $pcs += (int)$r['qty']; }
                    q('INSERT INTO payments (karigar_id, amount, pieces, paid_at, note, created_by) VALUES (?, ?, ?, ?, ?, ?)',
                      [$k['id'], $amt, $pcs, time(), p_str('note', 200), $me['id']]);
                    $pid = (int)$pdo->lastInsertId();
                    $ok = array_column($rows, 'id');
                    $in2 = implode(',', array_fill(0, count($ok), '?'));
                    q("UPDATE jobs SET payment_id = ? WHERE id IN ($in2)", array_merge([$pid], $ok));
                    $pdo->commit();
                    flash("{$k['name']} को " . inr($amt) . " का भुगतान दर्ज हुआ ($pcs पीस).");
                } else {
                    $pdo->rollBack();
                    flash('चुने गए काम का भुगतान पहले ही हो चुका है.', 'err');
                }
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
    } elseif ($a === 'unpay') {
        $p = one('SELECT * FROM payments WHERE id = ? AND karigar_id = ?', [p_int('pid'), $k['id']]);
        if ($p) {
            q('UPDATE jobs SET payment_id = NULL WHERE payment_id = ?', [$p['id']]);
            q('DELETE FROM payments WHERE id = ?', [$p['id']]);
            flash(inr($p['amount']) . ' का भुगतान हटाया');
        }
    }
    redirect('m_hisaab.php?k=' . (int)$k['id']);
}

page_head($k ? $k['name'] . ' का हिसाब' : 'हिसाब', 'm_hisaab.php');

if ($k):
    $s = hisaab_summary((int)$k['id']);
    $unpaid = all(JOB_SELECT . " WHERE j.karigar_id = ? AND j.status = 'done' AND j.payment_id IS NULL ORDER BY j.done_at", [$k['id']]);
?>
<div class="phead"><a class="iconbtn" href="m_hisaab.php" aria-label="वापस"><?= icon('back') ?></a><?= avatar($k['name'], 'av lg') ?><div><h1 class="h2"><?= h($k['name']) ?></h1><span class="muted small">हिसाब</span></div></div>
<div class="stats three">
  <?= stat_tile('बाकी भुगतान', $s['unpaid'], 'wallet', 'c-gold', '', true, $s['unpaid_pcs'] . ' पीस') ?>
  <?= stat_tile('इस महीने', $s['month'], 'trend', 'c-green', '', true, $s['month_pcs'] . ' पीस') ?>
  <?= stat_tile('चल रहे', $s['pending'], 'clock', 'c-blue', 'm_work.php?k=' . (int)$k['id']) ?>
</div>

<?php if ($unpaid): ?>
<form class="list" method="post" id="payform" data-confirm-pay="1">
  <?= csrf_field() ?><input type="hidden" name="action" value="pay">
  <div class="sec-head"><h2>भुगतान बाकी</h2><label class="checkall"><input type="checkbox" id="checkall" checked> सब</label></div>
  <?php foreach ($unpaid as $j):
      $chk = '<label class="jcheck"><input type="checkbox" name="jobs[]" value="' . (int)$j['id'] . '" data-amt="' . (int)$j['amount'] . '" data-pcs="' . (int)$j['qty'] . '" checked><span class="sr">चुनें</span></label>';
      echo job_card($j, ['showK' => false, 'money' => true, 'check' => $chk]);
  endforeach; ?>
  <div class="card paybox">
    <div class="field"><label class="lbl" for="note">नोट</label><input id="note" name="note" type="text" maxlength="200"></div>
    <button class="btn ok block" type="submit" id="paybtn" data-name="<?= h($k['name']) ?>"><?= inr($s['unpaid']) ?> का भुगतान दर्ज करें</button>
  </div>
</form>
<?php else: ?>
  <?= empty_box('कोई भुगतान बाकी नहीं', 'checkc') ?>
<?php endif; ?>

<section class="card"><div class="card-h"><span class="ch-ic"><?= icon('calendar') ?></span><h2>पिछले भुगतान</h2></div><?= payment_history((int)$k['id'], true) ?></section>

<?php else:
    $rows = [];
    foreach (karigars(false) as $x) {
        $s = hisaab_summary((int)$x['id']);
        if ($x['active'] || $s['unpaid'] > 0) { $rows[] = [$x, $s]; }
    }
    $tot = array_sum(array_map(fn($r) => $r[1]['unpaid'], $rows));
?>
<h1 class="h2">हिसाब</h1>
<div class="bigtotal"><span class="si"><?= icon('wallet') ?></span><div><span class="k">कुल बाकी भुगतान</span><span class="v" data-count="<?= (int)$tot ?>" data-money><?= inr($tot) ?></span></div></div>
<section class="list">
<?php if (!$rows) { echo empty_box('कोई कारीगर नहीं', 'users'); } ?>
<?php foreach ($rows as [$x, $s]): ?>
  <a class="krow" href="m_hisaab.php?k=<?= (int)$x['id'] ?>">
    <?= avatar($x['name']) ?>
    <span class="sp"><b><?= h($x['name']) ?></b><br><span class="small muted"><?= $s['unpaid_pcs'] ?> पीस बाकी · <?= $s['pending'] ?> चल रहे</span></span>
    <span class="amt<?= $s['unpaid'] ? '' : ' zero' ?>"><?= inr($s['unpaid']) ?></span><span class="go"><?= icon('chev') ?></span>
  </a>
<?php endforeach; ?>
</section>
<?php endif;
page_foot('m_hisaab.php');
