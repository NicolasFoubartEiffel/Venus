<?php
require_once 'connect.php';

function getAllProjects() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT 
            *
        FROM projects
        ORDER BY start_date ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllCategories() {
    global $pdo;
    return $pdo->query("SELECT * FROM categories ORDER BY important DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
}

function getImportantCategory() {
    global $pdo;
    return $pdo->query("SELECT * FROM categories WHERE important = 1")->fetchAll(PDO::FETCH_ASSOC);
}

function isValidUrl($url) {
    return !empty($url) && filter_var($url, FILTER_VALIDATE_URL);
}


function formatDateFr($date) {
    if (empty($date)) return '';
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt ? $dt->format('d/m/Y') : $date;
}


function groupProjectsByStatusAndCategory(array $projects): array {
    $result = [];

    foreach ($projects as $p) {
        $categoryId = $p['category_id'] ?? 0;

        if (!isset($result[$categoryId])) {
            $result[$categoryId] = [];
        }

        $result[$categoryId][] = $p;
    }

    return $result;
}


