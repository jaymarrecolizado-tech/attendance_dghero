<?php
declare(strict_types=1);
$token = function_exists('csrf_token') ? csrf_token() : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
  <style>
    img.sig { max-height: 64px; border: 1px solid #ddd; background: #fff; }
    .kpi-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: default;
    }
    .kpi-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
    }
    .kpi-card .h3, .kpi-card .h5 {
      transition: transform 0.3s ease;
    }
    #kpi-today-count, #kpi-attendance-rate, #kpi-recent-count {
      transition: transform 0.3s ease;
    }
  </style>
</head>
<body>
<meta name="csrf" content="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
<?php $activeNav = 'admin_attendance'; require __DIR__ . '/partials/admin_nav.php'; ?>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5">Attendance</h1>
    <div class="btn-group">
      <a class="btn btn-outline-secondary btn-sm" href="?r=admin_registrants">Registrants</a>
      <a class="btn btn-outline-secondary btn-sm" href="?r=admin_logout">Logout</a>
    </div>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?r=register">Home</a></li>
      <li class="breadcrumb-item"><a href="?r=admin_registrants">Admin</a></li>
      <li class="breadcrumb-item active" aria-current="page">Attendance</li>
    </ol>
  </nav>
  
  <!-- Date Selector for KPI Dashboard -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-12 col-md-6">
          <label class="form-label fw-bold mb-2">📅 Select Date to View Attendance</label>
          <div class="input-group">
            <input type="date" id="kpiDateSelector" class="form-control form-control-lg" value="<?= htmlspecialchars($date ?? date('Y-m-d'), ENT_QUOTES) ?>">
            <button class="btn btn-primary" type="button" id="kpiDateGo">Go</button>
            <button class="btn btn-outline-secondary" type="button" id="kpiDateToday">Today</button>
          </div>
          <small class="text-muted">View attendance statistics for any date</small>
        </div>
        <div class="col-12 col-md-6 text-md-end mt-3 mt-md-0">
          <div class="badge bg-info fs-6 p-2">
            Viewing: <strong id="kpiSelectedDate"><?= htmlspecialchars($selectedDate ?? date('Y-m-d'), ENT_QUOTES) ?></strong>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- KPI Dashboard -->
  <div class="row g-3 mb-4" id="kpiDashboard">
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100 kpi-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="card-body text-white">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-white-50 small mb-1">In Venue</div>
              <div class="h3 mb-0 fw-bold" id="kpi-today-count"><?= htmlspecialchars((string)($selectedDateCount ?? 0), ENT_QUOTES) ?></div>
              <div class="small mt-1" id="kpi-date-display"><?= htmlspecialchars($selectedDate ?? date('Y-m-d'), ENT_QUOTES) ?></div>
            </div>
            <div class="fs-1 opacity-50">👥</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100 kpi-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <div class="card-body text-white">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-white-50 small mb-1">Attendance Rate</div>
              <div class="h3 mb-0 fw-bold" id="kpi-attendance-rate"><?= htmlspecialchars((string)($attendanceRate ?? 0), ENT_QUOTES) ?>%</div>
              <div class="small mt-1"><?= htmlspecialchars((string)($selectedDateCount ?? 0), ENT_QUOTES) ?> of <?= htmlspecialchars((string)($totalRegistered ?? 0), ENT_QUOTES) ?></div>
            </div>
            <div class="fs-1 opacity-50">📊</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100 kpi-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
        <div class="card-body text-white">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-white-50 small mb-1">Last Hour</div>
              <div class="h3 mb-0 fw-bold" id="kpi-recent-count"><?= htmlspecialchars((string)($recentCount ?? 0), ENT_QUOTES) ?></div>
              <div class="small mt-1" id="kpi-recent-label"><?= ($selectedDate ?? date('Y-m-d')) === date('Y-m-d') ? 'Recent sign-ins' : 'N/A (past date)' ?></div>
            </div>
            <div class="fs-1 opacity-50">⏰</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100 kpi-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
        <div class="card-body text-white">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-white-50 small mb-1">Peak Hour</div>
              <div class="h5 mb-0 fw-bold" id="kpi-peak-hour"><?= htmlspecialchars($peakHourText ?? 'N/A', ENT_QUOTES) ?></div>
              <div class="small mt-1" id="kpi-peak-label">Busiest hour</div>
            </div>
            <div class="fs-1 opacity-50">📈</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Manual Attendance Section -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">📝 Manual Attendance</h5>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-8">
          <label class="form-label fw-bold">Search Attendee</label>
          <div class="input-group">
            <input type="text" id="searchAttendee" class="form-control" placeholder="Type name or email to search..." autocomplete="off">
            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
              <span>✕</span>
            </button>
          </div>
          <div id="searchResults" class="mt-2" style="max-height: 300px; overflow-y: auto; display: none;">
            <div class="list-group" id="searchResultsList"></div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label fw-bold">Attendance Date</label>
          <input type="date" id="manualAttendanceDate" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
      </div>
    </div>
  </div>
  
  <form method="get" action=".">
    <input type="hidden" name="r" value="admin_attendance">
    <div class="row g-2">
      <div class="col-12 col-md-3"><input name="date" type="date" value="<?= htmlspecialchars($date ?? date('Y-m-d'), ENT_QUOTES) ?>" class="form-control"></div>
      <div class="col-12 col-md-3"><input name="agency" list="agencyList" value="<?= htmlspecialchars($agency??'', ENT_QUOTES) ?>" placeholder="Agency" class="form-control"></div>
      <div class="col-12 col-md-3"><input name="name" value="<?= htmlspecialchars($name??'', ENT_QUOTES) ?>" placeholder="Name" class="form-control"></div>
      <div class="col-12 col-md-3">
        <button id="filterBtn" class="btn btn-primary w-100" type="submit">
          <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display:none"></span>
          Filter
        </button>
      </div>
    </div>
  </form>
  <datalist id="agencyList">
    <?php // we reuse registrants agencies if not passed explicitly ?>
    <?php if (isset($rows) && is_array($rows)) { $agSeen=[]; foreach ($rows as $r) { if (!empty($r['agency'])) { $agSeen[$r['agency']]=true; } } foreach (array_keys($agSeen) as $a): ?>
      <option value="<?= htmlspecialchars($a, ENT_QUOTES) ?>"></option>
    <?php endforeach; } ?>
  </datalist>
  <div class="mt-3">
    <div class="table-responsive table-modern">
      <table class="table table-sm align-middle">
        <thead>
          <tr><th>#</th><th>Date</th><th>Time</th><th>Name</th><th>Agency</th><th>UUID</th><th>Signature</th></tr>
        </thead>
        <tbody>
        <?php if (!($rows??[])) : ?>
          <tr><td colspan="7" class="text-center text-muted">No attendance records found</td></tr>
        <?php endif; ?>
        <?php foreach (($rows??[]) as $i=>$r): ?>
          <tr>
            <td><?= ($i+1) + (($page-1)*20) ?></td>
            <td><?= htmlspecialchars($r['attendance_date'], ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($r['time_in'], ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name'], ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($r['agency']??'', ENT_QUOTES) ?></td>
            <td><code><?= htmlspecialchars($r['uuid'], ENT_QUOTES) ?></code></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/signature.php?aid=<?= (int)$r['id'] ?>" target="_blank">Download</a>
              <img class="sig ms-2" src="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/signature.php?aid=<?= (int)$r['id'] ?>" alt="sig">
              <button class="btn btn-sm btn-outline-secondary ms-2" data-aid="<?= (int)$r['id'] ?>" data-uuid="<?= htmlspecialchars($r['uuid'], ENT_QUOTES) ?>" data-bs-toggle="modal" data-bs-target="#sigModal">Replace</button>
              <button class="btn btn-sm btn-outline-success ms-1" data-new="true" data-uuid="<?= htmlspecialchars($r['uuid'], ENT_QUOTES) ?>" data-bs-toggle="modal" data-bs-target="#sigModal">New</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <nav>
    <ul class="pagination">
      <?php for ($p=1; $p<=($pages??1); $p++): ?>
        <li class="page-item <?= $p==($page??1)?'active':'' ?>"><a class="page-link" href="?r=admin_attendance&page=<?= $p ?>&date=<?= urlencode($date??'') ?>&agency=<?= urlencode($agency??'') ?>&name=<?= urlencode($name??'') ?>"><?= $p ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>
<div class="modal fade" id="sigModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Signature</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <canvas id="sigCanvas" style="width:100%;height:240px;border:1px solid #ddd;touch-action:none"></canvas>
        <div class="mt-2 d-flex gap-2">
          <input type="date" id="sigDate" class="form-control" style="max-width:180px" value="<?= date('Y-m-d') ?>">
          <button class="btn btn-outline-secondary" id="sigClear">Clear</button>
          <button class="btn btn-primary" id="sigSave">Save</button>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="manualAttendanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Mark Attendance - <span id="manualAttendeeName"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold">Participant Signature (Optional)</label>
          <canvas id="manualSigCanvas" style="width:100%;height:200px;border:1px solid #ddd;touch-action:none;background:#fff;"></canvas>
          <div class="mt-2">
            <button class="btn btn-outline-secondary btn-sm" id="manualSigClear">Clear</button>
            <button class="btn btn-outline-info btn-sm" id="manualSigSkip">Skip Signature</button>
          </div>
        </div>
        <div class="alert alert-info">
          <small>You can capture the participant's signature or skip to mark attendance without a signature.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="manualAttendanceSubmit">Mark Attendance</button>
      </div>
    </div>
  </div>
</div>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1055">
  <div id="sigToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">Saved</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
(function(){
  const csrf = document.querySelector('meta[name="csrf"]').getAttribute('content');
  let sigPad; let currentAid=null; let currentUuid=null; let isNew=false;
  const modal = document.getElementById('sigModal');
  modal.addEventListener('shown.bs.modal', (e) => {
    const btn = e.relatedTarget;
    const canvas = document.getElementById('sigCanvas');
    calibrateCanvas(canvas);
    const ratio = Math.max(window.devicePixelRatio||1,1);
    sigPad = new SignaturePad(canvas, { backgroundColor:'rgba(255,255,255,1)', minWidth: ratio, maxWidth: Math.max(2, ratio*2) });
    currentAid = btn.getAttribute('data-aid');
    currentUuid = btn.getAttribute('data-uuid');
    isNew = btn.hasAttribute('data-new');
  });
  document.getElementById('sigClear').addEventListener('click', ()=>{ if(sigPad) sigPad.clear(); });
  document.getElementById('sigSave').addEventListener('click', ()=>{
    if (!sigPad || sigPad.isEmpty()) return;
    const data = sigPad.toDataURL('image/png');
    if (!isNew) {
      fetch('?r=admin_signature_replace', { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify({ aid: parseInt(currentAid||'0'), signature: data }) })
        .then(r=>r.json()).then(j=>{ showToast(j.ok, j.error); if (j.ok) refreshThumb(); });
    } else {
      const dInput = document.getElementById('sigDate');
      let d = dInput.value || '<?= date('Y-m-d') ?>';
      if (d && !/^\d{4}-\d{2}-\d{2}$/.test(d)) {
        const asDate = dInput.valueAsDate;
        if (asDate && !isNaN(asDate.getTime())) {
          d = new Date(asDate.getTime() - asDate.getTimezoneOffset()*60000).toISOString().slice(0,10);
        } else {
          const m = d.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
          if (m) d = `${m[3]}-${m[2]}-${m[1]}`;
        }
      }
      fetch('?r=admin_signature_new', { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify({ uuid: currentUuid, date: d, signature: data }) })
        .then(r=>r.json()).then(j=>{ showToast(j.ok, j.error); if (j.ok) window.location.reload(); });
    }
  });
  function showToast(ok, msg){ const el=document.getElementById('sigToast'); el.className='toast align-items-center '+(ok?'text-bg-success':'text-bg-danger')+' border-0'; el.querySelector('.toast-body').textContent = ok ? 'Saved' : (msg||'Error'); new bootstrap.Toast(el).show(); }
  function refreshThumb(){ const img=document.querySelector('img[src^="signature.php?aid='+currentAid+'"]'); if(img){ img.src='signature.php?aid='+currentAid+'&t='+(Date.now()); } }
  function calibrateCanvas(canvas){ const ratio=Math.max(window.devicePixelRatio||1,1); const data=sigPad?sigPad.toData():null; const rect=canvas.getBoundingClientRect(); canvas.width=Math.floor(rect.width*ratio); canvas.height=Math.floor(rect.height*ratio); const ctx=canvas.getContext('2d'); ctx.scale(ratio, ratio); if(sigPad){ sigPad.clear(); if(data&&data.length) sigPad.fromData(data); } }
  window.addEventListener('resize', ()=>{ const canvas=document.getElementById('sigCanvas'); if(canvas && sigPad){ calibrateCanvas(canvas); } });
})();
</script>
<script>
(function(){
  const form = document.querySelector('form[action="."]');
  const btn = document.getElementById('filterBtn');
  const spin = btn?.querySelector('.spinner-border');
  if (form && btn && spin) {
    form.addEventListener('submit', ()=>{ spin.style.display='inline-block'; btn.setAttribute('disabled','disabled'); });
  }
})();
</script>
<script>
// Date Selector and KPI Dashboard
(function(){
  const kpiDashboard = document.getElementById('kpiDashboard');
  const dateSelector = document.getElementById('kpiDateSelector');
  const dateGoBtn = document.getElementById('kpiDateGo');
  const dateTodayBtn = document.getElementById('kpiDateToday');
  const selectedDateDisplay = document.getElementById('kpiSelectedDate');
  
  if (!kpiDashboard) return;
  
  let currentDate = dateSelector ? dateSelector.value : new Date().toISOString().split('T')[0];
  
  // Apply KPI data to dashboard cards
  function applyKpiData(data, date = currentDate) {
    const todayCountEl = document.getElementById('kpi-today-count');
    const attendanceRateEl = document.getElementById('kpi-attendance-rate');
    const recentCountEl = document.getElementById('kpi-recent-count');
    const peakHourEl = document.getElementById('kpi-peak-hour');
    const dateDisplayEl = document.getElementById('kpi-date-display');
    const recentLabelEl = document.getElementById('kpi-recent-label');

    if (todayCountEl) {
      const oldVal = parseInt(todayCountEl.textContent) || 0;
      todayCountEl.textContent = data.dateCount || 0;
      if (oldVal !== data.dateCount) {
        todayCountEl.style.transform = 'scale(1.1)';
        setTimeout(() => { todayCountEl.style.transform = 'scale(1)'; }, 300);
      }
    }
    if (attendanceRateEl) {
      attendanceRateEl.textContent = (data.attendanceRate || 0) + '%';
      const parent = attendanceRateEl.closest('.card-body');
      if (parent) {
        const detail = parent.querySelector('.small.mt-1');
        if (detail) detail.textContent = (data.dateCount || 0) + ' of ' + (data.totalRegistered || 0);
      }
    }
    if (recentCountEl) {
      recentCountEl.textContent = data.recentCount || 0;
    }
    if (peakHourEl) {
      peakHourEl.textContent = data.peakHourText || 'N/A';
    }
    if (dateDisplayEl) {
      dateDisplayEl.textContent = data.selectedDate || date;
    }
    if (selectedDateDisplay) {
      selectedDateDisplay.textContent = data.selectedDate || date;
    }
    if (recentLabelEl) {
      const isToday = (data.selectedDate || date) === new Date().toISOString().split('T')[0];
      recentLabelEl.textContent = isToday ? 'Recent sign-ins' : 'N/A (past date)';
    }

    currentDate = data.selectedDate || date;
  }

  // Update KPIs for selected date
  function updateKPIs(date = currentDate) {
    const url = '?r=admin_attendance_kpi' + (date ? '&date=' + encodeURIComponent(date) : '');
    fetch(url)
      .then(r => r.json())
      .then(data => applyKpiData(data, date))
      .catch(err => {
        console.error('Failed to update KPIs:', err);
      });
  }
  
  // Date selector handlers
  if (dateGoBtn) {
    dateGoBtn.addEventListener('click', () => {
      const selectedDate = dateSelector.value;
      if (selectedDate) {
        window.location.href = '?r=admin_attendance&date=' + encodeURIComponent(selectedDate);
      }
    });
  }
  
  if (dateTodayBtn) {
    dateTodayBtn.addEventListener('click', () => {
      const today = new Date().toISOString().split('T')[0];
      window.location.href = '?r=admin_attendance&date=' + encodeURIComponent(today);
    });
  }
  
  // Sync date selector with filter form date input
  const filterDateInput = document.querySelector('form[action="."] input[name="date"]');
  if (filterDateInput && dateSelector) {
    // Sync filter form date to date selector
    filterDateInput.addEventListener('change', () => {
      dateSelector.value = filterDateInput.value;
      updateKPIs(filterDateInput.value);
    });
  }
  
  // Update on date change (without page reload for KPI, but reload for table)
  if (dateSelector) {
    dateSelector.addEventListener('change', () => {
      const selectedDate = dateSelector.value;
      if (selectedDate) {
        // Sync with filter form
        if (filterDateInput) {
          filterDateInput.value = selectedDate;
        }
        // Update KPIs immediately
        updateKPIs(selectedDate);
        // Reload page to update attendance table
        setTimeout(() => {
          window.location.href = '?r=admin_attendance&date=' + encodeURIComponent(selectedDate);
        }, 500);
      }
    });
  }
  
  // Live updates via SSE when viewing today; polling fallback otherwise
  const isToday = currentDate === new Date().toISOString().split('T')[0];
  updateKPIs(currentDate);

  let kpiSource = null;
  let pollFallback = null;

  function startKpiStream(date) {
    if (kpiSource) {
      kpiSource.close();
      kpiSource = null;
    }
    if (typeof EventSource === 'undefined') {
      return false;
    }
    const streamUrl = '?r=admin_attendance_kpi_stream&date=' + encodeURIComponent(date);
    kpiSource = new EventSource(streamUrl);
    kpiSource.onmessage = (event) => {
      try {
        applyKpiData(JSON.parse(event.data), date);
      } catch (err) {
        console.error('Failed to parse KPI stream:', err);
      }
    };
    kpiSource.onerror = () => {
      if (kpiSource) {
        kpiSource.close();
        kpiSource = null;
      }
      if (!pollFallback) {
        pollFallback = setInterval(() => updateKPIs(date), 30000);
      }
    };
    return true;
  }

  if (isToday) {
    if (!startKpiStream(currentDate)) {
      setInterval(() => updateKPIs(currentDate), 30000);
    }
  }
  
  // Add smooth transition
  const cards = kpiDashboard.querySelectorAll('.kpi-card');
  cards.forEach(card => {
    card.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
  });
})();
</script>
<script>
// Manual Attendance Feature
(function(){
  const csrf = document.querySelector('meta[name="csrf"]').getAttribute('content');
  const searchInput = document.getElementById('searchAttendee');
  const searchResults = document.getElementById('searchResults');
  const searchResultsList = document.getElementById('searchResultsList');
  const clearSearchBtn = document.getElementById('clearSearch');
  const manualAttendanceModal = new bootstrap.Modal(document.getElementById('manualAttendanceModal'));
  const manualSigCanvas = document.getElementById('manualSigCanvas');
  let searchTimeout = null;
  let manualSigPad = null;
  let selectedParticipant = null;
  
  // Initialize signature pad for manual attendance
  function initManualSigPad() {
    if (!manualSigCanvas) return;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = manualSigCanvas.getBoundingClientRect();
    manualSigCanvas.width = Math.floor(rect.width * ratio);
    manualSigCanvas.height = Math.floor(rect.height * ratio);
    const ctx = manualSigCanvas.getContext('2d');
    ctx.scale(ratio, ratio);
    manualSigPad = new SignaturePad(manualSigCanvas, {
      backgroundColor: 'rgba(255,255,255,1)',
      minWidth: ratio,
      maxWidth: Math.max(2, ratio * 2)
    });
  }
  
  // Search functionality with debouncing
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const query = this.value.trim();
      
      clearTimeout(searchTimeout);
      
      if (query.length < 2) {
        searchResults.style.display = 'none';
        searchResultsList.innerHTML = '';
        return;
      }
      
      searchTimeout = setTimeout(() => {
        fetch(`?r=admin_attendance_search&q=${encodeURIComponent(query)}`)
          .then(r => r.json())
          .then(data => {
            displaySearchResults(data.results || []);
          })
          .catch(err => {
            console.error('Search error:', err);
            searchResultsList.innerHTML = '<div class="list-group-item text-danger">Error searching participants</div>';
          });
      }, 300);
    });
  }
  
  // Clear search
  if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', () => {
      searchInput.value = '';
      searchResults.style.display = 'none';
      searchResultsList.innerHTML = '';
    });
  }
  
  // Display search results
  function displaySearchResults(results) {
    if (results.length === 0) {
      searchResultsList.innerHTML = '<div class="list-group-item text-muted">No participants found</div>';
      searchResults.style.display = 'block';
      return;
    }
    
    searchResultsList.innerHTML = '';
    results.forEach(participant => {
      const fullName = `${participant.first_name || ''} ${participant.middle_name || ''} ${participant.last_name || ''}`.trim();
      const agency = participant.agency || 'N/A';
      const email = participant.email || '';
      const alreadyMarked = participant.already_marked || false;
      
      const item = document.createElement('button');
      item.type = 'button';
      item.className = `list-group-item list-group-item-action ${alreadyMarked ? 'list-group-item-warning' : ''}`;
      item.innerHTML = `
        <div class="d-flex w-100 justify-content-between align-items-center">
          <div>
            <h6 class="mb-1">${escapeHtml(fullName)}</h6>
            <small class="text-muted">${escapeHtml(agency)}</small>
            ${email ? `<br><small class="text-muted">${escapeHtml(email)}</small>` : ''}
          </div>
          <div class="text-end">
            ${alreadyMarked ? '<span class="badge bg-warning text-dark">Already Marked</span>' : '<span class="badge bg-success">Available</span>'}
            <br><button class="btn btn-sm btn-primary mt-2" data-participant-id="${participant.id}" data-participant-name="${escapeHtml(fullName)}">Mark Attendance</button>
          </div>
        </div>
      `;
      
      const markBtn = item.querySelector('button[data-participant-id]');
      markBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        selectedParticipant = participant;
        openManualAttendanceModal(participant, fullName);
      });
      
      searchResultsList.appendChild(item);
    });
    
    searchResults.style.display = 'block';
  }
  
  // Open manual attendance modal
  function openManualAttendanceModal(participant, fullName) {
    document.getElementById('manualAttendeeName').textContent = fullName;
    selectedParticipant = participant;
    
    // Initialize signature pad if not already done
    if (!manualSigPad) {
      initManualSigPad();
    } else {
      manualSigPad.clear();
    }
    
    manualAttendanceModal.show();
  }
  
  // Clear signature
  document.getElementById('manualSigClear')?.addEventListener('click', () => {
    if (manualSigPad) manualSigPad.clear();
  });
  
  // Skip signature
  document.getElementById('manualSigSkip')?.addEventListener('click', () => {
    if (manualSigPad) manualSigPad.clear();
  });
  
  // Submit manual attendance
  document.getElementById('manualAttendanceSubmit')?.addEventListener('click', () => {
    if (!selectedParticipant) return;
    
    const attendanceDate = document.getElementById('manualAttendanceDate').value || new Date().toISOString().split('T')[0];
    const signature = manualSigPad && !manualSigPad.isEmpty() ? manualSigPad.toDataURL('image/png') : '';
    
    const btn = document.getElementById('manualAttendanceSubmit');
    btn.disabled = true;
    btn.textContent = 'Marking...';
    
    fetch('?r=admin_attendance_manual', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrf
      },
      body: JSON.stringify({
        participant_id: selectedParticipant.id,
        signature: signature,
        date: attendanceDate
      })
    })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        showToast(true, data.message || 'Attendance marked successfully');
        manualAttendanceModal.hide();
        searchInput.value = '';
        searchResults.style.display = 'none';
        searchResultsList.innerHTML = '';
        selectedParticipant = null;
        
        // Refresh KPI dashboard
        const kpiUpdate = new Event('kpi-update');
        window.dispatchEvent(kpiUpdate);
        
        // Optionally reload page after a short delay
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        showToast(false, data.error || 'Failed to mark attendance');
        btn.disabled = false;
        btn.textContent = 'Mark Attendance';
      }
    })
    .catch(err => {
      console.error('Error:', err);
      showToast(false, 'Error marking attendance');
      btn.disabled = false;
      btn.textContent = 'Mark Attendance';
    });
  });
  
  // Initialize signature pad when modal is shown
  document.getElementById('manualAttendanceModal')?.addEventListener('shown.bs.modal', () => {
    initManualSigPad();
  });
  
  // Helper function to escape HTML
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // Helper function to show toast
  function showToast(ok, msg) {
    const el = document.getElementById('sigToast');
    if (!el) return;
    el.className = 'toast align-items-center ' + (ok ? 'text-bg-success' : 'text-bg-danger') + ' border-0';
    el.querySelector('.toast-body').textContent = msg || (ok ? 'Success' : 'Error');
    new bootstrap.Toast(el).show();
  }
})();
</script>
</body>
</html>