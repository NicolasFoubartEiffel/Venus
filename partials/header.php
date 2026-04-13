<?php
$isAdmin = $isAdmin ?? false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? 'Projets Venus' ?></title>

    <!-- CSS commun -->
    <link rel="stylesheet" href="styles/style.css">

    <?php if ($isAdmin): ?>
        <link rel="stylesheet" href="styles/admin.css">
    <?php endif; ?>

    <!-- JS commun -->
    <script src="scripts/main.js" defer></script>

    <?php if ($isAdmin): ?>
        <script src="assets/vendor/tinymce/tinymce.min.js"></script>
        <script src="scripts/admin.js" defer></script>
    <?php endif; ?>
</head>
<body>

<header class="page-header">
    <div class="header-top">
        <div class="header-left">
            <img src="assets/icons/Logo_UGE.png" class="logo" alt="Logo UGE">
        </div>


        <nav>
            <h1><?= !($isAdmin) ? '🏠 Plateforme CRAc-RF' : '⚙️ Admin CRAc-RF' ?></h1>
        </nav>

        <div class="header-right">
            <span class="project-name">CRAc-RF</span>

            <a class="admin-link" href="<?= $isAdmin ? 'index.php' : 'admin.php' ?>">
                <?= $isAdmin ? '🏠 Accueil' : '⚙️ Admin' ?>
            </a>
        </div>
    </div>
</header>