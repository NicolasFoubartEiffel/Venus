<?php
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create_project') {
        $data = project_payload_from_post($_POST);
        $id = createProject($data);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    if ($action === 'update_project') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new InvalidArgumentException("ID invalide.");
        $data = project_payload_from_post($_POST);
        updateProject($id, $data);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    if ($action === 'delete_project') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new InvalidArgumentException("ID invalide.");
        deleteProject($id);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    // ✅ AJOUT : récupération d'un projet (édition)
    if ($action === 'get_project') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new InvalidArgumentException("ID invalide.");
        $p = getProjectById($id);
        if (!$p) throw new InvalidArgumentException("Projet introuvable.");
        // ✅ AJOUT CRITIQUE
        $p['category_ids'] = getProjectCategoryIds($id);
        echo json_encode(['success' => true, 'project' => $p]);
        exit;
    }


    echo json_encode(['success' => false, 'message' => 'Action inconnue: ' . $action]);
    exit;

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
