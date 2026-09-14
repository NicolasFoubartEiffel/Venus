<?php
$trackingStats = $trackingStats ?? [];
$trackedRows = array_values(array_filter($trackingStats, function ($row) {
    return (int)($row['total_clicks'] ?? 0) > 0;
}));

$totalTrackedClicks = array_sum(array_map(function ($row) {
    return (int)($row['total_clicks'] ?? 0);
}, $trackingStats));

$trackingDateFrom = $trackingDateFrom ?? '';
$trackingDateTo = $trackingDateTo ?? '';
$trackingStatsOpen = $trackingStatsOpen ?? false;
$trackingTopLimit = 3;
$lastTrackingExport = function_exists('getLastTileStatsExport') ? getLastTileStatsExport() : null;

$exportQuery = ['export' => 'csv'];

if ($trackingDateFrom !== '') {
    $exportQuery['date_from'] = $trackingDateFrom;
}

if ($trackingDateTo !== '') {
    $exportQuery['date_to'] = $trackingDateTo;
}

$trackingExportUrl = 'db/tracking.php?' . http_build_query($exportQuery, '', '&', PHP_QUERY_RFC3986);

$lastTrackingExportAt = '';

if ($lastTrackingExport) {
    $lastExportTimestamp = strtotime((string)($lastTrackingExport['exported_at'] ?? ''));

    if ($lastExportTimestamp) {
        $trackingExportMonths = [
            1 => 'Janvier',
            2 => 'Fevrier',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Aout',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Decembre',
        ];

        $lastTrackingExportAt = 'Le ' . date('j', $lastExportTimestamp) . ' ' . $trackingExportMonths[(int)date('n', $lastExportTimestamp)] . ' ' . date('Y', $lastExportTimestamp) . ' à ' . date('H:i', $lastExportTimestamp);
    }
}
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

        <div class="admin-export-block">
            <a class="admin-export-link" href="<?= e($trackingExportUrl) ?>">
                Export CSV
            </a>

            <?php if ($lastTrackingExport): ?>
                <p class="admin-export-last">
                    <span class="admin-export-last-label">Dernier export demand&eacute; par :</span>
                    <span class="admin-export-last-user"><?= e((string)($lastTrackingExport['nom'] ?? 'admin')) ?></span>

                    <?php if ($lastTrackingExportAt !== ''): ?>
                        <span class="admin-export-last-date"><?= e($lastTrackingExportAt) ?></span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <form class="admin-stats-filters" method="get" action="admin.php#admin-tracking-stats">
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
            <a class="admin-stats-reset" href="admin.php?stats=1#admin-tracking-stats">R&eacute;initialiser</a>
        <?php endif; ?>
    </form>

    <?php if (empty($trackedRows)): ?>
        <p class="admin-stats-empty">Aucun clic enregistr&eacute; pour le moment.</p>
    <?php else: ?>
        <div class="admin-stats-tools" data-admin-stats-tools data-limit="<?= (int) $trackingTopLimit ?>">
            <label class="admin-stats-search">
                <span>Rechercher une tuile</span>
                <input type="search" placeholder="Nom de tuile" data-admin-stats-search>
            </label>

            <?php if (count($trackedRows) > $trackingTopLimit): ?>
                <button type="button" class="admin-stats-show-all" data-admin-stats-show-all>
                    Voir tout
                </button>
            <?php endif; ?>

            <p class="admin-stats-summary" data-admin-stats-summary>
                Top <?= (int) min($trackingTopLimit, count($trackedRows)) ?> sur <?= (int) count($trackedRows) ?> tuile(s)
            </p>
        </div>

        <p class="admin-stats-empty admin-stats-search-empty" data-admin-stats-search-empty hidden>
            Aucune tuile ne correspond &agrave; cette recherche.
        </p>

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
                <?php foreach ($trackedRows as $index => $row): ?>
                    <?php
                    $tileTitle = (string)($row['tile_title'] ?? '');
                    $tileSearchText = trim($tileTitle);
                    ?>
                    <tr data-admin-stats-row data-tile-search="<?= e($tileSearchText) ?>" <?= $index >= $trackingTopLimit ? 'hidden' : '' ?>>
                        <td>
                            <strong><?= e($tileTitle) ?></strong>
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
