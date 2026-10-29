<?php
require __DIR__ . '/inc/boot.php';
$me = current_user();
if (!$me) { redirect('index.php'); }
check_post();

$err = '';
if (is_post() && ($_POST['action'] ?? '') === 'password') {
    $cur = (string)($_POST['current'] ?? '');
    $new = (string)($_POST['new'] ?? '');
    $new2 = (string)($_POST['new2'] ?? '');
    if (!password_verify($cur, $me['password_hash'])) { $err = 'अभी वाला पासवर्ड गलत है.'; }
    elseif (mb_strlen($new) < 6) { $err = 'नया पासवर्ड कम से कम 6 अक्षर का होना चाहिए.'; }
    elseif ($new !== $new2) { $err = 'दोनों नए पासवर्ड एक जैसे नहीं हैं.'; }
    else {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        session_regenerate_id(true);
        flash('पासवर्ड बदल गया');
        redirect('account.php');
    }
}

$active = $me['role'] === 'master' ? 'm_settings.php' : 'account.php';
page_head('पासवर्ड बदलें', $active);
?>
<h1 class="h2">मेरा अकाउंट</h1>
<section class="card profile">
  <?= avatar($me['name'], 'av lg') ?>
  <div><b class="pn"><?= h($me['name']) ?></b><span class="muted">@<?= h($me['username']) ?> · <?= $me['role'] === 'master' ? 'मास्टर' : 'कारीगर' ?></span></div>
</section>
<form class="card" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="password">
  <div class="card-h"><span class="ch-ic"><?= icon('lock') ?></span><h2>पासवर्ड बदलें</h2></div>
  <?php if ($err): ?><p class="err" role="alert"><?= h($err) ?></p><?php endif; ?>
  <div class="field"><label class="lbl" for="current">अभी वाला पासवर्ड</label><input id="current" name="current" type="password" required autocomplete="current-password"></div>
  <div class="field"><label class="lbl" for="new">नया पासवर्ड</label><input id="new" name="new" type="password" required minlength="6" autocomplete="new-password"></div>
  <div class="field"><label class="lbl" for="new2">नया पासवर्ड दोबारा</label><input id="new2" name="new2" type="password" required minlength="6" autocomplete="new-password"></div>
  <button class="btn primary" type="submit">पासवर्ड बदलें</button>
</form>
<?php page_foot($active);
