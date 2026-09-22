<?php
declare(strict_types=1);

/**
 * Door entrance for gate-layout events (e.g. Hack for Gov 5). Rendered by
 * guest_head.php before the nav when the event theme_layout is 'gate' and
 * the page is the register form (never on success or error pages). A canvas
 * window behind the doors hosts the particle field. The inline script below
 * skips the gate for the rest of the browser session, locks page scroll and
 * dims the page while the gate shows; without JS the gate is removed
 * entirely so the form stays reachable.
 */
$gateSlug = isset($event) && isset($event['slug']) ? (string)$event['slug'] : '';
?>
<!-- Page-level circuit background: lives outside #eventGate so it survives
     the session skip and stays behind the panel and form after the reveal. -->
<svg class="event-gate-page-circuits" id="eventGatePageCircuits" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
  <g data-page-circuit data-tone="gold"><polyline points="60,24 60,64 150,64"/><circle cx="60" cy="24" r="3"/></g>
  <g data-page-circuit data-tone="blue"><polyline points="300,40 380,40 380,80"/><circle cx="300" cy="40" r="3"/></g>
  <g data-page-circuit data-tone="gold"><polyline points="1100,26 1180,26 1180,66 1260,66"/><circle cx="1100" cy="26" r="3"/></g>
  <g data-page-circuit data-tone="gold"><polyline points="30,180 30,280 70,280"/><circle cx="30" cy="180" r="3"/></g>
  <g data-page-circuit data-tone="gold"><polyline points="100,860 100,800 200,800"/><circle cx="100" cy="860" r="3"/></g>
  <g data-page-circuit data-tone="blue"><polyline points="480,870 480,810 560,810"/><circle cx="480" cy="870" r="3"/></g>
  <g data-page-circuit data-tone="gold"><polyline points="900,866 980,866 980,806"/><circle cx="900" cy="866" r="3"/></g>
  <g data-page-circuit data-tone="gold"><polyline points="1410,300 1410,400 1370,400"/><circle cx="1410" cy="300" r="3"/></g>
  <g data-page-circuit data-tone="blue" class="event-gate-page-circuit-extra"><polyline points="24,420 70,420 70,500"/><circle cx="24" cy="420" r="3"/></g>
  <g data-page-circuit data-tone="red" class="event-gate-page-circuit-extra"><polyline points="1330,40 1400,40 1400,90"/><circle cx="1330" cy="40" r="3"/></g>
  <g data-page-circuit data-tone="red" class="event-gate-page-circuit-extra"><polyline points="1300,870 1370,870 1370,810"/><circle cx="1300" cy="870" r="3"/></g>
  <g data-page-circuit data-tone="blue" class="event-gate-page-circuit-extra"><polyline points="1400,560 1400,640 1350,640"/><circle cx="1400" cy="560" r="3"/></g>
  <g data-page-circuit data-tone="gold" class="event-gate-page-circuit-extra"><polyline points="640,880 640,830 720,830"/><circle cx="640" cy="880" r="3"/></g>
  <g data-page-circuit data-tone="blue" class="event-gate-page-circuit-extra"><polyline points="1050,30 1050,80 1120,80"/><circle cx="1050" cy="30" r="3"/></g>
  <g data-page-circuit data-tone="gold" class="event-gate-page-circuit-extra"><polyline points="200,300 260,300 260,360"/><circle cx="200" cy="300" r="3"/></g>
  <g data-page-circuit data-tone="red" class="event-gate-page-circuit-extra"><polyline points="1180,760 1240,760 1240,700"/><circle cx="1180" cy="760" r="3"/></g>
</svg>
<div class="event-gate" id="eventGate" role="dialog" aria-modal="true" aria-label="Event entrance" data-event-slug="<?= htmlspecialchars($gateSlug, ENT_QUOTES) ?>">
  <canvas class="event-gate-window" id="eventGateWindow" aria-hidden="true"></canvas>
  <div class="event-gate-door event-gate-door-l">
    <div class="event-gate-face">
      <img class="event-gate-mark" src="assets/hack4gov-door-logo.png" alt="" width="900" height="694">
      <div class="event-gate-bolts" aria-hidden="true"><i></i><i></i><i></i></div>
    </div>
  </div>
  <div class="event-gate-door event-gate-door-r">
    <div class="event-gate-face">
      <img class="event-gate-mark" src="assets/hack4gov-door-logo.png" alt="" width="900" height="694">
      <div class="event-gate-bolts" aria-hidden="true"><i></i><i></i><i></i></div>
    </div>
  </div>
  <div class="event-gate-seam" aria-hidden="true"></div>
  <div class="event-gate-flare" aria-hidden="true"></div>
  <div class="event-gate-seal" aria-hidden="true">
    <svg viewBox="0 0 72 72">
      <rect x="15.5" y="7" width="3.6" height="59" fill="#FCD116"/>
      <circle cx="17.3" cy="6.4" r="3" fill="#FCD116"/>
      <path d="M22 12 H64 L52 27 L64 42 H22 Z" fill="#0038A8"/>
      <path d="M22 27 H64 L52 27 L64 42 H22 Z" fill="#CE1126"/>
      <path d="M22 12 L22 42 L41 27 Z" fill="#F2F4F9"/>
      <circle cx="29" cy="27" r="3.4" fill="#FCD116"/>
      <path d="M22 12 H64 L52 27 L64 42 H22 Z" fill="none" stroke="#F2F4F9" stroke-width="1.6"/>
    </svg>
    <small>SECURE</small>
  </div>
  <div class="event-gate-icons" aria-hidden="true">
    <div class="event-gate-icon" title="Password Protected"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
    <div class="event-gate-icon" title="Government Grade"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4 8 4v14"/><path d="M9 21v-6h6v6"/></svg></div>
    <div class="event-gate-icon" title="Encrypted"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
  </div>
  <div class="event-gate-hud">
    <p class="event-gate-hud-line" id="eventGateHud">SECURE REGISTRATION // STATUS: <b>LOCKED</b></p>
    <button type="button" class="event-gate-unlock" id="eventGateUnlock">Access System &rarr;</button>
  </div>
</div>
<script>
(function () {
  var gate = document.getElementById('eventGate');
  if (!gate) return;
  var skipped = false;
  try {
    skipped = sessionStorage.getItem('gateUnlocked:' + (gate.getAttribute('data-event-slug') || 'event')) === '1';
  } catch (e) { /* storage unavailable: show the gate */ }
  if (skipped) {
    gate.parentNode.removeChild(gate);
    return;
  }
  // Page beneath starts dimmed and low; the reveal script lets it rise.
  document.body.classList.add('gate-locked', 'gate-pending');
})();
</script>
<noscript><style>.event-gate{display:none!important}</style></noscript>
