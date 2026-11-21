$(function () {
  const d = document.documentElement;
  const store = {
    get: k => { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} }
  };
  function paintTheme(t) {
    d.dataset.theme = t;
    d.style.backgroundColor = t === 'dark' ? '#0d0b1e' : '#f3f0ff';
  }
  paintTheme(store.get('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
  $('#mode').on('click', () => {
    const t = d.dataset.theme === 'dark' ? 'light' : 'dark';
    paintTheme(t); store.set('theme', t);
  });
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/admin/sw.js', { scope: '/admin/' }).catch(() => {});

  const cfg = $('body').data('cfg') || {}, S = cfg.schema;
  let tt;
  const toast = (msg, bad) => {
    $('#toast').text(msg).toggleClass('bad', !!bad).addClass('show');
    clearTimeout(tt); tt = setTimeout(() => $('#toast').removeClass('show'), 2800);
  };
  const fa = (cfg.lang || d.lang) === 'fa';
  const errs = {
    'Wrong username or password': 'نام کاربری یا رمز نادرست است',
    'Session expired. Reload the page.': 'نشست تمام شد. صفحه را دوباره باز کنید.',
    'Too many attempts. Try again in 15 minutes.': 'تلاش‌ها زیاد شد. ۱۵ دقیقه بعد دوباره امتحان کنید.',
    'Not authorized': 'اجازه دسترسی نیست',
    'Current password is wrong': 'رمز فعلی نادرست است',
    'New password needs at least 10 characters': 'رمز جدید باید حداقل ۱۰ نویسه باشد',
    'The file is larger than the server allows. Use a smaller image.': 'حجم فایل بیشتر از حد سرور است. تصویر کوچک‌تری بگذارید.',
    'The file is larger than the server allows': 'حجم فایل بیشتر از حد سرور است',
    'Upload failed': 'بارگذاری انجام نشد',
    'Image is larger than 5 MB': 'حجم تصویر بیشتر از ۵ مگابایت است',
    'Only JPG, PNG or WebP images are allowed': 'فقط تصویر JPG یا PNG یا WebP مجاز است',
    'The uploads folder is not writable. Set its permission to 755.': 'پوشه آپلود قابل نوشتن نیست.',
    'Could not save the file': 'فایل ذخیره نشد',
    'Bad request': 'درخواست نادرست است',
    'Unknown action': 'عملیات ناشناخته است',
    'Invalid request': 'درخواست نامعتبر است',
    'Zip support is missing on this server': 'افزونه Zip روی سرور نصب نیست',
    'Could not download the release': 'دانلود نسخه از گیت‌هاب انجام نشد',
    'GitHub did not return a release': 'نسخه‌ای در گیت‌هاب پیدا نشد',
    'Could not open the release archive': 'فایل نسخه باز نشد',
    'The release archive is not safe': 'فایل نسخه امن نیست',
    'Could not write the update': 'نوشتن بروزرسانی روی هاست انجام نشد'
  };
  const say = x => {
    const raw = (x.responseJSON || {}).error;
    if (!raw) return (fa ? 'درخواست انجام نشد' : 'Request failed') + ' (' + x.status + ')';
    if (!fa) return raw;
    if (errs[raw]) return errs[raw];
    const m = /^(.*) must start with https:\/\/, mailto: or tel:$/.exec(raw);
    if (m) return (labels[m[1]] || m[1]) + ' باید با https:// یا mailto: یا tel: شروع شود';
    return raw;
  };
  const api = (data, opt) => {
    const o = $.extend({}, opt);
    const quiet = !!o.quiet;
    delete o.quiet;
    return $.ajax($.extend({
      url: 'api.php', method: 'POST', data: data, dataType: 'json', headers: { 'X-CSRF': cfg.csrf }
    }, o)).fail(x => { if (!quiet) toast(say(x), true); });
  };

  $('#login').on('submit', function (e) {
    e.preventDefault();
    api($(this).serialize() + '&action=login').done(() => location.reload());
  });
  if (!S) return;

  const langs = (cfg.langs && cfg.langs.length) ? cfg.langs : ['fa', 'en'];
  const T = fa ? {
    dash: 'پیشخوان', visits: 'بازدید', range: '۳۰ روز گذشته',
    today: 'امروز', week: 'هفته گذشته', month: 'ماه گذشته',
    menu: 'منو', close: 'بستن', clicks: 'آمار کلیک', noHits: 'هنوز لینکی نیست',
    gIntro: 'تنظیمات معرفی', gLang: 'تنظیمات زبان', gPwa: 'تنظیمات برنامه و PWA',
    gUpdate: 'آپدیت از گیت‌هاب', currentVer: 'نسخه فعلی', latestVer: 'آخرین نسخه',
    updateNow: 'بروزرسانی', updating: 'در حال بروزرسانی…', updated: 'بروزرسانی شد',
    reinstall: 'نصب مجدد نسخه فعلی', reinstalling: 'در حال نصب مجدد…', reinstalled: 'نسخه فعلی دوباره نصب شد',
    upToDate: 'به‌روز است', updateFail: 'نسخه گیت‌هاب خوانده نشد',
    keepData: 'تنظیمات پایگاه داده و پوشه آپلود عوض نمی‌شود',
    add: 'افزودن', save: 'ذخیره', cancel: 'انصراف', saved: 'ذخیره شد',
    edit: 'ویرایش', newItem: 'مورد جدید', empty: 'هنوز چیزی این‌جا نیست. با دکمه افزودن اولین مورد را بسازید.',
    visible: 'نمایان', hidden: 'مخفی', up: 'انتقال به بالا', down: 'انتقال به پایین',
    del: 'حذف', confirmDel: 'این مورد حذف شود؟', deleted: 'حذف شد', untitled: '(بدون عنوان)',
    changeImg: 'تغییر تصویر', chooseImg: 'انتخاب تصویر', uploading: 'در حال بارگذاری…',
    imgErr: 'این تصویر خوانده نشد. یک JPG یا PNG امتحان کنید.',
    keepLang: 'حداقل یک زبان باید روشن بماند', passTitle: 'رمز عبور', curPass: 'رمز فعلی',
    newPass: 'رمز جدید (حداقل ۱۰ نویسه)', changePass: 'تغییر رمز', passOk: 'رمز عوض شد',
    fail: 'درخواست انجام نشد'
  } : {
    dash: 'Dashboard', visits: 'Visits', range: 'Last 30 days',
    today: 'Today', week: 'Past week', month: 'Past month',
    menu: 'Menu', close: 'Close', clicks: 'Clicks', noHits: 'No links yet',
    gIntro: 'Profile', gLang: 'Language', gPwa: 'App and PWA',
    gUpdate: 'Update from GitHub', currentVer: 'Current', latestVer: 'Latest',
    updateNow: 'Update', updating: 'Updating…', updated: 'Updated',
    reinstall: 'Reinstall current version', reinstalling: 'Reinstalling…', reinstalled: 'Current version reinstalled',
    upToDate: 'Up to date', updateFail: 'Could not read the GitHub release',
    keepData: 'Database settings and the uploads folder are left untouched',
    add: 'Add new', save: 'Save', cancel: 'Cancel', saved: 'Saved',
    edit: 'Edit', newItem: 'New item', empty: 'Nothing here yet. Use Add new to create the first item.',
    visible: 'Visible', hidden: 'Hidden', up: 'Move up', down: 'Move down',
    del: 'Delete', confirmDel: 'Delete this item?', deleted: 'Deleted', untitled: '(untitled)',
    changeImg: 'Change image', chooseImg: 'Choose image', uploading: 'Uploading…',
    imgErr: 'This image could not be read. Try a JPG or PNG.',
    keepLang: 'Keep at least one language on', passTitle: 'Password', curPass: 'Current password',
    newPass: 'New password (10+ characters)', changePass: 'Change password', passOk: 'Password changed',
    fail: 'Request failed'
  };
  const labels = {
    'Settings': 'تنظیمات', 'Slider': 'اسلایدر', 'Links': 'لینک‌ها', 'Projects': 'پروژه‌ها',
    'Title (Persian)': 'عنوان (فارسی)', 'Title (English)': 'عنوان (انگلیسی)',
    'URL': 'نشانی', 'Order (small first)': 'ترتیب (عدد کوچک‌تر بالاتر)', 'Visible': 'نمایان',
    'Name (Persian)': 'نام (فارسی)', 'Name (English)': 'نام (انگلیسی)',
    'Bio (Persian)': 'معرفی (فارسی)', 'Bio (English)': 'معرفی (انگلیسی)',
    'Avatar': 'تصویر', 'Default language': 'زبان پیش‌فرض',
    'Persian enabled': 'فارسی روشن', 'English enabled': 'انگلیسی روشن',
    'Persian': 'فارسی', 'English': 'انگلیسی',
    'PWA name': 'نام برنامه', 'PWA short name': 'نام کوتاه برنامه',
    'PWA theme color': 'رنگ برنامه', 'PWA icon (square, 512px or larger)': 'آیکون برنامه (مربع، حداقل ۵۱۲)',
    'Image': 'تصویر', 'Icon': 'آیکون',
    'Description (Persian)': 'توضیح (فارسی)', 'Description (English)': 'توضیح (انگلیسی)'
  };
  const lab = s => (fa && labels[s]) ? labels[s] : s;
  const num = n => { try { return new Intl.NumberFormat(fa ? 'fa-IR' : 'en-US').format(n); } catch (e) { return String(n); } };
  const enMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const faMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
  function toJalali(gy, gm, gd) {
    const g = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    let jy = gy <= 1600 ? 0 : 979;
    gy -= gy <= 1600 ? 621 : 1600;
    const gy2 = gm > 2 ? gy + 1 : gy;
    let days = (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) - 80 + gd + g[gm - 1];
    jy += 33 * Math.floor(days / 12053);
    days %= 12053;
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    const jm = days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
    const jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
    return { m: jm, d: jd };
  }
  const dayLabel = iso => {
    const p = String(iso).slice(0, 10).split('-').map(Number);
    if (p.length !== 3 || !p[0]) return String(iso).slice(5);
    if (!fa) return enMonths[p[1] - 1] + ' ' + p[2];
    const j = toJalali(p[0], p[1], p[2]);
    return num(j.d) + ' ' + faMonths[j.m - 1];
  };
  const $main = $('#main');
  const mq = matchMedia('(max-width:820px)');
  let statTimer = 0;
  function stopStats() { if (statTimer) { clearInterval(statTimer); statTimer = 0; } }
  function setNav(open) {
    if (!mq.matches) open = false;
    $('body').toggleClass('nav-open', !!open);
    $('#menu').attr({ 'aria-expanded': open ? 'true' : 'false', 'aria-label': open ? T.close : T.menu });
  }
  $('#menu').attr('aria-label', T.menu).on('click', () => setNav(!$('body').hasClass('nav-open')));
  $(document).on('keydown', e => { if (e.key === 'Escape') setNav(false); });
  if (mq.addEventListener) mq.addEventListener('change', () => setNav(false));

  // Shrinks the picture in the browser so big phone photos never hit server upload limits.
  function shrink(file, max, png) {
    return new Promise((res, rej) => {
      const img = new Image(), url = URL.createObjectURL(file);
      img.onload = () => {
        const r = Math.min(1, max / Math.max(img.width, img.height)), c = document.createElement('canvas');
        c.width = Math.round(img.width * r); c.height = Math.round(img.height * r);
        const x = c.getContext('2d');
        if (!png) { x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height); }
        x.drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        c.toBlob(b => b ? res(b) : rej(), png ? 'image/png' : 'image/jpeg', 0.86);
      };
      img.onerror = () => rej();
      img.src = url;
    });
  }

  function uploader(k, text, val) {
    const $h = $('<input type="hidden">').attr('name', k).val(val || '');
    const $p = $('<img alt="">').attr('src', val ? '../uploads/' + val : '').toggle(!!val);
    const $t = $('<span>').text(val ? T.changeImg : T.chooseImg);
    const $f = $('<input type="file" accept="image/*">').on('change', async function () {
      const file = this.files[0];
      if (!file) return;
      $t.text(T.uploading);
      try {
        const blob = await shrink(file, { avatar: 600, icon: 1024 }[k] || 1600, k === 'icon');
        const fd = new FormData();
        fd.append('action', 'upload'); fd.append('field', k); fd.append('file', blob, k === 'icon' ? 'u.png' : 'u.jpg');
        const r = await api(fd, { processData: false, contentType: false });
        $h.val(r.file); $p.attr('src', '../uploads/' + r.file).show(); $t.text(T.changeImg);
      } catch (err) {
        $t.text(val || $h.val() ? T.changeImg : T.chooseImg);
        if (!err || !err.status) toast(T.imgErr, true);
      }
    });
    return $('<div class="f wide">').append($('<span>').text(text), $('<label class="up">').append($p, $t, $f), $h);
  }

  function field(k, def, val) {
    const type = def[1], opts = def[2];
    if (type === 'check') {
      return $('<label class="sw wide">').append(
        $('<input type="checkbox">').attr('name', k).prop('checked', val == null || +val === 1), $('<i>'), $('<span>').text(lab(def[0])));
    }
    if (type === 'image') return uploader(k, def[0], val);
    let $i;
    if (type === 'textarea') $i = $('<textarea rows="3">');
    else if (type === 'select') {
      let list = opts || [];
      if (k === 'default_lang') list = list.filter(o => langs.indexOf(o) >= 0);
      if (!list.length) list = opts || [];
      $i = $('<select>').append(list.map(o => {
        let text = String(o).replace(/^fa-\w+ fa-/, '');
        if (k === 'default_lang') text = lab(o === 'fa' ? 'Persian' : 'English');
        return $('<option>').val(o).text(text);
      }));
    }
    else $i = $('<input>').attr('type', type);
    $i.attr('name', k).val(val != null ? val : (type === 'color' ? '#6c5ce7' : type === 'number' ? 0 : ''));
    if (type === 'url' || type === 'number' || type === 'color') $i.attr({ dir: 'ltr', lang: 'en' });
    else if (/_fa$/.test(k)) $i.attr({ dir: 'rtl', lang: 'fa' });
    else if (/_en$/.test(k)) $i.attr({ dir: 'ltr', lang: 'en' });
    return $('<label class="f">').toggleClass('wide', type === 'textarea').append($('<span>').text(lab(def[0])), $i);
  }

  function form(ent, row, done, cancel) {
    const $g = $('<div class="grid">');
    $.each(S[ent].fields, (k, def) => {
      if (k === 'sort') return;
      const m = /_(fa|en)$/.exec(k);
      if (ent !== 'settings' && m && langs.indexOf(m[1]) < 0) {
        $g.append($('<input type="hidden">').attr('name', k).val(row[k] != null ? row[k] : ''));
        return;
      }
      $g.append(field(k, def, row[k]));
    });
    if (ent === 'settings') {
      $g.on('change', '[name=show_fa],[name=show_en]', function () {
        if (!$g.find('[name=show_fa]').prop('checked') && !$g.find('[name=show_en]').prop('checked')) {
          $(this).prop('checked', true);
          toast(T.keepLang, true);
        }
      });
    }
    const $b = $('<div class="btns">').append($('<button class="btn pri">').text(T.save));
    if (cancel) $b.append($('<button type="button" class="btn">').text(T.cancel).on('click', cancel));
    return $('<form class="glass pad">').append($g, $b).on('submit', function (e) {
      e.preventDefault();
      api($(this).serialize() + '&action=save&entity=' + ent + '&id=' + (row.id || 0)).done(() => { toast(T.saved); done(); });
    });
  }

  function settingsPage(row) {
    const groups = [
      ['intro', T.gIntro, ['name_fa', 'name_en', 'bio_fa', 'bio_en', 'avatar']],
      ['lang', T.gLang, ['default_lang', 'show_fa', 'show_en']],
      ['pwa', T.gPwa, ['app_name', 'short_name', 'theme_color', 'icon']]
    ];
    return groups.map(([id, title, keys]) => {
      const $g = $('<div class="grid">');
      keys.forEach(k => $g.append(field(k, S.settings.fields[k], row[k])));
      if (id === 'lang') {
        const sync = () => {
          const on = ['fa', 'en'].filter(l => $g.find('[name=show_' + l + ']').prop('checked'));
          if (!on.length) {
            $g.find('[name=show_fa]').prop('checked', true);
            on.push('fa');
            toast(T.keepLang, true);
          }
          const $sel = $g.find('[name=default_lang]');
          const cur = $sel.val();
          $sel.empty();
          on.forEach(o => $sel.append($('<option>').val(o).text(lab(o === 'fa' ? 'Persian' : 'English'))));
          $sel.val(on.indexOf(cur) >= 0 ? cur : on[0]);
        };
        $g.on('change', '[name=show_fa],[name=show_en]', sync);
      }
      return $('<form class="glass pad part">').append(
        $('<h2 class="pw-title">').text(title),
        $g,
        $('<div class="btns">').append($('<button class="btn pri">').text(T.save))
      ).on('submit', function (e) {
        e.preventDefault();
        api($(this).serialize() + '&action=save&entity=settings&part=' + id).done(() => {
          toast(T.saved);
          if (id === 'lang') location.reload();
        });
      });
    });
  }

  function updateCard() {
    const $note = $('<p class="muted">');
    const $btn = $('<button class="btn pri" type="button">').text(T.updateNow);
    const $again = $('<button class="btn" type="button">').text(T.reinstall);
    const paint = r => {
      $note.text(T.currentVer + ' ' + (r.current || '—') + ' · ' + T.latestVer + ' ' + (r.latest || '—'));
      $btn.text(r.update ? T.updateNow : T.upToDate);
    };
    const run = (op, $b, busy, doneText) => {
      $btn.add($again).prop('disabled', true);
      $b.text(busy);
      api({ action: 'update', op: op }).done(r => {
        paint(r);
        toast(r.updated ? doneText : T.upToDate);
        if (r.updated) setTimeout(() => location.reload(), 800);
      }).always(() => {
        $btn.add($again).prop('disabled', false);
        $again.text(T.reinstall);
      });
    };
    api({ action: 'update', op: 'check' }, { quiet: true }).done(paint).fail(() => $note.text(T.updateFail));
    $btn.on('click', () => run('apply', $btn, T.updating, T.updated));
    $again.on('click', () => run('reinstall', $again, T.reinstalling, T.reinstalled));
    return $('<form class="glass pad part">').on('submit', e => e.preventDefault()).append(
      $('<h2 class="pw-title">').text(T.gUpdate),
      $note,
      $('<p class="muted">').text(T.keepData),
      $('<div class="btns">').append($btn, $again)
    );
  }

  function password() {
    const f = (name, text, auto) => $('<label class="f">').append($('<span>').text(text),
      $('<input type="password" required>').attr({ name: name, autocomplete: auto }));
    return $('<form class="glass pad pw">').append(
      $('<h2 class="pw-title">').text(T.passTitle),
      f('cur', T.curPass, 'current-password'),
      f('new', T.newPass, 'new-password'),
      $('<button class="btn pri">').text(T.changePass)
    ).on('submit', function (e) {
      e.preventDefault();
      api($(this).serialize() + '&action=password').done(() => { toast(T.passOk); this.reset(); });
    });
  }

  const ib = (icon, title, fn, off) => $('<button type="button" class="btn ib">').attr({ title: title, 'aria-label': title })
    .prop('disabled', !!off).append($('<i>').addClass('fa-solid ' + icon)).on('click', fn);

  function curve(pts) {
    let path = 'M ' + pts[0].x.toFixed(1) + ' ' + pts[0].y.toFixed(1);
    for (let i = 0; i < pts.length - 1; i++) {
      const p0 = pts[Math.max(0, i - 1)], p1 = pts[i], p2 = pts[i + 1], p3 = pts[Math.min(pts.length - 1, i + 2)];
      path += ' C ' + [
        p1.x + (p2.x - p0.x) / 6, p1.y + (p2.y - p0.y) / 6,
        p2.x - (p3.x - p1.x) / 6, p2.y - (p3.y - p1.y) / 6,
        p2.x, p2.y
      ].map(v => v.toFixed(1)).join(' ');
    }
    return path;
  }

  function chartSvg(days) {
    const W = 320, H = 150, padX = 4, padY = 12;
    const max = Math.max(1, ...days.map(x => +x.n));
    const n = days.length;
    const pts = days.map((x, i) => ({
      x: padX + (n < 2 ? 0 : i * (W - padX * 2) / (n - 1)),
      y: padY + (1 - (+x.n / max)) * (H - padY * 2)
    }));
    const line = curve(pts);
    const area = line + ' L ' + pts[n - 1].x.toFixed(1) + ' ' + (H - 1) + ' L ' + pts[0].x.toFixed(1) + ' ' + (H - 1) + ' Z';
    const grid = [0.25, 0.5, 0.75].map(g => {
      const y = (padY + (1 - g) * (H - padY * 2)).toFixed(1);
      return '<line x1="' + padX + '" x2="' + (W - padX) + '" y1="' + y + '" y2="' + y + '"/>';
    }).join('');
    return '<svg viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" role="img">' +
      '<defs><linearGradient id="vg" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#6c5ce7"/><stop offset="1" stop-color="#00b4d8"/></linearGradient>' +
      '<linearGradient id="vf" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6c5ce7" stop-opacity=".34"/><stop offset="1" stop-color="#6c5ce7" stop-opacity="0"/></linearGradient></defs>' +
      '<g class="grid">' + grid + '</g><path class="area" d="' + area + '"/><path class="line" d="' + line + '"/></svg>';
  }

  function dashboard() {
    const $plot = $('<div class="plot">');
    const $range = $('<div class="chart-range">');
    const box = k => $('<div class="glass stat">').append($('<b>').attr('data-k', k).text('–'), $('<span>').text(T[k]));
    const $stats = $('<div class="stats">').append(box('today'), box('week'), box('month'));
    const $hits = $('<div class="hit-list">');
    const $hitSum = $('<span>');
    $main.append(
      $('<div class="glass chart">').append(
        $('<div class="chart-head">').append($('<b>').text(T.visits), $('<span>').text(T.range)),
        $plot, $range
      ),
      $stats,
      $('<div class="glass hits">').append($('<div class="chart-head">').append($('<b>').text(T.clicks), $hitSum), $hits)
    );
    const paint = r => {
      const days = r.days || [];
      if (days.length) {
        $plot.html(chartSvg(days));
        const mark = t => $('<span>').attr('dir', fa ? 'rtl' : 'ltr').text(t);
        $range.empty().append(mark(dayLabel(days[0].d)), mark(dayLabel(days[days.length - 1].d)));
      }
      ['today', 'week', 'month'].forEach(k => $stats.find('[data-k="' + k + '"]').text(num(+r[k] || 0)));
      const rows = r.clicks || [];
      const total = rows.reduce((s, row) => s + (+row.n || 0), 0);
      $hitSum.text(rows.length ? num(total) : '');
      const names = rows.map(row => row.t || T.untitled);
      const $have = $hits.children('.hit');
      const same = $have.length === rows.length && names.every((t, i) => $have.eq(i).find('.hit-name').text() === t);
      if (!rows.length) { $hits.empty().append($('<p class="muted hit-empty">').text(T.noHits)); return; }
      if (!same) {
        $hits.empty();
        rows.forEach(() => $hits.append($('<div class="hit">').append(
          $('<div class="hit-top">').append($('<span class="hit-name">'), $('<b class="hit-n">')),
          $('<div class="hit-bar">').append($('<i>'))
        )));
      }
      const max = Math.max(1, ...rows.map(row => +row.n || 0));
      rows.forEach((row, i) => {
        const n = +row.n || 0;
        const $h = $hits.children('.hit').eq(i);
        $h.find('.hit-name').text(names[i]);
        $h.find('.hit-n').text(num(n));
        $h.find('.hit-bar i').css('width', Math.round(n / max * 100) + '%');
      });
    };
    const pull = () => api({ action: 'stats', lang: fa ? 'fa' : 'en' }, { quiet: true }).done(paint);
    pull();
    statTimer = setInterval(pull, 3000);
  }

  function show(ent) {
    stopStats();
    $('#nav button').removeClass('on').filter((_, b) => $(b).data('k') === ent).addClass('on');
    $('#actions').empty(); $main.empty();
    if (ent === '_dash') { $('#title').text(T.dash); return dashboard(); }
    $('#title').text(lab(S[ent].label));
    api({ action: 'list', entity: ent }).done(r => {
      if (ent === 'settings') {
        settingsPage(r.rows).forEach($f => $main.append($f));
        $main.append(updateCard(), password());
        return;
      }
      const edit = row => {
        $('#actions').empty(); $('#title').text(row.id ? T.edit : T.newItem);
        $main.empty().append(form(ent, row, () => show(ent), () => show(ent)));
      };
      $('#actions').append($('<button class="btn pri" type="button">').append($('<i class="fa-solid fa-plus">'), $('<span>').text(T.add)).on('click', () => { setNav(false); edit({}); }));
      if (!r.rows.length) return $main.append($('<div class="glass empty">').append($('<p class="muted">').text(T.empty)));
      const move = (row, dir) => api({ action: 'move', entity: ent, id: row.id, dir: dir }).done(() => show(ent));
      r.rows.forEach((row, n) => {
        const $th = row.image ? $('<img class="th" alt="">').attr('src', '../uploads/' + row.image)
          : $('<div class="th">').append($('<i>').addClass(row.icon || 'fa-solid fa-image'));
        const $acts = $('<div class="acts">').append(
          $('<span class="badge">').toggleClass('off', !+row.active).text(+row.active ? T.visible : T.hidden),
          ib('fa-arrow-up', T.up, () => move(row, 'up'), n === 0),
          ib('fa-arrow-down', T.down, () => move(row, 'down'), n === r.rows.length - 1),
          ib('fa-pen', T.edit, () => edit(row)),
          ib('fa-trash del', T.del, () => { if (confirm(T.confirmDel)) api({ action: 'delete', entity: ent, id: row.id }).done(() => { toast(T.deleted); show(ent); }); })
        );
        $main.append($('<div class="row glass">').append(
          $('<div class="meta">').append($('<b>').text(row.title_en || row.title_fa || T.untitled), $('<small>').text(row.title_fa && row.title_en ? row.title_fa : (row.url || ''))),
          $th,
          $acts
        ));
      });
    });
  }

  $('<button class="btn" type="button">').append($('<i class="fa-solid fa-chart-line">'), $('<span>').text(T.dash)).data('k', '_dash').appendTo('#nav');
  Object.keys(S).filter(k => k !== 'settings').concat(S.settings ? ['settings'] : []).forEach(k => {
    const v = S[k];
    $('<button class="btn" type="button">').append($('<i>').addClass('fa-solid ' + v.icon), $('<span>').text(lab(v.label))).data('k', k).appendTo('#nav');
  });
  $('#nav').on('click', 'button', function () { setNav(false); show($(this).data('k')); });
  $('#out').on('click', () => api({ action: 'logout' }).done(() => location.reload()));
  show('_dash');
});
