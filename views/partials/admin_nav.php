<?php
declare(strict_types=1);

$adminNavLinks = [
    ['label' => 'Register', 'route' => 'register'],
    ['label' => 'Registrants', 'route' => 'admin_registrants'],
    ['label' => 'Attendance', 'route' => 'admin_attendance'],
    ['label' => 'Gallery', 'route' => 'admin_attendance_gallery'],
    ['label' => 'Events', 'route' => 'admin_events'],
    ['label' => 'Import', 'route' => 'admin_import'],
    ['label' => 'Export', 'route' => 'admin_export'],
    ['label' => 'Report', 'route' => 'admin_report'],
    ['label' => 'Logs', 'route' => 'admin_logs'],
    ['label' => 'Settings', 'route' => 'admin_settings'],
];
$current = $activeNav ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark py-3">
  <div class="container">
    <a class="navbar-brand" href="?r=register">GovNet-Launching</a>
    <div class="ms-auto d-flex flex-wrap gap-2">
      <?php foreach ($adminNavLinks as $link): ?>
        <?php
          $isActive = $current === $link['route'];
          $classes = $isActive ? 'btn btn-light btn-sm px-3 text-dark shadow-sm' : 'btn btn-outline-light btn-sm px-3';
        ?>
        <a class="<?= $classes ?>" href="?r=<?= htmlspecialchars($link['route'], ENT_QUOTES) ?>"><?= htmlspecialchars($link['label'], ENT_QUOTES) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</nav>

