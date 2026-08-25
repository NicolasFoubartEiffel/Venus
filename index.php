<?php
require_once 'db/functions.php';

$categories = getAllCategories();

$pageTitle = "Accueil – CRAc-RF";
$isAdmin = false;
require_once 'partials/header.php';

$projects = getAllProjects($USER->uid[0] ?? null);
$mails = getAllMails();

?>

<section class="filters-panel" aria-label="Filtres et accès rapides">
    <div class="filters-panel__head">
        <div class="filters-panel__title-wrap">
            <p class="filters-panel__eyebrow">Navigation</p>
            <h2 class="filters-panel__title">Explorer les rubriques</h2>
        </div>

        <div class="quick-links" aria-label="Liens utiles">
            <a href="https://view.genially.com/69f316030093a708fe06ffae"
               class="quick-link-card"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="Ouvrir le calendrier de formation dans un nouvel onglet"
               title="Calendrier de formation">
                <span class="quick-link-card__icon" aria-hidden="true">📅</span>
                <span class="quick-link-card__content">
                    <span class="quick-link-card__title">Calendrier de formation</span>
                    <span class="quick-link-card__text">Consulter les dates et sessions</span>
                </span>
            </a>

            <button
                    type="button"
                    class="quick-link-card"
                    data-open-calendar
                    aria-label="Ouvrir le fil d'actualités"
            >
                <span class="quick-link-card__icon" aria-hidden="true">📰</span>
                <span class="quick-link-card__content">
        <span class="quick-link-card__title">Fils d'actualités</span>
        <span class="quick-link-card__text">Voir les dernières informations</span>
    </span>
            </button>
        </div>
    </div>

    <div class="category-box">
        <div class="category-box__label">Filtrer par catégorie</div>

        <div class="category-selection" role="tablist" aria-label="Catégories">
            <button type="button" class="cat-pill is-active" data-category-id="all" aria-selected="true">
                Tout
            </button>
            <?php foreach ($categories as $category): ?>
                <button
                        type="button"
                        class="cat-pill cat-pill--<?= (int)($category['id'] ?? 0) ?>"
                        data-category-id="<?= (int)($category['id'] ?? 0) ?>"
                        aria-selected="false"
                >
                    <?= htmlspecialchars($category['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="search-box" role="search">
        <label class="search-box__label" for="project-search">Rechercher une rubrique</label>
        <div class="search-box__control">
            <input
                    type="search"
                    id="project-search"
                    class="search-box__input"
                    placeholder="Titre, categorie, contact, ressource..."
                    autocomplete="off"
                    data-project-search
            >
            <button
                    type="button"
                    class="search-box__clear"
                    data-clear-search
                    aria-label="Effacer la recherche"
                    title="Effacer la recherche"
                    hidden
            >
                x
            </button>
        </div>
    </div>
</section>

<div class="hint" data-empty-results hidden>
    Aucune rubrique ne correspond a cette recherche.
</div>

<div class="layout-wrapper">
    <div class="project-grid">
        <?php require_once 'partials/cards.php'; ?>
    </div>
</div>

<?php require_once 'partials/expended_cards.php'; ?>
<?php require_once 'partials/expended_calendar.php'; ?>
<?php require_once 'partials/footer.php'; ?>
<div id="copy-toast" class="copy-toast" aria-hidden="true">
    Le lien a été copié dans votre presse papier
</div>
</body>
</html>
