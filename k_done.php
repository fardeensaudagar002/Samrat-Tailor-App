<?php
require __DIR__ . '/inc/boot.php';
$me = require_role('karigar');

if (is_post() && ($_POST['action'] ?? '') === 'undo') {
    $j = one("SELECT * FROM jobs WHERE id = ? AND karigar_id = ? AND status = 'done' AND payment_id IS NULL", [p_int('id'), $me['id']]);
    if ($j) {
        q("UPDATE jobs SET status = 'pending', done_at = NULL WHERE id = ?", [$j['id']]);
        flash("नंबर {$j['copy_no']} वापस बाकी में");
    }
    redirect('k_done.php');
}

$rows = all(JOB_SELECT . " WHERE j.karigar_id = ? AND j.status = 'done' AND j.done_at >= ? ORDER BY j.done_at DESC LIMIT 200",
            [$me['id'], time() - 60 * 86400]);

page_head('पूरे किए काम', 'k_done.php');
?>
<section class="list">
  <div class="sec-head"><h1 class="h2">पूरे किए काम</h1><span class="count"><?= count($rows) ?></span></div>
  <?php if (!$rows) { echo empty_box('कोई काम नहीं'); } ?>
  <?php foreach ($rows as $j):
      $act = '';
      if (!$j['payment_id'] && (int)$j['done_at'] > time() - 2 * 86400) {
          $act = '<form method="post" data-confirm="नंबर ' . h($j['copy_no']) . ' को वापस बाकी में डालना है?">' . csrf_field()
               . '<input type="hidden" name="action" value="undo"><input type="hidden" name="id" value="' . (int)$j['id'] . '">'
               . '<button class="btn sm ghost" type="submit">वापस बाकी करें</button></form>';
      }
      echo job_card($j, ['showK' => false, 'money' => true, 'actions' => $act]);
  endforeach; ?>
</section>
<?php page_foot('k_done.php');
