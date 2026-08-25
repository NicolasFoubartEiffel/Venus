<?php
$trackingStats = $trackingStats ?? [];
$trackedRows = array_filter($trackingStats, function ($row) {
    return (int)($row['total_clicks'] ?? 0) > 0;
});

$totalTrackedClicks = array_sum(array_map(function ($row) {
    return (int)($row['total_clicks'] ?? 0);
}, $trackingStats));

$trackingDateFrom = $trackingDateFrom ?? '';
$trackingDateTo = $trackingDateTo ?? '';
$trackingStatsOpen = $trackingStatsOpen ?? false;

$exportQuery = ['export' => 'csv'];

if ($trackingDateFrom !== '') {
    $exportQuery['date_from'] = $trackingDateFrom;
}

if ($trackingDateTo !== '') {
    $exportQuery['date_to'] = $trackingDateTo;
}

$trackingExportUrl = 'db/tracking.php?' . http_build_query($exportQuery, '', '&', PHP_QUERY_RFC3986);
?>

<section
        class="admin-stats-panel <?= $trackingStatsOpen ? 'is-open' : '' ?>"
        id="admin-tracking-stats"
        aria-labelledby="tracking-stats-title"
        <?= $trackingStatsOpen ? '' : 'hidden' ?>
>
    <div class="admin-stats-head">
        <div>
            <h2 id="tracking-stats-title">Statistiques des tuiles</h2>
            <p><?= (int) $totalTrackedClicks ?> clic(s) enregistr&eacute;(s)</p>
        </div>

        <a class="admin-export-link" href="<?= e($trackingExportUrl) ?>">
            Export CSV
        </a>
    </div>

    <form class="admin-stats-filters" method="get">
        <input type="hidden" name="stats" value="1">

        <label>
            <span>Du</span>
            <input type="date" name="date_from" value="<?= e($trackingDateFrom) ?>">
        </label>

        <label>
            <span>Au</span>
            <input type="date" name="date_to" value="<?= e($trackingDateTo) ?>">
        </label>

        <button type="submit" class="admin-stats-filter-btn">Filtrer</button>

        <?php if ($trackingDateFrom !== '' || $trackingDateTo !== ''): ?>
            <a class="admin-stats-reset" href="admin.php?stats=1">R&eacute;initialiser</a>
        <?php endif; ?>
    </form>

    <?php if (empty($trackedRows)): ?>
        <p class="admin-stats-empty">Aucun clic enregistr&eacute; pour le moment.</p>
    <?php else: ?>
        <div class="admin-stats-table-wrap">
            <table class="admin-stats-table">
                <thead>
                <tr>
                    <th>Tuile</th>
                    <th>Total</th>
                    <th>Description</th>
                    <th>Ressources</th>
                    <th>Contact</th>
                    <th>Dernier clic</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($trackedRows as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e($row['tile_title'] ?? '') ?></strong>
                            <span>#<?= (int)($row['tile_id'] ?? 0) ?></span>
                        </td>
                        <td><?= (int)($row['total_clicks'] ?? 0) ?></td>
                        <td><?= (int)($row['tab_1_clicks'] ?? 0) ?></td>
                        <td><?= (int)($row['tab_2_clicks'] ?? 0) ?></td>
                        <td><?= (int)($row['tab_3_clicks'] ?? 0) ?></td>
                        <td>
                            <?php if (!empty($row['last_click_at'])): ?>
                                <?= e(date('d/m/Y H:i', strtotime($row['last_click_at']))) ?>
                            <?php else: ?>
                                <span class="muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
