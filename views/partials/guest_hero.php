<?php
declare(strict_types=1);
$eventTitle = $eventTitle ?? 'GovNet-Launching';
$eventDate = $eventDate ?? '';
$eventTheme = $eventTheme ?? [];
$themeWelcome = trim((string)($eventTheme['welcome'] ?? ''));
$themeBannerUrl = (string)($eventTheme['banner_url'] ?? '');
// Gate-layout events (Hack for Gov 5) show the full mark and a circuit layer.
$gateLayout = ($eventTheme['layout'] ?? '') === 'gate';
?>
<aside class="guest-hero" aria-label="Registration guide">
  <?php if ($themeBannerUrl !== ''): ?>
  <img class="guest-hero-banner" src="<?= htmlspecialchars($themeBannerUrl, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($eventTitle, ENT_QUOTES) ?> banner">
  <?php endif; ?>
  <div class="guest-hero-mobile d-lg-none">
    <?php if ($gateLayout): ?>
    <svg class="event-gate-circuits" viewBox="0 0 420 560" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
      <g data-circuit data-tone="gold"><polyline points="24,18 24,86 96,86 96,150"/><circle cx="24" cy="18" r="3"/><circle cx="96" cy="150" r="3"/></g>
      <g data-circuit data-tone="gold"><polyline points="396,30 300,30 300,110 236,110"/><circle cx="396" cy="30" r="3"/></g>
      <g data-circuit data-tone="blue"><polyline points="18,220 90,220 90,286 168,286"/><circle cx="18" cy="220" r="3"/><circle cx="168" cy="286" r="3"/></g>
      <g data-circuit data-tone="gold"><polyline points="402,180 402,252 330,252"/><circle cx="330" cy="252" r="3"/></g>
      <g data-circuit data-tone="red"><polyline points="30,380 110,380 110,318 186,318"/><circle cx="30" cy="380" r="3"/></g>
      <g data-circuit data-tone="gold"><polyline points="390,360 390,420 318,420 318,486"/><circle cx="390" cy="360" r="3"/></g>
      <g data-circuit data-tone="blue"><polyline points="210,540 210,470 286,470"/><circle cx="210" cy="540" r="3"/></g>
      <g data-circuit data-tone="gold"><polyline points="60,530 60,470 130,470 130,410"/><circle cx="60" cy="530" r="3"/></g>
      <g data-circuit data-tone="gold" class="event-gate-circuit-extra"><polyline points="240,60 240,130 310,130"/><circle cx="310" cy="130" r="3"/></g>
      <g data-circuit data-tone="red" class="event-gate-circuit-extra"><polyline points="370,520 300,520 300,452"/><circle cx="370" cy="520" r="3"/></g>
      <g data-circuit data-tone="gold" class="event-gate-circuit-extra"><polyline points="150,30 150,70 220,70"/><circle cx="150" cy="30" r="3"/></g>
      <g data-circuit data-tone="blue" class="event-gate-circuit-extra"><polyline points="30,140 30,196 84,196"/><circle cx="84" cy="196" r="3"/></g>
    </svg>
    <?php endif; ?>
    <div class="guest-hero-mobile-icon" aria-hidden="true">
      <?php if ($gateLayout): ?>
      <img class="event-gate-hero-logo" src="assets/hack4gov-door-logo.png" alt="">
      <?php else: ?>
      <svg width="32" height="32" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="16" fill="rgba(26,68,128,0.12)"/><path d="M11 22V10h2.8c2.2 0 3.5 1.1 3.5 2.8 0 1.4-.85 2.35-2.15 2.6L17.5 22h-2.5l-1.85-4.1H13.2V22H11zm3-6.3h1.25c.9 0 1.4-.45 1.4-1.2s-.5-1.2-1.4-1.2H14v2.4zM18.5 22V10h6v2.5h-3v1.5h2.75v2.5H21.5V19h3.25V22h-6z" fill="#1a4480"/></svg>
      <?php endif; ?>
    </div>
    <div>
      <p class="guest-hero-mobile-title mb-0"><?= htmlspecialchars($eventTitle, ENT_QUOTES) ?></p>
      <p class="guest-hero-mobile-sub mb-0"><?= $themeWelcome !== '' ? htmlspecialchars($themeWelcome, ENT_QUOTES) : 'Register once, check in fast with your QR code' ?></p>
    </div>
  </div>

  <div class="guest-hero-panel d-none d-lg-flex">
    <div class="guest-hero-pattern" aria-hidden="true"></div>
    <?php if ($gateLayout): ?>
    <svg class="event-gate-circuits" viewBox="0 0 420 560" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
      <g data-circuit data-tone="gold"><polyline points="24,18 24,86 96,86 96,150"/><circle cx="24" cy="18" r="3"/><circle cx="96" cy="150" r="3"/></g>
      <g data-circuit data-tone="gold"><polyline points="396,30 300,30 300,110 236,110"/><circle cx="396" cy="30" r="3"/></g>
      <g data-circuit data-tone="blue"><polyline points="18,220 90,220 90,286 168,286"/><circle cx="18" cy="220" r="3"/><circle cx="168" cy="286" r="3"/></g>
      <g data-circuit data-tone="gold"><polyline points="402,180 402,252 330,252"/><circle cx="330" cy="252" r="3"/></g>
      <g data-circuit data-tone="red"><polyline points="30,380 110,380 110,318 186,318"/><circle cx="30" cy="380" r="3"/></g>
      <g data-circuit data-tone="gold"><polyline points="390,360 390,420 318,420 318,486"/><circle cx="390" cy="360" r="3"/></g>
      <g data-circuit data-tone="blue"><polyline points="210,540 210,470 286,470"/><circle cx="210" cy="540" r="3"/></g>
      <g data-circuit data-tone="gold"><polyline points="60,530 60,470 130,470 130,410"/><circle cx="60" cy="530" r="3"/></g>
      <g data-circuit data-tone="gold" class="event-gate-circuit-extra"><polyline points="240,60 240,130 310,130"/><circle cx="310" cy="130" r="3"/></g>
      <g data-circuit data-tone="red" class="event-gate-circuit-extra"><polyline points="370,520 300,520 300,452"/><circle cx="370" cy="520" r="3"/></g>
      <g data-circuit data-tone="gold" class="event-gate-circuit-extra"><polyline points="150,30 150,70 220,70"/><circle cx="150" cy="30" r="3"/></g>
      <g data-circuit data-tone="blue" class="event-gate-circuit-extra"><polyline points="30,140 30,196 84,196"/><circle cx="84" cy="196" r="3"/></g>
    </svg>
    <?php endif; ?>
    <div class="guest-hero-content">
      <span class="badge-soft guest-hero-badge">Event Registration</span>
      <?php if ($gateLayout): ?>
      <img class="event-gate-hero-logo" src="assets/hack4gov-door-logo.png" alt="<?= htmlspecialchars($eventTitle, ENT_QUOTES) ?> mark">
      <?php endif; ?>
      <h1 class="guest-hero-title"><?= htmlspecialchars($eventTitle, ENT_QUOTES) ?></h1>
      <?php if ($themeWelcome !== ''): ?>
      <p class="guest-hero-subtitle"><?= htmlspecialchars($themeWelcome, ENT_QUOTES) ?></p>
      <?php elseif ($eventDate !== ''): ?>
      <p class="guest-hero-subtitle"><?= htmlspecialchars(date('F j, Y', strtotime($eventDate)), ENT_QUOTES) ?></p>
      <?php else: ?>
      <p class="guest-hero-subtitle">Register once, check in fast with your QR code at the welcome desk.</p>
      <?php endif; ?>
      <ol class="guest-hero-steps">
        <li>
          <span class="guest-hero-step-num">1</span>
          <span>Fill in your details</span>
        </li>
        <li>
          <span class="guest-hero-step-num">2</span>
          <span>Receive your QR code</span>
        </li>
        <li>
          <span class="guest-hero-step-num">3</span>
          <span>Scan at the entrance</span>
        </li>
      </ol>
      <p class="guest-hero-tip">
        <strong>Staff tip:</strong> Tap Continue to help the guest through each step on kiosk tablets.
      </p>
    </div>
  </div>
</aside>
