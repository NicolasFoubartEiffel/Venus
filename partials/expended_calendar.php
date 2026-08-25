<div id="calendar-modal" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-calendar-close="1"></div>

    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="calendar-modal-title">
        <button type="button" class="modal-close" data-calendar-close="1" aria-label="Fermer la fenêtre">✕</button>

        <div class="modal-header">
            <button type="button" class="news-modal-back" data-calendar-back hidden>
                ← Revenir au fil d’actualités
            </button>

            <div class="modal-header-top">
                <p class="modal-kicker" id="calendar-modal-kicker">Vie de l’étudiant</p>
                <div class="modal-title" id="calendar-modal-title">Fils d'actualités</div>
                <div class="modal-subtext" id="calendar-modal-subtext">
                    Liste des informations pour la catégorie courante
                </div>
            </div>
        </div>

        <div class="modal-body">
            <div id="calendar-list-view">
                <ul class="news-modal-list">
                    <?php foreach ($mails as $mail): ?>
                        <?php
                        $date = !empty($mail['date'])
                            ? date('d/m/Y', strtotime($mail['date']))
                            : '';

                        $content = $mail['content'] ?? '';
                        ?>

                        <li>
                            <button
                                    type="button"
                                    class="news-modal-item"
                                    data-calendar-mail
                                    data-title="<?= e($mail['objet'] ?? '') ?>"
                                    data-date="<?= e($date) ?>"
                                    data-sender="<?= e($mail['sender'] ?? '') ?>"
                                    data-content="<?= e($content) ?>"
                            >
                    <span class="news-modal-meta">
                        <span class="news-modal-date">
                            <?= e($date) ?>
                        </span>

                        <span class="news-modal-sender">
                            <?= e(substr($mail['sender'],0,(strpos($mail['sender'], '@'))) ?? 'Expéditeur inconnu') ?>
                        </span>
                    </span>

                                <span class="news-modal-main">
                        <span class="news-modal-object">
                            <?= e($mail['objet'] ?? '') ?>
                        </span>
                    </span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div id="calendar-detail-view" hidden>
                <div class="news-modal-content" id="calendar-mail-content"></div>
            </div>
        </div>
    </div>
</div>