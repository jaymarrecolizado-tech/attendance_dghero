<?php
declare(strict_types=1);

$guestShowActions = $guestShowActions ?? true;
$isAdmin = !empty($_SESSION['admin_id']);
?>
<nav class="navbar navbar-expand-lg navbar-dark guest-navbar sticky-top">
  <div class="container guest-container">
    <a class="navbar-brand guest-brand" href="?r=register">
      <span class="guest-brand-mark" aria-hidden="true">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="28" height="28" rx="8" fill="rgba(255,255,255,0.2)"/>
          <path d="M8 18V10h3.2c1.76 0 2.8.92 2.8 2.24 0 1.12-.68 1.88-1.72 2.08L14.8 18H12.4l-1.48-3.28H10.4V18H8zm2.4-5.04h1c.72 0 1.12-.36 1.12-.96s-.4-.96-1.12-.96h-1v1.92zM15.6 18V10h4.8v2h-2.4v1.2h2.2v2h-2.2V16h2.6v2h-4.8z" fill="white"/>
        </svg>
      </span>
      <span>GovNet-Launching</span>
    </a>
    <?php if ($guestShowActions): ?>
    <div class="ms-auto d-flex gap-2 guest-nav-actions">
      <a class="btn btn-outline-light guest-nav-btn" href="?r=scan" title="Scan & Sign">
        <span class="guest-nav-icon d-md-none" aria-hidden="true">&#128247;</span>
        <span class="d-none d-md-inline">Scan &amp; Sign</span>
      </a>
      <?php if ($isAdmin): ?>
      <a class="btn btn-outline-light guest-nav-btn" href="?r=admin_registrants" title="Admin Dashboard">
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
