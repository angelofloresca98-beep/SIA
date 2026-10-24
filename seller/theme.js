/* FarmMarket theme interactions
   Load at the end of <body>:  <script src="theme.js"></script> */
(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(pointer: fine)').matches;

  // Buttons that get the full interactive treatment
  var actionSel = '.btn,.btn-sm,.btn-xs,.btn-primary,.btn-outline,.btn-order,.order-btn,.btn-ghost,.btn-cancel,.cancel-btn,.delete-btn,button[type=submit],#cb-send';
  // Everything that gets the mouse-following glow
  var glowSel = actionSel + ',.tab-btn,.navbar a';

  // 1. Mouse-following glow inside buttons / nav links / tabs
  document.addEventListener('mousemove', function (e) {
    var el = e.target.closest(glowSel);
    if (!el) return;
    var r = el.getBoundingClientRect();
    el.style.setProperty('--bx', (e.clientX - r.left) + 'px');
    el.style.setProperty('--by', (e.clientY - r.top) + 'px');
  }, { passive: true });

  // 2. Magnetic pull: action buttons lean toward the cursor, then spring back
  if (!reduce && finePointer) {
    document.addEventListener('mousemove', function (e) {
      var btn = e.target.closest(actionSel);
      if (!btn || btn.disabled) return;
      var r = btn.getBoundingClientRect();
      var dx = (e.clientX - (r.left + r.width / 2)) / r.width;
      var dy = (e.clientY - (r.top + r.height / 2)) / r.height;
      btn.style.setProperty('--tx', (dx * 8).toFixed(1) + 'px');
      btn.style.setProperty('--ty', (dy * 6).toFixed(1) + 'px');
    }, { passive: true });

    document.addEventListener('mouseout', function (e) {
      var btn = e.target.closest(actionSel);
      if (!btn || btn.contains(e.relatedTarget)) return;
      btn.style.setProperty('--tx', '0px');
      btn.style.setProperty('--ty', '0px');
    });
  }

  // 3. Click ripple
  var rippleSel = glowSel + ',#cb-btn';
  document.addEventListener('click', function (e) {
    var btn = e.target.closest(rippleSel);
    if (!btn || reduce) return;
    var r = btn.getBoundingClientRect();
    var size = Math.max(r.width, r.height);
    var s = document.createElement('span');
    s.className = 'fm-ripple';
    s.style.width = s.style.height = size + 'px';
    s.style.left = (e.clientX - r.left - size / 2) + 'px';
    s.style.top = (e.clientY - r.top - size / 2) + 'px';
    if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';
    btn.style.overflow = 'hidden';
    btn.appendChild(s);
    setTimeout(function () { s.remove(); }, 650);
  });

  // 4. Scroll reveal with stagger (classes removed afterwards so hover stays snappy)
  var targets = document.querySelectorAll('.product-card,.post-card,.stat-card,.stat,.section,.form-card,.filters,.table-card,.order-form,.product-info,.no-post,.request-card');
  if (!reduce && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target;
        el.classList.add('in');
        io.unobserve(el);
        var wait = 650 + (parseInt(el.style.transitionDelay, 10) || 0);
        setTimeout(function () {
          el.classList.remove('fm-reveal', 'in');
          el.style.transitionDelay = '';
        }, wait);
      });
    }, { threshold: 0.08 });
    targets.forEach(function (el, i) {
      el.classList.add('fm-reveal');
      el.style.transitionDelay = (i % 6) * 70 + 'ms';
      io.observe(el);
    });
  }

  // 5. Count-up for dashboard numbers
  if (!reduce) {
    document.querySelectorAll('.stat-number,.stat-n,.stat .value').forEach(function (el) {
      var raw = el.textContent.trim();
      var m = raw.match(/^([^\d]*)([\d,]+(?:\.\d+)?)(.*)$/);
      if (!m) return;
      var prefix = m[1], numStr = m[2], suffix = m[3];
      var target = parseFloat(numStr.replace(/,/g, ''));
      var decimals = (numStr.split('.')[1] || '').length;
      var start = null, dur = 1000;
      function step(t) {
        if (!start) start = t;
        var p = Math.min((t - start) / dur, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = prefix + (target * eased).toLocaleString('en-PH', {
          minimumFractionDigits: decimals, maximumFractionDigits: decimals
        }) + suffix;
        if (p < 1) requestAnimationFrame(step);
        else el.textContent = raw;
      }
      requestAnimationFrame(step);
    });
  }
})();