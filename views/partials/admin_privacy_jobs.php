<?php
declare(strict_types=1);

use App\Services\Paginator;
use App\Services\PrivacyJobTable;

$jobs = $jobs ?? [];
$jobPager = $jobPager ?? null;
$jobQ = (string)($jobQ ?? '');
$jobSort = (string)($jobSort ?? 'when');
$jobDir = (string)($jobDir ?? 'desc');
$jobPer = (string)($jobPer ?? PrivacyJobTable::PER_DEFAULT);
$token = function_exists('csrf_token') ? csrf_token() : '';

$privacyTab = (string)($privacyTab ?? 'jobs');
$noticeCounts = $noticeCounts ?? ['queued' => 0, 'failed' => 0, 'sent' => 0, 'skipped' => 0];
$jobCount = (int)($jobCount ?? 0);
$jobQs = static function (array $extra) use ($jobQ, $jobSort, $jobDir, $jobPer, $privacyTab): string {
    $query = [
        'r' => 'admin_privacy',
        'tab' => $privacyTab !== 'jobs' ? $privacyTab : null,
        'q' => $jobQ !== '' ? $jobQ : null,
        'sort' => $jobSort !== 'when' ? $jobSort : null,
        'dir' => ($jobSort === 'when' && $jobDir === 'desc') ? null : $jobDir,
        'per' => $jobPer !== PrivacyJobTable::PER_DEFAULT ? $jobPer : null,
        'page' => null,
    ];
    foreach ($extra as $key => $value) {
        $query[$key] = $value;
    }
    return Paginator::href($query, 'page', (int)($query['page'] ?? 1)) . '#privacy-jobs';
};
$sortTh = static function (string $key, string $label) use ($jobSort, $jobDir, $jobQs): string {
    $active = $jobSort === $key;
    $next = ($active && $jobDir === 'desc') ? 'asc' : 'desc';
    $arrow = $active ? ($jobDir === 'desc' ? ' ▼' : ' ▲') : '';
    $href = htmlspecialchars($jobQs(['sort' => $key, 'dir' => $next, 'page' => 1]), ENT_QUOTES);
    $cls = $active ? ' class="text-decoration-none fw-semibold"' : ' class="text-decoration-none text-body"';
    return '<a' . $cls . ' href="' . $href . '">' . htmlspecialchars($label, ENT_QUOTES) . $arrow . '</a>';
};
?>
<div id="privacy-jobs" class="pt-1">
  <?php
    $tabHref = static function (string $tab) use ($jobPer): string {
        $query = [
            'r' => 'admin_privacy',
            'tab' => $tab !== 'jobs' ? $tab : null,
            'per' => $jobPer !== PrivacyJobTable::PER_DEFAULT ? $jobPer : null,
        ];
        return Paginator::href($query, 'page', 1) . '#privacy-jobs';
    };
    $tabs = [
        'jobs' => 'Jobs (' . $jobCount . ')',
        'queued' => 'Queued (' . (int)$noticeCounts['queued'] . ')',
        'failed' => 'Failed (' . (int)$noticeCounts['failed'] . ')',
        'sent' => 'Sent (' . (int)$noticeCounts['sent'] . ')',
        'skipped' => 'Skipped (' . (int)$noticeCounts['skipped'] . ')',
    ];
  ?>
  <ul class="nav nav-tabs mb-3">
    <?php foreach ($tabs as $key => $label): ?>
    <li class="nav-item">
      <a class="nav-link<?= $privacyTab === $key ? ' active' : '' ?>" href="<?= htmlspecialchars($tabHref($key), ENT_QUOTES) ?>"><?= htmlspecialchars($label, ENT_QUOTES) ?></a>
    </li>
    <?php endforeach; ?>
  </ul>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <h2 class="h6 mb-0"><?= $privacyTab === 'jobs' ? 'Recent jobs' : ucfirst($privacyTab) . ' notices' ?></h2>
    <form class="d-flex gap-2" method="get" action="">
      <input type="hidden" name="r" value="admin_privacy">
      <?php if ($privacyTab !== 'jobs'): ?>
      <input type="hidden" name="tab" value="<?= htmlspecialchars($privacyTab, ENT_QUOTES) ?>">
      <?php endif; ?>
      <input type="hidden" name="sort" value="<?= htmlspecialchars($jobSort, ENT_QUOTES) ?>">
      <input type="hidden" name="dir" value="<?= htmlspecialchars($jobDir, ENT_QUOTES) ?>">
      <?php if ($jobPer !== PrivacyJobTable::PER_DEFAULT): ?>
      <input type="hidden" name="per" value="<?= htmlspecialchars($jobPer, ENT_QUOTES) ?>">
      <?php endif; ?>
      <input class="form-control form-control-sm" type="search" name="q" value="<?= htmlspecialchars($jobQ, ENT_QUOTES) ?>" placeholder="<?= $privacyTab === 'jobs' ? 'Search jobs' : 'Search notices' ?>" aria-label="Search" style="min-width:12rem">
      <button class="btn btn-sm btn-outline-primary">Search</button>
      <?php if ($jobQ !== ''): ?>
      <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($tabHref($privacyTab), ENT_QUOTES) ?>">Clear</a>
      <?php endif; ?>
    </form>
  </div>
  <div class="d-flex flex-wrap gap-2 mb-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="privacyJobsSelectAllBtn">Select all</button>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="privacyJobsUnselectAllBtn">Unselect all</button>
  </div>
  <form id="privacyJobsForm">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
    <div class="table-responsive table-modern table-sticky-first">
      <?php if ($privacyTab === 'jobs'): ?>
      <table class="table table-sm mb-0 align-middle">
        <thead>
          <tr>
            <th style="width:2rem">
              <input class="form-check-input" type="checkbox" id="privacyJobsSelectAll" aria-label="Select all on this page">
            </th>
            <th><?= $sortTh('id', '#') ?></th>
            <th><?= $sortTh('event', 'Event') ?></th>
            <th><?= $sortTh('status', 'Status') ?></th>
            <th><?= $sortTh('people', 'People') ?></th>
            <th><?= $sortTh('mailed', 'Mailed') ?></th>
            <th><?= $sortTh('skipped', 'Skipped') ?></th>
            <th><?= $sortTh('when', 'When') ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$jobs): ?>
          <tr><td colspan="9" class="text-center text-muted py-3"><?= $jobQ !== '' ? 'No jobs match this search.' : 'No purge jobs yet.' ?></td></tr>
          <?php endif; ?>
          <?php foreach ($jobs as $j): ?>
          <tr>
            <td>
              <input class="form-check-input js-privacy-job-tick" type="checkbox" name="job_ids[]" value="<?= (int)$j['id'] ?>">
            </td>
            <td><?= (int)$j['id'] ?></td>
            <td class="small">
              <?= htmlspecialchars((string)($j['event_name'] ?? ''), ENT_QUOTES) ?>
              <div class="text-muted">#<?= (int)$j['event_id'] ?></div>
            </td>
            <td><?= htmlspecialchars((string)$j['status'], ENT_QUOTES) ?></td>
            <td><?= (int)$j['participant_count'] ?></td>
            <td><?= (int)$j['mailed'] ?></td>
            <td><?= (int)$j['skipped_no_email'] ?></td>
            <td class="small"><?= htmlspecialchars((string)$j['created_at'], ENT_QUOTES) ?></td>
            <td class="small"><button type="button" class="btn btn-link btn-sm p-0 js-privacy-job-only" data-id="<?= (int)$j['id'] ?>">Select only</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <?php $notices = $notices ?? []; ?>
      <table class="table table-sm mb-0 align-middle">
        <thead>
          <tr>
            <th style="width:2rem">
              <input class="form-check-input" type="checkbox" id="privacyJobsSelectAll" aria-label="Select all on this page">
            </th>
            <th><?= $sortTh('id', '#') ?></th>
            <th><?= $sortTh('event', 'Event') ?></th>
            <th><?= $sortTh('kind', 'Kind') ?></th>
            <th><?= $sortTh('email', 'Email') ?></th>
            <th><?= $sortTh('status', 'Status') ?></th>
            <th>Error</th>
            <th><?= $sortTh('when', 'When') ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$notices): ?>
          <tr><td colspan="9" class="text-center text-muted py-3"><?= $jobQ !== '' ? 'No notices match this search.' : 'No ' . htmlspecialchars($privacyTab, ENT_QUOTES) . ' notices.' ?></td></tr>
          <?php endif; ?>
          <?php foreach ($notices as $n): ?>
          <tr>
            <td>
              <input class="form-check-input js-privacy-job-tick" type="checkbox" name="notice_ids[]" value="<?= (int)$n['id'] ?>">
            </td>
            <td><?= (int)$n['id'] ?></td>
            <td class="small"><?= htmlspecialchars((string)($n['event_name'] ?? ''), ENT_QUOTES) ?></td>
            <td class="small"><?= htmlspecialchars((string)($n['kind'] ?? ''), ENT_QUOTES) ?></td>
            <td class="small"><?= htmlspecialchars((string)($n['to_email'] ?? ''), ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars((string)($n['status'] ?? ''), ENT_QUOTES) ?></td>
            <td class="small text-danger"><?= htmlspecialchars((string)($n['error'] ?? ''), ENT_QUOTES) ?></td>
            <td class="small"><?= htmlspecialchars((string)($n['updated_at'] ?? $n['created_at'] ?? ''), ENT_QUOTES) ?></td>
            <td class="small"><button type="button" class="btn btn-link btn-sm p-0 js-privacy-job-only" data-id="<?= (int)$n['id'] ?>">Select only</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </form>
  <p class="text-muted small mb-0 mt-2">Select one row with Select only, or many with the checkboxes. Ticks apply on this page.</p>
  <?php
    $pager = $jobPager;
    $pagerQuery = [
        'r' => 'admin_privacy',
        'tab' => $privacyTab !== 'jobs' ? $privacyTab : null,
        'q' => $jobQ !== '' ? $jobQ : null,
        'sort' => $jobSort !== 'when' ? $jobSort : null,
        'dir' => ($jobSort === 'when' && $jobDir === 'desc') ? null : $jobDir,
        'per' => $jobPer !== PrivacyJobTable::PER_DEFAULT ? $jobPer : null,
    ];
    $pagerKey = 'page';
    $pagerLabel = 'Privacy jobs pages';
    $pagerPerOptions = PrivacyJobTable::PER_OPTIONS;
    $pagerPerDefault = PrivacyJobTable::PER_DEFAULT;
    $perChoice = $jobPer;
    $pagerHash = 'privacy-jobs';
    require __DIR__ . DIRECTORY_SEPARATOR . 'pagination.php';
    unset($pagerPerOptions, $pagerPerDefault, $pagerHash, $perChoice, $pagerLabel, $pagerKey, $pagerQuery, $pager);
  ?>
</div>
<script>
(function () {
  var form = document.getElementById('privacyJobsForm');
  if (!form) return;
  function ticks() {
    return Array.prototype.slice.call(form.querySelectorAll('.js-privacy-job-tick'));
  }
  function syncMaster() {
    var all = ticks();
    var master = document.getElementById('privacyJobsSelectAll');
    if (!master) return;
    var on = all.filter(function (el) { return el.checked; });
    master.checked = all.length > 0 && on.length === all.length;
    master.indeterminate = on.length > 0 && on.length < all.length;
  }
  function setAll(on) {
    ticks().forEach(function (el) { el.checked = on; });
    syncMaster();
  }
  document.getElementById('privacyJobsSelectAllBtn')?.addEventListener('click', function () { setAll(true); });
  document.getElementById('privacyJobsUnselectAllBtn')?.addEventListener('click', function () { setAll(false); });
  document.getElementById('privacyJobsSelectAll')?.addEventListener('change', function () { setAll(this.checked); });
  form.addEventListener('click', function (e) {
    var only = e.target.closest('.js-privacy-job-only');
    if (!only) return;
    e.preventDefault();
    var id = only.getAttribute('data-id');
    ticks().forEach(function (el) { el.checked = el.value === id; });
    syncMaster();
  });
  form.addEventListener('change', function (e) {
    if (e.target.classList && e.target.classList.contains('js-privacy-job-tick')) syncMaster();
  });
  syncMaster();
})();
</script>
