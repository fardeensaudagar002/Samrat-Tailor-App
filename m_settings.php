<?php
require __DIR__ . '/inc/boot.php';
$me = require_role('master');

if (is_post()) {
    $a = $_POST['action'] ?? '';
    if ($a === 'add_item') {
        $name = p_str('name', 50);
        $rate = p_int('rate', 0);
        if ($name === '') { flash('कपड़े का नाम डालें.', 'err'); }
        elseif (val('SELECT id FROM items WHERE name = ?', [$name])) { flash("\"$name\" पहले से है.", 'err'); }
        else {
            $sort = (int)val('SELECT COALESCE(MAX(sort_order),0) FROM items') + 1;
            q('INSERT INTO items (name, default_rate, sort_order, active) VALUES (?, ?, ?, 1)', [$name, max(0, $rate), $sort]);
            flash("\"$name\" जोड़ दिया.");
        }
    } elseif ($a === 'save_items') {
        $names = $_POST['name'] ?? [];
        $rates = $_POST['rate'] ?? [];
        $act   = $_POST['active'] ?? [];
        foreach (items(false) as $it) {
            $iid = (int)$it['id'];
            $n = mb_substr(trim((string)($names[$iid] ?? $it['name'])), 0, 50);
            $r = trim((string)($rates[$iid] ?? ''));
            q('UPDATE items SET name = ?, default_rate = ?, active = ? WHERE id = ?',
              [$n !== '' ? $n : $it['name'], ctype_digit($r) ? (int)$r : (int)$it['default_rate'], isset($act[$iid]) ? 1 : 0, $iid]);
        }
        flash('सेव हो गया');
    }
    redirect('m_settings.php');
}

page_head('सेटिंग', 'm_settings.php');
?>
<h1 class="h2">सेटिंग</h1>

<form class="card" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="save_items">
  <div class="card-h"><span class="ch-ic"><?= icon('scissors') ?></span><h2>कपड़े और रेट</h2></div>
  <div class="itable">
    <div class="ihead"><span>कपड़ा</span><span>रेट ₹</span><span>चालू</span></div>
    <?php foreach (items(false) as $it): $iid = (int)$it['id']; ?>
    <div class="irow">
      <input name="name[<?= $iid ?>]" type="text" aria-label="कपड़े का नाम" value="<?= h($it['name']) ?>" maxlength="50">
      <input name="rate[<?= $iid ?>]" type="number" min="0" inputmode="numeric" aria-label="<?= h($it['name']) ?> का रेट" value="<?= (int)$it['default_rate'] ?>">
      <label class="tick"><input type="checkbox" name="active[<?= $iid ?>]" value="1" <?= $it['active'] ? 'checked' : '' ?>><span class="sr"><?= h($it['name']) ?> चालू</span></label>
    </div>
    <?php endforeach; ?>
  </div>
  <button class="btn primary" type="submit">सेव करें</button>
</form>

<form class="card" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="add_item">
  <div class="card-h"><span class="ch-ic"><?= icon('plus') ?></span><h2>नया कपड़ा</h2></div>
  <div class="row">
    <div class="field"><label class="lbl" for="iname">नाम</label><input id="iname" name="name" type="text" required maxlength="50"></div>
    <div class="field"><label class="lbl" for="irate">रेट ₹</label><input id="irate" name="rate" type="number" min="0" inputmode="numeric" value="0"></div>
  </div>
  <button class="btn" type="submit"><?= icon('plus') ?>जोड़ें</button>
</form>

<section class="card menu">
  <a class="link-row" href="account.php"><?= avatar($me['name'], 'av lg') ?><div><b><?= h($me['name']) ?></b><span class="muted small">@<?= h($me['username']) ?> · पासवर्ड बदलें</span></div><span class="go"><?= icon('chev') ?></span></a>
  <a class="link-row" href="export.php"><span class="ch-ic"><?= icon('download') ?></span><div><b>रिकॉर्ड डाउनलोड</b><span class="muted small">Excel (CSV)</span></div><span class="go"><?= icon('chev') ?></span></a>
</section>
<?php page_foot('m_settings.php');
