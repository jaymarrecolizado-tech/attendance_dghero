<?php
declare(strict_types=1);

$guestTitle = 'Data Privacy Notice';
$guestIncludeRegistrationAssets = false;
$guestIncludeRegistrationJs = false;
$guestShowActions = false;
require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'guest_head.php';
?>
<main class="guest-main">
  <div class="container guest-container py-4">
    <div class="guest-form-card p-4">
      <?php $privacyCompact = false; require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'privacy_notice.php'; ?>
      <p class="small mb-0"><a href="?r=register<?= isset($_GET['e']) && (string)$_GET['e'] !== '' ? '&e=' . urlencode((string)$_GET['e']) : '' ?>">Back to registration</a></p>
    </div>
  </div>
</main>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'guest_footer.php'; ?>
