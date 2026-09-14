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
            <?php $slug = (string)($ev['slug'] ?? ''); if ($slug === '') continue; ?>
            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="?r=<?= htmlspecialchars($target, ENT_QUOTES) ?>&amp;e=<?= urlencode($slug) ?>">
              <span>
                <strong><?= htmlspecialchars((string)($ev['name'] ?? 'Event'), ENT_QUOTES) ?></strong>
                <?php if (!empty($ev['starts_at'])): ?>
                  <br><small class="text-muted"><?= htmlspecialchars((string)$ev['starts_at'], ENT_QUOTES) ?></small>
                <?php endif; ?>
              </span>
              <span class="btn btn-primary btn-sm"><?= $isScan ? 'Open kiosk' : 'Register' ?></span>
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
