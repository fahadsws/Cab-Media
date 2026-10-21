/* Cabmedia 2026 — interactions. Vanilla JS, no dependencies, no network calls except the contact form. */
(function () {
  'use strict';
  var d = document, root = d.documentElement;
  var $ = function (s, c) { return (c || d).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); };
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var clamp = function (v, a, b) { return Math.min(b, Math.max(a, v)); };
  root.classList.remove('nojs');

  /* ---------- split headings into words ---------- */
  $$('.split').forEach(function (el) {
    var i = 0;
    (function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (n) {
        if (n.nodeType === 3) {
          var frag = d.createDocumentFragment();
          n.textContent.split(/(\s+)/).forEach(function (part) {
            if (!part) return;
            if (/^\s+$/.test(part)) { frag.appendChild(d.createTextNode(' ')); return; }
            var w = d.createElement('span'); w.className = 'w';
            var s = d.createElement('span'); s.style.setProperty('--i', i++); s.textContent = part;
            w.appendChild(s); frag.appendChild(w);
          });
          node.replaceChild(frag, n);
        } else if (n.nodeType === 1) walk(n);
      });
    })(el);
  });

  /* ---------- reveal on scroll ---------- */
  var targets = $$('[data-reveal],.split');
  if ('IntersectionObserver' in window && !reduce) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    targets.forEach(function (t) { io.observe(t); });
  } else targets.forEach(function (t) { t.classList.add('in'); });

  /* ---------- nav, progress, back to top ---------- */
  var nav = $('.nav'), bar = $('.progress'), totop = $('.totop');
  var burger = $('.burger'), menu = $('.menu');
  var ticking = false;
  function onScroll() {
    if (ticking) return; ticking = true;
    requestAnimationFrame(function () {
      var y = window.scrollY, h = root.scrollHeight - window.innerHeight;
      if (nav) nav.classList.toggle('scrolled', y > 24);
      if (bar) bar.style.transform = 'scaleX(' + (h > 0 ? y / h : 0) + ')';
      if (totop) totop.classList.toggle('show', y > 900);
      parallax(); fillText(); timeline();
      ticking = false;
    });
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  if (totop) totop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); });
  function setMenu(open) {
    if (!burger || !menu) return;
    burger.setAttribute('aria-expanded', String(open));
    burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    menu.classList.toggle('open', open);
    menu.setAttribute('aria-hidden', String(!open));
    d.body.style.overflow = open ? 'hidden' : '';
  }
  if (burger) {
    burger.addEventListener('click', function () { setMenu(burger.getAttribute('aria-expanded') !== 'true'); });
    d.addEventListener('keydown', function (e) { if (e.key === 'Escape') setMenu(false); });
    $$('a', menu).forEach(function (a) { a.addEventListener('click', function () { setMenu(false); }); });
  }

  /* ---------- tile spotlight + magnetic buttons ---------- */
  if (fine) {
    $$('.tile').forEach(function (t) {
      t.addEventListener('pointermove', function (e) {
        var r = t.getBoundingClientRect();
        t.style.setProperty('--mx', (e.clientX - r.left) + 'px'); t.style.setProperty('--my', (e.clientY - r.top) + 'px');
      });
    });
    if (!reduce) $$('[data-magnetic]').forEach(function (b) {
      b.addEventListener('pointermove', function (e) {
        var r = b.getBoundingClientRect();
        b.style.transform = 'translate(' + ((e.clientX - r.left - r.width / 2) * 0.22) + 'px,' + ((e.clientY - r.top - r.height / 2) * 0.3) + 'px)';
      });
      b.addEventListener('pointerleave', function () { b.style.transform = ''; });
    });
  }

  /* ---------- marquee: duplicate content for a seamless loop ---------- */
  $$('.marq-track').forEach(function (tr) {
    Array.prototype.slice.call(tr.children).forEach(function (c) { var k = c.cloneNode(true); k.setAttribute('aria-hidden', 'true'); tr.appendChild(k); });
  });

  /* ---------- rotating word ---------- */
  var word = $('[data-words]');
  if (word && !reduce) {
    var list = word.getAttribute('data-words').split('|'), wi = 0;
    setInterval(function () {
      word.classList.add('out');
      setTimeout(function () { wi = (wi + 1) % list.length; while (word.firstChild) word.removeChild(word.firstChild); word.appendChild(d.createTextNode(list[wi])); word.classList.remove('out'); }, 350);
    }, 2800);
  }

  /* ---------- word-by-word fill on scroll ---------- */
  var fills = $$('.fill');
  fills.forEach(function (el) {
    var out = [];
    el.textContent.trim().split(/\s+/).forEach(function (w) { out.push('<span class="fw">' + w.replace(/</g, '&lt;') + '</span>'); });
    el.setAttribute('aria-label', el.textContent.trim());
    el.innerHTML = out.join(' ');
  });
  function fillText() {
    fills.forEach(function (el) {
      var r = el.getBoundingClientRect(), vh = window.innerHeight;
      var p = clamp((vh * 0.85 - r.top) / (vh * 0.55 + r.height * 0.4), 0, 1);
      var words = el.children, n = words.length, k = Math.round(p * n);
      for (var i = 0; i < n; i++) words[i].classList.toggle('on', i < k);
    });
  }

  /* ---------- parallax photo ---------- */
  var pars = $$('.photo img');
  function parallax() {
    if (reduce) return;
    pars.forEach(function (img) {
      var r = img.parentNode.getBoundingClientRect(), vh = window.innerHeight;
      if (r.bottom < 0 || r.top > vh) return;
      var p = (r.top + r.height / 2 - vh / 2) / vh;
      img.style.transform = 'translate3d(0,' + (p * -9) + '%,0)';
    });
  }

  /* ---------- timeline progress ---------- */
  var tls = $$('.timeline');
  function timeline() {
    tls.forEach(function (t) {
      var r = t.getBoundingClientRect(), vh = window.innerHeight;
      t.style.setProperty('--p', clamp((vh * 0.75 - r.top) / (r.height + vh * 0.2), 0, 1));
    });
  }

  /* ---------- counters ---------- */
  var counts = $$('[data-count]');
  if (counts.length && 'IntersectionObserver' in window) {
    var cio = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (!e.isIntersecting) return; cio.unobserve(e.target);
        var el = e.target, to = parseFloat(el.getAttribute('data-count')), t0 = performance.now(), dur = reduce ? 1 : 1600;
        (function tick(now) {
          var p = clamp((now - t0) / dur, 0, 1), v = 1 - Math.pow(1 - p, 4);
          el.textContent = Math.round(to * v);
          if (p < 1) requestAnimationFrame(tick);
        })(t0);
      });
    }, { threshold: 0.6 });
    counts.forEach(function (c) { cio.observe(c); });
  }

  /* ---------- method: sticky steps ---------- */
  var steps = $$('.step'), bigs = $$('.word-big span');
  if (steps.length && 'IntersectionObserver' in window) {
    var sio = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (!e.isIntersecting) return;
        steps.forEach(function (s, i) { s.classList.toggle('on', s === e.target); if (s === e.target) bigs.forEach(function (b, j) { b.classList.toggle('on', i === j); }); });
      });
    }, { threshold: 0, rootMargin: '-45% 0px -45% 0px' });
    steps.forEach(function (s) { sio.observe(s); });
    if (steps[0]) { steps[0].classList.add('on'); if (bigs[0]) bigs[0].classList.add('on'); }
  }

  /* ---------- cases: filter + expand ---------- */
  var fbar = $('.filters');
  if (fbar) {
    $$('button', fbar).forEach(function (b) {
      b.addEventListener('click', function () {
        $$('button', fbar).forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
        var f = b.getAttribute('data-filter');
        $$('.case').forEach(function (c) { c.hidden = !(f === 'all' || c.getAttribute('data-family') === f); });
      });
    });
  }
  $$('.case-btn').forEach(function (b) {
    b.addEventListener('click', function () {
      var c = b.closest('.case'), open = !c.classList.contains('open');
      c.classList.toggle('open', open); b.setAttribute('aria-expanded', String(open));
      b.firstElementChild.textContent = open ? 'Ver menos' : 'Qué implanto y qué cambia';
    });
  });

  /* ---------- legal: table of contents ---------- */
  var toc = $('.toc');
  if (toc && 'IntersectionObserver' in window) {
    var links = $$('a', toc), secs = links.map(function (a) { return $(a.getAttribute('href')); });
    var tio = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) links.forEach(function (a, i) { a.classList.toggle('on', secs[i] === e.target); }); });
    }, { rootMargin: '-20% 0px -70% 0px' });
    secs.forEach(function (s) { if (s) tio.observe(s); });
  }

  /* ---------- contact form ---------- */
  var form = $('#contact-form');
  if (form) {
    var started = Date.now(), sending = false;
    var btn = $('button[type="submit"]', form), label = btn.firstElementChild.textContent;
    var errBox = $('.form-err', form);
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (sending) return;
      var data = {}; new FormData(form).forEach(function (v, k) { data[k] = v; });
      var holder = form.parentNode;
      var thanks = function () {
        holder.innerHTML = '<div class="thanks" role="status"><div class="tick"><svg viewBox="0 0 24 24"><path d="M5 12.5 10 17.5 19 7"/></svg></div><h3>Thank you.</h3><p>We have received your enquiry and will get back to you shortly.</p></div>';
        holder.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
      };
      if ((data.web || '').trim() !== '' || Date.now() - started < 3000) { thanks(); return; }
      delete data.web;
      data.aceptaPrivacidad = data.aceptaPrivacidad === 'on' ? 'sí' : 'no';
      data.fechaAceptacion = new Date().toISOString();
      sending = true; btn.disabled = true; btn.firstElementChild.textContent = 'Sending...'; errBox.hidden = true;
      fetch(window.SITE.contactUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(data) })
        .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); thanks(); })
        .catch(function () {
          var txt = encodeURIComponent(['Name: ' + data.nombre, data.empresa && 'Company: ' + data.empresa, 'Email: ' + data.email, data.telefono && 'Phone: ' + data.telefono, '', data.mejora].filter(Boolean).join('\n'));
          errBox.innerHTML = 'Sorry, we could not send your enquiry. Please email <a href="mailto:info@cabmedia.in">info@cabmedia.in</a>, call +91 9820446872 or reach us on <a href="https://wa.me/' + window.SITE.whatsapp + '?text=' + txt + '" target="_blank" rel="noopener">WhatsApp</a>.';
          errBox.hidden = false; sending = false; btn.disabled = false; btn.firstElementChild.textContent = label;
        });
    });
  }

  /* ---------- hero orchestration canvas: from chaos to order ---------- */
  var flow = $('#flow');
  if (flow) {
    var W = 600, H = 470, NS = 'http://www.w3.org/2000/svg';
    var ICON = {
      brief: '<path d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
      brand: '<path d="M3 12V4h8l10 10-8 8L3 12Z"/><circle cx="7.5" cy="8.5" r="1.3"/>',
      budget: '<circle cx="12" cy="12" r="9"/><path d="M14.5 9a2.5 2 0 0 0-2.5-1.5c-1.5 0-2.5.8-2.5 2s1 1.7 2.5 2 2.5.8 2.5 2-1 2-2.5 2A2.7 2 0 0 1 9.5 15M12 6v1.5M12 16.5V18"/>',
      creative: '<path d="M4 20l1-4L16.5 4.5a2.1 2.1 0 0 1 3 3L8 19l-4 1Z"/><path d="m14 7 3 3"/>',
      media: '<path d="M3 11v2a1 1 0 0 0 1 1h2l8 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M18 9a4 4 0 0 1 0 6M7 14l1 6h2l-1-5"/>',
      digital: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
      hub: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2"/>'
    };
    var LAYOUT = { wide: { W: 600, H: 470 }, tall: { W: 400, H: 540 } };
    var N = {
      brief:    { l: 'Your brief',  i: 'brief',    w: { c: [130, 78],  o: [124, 96]  }, t: { c: [96, 74],   o: [64, 70]   } },
      brand:    { l: 'Your brand',  i: 'brand',    w: { c: [480, 66],  o: [124, 235] }, t: { c: [304, 62],  o: [200, 70]  } },
      budget:   { l: 'Your budget', i: 'budget',   w: { c: [300, 414], o: [124, 374] }, t: { c: [206, 470], o: [336, 70]  } },
      creative: { l: 'Creative',    i: 'creative', w: { c: [112, 292], o: [462, 108] }, t: { c: [92, 330],  o: [64, 470]  } },
      media:    { l: 'Media buying', i: 'media',   w: { c: [490, 262], o: [462, 235] }, t: { c: [310, 350], o: [200, 470] } },
      digital:  { l: 'Digital & PR', i: 'digital', w: { c: [300, 196], o: [462, 366] }, t: { c: [200, 210], o: [336, 470] } },
      hub:      { l: 'Cab Media',   i: 'hub', ai: 1, appear: 1, w: { c: [285, 235], o: [285, 235] }, t: { c: [200, 270], o: [200, 270] } }
    };
    var chaosLinks = [['brief', 'creative'], ['brief', 'digital'], ['brand', 'digital'], ['brand', 'media'], ['budget', 'digital'], ['budget', 'creative'], ['creative', 'media'], ['digital', 'media'], ['brief', 'media'], ['brand', 'budget'], ['creative', 'brand']];
    var orderLinks = [['brief', 'hub'], ['brand', 'hub'], ['budget', 'hub'], ['hub', 'creative'], ['hub', 'media'], ['hub', 'digital']];
    var stage = $('.flow-stage', flow), svg = $('svg', stage);
    var mode = 'wide', W = 600, H = 470;
    svg.innerHTML = '<defs><linearGradient id="og" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="600" y2="0"><stop offset="0" stop-color="#ff4a4a"/><stop offset="1" stop-color="#ff8f70"/></linearGradient></defs>';
    var els = {};
    Object.keys(N).forEach(function (k) {
      var n = N[k], e = d.createElement('div');
      e.className = 'fnode' + (n.ai ? ' ai' : '') + (n.human ? ' isperson' : '');
      e.innerHTML = '<i><svg viewBox="0 0 24 24">' + ICON[n.i] + '</svg></i><span>' + n.l + '</span>';
      stage.appendChild(e); els[k] = { box: e };
    });
    function mk(tag, attrs) { var e = d.createElementNS(NS, tag); for (var a in attrs) e.setAttribute(a, attrs[a]); svg.appendChild(e); return e; }
    var cPaths = chaosLinks.map(function () { return mk('path', { fill: 'none', stroke: 'rgba(255,255,255,.28)', 'stroke-width': 1.4, 'stroke-dasharray': '4 6' }); });
    var oPaths = orderLinks.map(function () { return mk('path', { fill: 'none', stroke: 'url(#og)', 'stroke-width': 2.2, 'stroke-linecap': 'round' }); });
    var dots = []; oPaths.forEach(function () { for (var k = 0; k < 2; k++) dots.push(mk('circle', { r: 3.6, fill: '#fff' })); });
    var grad = $('#og', svg);
    function applyLayout() {
      var m = window.matchMedia('(max-width: 700px)').matches ? 'tall' : 'wide';
      if (m === mode && svg.getAttribute('viewBox')) return;
      mode = m; W = LAYOUT[m].W; H = LAYOUT[m].H;
      svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
      stage.style.aspectRatio = W + ' / ' + H;
      flow.setAttribute('data-layout', m);
      if (grad) { grad.setAttribute('x2', m === 'tall' ? 0 : W); grad.setAttribute('y2', m === 'tall' ? H : 0); }
    }
    applyLayout();
    window.addEventListener('resize', applyLayout);
    var tabs = $$('.flow-tabs button', flow), cap = null;

    var target = 0, cur = 0, state = 'chaos', auto = !reduce, last = performance.now(), visible = true, timer;
    function pos(k, e) { var n = N[k][mode === 'tall' ? 't' : 'w']; return [n.c[0] + (n.o[0] - n.c[0]) * e, n.c[1] + (n.o[1] - n.c[1]) * e]; }
    function frame(now) {
      requestAnimationFrame(frame);
      if (!visible) { last = now; return; }
      var dt = Math.min(64, now - last); last = now;
      if (reduce) cur = target; else cur += (target - cur) * Math.min(1, dt / 420);
      if (Math.abs(target - cur) < 0.001) cur = target;
      var e = cur < 0.5 ? 2 * cur * cur : 1 - Math.pow(-2 * cur + 2, 2) / 2, t = now / 1000, P = {};
      Object.keys(N).forEach(function (k) {
        var p = pos(k, e); P[k] = p;
        var box = els[k].box;
        box.style.left = (p[0] / W * 100) + '%'; box.style.top = (p[1] / H * 100) + '%';
        if (N[k].appear) { box.style.opacity = e; box.style.scale = (0.7 + 0.3 * e); }
      });
      chaosLinks.forEach(function (l, i) {
        var a = P[l[0]], b = P[l[1]], mx = (a[0] + b[0]) / 2, my = (a[1] + b[1]) / 2;
        var dx = b[0] - a[0], dy = b[1] - a[1], len = Math.hypot(dx, dy) || 1, off = Math.sin(t * 0.9 + i * 1.7) * 38 * (1 - e) + 22;
        cPaths[i].setAttribute('d', 'M' + a[0] + ' ' + a[1] + ' Q' + (mx - dy / len * off) + ' ' + (my + dx / len * off) + ' ' + b[0] + ' ' + b[1]);
        cPaths[i].style.opacity = Math.max(0, 1 - e * 1.6);
        cPaths[i].setAttribute('stroke-dashoffset', -t * 14);
      });
      var dotI = 0;
      orderLinks.forEach(function (l, i) {
        var a = P[l[0]], b = P[l[1]], dx = Math.max(40, Math.abs(b[0] - a[0]) * 0.5), dy = Math.max(40, Math.abs(b[1] - a[1]) * 0.5);
        oPaths[i].setAttribute('d', mode === 'tall'
          ? 'M' + a[0] + ' ' + a[1] + ' C' + a[0] + ' ' + (a[1] + dy) + ' ' + b[0] + ' ' + (b[1] - dy) + ' ' + b[0] + ' ' + b[1]
          : 'M' + a[0] + ' ' + a[1] + ' C' + (a[0] + dx) + ' ' + a[1] + ' ' + (b[0] - dx) + ' ' + b[1] + ' ' + b[0] + ' ' + b[1]);
        var op = Math.max(0, (e - 0.35) / 0.65); oPaths[i].style.opacity = op;
        var len = oPaths[i].getTotalLength();
        for (var k = 0; k < 2; k++, dotI++) {
          var u = reduce ? 0.5 : ((t * 0.32 + k / 2 + i * 0.13) % 1), pt = oPaths[i].getPointAtLength(len * u);
          dots[dotI].setAttribute('cx', pt.x); dots[dotI].setAttribute('cy', pt.y);
          dots[dotI].style.opacity = op * (reduce ? 0 : Math.sin(u * Math.PI));
        }
      });
    }
    function setState(s, manual) {
      state = s; target = s === 'order' ? 1 : 0; flow.setAttribute('data-state', s);
      tabs.forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-state') === s)); });
      if (manual) { auto = false; clearTimeout(timer); }
    }
    tabs.forEach(function (b) { b.addEventListener('click', function () { setState(b.getAttribute('data-state'), true); }); });
    function loop() { if (!auto) return; timer = setTimeout(function () { if (auto) { setState(state === 'chaos' ? 'order' : 'chaos'); loop(); } }, state === 'chaos' ? 3200 : 6500); }
    setState('chaos'); if (auto) setTimeout(function () { if (auto) { setState('order'); loop(); } }, 2600);
    if ('IntersectionObserver' in window) new IntersectionObserver(function (es) { visible = es[0].isIntersecting; }).observe(flow);
    requestAnimationFrame(frame);
  }

  /* ---------- scroll-spy for the single-page navigation ---------- */
  var navLinks = $$('.nav-links > a[href^="/#"], .nav-links .dd > a[href^="/#"]');
  if ($('#home') && navLinks.length && 'IntersectionObserver' in window) {
    var spySecs = navLinks.map(function (a) { return $(a.getAttribute('href').replace('/', '')); }).filter(Boolean);
    var spy = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (!e.isIntersecting) return;
        navLinks.forEach(function (a) { if (a.getAttribute('href') === '/#' + e.target.id) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current'); });
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    spySecs.forEach(function (s) { spy.observe(s); });
  }

  /* ---------- service tabs ---------- */
  var stabs = $$('.svc-tabs button');
  stabs.forEach(function (b) {
    b.addEventListener('click', function () {
      var g = b.getAttribute('data-group');
      stabs.forEach(function (x) { x.setAttribute('aria-selected', String(x === b)); });
      $$('.svc-pane').forEach(function (p) {
        var on = p.getAttribute('data-group') === g;
        p.hidden = !on;
        if (on) $$('[data-reveal]', p).forEach(function (el, i) { el.classList.remove('in'); void el.offsetWidth; setTimeout(function () { el.classList.add('in'); }, 40 + i * 40); });
      });
    });
  });

  /* ---------- testimonials ---------- */
  var tst = $('.tst');
  if (tst) {
    var ttabs = $$('.tst-tab', tst), tcards = $$('.tst-card', tst), tn = $('#tst-n'), ti = 0, tt, DUR = 9000;
    var tshow = function (n) {
      ti = (n + tcards.length) % tcards.length;
      tcards.forEach(function (c, i) { c.classList.toggle('on', i === ti); });
      ttabs.forEach(function (b, i) {
        var on = i === ti; b.setAttribute('aria-selected', String(on));
        b.classList.remove('on'); if (on) { void b.offsetWidth; b.classList.add('on'); }
      });
      if (tn) tn.textContent = String(ti + 1);
      var act = ttabs[ti]; if (act && act.scrollIntoView && window.matchMedia('(max-width: 899px)').matches) { var l = $('.tst-list', tst); l.scrollTo({ left: act.offsetLeft - 16, behavior: 'smooth' }); }
      var stage = $('.tst-stage', tst); stage.style.minHeight = '';
    };
    var tauto = function () { clearInterval(tt); tst.classList.toggle('paused', false); if (!reduce) tt = setInterval(function () { tshow(ti + 1); }, DUR); else tst.classList.add('paused'); };
    ttabs.forEach(function (b, i) { b.addEventListener('click', function () { tshow(i); tauto(); }); });
    $$('.tsl-btn', tst).forEach(function (b) { b.addEventListener('click', function () { tshow(ti + parseInt(b.getAttribute('data-dir'), 10)); tauto(); }); });
    var sx = 0;
    tst.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    tst.addEventListener('touchend', function (e) { var dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) { tshow(ti + (dx < 0 ? 1 : -1)); tauto(); } });
    tst.addEventListener('pointerenter', function () { clearInterval(tt); tst.classList.add('paused'); });
    tst.addEventListener('pointerleave', tauto);
    tshow(0); tauto();
  }

  /* ---------- prefill the enquiry from ?service=slug ---------- */
  var svcParam = (new URLSearchParams(location.search)).get('service');
  var msgBox = $('#f-mejora');
  if (svcParam && msgBox && !msgBox.value) {
    var nice = svcParam.replace(/-/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    msgBox.value = 'I would like to know more about: ' + nice + '.\n';
  }

  /* ---------- article: table of contents + share ---------- */
  var body = $('#article-body');
  if (body) {
    var heads = $$('h2, h3', body), toc = $('#toc'), card = $('#toc-card');
    if (heads.length > 2 && toc && card) {
      heads.forEach(function (h, i) {
        if (!h.id) h.id = 's' + (i + 1) + '-' + h.textContent.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 40);
        var a = d.createElement('a'); a.href = '#' + h.id; a.textContent = h.textContent; if (h.tagName === 'H3') a.className = 'sub';
        toc.appendChild(a); h._a = a;
      });
      card.hidden = false;
      if ('IntersectionObserver' in window) {
        var tio2 = new IntersectionObserver(function (es) {
          es.forEach(function (e) { if (e.isIntersecting) heads.forEach(function (h) { h._a.classList.toggle('on', h === e.target); }); });
        }, { rootMargin: '-15% 0px -75% 0px' });
        heads.forEach(function (h) { tio2.observe(h); });
      }
    }
  }
  var cp = $('[data-copy]');
  if (cp) cp.addEventListener('click', function () {
    var url = cp.closest('[data-share]').getAttribute('data-share');
    (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(function () { cp.textContent = 'Copied ✓'; setTimeout(function () { cp.textContent = 'Copy link'; }, 2000); }).catch(function () { window.prompt('Copy this link', url); });
  });

  onScroll(); fillText(); parallax(); timeline();
})();
