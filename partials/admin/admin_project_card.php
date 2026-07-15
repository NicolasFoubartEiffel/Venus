<?php
if (!function_exists('admin_project_has_content')) {
    function admin_project_has_content(string $html): bool
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string)$text) !== '';
    }
}

$contactDetails     = (string)($p['contact_details'] ?? '');
$descriptionDetails = (string)($p['description_details'] ?? '');
$resourcesDetails   = (string)($p['resources_details'] ?? '');
$commentsDetails    = (string)($p['comments'] ?? '');

$subtext = trim((string)($p['subtext'] ?? ''));
$isHidden = (int)($p['hidden'] ?? 0) === 1;

$categories = !empty($p['categories']) && is_array($p['categories'])
    ? $p['categories']
    : [];

$hasContactDetails = admin_project_has_content($contactDetails);
$hasResourcesDetails = admin_project_has_content($resourcesDetails);
$hasDescriptionDetails = admin_project_has_content($descriptionDetails);

$missingSections = [];

if (!$hasContactDetails) {
    $missingSections[] = [
        'text' => 'contact',
        'html' => 'contact',
    ];
}

if (!$hasResourcesDetails) {
    $missingSections[] = [
        'text' => 'ressources',
        'html' => 'ressources',
    ];
}

if (!$hasDescriptionDetails) {
    $missingSections[] = [
        'text' => 'description avancee',
        'html' => 'description avanc&eacute;e',
    ];
}

$missingSectionsText = implode(', ', array_column($missingSections, 'text'));
$missingSectionsHtml = implode(', ', array_column($missingSections, 'html'));
?>

<div class="project-card is-collapsed" data-project-id="<?= (int)($p['id'] ?? 0) ?>">
    <button class="card-gache" type="button" aria-expanded="false">
        <div class="gache-left">
            <div class="gache-title-row">
                <div class="title">
                    <?= htmlspecialchars((string)($p['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($p['comments'])): ?>
                        <span class="title-comment-icon" aria-hidden="true">&nbsp;&nbsp;&#128488;</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($missingSections)): ?>
                    <span
                            class="completion-hint"
                            title="Sections sans contenu : <?= htmlspecialchars($missingSectionsText, ENT_QUOTES, 'UTF-8') ?>"
                    >
                        &Agrave; compl&eacute;ter : <?= $missingSectionsHtml ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="gache-meta-row">
                <span class="visibility-badge <?= $isHidden ? 'is-hidden' : 'is-visible' ?>">
                    <?= $isHidden ? 'Masqu&eacute; c&ocirc;t&eacute; utilisateur' : 'Visible c&ocirc;t&eacute; utilisateur' ?>
                </span>
            </div>
        </div>

        <div class="gache-right">
            <div class="chips">
                <?php foreach ($categories as $category): ?>
                    <span class="tile-category-pill tile-category-pill--<?= (int)$category['id'] ?> visibility-badge">
                        <?= htmlspecialchars(categoryDisplayName(trim((string)$category['name'], "\"")), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <span class="drag-handle" title="D&eacute;placer" aria-hidden="true">&#9776;</span>
            <span class="chevron" aria-hidden="true">&#9662;</span>
        </div>
    </button>

    <div class="card-body" hidden>
        <?php if ($subtext !== ''): ?>
            <div class="card-subtext">
                <span class="subtext-label">Pr&eacute;sentation</span>
                <span class="subtext-value">
                    <?= nl2br(htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8')) ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="info-strip">
            <div class="info-box <?= $hasContactDetails ? '' : 'info-box--empty' ?>">
                <div class="info-head">
                    <span class="info-ico" aria-hidden="true">&#128100;</span>
                    <span class="info-label">Contact</span>
                </div>

                <div class="info-desc wysiwyg wysiwyg--compact">
                    <?= $hasContactDetails ? $contactDetails : '<span class="muted"><em>&mdash;</em></span>' ?>
                </div>
            </div>

            <div class="info-box <?= $hasResourcesDetails ? '' : 'info-box--empty' ?>">
                <div class="info-head">
                    <span class="info-ico" aria-hidden="true">&#128206;</span>
                    <span class="info-label">Ressources</span>
                </div>

                <div class="info-desc wysiwyg wysiwyg--compact">
                    <?= $hasResourcesDetails ? $resourcesDetails : '<span class="muted"><em>&mdash;</em></span>' ?>
                </div>
            </div>

            <div class="info-box <?= $hasDescriptionDetails ? '' : 'info-box--empty' ?>">
                <div class="info-head">
                    <span class="info-ico" aria-hidden="true">&#8505;</span>
                    <span class="info-label">Description avanc&eacute;e</span>
                </div>

                <div class="info-desc wysiwyg wysiwyg--compact">
                    <?= $hasDescriptionDetails ? $descriptionDetails : '<span class="muted"><em>&mdash;</em></span>' ?>
                </div>
            </div>
        </div>

        <div class="comments-box">
            <div class="comments-head">
                <span class="comments-ico" aria-hidden="true">&#128488;</span>
                <span class="comments-label">Commentaire interne</span>
            </div>

            <div class="comments-body wysiwyg wysiwyg--compact">
                <?= trim($commentsDetails) !== '' ? $commentsDetails : '<span class="muted"><em>&mdash;</em></span>' ?>
            </div>
        </div>

        <div class="card-actions">
            <div class="card-actions-left">
                <button class="admin-action-btn edit-btn" type="button" data-id="<?= (int)($p['id'] ?? 0) ?>">
                    &#9998;&#65039; &Eacute;diter
                </button>

                <button class="admin-action-btn publish-btn <?= $isHidden ? 'is-publish' : 'is-unpublish' ?>" type="button" data-id="<?= (int)($p['id'] ?? 0) ?>" data-hidden="<?= $isHidden ? '1' : '0' ?>" title="<?= $isHidden ? 'Publier le projet' : 'D&eacute;publier le projet' ?>">
                    <?= $isHidden ? '&#128065; Publier' : '&#128683; D&eacute;publier' ?>
                </button>
            </div>

            <button class="admin-action-btn delete-btn" type="button" data-id="<?= (int)($p['id'] ?? 0) ?>">
                &#128465; Supprimer
            </button>
        </div>
    </div>
</div>
