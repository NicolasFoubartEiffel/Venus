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

/**
 * =====================================================
 * ===== READ ==========================================
 * =====================================================
 */

function getAllProjects(): array
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT
            p.*,
            COALESCE(
                array_agg(DISTINCT pc.category_id ORDER BY pc.category_id)
                FILTER (WHERE pc.category_id IS NOT NULL),
                '{}'
            ) AS category_ids,
            COALESCE(
                array_agg(DISTINCT c.nom ORDER BY c.nom)
                FILTER (WHERE c.nom IS NOT NULL),
                '{}'
            ) AS category_names
        FROM projects p
        LEFT JOIN project_category pc
            ON pc.project_id = p.id
        LEFT JOIN category c
            ON c.id = pc.category_id
        GROUP BY p.id
        ORDER BY p.id DESC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['category_ids'] = pgArrayParse($row['category_ids']);
        $row['category_names'] = pgArrayParse($row['category_names']);
    }

    return $rows;
}

function getAllCategories(): array
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT *
        FROM category
        ORDER BY nom ASC
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
                fn($v) => $v > 0
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
 * - contact_title
 * - contact_details
 * - resources_title
 * - resources_details
 * - description_title
 * - description_details
 * - comments
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
                fn($v) => $v > 0
            )
        )
    );

    if (count($categoryIds) === 0) {
        throw new InvalidArgumentException("Choisis au moins une catégorie.");
    }

    $contactTitle = trim((string)($post['contact_title'] ?? ''));
    $resourcesTitle = trim((string)($post['resources_title'] ?? ''));
    $descriptionTitle = trim((string)($post['description_title'] ?? ''));

    $contactDetails = (string)($post['contact_details'] ?? '');
    $resourcesDetails = (string)($post['resources_details'] ?? '');
    $descriptionDetails = (string)($post['description_details'] ?? '');
    $comments = (string)($post['comments'] ?? '');

    return [
        'title' => $title,
        'subtext' => $subtext,
        'category_ids' => $categoryIds,

        'contact_title' => ($contactTitle === '') ? null : $contactTitle,
        'contact_details' => $contactDetails,

        'resources_title' => ($resourcesTitle === '') ? null : $resourcesTitle,
        'resources_details' => $resourcesDetails,

        'description_title' => ($descriptionTitle === '') ? null : $descriptionTitle,
        'description_details' => $descriptionDetails,

        'comments' => $comments,
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
                contact_title,
                contact_details,
                resources_title,
                resources_details,
                description_title,
                description_details,
                comments
            )
            VALUES (
                :title,
                :subtext,
                :contact_title,
                :contact_details,
                :resources_title,
                :resources_details,
                :description_title,
                :description_details,
                :comments
            )
        ");

        $stmt->execute([
            ':title' => $data['title'],
            ':subtext' => $data['subtext'],

            ':contact_title' => $data['contact_title'],
            ':contact_details' => $data['contact_details'],

            ':resources_title' => $data['resources_title'],
            ':resources_details' => $data['resources_details'],

            ':description_title' => $data['description_title'],
            ':description_details' => $data['description_details'],

            ':comments' => $data['comments'],
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
                contact_title = :contact_title,
                contact_details = :contact_details,
                resources_title = :resources_title,
                resources_details = :resources_details,
                description_title = :description_title,
                description_details = :description_details,
                comments = :comments
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':subtext' => $data['subtext'],

            ':contact_title' => $data['contact_title'],
            ':contact_details' => $data['contact_details'],

            ':resources_title' => $data['resources_title'],
            ':resources_details' => $data['resources_details'],

            ':description_title' => $data['description_title'],
            ':description_details' => $data['description_details'],

            ':comments' => $data['comments'],
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

/**
 * =====================================================
 * ===== ACTION ROUTER =================================
 * =====================================================
 */

function handleProjectPostActions(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }

    $action = $_POST['action'] ?? '';

    $jsonActions = [
        'create_project',
        'update_project',
        'delete_project',
        'get_project',
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