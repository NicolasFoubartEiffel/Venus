<div class="project-card is-collapsed" data-project-id="<?= (int)($p['id'] ?? 0) ?>">
    <!-- GÂCHE -->
    <button class="card-gache" type="button" aria-expanded="false">
        <div class="gache-left">
            <div class="title"><?= htmlspecialchars($p['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <div class="gache-right">
            <div class="chips">
                <?php foreach (($p['category_names'] ?? []) as $name): ?>
                    <span class="chip"><?= htmlspecialchars(trim($name, "\""), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
            <span class="chevron" aria-hidden="true">▾</span>
        </div>
    </button>

    <!-- CONTENU -->
    <div class="card-body" hidden>

        <?php
        // Helpers (titres)
        $contact_title = trim((string)($p['contact'] ?? ''));
        $info1_title   = trim((string)($p['info_1_title'] ?? ''));
        $info2_title   = trim((string)($p['info_2_title'] ?? ''));

        // Descriptions (HTML TinyMCE possible)
        $contact_desc = (string)($p['contact_description'] ?? '');
        $info1_desc   = (string)($p['info_1_description'] ?? '');
        $info2_desc   = (string)($p['info_2_description'] ?? '');

        // Présentation courte
        $subtext = trim((string)($p['subtext'] ?? ''));
        ?>

        <?php if ($subtext !== ''): ?>
            <div class="card-subtext">
                <span class="subtext-label">Présentation</span>
                <span class="subtext-value"><?= nl2br(htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8')) ?></span>
            </div>
        <?php endif; ?>

        <!-- 3 blocs d'infos -->
        <div class="info-strip">
            <!-- CONTACT -->
            <div class="info-box">
                <div class="info-head">
                    <span class="info-ico" aria-hidden="true">👤</span>
                    <span class="info-label">Contact</span>
                </div>

                <div class="info-title">
                    <?php if ($contact_title && strtolower($contact_title) !== 'non renseigné'): ?>
                        <?php if (filter_var($contact_title, FILTER_VALIDATE_EMAIL)): ?>
                            <a class="info-link" href="mailto:<?= htmlspecialchars($contact_title, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($contact_title, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php else: ?>
                            <span><?= htmlspecialchars($contact_title, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="muted">Non renseigné</span>
                    <?php endif; ?>
                </div>

                <div class="info-desc wysiwyg wysiwyg--compact">
                    <?= trim($contact_desc) !== '' ? $contact_desc : '<span class="muted"><em>—</em></span>'; ?>
                </div>
            </div>

            <!-- INFO 1 -->
            <div class="info-box">
                <div class="info-head">
                    <span class="info-ico" aria-hidden="true">ℹ️</span>
                    <span class="info-label">Information 1</span>
                </div>

                <div class="info-title">
                    <?php if ($info1_title && strtolower($info1_title) !== 'non renseigné'): ?>
                        <?php if (filter_var($info1_title, FILTER_VALIDATE_URL)): ?>
                            <a class="info-link" href="<?= htmlspecialchars($info1_title, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                <?= htmlspecialchars($info1_title, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php else: ?>
                            <span><?= htmlspecialchars($info1_title, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="muted">Non renseigné</span>
                    <?php endif; ?>
                </div>

                <div class="info-desc wysiwyg wysiwyg--compact">
                    <?= trim($info1_desc) !== '' ? $info1_desc : '<span class="muted"><em>—</em></span>'; ?>
                </div>
            </div>

            <!-- INFO 2 -->
            <div class="info-box">
                <div class="info-head">
                    <span class="info-ico" aria-hidden="true">📎</span>
                    <span class="info-label">Information 2</span>
                </div>

                <div class="info-title">
                    <?php if ($info2_title && strtolower($info2_title) !== 'non renseigné'): ?>
                        <?php if (filter_var($info2_title, FILTER_VALIDATE_URL)): ?>
                            <a class="info-link" href="<?= htmlspecialchars($info2_title, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                <?= htmlspecialchars($info2_title, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php else: ?>
                            <span><?= htmlspecialchars($info2_title, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="muted">Non renseigné</span>
                    <?php endif; ?>
                </div>

                <div class="info-desc wysiwyg wysiwyg--compact">
                    <?= trim($info2_desc) !== '' ? $info2_desc : '<span class="muted"><em>—</em></span>'; ?>
                </div>
            </div>
        </div>

        <!-- Description détaillée -->
        <div class="card-section">
            <div class="label">Description détaillée</div>
            <div class="wysiwyg">
                <?= !empty($p['description']) ? $p['description'] : '<span class="muted"><em>Aucune description</em></span>'; ?>
            </div>
        </div>

        <div class="card-actions">
            <button class="edit-btn" type="button" data-id="<?= (int)($p['id'] ?? 0) ?>">✏️ Éditer</button>
            <button class="delete-btn" type="button" data-id="<?= (int)($p['id'] ?? 0) ?>">🗑 Supprimer</button>
        </div>
    </div>                        </div>
