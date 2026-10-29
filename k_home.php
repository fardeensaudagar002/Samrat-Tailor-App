<?php
require __DIR__ . '/inc/boot.php';
$me = require_role('karigar');

if (is_post() && ($_POST['action'] ?? '') === 'done') {
    $j = one("SELECT j.*, i.name AS item_name FROM jobs j JOIN items i ON i.id = j.item_id WHERE j.id = ? AND j.karigar_id = ? AND j.status = 'pending'",
             [p_int('id'), $me['id']]);
    if ($j) {
        q("UPDATE jobs SET status = 'done', done_at = ? WHERE id = ?", [time(), $j['id']]);
        flash("नंबर {$j['copy_no']} · {$j['item_name']} × {$j['qty']} पूरा हुआ");
    }
    redirect('k_home.php');
}

$rows = all(JOB_SELECT . " WHERE j.karigar_id = ? AND j.status = 'pending' ORDER BY j.assigned_at", [$me['id']]);
$pcs = array_sum(array_map(fn($j) => (int)$j['qty'], $rows));
$doneToday = (int)val("SELECT COALESCE(SUM(qty),0) FROM jobs WHERE karigar_id = ? AND status = 'done' AND done_at >= ?", [$me['id'], today_start()]);
$s = hisaab_summary((int)$me['id']);

page_head('मेरे काम', 'k_home.php');
?>
<section class="hero">
  <div><p class="hello"><?= greeting() ?>,</p><h1 class="hname"><?= h($me['name']) ?></h1></div>
  <span class="today"><?= icon('calendar') ?><?= hdate(time(), true) ?></span>
</section>

<div class="stats three">
  <?= stat_tile('बाकी काम', count($rows), 'clock', 'c-blue', '', false, $pcs . ' पीस') ?>
  <?= stat_tile('आज पूरे पीस', $doneToday, 'checkc', 'c-green') ?>
  <?= stat_tile('बाकी भुगतान', $s['unpaid'], 'wallet', 'c-gold', 'k_hisaab.php', true) ?>
</div>

<section class="list">
  <div class="sec-head"><h2>बाकी काम</h2><span class="count"><?= count($rows) ?></span></div>
  <?php if (!$rows) { echo empty_box('कोई काम बाकी नहीं', 'checkc'); } ?>
  <?php foreach ($rows as $j):
      $act = '<form method="post" data-confirm="नंबर ' . h($j['copy_no']) . ' · ' . h($j['item_name']) . ' × ' . (int)$j['qty'] . ' पूरा हो गया?">'
           . csrf_field() . '<input type="hidden" name="action" value="done"><input type="hidden" name="id" value="' . (int)$j['id'] . '">'
           . '<button class="btn ok block" type="submit">' . icon('check') . 'पूरा हुआ</button></form>';
      echo job_card($j, ['showK' => false, 'actions' => $act]);
  endforeach; ?>
</section>
<?php page_foot('k_home.php');
