<?php
declare(strict_types=1);

$token = function_exists('csrf_token') ? csrf_token() : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Event Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark py-3">
  <div class="container">
    <a class="navbar-brand" href="?r=register">GovNet-Launching</a>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-outline-light btn-sm px-3" href="?r=scan">Scan & Sign</a>
      <a class="btn btn-outline-light btn-sm px-3" href="?r=admin_login">Admin</a>
    </div>
  </div>
</nav>
<div class="container py-5">
  <div class="row g-4 align-items-start">
    <div class="col-12 col-lg-8 mx-auto">
      <div class="card p-4 p-md-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <div>
            <p class="text-uppercase text-muted mb-1" style="letter-spacing:.2em;font-size:.75rem;">Step 1 of 2</p>
            <h2 class="page-heading h3 mb-0">Participant Information</h2>
          </div>
          <span class="badge-soft">QR ready</span>
        </div>
  <form method="post" action="?r=register_submit" class="needs-validation" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
    <div class="row g-3">
      <div class="col-12 col-md-6">
        <label class="form-label">First Name</label>
        <input name="first_name" class="form-control" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Middle Name</label>
        <input name="middle_name" class="form-control">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Last Name</label>
        <input name="last_name" class="form-control" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Email Address</label>
        <input name="email" type="email" class="form-control">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Nickname</label>
        <input name="nickname" class="form-control">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Sex</label>
        <select name="sex" class="form-select">
          <option value="">Select</option>
          <option value="Female">Female</option>
          <option value="Male">Male</option>
          <option value="Other">Prefer not to say</option>
        </select>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Sector</label>
        <select name="sector" class="form-select" required>
          <option value="">Select</option>
          <?php foreach (($sectors ?? []) as $s): ?>
            <option value="<?= htmlspecialchars($s, ENT_QUOTES) ?>"><?= htmlspecialchars($s, ENT_QUOTES) ?></option>
          <?php endforeach; ?>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Agency</label>
        <div class="input-group">
          <input name="agency_select" id="agencyInput" class="form-control" list="agencyList" placeholder="Type to search">
          <button class="btn btn-outline-secondary" type="button" id="agencyExpand">Expand</button>
        </div>
        <datalist id="agencyList">
          <?php foreach (($agencies ?? []) as $a): ?>
            <option value="<?= htmlspecialchars($a['agency'], ENT_QUOTES) ?>"></option>
          <?php endforeach; ?>
        </datalist>
        <select id="agencySelectFull" class="form-select scroll-select mt-2" style="display:none">
          <option value="">Select</option>
          <?php foreach (($agencies ?? []) as $a): ?>
            <option value="<?= htmlspecialchars($a['agency'], ENT_QUOTES) ?>"><?= htmlspecialchars($a['agency'], ENT_QUOTES) ?></option>
          <?php endforeach; ?>
          <option value="other">Other</option>
        </select>
        <input name="agency_other" id="agencyOther" class="form-control mt-2" placeholder="Enter agency" style="display:none">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Designation</label>
        <div class="input-group">
          <input name="designation_select" id="designationInput" class="form-control" list="designationList" placeholder="Type to search">
          <button class="btn btn-outline-secondary" type="button" id="designationExpand">Expand</button>
        </div>
        <datalist id="designationList">
          <?php foreach (($designations ?? []) as $d): ?>
            <option value="<?= htmlspecialchars($d['designation'], ENT_QUOTES) ?>"></option>
          <?php endforeach; ?>
        </datalist>
        <select id="designationSelectFull" class="form-select scroll-select mt-2" style="display:none">
          <option value="">Select</option>
          <?php foreach (($designations ?? []) as $d): ?>
            <option value="<?= htmlspecialchars($d['designation'], ENT_QUOTES) ?>"><?= htmlspecialchars($d['designation'], ENT_QUOTES) ?></option>
          <?php endforeach; ?>
          <option value="other">Other</option>
        </select>
        <input name="designation_other" id="designationOther" class="form-control mt-2" placeholder="Enter designation" style="display:none">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Office Email</label>
        <input name="office_email" type="email" class="form-control">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Contact No</label>
        <input name="contact_no" class="form-control">
      </div>
    </div>
    <div class="mt-3 d-grid">
      <button class="btn btn-primary btn-lg">Register</button>
    </div>
  </form>
      </div>
    </div>
  </div>
  </div>
<script>
(() => {
  'use strict';
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  });
  const agencyInput = document.getElementById('agencyInput');
  const agencyExpand = document.getElementById('agencyExpand');
  const agencySelectFull = document.getElementById('agencySelectFull');
  const agencyOther = document.getElementById('agencyOther');
  const designationInput = document.getElementById('designationInput');
  const designationExpand = document.getElementById('designationExpand');
  const designationSelectFull = document.getElementById('designationSelectFull');
  const designationOther = document.getElementById('designationOther');
  function toggleOther(value, other){ other.style.display = value === 'other' ? 'block' : 'none'; if (value === 'other') other.focus(); }
  if (agencyExpand) agencyExpand.addEventListener('click', () => { agencySelectFull.style.display = agencySelectFull.style.display === 'none' ? 'block' : 'none'; });
  if (agencySelectFull) agencySelectFull.addEventListener('change', () => { agencyInput.value = agencySelectFull.value; toggleOther(agencySelectFull.value, agencyOther); });
  if (designationExpand) designationExpand.addEventListener('click', () => { designationSelectFull.style.display = designationSelectFull.style.display === 'none' ? 'block' : 'none'; });
  if (designationSelectFull) designationSelectFull.addEventListener('change', () => { designationInput.value = designationSelectFull.value; toggleOther(designationSelectFull.value, designationOther); });
})();
</script>
</body>
</html>