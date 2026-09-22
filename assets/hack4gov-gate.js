/*
 * Hack for Gov 5 door gate behavior: unlock sequence (bolts, flare, door
 * swing), session skip, and scroll-lock release. The gate partial renders
 * server-side; this script only enhances it and never blocks the form
 * (guest_gate.php already removes the gate without JS or when the session
 * flag says it was unlocked before).
 */
(function () {
  var gate = document.getElementById('eventGate');
  if (!gate) return;

  var slug = gate.getAttribute('data-event-slug') || 'event';
  var hud = document.getElementById('eventGateHud');
  var unlockBtn = document.getElementById('eventGateUnlock');
  var opening = false;

  function release() {
    gate.classList.add('done');
    gate.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('gate-locked');
    try {
      sessionStorage.setItem('gateUnlocked:' + slug, '1');
    } catch (e) { /* storage unavailable: gate just re-shows next load */ }
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
    window.setTimeout(function () {
      release();
      var first = document.querySelector('#registrationForm input[name="first_name"]');
      if (first) first.focus({ preventScroll: true });
    }, 2100);
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
})();
