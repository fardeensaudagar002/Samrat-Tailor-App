(function () {
  'use strict';
  var rupee = function (n) { return '₹' + Math.round(n || 0).toLocaleString('en-IN'); };
  var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var SVG = function (d) { return '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + d + '</svg>'; };
  var IC_Q = SVG('<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01"/>');
  var IC_ALERT = SVG('<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4M12 17h.01"/>');

  /* ---- toasts: auto hide ---- */
  function hideToast(t) { if (!t.isConnected || t.classList.contains('out')) return; t.classList.add('out'); setTimeout(function () { t.remove(); }, 350); }
  document.querySelectorAll('.toasts .flash').forEach(function (t, i) {
    setTimeout(function () { hideToast(t); }, 4500 + i * 400);
  });
  document.addEventListener('click', function (e) {
    var x = e.target.closest('.flash .x');
    if (x) hideToast(x.closest('.flash'));
  });

  /* ---- list stagger index ---- */
  document.querySelectorAll('.list').forEach(function (l) {
    var n = 0;
    Array.prototype.forEach.call(l.children, function (c) {
      if (c.classList.contains('job') || c.classList.contains('krow')) c.style.setProperty('--n', Math.min(n++, 12));
    });
  });

  /* ---- count-up numbers ---- */
  if (!calm) {
    document.querySelectorAll('[data-count]').forEach(function (el) {
      var end = parseInt(el.dataset.count, 10) || 0, money = el.hasAttribute('data-money');
      if (!end) return;
      var t0 = null, dur = Math.min(1100, 500 + end / 20);
      var fmt = function (v) { return money ? rupee(v) : String(Math.round(v)); };
      el.textContent = fmt(0);
      var step = function (t) {
        if (!t0) t0 = t;
        var p = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - p, 3);
        el.textContent = fmt(end * e);
        if (p < 1) requestAnimationFrame(step);
      };
      setTimeout(function () { requestAnimationFrame(step); }, 150);
    });
  }

  /* ---- button ripple ---- */
  document.addEventListener('pointerdown', function (e) {
    var b = e.target.closest('.btn');
    if (!b || b.disabled || calm) return;
    var r = b.getBoundingClientRect(), s = Math.max(r.width, r.height);
    var c = document.createElement('span');
    c.className = 'ripple';
    c.style.cssText = 'width:' + s + 'px;height:' + s + 'px;left:' + (e.clientX - r.left - s / 2) + 'px;top:' + (e.clientY - r.top - s / 2) + 'px';
    b.appendChild(c);
    setTimeout(function () { c.remove(); }, 600);
  });

  /* ---- confirmation box for forms with data-confirm ---- */
  function ask(msg, yesText, onYes, danger) {
    var d = document.createElement('div');
    d.className = 'dialog';
    d.setAttribute('role', 'dialog');
    d.setAttribute('aria-modal', 'true');
    d.innerHTML = '<div class="box"><span class="dic">' + (danger ? IC_ALERT : IC_Q) + '</span><p></p><div class="row"><button type="button" class="btn" data-n>नहीं</button><button type="button" class="btn ' + (danger ? 'danger' : 'primary') + '" data-y></button></div></div>';
    if (danger) d.querySelector('.dic').style.cssText = 'background:var(--danger-bg);color:var(--danger)';
    d.querySelector('p').textContent = msg;
    d.querySelector('[data-y]').textContent = yesText || 'हाँ';
    document.body.appendChild(d);
    d.querySelector('[data-y]').focus();
    var close = function () { d.remove(); };
    d.addEventListener('click', function (e) {
      if (e.target.closest('[data-y]')) { close(); onYes(); }
      else if (e.target.closest('[data-n]') || e.target === d) { close(); }
    });
    d.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  }
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.dataset.ok === '1') return;
    var msg = f.getAttribute('data-confirm');
    if (f.id === 'payform') {
      var t = payTotals();
      if (!t.n) { e.preventDefault(); ask('कम से कम एक काम चुनें.', 'ठीक है', function () {}); return; }
      msg = document.getElementById('paybtn').dataset.name + ' को ' + rupee(t.amt) + ' (' + t.pcs + ' पीस) दे दिए?';
    }
    if (!msg) return;
    e.preventDefault();
    var btn = f.querySelector('button[type=submit]');
    var danger = !!(btn && btn.classList.contains('danger'));
    ask(msg, 'हाँ', function () {
      f.dataset.ok = '1';
      var card = f.closest('.job');
      var go = function () { if (f.requestSubmit) f.requestSubmit(); else f.submit(); };
      if (card && !calm && (danger || (btn && btn.classList.contains('ok')))) { card.classList.add('leaving'); setTimeout(go, 280); }
      else go();
    }, danger);
  });
  // prevent double submits + show a spinner
  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    var f = e.target;
    if ((f.method || '').toLowerCase() === 'get') return;
    var b = f.querySelector('button[type=submit]');
    if (b) setTimeout(function () { b.disabled = true; b.classList.add('loading'); }, 0);
  });
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) document.querySelectorAll('.btn.loading').forEach(function (b) { b.disabled = false; b.classList.remove('loading'); });
  });

  /* ---- show password ---- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-showpw]');
    if (!b) return;
    var i = document.getElementById(b.dataset.showpw);
    var show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    if (b.classList.contains('peek')) b.classList.toggle('on', show);
    else b.textContent = show ? 'छिपाएँ' : 'दिखाएँ';
  });

  /* ---- filters auto submit ---- */
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  /* ---- assign form: qty stepper + rate autofill ---- */
  var af = document.getElementById('assign-form');
  if (af) {
    var rates = {};
    try { rates = JSON.parse(af.dataset.rates || '{}'); } catch (e) {}
    var qty = document.getElementById('qty'), rate = document.getElementById('rate'), total = document.getElementById('total');
    var manual = !!rate && rate.value !== '';
    var picked = function (name) { var r = af.querySelector('input[name="' + name + '"]:checked'); return r ? r.value : ''; };
    var autoRate = function () {
      var i = picked('item_id'), k = picked('karigar_id');
      if (!i) return '';
      var kr = (rates.k && rates.k[k]) || {};
      if (k && kr[i] !== undefined) return kr[i];
      return rates.d && rates.d[i] !== undefined ? rates.d[i] : '';
    };
    var update = function () {
      if (!rate || !total) return;
      if (!manual) { var r = autoRate(); rate.value = r === '' ? '' : r; }
      var q = Math.max(1, parseInt(qty.value, 10) || 1), rv = parseInt(rate.value, 10);
      total.innerHTML = isNaN(rv) ? '' : '<span>मज़दूरी ' + rupee(rv) + ' × ' + q + '</span><b>' + rupee(rv * q) + '</b>';
    };
    // only the items the picked karigar makes can be chosen
    var filterItems = function () {
      var kr = rates.k && rates.k[picked('karigar_id')];
      af.querySelectorAll('input[name="item_id"]').forEach(function (r) {
        var ok = !kr || kr[r.value] !== undefined;
        r.disabled = !ok;
        r.closest('.chip').classList.toggle('off', !ok);
        if (!ok && r.checked) r.checked = false;
      });
    };
    af.addEventListener('change', function (e) {
      if (e.target.name === 'item_id' || e.target.name === 'karigar_id') { manual = false; }
      if (e.target.name === 'karigar_id') filterItems();
      update();
    });
    filterItems();
    if (rate) rate.addEventListener('input', function () { manual = rate.value !== ''; update(); });
    qty.addEventListener('input', update);
    af.querySelectorAll('[data-step]').forEach(function (b) {
      b.addEventListener('click', function () {
        qty.value = Math.min(100, Math.max(1, (parseInt(qty.value, 10) || 1) + parseInt(b.dataset.step, 10)));
        update();
      });
    });
    update();
    var no = document.getElementById('copy_no');
    if (no && !no.value) no.focus({ preventScroll: true });
  }

  /* ---- payment checkboxes ---- */
  function payTotals() {
    var amt = 0, pcs = 0, n = 0;
    document.querySelectorAll('#payform input[name="jobs[]"]:checked').forEach(function (c) {
      amt += +c.dataset.amt; pcs += +c.dataset.pcs; n++;
    });
    return { amt: amt, pcs: pcs, n: n };
  }
  var pf = document.getElementById('payform');
  if (pf) {
    var btn = document.getElementById('paybtn'), all = document.getElementById('checkall');
    var refresh = function () {
      var t = payTotals();
      btn.textContent = t.n ? rupee(t.amt) + ' का भुगतान दर्ज करें · ' + t.pcs + ' पीस' : 'काम चुनें';
      btn.disabled = !t.n;
    };
    pf.addEventListener('change', function (e) {
      if (e.target === all) pf.querySelectorAll('input[name="jobs[]"]').forEach(function (c) { c.checked = all.checked; });
      refresh();
    });
    refresh();
  }
})();
