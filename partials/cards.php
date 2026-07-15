<?php $favorites = $favorites ?? []; ?>

<?php foreach ($projects as $p): ?>
    <?php
    $projectId = (int)($p['id'] ?? 0);

    $title   = trim((string)($p['title'] ?? ''));
    $subtext = trim((string)($p['subtext'] ?? ''));
    $titleHtml = str_replace(
        '/',
        '/<wbr>',
        htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
    );

    $contactDetails     = (string)($p['contact_details'] ?? '');
    $resourcesDetails   = (string)($p['resources_details'] ?? '');
    $descriptionDetails = (string)($p['description_details'] ?? '');

    $projectDescription = $descriptionDetails;

    $categories = !empty($p['categories']) && is_array($p['categories'])
        ? $p['categories']
        : [];

    $categoryNames = array_map(
        function ($category) {
            return trim((string)($category['name'] ?? ''));
        },
        $categories
    );

    $searchText = html_entity_decode(implode(' ', array_filter([
        $title,
        $subtext,
        strip_tags($contactDetails),
        strip_tags($resourcesDetails),
        strip_tags($descriptionDetails),
        implode(' ', $categoryNames),
    ])), ENT_QUOTES, 'UTF-8');

    $categoryIds = array_map(
        function ($category) {
            return (int)($category['id'] ?? 0);
        },
        $categories
    );

    $categoryIdsAttr = implode(',', array_filter($categoryIds));

    $titleContact = trim(strip_tags($contactDetails)) === ''
        ? 'Non renseign&eacute;'
        : 'Contact';

    $titleResources = trim(strip_tags($resourcesDetails)) === ''
        ? 'Non renseign&eacute;'
        : 'Ressources';

    $titleDescription = trim(strip_tags($descriptionDetails)) === ''
        ? 'Non renseign&eacute;'
        : 'Description';

    $hasSubtext = ($subtext !== '');
    $isFavorite = in_array($projectId, $favorites, true);
    ?>

    <article
            class="project-tile"
            data-project-id="<?= $projectId ?>"
            data-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
            data-subtext="<?= htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8') ?>"
            data-category-ids="<?= htmlspecialchars($categoryIdsAttr, ENT_QUOTES, 'UTF-8') ?>"
            data-search-text="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
    >
        <button
                type="button"
                class="favorite-control favorite-badge <?= $isFavorite ? 'is-active' : '' ?>"
                data-project-id="<?= $projectId ?>"
                aria-label="<?= $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>"
                title="<?= $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>"
        >
            <?= $isFavorite ? '&#9733;' : '&#9734;' ?>
        </button>

        <template class="tpl-contact-details"><?= $contactDetails ?></template>
        <template class="tpl-resources-details"><?= $resourcesDetails ?></template>
        <template class="tpl-description-details"><?= $descriptionDetails ?></template>
        <template class="tpl-project-description"><?= $projectDescription ?></template>

        <div class="tile-body">
            <div class="tile-head">
                <div class="tile-title"><?= $titleHtml ?></div>

                <?php if ($hasSubtext): ?>
                    <div class="tile-subtext"><?= htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8') ?></div>
                <?php else: ?>
                    <div class="tile-subtext tile-subtext--empty">Aucune description courte</div>
                <?php endif; ?>
            </div>

            <div class="tile-footer">
                <div class="tile-actions">
                    <button
                            class="tile-btn"
                            type="button"
                            title="<?= $titleDescription ?>"
                            aria-label="Description"
                    >
                        <img src="assets/icons/mail_doc.png" alt="">
                    </button>

                    <button
                            class="tile-btn"
                            type="button"
                            title="<?= $titleResources ?>"
                            aria-label="Ressources"
                    >
                        <img src="assets/icons/mail_link.png" alt="">
                    </button>

                    <button
                            class="tile-btn"
                            type="button"
                            title="<?= $titleContact ?>"
                            aria-label="Contacter"
                    >
                        <img src="assets/icons/mail_icon.png" alt="">
                    </button>
                </div>

                <?php if (!empty($categories)): ?>
                    <div class="tile-categories">
                        <?php foreach ($categories as $category): ?>
                            <?php
                            $categoryId = (int)($category['id'] ?? 0);
                            $categoryName = trim((string)($category['name'] ?? ''));

                            if ($categoryId <= 0 || $categoryName === '') {
                                continue;
                            }

                            $displayName = htmlspecialchars(categoryDisplayName($categoryName), ENT_QUOTES, 'UTF-8');
                            ?>

                            <span
                                    class="tile-category-pill tile-category-pill--<?= $categoryId ?>"
                                    title="<?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <?= $displayName ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tile-expand" aria-hidden="true"></div>
    </article>
<?php endforeach; ?>
