<?php
require_once 'db/functions.php';
$projects = getAllProjects();
$categories = getAllCategories();

$pageTitle = "Accueil – Projets Venus";
$isAdmin = false;
require_once 'partials/header.php';
?>

<div class="category-selection" role="tablist" aria-label="Catégories">
    <button type="button" class="cat-pill is-active" data-category-id="all" aria-selected="true">
        Tout
    </button>

    <?php foreach ($categories as $category): ?>
        <button type="button" class="cat-pill" data-category-id="<?= (int)$category['id'] ?>" aria-selected="false">
            <?= htmlspecialchars($category['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </button>
    <?php endforeach; ?>
</div>

<div class="hint">
    Cliquez sur une tuile pour obtenir plus d'informations.
</div>

<div class="layout-wrapper">
    <div class="project-grid">
            <!-- Display global -->
            <?php require_once 'partials/cards.php'; ?>
    </div>
</div>

<!-- Display modal -->
<?php require_once 'partials/expended_cards.php'; ?>

<?php require_once 'partials/footer.php'; ?>
</body>
</html>