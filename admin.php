<?php
require_once __DIR__ . '/db/functions.php';
$projects = getAllProjects();
$categories = getAllCategories();

$pageTitle = "Administration – Projets Venus";
$isAdmin = true;
require_once 'partials/header.php';

?>

<div class="admin-layout">
    <?php require 'partials/admin/admin_layout_left.php'; ?>
    <?php require 'partials/admin/admin_layout_right.php'; ?>
</div>

<?php require_once 'partials/footer.php'; ?>
</body>
</html>