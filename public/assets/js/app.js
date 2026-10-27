// sidebar toggle: collapse on desktop, slide in/out on mobile
(function () {
  var body = document.body;
  var isMobile = function () { return window.innerWidth < 992; };

  // remember collapsed state on desktop
  try {
    if (localStorage.getItem('sbCollapsed') === '1') body.classList.add('sb-collapsed');
  } catch (e) {}

  document.getElementById('sbToggleBtn').addEventListener('click', function () {
    if (isMobile()) {
      body.classList.toggle('sb-open');
      return;
    }
    body.classList.toggle('sb-collapsed');
    try {
      localStorage.setItem('sbCollapsed', body.classList.contains('sb-collapsed') ? '1' : '0');
    } catch (e) {}
  });

  document.getElementById('sbOverlay').addEventListener('click', function () {
    body.classList.remove('sb-open');
  });
})();
