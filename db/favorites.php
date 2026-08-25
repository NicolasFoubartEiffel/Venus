<?php
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

try {
    $USER = isset($_SESSION['ldap_data'][0]) && is_array($_SESSION['ldap_data'][0])
        ? (object) $_SESSION['ldap_data'][0]
        : null;

    if ($USER === null) {
        throw new RuntimeException("Utilisateur non connecté.");
    }

    $uid = trim((string)($USER->uid[0] ?? ''));
    $username = trim((string)($USER->displayname[0] ?? $uid));

    if ($uid === '') {
        throw new RuntimeException("UID utilisateur introuvable.");
    }

    $action = $_POST['action'] ?? '';

    if ($action !== 'toggle_favorite') {
        throw new RuntimeException("Action inconnue.");
    }

    $projectId = (int)($_POST['project_id'] ?? 0);

    if ($projectId <= 0) {
        throw new RuntimeException("Projet invalide.");
    }

    $isFavorite = toggleFavorite($uid, $username, $projectId);

    echo json_encode([
        'success' => true,
        'project_id' => $projectId,
        'is_favorite' => $isFavorite,
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
    exit;
}