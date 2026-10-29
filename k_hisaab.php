<?php
require __DIR__ . '/inc/boot.php';
$me = require_role('karigar');

$s = hisaab_summary((int)$me['id']);
$unpaid = all(JOB_SELECT . " WHERE j.karigar_id = ? AND j.status = 'done' AND j.payment_id IS NULL ORDER BY j.done_at", [$me['id']]);

page_head('मेरा हिसाब', 'k_hisaab.php');
?>
<h1 class="h2">मेरा हिसाब</h1>
<div class="stats two">
  <?= stat_tile('बाकी भुगतान', $s['unpaid'], 'wallet', 'c-gold', '', true, $s['unpaid_pcs'] . ' पीस') ?>
  <?= stat_tile('इस महीने', $s['month'], 'trend', 'c-green', '', true, $s['month_pcs'] . ' पीस') ?>
</div>

<section class="list">
  <div class="sec-head"><h2>भुगतान बाकी</h2></div>
  <?= $unpaid ? implode('', array_map(fn($j) => job_card($j, ['showK' => false, 'money' => true]), $unpaid)) : empty_box('कोई भुगतान बाकी नहीं', 'checkc') ?>
</section>

<section class="card"><div class="card-h"><span class="ch-ic"><?= icon('wallet') ?></span><h2>मिले हुए भुगतान</h2></div><?= payment_history((int)$me['id'], false) ?></section>
<?php page_foot('k_hisaab.php');
