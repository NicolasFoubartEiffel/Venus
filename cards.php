<?php
    require_once 'db/functions.php';
?>
<div class="project-card">
    <div class="title-header">
        <h2><?= htmlspecialchars($p['title']) ?></h2>
    </div>
    <?php if (!empty($p['task'])): ?>
        <div class="task"><em>Tâche :</em> <?= nl2br(htmlspecialchars($p['task'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($p['link'])): ?>
        <div class="link">
            <?php if (isValidUrl($p['link'])): ?>
                <a href="<?= htmlspecialchars($p['link']) ?>" target="_blank">🔗 Lien vers l'application</a>
            <?php else: ?>
                <?= htmlspecialchars($p['link']) ?>
            <?php endif; ?>

            <?php if (!empty($p['hint'])): ?>
                <?php if (isValidUrl($p['hint'])): ?>
                    <br><sub><a href="<?= htmlspecialchars($p['hint']) ?>" target="_blank">🔗 Tutoriel</a></sub>
                <?php else: ?>
                    <br><sub><?= htmlspecialchars($p['hint']) ?></sub>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="more-info">
        <!-- Dates -->
        <?php if (!empty($p['start_date']) || !empty($p['end_date'])): ?>
            <div class="dates">
                Période :
                📅
                <b><?= $p['start_date'] ? formatDateFr($p['start_date']) : '—' ?></b>
                -
                <b><?= $p['end_date'] ? formatDateFr($p['end_date']) : '—' ?></b>
            </div>
        <?php endif; ?>
        <br><br>
        <!-- Description -->
        <?php if (!empty($p['description'])): ?>
            <u>Description</u> :
            <div class="desc">
                <?= nl2br(htmlspecialchars($p['description'])) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
