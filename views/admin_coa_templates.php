<?php
declare(strict_types=1);

/**
 * Plan#11: CoA template library (All Father). Save named certificate
 * templates, apply one to an event's CoA settings, and preview a sample PDF.
 */
$editing = $editing ?? null;
$templates = $templates ?? [];
$events = $events ?? [];
$currentEventId = (int)($currentEventId ?? 0);
$flash = $flash ?? null;
$activeNav = 'admin_coa_monitor';
$token = function_exists('csrf_token') ? csrf_token() : '';
if (!function_exists('coaTpl')) {
    function coaTpl(array $row, string $field, string $fallback = ''): string
    {
        $v = trim((string)($row[$field] ?? ''));
        return $v !== '' ? $v : $fallback;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CoA Templates</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'admin_nav.php'; ?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0">Certificate templates</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="?r=admin_coa_monitor">Back to monitor</a>
      <a class="btn btn-sm btn-outline-primary" href="?r=admin_coa_preview_template<?= $editing ? '&template_id=' . (int)$editing['id'] : '' ?>" target="_blank" rel="noopener">Preview <?= $editing ? 'saved template' : 'current settings' ?></a>
    </div>
  </div>

  <?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?> py-2"><?= htmlspecialchars($flash['message'], ENT_QUOTES) ?></div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-lg-7">
      <div class="card"><div class="card-body">
        <h2 class="h6 mb-3"><?= $editing ? 'Edit template #' . (int)$editing['id'] : 'New template' ?></h2>
        <form method="post" action="?r=admin_coa_template_save" class="row g-2">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
          <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
          <div class="col-12">
            <label class="form-label small mb-1">Template name</label>
            <input name="name" class="form-control form-control-sm" maxlength="120" required value="<?= htmlspecialchars(coaTpl($editing ?? [], 'name'), ENT_QUOTES) ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small mb-1">Venue</label>
            <input name="venue" maxlength="255" class="form-control form-control-sm" value="<?= htmlspecialchars(coaTpl($editing ?? [], 'venue'), ENT_QUOTES) ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small mb-1">Purpose line</label>
            <input name="purpose" maxlength="255" class="form-control form-control-sm" value="<?= htmlspecialchars(coaTpl($editing ?? [], 'purpose'), ENT_QUOTES) ?>">
          </div>
          <div class="col-12">
            <label class="form-label small mb-1">Particulars (one line per row, "Label - Value"; empty = defaults)</label>
            <textarea name="particulars" class="form-control form-control-sm" rows="3"><?= htmlspecialchars(coaTpl($editing ?? [], 'particulars'), ENT_QUOTES) ?></textarea>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small mb-1">Signatory name</label>
            <input name="signatory_name" maxlength="120" class="form-control form-control-sm" value="<?= htmlspecialchars(coaTpl($editing ?? [], 'signatory_name'), ENT_QUOTES) ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small mb-1">Signatory title</label>
            <input name="signatory_title" maxlength="120" class="form-control form-control-sm" value="<?= htmlspecialchars(coaTpl($editing ?? [], 'signatory_title'), ENT_QUOTES) ?>">
          </div>
          <div class="col-12">
            <label class="form-label small mb-1">Signature image path (optional)</label>
            <input name="signatory_path" class="form-control form-control-sm" value="<?= htmlspecialchars(coaTpl($editing ?? [], 'signatory_path'), ENT_QUOTES) ?>">
          </div>
          <div class="col-12">
            <label class="form-label small mb-1">Left logo image path (optional)</label>
            <input name="logo_path" class="form-control form-control-sm" value="<?= htmlspecialchars(coaTpl($editing ?? [], 'logo_path'), ENT_QUOTES) ?>">
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary btn-sm"><?= $editing ? 'Update template' : 'Save as template' ?></button>
            <?php if ($editing): ?><a class="btn btn-outline-secondary btn-sm" href="?r=admin_coa_templates">New</a><?php endif; ?>
            <a class="btn btn-outline-secondary btn-sm" href="?r=admin_coa_preview_template<?= $editing ? '&template_id=' . (int)$editing['id'] : '' ?>" target="_blank" rel="noopener">Preview</a>
          </div>
        </form>
      </div></div>

      <div class="card mt-3"><div class="card-body">
        <h2 class="h6 mb-3">Apply a template to an event</h2>
        <p class="text-muted small">Copies venue, purpose, particulars, signatory, and image paths onto the event's CoA settings (Events page block stays for per-event overrides).</p>
        <form method="post" action="?r=admin_coa_template_apply" class="row g-2 align-items-end">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
          <div class="col-12 col-md-6">
            <label class="form-label small mb-1">Template</label>
            <select name="template_id" class="form-select form-select-sm" required>
              <?php foreach ($templates as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars((string)$t['name'], ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small mb-1">Event</label>
            <select name="event_id" class="form-select form-select-sm" required>
              <?php foreach ($events as $ev): ?>
              <option value="<?= (int)$ev['id'] ?>" <?= (int)$ev['id'] === $currentEventId ? 'selected' : '' ?>><?= htmlspecialchars((string)$ev['name'], ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <button class="btn btn-primary btn-sm">Apply to event</button>
          </div>
        </form>
      </div></div>
    </div>

    <div class="col-12 col-lg-5">
      <div class="card"><div class="card-body">
        <h2 class="h6 mb-3">Saved templates</h2>
        <?php if (!count($templates)): ?>
        <p class="text-muted small mb-0">No templates yet. Fill the form and save one.</p>
        <?php endif; ?>
        <div class="list-group list-group-flush">
          <?php foreach ($templates as $t): ?>
          <div class="list-group-item px-0">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <strong><?= htmlspecialchars((string)$t['name'], ENT_QUOTES) ?></strong>
                <div class="small text-muted"><?= htmlspecialchars((string)($t['venue'] ?? ''), ENT_QUOTES) ?: 'No venue set' ?></div>
                <div class="small text-muted"><?= htmlspecialchars((string)($t['signatory_name'] ?? ''), ENT_QUOTES) ?> - <?= htmlspecialchars((string)($t['signatory_title'] ?? ''), ENT_QUOTES) ?></div>
              </div>
              <div class="d-flex flex-column gap-1">
                <a class="btn btn-sm btn-outline-primary py-0" href="?r=admin_coa_templates&edit_id=<?= (int)$t['id'] ?>">Edit</a>
                <a class="btn btn-sm btn-outline-secondary py-0" href="?r=admin_coa_preview_template&template_id=<?= (int)$t['id'] ?>" target="_blank" rel="noopener">Preview</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div></div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
