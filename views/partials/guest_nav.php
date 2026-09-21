<?php
declare(strict_types=1);

use App\Services\AuthService;
use App\Services\Database;
use App\Services\EventContext;

$guestShowActions = $guestShowActions ?? true;
$isLoggedIn = AuthService::check();
$dashboardRoute = $isLoggedIn ? AuthService::loginHomeRoute() : 'admin_login';
$navSlugForBrand = trim((string)($_GET['e'] ?? ''));
$navBrand = $eventName ?? $eventTitle ?? $guestBrand ?? null;
if (($navBrand === null || $navBrand === '') && $navSlugForBrand !== '') {
    try { $pdo0 = Database::pdo(); $ev0 = EventContext::findBySlug($pdo0, $navSlugForBrand); if ($ev0 && !empty($ev0['name'])) $navBrand = (string)$ev0['name']; } catch (\Throwable $e) {}
}
if ($navBrand === null || $navBrand === '') $navBrand = 'GovNet-Launching';
$navHome = $navSlugForBrand !== '' ? '?r=register&e=' . urlencode($navSlugForBrand) : '?r=register';
$navScan = $navSlugForBrand !== '' ? '?r=scan&e=' . urlencode($navSlugForBrand) : '?r=scan';
?>
<nav class="navbar navbar-expand-lg navbar-dark guest-navbar sticky-top">
  <div class="container guest-container">
    <a class="navbar-brand guest-brand" href="<?= htmlspecialchars($navHome, ENT_QUOTES) ?>">
      <span><?= htmlspecialchars($navBrand, ENT_QUOTES) ?></span>
    </a>
    <?php if ($guestShowActions): ?>
    <div class="ms-auto d-flex gap-2 guest-nav-actions">
      <a class="btn btn-outline-light guest-nav-btn" href="<?= htmlspecialchars($navScan, ENT_QUOTES) ?>" title="Scan & Sign">
        <span class="guest-nav-icon d-md-none" aria-hidden="true">&#128247;</span>
        <span class="d-none d-md-inline">Scan &amp; Sign</span>
      </a>
      <?php if ($isLoggedIn): ?>
      <a class="btn btn-outline-light guest-nav-btn" href="?r=<?= htmlspecialchars($dashboardRoute, ENT_QUOTES) ?>" title="Dashboard">
        <span class="guest-nav-icon d-md-none" aria-hidden="true">&#9881;</span>
        <span class="d-none d-md-inline">Dashboard</span>
      </a>
      <a class="btn btn-outline-light guest-nav-btn" href="?r=admin_logout" title="Logout">
        <span class="d-none d-md-inline">Logout</span>
        <span class="guest-nav-icon d-md-none" aria-hidden="true">&#10140;</span>
      </a>
      <?php else: ?>
      <a class="btn btn-outline-light guest-nav-btn" href="?r=admin_login" title="Admin Login">
        <span class="guest-nav-icon d-md-none" aria-hidden="true">&#9881;</span>
        <span class="d-none d-md-inline">Admin</span>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</nav>
