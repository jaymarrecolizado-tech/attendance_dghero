<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registration Error</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark py-3">
  <div class="container">
    <a class="navbar-brand" href="?r=register">GovNet-Launching</a>
  </div>
</nav>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-6">
      <div class="glass-panel">
        <h1 class="page-heading h4 mb-3">We couldn’t complete your registration</h1>
        <p class="subtext mb-4"><?= htmlspecialchars($error ?? 'An unexpected error occurred.', ENT_QUOTES) ?></p>
        <?php if (!empty($errorsList) && is_array($errorsList)): ?>
          <div class="alert alert-warning">
            <ul class="mb-0 ps-3">
              <?php foreach ($errorsList as $message): ?>
                <li><?= htmlspecialchars($message, ENT_QUOTES) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <div class="d-flex flex-column flex-sm-row gap-2">
          <a class="btn btn-primary" href="?r=register">Back to form</a>
          <a class="btn btn-outline-secondary" href="mailto:<?= htmlspecialchars(env('SUPPORT_EMAIL', 'support@example.com'), ENT_QUOTES) ?>">Contact support</a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>