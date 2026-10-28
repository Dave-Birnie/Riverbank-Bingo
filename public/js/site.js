/* Riverbank Bingo — public site behaviour. No build step, no framework. */
(function () {
  'use strict';

  var root = document.documentElement;
  var LETTERS = ['B', 'I', 'N', 'G', 'O'];
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function letterFor(n) { return LETTERS[Math.min(4, Math.floor((n - 1) / 15))]; }
  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  /* ---- Live status -------------------------------------------------------
     The server renders the right state; this keeps it right while the page
     stays open. Pages that need different markup when live (Watch Live,
     Schedule) reload; everything else flips the data-live attribute. */
  var statusUrl = root.getAttribute('data-status-url');
  var nextStart = root.getAttribute('data-next-game');

  function applyStatus(status) {
    var wasLive = root.getAttribute('data-live') === '1';
    root.setAttribute('data-live', status.live ? '1' : '0');

    if (status.next_game && status.next_game.starts_at !== nextStart) {
      nextStart = status.next_game.starts_at;
      root.setAttribute('data-next-game', nextStart);
    }

    if (wasLive !== status.live && document.body.hasAttribute('data-reload-on-live-change')) {
      window.location.reload();
    }
  }

  function poll() {
    if (!statusUrl || !window.fetch) return;
    fetch(statusUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (s) { if (s) applyStatus(s); })
      .catch(function () {});
  }

  if (statusUrl) {
    setInterval(function () { if (!document.hidden) poll(); }, 30000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
  }

  /* ---- Countdowns --------------------------------------------------------- */
  var countdowns = document.querySelectorAll('[data-countdown]');

  function tickCountdowns() {
    var target = nextStart ? Date.parse(nextStart) : NaN;
    var secs = isNaN(target) ? 0 : Math.max(0, Math.floor((target - Date.now()) / 1000));
    var parts = { d: Math.floor(secs / 86400), h: Math.floor(secs / 3600) % 24, m: Math.floor(secs / 60) % 60, s: secs % 60 };

    countdowns.forEach(function (el) {
      el.querySelectorAll('[data-unit]').forEach(function (u) {
        u.textContent = pad(parts[u.getAttribute('data-unit')]);
      });
    });
  }

  if (countdowns.length) {
    tickCountdowns();
    setInterval(tickCountdowns, 1000);
  }

  /* ---- Flashboard ----------------------------------------------------------
     Attract mode for the homepage hero: draws a ball every few seconds,
     lights it on the board, and resets after a full card's worth of calls.
     Decorative only — real games are called on the broadcast. */
  var flash = document.querySelector('[data-flashboard]');

  if (flash) {
    var board = flash.querySelector('.board');
    var current = flash.querySelector('.ball-current');
    var cage = flash.querySelector('[data-cage]');
    var countLabel = flash.querySelector('[data-call-count]');
    var cells = {};
    var calls = [];

    LETTERS.forEach(function (letter, row) {
      var head = document.createElement('div');
      head.className = 'row-letter';
      head.textContent = letter;
      board.appendChild(head);

      for (var i = 1; i <= 15; i++) {
        var n = row * 15 + i;
        var cell = document.createElement('div');
        cell.className = 'cell ball-' + letter;
        cell.textContent = n;
        board.appendChild(cell);
        cells[n] = cell;
      }
    });

    var spots = [
      { x: 30, y: 24, d: 26, a: 'rb-tumble1' }, { x: 60, y: 34, d: 31, a: 'rb-tumble2' }, { x: 44, y: 58, d: 23, a: 'rb-tumble3' },
      { x: 66, y: 64, d: 28, a: 'rb-tumble1' }, { x: 22, y: 56, d: 34, a: 'rb-tumble2' }, { x: 52, y: 20, d: 29, a: 'rb-tumble3' },
      { x: 36, y: 74, d: 25, a: 'rb-tumble2' }
    ];
    var tumblers = spots.map(function (p, i) {
      var b = document.createElement('div');
      b.className = 'ball';
      b.setAttribute('aria-hidden', 'true');
      b.style.left = p.x + '%';
      b.style.top = p.y + '%';
      b.style.animation = p.a + ' ' + p.d + 's ease-in-out infinite';
      b.style.animationDelay = '-' + (i * 2.3) + 's';
      cage.appendChild(b);
      return b;
    });

    function paintCage() {
      var shown = calls.slice(-7).reverse();
      [7, 19, 33, 48, 62, 12, 71].forEach(function (f) { if (shown.length < 7 && shown.indexOf(f) < 0) shown.push(f); });
      tumblers.forEach(function (b, i) {
        b.className = 'ball ball-' + letterFor(shown[i]);
        b.textContent = shown[i];
      });
    }

    function draw() {
      if (calls.length >= 75) {
        calls = [];
        Object.keys(cells).forEach(function (k) { cells[k].classList.remove('is-called', 'is-current'); });
      }

      var n;
      do { n = 1 + Math.floor(Math.random() * 75); } while (calls.indexOf(n) >= 0);

      var prev = calls[calls.length - 1];
      if (prev) cells[prev].classList.remove('is-current');
      calls.push(n);
      cells[n].classList.add('is-called', 'is-current');

      current.className = 'ball ball-current ball-' + letterFor(n);
      current.innerHTML = '<small>' + letterFor(n) + '</small><b>' + n + '</b>';
      void current.offsetWidth; // restart the drop animation
      current.classList.add('is-new');

      if (countLabel) countLabel.textContent = calls.length + ' called';
      paintCage();
    }

    // Start with a handful already called so the board isn't empty.
    for (var k = 0; k < 9; k++) draw();
    if (!reduceMotion) setInterval(function () { if (!document.hidden) draw(); }, 3200);
  }

  /* ---- Distributor map ---------------------------------------------------- */
  var mapEl = document.querySelector('[data-map]');

  if (mapEl && window.L) {
    var pins = JSON.parse(mapEl.getAttribute('data-map') || '[]');
    var map = L.map(mapEl, { scrollWheelZoom: false });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    var bounds = [];
    pins.forEach(function (p) {
      var popup = document.createElement('div');
      var name = document.createElement('strong');
      name.textContent = p.name;
      popup.appendChild(name);
      if (p.detail) {
        var detail = document.createElement('div');
        detail.textContent = p.detail;
        popup.appendChild(detail);
      }
      if (p.phone) {
        var call = document.createElement('a');
        call.href = 'tel:' + p.phone.replace(/\D+/g, '');
        call.textContent = p.phone;
        popup.appendChild(call);
      }
      L.marker([p.lat, p.lng], { title: p.name }).addTo(map).bindPopup(popup);
      bounds.push([p.lat, p.lng]);
    });

    if (bounds.length > 1) map.fitBounds(bounds, { padding: [36, 36] });
    else map.setView(bounds[0] || [45.4089, -73.6857], 13);
  }
})();
