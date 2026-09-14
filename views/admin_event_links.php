<?php
declare(strict_types=1);

use App\Services\EventContext;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Event Links</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<?php $activeNav = 'admin_event_links'; require __DIR__ . '/partials/admin_nav.php'; ?>
<div class="container py-4">
  <h1 class="h5 mb-1">Event Links</h1>
  <p class="text-muted small mb-3">Share these links with participants and the scan kiosk. Each link works for its event only.</p>
  <?php if (empty($events)): ?>
    <div class="alert alert-info">No events assigned to you yet. Ask the All Father for access.</div>
  <?php else: ?>
  <div class="table-responsive table-modern">
    <table class="table table-sm align-middle">
      <thead><tr><th>Event</th><th>Status</th><th>Register link</th><th>Scan link</th></tr></thead>
      <tbody>
        <?php foreach ($events as $ev): ?>
        <?php
          $slug = (string)($ev['slug'] ?? '');
          if ($slug === '') continue;
          $links = EventContext::publicLinks($slug);
          $starts = trim((string)($ev['starts_at'] ?? ''));
          $ends = trim((string)($ev['ends_at'] ?? ''));
        ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars((string)($ev['name'] ?? ''), ENT_QUOTES) ?></strong>
            <?php if ($starts !== '' || $ends !== ''): ?>
            <div class="small text-muted">
              <?php if ($starts !== ''): ?>Opens <?= htmlspecialchars($starts, ENT_QUOTES) ?><?php endif; ?>
              <?php if ($starts !== '' && $ends !== ''): ?><br><?php endif; ?>
              <?php if ($ends !== ''): ?>Closes <?= htmlspecialchars($ends, ENT_QUOTES) ?><?php endif; ?>
            </div>
            <?php endif; ?>
          </td>
          <td><span class="badge text-bg-<?= (($ev['status'] ?? '') === 'open') ? 'success' : 'secondary' ?>"><?= htmlspecialchars((string)($ev['status'] ?? ''), ENT_QUOTES) ?></span></td>
          <td><code class="text-break"><?= htmlspecialchars($links['register'], ENT_QUOTES) ?></code> <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-copy="<?= htmlspecialchars($links['register'], ENT_QUOTES) ?>" aria-label="Copy registration link">Copy</button></td>
          <td><code class="text-break"><?= htmlspecialchars($links['scan'], ENT_QUOTES) ?></code> <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-copy="<?= htmlspecialchars($links['scan'], ENT_QUOTES) ?>" aria-label="Copy scan kiosk link">Copy</button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<script>
// Clipboard with a non-secure-context fallback (kiosks may run over plain HTTP),
// and a temporary "Copied" label so the button always returns to its original text.
function copyToClipboard(text, btn) {
  // Shareable absolute URL; the on-screen code stays short on purpose.
  var full = text.indexOf('http') === 0 ? text : window.location.origin + window.location.pathname + text;
  var done = function () {
    if (!btn.getAttribute('data-label')) btn.setAttribute('data-label', btn.textContent);
    btn.textContent = 'Copied';
    setTimeout(function () { btn.textContent = btn.getAttribute('data-label') || 'Copy'; }, 1600);
  };
  var fallback = function () {
    var ta = document.createElement('textarea');
    ta.value = full;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); done(); } catch (e) { /* clipboard unavailable */ }
    document.body.removeChild(ta);
  };
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(full).then(done, fallback);
  } else {
    fallback();
  }
}
document.querySelectorAll('[data-copy]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    copyToClipboard(btn.getAttribute('data-copy') || '', btn);
  });
});
</script>
</body>
</html>
