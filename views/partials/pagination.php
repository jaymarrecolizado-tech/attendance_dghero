<?php
declare(strict_types=1);

use App\Services\Paginator;

/**
 * Expects $pager from Paginator::make(), $pagerQuery base params, $pagerKey page query key.
 *
 * @var array{page:int,pages:int,offset:int,per:int,total:int,from:int,to:int} $pager
 * @var array<string,scalar|null> $pagerQuery
 * @var string $pagerKey
 */
$pager = $pager ?? null;
$pagerQuery = $pagerQuery ?? [];
$pagerKey = (string)($pagerKey ?? 'page');
if (!is_array($pager) || (int)($pager['total'] ?? 0) <= 0) {
    return;
}
$page = (int)$pager['page'];
$pages = (int)$pager['pages'];
$window = Paginator::window($page, $pages);
$label = (string)($pagerLabel ?? 'Pagination');
$pagerPerOptions = $pagerPerOptions ?? Paginator::perOptions();
$pagerPerDefault = (string)($pagerPerDefault ?? '25');
$pagerHash = trim((string)($pagerHash ?? ''));
$perChoice = Paginator::perChoice(
    isset($perChoice) ? (string)$perChoice : (isset($_GET['per']) ? (string)$_GET['per'] : null),
    $pagerPerOptions,
    $pagerPerDefault
);
$perFormQuery = $pagerQuery;
unset($perFormQuery['cpage'], $perFormQuery['obpage'], $perFormQuery['bpage'], $perFormQuery['page'], $perFormQuery['per']);
$pagerHref = static function (array $query, string $key, int $pageNum) use ($pagerHash): string {
    $url = Paginator::href($query, $key, $pageNum);
    return $pagerHash !== '' ? $url . '#' . rawurlencode($pagerHash) : $url;
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
  <div class="d-flex flex-wrap align-items-center gap-2">
    <small class="text-muted">Showing <?= (int)$pager['from'] ?>–<?= (int)$pager['to'] ?> of <?= (int)$pager['total'] ?></small>
    <label class="small text-muted mb-0" for="per-<?= htmlspecialchars($pagerKey, ENT_QUOTES) ?>">Show</label>
    <select id="per-<?= htmlspecialchars($pagerKey, ENT_QUOTES) ?>" class="form-select form-select-sm" style="width:auto" aria-label="Rows per page" onchange="if (this.value) window.location = this.value">
      <?php foreach ($pagerPerOptions as $opt): ?>
        <?php
          $optQuery = $perFormQuery;
          $optQuery['per'] = $opt === $pagerPerDefault ? null : $opt;
          $optUrl = $pagerHref($optQuery, $pagerKey, 1);
        ?>
      <option value="<?= htmlspecialchars($optUrl, ENT_QUOTES) ?>" <?= $perChoice === $opt ? 'selected' : '' ?>><?= $opt === 'all' ? 'All' : $opt ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php if ($pages > 1): ?>
  <nav aria-label="<?= htmlspecialchars($label, ENT_QUOTES) ?>">
    <ul class="pagination pagination-sm mb-0">
      <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
        <?php if ($page <= 1): ?>
        <span class="page-link">Previous</span>
        <?php else: ?>
        <a class="page-link" href="<?= htmlspecialchars($pagerHref($pagerQuery, $pagerKey, $page - 1), ENT_QUOTES) ?>">Previous</a>
        <?php endif; ?>
      </li>
      <?php foreach ($window as $item): ?>
        <?php if ($item === null): ?>
        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
        <?php else: ?>
        <li class="page-item<?= $item === $page ? ' active' : '' ?>">
          <a class="page-link" href="<?= htmlspecialchars($pagerHref($pagerQuery, $pagerKey, $item), ENT_QUOTES) ?>"><?= $item ?></a>
        </li>
        <?php endif; ?>
      <?php endforeach; ?>
      <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>">
        <?php if ($page >= $pages): ?>
        <span class="page-link">Next</span>
        <?php else: ?>
        <a class="page-link" href="<?= htmlspecialchars($pagerHref($pagerQuery, $pagerKey, $page + 1), ENT_QUOTES) ?>">Next</a>
        <?php endif; ?>
      </li>
    </ul>
  </nav>
  <?php endif; ?>
</div>
