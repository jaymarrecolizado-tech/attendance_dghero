<?php
declare(strict_types=1);

use App\Services\PrivacyNotice;

$compact = !empty($privacyCompact);
?>
<div class="privacy-notice<?= $compact ? ' is-compact' : '' ?>">
  <h3 class="<?= $compact ? 'h6' : 'h5' ?>"><?= htmlspecialchars(PrivacyNotice::title(), ENT_QUOTES) ?></h3>
  <?php foreach (PrivacyNotice::sections() as $section): ?>
  <p class="small mb-2"><strong><?= htmlspecialchars($section['heading'], ENT_QUOTES) ?>.</strong>
    <?= htmlspecialchars($section['body'], ENT_QUOTES) ?></p>
  <?php endforeach; ?>
</div>
