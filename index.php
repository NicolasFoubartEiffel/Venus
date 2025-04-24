<?php
require_once __DIR__ . '/db/functions.php';
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
<div class="project-columns">
    <?php foreach ($categories as $cat): ?>
        <?php $stickyClass = ($cat['important'] == 1) ? 'sticky-column' : ''; ?>
        <div class="project-column <?= $stickyClass ?>">
            <h3><?= htmlspecialchars($cat['name']) ?></h3>

            <?php
            $list = $groupedProjects[$cat['id']] ?? [];
            ?>

            <?php if (empty($list)): ?>
                <p><em>Aucun projet</em></p>
            <?php else: ?>
                <?php foreach ($list as $p): ?>
                    <div class="project-card">
                        <!-- Titre -->
                        <div class="title-header">
                            <h2><?= htmlspecialchars($p['title']) ?></h2>
                        </div>

                        <!-- Tâche -->
                        <?php if (!empty($p['task'])): ?>
                            <div class="task"><em>Tâche :</em> <?= nl2br(htmlspecialchars($p['task'])) ?></div>
                        <?php endif; ?>

                        <!-- Lien + indice -->
                        <?php if (!empty($p['link'])): ?>
                            <div class="link">
                                <?php if (isValidUrl($p['link'])): ?>
                                    <a href="<?= htmlspecialchars($p['link']) ?>" target="_blank">🔗 Lien vers l'application</a>
                                <?php else: ?>
                                    <?= htmlspecialchars($p['link']) ?>
                                <?php endif; ?>

                                <?php if (!empty($p['hint'])): ?>
                                    <?php if (isValidUrl($p['hint'])): ?>
                                        <br><sub><a href="<?= htmlspecialchars($p['hint']) ?>" target="_blank">🔗 Tutoriel</a></sub>
                                    <?php else: ?>
                                        <br><sub><?= htmlspecialchars($p['hint']) ?></sub>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>




                        <div class="more-info">
                            <!-- Dates -->
                            <?php if (!empty($p['start_date']) || !empty($p['end_date'])): ?>
                                <div class="dates">
                                    Période :
                                    📅
                                    <b><?= $p['start_date'] ? formatDateFr($p['start_date']) : '—' ?></b>
                                    -
                                    <b><?= $p['end_date'] ? formatDateFr($p['end_date']) : '—' ?></b>
                                </div>
                            <?php endif; ?>

                            <!-- Description -->
                            <?php if (!empty($p['description'])): ?>
                                <u>Description</u> :
                                <div class="desc">
                                    <?= nl2br(htmlspecialchars($p['description'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
