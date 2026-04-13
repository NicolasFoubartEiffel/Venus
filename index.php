<?php
require_once 'db/functions.php';
$projects = getAllProjects();
$categories = getAllCategories();

$pageTitle = "Accueil – CRAc-RF";
$isAdmin = false;
require_once 'partials/header.php';
?>

<div class="category-bar">
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

    <a href="https://view.genially.com/6698c94b454e30b004d2b7d1"
       class="floating-calendar-btn"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Ouvrir le calendrier de formation dans un nouvel onglet"
       title="Calendrier de formation">
        <span class="floating-calendar-btn__icon">📅</span>
        <span class="floating-calendar-btn__text">Calendrier de formation</span>
    </a>
</div>

<div class="hint">
    Cliquez sur une rubrique pour obtenir plus d'informations.
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