<?php
require_once 'db/project.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit('ID manquant ou invalide');
}

$id = (int) $_GET['id'];
$projects = getProjectsByCategoryId($id);

if (!$projects) {
    echo "<p style='padding: 1rem; font-style: italic;'>Aucun projet dans cette catégorie.</p>";
    exit;
}

// On boucle et on utilise le template
foreach ($projects as $p) {
    include 'cards.php'; // adapte le chemin si besoin
}
