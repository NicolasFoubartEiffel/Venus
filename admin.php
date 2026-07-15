<?php
require_once __DIR__ . '/db/functions.php';
$projects = getAllProjects();
$categories = getAllCategories();

$pageTitle = "Administration – CRAc-RF";
$isAdmin = true;
require_once 'partials/header.php';

?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<div class="admin-layout">
    <?php require 'partials/admin/admin_layout_left.php'; ?>
    <?php require 'partials/admin/admin_layout_right.php'; ?>
</div>

<?php require_once 'partials/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const list = document.getElementById('sortable-projects');
        if (!list || typeof Sortable === 'undefined') return;
        new Sortable(list, {
            animation: 150,
            draggable: '.project-card',
            filter: '.admin-action-btn, a, input, textarea, select',
            preventOnFilter: false,

            onEnd: () => {
                const orderedIds = Array.from(list.querySelectorAll('.project-card'))
                    .map(card => card.dataset.projectId)
                    .filter(Boolean);

                fetch('db/project.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({
                        action: 'update_project_order',
                        ids: JSON.stringify(orderedIds)
                    })
                });
            }
        });
    });
</script>

</body>
</html>