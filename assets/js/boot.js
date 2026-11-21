(function () {
  var d = document.documentElement, t = 'light';
  try {
    t = localStorage.getItem('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  } catch (e) {}
  d.dataset.theme = t;
  d.style.backgroundColor = t === 'dark' ? '#0d0b1e' : '#f3f0ff';
})();
