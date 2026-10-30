
/* FarmMarket theme interactions
   Load at the end of <body>:  <script src="theme.js"></script> */
(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(pointer: fine)').matches;

  // Buttons that get the full interactive treatment
  var actionSel = '.btn,.btn-sm,.btn-xs,.btn-primary,.btn-outline,.btn-order,.order-btn,.btn-ghost,.btn-cancel,.cancel-btn,.delete-btn,.update-box summary,button[type=submit],#cb-send';
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
/* ==========================================================
   Dark mode toggle
   - Adds a sun/moon switch to the navbar of every page
   - Saves the choice in localStorage ("fm-theme")
   - First visit follows the device setting (prefers-color-scheme)
   - Stays in sync across open tabs
   ========================================================== */
(function () {
  'use strict';
  var KEY = 'fm-theme';
  var root = document.documentElement;
  var mq = window.matchMedia('(prefers-color-scheme: dark)');

  function saved() { try { return localStorage.getItem(KEY); } catch (e) { return null; } }
  function save(v) { try { localStorage.setItem(KEY, v); } catch (e) { /* private mode: ignore */ } }
  function current() { return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'; }

  var btn;
  function paint(theme) {
    var dark = theme === 'dark';
    if (btn) {
      btn.setAttribute('aria-checked', dark ? 'true' : 'false');
      btn.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
      btn.title = dark ? 'Switch to light mode' : 'Switch to dark mode';
    }
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', dark ? '#06140c' : '#0a3a1d');
  }

  function apply(theme, animate) {
    if (animate) {
      root.classList.add('fm-theme-anim');
      setTimeout(function () { root.classList.remove('fm-theme-anim'); }, 450);
    }
    if (theme === 'dark') root.setAttribute('data-theme', 'dark');
    else root.setAttribute('data-theme', 'light');
    paint(theme);
  }

  // initial theme: saved choice, otherwise the device setting
  var start = saved() || (mq.matches ? 'dark' : 'light');
  apply(start, false);

  // build the switch
  btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'fm-theme-toggle';
  btn.setAttribute('role', 'switch');
  btn.innerHTML =
    '<svg class="fm-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">' +
      '<circle cx="12" cy="12" r="4.2" fill="currentColor"/>' +
      '<g class="fm-rays"><path d="M12 2.5v2.2M12 19.3v2.2M2.5 12h2.2M19.3 12h2.2M5.3 5.3l1.6 1.6M17.1 17.1l1.6 1.6M18.7 5.3l-1.6 1.6M6.9 17.1l-1.6 1.6"/></g>' +
    '</svg>' +
    '<svg class="fm-moon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.5 14.6A8.5 8.5 0 0 1 9.4 3.5a.7.7 0 0 0-.9-.9A10 10 0 1 0 21.4 15.5a.7.7 0 0 0-.9-.9z"/></svg>';

  // place it inside the navbar (right side, next to the hamburger on small screens)
  var navbar = document.querySelector('.navbar');
  if (navbar) navbar.appendChild(btn);
  else { btn.classList.add('fm-floating'); document.body.appendChild(btn); }
  paint(current());

  btn.addEventListener('click', function () {
    var next = current() === 'dark' ? 'light' : 'dark';
    save(next);
    apply(next, true);
    // restart the click animation
    btn.classList.remove('fm-tt-pop');
    void btn.offsetWidth;
    btn.classList.add('fm-tt-pop');
    setTimeout(function () { btn.classList.remove('fm-tt-pop'); }, 650);
  });

  // follow the device setting only until the user picks a theme themselves
  var onSystem = function (e) { if (!saved()) apply(e.matches ? 'dark' : 'light', true); };
  if (mq.addEventListener) mq.addEventListener('change', onSystem);
  else if (mq.addListener) mq.addListener(onSystem);

  // keep other open tabs in sync
  window.addEventListener('storage', function (e) {
    if (e.key === KEY && e.newValue) apply(e.newValue === 'dark' ? 'dark' : 'light', true);
  });
})();
/* ==========================================================
   Photo zoom viewer
   - Click any product photo to open it full-screen
   - Zoom: mouse wheel, + / - buttons, double-click / double-tap, pinch on phones
   - Pan: drag the photo when zoomed in
   - Close: X button, Esc key, or click outside the photo
   Works on <img> tags and on elements that use a CSS background-image
   ========================================================== */
(function () {
  'use strict';

  var SEL = '.product-img,.product-card img,.post-card img,.request-card img,.product-info img,img.zoomable,.zoomable';
  var MIN = 1, MAX = 6;

  var overlay, stage, img, label, lastFocus;
  var s = 1, tx = 0, ty = 0;
  var pointers = {}, pinch = null, drag = null, moved = false;

  function srcOf(el) {
    if (el.tagName === 'IMG') return el.currentSrc || el.src || '';
    var bg = getComputedStyle(el).backgroundImage || '';
    var m = bg.match(/url\(["']?(.*?)["']?\)/);
    return m ? m[1] : '';
  }
  function altOf(el) {
    return el.getAttribute('alt') || el.getAttribute('aria-label') || el.getAttribute('title') || 'Product photo';
  }

  function build() {
    overlay = document.createElement('div');
    overlay.className = 'fm-lb';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Photo viewer');
    overlay.innerHTML =
      '<div class="fm-lb-stage"><img class="fm-lb-img" alt="" draggable="false"></div>' +
      '<button type="button" class="fm-lb-close" aria-label="Close photo">&times;</button>' +
      '<div class="fm-lb-bar">' +
        '<button type="button" data-z="out" aria-label="Zoom out">&minus;</button>' +
        '<span class="fm-lb-zoom">100%</span>' +
        '<button type="button" data-z="in" aria-label="Zoom in">+</button>' +
        '<button type="button" data-z="reset" class="fm-lb-reset" aria-label="Reset zoom">Reset</button>' +
      '</div>' +
      '<div class="fm-lb-hint">Scroll or pinch to zoom &middot; drag to move &middot; double-click to toggle</div>';
    document.body.appendChild(overlay);

    stage = overlay.querySelector('.fm-lb-stage');
    img = overlay.querySelector('.fm-lb-img');
    label = overlay.querySelector('.fm-lb-zoom');

    overlay.querySelector('.fm-lb-close').addEventListener('click', close);

    overlay.querySelector('.fm-lb-bar').addEventListener('click', function (e) {
      var b = e.target.closest('button');
      if (!b) return;
      var r = stage.getBoundingClientRect();
      var cx = r.left + r.width / 2, cy = r.top + r.height / 2;
      overlay.classList.add('fm-lb-smooth');
      if (b.dataset.z === 'in') zoomAt(s * 1.5, cx, cy);
      else if (b.dataset.z === 'out') zoomAt(s / 1.5, cx, cy);
      else { s = 1; tx = 0; ty = 0; apply(); }
    });

    stage.addEventListener('wheel', function (e) {
      e.preventDefault();
      overlay.classList.remove('fm-lb-smooth');
      zoomAt(s * Math.exp(-e.deltaY * 0.0018), e.clientX, e.clientY);
    }, { passive: false });

    img.addEventListener('dblclick', function (e) {
      e.preventDefault();
      overlay.classList.add('fm-lb-smooth');
      if (s > 1.05) { s = 1; tx = 0; ty = 0; apply(); }
      else zoomAt(2.5, e.clientX, e.clientY);
    });

    stage.addEventListener('pointerdown', onDown);
    stage.addEventListener('pointermove', onMove);
    stage.addEventListener('pointerup', onUp);
    stage.addEventListener('pointercancel', onUp);
  }

  function clamp() {
    if (s <= 1) { tx = 0; ty = 0; return; }
    var r = stage.getBoundingClientRect();
    var w = img.offsetWidth * s, h = img.offsetHeight * s;
    var mx = Math.max(0, (w - r.width) / 2) + 60;
    var my = Math.max(0, (h - r.height) / 2) + 60;
    tx = Math.min(mx, Math.max(-mx, tx));
    ty = Math.min(my, Math.max(-my, ty));
  }

  function apply() {
    clamp();
    img.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + s + ')';
    label.textContent = Math.round(s * 100) + '%';
    overlay.classList.toggle('fm-lb-zoomed', s > 1.01);
  }

  function zoomAt(ns, cx, cy) {
    ns = Math.min(MAX, Math.max(MIN, ns));
    var r = stage.getBoundingClientRect();
    var px = cx - (r.left + r.width / 2);
    var py = cy - (r.top + r.height / 2);
    tx = px - (px - tx) * (ns / s);
    ty = py - (py - ty) * (ns / s);
    s = ns;
    apply();
  }

  function dist(a, b) { return Math.hypot(a.x - b.x, a.y - b.y); }

  function onDown(e) {
    overlay.classList.remove('fm-lb-smooth');
    stage.setPointerCapture(e.pointerId);
    pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
    moved = false;
    var ids = Object.keys(pointers);
    if (ids.length === 2) {
      pinch = { d: dist(pointers[ids[0]], pointers[ids[1]]), s: s };
      drag = null;
    } else if (ids.length === 1) {
      drag = { x: e.clientX, y: e.clientY, tx: tx, ty: ty };
    }
    overlay.classList.add('fm-lb-grabbing');
  }

  function onMove(e) {
    if (!pointers[e.pointerId]) return;
    pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
    var ids = Object.keys(pointers);
    if (ids.length === 2 && pinch) {
      var a = pointers[ids[0]], b = pointers[ids[1]];
      zoomAt(pinch.s * dist(a, b) / pinch.d, (a.x + b.x) / 2, (a.y + b.y) / 2);
      moved = true;
    } else if (drag && s > 1) {
      var dx = e.clientX - drag.x, dy = e.clientY - drag.y;
      if (Math.abs(dx) + Math.abs(dy) > 3) moved = true;
      tx = drag.tx + dx;
      ty = drag.ty + dy;
      apply();
    }
  }

  function onUp(e) {
    var wasDrag = moved;
    delete pointers[e.pointerId];
    pinch = null;
    var ids = Object.keys(pointers);
    if (ids.length === 1) drag = { x: pointers[ids[0]].x, y: pointers[ids[0]].y, tx: tx, ty: ty };
    else drag = null;
    if (!ids.length) {
      overlay.classList.remove('fm-lb-grabbing');
      // click on the dark background (not on the photo) closes the viewer
      if (!wasDrag && e.target === stage) close();
    }
  }

  function onKey(e) {
    if (!overlay || !overlay.classList.contains('fm-lb-open')) return;
    var r = stage.getBoundingClientRect();
    var cx = r.left + r.width / 2, cy = r.top + r.height / 2;
    if (e.key === 'Escape') { e.preventDefault(); close(); }
    else if (e.key === '+' || e.key === '=') { overlay.classList.add('fm-lb-smooth'); zoomAt(s * 1.5, cx, cy); }
    else if (e.key === '-' || e.key === '_') { overlay.classList.add('fm-lb-smooth'); zoomAt(s / 1.5, cx, cy); }
    else if (e.key === '0') { overlay.classList.add('fm-lb-smooth'); s = 1; tx = 0; ty = 0; apply(); }
  }

  function open(src, alt) {
    if (!overlay) build();
    lastFocus = document.activeElement;
    s = 1; tx = 0; ty = 0;
    img.alt = alt || '';
    img.style.transform = '';
    img.src = src;
    apply();
    overlay.classList.add('fm-lb-open');
    document.documentElement.classList.add('fm-lb-lock');
    overlay.querySelector('.fm-lb-close').focus({ preventScroll: true });
  }

  function close() {
    if (!overlay) return;
    overlay.classList.remove('fm-lb-open', 'fm-lb-zoomed', 'fm-lb-grabbing');
    document.documentElement.classList.remove('fm-lb-lock');
    pointers = {}; pinch = null; drag = null;
    if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
  }

  document.addEventListener('keydown', onKey);

  // open on click (works for images added later too)
  document.addEventListener('click', function (e) {
    var el = e.target.closest(SEL);
    if (!el || (overlay && overlay.contains(el))) return;
    var src = srcOf(el);
    if (!src) return;
    e.preventDefault();
    e.stopPropagation();
    open(src, altOf(el));
  }, true);

  // make thumbnails keyboard friendly + show the zoom cursor
  function mark() {
    document.querySelectorAll(SEL).forEach(function (el) {
      if (el.dataset.fmZoom) return;
      if (!srcOf(el)) return;
      el.dataset.fmZoom = '1';
      el.classList.add('fm-zoomable');
      el.setAttribute('title', el.getAttribute('title') || 'Click to zoom');
      if (el.tagName !== 'IMG') { el.setAttribute('role', 'button'); el.setAttribute('tabindex', '0'); }
      el.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); open(srcOf(el), altOf(el)); }
      });
    });
  }
  mark();
  if ('MutationObserver' in window) new MutationObserver(mark).observe(document.body, { childList: true, subtree: true });
})();