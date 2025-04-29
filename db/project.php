<?php
require_once __DIR__ . '/connect.php';

function createCategory(string $name, mixed $important = 0): bool {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Si une nouvelle catégorie est marquée prioritaire,
        // on retire le statut prioritaire à toutes les autres
        if ($important == 1) {
            $pdo->exec("UPDATE categories SET important = 0 WHERE important = 1");
        }

        // Insérer la nouvelle catégorie
        $stmt = $pdo->prepare("INSERT INTO categories (name, important) VALUES (:name, :important)");
        $success = $stmt->execute([
            'name' => $name,
            'important' => $important
        ]);

        $pdo->commit(); // Appliquer la transaction
        return $success;
    } catch (Exception $e) {
        $pdo->rollBack(); // Annuler la transaction en cas d'erreur
        error_log("Erreur création catégorie : " . $e->getMessage());
        return false;
    }
}

function getProjectById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getProjectsByCategoryId(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE category_id = :id");
    $stmt->execute(['id' => $id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results ?: null;
}


function createProject(array $data): void {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO projects (title, task, link, hint, description, category_id, start_date, end_date)
        VALUES (:title, :task, :link, :hint, :description, :category_id, :start_date, :end_date)
    ");
    $stmt->execute([
        'title' => $data['title'],
        'task' => $data['task'],
        'link' => $data['link'],
        'hint' => $data['hint'],
        'description' => $data['description'] ?? null,
        'category_id' => $data['category_id'] ?: null,
        'start_date' => $data['start_date'] ?: null,
        'end_date' => $data['end_date'] ?: null
    ]);
}

function deleteProject(int $id): void {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

function updateProject(int $id, array $data): void {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE projects SET
        title = :title,
        description = :description,
        category_id = :category_id,
        status_id = :status_id,
        start_date = :start_date,
        end_date = :end_date,
        parent_id = :parent_id
        WHERE id = :id
    ");
    $stmt->execute([
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'category_id' => $data['category_id'] ?: null,
        'status_id' => $data['status_id'] ?: null,
        'start_date' => $data['start_date'] ?: null,
        'end_date' => $data['end_date'] ?: null,
        'parent_id' => $data['parent_id'] ?: null,
        'id' => $id,
    ]);
}

// ----- ROUTER BAS� SUR L'ACTION -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'create') {
        createProject($_POST);
        header('Location: ../admin.php');
        exit;
    }

    if ($action === 'update' && isset($_POST['id'])) {
        updateProject((int)$_POST['id'], $_POST);
        header('Location: ../admin.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? null;

    if ($action === 'create_category') {
        if (!empty($_POST['name'])) {
            $name = trim($_POST['name']);
            $important = $_POST['important'] ?? 0;
            $success = createCategory($name, $important);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nom requis']);
        }
        exit;
    }

    if ($action === 'create_projet') {
        createProject($_POST);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'update' && isset($_POST['id'])) {
        updateProject((int)$_POST['id'], $_POST);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    exit;
}

