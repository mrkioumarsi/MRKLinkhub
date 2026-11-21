(function () {
  const d = document.documentElement, ls = window.localStorage;
  const get = k => { try { return ls.getItem(k); } catch (e) { return null; } };
  const set = (k, v) => { try { ls.setItem(k, v); } catch (e) {} };
  const rtl = () => d.dir === 'rtl';
  const allow = (d.dataset.langs || 'fa,en').split(',').filter(Boolean);

  function setLang(l) {
    if (allow.indexOf(l) < 0) l = allow[0] || 'fa';
    d.lang = l; d.dir = l === 'fa' ? 'rtl' : 'ltr'; set('lang', l);
    const b = document.getElementById('lang');
    if (b) b.textContent = l === 'fa' ? 'EN' : 'فا';
    const don = document.getElementById('donate');
    if (don) don.setAttribute('href', 'donate.php?lang=' + l);
    document.querySelectorAll('[data-fa][data-en]').forEach(el => {
      el.setAttribute('aria-label', l === 'fa' ? el.getAttribute('data-fa') : el.getAttribute('data-en'));
    });
    paintNote();
  }
  function waitPhrase(sec, lang) {
    sec = Math.max(1, +sec || 1);
    let n, faWord, enOne, enMany;
    if (sec < 3600) {
      n = Math.ceil(sec / 60); faWord = 'دقیقه'; enOne = 'minute'; enMany = 'minutes';
    } else if (sec < 86400) {
      n = Math.ceil(sec / 3600); faWord = 'ساعت'; enOne = 'hour'; enMany = 'hours';
    } else if (sec < 7 * 86400) {
      n = Math.ceil(sec / 86400); faWord = 'روز'; enOne = 'day'; enMany = 'days';
    } else {
      n = Math.ceil(sec / (7 * 86400)); faWord = 'هفته'; enOne = 'week'; enMany = 'weeks';
    }
    const shown = lang === 'fa' ? String(n).replace(/\d/g, ch => '۰۱۲۳۴۵۶۷۸۹'[ch]) : String(n);
    if (lang === 'fa') return 'تلاش زیاد بود. ' + shown + ' ' + faWord + ' دیگر امتحان کنید.';
    return 'Too many attempts. Try again in ' + shown + ' ' + (n === 1 ? enOne : enMany) + '.';
  }
  let lockTimer = 0;
  function paintNote() {
    const el = document.getElementById('lock');
    if (!el) return;
    if (el.dataset.until) {
      const left = Math.ceil((+el.dataset.until - Date.now()) / 1000);
      if (left <= 0) {
        el.hidden = true;
        el.textContent = '';
        delete el.dataset.until;
        const btn = document.querySelector('#gate [type=submit]');
        if (btn) btn.disabled = false;
        if (lockTimer) { clearInterval(lockTimer); lockTimer = 0; }
        return;
      }
      el.hidden = false;
      el.textContent = waitPhrase(left, d.lang);
      return;
    }
    const kind = el.dataset.note || '';
    if (!kind) return;
    const fa = d.lang === 'fa';
    const map = {
      session: fa ? 'نشست تمام شد. صفحه را دوباره باز کنید.' : 'Session expired. Reload the page.',
      name: fa ? 'نام را درست بنویسید.' : 'Enter a valid name.',
      off: fa ? 'حمایت خاموش است.' : 'Donate is turned off.',
      fail: fa ? 'درخواست انجام نشد.' : 'Request failed.'
    };
    el.hidden = false;
    el.textContent = map[kind] || map.fail;
  }
  function paintTheme(t) {
    d.dataset.theme = t;
    d.style.backgroundColor = t === 'dark' ? '#0d0b1e' : '#f3f0ff';
  }
  function setTheme(t) { paintTheme(t); set('theme', t); }

  const forced = d.dataset.langforce || '';
  const l0 = (forced === 'fa' || forced === 'en') ? forced : get('lang');
  setLang(allow.indexOf(l0) >= 0 ? l0 : (d.lang || allow[0] || 'fa'));
  paintTheme(get('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

  $(function () {
    setLang(d.lang);
    $('#lang').on('click', () => setLang(d.lang === 'fa' ? 'en' : 'fa'));
    $('#theme').on('click', () => setTheme(d.dataset.theme === 'dark' ? 'light' : 'dark'));
    const siteUrl = () => location.origin + location.pathname.replace(/\/(index|donate)\.php$/, '/');
    const copyText = (btn, text) => {
      const icon = btn.querySelector('i');
      const back = btn.getAttribute('data-icon') || 'fa-solid fa-link';
      const done = () => {
        if (!icon) return;
        icon.className = 'fa-solid fa-check';
        clearTimeout(btn._t);
        btn._t = setTimeout(() => { icon.className = back; }, 1400);
      };
      const fallback = () => {
        const box = document.createElement('textarea');
        box.value = text;
        box.setAttribute('readonly', '');
        box.style.position = 'fixed';
        box.style.left = '-999px';
        document.body.appendChild(box);
        box.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
        box.remove();
      };
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(done).catch(fallback);
      else fallback();
    };
    $(document).on('click', '[data-copy]', function () {
      const text = this.getAttribute('data-copy') === 'addr' ? (this.getAttribute('data-text') || '') : siteUrl();
      if (text) copyText(this, text);
    });
    const iconOk = /^fa-(solid|brands) fa-[a-z0-9-]+$/;
    const paintWallets = rows => {
      const list = document.getElementById('wallet-list');
      const empty = document.getElementById('wallet-empty');
      if (!list) return;
      list.replaceChildren();
      if (!rows.length) {
        if (empty) empty.hidden = false;
        return;
      }
      if (empty) empty.hidden = true;
      rows.forEach(row => {
        const art = document.createElement('article');
        art.className = 'acc glass';
        const head = document.createElement('button');
        head.type = 'button';
        head.className = 'acc-h';
        head.setAttribute('aria-expanded', 'false');
        const ic = document.createElement('i');
        ic.className = iconOk.test(row.icon || '') ? row.icon : 'fa-solid fa-coins';
        const title = document.createElement('span');
        const faT = document.createElement('span');
        faT.setAttribute('data-l', 'fa');
        faT.textContent = row.title_fa || row.title_en || '';
        const enT = document.createElement('span');
        enT.setAttribute('data-l', 'en');
        enT.textContent = row.title_en || row.title_fa || '';
        title.append(faT, enT);
        const chev = document.createElement('i');
        chev.className = 'fa-solid fa-chevron-down chev';
        head.append(ic, title, chev);
        const body = document.createElement('div');
        body.className = 'acc-b';
        body.hidden = true;
        const addr = document.createElement('p');
        addr.className = 'addr';
        addr.dir = 'ltr';
        addr.textContent = row.address || '';
        const qr = document.createElement('div');
        qr.className = 'qr';
        qr.setAttribute('data-qr', row.address || '');
        const copy = document.createElement('button');
        copy.type = 'button';
        copy.className = 'btn pri text-btn';
        copy.setAttribute('data-copy', 'addr');
        copy.setAttribute('data-icon', 'fa-solid fa-copy');
        copy.setAttribute('data-text', row.address || '');
        const ci = document.createElement('i');
        ci.className = 'fa-solid fa-copy';
        const cfa = document.createElement('span');
        cfa.setAttribute('data-l', 'fa');
        cfa.textContent = 'کپی آدرس';
        const cen = document.createElement('span');
        cen.setAttribute('data-l', 'en');
        cen.textContent = 'Copy address';
        copy.append(ci, cfa, cen);
        body.append(addr, qr, copy);
        art.append(head, body);
        list.append(art);
      });
    };
    let gateBusy = false;
    $('#gate').on('submit', function (e) {
      e.preventDefault();
      if (gateBusy) return;
      const form = this;
      const input = form.querySelector('[name=who]');
      const name = String(input.value || '').replace(/\s+/g, ' ').trim();
      if (!name) {
        input.value = '';
        input.reportValidity();
        return;
      }
      const btn = form.querySelector('[type=submit]');
      const lock = document.getElementById('lock');
      gateBusy = true;
      if (btn) btn.disabled = true;
      $.ajax({
        url: 'donate.php',
        method: 'POST',
        dataType: 'json',
        data: { who: name, csrf: form.getAttribute('data-csrf') || '' },
        headers: { 'X-CSRF': form.getAttribute('data-csrf') || '' }
      }).done(r => {
        paintWallets((r && r.rows) || []);
        form.hidden = true;
        $('#wallets').prop('hidden', false);
      }).fail(x => {
        const body = x.responseJSON || {};
        if (lock) {
          delete lock.dataset.note;
          delete lock.dataset.until;
          if (x.status === 429 && body.wait) {
            lock.dataset.until = String(Date.now() + (+body.wait * 1000));
            if (!lockTimer) lockTimer = setInterval(paintNote, 30000);
          } else if (x.status === 403 && body.error === 'Session expired. Reload the page.') {
            lock.dataset.note = 'session';
          } else if (x.status === 403) {
            lock.dataset.note = 'off';
          } else if (x.status === 400) {
            lock.dataset.note = 'name';
          } else {
            lock.dataset.note = 'fail';
          }
          paintNote();
        }
        gateBusy = false;
        if (btn && !(lock && lock.dataset.until)) btn.disabled = false;
      });
    });
    $(document).on('click', '.acc-h', function () {
      const item = this.parentNode;
      const open = item.classList.contains('open');
      document.querySelectorAll('.acc').forEach(el => {
        el.classList.remove('open');
        const body = el.querySelector('.acc-b');
        const head = el.querySelector('.acc-h');
        if (body) body.hidden = true;
        if (head) head.setAttribute('aria-expanded', 'false');
      });
      if (open) return;
      item.classList.add('open');
      this.setAttribute('aria-expanded', 'true');
      const body = item.querySelector('.acc-b');
      if (body) body.hidden = false;
      const box = item.querySelector('.qr');
      if (!box || box.dataset.done || typeof qrcode !== 'function') return;
      const text = box.getAttribute('data-qr') || '';
      if (!text) return;
      try {
        if (qrcode.stringToBytesFuncs && qrcode.stringToBytesFuncs['UTF-8']) qrcode.stringToBytes = qrcode.stringToBytesFuncs['UTF-8'];
        const q = qrcode(0, 'M');
        q.addData(text);
        q.make();
        const cell = 4, margin = 2, n = q.getModuleCount();
        const size = n * cell + margin * 2;
        let marks = '';
        for (let row = 0; row < n; row++) {
          for (let col = 0; col < n; col++) {
            if (!q.isDark(row, col)) continue;
            const x = (col * cell + margin - 0.4).toFixed(2);
            const y = (row * cell + margin - 0.4).toFixed(2);
            marks += '<rect x="' + x + '" y="' + y + '" width="4.8" height="4.8" rx="1.15" fill="#000"/>';
          }
        }
        box.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + size + ' ' + size + '" role="img"><rect width="100%" height="100%" fill="#fff"/>' + marks + '</svg>';
        box.dataset.done = '1';
      } catch (err) {}
    });

    $('.slider').each(function () {
      const $s = $(this), $t = $s.find('.track'), n = $t.children().length;
      let i = 0, timer, x0 = null;
      const go = k => {
        i = (k + n) % n;
        $t.css('transform', 'translateX(' + (rtl() ? 1 : -1) * i * 100 + '%)');
        $s.find('.dots b').removeClass('on').eq(i).addClass('on');
      };
      const play = () => { clearInterval(timer); if (n > 1) timer = setInterval(() => go(i + 1), 5000); };
      const step = k => { go(k); play(); };
      $s.find('.next').on('click', () => step(i + 1));
      $s.find('.prev').on('click', () => step(i - 1));
      $s.find('.dots b').each(function (k) { $(this).on('click', () => step(k)); });
      $s.on('touchstart', e => { x0 = e.originalEvent.touches[0].clientX; })
        .on('touchend', e => {
          if (x0 === null) return;
          const dx = e.originalEvent.changedTouches[0].clientX - x0; x0 = null;
          if (Math.abs(dx) > 40) step(i + ((dx < 0) === !rtl() ? 1 : -1));
        })
        .on('mouseenter', () => clearInterval(timer)).on('mouseleave', play);
      $('#lang').on('click', () => go(i)); // direction flips with the language
      go(0); play();
    });

    $(document).on('click', '[data-hit]', function () {
      const p = String($(this).attr('data-hit') || '').split(':');
      if (p.length !== 2) return;
      const body = new URLSearchParams({ k: p[0], id: p[1] });
      if (navigator.sendBeacon) navigator.sendBeacon('click.php', body);
      else fetch('click.php', { method: 'POST', body: body, keepalive: true }).catch(() => {});
    });

    if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js').catch(() => {});
  });
})();
