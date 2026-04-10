<main class="admin-right">
    <h2>Projets existants</h2>

    <div class="hint">
        Cliquez sur une rubrique pour obtenir plus d'informations.
    </div>

    <div class="project-columns-admin">
        <div class="project-column">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $p): ?>
                    <?php require 'partials/admin/admin_project_card.php'; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p><em>Aucun projet</em></p>
            <?php endif; ?>
        </div>

        <form id="hidden-form" action="db/project.php" method="post" style="display:none;">
            <input type="hidden" name="action" value="">
            <input type="hidden" name="id" value="">
        </form>
    </div>
</main>