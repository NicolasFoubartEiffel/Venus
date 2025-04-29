<?php
require_once __DIR__ . '/db/functions.php';
$projects = getAllProjects();
$groupedProjects = groupProjectsByStatusAndCategory($projects);
$categories = getAllCategories();
$important = getImportantCategory();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin – Projets Venus</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/admin.css">
    <script src="scripts/admin.js"></script>
</head>
<body>
<h1>Admin – Gérer les projets</h1>
<nav>
    <a href="index.php">🏠 Accueil</a>
</nav>

<div class="creation">
    <div class="project-form">
        <h2>Ajouter un projet</h2>
        <form id="create-project-form" method="post">
            <input type="hidden" name="action" value="create_projet">
            <input type="text" name="title" placeholder="Titre" required>
            <input type="text" name="task" placeholder="Tache" required>
            <input type="text" name="hint" placeholder="Tutoriel" required>

            <textarea name="description" placeholder="Description"></textarea>
            <input type="text" name="link" placeholder="Lien" required>

            <label>Catégorie :
                <select name="category_id">
                    <option value="">--</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            Période (Optionnel): <br>
            Début : <input type="date" name="start_date">
            Fin : <input type="date" name="end_date">
            <button type="submit">Créer le projet</button>
        </form>
    </div>

    <div class="category-form">
        <h2>Ajouter une catégorie</h2>
        <form id="create-category-form" method="post" data-important='<?= json_encode($important) ?>'>
            <input type="text" name="name" placeholder="Titre" required>
            <?php if ($important) { ?>
            <span>La catégorie prioritaire actuelle est : <b><?= $important[0]['name'] ?></b></span><br><br>
            <?php } ?>
            <div class="prioritaire">
                Catégorie prioritaire :<br>
                <label><input type="radio" value="1" name="important"> Oui</label>
                <label><input type="radio" value="0" name="important" checked> Non</label>
            </div>

            <button type="submit">Créer la catégorie</button>
        </form>
    </div>
</div>

<h2>Projets existants</h2>
<div class="project-columns-admin">
        <div class="project-column">
            <?php foreach ($categories as $cat): ?>
                <h4><?= htmlspecialchars($cat['name']) ?></h4>

                <?php
                $list = $groupedProjects[$cat['id']] ?? [];
                ?>

                <?php if (empty($list)): ?>
                    <p><em>Aucun projet</em></p>
                <?php else: ?>
                    <?php foreach ($list as $p): ?>
                        <?php if ($cat['name'] === $important[0]['name']): ?>
                            <div class="project-card important">
                        <?php else:?>
                            <div class="project-card">
                        <?php endif;?>
                            <div class="title"><?= htmlspecialchars($p['title']) ?></div>
                            <?php if (!empty($p['description'])): ?>
                                <div class="desc"><?= nl2br(htmlspecialchars($p['description'])) ?></div>
                            <?php endif; ?>
                            <div class="actions">
                                <!--<a href="db/projet.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Supprimer ce projet ?')">🗑 Supprimer</a>-->
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>

        </div>
</div>


<script>
    document.getElementById("create-category-form").addEventListener("submit", function(event) {
        const radioOui = document.querySelector('input[name="important"][value="1"]:checked');
        const importantCategory = <?= json_encode($important) ?>; // Injecte la catégorie prioritaire actuelle en PHP

        if (radioOui && importantCategory.length > 0) {
            const message = `La catégorie prioritaire actuelle (${importantCategory[0]['name']}) sera remplacée par cette nouvelle catégorie. Voulez-vous continuer ?`;
            if (!confirm(message)) {
                event.preventDefault(); // Empêche la soumission du formulaire si l'utilisateur annule
            }
        }
    });
</script>
</body>
</html>
