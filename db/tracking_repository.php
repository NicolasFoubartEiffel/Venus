<?php

function normalizeTileTrackingTab(string $tabKey): string
{
    $tabKey = strtolower(trim($tabKey));

    $aliases = [
        'doc' => 'description',
        'open' => 'resources',
        'mailto' => 'contact',
    ];

    return $aliases[$tabKey] ?? $tabKey;
}

function getTileTrackingFlags(string $tabKey): array
{
    $tabKey = normalizeTileTrackingTab($tabKey);

    return [
        'tab_1' => $tabKey === 'description',
        'tab_2' => $tabKey === 'resources',
        'tab_3' => $tabKey === 'contact',
    ];
}

function normalizeTrackingDate($value): string
{
    $value = trim((string)$value);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return '';
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);

    if (!$date || $date->format('Y-m-d') !== $value) {
        return '';
    }

    return $value;
}

function getTileStatsExportFilterLabel(string $dateFrom = '', string $dateTo = ''): string
{
    $dateFrom = normalizeTrackingDate($dateFrom);
    $dateTo = normalizeTrackingDate($dateTo);

    if ($dateFrom !== '' && $dateTo !== '') {
        return 'du ' . date('d/m/Y', strtotime($dateFrom)) . ' au ' . date('d/m/Y', strtotime($dateTo));
    }

    if ($dateFrom !== '') {
        return 'depuis le ' . date('d/m/Y', strtotime($dateFrom));
    }

    if ($dateTo !== '') {
        return 'jusqu\'au ' . date('d/m/Y', strtotime($dateTo));
    }

    return 'toutes les dates';
}

function ensureTileStatsExportLogTable(): void
{
    // DDL is handled by init_db/add_tile_stats_export_log.sql.
    // The application database user only needs SELECT/INSERT privileges.
}

function recordTileStatsExport(string $dateFrom = '', string $dateTo = ''): void
{
    global $pdo;

    try {
        $dateFrom = normalizeTrackingDate($dateFrom);
        $dateTo = normalizeTrackingDate($dateTo);

        ensureTileStatsExportLogTable();
        $identity = getCurrentLdapUserIdentity();

        $stmt = $pdo->prepare("
            INSERT INTO tile_stats_export_log (
                filter_label,
                username,
                nom,
                date_from,
                date_to
            )
            VALUES (
                :filter_label,
                :username,
                :nom,
                :date_from,
                :date_to
            )
        ");

        $stmt->execute([
            ':filter_label' => getTileStatsExportFilterLabel($dateFrom, $dateTo),
            ':username' => $identity['username'],
            ':nom' => $identity['name'],
            ':date_from' => $dateFrom !== '' ? $dateFrom : null,
            ':date_to' => $dateTo !== '' ? $dateTo : null,
        ]);
    } catch (Throwable $e) {
        error_log('Tile stats export log failed: ' . $e->getMessage());
    }
}

function getLastTileStatsExport(): ?array
{
    global $pdo;

    try {
        ensureTileStatsExportLogTable();

        $stmt = $pdo->query("
            SELECT
                filter_label,
                username,
                nom,
                date_from,
                date_to,
                exported_at
            FROM tile_stats_export_log
            ORDER BY exported_at DESC, id DESC
            LIMIT 1
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function recordTileInteraction(int $tileId, string $tabKey): bool
{
    global $pdo;

    if ($tileId <= 0) {
        return false;
    }

    $tabKey = normalizeTileTrackingTab($tabKey);
    $flags = getTileTrackingFlags($tabKey);

    $existsStmt = $pdo->prepare("
        SELECT 1
        FROM projects
        WHERE id = :id
        LIMIT 1
    ");

    $existsStmt->execute([':id' => $tileId]);

    if (!$existsStmt->fetchColumn()) {
        return false;
    }

    $stmt = $pdo->prepare("
        INSERT INTO tile_click_tracking (
            tile_id,
            tab_1,
            tab_2,
            tab_3,
            clicked_at
        )
        VALUES (
            :tile_id,
            :tab_1,
            :tab_2,
            :tab_3,
            NOW()
        )
    ");

    $stmt->execute([
        ':tile_id' => $tileId,
        ':tab_1' => $flags['tab_1'] ? 1 : 0,
        ':tab_2' => $flags['tab_2'] ? 1 : 0,
        ':tab_3' => $flags['tab_3'] ? 1 : 0,
    ]);

    return true;
}

function getTileInteractionStats(string $dateFrom = '', string $dateTo = ''): array
{
    global $pdo;

    try {
        $dateFrom = normalizeTrackingDate($dateFrom);
        $dateTo = normalizeTrackingDate($dateTo);

        $conditions = [];
        $params = [];

        if ($dateFrom !== '') {
            $conditions[] = 't.clicked_at >= :date_from';
            $params[':date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $conditions[] = 't.clicked_at < (CAST(:date_to AS date) + INTERVAL ' . "'1 day'" . ')';
            $params[':date_to'] = $dateTo;
        }

        $trackingFilter = '';

        if (!empty($conditions)) {
            $trackingFilter = 'AND ' . implode(' AND ', $conditions);
        }

        $stmt = $pdo->prepare("
            SELECT
                p.id AS tile_id,
                p.title AS tile_title,
                COUNT(t.clicked_at) AS total_clicks,
                COALESCE(SUM(CASE WHEN t.tab_1 THEN 1 ELSE 0 END), 0) AS tab_1_clicks,
                COALESCE(SUM(CASE WHEN t.tab_2 THEN 1 ELSE 0 END), 0) AS tab_2_clicks,
                COALESCE(SUM(CASE WHEN t.tab_3 THEN 1 ELSE 0 END), 0) AS tab_3_clicks,
                MAX(t.clicked_at) AS last_click_at
            FROM projects p
            LEFT JOIN tile_click_tracking t
                ON t.tile_id = p.id
                $trackingFilter
            GROUP BY p.id, p.title, p.display_order
            ORDER BY total_clicks DESC, p.display_order ASC, p.title ASC
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function exportTileInteractionStatsCsv(string $dateFrom = '', string $dateTo = ''): void
{
    recordTileStatsExport($dateFrom, $dateTo);

    $stats = getTileInteractionStats($dateFrom, $dateTo);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="stats_tuiles.csv"');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'tile_id',
        'titre',
        'total',
        'tab_1_description',
        'tab_2_ressources',
        'tab_3_contact',
        'dernier_clic',
    ], ';');

    foreach ($stats as $row) {
        fputcsv($output, [
            $row['tile_id'] ?? '',
            $row['tile_title'] ?? '',
            $row['total_clicks'] ?? 0,
            $row['tab_1_clicks'] ?? 0,
            $row['tab_2_clicks'] ?? 0,
            $row['tab_3_clicks'] ?? 0,
            $row['last_click_at'] ?? '',
        ], ';');
    }

    fclose($output);
    exit;
}
