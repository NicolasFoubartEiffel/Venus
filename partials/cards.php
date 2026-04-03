<?php foreach ($projects as $p): ?>
    <?php
    // champs texte simples (ok en data-*)
    $title   = (string)($p['title'] ?? '');
    $subtext = (string)($p['subtext'] ?? '');
    $contact = (string)($p['contact'] ?? '');
    $info1_t = (string)($p['info_1_title'] ?? '');
    $info2_t = (string)($p['info_2_title'] ?? '');

    // champs HTML TinyMCE (NE PAS mettre en data-*)
    $contact_desc = (string)($p['contact_description'] ?? '');
    $info1_desc   = (string)($p['info_1_description'] ?? '');
    $info2_desc   = (string)($p['info_2_description'] ?? '');
    $desc         = (string)($p['description'] ?? '');

    // titres de tooltip
    $titleContact = (trim($contact) === '' || strtolower(trim($contact)) === 'non renseigné') ? 'Non renseigné' : 'Contact';
    $titleInfo1   = (trim($info1_t) === '' || strtolower(trim($info1_t)) === 'non renseigné') ? 'Non renseigné' : 'Liens utiles';
    $titleInfo2   = (trim($info2_t) === '' || strtolower(trim($info2_t)) === 'non renseigné') ? 'Non renseigné' : 'Ressources Documentaires';
    ?>

    <article
            class="project-tile"
            data-project-id="<?= (int)($p['id'] ?? 0) ?>"

            data-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
            data-subtext="<?= htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8') ?>"

            data-contact="<?= htmlspecialchars($contact, ENT_QUOTES, 'UTF-8') ?>"
            data-info1-title="<?= htmlspecialchars($info1_t, ENT_QUOTES, 'UTF-8') ?>"
            data-info2-title="<?= htmlspecialchars($info2_t, ENT_QUOTES, 'UTF-8') ?>"

            data-category-ids="<?= htmlspecialchars(implode(',', $p['category_ids'] ?? []), ENT_QUOTES, 'UTF-8') ?>"
    >
        <!-- HTML riche : stocké en template -->
        <template class="tpl-contact-desc"><?= $contact_desc ?></template>
        <template class="tpl-info1-desc"><?= $info1_desc ?></template>
        <template class="tpl-info2-desc"><?= $info2_desc ?></template>
        <template class="tpl-description"><?= $desc ?></template>

        <div class="tile-head">
            <div class="tile-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="tile-subtext"><?= htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8') ?></div>

            <div class="tile-actions">
                <button class="tile-btn" type="button" title="<?= htmlspecialchars($titleContact, ENT_QUOTES, 'UTF-8') ?>" aria-label="Contacter">
                    <img src="assets/icons/mail_icon.png" alt="">
                </button>

                <button class="tile-btn" type="button" title="<?= htmlspecialchars($titleInfo1, ENT_QUOTES, 'UTF-8') ?>" aria-label="Ouvrir le lien">
                    <img src="assets/icons/mail_link.png" alt="">
                </button>

                <button class="tile-btn" type="button" title="<?= htmlspecialchars($titleInfo2, ENT_QUOTES, 'UTF-8') ?>" aria-label="Ouvrir la documentation">
                    <img src="assets/icons/mail_doc.png" alt="">
                </button>
            </div>
        </div>

        <div class="tile-expand" aria-hidden="true"></div>
    </article>
<?php endforeach; ?>