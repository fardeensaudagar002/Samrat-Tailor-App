<?php
require __DIR__ . '/inc/boot.php';
$me = require_role('master');

$id  = g_int('id');
$k   = $id ? one("SELECT * FROM users WHERE id = ? AND role = 'karigar'", [$id]) : null;
$err = '';

if (is_post()) {
    $a = $_POST['action'] ?? '';

    if ($a === 'add') {
        $name  = p_str('name', 100);
        $uname = strtolower(p_str('username', 30));
        $mob   = preg_replace('/\D/', '', p_str('mobile', 20));
        $pass  = (string)($_POST['password'] ?? '');
        $made  = posted_items();
        if ($name === '') { $err = 'कारीगर का नाम डालें.'; }
        elseif (!valid_username($uname)) { $err = 'यूज़रनेम 3 से 30 अक्षर का हो: अंग्रेज़ी छोटे अक्षर, अंक, _ या .'; }
        elseif (val('SELECT id FROM users WHERE username = ?', [$uname])) { $err = "यूज़रनेम \"$uname\" पहले से किसी का है. दूसरा चुनें."; }
        elseif (mb_strlen($pass) < 6) { $err = 'पासवर्ड कम से कम 6 अक्षर का होना चाहिए.'; }
        elseif (is_string($made)) { $err = $made; }
        else {
            q('INSERT INTO users (role, name, username, password_hash, mobile, active, created_at) VALUES (?, ?, ?, ?, ?, 1, ?)',
              ['karigar', $name, $uname, password_hash($pass, PASSWORD_DEFAULT), $mob, time()]);
            $nid = (int)db()->lastInsertId();
            save_karigar_items($nid, $made);
            flash("$name जोड़े गए · @$uname");
            redirect('m_karigar.php?id=' . $nid);
        }
    } elseif ($k) {
        $back = 'm_karigar.php?id=' . (int)$k['id'];
        if ($a === 'save') {
            $name  = p_str('name', 100);
            $uname = strtolower(p_str('username', 30));
            $mob   = preg_replace('/\D/', '', p_str('mobile', 20));
            if ($name === '') { flash('नाम खाली नहीं हो सकता.', 'err'); }
            elseif (!valid_username($uname)) { flash('यूज़रनेम सही नहीं है: अंग्रेज़ी छोटे अक्षर, अंक, _ या . (3 से 30 अक्षर).', 'err'); }
            elseif (val('SELECT id FROM users WHERE username = ? AND id <> ?', [$uname, $k['id']])) { flash("यूज़रनेम \"$uname\" पहले से किसी का है.", 'err'); }
            else { q('UPDATE users SET name = ?, username = ?, mobile = ? WHERE id = ?', [$name, $uname, $mob, $k['id']]); flash('सेव हो गया'); }
        } elseif ($a === 'password') {
            $pass = (string)($_POST['password'] ?? '');
            if (mb_strlen($pass) < 6) { flash('पासवर्ड कम से कम 6 अक्षर का होना चाहिए.', 'err'); }
            else { q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $k['id']]); flash("{$k['name']} का पासवर्ड बदल गया"); }
        } elseif ($a === 'rates') {
            $made = posted_items();
            if (is_string($made)) { flash($made, 'err'); }
            else { save_karigar_items((int)$k['id'], $made); flash("{$k['name']} के कपड़े और रेट सेव हुए"); }
        } elseif ($a === 'deactivate') {
            q('UPDATE users SET active = 0 WHERE id = ?', [$k['id']]);
            flash("{$k['name']} को हटा दिया");
        } elseif ($a === 'activate') {
            q('UPDATE users SET active = 1 WHERE id = ?', [$k['id']]);
            flash("{$k['name']} वापस जुड़ गए.");
        } elseif ($a === 'delete') {
            $n = (int)val('SELECT COUNT(*) FROM jobs WHERE karigar_id = ?', [$k['id']]) + (int)val('SELECT COUNT(*) FROM payments WHERE karigar_id = ?', [$k['id']]);
            if ($n > 0) { flash('काम का रिकॉर्ड होने से नहीं मिटेगा.', 'err'); }
            else {
                q('DELETE FROM rates WHERE karigar_id = ?', [$k['id']]);
                q('DELETE FROM users WHERE id = ?', [$k['id']]);
                flash("{$k['name']} को पूरी तरह मिटा दिया.");
                redirect('m_karigar.php');
            }
        }
        redirect($back);
    }
}

page_head($k ? $k['name'] : 'कारीगर', 'm_karigar.php');

if ($k):
    $s = hisaab_summary((int)$k['id']);
    $jobCount = (int)val('SELECT COUNT(*) FROM jobs WHERE karigar_id = ?', [$k['id']]);
?>
<div class="phead"><a class="iconbtn" href="m_karigar.php" aria-label="वापस"><?= icon('back') ?></a><?= avatar($k['name'], 'av lg') ?><div><h1 class="h2"><?= h($k['name']) ?><?= $k['active'] ? '' : ' <span class="pill paid">हटाया गया</span>' ?></h1><span class="muted small">@<?= h($k['username']) ?><?= $k['mobile'] !== '' ? ' · ' . h($k['mobile']) : '' ?></span></div></div>

<div class="stats three">
  <?= stat_tile('बाकी काम', $s['pending'], 'clock', 'c-blue', 'm_work.php?k=' . (int)$k['id']) ?>
  <?= stat_tile('बाकी भुगतान', $s['unpaid'], 'wallet', 'c-gold', 'm_hisaab.php?k=' . (int)$k['id'], true) ?>
  <?= stat_tile('इस महीने पीस', $s['month_pcs'], 'trend', 'c-green') ?>
</div>

<form class="card" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="rates">
  <div class="card-h"><span class="ch-ic"><?= icon('scissors') ?></span><h2>क्या बनाते हैं · रेट</h2></div>
  <?= item_picker(karigar_items((int)$k['id'])) ?>
  <button class="btn primary" type="submit">सेव करें</button>
</form>

<form class="card" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="save">
  <div class="card-h"><span class="ch-ic"><?= icon('user') ?></span><h2>जानकारी</h2></div>
  <div class="row">
    <div class="field"><label class="lbl" for="name">नाम</label><input id="name" name="name" type="text" required value="<?= h($k['name']) ?>"></div>
    <div class="field"><label class="lbl" for="mobile">मोबाइल</label><input id="mobile" name="mobile" type="tel" inputmode="numeric" value="<?= h($k['mobile']) ?>"></div>
  </div>
  <div class="field"><label class="lbl" for="username">लॉगिन यूज़रनेम</label><input id="username" name="username" type="text" required autocapitalize="none" spellcheck="false" value="<?= h($k['username']) ?>"></div>
  <button class="btn primary" type="submit">सेव करें</button>
</form>

<form class="card" method="post" autocomplete="off">
  <?= csrf_field() ?><input type="hidden" name="action" value="password">
  <div class="card-h"><span class="ch-ic"><?= icon('lock') ?></span><h2>नया पासवर्ड</h2></div>
  <div class="field"><label class="lbl" for="password">नया पासवर्ड</label>
    <div class="pw"><input id="password" name="password" type="password" minlength="6" required autocomplete="new-password"><button type="button" class="btn sm" data-showpw="password">दिखाएँ</button></div></div>
  <button class="btn" type="submit">पासवर्ड सेट करें</button>
</form>

<section class="card">
  <div class="card-h"><span class="ch-ic red"><?= icon('alert') ?></span><h2>कारीगर हटाएँ</h2></div>
  <?php if ($k['active']): ?>
    <?php if ($s['pending']): ?><p class="warnline small"><?= icon('alert') ?><span><?= $s['pending'] ?> काम अभी बाकी हैं</span></p><?php endif; ?>
    <form method="post" data-confirm="<?= h($k['name']) ?> को काम से हटाना है?"><?= csrf_field() ?><input type="hidden" name="action" value="deactivate"><button class="btn danger" type="submit">काम से हटाएँ</button></form>
  <?php else: ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="activate"><button class="btn ok" type="submit">वापस जोड़ें</button></form>
  <?php endif; ?>
  <?php if ($jobCount === 0): ?>
    <form method="post" data-confirm="<?= h($k['name']) ?> को पूरी तरह मिटाना है? यह वापस नहीं आएगा."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn danger sm" type="submit">पूरी तरह मिटाएँ</button></form>
  <?php endif; ?>
</section>

<?php else:
    $list = karigars(false);
?>
<div class="sec-head"><h1 class="h2">कारीगर</h1><span class="count"><?= count($list) ?></span></div>

<form class="card" method="post" autocomplete="off">
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div class="card-h"><span class="ch-ic"><?= icon('plus') ?></span><h2>नया कारीगर</h2></div>
  <?php if ($err): ?><p class="err" role="alert"><?= h($err) ?></p><?php endif; ?>
  <div class="row">
    <div class="field"><label class="lbl" for="name">नाम</label><input id="name" name="name" type="text" required value="<?= h($_POST['name'] ?? '') ?>"></div>
    <div class="field"><label class="lbl" for="mobile">मोबाइल</label><input id="mobile" name="mobile" type="tel" inputmode="numeric" value="<?= h($_POST['mobile'] ?? '') ?>"></div>
  </div>
  <div class="row">
    <div class="field"><label class="lbl" for="username">यूज़रनेम</label><input id="username" name="username" type="text" required autocapitalize="none" spellcheck="false" value="<?= h($_POST['username'] ?? '') ?>"></div>
    <div class="field"><label class="lbl" for="password">पासवर्ड</label>
      <div class="pw"><input id="password" name="password" type="password" required minlength="6" autocomplete="new-password"><button type="button" class="btn sm" data-showpw="password">दिखाएँ</button></div></div>
  </div>
  <fieldset class="field"><legend class="lbl">क्या बनाते हैं · रेट ₹</legend>
    <?php
      $pick = [];
      if ($err) {
          foreach (items(false) as $it) {
              $iid = (int)$it['id'];
              if (!empty($_POST['make'][$iid])) { $pick[$iid] = (int)($_POST['rate'][$iid] ?? $it['default_rate']); }
          }
      }
    ?>
    <?= item_picker($pick) ?>
  </fieldset>
  <button class="btn primary block" type="submit"><?= icon('plus') ?>कारीगर जोड़ें</button>
</form>

<section class="list">
  <?php if (!$list) { echo empty_box('कोई कारीगर नहीं', 'users'); } ?>
  <?php foreach ($list as $x): $s = hisaab_summary((int)$x['id']); ?>
    <a class="krow" href="m_karigar.php?id=<?= (int)$x['id'] ?>">
      <?= avatar($x['name']) ?>
      <span class="sp"><b><?= h($x['name']) ?></b><?= $x['active'] ? '' : ' <span class="pill paid">हटाया गया</span>' ?><br>
        <span class="small muted">@<?= h($x['username']) ?> · <?= $s['pending'] ?> काम बाकी</span></span>
      <span class="go"><?= icon('chev') ?></span>
    </a>
  <?php endforeach; ?>
</section>
<?php endif;
page_foot('m_karigar.php');
