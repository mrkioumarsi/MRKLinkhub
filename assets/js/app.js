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
  }
  function paintTheme(t) {
    d.dataset.theme = t;
    d.style.backgroundColor = t === 'dark' ? '#0d0b1e' : '#f3f0ff';
  }
  function setTheme(t) { paintTheme(t); set('theme', t); }

  const l0 = get('lang');
  setLang(allow.indexOf(l0) >= 0 ? l0 : (d.lang || allow[0] || 'fa'));
  paintTheme(get('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

  $(function () {
    setLang(d.lang);
    $('#lang').on('click', () => setLang(d.lang === 'fa' ? 'en' : 'fa'));
    $('#theme').on('click', () => setTheme(d.dataset.theme === 'dark' ? 'light' : 'dark'));

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
