<?php
declare(strict_types=1);

use App\Services\EventContext;

$token = function_exists('csrf_token') ? csrf_token() : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Events</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<?php $activeNav = 'admin_events'; require __DIR__ . '/partials/admin_nav.php'; ?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5">Events</h1>
    <div class="btn-group">
      <a class="btn btn-outline-secondary btn-sm" href="?r=admin_registrants">Registrants</a>
      <a class="btn btn-outline-secondary btn-sm" href="?r=admin_attendance">Attendance</a>
    </div>
  </div>
  <form method="post" action="?r=admin_events_create" class="row g-2 mb-3">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
    <div class="col-12 col-md-4"><input name="name" class="form-control" placeholder="Event name" required></div>
    <div class="col-6 col-md-2">
      <select name="status" class="form-select">
        <option value="open">Open</option>
        <option value="draft">Draft</option>
        <option value="closed">Closed</option>
      </select>
    </div>
    <div class="col-6 col-md-2"><input name="starts_at" type="datetime-local" class="form-control" title="Starts at"></div>
    <div class="col-6 col-md-2"><input name="ends_at" type="datetime-local" class="form-control" title="Ends at"></div>
    <div class="col-6 col-md-1"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="enforce" id="enf" checked><label class="form-check-label" for="enf">Single</label></div></div>
    <div class="col-12 col-md-1"><button class="btn btn-primary w-100">Add</button></div>
  </form>
  <div class="table-responsive table-modern">
    <table class="table table-sm align-middle">
      <thead><tr><th>#</th><th>Name</th><th>Slug</th><th>Status</th><th>Schedule</th><th>Links</th><th>Staff</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach (($rows??[]) as $r): ?>
        <?php
          $slug = (string)($r['slug'] ?? '');
          $links = $slug !== '' ? EventContext::publicLinks($slug) : null;
          $assigned = $byEvent[(int)$r['id']] ?? [];
        ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars((string)$r['name'], ENT_QUOTES) ?></td>
          <td><code><?= htmlspecialchars($slug, ENT_QUOTES) ?></code></td>
          <td><span class="badge text-bg-<?= ($r['status'] ?? '') === 'open' ? 'success' : ((($r['status'] ?? '') === 'closed') ? 'secondary' : 'warning') ?>"><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES) ?></span></td>
          <td class="small text-muted">
            <?= htmlspecialchars((string)($r['starts_at'] ?? ''), ENT_QUOTES) ?>
            <?= !empty($r['ends_at']) ? ' – ' . htmlspecialchars((string)$r['ends_at'], ENT_QUOTES) : '' ?>
          </td>
          <td>
            <?php if ($links): ?>
            <div class="d-flex flex-column gap-1">
              <span class="small"><code><?= htmlspecialchars($links['register'], ENT_QUOTES) ?></code> <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-copy="<?= htmlspecialchars($links['register'], ENT_QUOTES) ?>">Copy register</button></span>
              <span class="small"><code><?= htmlspecialchars($links['scan'], ENT_QUOTES) ?></code> <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-copy="<?= htmlspecialchars($links['scan'], ENT_QUOTES) ?>">Copy scan</button></span>
            </div>
            <?php endif; ?>
          </td>
          <td class="small">
            <?php foreach ($assigned as $a): ?>
              <div><?= htmlspecialchars((string)($a['username'] ?? ''), ENT_QUOTES) ?> (<?= htmlspecialchars((string)($a['role'] ?? ''), ENT_QUOTES) ?>)
                <form method="post" action="?r=admin_events_unassign" class="d-inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
                  <input type="hidden" name="admin_id" value="<?= (int)$a['admin_id'] ?>">
                  <input type="hidden" name="event_id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-link text-danger p-0">remove</button>
                </form>
              </div>
            <?php endforeach; ?>
            <form method="post" action="?r=admin_events_assign" class="d-flex gap-1 mt-1">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <input type="hidden" name="event_id" value="<?= (int)$r['id'] ?>">
              <select name="admin_id" class="form-select form-select-sm" required>
                <option value="">Staff…</option>
                <?php foreach (($staff??[]) as $s): ?>
                  <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars((string)($s['username'] ?? ''), ENT_QUOTES) ?></option>
                <?php endforeach; ?>
              </select>
              <select name="role" class="form-select form-select-sm">
                <option value="event_admin">event_admin</option>
                <option value="checker">checker</option>
                <option value="seo_viewer">seo_viewer</option>
              </select>
              <button class="btn btn-sm btn-outline-primary">Assign</button>
            </form>
          </td>
          <td>
            <form method="post" action="?r=admin_events_update" class="d-flex flex-column gap-1">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="enforce" value="<?= ((int)($r['enforce_single_time_in'] ?? 1)) ? '1' : '' ?>">
              <select name="status" class="form-select form-select-sm">
                <?php foreach (['draft','open','closed'] as $st): ?>
                  <option value="<?= $st ?>" <?= (($r['status'] ?? '') === $st) ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-outline-primary">Save</button>
            </form>
            <form method="post" action="?r=admin_events_switch" class="d-inline mt-1">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary mt-1">Switch to</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
document.querySelectorAll('[data-copy]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var v = btn.getAttribute('data-copy') || '';
    if (navigator.clipboard) { navigator.clipboard.writeText(v); btn.textContent = 'Copied'; }
  });
});
</script>
</body>
</html>
