<?php
require_once 'connect.php';

/**
 * ===== READ =====
 */

function pgArrayParse($value): array {
    if (!$value || $value === '{}') return [];
    // Attention: tes arrays peuvent contenir des guillemets, on garde ton parse simple
    return array_map('trim', explode(',', trim($value, '{}')));
}

function getAllProjects(): array {
    global $pdo;

    $stmt = $pdo->query("
        SELECT
            p.*,
            COALESCE(array_agg(pc.category_id ORDER BY pc.category_id)
                FILTER (WHERE pc.category_id IS NOT NULL), '{}') AS category_ids,
            COALESCE(array_agg(c.nom ORDER BY c.nom)
                FILTER (WHERE c.nom IS NOT NULL), '{}') AS category_names
        FROM projects p
        LEFT JOIN project_category pc ON pc.project_id = p.id
        LEFT JOIN category c ON c.id = pc.category_id
        GROUP BY p.id
        ORDER BY p.id DESC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['category_ids'] = pgArrayParse($r['category_ids']);
        $r['category_names'] = pgArrayParse($r['category_names']);
    }

    return $rows;
}

function getAllCategories(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM category ORDER BY nom ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ⚠️ Important: pour l'édition, on veut aussi récupérer les champs descriptions.
 * Ici c'est SELECT * donc OK si les colonnes existent bien dans projects.
 */
function getProjectById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * ===== MANY-TO-MANY (projects <-> category) =====
 * Table: project_category(project_id, category_id)
 */

function getProjectCategoryIds(int $projectId): array {
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

function setProjectCategories(int $projectId, array $categoryIds): void {
    global $pdo;

    $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds), fn($v) => $v > 0)));

    $pdo->prepare("DELETE FROM project_category WHERE project_id = :pid")
        ->execute([':pid' => $projectId]);

    if (!$categoryIds) return;

    $ins = $pdo->prepare("
        INSERT INTO project_category (project_id, category_id)
        VALUES (:pid, :cid)
    ");

    foreach ($categoryIds as $cid) {
        $ins->execute([':pid' => $projectId, ':cid' => $cid]);
    }
}

/**
 * Construit le payload projet depuis $_POST
 * - multi catégories via category_ids[]
 * - subtext requis (car form required)
 * - ✅ AJOUT: *_description (TinyMCE)
 */
function project_payload_from_post(array $post): array {
    $title = trim($post['title'] ?? '');
    if ($title === '') {
        throw new InvalidArgumentException("Le titre est obligatoire.");
    }

    $category_ids = $post['category_ids'] ?? [];
    if (!is_array($category_ids)) $category_ids = [];
    $category_ids = array_values(array_unique(array_filter(array_map('intval', $category_ids), fn($v) => $v > 0)));
    if (count($category_ids) === 0) {
        throw new InvalidArgumentException("Choisis au moins une catégorie.");
    }

    $contact = trim($post['contact'] ?? '');
    $info_1_title = trim($post['info_1_title'] ?? '');
    $info_2_title = trim($post['info_2_title'] ?? '');

    $subtext = trim($post['subtext'] ?? '');
    if ($subtext === '') {
        throw new InvalidArgumentException("La présentation est obligatoire.");
    }

    // ✅ Descriptions TinyMCE (on garde la string brute HTML)
    $contact_description = (string)($post['contact_description'] ?? '');
    $info_1_description  = (string)($post['info_1_description'] ?? '');
    $info_2_description  = (string)($post['info_2_description'] ?? '');

    $desc = (string)($post['description'] ?? '');

    $contact = ($contact === '') ? null : $contact;

    return [
        'title'               => $title,
        'category_ids'        => $category_ids,

        'contact'             => $contact,
        'contact_description' => $contact_description,

        'info_1_title'        => ($info_1_title === '') ? null : $info_1_title,
        'info_1_description'  => $info_1_description,

        'info_2_title'        => ($info_2_title === '') ? null : $info_2_title,
        'info_2_description'  => $info_2_description,

        'subtext'             => $subtext,
        'description'         => $desc,
    ];
}

/**
 * ===== WRITE =====
 */

function createProject(array $data): int {
    global $pdo;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO projects (
                title,
                contact,
                contact_description,
                info_1_title,
                info_1_description,
                info_2_title,
                info_2_description,
                subtext,
                description
            )
            VALUES (
                :title,
                :contact,
                :contact_description,
                :info_1_title,
                :info_1_description,
                :info_2_title,
                :info_2_description,
                :subtext,
                :description
            )
        ");

        $stmt->execute([
            ':title'               => $data['title'],
            ':contact'             => $data['contact'],
            ':contact_description' => $data['contact_description'],

            ':info_1_title'        => $data['info_1_title'],
            ':info_1_description'  => $data['info_1_description'],

            ':info_2_title'        => $data['info_2_title'],
            ':info_2_description'  => $data['info_2_description'],

            ':subtext'             => $data['subtext'],
            ':description'         => $data['description'],
        ]);

        $id = (int)$pdo->lastInsertId();

        setProjectCategories($id, $data['category_ids']);

        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function updateProject(int $id, array $data): bool {
    global $pdo;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            UPDATE projects
            SET title = :title,
                contact = :contact,
                contact_description = :contact_description,
                info_1_title = :info_1_title,
                info_1_description = :info_1_description,
                info_2_title = :info_2_title,
                info_2_description = :info_2_description,
                subtext = :subtext,
                description = :description
            WHERE id = :id
        ");

        $stmt->execute([
            ':id'                  => $id,
            ':title'               => $data['title'],
            ':contact'             => $data['contact'],
            ':contact_description' => $data['contact_description'],

            ':info_1_title'        => $data['info_1_title'],
            ':info_1_description'  => $data['info_1_description'],

            ':info_2_title'        => $data['info_2_title'],
            ':info_2_description'  => $data['info_2_description'],

            ':subtext'             => $data['subtext'],
            ':description'         => $data['description'],
        ]);

        setProjectCategories($id, $data['category_ids']);

        $pdo->commit();
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function deleteProject(int $id): bool {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->rowCount() > 0;
}

/**
 * ===== ACTION ROUTER =====
 */
function handleProjectPostActions(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;

    $action = $_POST['action'] ?? '';
    $wantsJson = in_array($action, ['create_projet','update_project','delete_project','get_project'], true);

    try {
        if ($action === 'create_projet') {
            $data = project_payload_from_post($_POST);
            $id = createProject($data);

            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'id' => $id]);
                exit;
            }

            header("Location: ?success=1&created_id=" . $id);
            exit;
        }

        if ($action === 'update_project') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException("ID invalide.");
            $data = project_payload_from_post($_POST);
            updateProject($id, $data);

            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'id' => $id]);
                exit;
            }

            header("Location: ?success=1&updated_id=" . $id);
            exit;
        }

        if ($action === 'delete_project') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException("ID invalide.");
            deleteProject($id);

            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
                exit;
            }

            header("Location: ?success=1&deleted_id=" . $id);
            exit;
        }

        if ($action === 'get_project') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException("ID invalide.");

            $p = getProjectById($id);
            if (!$p) throw new InvalidArgumentException("Projet introuvable.");

            // ✅ Pour l'édition : renvoie les catégories cochées
            $p['category_ids'] = getProjectCategoryIds($id);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'project' => $p]);
            exit;
        }

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
            exit;
        }

        header("Location: ?error=1&msg=" . urlencode("Action inconnue."));
        exit;

    } catch (Throwable $e) {
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        header("Location: ?error=1&msg=" . urlencode($e->getMessage()));
        exit;
    }
}