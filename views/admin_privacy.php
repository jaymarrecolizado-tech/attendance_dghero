<?php
declare(strict_types=1);

$flash = $flash ?? null;
$activeNav = 'admin_privacy';
$token = function_exists('csrf_token') ? csrf_token() : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Privacy</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css?v=20261103m" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'admin_nav.php'; ?>
<div class="container py-4">
  <h1 class="h5 mb-3">Privacy — 15-day retention</h1>
  <?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars((string)$flash['type'], ENT_QUOTES) ?> py-2"><?= htmlspecialchars((string)$flash['message'], ENT_QUOTES) ?></div>
  <?php endif; ?>
  <p class="text-muted small">Live participant data is removed 15 days after the event ends (Asia/Manila). An encrypted zip stays on the VPS. Download it here and copy it to the office Drive folder if you want an extra copy. Type the event name to purge or restore.</p>
  <div class="table-responsive table-modern table-sticky-first mb-4">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Event</th><th>Ends</th><th>People</th><th>Purge</th><th>Drive</th><th></th></tr></thead>
      <tbody>
        <?php foreach (($events ?? []) as $ev): ?>
        <tr>
          <td class="small"><?= htmlspecialchars((string)$ev['name'], ENT_QUOTES) ?></td>
          <td class="small"><?= htmlspecialchars((string)($ev['ends_at'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= (int)($ev['participant_count'] ?? 0) ?></td>
          <td class="small">
            <?php if (!empty($ev['privacy_purged_at'])): ?>
              Purged <?= htmlspecialchars((string)$ev['privacy_purged_at'], ENT_QUOTES) ?>
            <?php elseif ($ev['days_until_purge'] === null): ?>
              No end date
            <?php elseif ((int)$ev['days_until_purge'] > 0): ?>
              In <?= (int)$ev['days_until_purge'] ?> day(s)
            <?php else: ?>
              Due
            <?php endif; ?>
            <?php if (!empty($ev['privacy_restore_hold_until'])): ?>
              <div class="text-muted">Hold until <?= htmlspecialchars((string)$ev['privacy_restore_hold_until'], ENT_QUOTES) ?></div>
            <?php endif; ?>
          </td>
          <td class="small">
            <?php if (!empty($ev['privacy_backup_url'])): ?>
              <a href="<?= htmlspecialchars((string)$ev['privacy_backup_url'], ENT_QUOTES) ?>" target="_blank" rel="noopener">Open</a>
            <?php elseif (!empty($ev['privacy_backup_path'])): ?>
              <a href="?r=admin_privacy_download&amp;event_id=<?= (int)$ev['id'] ?>">Download zip</a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td>
            <?php if (empty($ev['privacy_purged_at']) && (int)($ev['participant_count'] ?? 0) > 0): ?>
            <form method="post" action="?r=admin_privacy_purge" class="d-flex gap-1 align-items-center">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
              <input class="form-control form-control-sm" name="confirm_name" placeholder="Type event name" required>
              <button class="btn btn-sm btn-outline-danger">Purge now</button>
            </form>
            <?php elseif (!empty($ev['privacy_purged_at'])): ?>
            <form method="post" action="?r=admin_privacy_restore" class="d-flex gap-1 align-items-center">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
              <input class="form-control form-control-sm" name="confirm_name" placeholder="Type event name" required>
              <button class="btn btn-sm btn-outline-primary">Restore</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h2 class="h6">Deletion email</h2>
  <p class="text-muted small">Participants receive this after live data is removed. The registration photo for that event is at the top of the message.</p>
  <div class="row g-3 mb-4">
    <?php foreach (($events ?? []) as $ev): ?>
    <div class="col-lg-6">
      <div class="guest-form-card border rounded-3 p-3 h-100 bg-white">
        <div class="small fw-semibold mb-2"><?= htmlspecialchars((string)$ev['name'], ENT_QUOTES) ?></div>
        <?= $ev['deletion_preview_html'] ?? '' ?>
        <?php if (($ev['privacy_photo_url'] ?? '') !== ''): ?>
        <p class="small mb-0 mt-2">Image link (same as the email):<br>
          <a href="<?= htmlspecialchars((string)$ev['privacy_photo_url'], ENT_QUOTES) ?>" target="_blank" rel="noopener"><?= htmlspecialchars((string)$ev['privacy_photo_url'], ENT_QUOTES) ?></a>
        </p>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'admin_privacy_jobs.php'; ?>
</div>
</body>
</html>
