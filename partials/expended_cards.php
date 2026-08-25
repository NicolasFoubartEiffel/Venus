<!-- Modal (popup) projet -->
<div id="project-modal" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close="1"></div>

    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <button
                type="button"
                class="modal-close modal-action-btn"
                data-close="1"
                aria-label="Fermer la fenêtre"
        >
            ✕
        </button>

        <div class="modal-header">
            <div class="modal-side-actions">
                <button
                        type="button"
                        class="modal-favorite modal-action-btn favorite-control"
                        aria-label="Ajouter aux favoris"
                        title="Ajouter aux favoris"
                >
                    ☆
                </button>

                <button
                        type="button"
                        class="modal-share modal-action-btn"
                        data-modal-action="share"
                        aria-label="Partager la rubrique"
                        title="Partager la rubrique"
                >
                    🔗
                </button>
            </div>

            <div class="modal-category-beans" id="modal-category-beans"></div>

            <div class="modal-header-top">
                <div class="modal-title" id="modal-title"></div>
                <div class="modal-subtext" id="modal-subtext"></div>
            </div>

            <div class="modal-actions">
                <button class="tile-btn" type="button" data-modal-action="doc" aria-label="Description">
                    <img src="assets/icons/mail_doc.png" alt="">
                </button>
                <button class="tile-btn" type="button" data-modal-action="open" aria-label="Ressources">
                    <img src="assets/icons/mail_link.png" alt="">
                </button>
                <button class="tile-btn" type="button" data-modal-action="mailto" aria-label="Contacter">
                    <img src="assets/icons/mail_icon.png" alt="">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <div id="modal-description"></div>
        </div>
    </div>
</div>
