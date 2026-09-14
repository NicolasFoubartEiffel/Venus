<?php
if (!function_exists('admin_project_has_content')) {
    function admin_project_has_content(string $html): bool
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string)$text) !== '';
    }
}

$adminCategories = $categories ?? [];
$totalProjects = count($projects ?? []);
$hiddenProjects = 0;
$incompleteProjects = 0;

foreach (($projects ?? []) as $projectForCount) {
    if ((int)($projectForCount['hidden'] ?? 0) === 1) {
        $hiddenProjects += 1;
    }

    $hasAllCoreSections = admin_project_has_content((string)($projectForCount['contact_details'] ?? ''))
        && admin_project_has_content((string)($projectForCount['resources_details'] ?? ''))
        && admin_project_has_content((string)($projectForCount['description_details'] ?? ''));

    if (!$hasAllCoreSections) {
        $incompleteProjects += 1;
    }
}
?>

<main class="admin-right is-density-dense">
    <div class="admin-list-head">
        <div>
            <h2>Projets existants</h2>
            <p class="admin-list-counts">
                <?= (int) $totalProjects ?> tuile(s)
                &middot; <?= (int) $hiddenProjects ?> masqu&eacute;e(s)
                &middot; <?= (int) $incompleteProjects ?> &agrave; compl&eacute;ter
            </p>
        </div>

        <fieldset class="density-toggle" aria-label="Densit&eacute; d'affichage">
            <label>
                <input type="radio" name="admin_density" value="dense" data-admin-density checked>
                <span>Dense</span>
            </label>

            <label>
                <input type="radio" name="admin_density" value="normal" data-admin-density>
                <span>Normal</span>
            </label>
        </fieldset>
    </div>

    <div class="admin-search" role="search">
        <label class="admin-search__label" for="admin-project-search">Rechercher une rubrique</label>
        <div class="admin-search__control">
            <input
                    type="search"
                    id="admin-project-search"
                    class="admin-search__input"
                    placeholder="Nom de rubrique..."
                    autocomplete="off"
                    data-admin-project-search
            >
            <button
                    type="button"
                    class="admin-search__clear"
                    data-admin-clear-search
                    aria-label="Effacer la recherche"
                    hidden
            >
                &times;
            </button>
        </div>
    </div>

    <div class="admin-filter-chips" aria-label="Filtres des projets">
        <button type="button" class="admin-filter-chip is-active" data-admin-filter="all">Tous</button>
        <button type="button" class="admin-filter-chip" data-admin-filter="hidden">Masqu&eacute;s</button>
        <button type="button" class="admin-filter-chip" data-admin-filter="incomplete">&Agrave; compl&eacute;ter</button>

        <?php foreach ($adminCategories as $cat): ?>
            <button type="button" class="admin-filter-chip" data-admin-filter="category" data-category-id="<?= (int)($cat['id'] ?? 0) ?>">
                <?= htmlspecialchars(categoryDisplayName((string)($cat['nom'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="project-columns-admin">
        <div class="project-column" id="sortable-projects">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $p): ?>
                    <?php require 'partials/admin/admin_project_card.php'; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p><em>Aucun projet</em></p>
            <?php endif; ?>
        </div>

        <p class="admin-search__empty" data-admin-search-empty hidden>
            Aucun projet ne correspond &agrave; cette recherche.
        </p>

        <form id="hidden-form" action="db/project.php" method="post" style="display:none;">
            <input type="hidden" name="action" value="">
            <input type="hidden" name="id" value="">
        </form>
    </div>

</main>
