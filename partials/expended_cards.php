<!-- Modal (popup) projet -->
<div id="project-modal" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close="1"></div>

    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <button type="button" class="modal-close" data-close="1" aria-label="Fermer">✕</button>

        <div class="modal-header">
            <div class="modal-title" id="modal-title"></div>
            <div class="modal-subtext" id="modal-subtext"></div>

            <div class="modal-actions">
                <button class="tile-btn" type="button" data-modal-action="mailto" aria-label="Contacter">
                    <img src="assets/icons/mail_icon.png" alt="">
                </button>

                <button class="tile-btn" type="button" data-modal-action="doc" aria-label="Ouvrir les ressources documentaires">
                    <img src="assets/icons/mail_link.png" alt="">
                </button>

                <button class="tile-btn" type="button" data-modal-action="open" aria-label="Ouvrir la description avancée">
                    <img src="assets/icons/mail_doc.png" alt="">
                </button>
            </div>
        </div>

        <div class="modal-body">
            <div id="modal-description"></div>
        </div>
    </div>
</div>