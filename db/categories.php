<?php

function categoryDisplayName(string $name): string
{
    $name = trim($name);

    $name = trim((string)preg_replace([
        "/^Vie\s+de\s+l['’]/iu",
        '/^Vie\s+de\s+la\s+/iu',
        '/^Vie\s+du\s+/iu',
        '/^Vie\s+des\s+/iu',
    ], '', $name));

    if ($name === '') {
        return '';
    }

    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($name, 1, null, 'UTF-8');
    }

    if (strpos($name, 'é') === 0) {
        return 'É' . substr($name, 2);
    }

    return ucfirst($name);
}

function categorySortRank(int $id): int
{
    $order = [
        2 => 1, // Formation
        3 => 2, // Etablissement
        1 => 3, // Etudiant
    ];

    return $order[$id] ?? 999;
}

function getAllCategories(): array
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT *
        FROM category
        ORDER BY
            CASE id
                WHEN 2 THEN 1
                WHEN 3 THEN 2
                WHEN 1 THEN 3
                ELSE 999
            END,
            nom ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProjectCategoryIds(int $projectId): array
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT category_id
        FROM project_category
        WHERE project_id = :pid
        ORDER BY category_id
    ");

    $stmt->execute([':pid' => $projectId]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function setProjectCategories(int $projectId, array $categoryIds): void
{
    global $pdo;

    $categoryIds = array_values(
        array_unique(
            array_filter(
                array_map('intval', $categoryIds),
                function ($v) {
                    return $v > 0;
                }
            )
        )
    );

    $pdo->prepare("
        DELETE FROM project_category
        WHERE project_id = :pid
    ")->execute([':pid' => $projectId]);

    if (!$categoryIds) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO project_category (project_id, category_id)
        VALUES (:pid, :cid)
    ");

    foreach ($categoryIds as $categoryId) {
        $stmt->execute([
            ':pid' => $projectId,
            ':cid' => $categoryId,
        ]);
    }
}
