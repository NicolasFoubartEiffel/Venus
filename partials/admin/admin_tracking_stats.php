<?php
$trackingStats = $trackingStats ?? [];
$trackedRows = array_filter($trackingStats, function ($row) {
    return (int)($row['total_clicks'] ?? 0) > 0;
});

$totalTrackedClicks = array_sum(array_map(function ($row) {
    return (int)($row['total_clicks'] ?? 0);
}, $trackingStats));
?>

<section class="admin-stats-panel" aria-labelledby="tracking-stats-title">
    <div class="admin-stats-head">
        <div>
            <h2 id="tracking-stats-title">Statistiques des tuiles</h2>
            <p><?= (int) $totalTrackedClicks ?> clic(s) enregistr&eacute;(s)</p>
        </div>

        <a class="admin-export-link" href="db/tracking.php?export=csv">
            Export CSV
        </a>
    </div>

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
