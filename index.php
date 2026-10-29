<?php
require __DIR__ . '/inc/boot.php';

if ($u = current_user()) { redirect(home_for($u)); }

$hasMaster = (int)val("SELECT COUNT(*) FROM users WHERE role = 'master'") > 0;
$as = ($_GET['as'] ?? 'master') === 'karigar' ? 'karigar' : 'master';
$err = '';
$uname = '';

if (is_post()) {
    check_post();
    $action = $_POST['action'] ?? '';

    if (!$hasMaster && $action === 'setup') {
        $name  = p_str('name', 100);
        $uname = strtolower(p_str('username', 30));
        $pass  = (string)($_POST['password'] ?? '');
        $pass2 = (string)($_POST['password2'] ?? '');
        if ($name === '') { $err = 'अपना नाम डालें.'; }
        elseif (!valid_username($uname)) { $err = 'यूज़रनेम 3 से 30 अक्षर का हो: सिर्फ़ अंग्रेज़ी छोटे अक्षर, अंक, _ या .'; }
        elseif (mb_strlen($pass) < 6) { $err = 'पासवर्ड कम से कम 6 अक्षर का होना चाहिए.'; }
        elseif ($pass !== $pass2) { $err = 'दोनों पासवर्ड एक जैसे नहीं हैं.'; }
        else {
            q('INSERT INTO users (role, name, username, password_hash, active, created_at) VALUES (?, ?, ?, ?, 1, ?)',
              ['master', $name, $uname, password_hash($pass, PASSWORD_DEFAULT), time()]);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)db()->lastInsertId();
            flash('मास्टर अकाउंट बन गया. अब "कारीगर" में जाकर कारीगर जोड़ें.');
            redirect('m_karigar.php');
        }
    } elseif ($hasMaster && $action === 'login') {
        $as    = ($_POST['as'] ?? '') === 'karigar' ? 'karigar' : 'master';
        $uname = strtolower(p_str('username', 50));
        $pass  = (string)($_POST['password'] ?? '');
        $ip    = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

        q('DELETE FROM login_attempts WHERE at < ?', [time() - 86400]);
        $fails = (int)val('SELECT COUNT(*) FROM login_attempts WHERE at > ? AND (ip = ? OR username = ?)', [time() - 900, $ip, $uname]);

        if ($fails >= 8) {
            $err = 'बहुत बार गलत पासवर्ड डाला गया. 15 मिनट बाद फिर कोशिश करें.';
        } else {
            $user = $uname !== '' ? one('SELECT * FROM users WHERE username = ?', [$uname]) : null;
            $ok = $user && password_verify($pass, $user['password_hash']);
            if ($ok && !$user['active']) {
                $err = 'यह अकाउंट बंद कर दिया गया है. मास्टर जी से बात करें.';
            } elseif ($ok && $user['role'] !== $as) {
                $err = $user['role'] === 'master' ? 'यह मास्टर का अकाउंट है. ऊपर "मास्टर लॉगिन" चुनें.' : 'यह कारीगर का अकाउंट है. ऊपर "कारीगर लॉगिन" चुनें.';
            } elseif ($ok) {
                q('DELETE FROM login_attempts WHERE username = ?', [$uname]);
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $user['id']]);
                }
                session_regenerate_id(true);
                $_SESSION['uid'] = (int)$user['id'];
                redirect(home_for($user));
            } else {
                q('INSERT INTO login_attempts (ip, username, at) VALUES (?, ?, ?)', [$ip, $uname, time()]);
                $err = 'यूज़रनेम या पासवर्ड गलत है.';
            }
        }
    }
}

page_head('लॉगिन');
?>
<div class="login">
  <div class="brand">
    <img class="mark xl" src="assets/icon.svg?v=2" alt="" width="84" height="84">
    <div class="logo"><?= h($CFG['shop_name']) ?></div>
    <div class="tagline"><?= h($CFG['shop_city']) ?></div>
  </div>

<?php if (!$hasMaster): ?>
  <form class="card glass" method="post" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="action" value="setup">
    <h1 class="h2">मास्टर अकाउंट बनाएँ</h1>
    <?php if ($err): ?><p class="err" role="alert"><?= icon('alert') ?><?= h($err) ?></p><?php endif; ?>
    <div class="field"><label class="lbl" for="name">नाम</label><div class="ifield"><?= icon('user') ?><input id="name" name="name" type="text" required value="<?= h($_POST['name'] ?? '') ?>"></div></div>
    <div class="field"><label class="lbl" for="username">यूज़रनेम</label><div class="ifield"><?= icon('user') ?><input id="username" name="username" type="text" required autocapitalize="none" spellcheck="false" value="<?= h($uname) ?>"></div></div>
    <div class="field"><label class="lbl" for="password">पासवर्ड</label><div class="ifield"><?= icon('lock') ?><input id="password" name="password" type="password" required minlength="6" autocomplete="new-password"></div></div>
    <div class="field"><label class="lbl" for="password2">पासवर्ड दोबारा</label><div class="ifield"><?= icon('lock') ?><input id="password2" name="password2" type="password" required minlength="6" autocomplete="new-password"></div></div>
    <button class="btn primary block lg" type="submit">अकाउंट बनाएँ</button>
  </form>
<?php else: ?>
  <form class="card glass" method="post" action="index.php?as=<?= h($as) ?>">
    <div class="seg" role="tablist">
      <a href="?as=master" role="tab" aria-selected="<?= $as === 'master' ? 'true' : 'false' ?>"><?= icon('scissors') ?>मास्टर</a>
      <a href="?as=karigar" role="tab" aria-selected="<?= $as === 'karigar' ? 'true' : 'false' ?>"><?= icon('user') ?>कारीगर</a>
    </div>
    <?= csrf_field() ?><input type="hidden" name="action" value="login"><input type="hidden" name="as" value="<?= h($as) ?>">
    <h1 class="sr"><?= $as === 'master' ? 'मास्टर लॉगिन' : 'कारीगर लॉगिन' ?></h1>
    <?php if ($err): ?><p class="err" role="alert"><?= icon('alert') ?><?= h($err) ?></p><?php endif; ?>
    <div class="field"><label class="lbl" for="username">यूज़रनेम</label><div class="ifield"><?= icon('user') ?><input id="username" name="username" type="text" required autocapitalize="none" autocomplete="username" spellcheck="false" value="<?= h($uname) ?>"></div></div>
    <div class="field"><label class="lbl" for="password">पासवर्ड</label>
      <div class="ifield"><?= icon('lock') ?><input id="password" name="password" type="password" required autocomplete="current-password"><button type="button" class="peek" data-showpw="password" aria-label="पासवर्ड दिखाएँ"><?= icon('eye') ?></button></div></div>
    <button class="btn primary block lg" type="submit">लॉगिन करें</button>
  </form>
<?php endif; ?>
  <p class="foot muted small center">© <?= date('Y') ?> <?= h($CFG['shop_name']) ?></p>
</div>
<?php page_foot();
