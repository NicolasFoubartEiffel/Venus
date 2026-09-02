<?php

function getAllMails(): array {
    global $pdo;
    $sql = 'SELECT * FROM mails';
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $rows;
}

function getAllProjects(?string $uid = null): array
{
    global $pdo;

    $hasUser = ($uid !== null && trim($uid) !== '');

    $where = $hasUser
        ? 'WHERE COALESCE(p.hidden, 0) = 0'
        : '';

    $order = $hasUser
        ? 'ORDER BY is_favorite DESC, p.display_order ASC, p.id DESC'
        : 'ORDER BY p.display_order ASC, p.id DESC';

    $favoriteSelect = $hasUser
        ? "CASE WHEN uf.uid IS NOT NULL THEN 1 ELSE 0 END AS is_favorite"
        : "0 AS is_favorite";

    $favoriteJoin = $hasUser
        ? "LEFT JOIN user_favorite uf
                ON uf.project_id = p.id
                AND uf.uid = :uid"
        : "";

    $groupBy = $hasUser
        ? "GROUP BY p.id, uf.uid"
        : "GROUP BY p.id";

    $sql = "
        SELECT
            p.*,
            $favoriteSelect,
            COALESCE(
                array_agg(
                    DISTINCT (pc.category_id || '|||' || c.nom)
                    ORDER BY (pc.category_id || '|||' || c.nom)
                )
                FILTER (WHERE pc.category_id IS NOT NULL AND c.nom IS NOT NULL),
                '{}'
            ) AS categories
        FROM projects p
        LEFT JOIN project_category pc
            ON pc.project_id = p.id
        LEFT JOIN category c
            ON c.id = pc.category_id
        $favoriteJoin
        $where
        $groupBy
        $order
    ";
    
    $stmt = $pdo->prepare($sql);

    if ($hasUser) {
        $stmt->bindValue(':uid', trim($uid), PDO::PARAM_STR);
    }

    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $rawCategories = pgArrayParse($row['categories']);

        $row['categories'] = [];

        foreach ($rawCategories as $category) {
            $category = trim((string)$category, "\" \t\n\r\0\x0B");

            [$id, $name] = array_pad(explode('|||', $category, 2), 2, '');

            $id = (int)$id;
            $name = trim($name);

            if ($id <= 0 || $name === '') {
                continue;
            }

            $row['categories'][] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        usort($row['categories'], function ($a, $b) {
            $rankA = categorySortRank((int)($a['id'] ?? 0));
            $rankB = categorySortRank((int)($b['id'] ?? 0));

            if ($rankA === $rankB) {
                return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
            }

            return $rankA <=> $rankB;
        });

        $row['is_favorite'] = (int)$row['is_favorite'];
    }
    return $rows;
}

function getProjectById(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT *
        FROM projects
        WHERE id = :id
    ");

    $stmt->execute([':id' => $id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    $row['category_ids'] = getProjectCategoryIds($id);

    return $row;
}

function project_payload_from_post(array $post): array
{
    $title = trim((string)($post['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException("Le titre est obligatoire.");
    }

    $subtext = trim((string)($post['subtext'] ?? ''));
    if ($subtext === '') {
        throw new InvalidArgumentException("La présentation courte est obligatoire.");
    }

    $categoryIds = $post['category_ids'] ?? [];
    if (!is_array($categoryIds)) {
        $categoryIds = [];
    }

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

    if (count($categoryIds) === 0) {
        throw new InvalidArgumentException("Choisis au moins une catégorie.");
    }

    $contactDetails = (string)($post['contact_details'] ?? '');
    $resourcesDetails = (string)($post['resources_details'] ?? '');
    $descriptionDetails = (string)($post['description_details'] ?? '');
    $comments = (string)($post['comments'] ?? '');
    $hidden = (int)($post['hidden'] ?? 0);

    return [
        'title' => $title,
        'subtext' => $subtext,
        'category_ids' => $categoryIds,

        'contact_details' => $contactDetails,

        'resources_details' => $resourcesDetails,

        'description_details' => $descriptionDetails,

        'comments' => $comments,
        'hidden' => $hidden,
    ];
}

function createProject(array $data): int
{
    global $pdo;

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO projects (
                title,
                subtext,
                contact_details,
                resources_details,
                description_details,
                comments,
                hidden
            )
            VALUES (
                :title,
                :subtext,
                :contact_details,
                :resources_details,
                :description_details,
                :comments,
                :hidden
            )
        ");

        $stmt->execute([
            ':title' => $data['title'],
            ':subtext' => $data['subtext'],

            ':contact_details' => $data['contact_details'],

            ':resources_details' => $data['resources_details'],

            ':description_details' => $data['description_details'],

            ':comments' => $data['comments'],
            ':hidden' => $data['hidden'],
        ]);

        $projectId = (int)$pdo->lastInsertId();

        setProjectCategories($projectId, $data['category_ids']);

        $pdo->commit();

        return $projectId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function updateProject(int $id, array $data): bool
{
    global $pdo;

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            UPDATE projects
            SET
                title = :title,
                subtext = :subtext,
                contact_details = :contact_details,
                resources_details = :resources_details,
                description_details = :description_details,
                comments = :comments,
                hidden = :hidden
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':subtext' => $data['subtext'],

            ':contact_details' => $data['contact_details'],

            ':resources_details' => $data['resources_details'],

            ':description_details' => $data['description_details'],

            ':comments' => $data['comments'],
            ':hidden' => $data['hidden'],
        ]);

        setProjectCategories($id, $data['category_ids']);

        $pdo->commit();

        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function deleteProject(int $id): bool
{
    global $pdo;

    $stmt = $pdo->prepare("
        DELETE FROM projects
        WHERE id = :id
    ");

    $stmt->execute([':id' => $id]);

    return $stmt->rowCount() > 0;
}

function toggleProjectVisibility(int $id): int
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE projects
        SET hidden = CASE WHEN COALESCE(hidden, 0) = 1 THEN 0 ELSE 1 END
        WHERE id = :id
        RETURNING hidden
    ");

    $stmt->execute([':id' => $id]);

    $hidden = $stmt->fetchColumn();

    if ($hidden === false) {
        throw new InvalidArgumentException("Projet introuvable.");
    }

    return (int)$hidden;
}
