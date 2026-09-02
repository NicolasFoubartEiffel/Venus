<?php

function handleProjectPostActions(): void
{
    global $pdo;

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Methode non autorisee.',
        ]);
        exit;
    }

    if (($_POST['action'] ?? '') === '') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action manquante.',
        ]);
        exit;
    }

    $action = $_POST['action'] ?? '';

    $jsonActions = [
        'create_project',
        'update_project',
        'delete_project',
        'get_project',
        'toggle_visibility',
        'update_project_order',
    ];

    $wantsJson = in_array($action, $jsonActions, true);

    requireCurrentAdminJson();
    requireValidCsrfTokenJson();

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
