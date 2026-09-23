<?php
declare(strict_types=1);

use App\Services\AuthService;

/**
 * Plan#11: Certificate send monitor (All Father). KPIs, manual batches,
 * queue/resend actions, recent batches, and per-recipient status detail.
 */
$flash = $flash ?? null;
$scopeEventId = (int)($_GET['event_id'] ?? 0);
$statusFilter = (string)($_GET['status'] ?? 'all');
$activeNav = 'admin_coa_monitor';
$token = function_exists('csrf_token') ? csrf_token() : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Certificates</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'admin_nav.php'; ?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0">Certificates</h1>
    <?php $coaPage = 'monitor'; require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'coa_subnav.php'; ?>
  </div>

  <?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?> py-2"><?= htmlspecialchars($flash['message'], ENT_QUOTES) ?></div>
  <?php endif; ?>

  <div class="row g-2 mb-3">
    <?php foreach (['sent' => 'Sent', 'failed' => 'Failed', 'queued' => 'Queued', 'skipped' => 'Skipped', 'batches' => 'Batches'] as $key => $label): ?>
    <div class="col-6 col-md">
      <div class="card h-100"><div class="card-body py-2 text-center">
        <div class="h4 mb-0"><?= (int)($kpis[$key] ?? 0) ?></div>
        <div class="text-muted small text-uppercase" style="letter-spacing:.08em;"><?= $label ?></div>
        <div class="text-muted" style="font-size:.68rem;">
          <?= $key === 'queued' && $scopeEventId > 0
              ? htmlspecialchars($outboxHint !== '' ? $outboxHint : 'waiting + due', ENT_QUOTES)
              : (['sent' => 'emailed OK', 'failed' => 'generation or mail error', 'queued' => 'waiting (scheduled or retry)', 'skipped' => 'no email / cancelled', 'batches' => 'send runs'][$key] ?? '') ?>
        </div>
      </div></div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($scopeEventId > 0):
      $obBase = '?r=admin_coa_monitor&event_id=' . $scopeEventId;
      $obChips = ['all' => 'All', 'waiting' => 'Waiting', 'due' => 'Due', 'sent' => 'Sent', 'failed' => 'Failed', 'skipped' => 'Skipped'];
      $obLink = static function (string $status, int $page = 1) use ($obBase): string {
          return $obBase . '&outbox=' . $status . ($page > 1 ? '&obpage=' . $page : '');
      };
  ?>
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h6 mb-0">Outbox - every send for this event</h2>
    <div class="d-flex gap-1">
      <?php foreach ($obChips as $f => $label): ?>
      <a class="btn btn-sm <?= $outboxStatus === $f ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= $obLink($f) ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="table-responsive table-modern mb-4">
    <form method="post" action="?r=admin_coa_cancel_selected">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
      <input type="hidden" name="event_id" value="<?= $scopeEventId ?>">
      <table class="table table-sm align-middle mb-0">
        <thead><tr><th></th><th>Name</th><th>Agency</th><th>Email</th><th>Status</th><th>Scheduled / sent</th><th>Template</th><th>Error</th><th>Preview</th></tr></thead>
        <tbody>
          <?php if (!($outboxRows ?? [])): ?>
          <tr><td colspan="9" class="text-center text-muted py-3">No sends for this event yet. Compose a send below.</td></tr>
          <?php endif; ?>
          <?php foreach (($outboxRows ?? []) as $o): ?>
          <?php $isWaiting = ($o['status'] ?? '') === 'queued' && ($o['send_at'] ?? '') !== '' && strtotime((string)$o['send_at']) > time(); ?>
          <tr>
            <td><?php if (($o['status'] ?? '') === 'queued'): ?><input class="form-check-input" type="checkbox" name="send_ids[]" value="<?= (int)$o['id'] ?>"><?php endif; ?></td>
            <td class="small"><?= htmlspecialchars(trim((string)($o['first_name'] ?? '') . ' ' . (string)($o['last_name'] ?? '')), ENT_QUOTES) ?></td>
            <td class="small"><?= htmlspecialchars((string)($o['agency'] ?? ''), ENT_QUOTES) ?></td>
            <td class="small"><?= htmlspecialchars((string)($o['email'] ?? ''), ENT_QUOTES) ?></td>
            <td>
              <?php $oStatus = (string)($o['status'] ?? ''); ?>
              <span class="badge text-bg-<?= ['sent' => 'success', 'failed' => 'danger', 'queued' => ($isWaiting ? 'info' : 'secondary'), 'skipped' => 'light'][$oStatus] ?? 'light' ?>">
                <?= $oStatus === 'queued' ? ($isWaiting ? 'waiting' : 'due') : htmlspecialchars($oStatus, ENT_QUOTES) ?>
              </span>
            </td>
            <td class="small"><?= htmlspecialchars(($o['send_at'] ?? null) !== null ? (string)$o['send_at'] : (string)($o['updated_at'] ?? ''), ENT_QUOTES) ?></td>
            <td class="small"><?= htmlspecialchars((string)($o['template_name'] ?? '') !== '' ? (string)$o['template_name'] : 'Event settings', ENT_QUOTES) ?></td>
            <td class="small text-danger"><?= htmlspecialchars((string)($o['error'] ?? ''), ENT_QUOTES) ?></td>
            <td><a class="btn btn-sm btn-outline-secondary py-0" href="?r=admin_coa_preview&send_id=<?= (int)$o['id'] ?>" target="_blank" rel="noopener">Preview</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="d-flex justify-content-between align-items-center mt-2">
        <small class="text-muted"><?= $outboxTotal ?> send(s)</small>
        <?php if ($outboxStatus === 'waiting'): ?>
        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel the ticked waiting rows? No mail will be sent.');">Cancel selected</button>
        <?php endif; ?>
      </div>
    </form>
    <?php if ($outboxPages > 1): ?>
    <nav class="mt-2"><ul class="pagination pagination-sm mb-0">
      <?php for ($p = 1; $p <= $outboxPages; $p++): ?>
      <li class="page-item <?= $p === $outboxPage ? 'active' : '' ?>"><a class="page-link" href="<?= $obLink($outboxStatus, $p) ?>"><?= $p ?></a></li>
      <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php $monitorTemplates = $templates ?? []; $monitorSignatories = $signatories ?? []; ?>
  <div class="card mb-3"><div class="card-body">
    <h2 class="h6 mb-2">Compose send</h2>
    <form method="get" action="?r=admin_coa_monitor" class="row g-2 align-items-end">
      <input type="hidden" name="r" value="admin_coa_monitor">
      <input type="hidden" name="compose" value="1">
      <div class="col-12 col-md-3">
        <label class="form-label small mb-1">Event</label>
        <select name="compose_event_id" class="form-select form-select-sm" required>
          <?php foreach (($events ?? []) as $ev): ?>
          <option value="<?= (int)$ev['id'] ?>" <?= $composeEventId === (int)$ev['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$ev['name'], ENT_QUOTES) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label small mb-1">Attendance date</label>
        <input type="date" name="compose_date" class="form-control form-control-sm" value="<?= htmlspecialchars($composeDate, ENT_QUOTES) ?>" required>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label small mb-1">Template</label>
        <select name="template" class="form-select form-select-sm">
          <option value="0">Use event CoA settings</option>
          <?php foreach ($monitorTemplates as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= $composeTemplateId === (int)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$t['name'], ENT_QUOTES) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-2">
        <button class="btn btn-sm btn-outline-primary w-100">Load attendees</button>
      </div>
    </form>

    <?php if ($composeEventId > 0): ?>
    <?php $composeAttendees = $composeAttendees ?? []; ?>
    <form method="post" action="?r=admin_coa_send_selected" class="mt-3">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
      <input type="hidden" name="event_id" value="<?= $composeEventId ?>">
      <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($composeDate, ENT_QUOTES) ?>">
      <input type="hidden" name="template_id" value="<?= $composeTemplateId ?>">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-2">
          <thead><tr><th></th><th>Name</th><th>Agency</th><th>Email</th><th>Last CoA status</th></tr></thead>
          <tbody>
            <?php if (!count($composeAttendees)): ?>
            <tr><td colspan="5" class="text-center text-muted py-2">No attendees with attendance on <?= htmlspecialchars($composeDate, ENT_QUOTES) ?>.</td></tr>
            <?php endif; ?>
            <?php foreach ($composeAttendees as $a): ?>
            <tr>
              <td>
                <input class="form-check-input" type="checkbox" name="participant_ids[]" value="<?= (int)$a['id'] ?>"
                  <?= (string)$a['email'] === '' ? 'disabled' : 'checked' ?>>
              </td>
              <td class="small"><?= htmlspecialchars((string)$a['name'], ENT_QUOTES) ?></td>
              <td class="small"><?= htmlspecialchars((string)$a['agency'], ENT_QUOTES) ?></td>
              <td class="small"><?= htmlspecialchars((string)$a['email'], ENT_QUOTES) ?></td>
              <td class="small"><?= htmlspecialchars((string)($a['last_status'] ?? 'none'), ENT_QUOTES) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1">Schedule (optional, Asia/Manila)</label>
          <input type="datetime-local" name="send_at" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-8 d-flex gap-2">
          <button class="btn btn-primary btn-sm" name="do" value="send">Send selected now</button>
          <button class="btn btn-outline-primary btn-sm" name="do" value="schedule">Schedule selected</button>
        </div>
      </div>
    </form>
    <?php endif; ?>
  </div></div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
      <div class="card h-100"><div class="card-body">
        <h2 class="h6">Send new (manual batch)</h2>
        <p class="text-muted small mb-2">Generates and emails Certificates for attendees of the chosen date who do not have a successful send yet.</p>
        <form method="post" action="?r=admin_coa_send_new" class="row g-2 align-items-end">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
          <div class="col-12 col-md-5">
            <label class="form-label small mb-1">Event</label>
            <select name="event_id" class="form-select form-select-sm" required>
              <?php foreach (($events ?? []) as $ev): ?>
              <option value="<?= (int)$ev['id'] ?>" <?= $scopeEventId === (int)$ev['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$ev['name'], ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label small mb-1">Attendance date</label>
            <input type="date" name="attendance_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-12 col-md-3">
            <button class="btn btn-primary btn-sm w-100">Send new</button>
          </div>
        </form>
      </div></div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100"><div class="card-body">
        <h2 class="h6">Resend queue</h2>
        <p class="text-muted small mb-2">Queue the failed sends, then process up to 50 queued rows per run.</p>
        <form method="post" action="?r=admin_coa_queue_failed" class="d-inline">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
          <input type="hidden" name="event_id" value="<?= $scopeEventId > 0 ? $scopeEventId : '' ?>">
          <button class="btn btn-sm btn-outline-warning">Queue failed</button>
        </form>
        <form method="post" action="?r=admin_coa_resend_queued" class="d-inline">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
          <input type="hidden" name="event_id" value="<?= $scopeEventId > 0 ? $scopeEventId : '' ?>">
          <button class="btn btn-sm btn-outline-primary">Resend queued (max 50)</button>
        </form>
      </div></div>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h6 mb-0">Recent batches</h2>
    <form method="get" action="?r=admin_coa_monitor" class="d-flex gap-2 align-items-center">
      <label class="small text-muted mb-0">Scope</label>
      <select name="event_id" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">All events</option>
        <?php foreach (($events ?? []) as $ev): ?>
        <option value="<?= (int)$ev['id'] ?>" <?= $scopeEventId === (int)$ev['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$ev['name'], ENT_QUOTES) ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button class="btn btn-sm btn-outline-secondary">Apply</button></noscript>
    </form>
  </div>
  <div class="table-responsive table-modern mb-4">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>#</th><th>When</th><th>Inclusive date</th><th>Signatory</th><th>Source</th><th>Sent</th><th>Failed</th><th>Queued</th><th>Skipped</th><th></th></tr></thead>
      <tbody>
        <?php if (!($batches ?? [])): ?>
        <tr><td colspan="10" class="text-center text-muted py-3">No batches yet. They appear after a scan with CoA enabled, or a manual send.</td></tr>
        <?php endif; ?>
        <?php foreach (($batches ?? []) as $b): ?>
        <tr>
          <td><?= (int)$b['id'] ?></td>
          <td class="small"><?= htmlspecialchars((string)$b['created_at'], ENT_QUOTES) ?></td>
          <td class="small"><?= htmlspecialchars((string)$b['inclusive_date'], ENT_QUOTES) ?></td>
          <td class="small"><?= htmlspecialchars((string)($b['signatory_name'] ?? ''), ENT_QUOTES) ?></td>
          <td class="small"><?= htmlspecialchars((string)($b['source'] ?? ''), ENT_QUOTES) ?></td>
          <td><span class="badge text-bg-success"><?= (int)$b['sent_count'] ?></span></td>
          <td><span class="badge text-bg-danger"><?= (int)$b['failed_count'] ?></span></td>
          <td><span class="badge text-bg-secondary"><?= (int)$b['queued_count'] ?></span></td>
          <td><span class="badge text-bg-light text-dark"><?= (int)$b['skipped_count'] ?></span></td>
          <td>
            <a class="btn btn-sm btn-outline-primary py-0" href="?r=admin_coa_monitor&batch_id=<?= (int)$b['id'] ?>&event_id=<?= (int)$b['event_id'] ?>">Open</a>
            <?php if ((int)$b['queued_count'] > 0 && (string)($b['source'] ?? '') === 'scheduled'): ?>
            <form method="post" action="?r=admin_coa_cancel" class="d-inline mt-1" onsubmit="return confirm('Cancel this scheduled batch? Queued rows will be removed and no mail will be sent.');">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
              <input type="hidden" name="batch_id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-sm btn-outline-danger py-0">Cancel</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (($batchPages ?? 1) > 1): ?>
  <nav>
    <ul class="pagination">
      <?php for ($p = 1; $p <= (int)$batchPages; $p++): ?>
        <li class="page-item <?= $p === (int)($batchPage ?? 1) ? 'active' : '' ?>">
          <a class="page-link" href="?r=admin_coa_monitor&bpage=<?= $p ?><?= $scopeEventId > 0 ? '&event_id=' . $scopeEventId : '' ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>

  <?php if (!empty($batchDetail)): ?>
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h6 mb-0">
      Batch #<?= (int)$batchDetail['id'] ?> - <?= htmlspecialchars((string)$batchDetail['event_name_snapshot'], ENT_QUOTES) ?>
      <span class="text-muted">at <?= htmlspecialchars((string)$batchDetail['venue_snapshot'], ENT_QUOTES) ?></span>
    </h2>
    <div class="d-flex gap-2">
      <?php if ((int)($batchDetail['queued_left'] ?? 0) > 0): ?>
      <form method="post" action="?r=admin_coa_cancel" class="d-inline">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
        <input type="hidden" name="batch_id" value="<?= (int)$batchDetail['id'] ?>">
        <button class="btn btn-sm btn-outline-danger">Cancel queued</button>
      </form>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-secondary" href="?r=admin_coa_monitor<?= $scopeEventId > 0 ? '&event_id=' . $scopeEventId : '' ?>">Close</a>
    </div>
  </div>
  <p class="text-muted small mb-2">
    Inclusive date: <?= htmlspecialchars((string)$batchDetail['inclusive_date'], ENT_QUOTES) ?>
    - Issued: <?= htmlspecialchars((string)$batchDetail['created_at'], ENT_QUOTES) ?>
    - Signatory: <?= htmlspecialchars((string)($batchDetail['signatory_name'] ?? ''), ENT_QUOTES) ?>
  </p>
  <div class="d-flex gap-1 mb-2">
    <?php foreach (['all', 'sent', 'failed', 'queued', 'skipped'] as $f): ?>
    <a class="btn btn-sm <?= $statusFilter === $f ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?r=admin_coa_monitor&batch_id=<?= (int)$batchDetail['id'] ?>&status=<?= $f ?>&event_id=<?= (int)$batchDetail['event_id'] ?>&bpage=<?= (int)($batchPage ?? 1) ?>"><?= ucfirst($f) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="table-responsive table-modern">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>#</th><th>Name</th><th>Agency</th><th>Email</th><th>Status</th><th>Error</th><th>Preview</th></tr></thead>
      <tbody>
        <?php if (!($recipients ?? [])): ?>
        <tr><td colspan="7" class="text-center text-muted py-3">No recipients match this filter.</td></tr>
        <?php endif; ?>
        <?php foreach (($recipients ?? []) as $s): ?>
        <tr>
          <td><?= (int)$s['id'] ?></td>
          <td class="small"><?= htmlspecialchars(trim((string)($s['first_name'] ?? '') . ' ' . (string)($s['last_name'] ?? '')), ENT_QUOTES) ?></td>
          <td class="small"><?= htmlspecialchars((string)($s['agency'] ?? ''), ENT_QUOTES) ?></td>
          <td class="small"><?= htmlspecialchars((string)($s['email'] ?? ''), ENT_QUOTES) ?></td>
          <td><span class="badge text-bg-<?= ['sent' => 'success', 'failed' => 'danger', 'queued' => 'secondary', 'skipped' => 'light'][$s['status']] ?? 'light' ?>"><?= htmlspecialchars((string)$s['status'], ENT_QUOTES) ?></span></td>
          <td class="small text-danger"><?= htmlspecialchars((string)($s['error'] ?? ''), ENT_QUOTES) ?></td>
          <td><a class="btn btn-sm btn-outline-secondary py-0" href="?r=admin_coa_preview&send_id=<?= (int)$s['id'] ?>" target="_blank" rel="noopener">Preview</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (($recipPages ?? 1) > 1): ?>
  <nav>
    <ul class="pagination mt-2">
      <?php for ($p = 1; $p <= (int)$recipPages; $p++): ?>
        <li class="page-item <?= $p === (int)($recipPage ?? 1) ? 'active' : '' ?>">
          <a class="page-link" href="?r=admin_coa_monitor&batch_id=<?= (int)$batchDetail['id'] ?>&status=<?= htmlspecialchars($statusFilter, ENT_QUOTES) ?>&event_id=<?= (int)$batchDetail['event_id'] ?>&page=<?= $p ?>&bpage=<?= (int)($batchPage ?? 1) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
