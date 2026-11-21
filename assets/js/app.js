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
    $('#gate').on('submit', function (e) {
      e.preventDefault();
      const input = this.querySelector('[name=who]');
      const name = String(input.value || '').trim();
      if (!name) {
        input.value = '';
        input.reportValidity();
        return;
      }
      $('#who-name').text(name);
      this.hidden = true;
      $('#wallets').prop('hidden', false);
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
        box.innerHTML = q.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
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
