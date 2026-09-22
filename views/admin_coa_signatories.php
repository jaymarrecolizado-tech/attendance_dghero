<?php
declare(strict_types=1);

/**
 * Plan#12: signatory library (All Father). Upload e-signatures (PNG/JPG/WebP,
 * max 1 MB), edit names/titles, and preview the stored signature image.
 */
$signatories = $signatories ?? [];
$flash = $flash ?? null;
$activeNav = 'admin_coa_monitor';
$token = function_exists('csrf_token') ? csrf_token() : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CoA Signatories</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'admin_nav.php'; ?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0">CoA signatories</h1>
    <?php $coaPage = 'signatories'; require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'coa_subnav.php'; ?>
  </div>

  <?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?> py-2"><?= htmlspecialchars($flash['message'], ENT_QUOTES) ?></div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-lg-5">
      <div class="card"><div class="card-body">
        <h2 class="h6 mb-3">Add a signatory</h2>
        <form method="post" action="?r=admin_coa_signatory_save" enctype="multipart/form-data" class="d-flex flex-column gap-2">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
          <div>
            <label class="form-label small mb-1">Name</label>
            <input name="name" class="form-control form-control-sm" maxlength="120" required>
          </div>
          <div>
            <label class="form-label small mb-1">Title</label>
            <input name="title" class="form-control form-control-sm" maxlength="120" placeholder="e.g. Regional Director, DICT Region II">
          </div>
          <div>
            <label class="form-label small mb-1">E-signature (PNG/JPG/WebP, max 1 MB)</label>
            <input type="file" name="signature" accept=".png,.jpg,.jpeg,.webp" class="form-control form-control-sm">
          </div>
          <button class="btn btn-primary btn-sm align-self-start">Add signatory</button>
        </form>
      </div></div>
    </div>

    <div class="col-12 col-lg-7">
      <div class="card"><div class="card-body">
        <h2 class="h6 mb-3">Saved signatories</h2>
        <?php if (!count($signatories)): ?>
        <p class="text-muted small mb-0">No signatories yet. Templates and events pick a signatory from this list.</p>
        <?php endif; ?>
        <div class="list-group list-group-flush">
          <?php foreach ($signatories as $s): ?>
          <div class="list-group-item px-0">
            <div class="d-flex align-items-start gap-3">
              <?php $sigPath = trim((string)($s['signature_path'] ?? '')); ?>
              <?php if ($sigPath !== ''): ?>
              <img src="?r=admin_coa_signatory_image&id=<?= (int)$s['id'] ?>" alt="" style="height:44px;width:auto;max-width:120px;object-fit:contain;border:1px solid rgba(22,46,81,.15);border-radius:6px;background:#fff;padding:2px;">
              <?php else: ?>
              <span class="badge text-bg-light text-dark border" style="height:fit-content;">No e-sig</span>
              <?php endif; ?>
              <div class="flex-grow-1">
                <form method="post" action="?r=admin_coa_signatory_save" class="row g-1 align-items-end">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-0">Name</label>
                    <input name="name" class="form-control form-control-sm" maxlength="120" value="<?= htmlspecialchars((string)$s['name'], ENT_QUOTES) ?>" required>
                  </div>
                  <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-0">Title</label>
                    <input name="title" class="form-control form-control-sm" maxlength="120" value="<?= htmlspecialchars((string)($s['title'] ?? ''), ENT_QUOTES) ?>">
                  </div>
                  <div class="col-12 col-md-2">
                    <button class="btn btn-sm btn-outline-primary w-100">Save</button>
                  </div>
                </form>
                <form method="post" action="?r=admin_coa_signatory_delete" class="mt-1"
                      onsubmit="return confirm('Delete this signatory? Templates keep their copied name and title.');">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <button class="btn btn-sm btn-link text-danger p-0">Delete signatory</button>
                </form>
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
