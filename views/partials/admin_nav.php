<?php
declare(strict_types=1);

use App\Services\AuthService;
use App\Services\Database;
use App\Services\EventContext;

$role = AuthService::role() ?? 'admin';
$adminId = (int)($_SESSION['admin_id'] ?? 0);
$navEvents = [];
$navCurrent = null;
$effectiveRole = $role;
try {
    $navPdo = Database::pdo();
    $navEvents = AuthService::isAdmin() ? EventContext::allEvents($navPdo) : EventContext::assignedEvents($navPdo, $adminId);
    $navCurrent = EventContext::currentEvent($navPdo);
    if ($navCurrent && $adminId > 0 && !AuthService::isAdmin()) {
        $er = EventContext::effectiveRole($navPdo, $adminId, (int)$navCurrent['id']);
        if ($er) $effectiveRole = $er;
    }
} catch (\Throwable $e) {
    $navEvents = [];
}

$navSlug = $navCurrent && isset($navCurrent['slug']) ? (string)$navCurrent['slug'] : '';
$withEvent = static fn(string $route): string => $navSlug !== '' && in_array($route, ['register', 'scan'], true)
    ? $route . '&e=' . urlencode($navSlug)
    : $route;

$adminNavLinks = [
    ['label' => 'SEO Dashboard', 'route' => 'admin_seo_dashboard', 'roles' => ['admin', 'event_admin', 'seo_viewer']],
    ['label' => 'Register', 'route' => 'register', 'roles' => ['admin', 'event_admin', 'checker']],
    ['label' => 'Registrants', 'route' => 'admin_registrants', 'roles' => ['admin', 'event_admin', 'checker']],
    ['label' => 'Attendance', 'route' => 'admin_attendance', 'roles' => ['admin', 'event_admin', 'checker']],
    ['label' => 'Scan', 'route' => 'scan', 'roles' => ['admin', 'event_admin', 'checker']],
    ['label' => 'Gallery', 'route' => 'admin_attendance_gallery', 'roles' => ['admin', 'event_admin']],
    ['label' => 'Events', 'route' => 'admin_events', 'roles' => ['admin']],
    ['label' => 'Import', 'route' => 'admin_import', 'roles' => ['admin', 'event_admin']],
    ['label' => 'Export', 'route' => 'admin_export', 'roles' => ['admin', 'event_admin']],
    ['label' => 'Report', 'route' => 'admin_report', 'roles' => ['admin', 'event_admin']],
    ['label' => 'Users', 'route' => 'admin_users', 'roles' => ['admin']],
    ['label' => 'Logs', 'route' => 'admin_logs', 'roles' => ['admin']],
    ['label' => 'Settings', 'route' => 'admin_settings', 'roles' => ['admin']],
];

$adminNavLinks = array_values(array_filter(
    $adminNavLinks,
    static fn(array $link): bool => in_array($effectiveRole, $link['roles'], true)
));

$current = $activeNav ?? '';
$homeRoute = AuthService::loginHomeRoute($role);
$roleLabel = AuthService::roleLabel($role) . ($effectiveRole !== $role ? ' / ' . $effectiveRole : '');
$displayName = AuthService::displayName() ?? 'Staff';
$csrfNav = function_exists('csrf_token') ? csrf_token() : '';
?>
<nav class="navbar navbar-expand-lg navbar-dark py-3">
  <div class="container">
    <a class="navbar-brand" href="?r=<?= htmlspecialchars($homeRoute, ENT_QUOTES) ?>">GovNet-Launching</a>
    <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
      <?php if (count($navEvents) > 0): ?>
      <form method="post" action="?r=admin_events_switch" class="d-flex align-items-center gap-1">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfNav, ENT_QUOTES) ?>">
        <input type="hidden" name="back" value="?r=<?= htmlspecialchars($current !== '' ? $current : $homeRoute, ENT_QUOTES) ?>">
        <select name="id" class="form-select form-select-sm" onchange="this.form.submit()" title="Current event">
          <?php foreach ($navEvents as $ev): ?>
            <option value="<?= (int)$ev['id'] ?>" <?= ($navCurrent && (int)$navCurrent['id'] === (int)$ev['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars((string)($ev['name'] ?? ('Event ' . $ev['id'])), ENT_QUOTES) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php endif; ?>
      <span class="badge text-bg-light text-dark"><?= htmlspecialchars($roleLabel, ENT_QUOTES) ?></span>
      <span class="text-white-50 small d-none d-md-inline"><?= htmlspecialchars($displayName, ENT_QUOTES) ?></span>
      <?php foreach ($adminNavLinks as $link): ?>
        <?php
          $isActive = $current === $link['route'];
          $classes = $isActive ? 'btn btn-light btn-sm px-3 text-dark shadow-sm' : 'btn btn-outline-light btn-sm px-3';
        ?>
        <a class="<?= $classes ?>" href="?r=<?= htmlspecialchars($withEvent($link['route']), ENT_QUOTES) ?>"><?= htmlspecialchars($link['label'], ENT_QUOTES) ?></a>
      <?php endforeach; ?>
      <a class="btn btn-outline-light btn-sm px-3" href="?r=admin_logout">Logout</a>
    </div>
  </div>
</nav>
