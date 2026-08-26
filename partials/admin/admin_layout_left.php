<aside class="admin-left">
    <div class="project-form is-collapsed" data-project-form-panel>
        <div class="project-form-head">
            <div>
                <h2 id="project-form-title">Ajouter un projet</h2>
                <p>Cr&eacute;er ou modifier une tuile du portail.</p>
            </div>

            <button type="button" class="project-form-toggle btn-primary" data-project-form-toggle aria-expanded="false" aria-controls="project-form-body">
                + Ajouter une tuile
            </button>
        </div>

        <div id="project-form-body" class="project-form-body" data-project-form-body hidden>
        <form id="create-project-form" method="post" class="project-form-ui">
            <input type="hidden" name="action" id="project-action" value="create_project">
            <input type="hidden" name="id" id="project-id" value="">

            <div class="form-grid">

                <div class="field">
                    <label for="project-title">Titre</label>
                    <input type="text" name="title" id="project-title" placeholder="Titre" required>
                </div>

                <div class="field">
                    <label for="project-subtext">Description courte</label>
                    <input type="text" name="subtext" id="project-subtext" placeholder="Pr&eacute;sentation courte" required>
                </div>

                <div class="field field-full">
                    <fieldset class="visibility-toggle">
                        <legend>Visibilit&eacute; du projet</legend>

                        <label class="visibility-option">
                             <input type="radio" name="hidden" value="0" checked>
                            <span>
                                <strong>Visible</strong>
                                <small>Le projet appara&icirc;t sur l&rsquo;interface principale.</small>
                            </span>
                        </label>

                        <label class="visibility-option">
                            <input type="radio" name="hidden" value="1">
                            <span><strong>Masqu&eacute;</strong>
                                <small>Le projet reste administrable mais n&rsquo;est pas visible c&ocirc;t&eacute; utilisateur.</small>
                            </span>
                        </label>
                    </fieldset>
                </div>

                <fieldset class="field field-full" id="category-checkboxes">
                    <legend>Cat&eacute;gories</legend>
                    <div class="cat-grid">
                        <?php foreach ($categories as $cat): ?>
                            <label class="cat-check">
                                <input type="checkbox" name="category_ids[]" value="<?= (int) $cat['id'] ?>">
                                <span><?= htmlspecialchars($cat['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="field field-full">
                    <div class="segmented" role="tablist" aria-label="Sections du projet" data-active="0">
                        <button type="button" class="seg-btn is-active" role="tab" aria-selected="true" data-target="pane-contact">
                            Contact
                        </button>

                        <button type="button" class="seg-btn" role="tab" aria-selected="false" data-target="pane-resources">
                            Ressources
                        </button>

                        <button type="button" class="seg-btn" role="tab" aria-selected="false" data-target="pane-description">
                            Description avanc&eacute;e
                        </button>

                        <span class="seg-indicator" aria-hidden="true"></span>
                    </div>

                    <div class="seg-wrap" data-active="0">
                        <div class="seg-track">

                            <div class="seg-pane is-active" id="pane-contact" role="tabpanel" aria-hidden="false">
                                <div class="field field-full">
                                    <label for="contact-details">Contact</label>
                                    <textarea name="contact_details" id="contact-details" class="textarea-sm tinymce"></textarea>
                                </div>
                            </div>

                            <div class="seg-pane" id="pane-resources" role="tabpanel" aria-hidden="true">
                                <div class="field field-full">
                                    <label for="resources-details">Ressources</label>
                                    <textarea name="resources_details" id="resources-details" class="textarea-sm tinymce"></textarea>
                                </div>
                            </div>

                            <div class="seg-pane" id="pane-description" role="tabpanel" aria-hidden="true">

                                <div class="field field-full">
                                    <label for="description-details">Description avanc&eacute;e</label>
                                    <textarea name="description_details" id="description-details" class="textarea-sm tinymce"></textarea>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="field field-full">
                    <label for="comments">Commentaire interne</label>
                    <textarea name="comments" id="comments" class="textarea-lg tinymce"></textarea>
                </div>

            </div>

            <div class="form-actions form-actions--sticky">
                <button type="submit" id="project-submit-btn" class="btn-primary">Sauvegarder</button>
                <button type="button" id="project-cancel-btn" class="btn-ghost" style="display:none;">Annuler</button>
            </div>
        </form>
        </div>
    </div>
</aside>
