<aside class="admin-left">
    <div class="project-form">
        <h2 id="project-form-title">Ajouter un projet</h2>

        <form id="create-project-form" method="post" class="project-form-ui">
            <input type="hidden" name="action" id="project-action" value="create_projet">
            <input type="hidden" name="id" id="project-id" value="">

            <div class="form-grid">

                <div class="field">
                    <label for="project-title">Titre</label>
                    <input type="text" name="title" id="project-title" placeholder="Titre" required>
                </div>

                <div class="field">
                    <label for="project-subtext">Description courte</label>
                    <input type="text" name="subtext" id="project-subtext" placeholder="Présentation courte" required>
                </div>

                <fieldset class="field field-full" id="category-checkboxes">
                    <legend>Catégories</legend>
                    <div class="cat-grid">
                        <?php foreach ($categories as $cat): ?>
                            <label class="cat-check">
                                <input type="checkbox" name="category_ids[]" value="<?= (int)$cat['id'] ?>">
                                <span><?= htmlspecialchars($cat['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <!-- Segmented control + slider panes -->
                <div class="field field-full">

                    <div class="segmented" role="tablist" aria-label="Sections du projet" data-active="0">
                        <button type="button" class="seg-btn is-active" role="tab" aria-selected="true" data-target="pane-contact">
                            Contact
                        </button>
                        <button type="button" class="seg-btn" role="tab" aria-selected="false" data-target="pane-info1">
                            Liens utiles
                        </button>
                        <button type="button" class="seg-btn" role="tab" aria-selected="false" data-target="pane-info2">
                            Ressources documentaires
                        </button>
                        <span class="seg-indicator" aria-hidden="true"></span>
                    </div>

                    <!-- IMPORTANT: wrapper + track pour le slide des panes -->
                    <div class="seg-wrap" data-active="0">

                        <div class="seg-track">
                            <!-- Pane Contact -->
                            <div class="seg-pane is-active" id="pane-contact" role="tabpanel" aria-hidden="false">
                                <div class="field">
                                    <label for="project-contact">Titre - Contact</label>
                                    <input type="text" name="contact" id="project-contact">
                                </div>

                                <div class="field field-full">
                                    <label for="contact-description">Description du contact</label>
                                    <textarea name="contact_description" id="contact-description" class="textarea-sm tinymce"></textarea>
                                </div>
                            </div>

                            <!-- Pane Info 1 -->
                            <div class="seg-pane" id="pane-info1" role="tabpanel" aria-hidden="true">
                                <div class="field">
                                    <label for="info_1_title">Titre - Liens utile</label>
                                    <input type="text" name="info_1_title" id="info_1_title">
                                </div>

                                <div class="field field-full">
                                    <label for="info-1-description">Description</label>
                                    <textarea name="info_1_description" id="info-1-description" class="textarea-sm tinymce"></textarea>
                                </div>
                            </div>

                            <!-- Pane Info 2 -->
                            <div class="seg-pane" id="pane-info2" role="tabpanel" aria-hidden="true">
                                <div class="field">
                                    <label for="info_2_title">Titre - Ressources documentaire</label>
                                    <input type="text" name="info_2_title" id="info_2_title">
                                </div>

                                <div class="field field-full">
                                    <label for="info-2-description">Description</label>
                                    <textarea name="info_2_description" id="info-2-description" class="textarea-sm tinymce"></textarea>
                                </div>
                            </div>
                        </div><!-- /.seg-track -->

                    </div><!-- /.seg-wrap -->
                </div>

                <div class="field field-full">
                    <label for="project-description">Description détaillée du projet</label>
                    <textarea name="description" id="project-description" class="textarea-lg tinymce"></textarea>
                </div>

            </div>

            <div class="form-actions form-actions--sticky">
                <button type="submit" id="project-submit-btn" class="btn-primary">Sauvegarder</button>
                <button type="button" id="project-cancel-btn" class="btn-ghost" style="display:none;">Annuler</button>
            </div>
        </form>
    </div>
</aside>