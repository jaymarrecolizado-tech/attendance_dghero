/*
 * Hack for Gov 5 door gate behavior: unlock sequence (bolts, flare, door
 * swing), particle window behind the doors, staged handoff into the form,
 * and session skip. The gate partial renders server-side; this script only
 * enhances it and never blocks the form (guest_gate.php already removes the
 * gate without JS or when the session flag says it was unlocked before).
 */
(function () {
  var gate = document.getElementById('eventGate');
  if (!gate) return;

  var slug = gate.getAttribute('data-event-slug') || 'event';
  var hud = document.getElementById('eventGateHud');
  var unlockBtn = document.getElementById('eventGateUnlock');
  var canvas = document.getElementById('eventGateWindow');
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
    // Doors finish their swing (1.55s) before the handoff starts.
    window.setTimeout(release, reduceMotion ? 900 : 2150);
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

  /* ---------- Particle window (gold, white, sparse flag specks) ---------- */

  if (!canvas || !canvas.getContext) return;

  var ctx = canvas.getContext('2d');
  var particles = [];
  var raf = 0;
  var running = false;
  var w = 0;
  var h = 0;
  var dpr = Math.min(window.devicePixelRatio || 1, 2);
  var pointerX = -9999;
  var pointerY = -9999;

  // Flag palette: mostly gold and white, a few blue and red specks.
  var COLORS = [
    '252, 209, 22', '252, 209, 22', '252, 209, 22', '252, 209, 22',
    '242, 244, 249', '242, 244, 249',
    '0, 56, 168',
    '206, 17, 38'
  ];

  function sizeCanvas() {
    w = window.innerWidth;
    h = window.innerHeight;
    canvas.width = Math.floor(w * dpr);
    canvas.height = Math.floor(h * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  function spawn() {
    // Cap by viewport area so phones get a light field.
    var count = Math.min(130, Math.max(30, Math.round((w * h) / 13000)));
    particles = [];
    for (var i = 0; i < count; i++) {
      particles.push({
        x: Math.random() * w,
        y: Math.random() * h,
        r: 0.8 + Math.random() * 1.7,
        vx: (Math.random() - 0.5) * 0.24,
        vy: (Math.random() - 0.5) * 0.24 - 0.06,
        color: COLORS[Math.floor(Math.random() * COLORS.length)],
        alpha: 0.3 + Math.random() * 0.5,
        phase: Math.random() * Math.PI * 2
      });
    }
  }

  function drawParticle(p, t) {
    var twinkle = reduceMotion ? 1 : 0.72 + 0.28 * Math.sin(t * 0.0016 + p.phase);
    ctx.beginPath();
    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
    ctx.fillStyle = 'rgba(' + p.color + ',' + (p.alpha * twinkle).toFixed(3) + ')';
    ctx.fill();
  }

  function step(t) {
    if (!running) return;
    ctx.clearRect(0, 0, w, h);
    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      p.x += p.vx;
      p.y += p.vy;
      if (p.x < -8) p.x = w + 8; else if (p.x > w + 8) p.x = -8;
      if (p.y < -8) p.y = h + 8; else if (p.y > h + 8) p.y = -8;
      // Pointer and touch nudge nearby specks, gently.
      var dx = p.x - pointerX;
      var dy = p.y - pointerY;
      var d2 = dx * dx + dy * dy;
      if (d2 < 14400 && d2 > 0.01) {
        var d = Math.sqrt(d2);
        var push = (120 - d) / 120 * 0.55;
        p.x += (dx / d) * push;
        p.y += (dy / d) * push;
      }
      drawParticle(p, t);
    }
    raf = window.requestAnimationFrame(step);
  }

  function startParticles() {
    if (running) return;
    running = true;
    sizeCanvas();
    spawn();
    if (reduceMotion) {
      // Still field: one painted frame, no drift, no pointer chase.
      for (var i = 0; i < particles.length; i++) drawParticle(particles[i], 0);
      return;
    }
    raf = window.requestAnimationFrame(step);
  }

  function stopParticles() {
    running = false;
    if (raf) window.cancelAnimationFrame(raf);
    raf = 0;
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

  window.addEventListener('resize', function () {
    if (!running) return;
    sizeCanvas();
    spawn();
    if (reduceMotion) {
      for (var i = 0; i < particles.length; i++) drawParticle(particles[i], 0);
    }
  });

  startParticles();
})();
