<main class="admin-right is-density-dense">
    <div class="admin-list-head">
        <h2>Projets existants</h2>

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

    <?php require 'partials/admin/admin_tracking_stats.php'; ?>
</main>
