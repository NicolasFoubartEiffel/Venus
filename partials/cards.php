<?php foreach ($projects as $p): ?>
    <?php
    $projectId = (int)($p['id'] ?? 0);

    // Champs texte
    $title             = trim((string)($p['title'] ?? ''));
    $subtext           = trim((string)($p['subtext'] ?? ''));
    $contactTitle      = trim((string)($p['contact_title'] ?? ''));
    $resourcesTitle    = trim((string)($p['resources_title'] ?? ''));
    $descriptionTitle  = trim((string)($p['description_title'] ?? ''));

    // HTML
    $contactDetails     = (string)($p['contact_details'] ?? '');
    $resourcesDetails   = (string)($p['resources_details'] ?? '');
    $descriptionDetails = (string)($p['description_details'] ?? '');

    // Description principale = avancée (OK)
    $projectDescription = $descriptionDetails;

    // Catégories
    $categoryIds = !empty($p['category_ids']) && is_array($p['category_ids'])
        ? array_map('intval', $p['category_ids'])
        : [];

    $categoryIdsAttr = implode(',', $categoryIds);

    // Tooltips
    $titleContact = ($contactTitle === '' || mb_strtolower($contactTitle) === 'non renseigné')
        ? 'Non renseigné'
        : 'Contact';

    $titleResources = ($resourcesTitle === '' || mb_strtolower($resourcesTitle) === 'non renseigné')
        ? 'Non renseigné'
        : 'Ressources documentaires';

    $titleDescription = ($descriptionTitle === '' || mb_strtolower($descriptionTitle) === 'non renseigné')
        ? 'Non renseigné'
        : 'Description avancée';
    ?>

    <article
            class="project-tile"
            data-project-id="<?= $projectId ?>"
            data-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
            data-subtext="<?= htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8') ?>"
            data-contact-title="<?= htmlspecialchars($contactTitle, ENT_QUOTES, 'UTF-8') ?>"
            data-resources-title="<?= htmlspecialchars($resourcesTitle, ENT_QUOTES, 'UTF-8') ?>"
            data-description-title="<?= htmlspecialchars($descriptionTitle, ENT_QUOTES, 'UTF-8') ?>"
            data-category-ids="<?= htmlspecialchars($categoryIdsAttr, ENT_QUOTES, 'UTF-8') ?>"
    >
        <!-- ORDRE GARANTI -->
        <template class="tpl-contact-details"><?= $contactDetails ?></template>
        <template class="tpl-resources-details"><?= $resourcesDetails ?></template>
        <template class="tpl-description-details"><?= $descriptionDetails ?></template>

        <template class="tpl-project-description"><?= $projectDescription ?></template>

        <div class="tile-head">
            <div class="tile-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>

            <?php if ($subtext !== ''): ?>
                <div class="tile-subtext"><?= htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="tile-actions">
                <!-- 1️⃣ CONTACT -->
                <button
                        class="tile-btn"
                        type="button"
                        title="<?= htmlspecialchars($titleContact, ENT_QUOTES, 'UTF-8') ?>"
                        aria-label="Contacter"
                >
                    <img src="assets/icons/mail_icon.png" alt="">
                </button>

                <!-- 2️⃣ RESSOURCES -->
                <button
                        class="tile-btn"
                        type="button"
                        title="<?= htmlspecialchars($titleResources, ENT_QUOTES, 'UTF-8') ?>"
                        aria-label="Ressources"
                >
                    <img src="assets/icons/mail_link.png" alt="">
                </button>

                <!-- 3️⃣ DESCRIPTION AVANCÉE (TOUJOURS DERNIER) -->
                <button
                        class="tile-btn"
                        type="button"
                        title="<?= htmlspecialchars($titleDescription, ENT_QUOTES, 'UTF-8') ?>"
                        aria-label="Description avancée"
                >
                    <img src="assets/icons/mail_doc.png" alt="">
                </button>
            </div>
        </div>

        <div class="tile-expand" aria-hidden="true"></div>
    </article>
<?php endforeach; ?>