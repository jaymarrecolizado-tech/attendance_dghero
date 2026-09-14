<?php
declare(strict_types=1);

$mode = $mode ?? 'register';
$isScan = $mode === 'scan';
$guestTitle = $isScan ? 'Select Event for Check-in' : 'Select Your Event';
require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'guest_head.php';
$target = $isScan ? 'scan' : 'register';
?>
<main class="guest-main">
  <div class="container guest-container">
    <div class="guest-form-wrap" style="max-width:640px;margin:0 auto;">
      <div class="guest-form-card">
        <h1 class="guest-form-title"><?= $isScan ? 'Select event kiosk' : 'Select your event' ?></h1>
        <p class="guest-form-subtitle">
          <?= $isScan ? 'Open the check-in kiosk for your event.' : 'Choose the event you are registering for. Each event has its own sign-up link.' ?>
        </p>
        <?php if (!empty($events)): ?>
        <div class="list-group">
          <?php foreach ($events as $ev): ?>
            <?php
              $slug = (string)($ev['slug'] ?? ''); if ($slug === '') continue;
              $startsRaw = trim((string)($ev['starts_at'] ?? ''));
              $startsTs = $startsRaw !== '' ? strtotime($startsRaw) : false;
              $schedule = $startsTs ? date('M j, Y, g:i A', $startsTs) : ($startsRaw !== '' ? $startsRaw : 'Schedule to be announced');
            ?>
            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3" href="?r=<?= htmlspecialchars($target, ENT_QUOTES) ?>&amp;e=<?= urlencode($slug) ?>">
              <span>
                <strong><?= htmlspecialchars((string)($ev['name'] ?? 'Event'), ENT_QUOTES) ?></strong>
                <br><small class="text-muted"><?= htmlspecialchars($schedule, ENT_QUOTES) ?></small>
              </span>
              <span class="btn btn-primary btn-sm flex-shrink-0"><?= $isScan ? 'Open kiosk' : 'Register' ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info mb-0">No open events right now. Please check back later.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'guest_footer.php'; ?>
