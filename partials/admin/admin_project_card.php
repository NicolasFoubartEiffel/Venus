<div class="project-card is-collapsed" data-project-id="<?= (int)($p['id'] ?? 0) ?>">
    <button class="card-gache" type="button" aria-expanded="false">
        <div class="gache-left">
            <div class="title">
                <?= htmlspecialchars((string)($p['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($p['comments'])): ?>
                    <span class="title-comment-icon" aria-hidden="true">&nbsp;&nbsp;🗨</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="gache-right">
            <div class="chips">
                <?php foreach (($p['category_names'] ?? []) as $name): ?>
                    <span class="chip">
                        <?= htmlspecialchars(trim((string)$name, "\""), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <span class="chevron" aria-hidden="true">▾</span>
        </div>
    </button>

    <div class="card-body" hidden>
        <?php
        $contactTitle       = trim((string)($p['contact_title'] ?? ''));
        $descriptionTitle   = trim((string)($p['description_title'] ?? ''));
        $resourcesTitle     = trim((string)($p['resources_title'] ?? ''));

        $contactDetails     = (string)($p['contact_details'] ?? '');
        $descriptionDetails = (string)($p['description_details'] ?? '');
        $resourcesDetails   = (string)($p['resources_details'] ?? '');
        $commentsDetails    = (string)($p['comments'] ?? '');

        $subtext = trim((string)($p['subtext'] ?? ''));
        ?>

        <?php if ($subtext !== ''): ?>
            <div class="card-subtext">
                <span class="subtext-label">Présentation</span>
                <span class="subtext-value">
                    <?= nl2br(htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8')) ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="info-strip">
            <!-- CONTACT -->
            <div class="info-box">
                <div class="info-head">
                    <span class="info-ico" aria-hidden="true">👤</span>
                    <span class="info-label">Contact</span>
                </div>

                <div class="info-title">
                    <?php if ($contactTitle !== '' && mb_strtolower($contactTitle) !== 'non renseigné'): ?>
                        <?php if (filter_var($contactTitle, FILTER_VALIDATE_EMAIL)): ?>
                            <a class="info-link" href="mailto:<?= htmlspecialchars($contactTitle, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($contactTitle, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php else: ?>
                            <span><?= htmlspecialchars($contactTitle, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="muted">Non renseigné</span>
                    <?php endif; ?>
                </div>

                <div class="info-desc wysiwyg wysiwyg--compact">
                    <?= trim($contactDetails) !== '' ? $contactDetails : '<span class="muted"><em>—</em></span>' ?>
                </div>
            </div>

            <!-- RESSOURCES -->
            <div class="info-box">
                <div class="info-head">
                    <span class="info-ico" aria-hidden="true">📎</span>
                    <span class="info-label">Ressources</span>
                </div>

                <div class="info-title">
                    <?php if ($resourcesTitle !== '' && mb_strtolower($resourcesTitle) !== 'non renseigné'): ?>
                        <?php if (filter_var($resourcesTitle, FILTER_VALIDATE_URL)): ?>
                            <a class="info-link" href="<?= htmlspecialchars($resourcesTitle, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                <?= htmlspecialchars($resourcesTitle, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php else: ?>
                            <span><?= htmlspecialchars($resourcesTitle, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="muted">Non renseigné</span>
                    <?php endif; ?>
                </div>

                <div class="info-desc wysiwyg wysiwyg--compact">
                    <?= trim($resourcesDetails) !== '' ? $resourcesDetails : '<span class="muted"><em>—</em></span>' ?>
                </div>
            </div>

            <!-- DESCRIPTION AVANCÉE -->
            <div class="info-box">
                <div class="info-head">
                    <span class="info-ico" aria-hidden="true">ℹ️</span>
                    <span class="info-label">Description avancée</span>
                </div>

                <div class="info-title">
                    <?php if ($descriptionTitle !== '' && mb_strtolower($descriptionTitle) !== 'non renseigné'): ?>
                        <?php if (filter_var($descriptionTitle, FILTER_VALIDATE_URL)): ?>
                            <a class="info-link" href="<?= htmlspecialchars($descriptionTitle, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                <?= htmlspecialchars($descriptionTitle, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php else: ?>
                            <span><?= htmlspecialchars($descriptionTitle, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="muted">Non renseigné</span>
                    <?php endif; ?>
                </div>

                <div class="info-desc wysiwyg wysiwyg--compact">
                    <?= trim($descriptionDetails) !== '' ? $descriptionDetails : '<span class="muted"><em>—</em></span>' ?>
                </div>
            </div>
        </div>

        <div class="comments-box">
            <div class="comments-head">
                <span class="comments-ico" aria-hidden="true">🗨</span>
                <span class="comments-label">Commentaire interne</span>
            </div>

            <div class="comments-body wysiwyg wysiwyg--compact">
                <?= trim($commentsDetails) !== '' ? $commentsDetails : '<span class="muted"><em>—</em></span>' ?>
            </div>
        </div>

        <div class="card-actions">
            <button class="edit-btn" type="button" data-id="<?= (int)($p['id'] ?? 0) ?>">✏️ Éditer</button>
            <button class="delete-btn" type="button" data-id="<?= (int)($p['id'] ?? 0) ?>">🗑 Supprimer</button>
        </div>
    </div>
</div>