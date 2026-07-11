<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registration Complete</title>
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
      <div class="glass-panel text-center">
        <h1 class="page-heading h3 mb-2">You are all set!</h1>
        <p class="subtext mb-4">Present this QR code at the welcome desk to breeze through check-in.</p>
        <img src="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/qrcode.php?uuid=<?= htmlspecialchars($participant['uuid'], ENT_QUOTES) ?>" alt="QR Code" class="img-fluid mb-4" style="max-width:280px;">
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
          <a class="btn btn-primary" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/qrcode.php?uuid=<?= htmlspecialchars($participant['uuid'], ENT_QUOTES) ?>" download>Download QR</a>
          <a class="btn btn-outline-secondary" href="?r=register">Register another</a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>