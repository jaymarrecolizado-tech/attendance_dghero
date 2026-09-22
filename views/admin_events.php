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
    <div class="col-12 col-md-4"><input name="name" class="form-control" placeholder="Event name" aria-label="Event name" required></div>
    <div class="col-6 col-md-2">
      <select name="status" class="form-select" aria-label="Status">
        <option value="open">Open</option>
        <option value="draft">Draft</option>
        <option value="closed">Closed</option>
      </select>
    </div>
    <div class="col-6 col-md-2"><input name="starts_at" type="datetime-local" class="form-control" aria-label="Starts at" title="Starts at"></div>
    <div class="col-6 col-md-2"><input name="ends_at" type="datetime-local" class="form-control" aria-label="Ends at" title="Ends at"></div>
    <div class="col-6 col-md-1"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="enforce" id="enf" checked><label class="form-check-label" for="enf">Single</label></div></div>
    <div class="col-12 col-md-1"><button class="btn btn-primary w-100" aria-label="Add event">Add</button></div>
  </form>
  <div class="table-responsive table-modern">
    <table class="table table-sm align-middle">
      <thead><tr><th>#</th><th>Name</th><th>Slug</th><th>Status</th><th>Schedule</th><th>Links</th><th>Appearance</th><th>Staff</th><th>Actions</th></tr></thead>
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
            <?php $starts = trim((string)($r['starts_at'] ?? '')); $ends = trim((string)($r['ends_at'] ?? '')); ?>
            <?= $starts !== '' ? htmlspecialchars($starts, ENT_QUOTES) : 'Not set' ?>
            <?php if ($ends !== ''): ?><br>to <?= htmlspecialchars($ends, ENT_QUOTES) ?><?php endif; ?>
          </td>
          <td>
            <?php if ($links): ?>
            <div class="d-flex flex-column gap-1">
              <span class="small"><code class="text-break"><?= htmlspecialchars($links['register'], ENT_QUOTES) ?></code> <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-copy="<?= htmlspecialchars($links['register'], ENT_QUOTES) ?>" aria-label="Copy registration link">Copy register</button></span>
              <span class="small"><code class="text-break"><?= htmlspecialchars($links['scan'], ENT_QUOTES) ?></code> <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-copy="<?= htmlspecialchars($links['scan'], ENT_QUOTES) ?>" aria-label="Copy scan kiosk link">Copy scan</button></span>
            </div>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $themePrimary = trim((string)($r['theme_primary'] ?? ''));
              $themeAccent = trim((string)($r['theme_accent'] ?? ''));
              $themeWelcome = trim((string)($r['welcome_text'] ?? ''));
              $themeLogo = trim((string)($r['logo_path'] ?? ''));
              $themeBanner = trim((string)($r['banner_path'] ?? ''));
              $appearanceActive = $themePrimary !== '' || $themeAccent !== '' || $themeWelcome !== '' || $themeLogo !== '' || $themeBanner !== '';
            ?>
            <details class="event-appearance">
              <summary class="small <?= $appearanceActive ? 'fw-semibold text-primary' : 'text-muted' ?>">Appearance<?= $appearanceActive ? ' (set)' : '' ?></summary>
              <form method="post" action="?r=admin_event_theme" enctype="multipart/form-data" class="d-flex flex-column gap-1 mt-1">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <div class="d-flex gap-2">
                  <label class="small text-muted">Primary<br>
                    <input type="color" name="theme_primary" aria-label="Primary color" class="form-control form-control-color form-control-sm" value="<?= htmlspecialchars($themePrimary !== '' ? $themePrimary : '#1A4480', ENT_QUOTES) ?>">
                  </label>
                  <label class="small text-muted">Accent<br>
                    <input type="color" name="theme_accent" aria-label="Accent color" class="form-control form-control-color form-control-sm" value="<?= htmlspecialchars($themeAccent !== '' ? $themeAccent : '#0B687A', ENT_QUOTES) ?>">
                  </label>
                </div>
                <input name="welcome_text" maxlength="180" class="form-control form-control-sm" placeholder="Welcome line (optional)" aria-label="Welcome line" value="<?= htmlspecialchars($themeWelcome, ENT_QUOTES) ?>">
                <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp" class="form-control form-control-sm" aria-label="Logo upload">
                <input type="file" name="banner" accept=".png,.jpg,.jpeg,.webp" class="form-control form-control-sm" aria-label="Banner upload">
                <button class="btn btn-sm btn-outline-primary" aria-label="Save appearance">Save appearance</button>
                <div class="d-flex flex-wrap gap-1">
                  <button type="submit" name="clear_logo" value="1" class="btn btn-sm btn-outline-secondary" <?= $themeLogo === '' ? 'disabled' : '' ?>>Clear logo</button>
                  <button type="submit" name="clear_banner" value="1" class="btn btn-sm btn-outline-secondary" <?= $themeBanner === '' ? 'disabled' : '' ?>>Clear banner</button>
                  <button type="submit" name="reset_colors" value="1" class="btn btn-sm btn-outline-secondary">Revert colors</button>
                </div>
              </form>
            </details>
            <?php
              $coaEnabled = (int)($r['coa_enabled'] ?? 0) === 1;
              $coaVenue = trim((string)($r['coa_venue'] ?? ''));
              $coaPurpose = trim((string)($r['coa_purpose'] ?? ''));
              $coaParticulars = trim((string)($r['coa_particulars'] ?? ''));
              $coaSigName = trim((string)($r['coa_signatory_name'] ?? ''));
              $coaSigTitle = trim((string)($r['coa_signatory_title'] ?? ''));
              $coaSigPath = trim((string)($r['coa_signatory_path'] ?? ''));
              $coaLogoPath = trim((string)($r['coa_logo_path'] ?? ''));
              $coaActive = $coaEnabled || $coaVenue !== '' || $coaPurpose !== '' || $coaParticulars !== '' || $coaSigName !== '' || $coaSigTitle !== '';
            ?>
            <details class="event-coa mt-1">
              <summary class="small <?= $coaActive ? 'fw-semibold text-primary' : 'text-muted' ?>">CoA<?= $coaActive ? ' (' . ($coaEnabled ? 'on' : 'settings') . ')' : '' ?></summary>
              <form method="post" action="?r=admin_event_coa" class="d-flex flex-column gap-1 mt-1">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="coa_enabled" id="coaEnabled<?= (int)$r['id'] ?>" <?= $coaEnabled ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="coaEnabled<?= (int)$r['id'] ?>">Auto-send Certificate of Appearance after scan</label>
                </div>
                <input name="coa_venue" maxlength="255" class="form-control form-control-sm" placeholder="Venue" aria-label="CoA venue" value="<?= htmlspecialchars($coaVenue, ENT_QUOTES) ?>">
                <input name="coa_purpose" maxlength="255" class="form-control form-control-sm" placeholder="Purpose line (e.g. for the Hack for Gov 5 roadmap briefing)" aria-label="CoA purpose" value="<?= htmlspecialchars($coaPurpose, ENT_QUOTES) ?>">
                <textarea name="coa_particulars" class="form-control form-control-sm" rows="3" placeholder="Particulars, one line per row (Label - Value). Empty = defaults." aria-label="CoA particulars"><?= htmlspecialchars($coaParticulars, ENT_QUOTES) ?></textarea>
                <label class="form-label small text-muted mb-0 mt-1">Signatory</label>
                <select name="coa_signatory_id" class="form-select form-select-sm" aria-label="CoA signatory">
                  <option value="">Select a signatory</option>
                  <?php foreach (($coaSignatories ?? []) as $cs): ?>
                  <option value="<?= (int)$cs['id'] ?>" <?= (int)($r['coa_signatory_id'] ?? 0) === (int)$cs['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$cs['name'], ENT_QUOTES) ?><?= (string)($cs['title'] ?? '') !== '' ? ' - ' . htmlspecialchars((string)$cs['title'], ENT_QUOTES) : '' ?><?= (string)($cs['signature_path'] ?? '') === '' ? ' (no e-sig)' : '' ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-outline-primary" aria-label="Save CoA settings">Save CoA</button>
              </form>
            </details>
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
            <form method="post" action="?r=admin_events_assign" class="d-flex flex-wrap gap-1 mt-1">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <input type="hidden" name="event_id" value="<?= (int)$r['id'] ?>">
              <select name="admin_id" class="form-select form-select-sm" aria-label="Staff account" required>
                <option value="">Select staff</option>
                <?php foreach (($staff??[]) as $s): ?>
                  <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars((string)($s['username'] ?? ''), ENT_QUOTES) ?></option>
                <?php endforeach; ?>
              </select>
              <select name="role" class="form-select form-select-sm" aria-label="Role">
                <option value="event_admin">event_admin</option>
                <option value="checker">checker</option>
                <option value="seo_viewer">seo_viewer</option>
              </select>
              <button class="btn btn-sm btn-outline-primary" aria-label="Assign staff to event">Assign</button>
            </form>
          </td>
          <td>
            <form method="post" action="?r=admin_events_update" class="d-flex flex-column gap-1">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <select name="status" class="form-select form-select-sm" aria-label="Status">
                <?php foreach (['draft','open','closed'] as $st): ?>
                  <option value="<?= $st ?>" <?= (($r['status'] ?? '') === $st) ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
              </select>
              <input name="starts_at" type="datetime-local" class="form-control form-control-sm" aria-label="Starts at" title="Starts at" value="<?= htmlspecialchars(substr(str_replace(' ', 'T', (string)($r['starts_at'] ?? '')), 0, 16), ENT_QUOTES) ?>">
              <input name="ends_at" type="datetime-local" class="form-control form-control-sm" aria-label="Ends at" title="Ends at" value="<?= htmlspecialchars(substr(str_replace(' ', 'T', (string)($r['ends_at'] ?? '')), 0, 16), ENT_QUOTES) ?>">
              <div class="form-check"><input class="form-check-input" type="checkbox" name="enforce" id="enf<?= (int)$r['id'] ?>" <?= ((int)($r['enforce_single_time_in'] ?? 1)) ? 'checked' : '' ?>><label class="form-check-label small" for="enf<?= (int)$r['id'] ?>">Single time-in</label></div>
              <button class="btn btn-sm btn-outline-primary" aria-label="Save event changes">Save</button>
            </form>
            <form method="post" action="?r=admin_events_switch" class="d-inline mt-1">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary mt-1" aria-label="Switch active context to this event">Switch to</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
// Clipboard with a non-secure-context fallback (admin kiosks may run over plain HTTP),
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
