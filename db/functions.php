<?php
require_once 'connect.php';

/**
 * =====================================================
 * ===== HELPERS =======================================
 * =====================================================
 */

function pgArrayParse($value): array
{
    if (!$value || $value === '{}') {
        return [];
    }

    return array_map('trim', explode(',', trim($value, '{}')));
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

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


/**
 * =====================================================
 * ===== READ ==========================================
 * =====================================================
 */

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

/**
 * =====================================================
 * ===== MANY TO MANY : PROJECTS <-> CATEGORY ==========
 * =====================================================
 */

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

/**
 * =====================================================
 * ===== PAYLOAD =======================================
 * =====================================================
 *
 * Champs attendus depuis le HTML actuel :
 * - title
 * - subtext
 * - category_ids[]
 * - contact_details
 * - resources_details
 * - description_details
 * - comments
 * - hidden
 */

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

/**
 * =====================================================
 * ===== WRITE =========================================
 * =====================================================
 */

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

/**
 * =====================================================
 * ===== FAVORITE HANDLER =================================
 * =====================================================
 */


function createUserIfMissing(string $uid, string $username): void
{
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO users (uid, username)
        VALUES (:uid, :username)
        ON CONFLICT (uid) DO NOTHING
    ");

    $stmt->execute([
        ':uid' => $uid,
        ':username' => $username,
    ]);
}

function addFavorite(string $uid, string $username, int $projectId): void
{
    global $pdo;

    createUserIfMissing($uid, $username);

    $stmt = $pdo->prepare("
        INSERT INTO user_favorite (uid, project_id)
        VALUES (:uid, :project_id)
        ON CONFLICT (uid, project_id) DO NOTHING
    ");

    $stmt->execute([
        ':uid' => $uid,
        ':project_id' => $projectId,
    ]);
}

function deleteFavorite(string $uid, int $projectId): void
{
    global $pdo;

    $stmt = $pdo->prepare("
        DELETE FROM user_favorite
        WHERE uid = :uid
          AND project_id = :project_id
    ");

    $stmt->execute([
        ':uid' => $uid,
        ':project_id' => $projectId,
    ]);
}

function toggleFavorite(string $uid, string $username, int $projectId): bool
{
    global $pdo;

    createUserIfMissing($uid, $username);

    $stmt = $pdo->prepare("
        SELECT 1
        FROM user_favorite
        WHERE uid = :uid
          AND project_id = :project_id
        LIMIT 1
    ");

    $stmt->execute([
        ':uid' => $uid,
        ':project_id' => $projectId,
    ]);

    $exists = (bool) $stmt->fetchColumn();

    if ($exists) {
        deleteFavorite($uid, $projectId);
        return false;
    }

    addFavorite($uid, $username, $projectId);
    return true;
}

function getUserFavoriteProjectIds(string $uid): array
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT project_id
        FROM user_favorite
        WHERE uid = :uid
    ");

    $stmt->execute([':uid' => $uid]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * =====================================================
 * ===== ACTION ROUTER =================================
 * =====================================================
 */

function handleProjectPostActions(): void
{
    global $pdo;

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }

    $action = $_POST['action'] ?? '';

    $jsonActions = [
        'create_project',
        'update_project',
        'delete_project',
        'get_project',
        'toggle_visibility',
    ];

    $wantsJson = in_array($action, $jsonActions, true);

    try {
        if ($action === 'create_project') {
            $data = project_payload_from_post($_POST);
            $id = createProject($data);

            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'id' => $id,
                ]);
                exit;
            }

            header('Location: ?success=1&created_id=' . $id);
            exit;
        }

        if ($action === 'update_project') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException("ID invalide.");
            }

            $data = project_payload_from_post($_POST);
            updateProject($id, $data);

            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'id' => $id,
                ]);
                exit;
            }

            header('Location: ?success=1&updated_id=' . $id);
            exit;
        }

        if ($action === 'toggle_visibility') {
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new InvalidArgumentException("ID invalide.");
            }

            $hidden = toggleProjectVisibility($id);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'id' => $id,
                'hidden' => $hidden,
            ]);
            exit;
        }

        if ($action === 'delete_project') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException("ID invalide.");
            }

            deleteProject($id);

            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                ]);
                exit;
            }

            header('Location: ?success=1&deleted_id=' . $id);
            exit;
        }

        if ($action === 'get_project') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException("ID invalide.");
            }

            $project = getProjectById($id);
            if (!$project) {
                throw new InvalidArgumentException("Projet introuvable.");
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'project' => $project,
            ]);
            exit;
        }


        if ($action === 'update_project_order') {
            $ids = json_decode($_POST['ids'] ?? '[]', true);

            if (!is_array($ids)) {
                throw new InvalidArgumentException('Ordre invalide.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE projects
                SET display_order = :display_order
                WHERE id = :id
            ");

            foreach ($ids as $index => $id) {
                $stmt->execute([
                    ':display_order' => $index + 1,
                    ':id' => (int)$id,
                ]);
            }

            $pdo->commit();

            echo json_encode(['success' => true]);
            exit;
        }


        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Action inconnue.',
            ]);
            exit;
        }

        header('Location: ?error=1&msg=' . urlencode('Action inconnue.'));
        exit;
    } catch (Throwable $e) {
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
            exit;
        }

        header('Location: ?error=1&msg=' . urlencode($e->getMessage()));
        exit;
    }
}
