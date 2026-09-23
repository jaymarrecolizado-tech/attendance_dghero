/*
 * Hack for Gov 5 gate assets: door unlock sequence (bolts, flare, door
 * swing), particle window behind the doors, staged handoff into the form,
 * session skip, and the hero circuit field that answers the pointer. The
 * gate partial renders server-side; this script only enhances it and never
 * blocks the form (guest_gate.php already removes the gate without JS or
 * when the session flag says it was unlocked before).
 *
 * One animated canvas at a time: while the door window runs, the page
 * circuit stays unstarted; release() hands over to it (a session-skip
 * return starts it immediately). The page loop pauses on visibilitychange
 * hidden and resumes with one frame. Per-frame work stays light: no
 * shadowBlur (a wider translucent stroke stands in for the glow), DPR
 * capped at 1 (page) / 1.5 (door), halved seed counts, pointer lighting
 * sampled on a ~50ms timer, and debounced resizes.
 */
(function () {
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Hero circuit field (independent of the gate overlay) ------- */

  function initCircuits() {
    if (reduceMotion) return; // Static traces: no pointer chase.
    var svgs = Array.prototype.slice.call(document.querySelectorAll('.event-gate-circuits'));
    if (!svgs.length) return;
    svgs.forEach(function (svg) {
      var host = svg.closest('.guest-hero-panel') || svg.closest('.guest-hero-mobile');
      if (!host) return;
      var groups = Array.prototype.slice.call(svg.querySelectorAll('[data-circuit]'));
      if (!groups.length) return;

      var clear = function () {
        groups.forEach(function (g) { g.classList.remove('is-lit'); });
      };

      host.addEventListener('pointermove', function (e) {
        var hostRect = host.getBoundingClientRect();
        var x = e.clientX - hostRect.left;
        var y = e.clientY - hostRect.top;
        var near = [];
        groups.forEach(function (g) {
          var r = g.getBoundingClientRect();
          if (r.width === 0 && r.height === 0) return; // capped/hidden trace
          var cx = r.left + r.width / 2 - hostRect.left;
          var cy = r.top + r.height / 2 - hostRect.top;
          var d = Math.sqrt((cx - x) * (cx - x) + (cy - y) * (cy - y));
          if (d < 150) near.push([d, g]);
        });
        clear();
        near.sort(function (a, b) { return a[0] - b[0]; });
        near.slice(0, 3).forEach(function (pair) { pair[1].classList.add('is-lit'); });
      });

      host.addEventListener('pointerleave', clear);
    });
  }

  initCircuits();

  /* ---------- Page-level circuit background ------------------------------ */
  /* Not started while the door overlay is on screen: release() calls this
     once the overlay leaves, and the session-skip path (no #eventGate left
     in the DOM) starts it right away. */

  function initPageCircuits() {
    var canvas = document.getElementById('eventGatePageCircuits');
    if (!canvas || !canvas.getContext) return;
    var ctx = canvas.getContext('2d');
    var traces = [];
    var raf = 0;
    var running = false;
    var w = 0;
    var h = 0;
    var dpr = Math.min(window.devicePixelRatio || 1, 1);
    var pointerX = -9999;
    var pointerY = -9999;
    var colors = ['252,209,22', '46,196,255', '0,140,255', '255,150,48'];

    function clamp(n, max) {
      return Math.max(0, Math.min(max, n));
    }

    function sizeCanvas() {
      w = window.innerWidth;
      h = window.innerHeight;
      canvas.width = Math.floor(w * dpr);
      canvas.height = Math.floor(h * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function buildTraces() {
      var step = w < 720 ? 72 : 56;
      var count = w < 720 ? 18 : 40;
      traces = [];
      for (var i = 0; i < count; i++) {
        var horiz = Math.random() < 0.5;
        var x = clamp(Math.round(Math.random() * w / step) * step, w);
        var y = clamp(Math.round(Math.random() * h / step) * step, h);
        var len = (2 + Math.floor(Math.random() * 4)) * step;
        var dir = Math.random() < 0.5 ? 1 : -1;
        var x2 = clamp(horiz ? x + dir * len : x, w);
        var y2 = clamp(horiz ? y : y + dir * len, h);
        var bend = (1 + Math.floor(Math.random() * 3)) * step;
        var color = colors[i % colors.length];
        var phase = Math.random();
        traces.push({ x1: x, y1: y, x2: x2, y2: y2, color: color, phase: phase, lit: false });
        traces.push({
          x1: x2,
          y1: y2,
          x2: clamp(horiz ? x2 : x2 + (Math.random() < 0.5 ? bend : -bend), w),
          y2: clamp(horiz ? y2 + (Math.random() < 0.5 ? bend : -bend) : y2, h),
          color: color,
          phase: phase,
          lit: false
        });
      }
    }

    function distToSeg(px, py, t) {
      var dx = t.x2 - t.x1;
      var dy = t.y2 - t.y1;
      var l2 = dx * dx + dy * dy || 1;
      var u = Math.max(0, Math.min(1, ((px - t.x1) * dx + (py - t.y1) * dy) / l2));
      var sx = t.x1 + u * dx;
      var sy = t.y1 + u * dy;
      return Math.sqrt((px - sx) * (px - sx) + (py - sy) * (py - sy));
    }

    // Pointer lighting is sampled here on a timer, not measured per segment
    // inside the animation frame.
    function hitTest() {
      var far = pointerX < -9000;
      for (var i = 0; i < traces.length; i++) {
        traces[i].lit = !far && distToSeg(pointerX, pointerY, traces[i]) < 150;
      }
    }

    function drawTrace(t, now) {
      var lit = t.lit;
      var dx = t.x2 - t.x1;
      var dy = t.y2 - t.y1;
      ctx.save();
      ctx.lineCap = 'round';
      ctx.strokeStyle = 'rgba(' + t.color + ',' + (lit ? '0.55' : '0.28') + ')';
      ctx.lineWidth = lit ? 1.8 : 1.15;
      ctx.beginPath();
      ctx.moveTo(t.x1, t.y1);
      ctx.lineTo(t.x2, t.y2);
      ctx.stroke();

      if (!reduceMotion) {
        var speed = lit ? 0.00062 : 0.0002;
        var u = ((now * speed) + t.phase) % 1;
        var span = lit ? 0.36 : 0.2;
        var u0 = u - span;
        ctx.beginPath();
        if (u0 < 0) {
          ctx.moveTo(t.x1, t.y1);
          ctx.lineTo(t.x1 + dx * u, t.y1 + dy * u);
          ctx.moveTo(t.x1 + dx * (1 + u0), t.y1 + dy * (1 + u0));
          ctx.lineTo(t.x2, t.y2);
        } else {
          ctx.moveTo(t.x1 + dx * u0, t.y1 + dy * u0);
          ctx.lineTo(t.x1 + dx * u, t.y1 + dy * u);
        }
        // Glow without shadowBlur: one wide translucent pass under the
        // bright traveling dash.
        ctx.strokeStyle = 'rgba(' + t.color + ',0.22)';
        ctx.lineWidth = lit ? 6 : 4.5;
        ctx.stroke();
        ctx.strokeStyle = 'rgba(' + t.color + ',1)';
        ctx.lineWidth = lit ? 2.8 : 2;
        ctx.stroke();
        ctx.fillStyle = '#fff';
        ctx.beginPath();
        ctx.arc(t.x1 + dx * u, t.y1 + dy * u, lit ? 3.6 : 2.4, 0, Math.PI * 2);
        ctx.fill();
      }

      ctx.fillStyle = 'rgba(' + t.color + ',' + (lit ? '1' : '0.85') + ')';
      ctx.beginPath();
      ctx.arc(t.x1, t.y1, lit ? 3.5 : 2.2, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }

    function drawFrame(now) {
      ctx.clearRect(0, 0, w, h);
      for (var i = 0; i < traces.length; i++) drawTrace(traces[i], now || 0);
    }

    function frame(now) {
      raf = 0;
      if (!running) return;
      drawFrame(now);
      raf = window.requestAnimationFrame(frame);
    }

    function start() {
      sizeCanvas();
      buildTraces();
      hitTest();
      if (reduceMotion) {
        drawFrame(0); // static field, no loop
        return;
      }
      if (document.hidden) return; // visibilitychange resumes the loop
      if (!running) {
        running = true;
        raf = window.requestAnimationFrame(frame);
      }
    }

    document.addEventListener('pointermove', function (e) {
      pointerX = e.clientX;
      pointerY = e.clientY;
    }, { passive: true });

    if (!reduceMotion) window.setInterval(hitTest, 50);

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        running = false;
        if (raf) window.cancelAnimationFrame(raf);
        raf = 0;
        return;
      }
      // Back on screen: one frame restarts the loop over the same field.
      if (!reduceMotion && !running) {
        running = true;
        raf = window.requestAnimationFrame(frame);
      }
    });

    // A drag-resize must not rebuild the buffer on every event.
    var resizeTimer = 0;
    window.addEventListener('resize', function () {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(start, 150);
    });

    start();
  }

  /* ---------- Door gate (only when the overlay rendered) ----------------- */

  var gate = document.getElementById('eventGate');
  if (!gate) {
    // guest_gate.php's inline script already removed the overlay for this
    // browser session (or no gate rendered): the page circuit is the only
    // canvas, so it starts now instead of waiting for a door release.
    initPageCircuits();
    return;
  }

  var slug = gate.getAttribute('data-event-slug') || 'event';
  var hud = document.getElementById('eventGateHud');
  var unlockBtn = document.getElementById('eventGateUnlock');
  var canvas = document.getElementById('eventGateWindow');
  var opening = false;
  var released = false;

  function release() {
    if (released) return;
    released = true;
    // Overlay fades out while the page beneath fades and rises into place;
    // visibility drops only after the fade (see .event-gate.done).
    gate.classList.add('done');
    gate.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('gate-pending');
    // The door window is leaving, so the page circuit takes over now.
    initPageCircuits();
    try {
      sessionStorage.setItem('gateUnlocked:' + slug, '1');
    } catch (e) { /* storage unavailable: gate just re-shows next load */ }
    // Stop the particle field once the overlay has faded away.
    window.setTimeout(stopParticles, 900);
    window.setTimeout(function () {
      // Scroll unlocks after the overlay has faded, per the staged handoff.
      document.body.classList.remove('gate-locked');
    }, 820);
    window.setTimeout(function () {
      var first = document.querySelector('#registrationForm input[name="first_name"]');
      if (first) first.focus({ preventScroll: true });
    }, 500);
  }

  function openGate() {
    if (opening) return;
    opening = true;
    gate.classList.add('opening');
    if (hud) hud.innerHTML = 'AUTHENTICATING // <b>VERIFYING</b>';
    window.setTimeout(function () {
      gate.classList.add('open');
      if (hud) hud.innerHTML = 'ACCESS GRANTED // <b>WELCOME</b>';
    }, 380);
    if (reduceMotion) {
      window.setTimeout(release, 900);
      return;
    }
    // Doors finish their swing (1.55s) while the trench backdrop clears,
    // then the opening is held so the circuit field shows through; the
    // page settles into place before the overlay leaves.
    window.setTimeout(function () {
      document.body.classList.remove('gate-pending');
    }, 1700);
    window.setTimeout(release, 2600);
  }

  if (unlockBtn) unlockBtn.addEventListener('click', openGate);

  // Enter opens the gate, but never while typing in the form behind it and
  // never once the gate is done.
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' || opening || gate.classList.contains('done')) return;
    var t = e.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'BUTTON' || t.tagName === 'SELECT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) return;
    openGate();
  });

  /* ---------- Circuit window behind the doors --------------------------- */

  if (!canvas || !canvas.getContext) return;

  var ctx = canvas.getContext('2d');
  var traces = [];
  var raf = 0;
  var running = false;
  var hitTimer = 0;
  var w = 0;
  var h = 0;
  var dpr = Math.min(window.devicePixelRatio || 1, 1.5);
  var pointerX = -9999;
  var pointerY = -9999;
  var COLORS = ['252,209,22', '46,196,255', '0,140,255', '255,150,48'];

  function clamp(n, max) {
    return Math.max(0, Math.min(max, n));
  }

  function sizeCanvas() {
    w = window.innerWidth;
    h = window.innerHeight;
    canvas.width = Math.floor(w * dpr);
    canvas.height = Math.floor(h * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  function spawn() {
    var step = w < 720 ? 70 : 54;
    var count = w < 720 ? 14 : 28;
    traces = [];
    for (var i = 0; i < count; i++) {
      var horiz = Math.random() < 0.5;
      var x = clamp(Math.round(Math.random() * w / step) * step, w);
      var y = clamp(Math.round(Math.random() * h / step) * step, h);
      var len = (2 + Math.floor(Math.random() * 5)) * step;
      var dir = Math.random() < 0.5 ? 1 : -1;
      var x2 = clamp(horiz ? x + dir * len : x, w);
      var y2 = clamp(horiz ? y : y + dir * len, h);
      var bend = (1 + Math.floor(Math.random() * 3)) * step;
      var color = COLORS[i % COLORS.length];
      var phase = Math.random();
      traces.push({ x1: x, y1: y, x2: x2, y2: y2, color: color, phase: phase, lit: false });
      traces.push({
        x1: x2,
        y1: y2,
        x2: clamp(horiz ? x2 : x2 + (Math.random() < 0.5 ? bend : -bend), w),
        y2: clamp(horiz ? y2 + (Math.random() < 0.5 ? bend : -bend) : y2, h),
        color: color,
        phase: phase,
        lit: false
      });
    }
  }

  function distToSeg(px, py, t) {
    var dx = t.x2 - t.x1;
    var dy = t.y2 - t.y1;
    var l2 = dx * dx + dy * dy || 1;
    var u = Math.max(0, Math.min(1, ((px - t.x1) * dx + (py - t.y1) * dy) / l2));
    var sx = t.x1 + u * dx;
    var sy = t.y1 + u * dy;
    return Math.sqrt((px - sx) * (px - sx) + (py - sy) * (py - sy));
  }

  // Pointer lighting sampled on a timer while the window runs, not per frame.
  function hitTest() {
    var far = pointerX < -9000;
    for (var i = 0; i < traces.length; i++) {
      traces[i].lit = !far && distToSeg(pointerX, pointerY, traces[i]) < 140;
    }
  }

  function drawTrace(t, now) {
    var lit = t.lit;
    var dx = t.x2 - t.x1;
    var dy = t.y2 - t.y1;
    ctx.save();
    ctx.lineCap = 'round';
    ctx.strokeStyle = 'rgba(' + t.color + ',' + (lit ? '0.7' : '0.38') + ')';
    ctx.lineWidth = lit ? 2 : 1.3;
    ctx.beginPath();
    ctx.moveTo(t.x1, t.y1);
    ctx.lineTo(t.x2, t.y2);
    ctx.stroke();
    if (!reduceMotion) {
      var speed = lit ? 0.0007 : 0.00028;
      var u = ((now * speed) + t.phase) % 1;
      var span = lit ? 0.34 : 0.18;
      var u0 = u - span;
      ctx.beginPath();
      if (u0 < 0) {
        ctx.moveTo(t.x1, t.y1);
        ctx.lineTo(t.x1 + dx * u, t.y1 + dy * u);
        ctx.moveTo(t.x1 + dx * (1 + u0), t.y1 + dy * (1 + u0));
        ctx.lineTo(t.x2, t.y2);
      } else {
        ctx.moveTo(t.x1 + dx * u0, t.y1 + dy * u0);
        ctx.lineTo(t.x1 + dx * u, t.y1 + dy * u);
      }
      // Glow without shadowBlur: one wide translucent pass under the dash.
      ctx.strokeStyle = 'rgba(' + t.color + ',0.24)';
      ctx.lineWidth = lit ? 6.5 : 5;
      ctx.stroke();
      ctx.strokeStyle = 'rgba(' + t.color + ',1)';
      ctx.lineWidth = lit ? 3 : 2.1;
      ctx.stroke();
      ctx.fillStyle = '#fff';
      ctx.beginPath();
      ctx.arc(t.x1 + dx * u, t.y1 + dy * u, lit ? 3.4 : 2.3, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.fillStyle = 'rgba(' + t.color + ',' + (lit ? '1' : '0.85') + ')';
    ctx.beginPath();
    ctx.arc(t.x1, t.y1, lit ? 3.2 : 2.1, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
  }

  function step(now) {
    if (!running) return;
    ctx.clearRect(0, 0, w, h);
    for (var i = 0; i < traces.length; i++) drawTrace(traces[i], now || 0);
    raf = window.requestAnimationFrame(step);
  }

  function startParticles() {
    if (running && !reduceMotion) return;
    running = true;
    sizeCanvas();
    spawn();
    hitTest();
    if (reduceMotion) {
      for (var i = 0; i < traces.length; i++) drawTrace(traces[i], 0);
      return;
    }
    if (!hitTimer) hitTimer = window.setInterval(hitTest, 50);
    if (raf) window.cancelAnimationFrame(raf);
    raf = window.requestAnimationFrame(step);
  }

  function stopParticles() {
    running = false;
    if (raf) window.cancelAnimationFrame(raf);
    raf = 0;
    if (hitTimer) {
      window.clearInterval(hitTimer);
      hitTimer = 0;
    }
    ctx.clearRect(0, 0, w, h);
  }

  gate.addEventListener('pointermove', function (e) {
    pointerX = e.clientX;
    pointerY = e.clientY;
  }, { passive: true });

  gate.addEventListener('touchmove', function (e) {
    if (e.touches && e.touches[0]) {
      pointerX = e.touches[0].clientX;
      pointerY = e.touches[0].clientY;
    }
  }, { passive: true });

  // A drag-resize must not rebuild the buffer on every event.
  var resizeTimer = 0;
  window.addEventListener('resize', function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(function () {
      if (!running) return;
      sizeCanvas();
      spawn();
      hitTest();
      if (reduceMotion) {
        ctx.clearRect(0, 0, w, h);
        for (var i = 0; i < traces.length; i++) drawTrace(traces[i], 0);
      }
    }, 150);
  });

  startParticles();
})();
