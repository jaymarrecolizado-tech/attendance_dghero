<?php
declare(strict_types=1);

use App\Services\Paginator;
use App\Services\SignatureSheetTable;

$token = function_exists('csrf_token') ? csrf_token() : '';
$days = $days ?? [];
$rows = $rows ?? [];
$eventName = $eventName ?? '';
$spanLabel = $spanLabel ?? '';
$sort = $sort ?? 'sigs';
$dir = $dir ?? 'desc';
$signed = $signed ?? 'all';
$q = $q ?? '';
$perChoice = $perChoice ?? SignatureSheetTable::PER_DEFAULT;
$pager = $pager ?? Paginator::make(1, 10, 0);
$dayCount = count($days);
$scriptBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$sheetQs = static function (array $extra) use ($sort, $dir, $signed, $q, $perChoice): string {
    $query = [
        'r' => 'admin_signature_sheet',
        'sort' => $sort !== 'sigs' ? $sort : null,
        'dir' => ($sort === 'sigs' && $dir === 'desc') ? null : $dir,
        'signed' => $signed !== 'all' ? $signed : null,
        'q' => $q !== '' ? $q : null,
        'per' => $perChoice !== SignatureSheetTable::PER_DEFAULT ? $perChoice : null,
        'page' => null,
    ];
    foreach ($extra as $key => $value) {
        $query[$key] = $value;
    }
    return Paginator::href($query, 'page', (int)($query['page'] ?? 1));
};
$sortHref = static function (string $key) use ($sort, $dir, $sheetQs): string {
    $next = ($sort === $key && $dir === 'desc') ? 'asc' : 'desc';
    $arrow = $sort === $key ? ($dir === 'desc' ? ' ▼' : ' ▲') : '';
    $href = htmlspecialchars($sheetQs(['sort' => $key, 'dir' => $next, 'page' => 1]), ENT_QUOTES);
    return '<a href="' . $href . '">' . ($key === 'sigs' ? 'Signed' : 'Name') . $arrow . '</a>';
};
$rowStart = (int)($pager['from'] ?? 1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf" content="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
  <title>Sign sheet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css?v=20261103m" rel="stylesheet">
  <style>
    .sig-sheet { min-width: <?= 220 + (count($days) * 120) ?>px; }
    .sig-sheet th a { color: inherit; text-decoration: none; white-space: nowrap; }
    .sig-sheet th a:hover { text-decoration: underline; }
    .sig-sheet th, .sig-sheet td { vertical-align: middle; }
    .sig-cell { min-width: 160px; height: 72px; }
    .sig-cell button {
      width: 100%;
      min-height: 64px;
      border: 1px dashed #cbd5e1;
      background: #fff;
      padding: 4px;
    }
    .sig-cell button:hover { border-color: #1a4480; }
    .sig-cell img { max-height: 56px; max-width: 100%; }
  </style>
</head>
<body>
<?php $activeNav = 'admin_signature_sheet'; require __DIR__ . '/partials/admin_nav.php'; ?>
<div class="container-fluid py-3">
  <p class="text-uppercase text-muted mb-1" style="letter-spacing:.2em;font-size:.75rem;">Operations</p>
  <h1 class="page-heading h4 mb-1">Sign sheet</h1>
  <p class="text-muted mb-3">
    <?= htmlspecialchars($eventName, ENT_QUOTES) ?>
    <?php if ($spanLabel !== ''): ?> · <?= htmlspecialchars($spanLabel, ENT_QUOTES) ?><?php endif; ?>
  </p>
  <p class="small text-muted">Click an empty cell to capture a signature. Saving it marks that person present for that day. Replicate copies their latest ink onto empty days.</p>
  <form class="toolbar-stack mb-3" method="get" action="">
    <input type="hidden" name="r" value="admin_signature_sheet">
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES) ?>">
    <input type="hidden" name="dir" value="<?= htmlspecialchars($dir, ENT_QUOTES) ?>">
    <?php if ($perChoice !== SignatureSheetTable::PER_DEFAULT): ?>
    <input type="hidden" name="per" value="<?= htmlspecialchars($perChoice, ENT_QUOTES) ?>">
    <?php endif; ?>
    <div>
      <label class="form-label small text-muted mb-1" for="sheet-q">Name</label>
      <input class="form-control form-control-sm" id="sheet-q" type="search" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES) ?>" placeholder="Search name" aria-label="Search name">
    </div>
    <div>
      <label class="form-label small text-muted mb-1" for="sheet-signed">Signatures</label>
      <select class="form-select form-select-sm" id="sheet-signed" name="signed" aria-label="Filter by signature count">
        <?php foreach (SignatureSheetTable::signedOptions($dayCount) as $opt): ?>
        <option value="<?= htmlspecialchars($opt['value'], ENT_QUOTES) ?>" <?= $signed === $opt['value'] ? 'selected' : '' ?>><?= htmlspecialchars($opt['label'], ENT_QUOTES) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-sm btn-outline-primary" type="submit">Apply</button>
    <button type="button" class="btn btn-sm btn-primary" id="sig-replicate-all">Replicate all missing</button>
  </form>
  <p class="swipe-hint mb-2">Swipe sideways to see each event day. Name stays pinned.</p>
  <div class="table-responsive table-modern table-sticky-first">
    <table class="table table-sm table-bordered align-middle sig-sheet table-sticky-2">
      <thead>
        <tr>
          <th rowspan="2" class="bg-light">#</th>
          <th rowspan="2" class="bg-light"><?= $sortHref('name') ?></th>
          <th rowspan="2" class="bg-light text-center"><?= $sortHref('sigs') ?></th>
          <th rowspan="2" class="bg-light"></th>
          <?php foreach ($days as $i => $day): ?>
            <th class="text-center">Day <?= (int)$i + 1 ?></th>
          <?php endforeach; ?>
        </tr>
        <tr>
          <?php foreach ($days as $day): ?>
            <th class="text-center small fw-normal"><?= htmlspecialchars(date('M j, Y', strtotime($day . ' 00:00:00') ?: time()), ENT_QUOTES) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php if ($rows === []): ?>
        <tr><td colspan="<?= 4 + count($days) ?>" class="text-center text-muted"><?= ($q !== '' || $signed !== 'all') ? 'No matching registrants' : 'No registrants found' ?></td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td><?= $rowStart + (int)$i ?></td>
          <td>
            <?= htmlspecialchars(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')), ENT_QUOTES) ?>
            <?php if ((int)($r['is_vip'] ?? 0) === 1): ?><span class="badge text-bg-warning ms-1">VIP</span><?php endif; ?>
          </td>
          <td class="text-center"><?= (int)($r['sig_count'] ?? 0) ?>/<?= (int)$dayCount ?></td>
          <td class="text-nowrap">
            <?php if ((int)($r['sig_count'] ?? 0) > 0 && (int)($r['sig_count'] ?? 0) < $dayCount): ?>
              <button type="button" class="btn btn-sm btn-outline-primary sig-replicate"
                      data-uuid="<?= htmlspecialchars((string)$r['uuid'], ENT_QUOTES) ?>">
                Replicate
              </button>
            <?php endif; ?>
          </td>
          <?php foreach ($days as $day): ?>
            <?php
              $cell = $r['cells'][$day] ?? null;
              $aid = (int)($cell['id'] ?? 0);
              $status = (string)($cell['status'] ?? '');
              $hasInk = $status === 'present' && trim((string)($cell['signature_path'] ?? '')) !== '';
            ?>
            <td class="sig-cell text-center p-1">
              <button type="button"
                      class="sig-open"
                      data-uuid="<?= htmlspecialchars((string)$r['uuid'], ENT_QUOTES) ?>"
                      data-date="<?= htmlspecialchars($day, ENT_QUOTES) ?>"
                      data-aid="<?= $aid ?>"
                      data-has-ink="<?= $hasInk ? '1' : '0' ?>"
                      data-name="<?= htmlspecialchars(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')), ENT_QUOTES) ?>">
                <?php if ($hasInk): ?>
                  <img src="<?= htmlspecialchars($scriptBase, ENT_QUOTES) ?>/signature.php?aid=<?= $aid ?>" alt="signature" loading="lazy" decoding="async">
                <?php else: ?>
                  <span class="text-muted small">Sign</span>
                <?php endif; ?>
              </button>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php
    $pagerQuery = [
        'r' => 'admin_signature_sheet',
        'sort' => $sort !== 'sigs' ? $sort : null,
        'dir' => ($sort === 'sigs' && $dir === 'desc') ? null : $dir,
        'signed' => $signed !== 'all' ? $signed : null,
        'q' => $q !== '' ? $q : null,
        'per' => $perChoice !== SignatureSheetTable::PER_DEFAULT ? $perChoice : null,
    ];
    $pagerKey = 'page';
    $pagerLabel = 'Sign sheet pages';
    $pagerPerOptions = SignatureSheetTable::PER_OPTIONS;
    $pagerPerDefault = SignatureSheetTable::PER_DEFAULT;
    $perChoice = $perChoice;
    require __DIR__ . '/partials/pagination.php';
  ?>
</div>
<div class="modal fade" id="sigModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Signature · <span id="sigWho"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-2" id="sigWhen"></p>
        <canvas id="sigCanvas" style="width:100%;height:240px;border:1px solid #ddd;touch-action:none"></canvas>
        <div class="mt-2 d-flex gap-2">
          <button class="btn btn-outline-secondary" id="sigClear" type="button">Clear</button>
          <button class="btn btn-primary" id="sigSave" type="button">Save</button>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1055">
  <div id="sigToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex"><div class="toast-body">Saved</div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>
  </div>
</div>
<?php if (!defined('ADMIN_BOOTSTRAP_JS')): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
(function(){
  let csrf = document.querySelector('meta[name="csrf"]').getAttribute('content');
  function rememberCsrf(token) {
    if (typeof token === 'string' && token !== '') {
      csrf = token;
      const meta = document.querySelector('meta[name="csrf"]');
      if (meta) meta.setAttribute('content', token);
    }
  }
  let sigPad, currentAid = 0, currentUuid = '', currentDate = '', replaceInk = false;
  const modalEl = document.getElementById('sigModal');
  const modal = new bootstrap.Modal(modalEl);
  document.querySelectorAll('.sig-open').forEach((btn) => {
    btn.addEventListener('click', () => {
      currentUuid = btn.getAttribute('data-uuid') || '';
      currentDate = btn.getAttribute('data-date') || '';
      currentAid = parseInt(btn.getAttribute('data-aid') || '0', 10);
      replaceInk = btn.getAttribute('data-has-ink') === '1' && currentAid > 0;
      document.getElementById('sigWho').textContent = btn.getAttribute('data-name') || '';
      document.getElementById('sigWhen').textContent = currentDate;
      modal.show();
    });
  });
  modalEl.addEventListener('shown.bs.modal', () => {
    const canvas = document.getElementById('sigCanvas');
    calibrateCanvas(canvas);
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    sigPad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,1)', minWidth: ratio, maxWidth: Math.max(2, ratio * 2) });
  });
  document.getElementById('sigClear').addEventListener('click', () => { if (sigPad) sigPad.clear(); });
  const allBtn = document.getElementById('sig-replicate-all');
  if (allBtn) {
    allBtn.addEventListener('click', () => {
      if (!confirm('Copy each person’s existing signature onto their empty days? People with no signature are skipped.')) {
        return;
      }
      allBtn.disabled = true;
      fetch('?r=admin_signature_sheet_replicate_all', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ csrf: csrf })
      }).then(r => r.json()).then(j => {
        rememberCsrf(j.csrf);
        const el = document.getElementById('sigToast');
        el.className = 'toast align-items-center ' + (j.ok ? 'text-bg-success' : 'text-bg-danger') + ' border-0';
        el.querySelector('.toast-body').textContent = j.ok
          ? ('Filled ' + (j.copied || 0) + ' missing day(s) for ' + (j.people || 0) + ' people')
          : (j.error === 'csrf' ? 'Session expired. Refresh and try again.' : (j.error || 'Error'));
        new bootstrap.Toast(el).show();
        if (j.ok) window.location.reload();
        else allBtn.disabled = false;
      }).catch(() => { allBtn.disabled = false; });
    });
  }
  document.querySelectorAll('.sig-replicate').forEach((btn) => {
    btn.addEventListener('click', () => {
      btn.disabled = true;
      fetch('?r=admin_signature_sheet_replicate', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ uuid: btn.getAttribute('data-uuid') || '', csrf: csrf })
      }).then(r => r.json()).then(j => {
        rememberCsrf(j.csrf);
        const el = document.getElementById('sigToast');
        el.className = 'toast align-items-center ' + (j.ok ? 'text-bg-success' : 'text-bg-danger') + ' border-0';
        el.querySelector('.toast-body').textContent = j.ok
          ? ('Copied to ' + (j.copied || 0) + ' day(s)')
          : (j.error === 'csrf' ? 'Session expired. Refresh and try again.' : (j.error || 'Error'));
        new bootstrap.Toast(el).show();
        if (j.ok) window.location.reload();
        else btn.disabled = false;
      }).catch(() => { btn.disabled = false; });
    });
  });
  document.getElementById('sigSave').addEventListener('click', () => {
    if (!sigPad || sigPad.isEmpty()) return;
    const data = sigPad.toDataURL('image/png');
    const body = replaceInk
      ? { aid: currentAid, signature: data, csrf: csrf }
      : { uuid: currentUuid, date: currentDate, signature: data, csrf: csrf };
    const route = replaceInk ? 'admin_signature_replace' : 'admin_signature_new';
    fetch('?r=' + route, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(body)
    }).then(r => r.json()).then(j => {
      rememberCsrf(j.csrf);
      const el = document.getElementById('sigToast');
      el.className = 'toast align-items-center ' + (j.ok ? 'text-bg-success' : 'text-bg-danger') + ' border-0';
      el.querySelector('.toast-body').textContent = j.ok ? 'Saved' : (j.error === 'csrf' ? 'Session expired. Refresh and try again.' : (j.error || 'Error'));
      new bootstrap.Toast(el).show();
      if (j.ok) window.location.reload();
    });
  });
  function calibrateCanvas(canvas) {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();
    canvas.width = Math.floor(rect.width * ratio);
    canvas.height = Math.floor(rect.height * ratio);
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);
  }
})();
</script>
</body>
</html>
