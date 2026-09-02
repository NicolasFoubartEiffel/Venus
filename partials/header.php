<?php
global $USER;

$USER = getCurrentLdapUser();

if ($USER === null) {
    redirectToLoginPage();
}

$currentUser = $USER->uid[0] ?? '';

$favorites = [];

if ($currentUser !== '') {
    $favorites = getUserFavoriteProjectIds($currentUser);
}

$csrfToken = getCsrfToken();
$isAdmin = isAdminUsername($currentUser);
$isAdminPage = basename($_SERVER['PHP_SELF']) === 'admin.php';

$pageTitle = $pageTitle ?? 'Projets Venus';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="styles/main.css">

    <?php if ($isAdmin): ?>
        <link rel="stylesheet" href="styles/admin.css">
    <?php endif; ?>

    <script src="scripts/main.js" defer></script>
    <script src="scripts/favorites.js"></script>

    <?php if ($isAdmin): ?>
        <script src="assets/vendor/tinymce/tinymce.min.js"></script>
        <script src="scripts/admin.js" defer></script>
    <?php endif; ?>
</head>
<body>

<header class="page-header">
    <div class="header-top">
        <div class="header-left">
            <a href="index.php" class="logo-link" aria-label="Retour a l'accueil CRAc-RF">
                <img src="assets/icons/Logo_UGE.png" class="logo" alt="Logo UGE">
            </a>
        </div>

        <div class="header-main">
            <h1 class="page-title">
                <?= $isAdminPage ? 'Administration CRAc-RF' : 'Centre de Ressources et d\'Accompagnement pour <br>la Responsabilit&eacute; de Formation' ?>
            </h1>
            <p class="header-subtitle">
                <?= $isAdminPage ? 'Gestion des contenus' : 'CRAc-RF' ?>
            </p>
        </div>

        <div class="header-right">
            <?php if ($isAdmin): ?>
                <?php if ($isAdminPage): ?>
                    <button
                            type="button"
                            class="admin-header-tool"
                            data-admin-stats-toggle
                            aria-controls="admin-tracking-stats"
                            aria-expanded="false"
                    >
                        Statistiques des tuiles
                    </button>
                <?php endif; ?>

                <a class="admin-link" href="<?= $isAdminPage ? 'index.php' : 'admin.php' ?>">
                    <span class="admin-link-icon" aria-hidden="true"><?= $isAdminPage ? '&larr;' : '&#9881;' ?></span>
                    <span><?= $isAdminPage ? 'Retour accueil' : 'Administration' ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
