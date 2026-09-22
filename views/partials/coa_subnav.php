<?php
declare(strict_types=1);

$coaPage = $coaPage ?? 'monitor';
$scopeEventId = (int)($scopeEventId ?? 0);
$previewHref = $previewHref ?? '?r=admin_coa_preview_template';
?>
<div class="d-flex flex-wrap gap-2">
  <a class="btn btn-sm <?= $coaPage === 'monitor' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?r=admin_coa_monitor<?= $scopeEventId > 0 ? '&event_id=' . $scopeEventId : '' ?>">Monitor</a>
  <a class="btn btn-sm <?= $coaPage === 'templates' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?r=admin_coa_templates">Templates</a>
  <a class="btn btn-sm <?= $coaPage === 'signatories' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?r=admin_coa_signatories">Signatories</a>
  <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($previewHref, ENT_QUOTES) ?>" target="_blank" rel="noopener">Preview template</a>
  <?php if ($coaPage === 'monitor'): ?>
  <a class="btn btn-sm btn-outline-secondary" href="?r=admin_coa_monitor<?= $scopeEventId > 0 ? '&event_id=' . $scopeEventId : '' ?>">Refresh</a>
  <?php endif; ?>
</div>
