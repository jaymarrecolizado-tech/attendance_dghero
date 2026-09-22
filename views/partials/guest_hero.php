<?php
declare(strict_types=1);
$eventTitle = $eventTitle ?? 'GovNet-Launching';
$eventDate = $eventDate ?? '';
$eventTheme = $eventTheme ?? [];
$themeWelcome = trim((string)($eventTheme['welcome'] ?? ''));
$themeBannerUrl = (string)($eventTheme['banner_url'] ?? '');
// Gate-layout events (Hack for Gov 5) show the full mark and a circuit layer.
// Strokes are attributes so the traces stay lines even if a cached stylesheet is old.
$gateLayout = ($eventTheme['layout'] ?? '') === 'gate';
$gateCircuits = '';
if ($gateLayout) {
    $trace = static function (string $tone, string $points, string $nodes, string $extra = ''): string {
        $stroke = $tone === 'blue' ? '#0038A8' : ($tone === 'red' ? '#CE1126' : '#FCD116');
        $circles = '';
        foreach (preg_split('/\s+/', trim($nodes)) as $node) {
            [$x, $y] = explode(',', $node);
            $circles .= '<circle fill="' . $stroke . '" cx="' . $x . '" cy="' . $y . '" r="3.2"/>';
        }
        return '<g data-circuit data-tone="' . $tone . '"' . ($extra !== '' ? ' class="' . $extra . '"' : '') . '>'
            . '<polyline fill="none" stroke="' . $stroke . '" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" points="' . $points . '"/>'
            . $circles . '</g>';
    };
    $gateCircuits = '<svg class="event-gate-circuits" viewBox="0 0 420 560" preserveAspectRatio="xMidYMid slice" aria-hidden="true">'
        . $trace('gold', '24,40 24,120 110,120 110,190', '24,40 110,190')
        . $trace('gold', '396,48 280,48 280,140 200,140', '396,48')
        . $trace('blue', '18,250 100,250 100,320 190,320', '18,250 190,320')
        . $trace('gold', '402,200 402,280 300,280', '300,280')
        . $trace('red', '28,400 120,400 120,330 200,330', '28,400')
        . $trace('gold', '392,380 392,450 300,450 300,520', '392,380')
        . $trace('blue', '210,540 210,470 300,470', '210,540')
        . $trace('gold', '48,530 48,460 140,460', '48,530', 'event-gate-circuit-extra')
        . $trace('red', '370,530 290,530 290,460', '370,530', 'event-gate-circuit-extra')
        . '</svg>';
}
?>
<aside class="guest-hero" aria-label="Registration guide">
  <?php if ($themeBannerUrl !== ''): ?>
  <img class="guest-hero-banner" src="<?= htmlspecialchars($themeBannerUrl, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($eventTitle, ENT_QUOTES) ?> banner">
  <?php endif; ?>
  <div class="guest-hero-mobile d-lg-none<?= $gateLayout ? ' is-gate' : '' ?>">
    <?php if ($gateLayout): ?>
    <div class="event-gate-hero-art">
      <?= $gateCircuits ?>
      <img class="event-gate-hero-mark" src="assets/hack4gov-door-logo.png" alt="" width="900" height="694" style="width:100%;height:auto;display:block">
    </div>
    <?php else: ?>
    <div class="guest-hero-mobile-icon" aria-hidden="true">
      <svg width="32" height="32" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="16" fill="rgba(26,68,128,0.12)"/><path d="M11 22V10h2.8c2.2 0 3.5 1.1 3.5 2.8 0 1.4-.85 2.35-2.15 2.6L17.5 22h-2.5l-1.85-4.1H13.2V22H11zm3-6.3h1.25c.9 0 1.4-.45 1.4-1.2s-.5-1.2-1.4-1.2H14v2.4zM18.5 22V10h6v2.5h-3v1.5h2.75v2.5H21.5V19h3.25V22h-6z" fill="#1a4480"/></svg>
    </div>
    <?php endif; ?>
    <div>
      <p class="guest-hero-mobile-title mb-0"><?= htmlspecialchars($eventTitle, ENT_QUOTES) ?></p>
      <p class="guest-hero-mobile-sub mb-0"><?= $themeWelcome !== '' ? htmlspecialchars($themeWelcome, ENT_QUOTES) : 'Register once, check in fast with your QR code' ?></p>
    </div>
  </div>

  <div class="guest-hero-panel d-none d-lg-flex<?= $gateLayout ? ' is-gate' : '' ?>">
    <?php if (!$gateLayout): ?>
    <div class="guest-hero-pattern" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="guest-hero-content">
      <span class="badge-soft guest-hero-badge">Event Registration</span>
      <?php if ($gateLayout): ?>
      <div class="event-gate-hero-art">
        <?= $gateCircuits ?>
        <img class="event-gate-hero-mark" src="assets/hack4gov-door-logo.png" alt="<?= htmlspecialchars($eventTitle, ENT_QUOTES) ?> mark" width="900" height="694" style="width:100%;height:auto;display:block">
      </div>
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
