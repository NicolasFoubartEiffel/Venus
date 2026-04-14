<?php
global $USER;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$USER = isset($_SESSION['ldap_data'][0]) && is_array($_SESSION['ldap_data'][0])
    ? (object) $_SESSION['ldap_data'][0]
    : null;

if ($USER === null) {
    header('Location: /apps/index.php?app=/apps/venus/index.php');
    exit;
}

$currentUser = $USER->uid[0] ?? '';

$adminList = [
    'nicolas.foubart',
    'mickael.huneau',
    'jonathan.gibert',
    'jamila.al-khatib'
];

$isAdmin = in_array($currentUser, $adminList, true);

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

        <?php $isAdminPage = basename($_SERVER['PHP_SELF']) === 'admin.php'; ?>

        <nav>
            <h1><?= !($isAdmin) ? '🏠 Plateforme CRAc-RF' : '⚙️ Admin CRAc-RF' ?></h1>
        </nav>

        <div class="header-right">
            <span class="project-name">CRAc-RF</span>

            <?php if ($isAdmin): ?>
                <a class="admin-link" href="<?= $isAdminPage ? 'index.php' : 'admin.php' ?>">
                    <?= $isAdminPage ? '🏠 Retour accueil' : '⚙️ Panneau d’administration' ?>
                </a>
            <?php endif; ?>
        </div>

    </div>
</header>