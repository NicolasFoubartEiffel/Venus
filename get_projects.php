<?php
require_once __DIR__ . '/db/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo '<em>Paramètre invalide</em>';
    exit;
}

$p = getProjectById($id);
if (!$p) {
    http_response_code(404);
    echo '<em>Projet introuvable</em>';
    exit;
}

$contact = $p['contact'] ?? '';
$link    = $p['link'] ?? '';
$link2   = $p['link_2'] ?? '';
$desc    = $p['description'] ?? '';

?>
<div class="project-detail">
    <div class="detail-desc">
        <?= $desc !== '' ? $desc : '<em>Aucune description</em>' ?>
    </div>
</div>
