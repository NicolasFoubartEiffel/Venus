<?php
require_once 'db/functions.php';
$projects = getAllProjects();
$groupedProjects = groupProjectsByStatusAndCategory($projects);
$categories = getAllCategories();


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil – Projets Venus</title>
    <script src="scripts/main.js" defer></script>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<h1>Liste des projets</h1>
<nav>
    <a href="admin.php">⚙️ Admin</a>
</nav>
<b><u>Cliquez sur une carte pour obtenir plus d'informations</u></b>
<div class="layout-wrapper">
    <!-- Colonne sticky avec les catégories importantes -->
    <div class="sticky-wrapper">
        <?php foreach ($categories as $cat): ?>
            <?php if ($cat['important'] == 1): ?>
                <div class="project-column sticky-column">
                    <h3><?= htmlspecialchars($cat['name']) ?></h3>
                    <?php $list = $groupedProjects[$cat['id']] ?? []; ?>
                    <?php if (empty($list)): ?>
                        <p><em>Aucun projet</em></p>
                    <?php else: ?>
                        <?php foreach ($list as $p): ?>
                            <?php include 'cards.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="category-grid">
        <?php
        $counter = -1;
        foreach ($categories as $cat):
        // Si on a déjà affiché 5 catégories, on commence une nouvelle colonne
        if ($counter % 5 == 0 && $counter > 0): ?>
    </div><div class="category-column">
        <?php endif; ?>

        <?php if ($cat['important'] != 1): ?>
            <div class="category-pill" data-cat-id="<?= $cat['id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </div>
            <div class="project-container" id="projects-cat-<?= $cat['id'] ?>"></div>
        <?php endif; ?>

        <?php $counter++; endforeach; ?>
    </div>


</div>

</body>
</html>
