<?php
declare(strict_types=1);

$guestTitle = $guestTitle ?? 'GovNet-Launching';
$guestBodyClass = $guestBodyClass ?? 'guest-page';
$guestIncludeRegistrationAssets = $guestIncludeRegistrationAssets ?? false;
// Per-event branding (sanitized hex values from EventContext::themeFor).
$eventTheme = $eventTheme ?? [];
?>
<!doctype html>
<html lang="en" class="no-js">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="<?= !empty($eventTheme['dark']) ? htmlspecialchars($eventTheme['dark'], ENT_QUOTES) : '#162e51' ?>">
  <title><?= htmlspecialchars($guestTitle, ENT_QUOTES) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
  <?php if ($guestIncludeRegistrationAssets): ?>
  <link href="assets/guest-registration.css" rel="stylesheet">
  <?php endif; ?>
  <?php if (!empty($eventTheme)): ?>
  <style>
    .guest-page {
      <?php if (!empty($eventTheme['primary'])): ?>
      --brand-primary: <?= htmlspecialchars($eventTheme['primary'], ENT_QUOTES) ?>;
      --brand-primary-dark: <?= htmlspecialchars($eventTheme['dark'], ENT_QUOTES) ?>;
      --guest-hero-gradient: linear-gradient(150deg, <?= htmlspecialchars($eventTheme['primary'], ENT_QUOTES) ?> 0%, <?= htmlspecialchars($eventTheme['dark'], ENT_QUOTES) ?> 100%);
      <?php endif; ?>
      <?php if (!empty($eventTheme['accent'])): ?>
      --brand-accent: <?= htmlspecialchars($eventTheme['accent'], ENT_QUOTES) ?>;
      <?php endif; ?>
    }
  </style>
  <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($guestBodyClass, ENT_QUOTES) ?>">
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'guest_nav.php'; ?>
