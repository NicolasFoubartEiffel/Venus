document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);
    const qs = (selector, root = document) => root.querySelector(selector);
    const qsa = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    const form = $('create-project-form');
    if (!form) return;

    const formTitle = $('project-form-title');
    const actionInput = $('project-action');
    const idInput = $('project-id');
    const submitBtn = $('project-submit-btn');
    const cancelBtn = $('project-cancel-btn');

    const adminLeft = qs('.admin-left');
    const adminRight = qs('.admin-right');

    const categoryCheckboxes = qsa('#category-checkboxes input[type="checkbox"][name="category_ids[]"]');

    const textFields = {
        title: $('project-title'),
        subtext: $('project-subtext'),
        contact_title: $('project-contact'),
        resources_title: $('resources-title'),
        description_title: $('description-title'),
    };

    const editorFields = {
        contact_details: 'contact-details',
        resources_details: 'resources-details',
        description_details: 'description-details',
        comments: 'comments',
    };

    const segmented = qs('.segmented');
    const segWrap = qs('.seg-wrap');

    let currentMode = 'create';

    function getTinyMCE() {
        return window.tinymce || null;
    }

    function triggerTinySave() {
        const tinymce = getTinyMCE();
        if (tinymce?.triggerSave) {
            tinymce.triggerSave();
        }
    }

    function getEditor(id) {
        const tinymce = getTinyMCE();
        return tinymce ? tinymce.get(id) : null;
    }

    function setEditorContent(id, html = '') {
        const textarea = $(id);
        if (textarea) {
            textarea.value = html;
        }

        const editor = getEditor(id);
        if (editor) {
            editor.setContent(html || '');
            try {
                editor.undoManager.clear();
                editor.undoManager.reset();
            } catch (_) {}
        }
    }

    function clearEditors() {
        Object.values(editorFields).forEach((id) => setEditorContent(id, ''));
    }

    function repaintEditorsInPane(pane) {
        if (!pane) return;

        pane.querySelectorAll('textarea.tinymce').forEach((textarea) => {
            const editor = getEditor(textarea.id);
            if (!editor) return;

            try {
                editor.execCommand('mceRepaint');
            } catch (_) {}
        });
    }

    function initTinyMCE() {
        const tinymce = getTinyMCE();
        if (!tinymce) return;

        if (Array.isArray(tinymce.editors) && tinymce.editors.length > 0) {
            return;
        }

        tinymce.init({
            selector: 'textarea.tinymce',
            license_key: 'gpl',
            promotion: false,
            height: 320,
            menubar: true,
            branding: false,
            resize: true,
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat | code',
            link_default_target: '_blank',
            content_style: 'body { font-family: Segoe UI, sans-serif; font-size: 14px; }',
            setup: (editor) => {
                editor.on('init', () => {
                    const activePane = qs('.seg-pane.is-active');
                    if (activePane && activePane.contains(editor.getElement())) {
                        try {
                            editor.execCommand('mceRepaint');
                        } catch (_) {}
                    }
                });
            },
        });
    }

    function bootTinyMCE() {
        if (getTinyMCE()) {
            initTinyMCE();
            return;
        }

        let tries = 0;
        const timer = setInterval(() => {
            tries += 1;

            if (getTinyMCE()) {
                clearInterval(timer);
                initTinyMCE();
                return;
            }

            if (tries >= 60) {
                clearInterval(timer);
            }
        }, 100);
    }

    function clearCategories() {
        categoryCheckboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });
    }

    function setCategories(ids = []) {
        const selected = new Set((Array.isArray(ids) ? ids : []).map(String));

        categoryCheckboxes.forEach((checkbox) => {
            checkbox.checked = selected.has(String(checkbox.value));
        });
    }

    function hasCategorySelected() {
        if (!categoryCheckboxes.length) return true;
        return categoryCheckboxes.some((checkbox) => checkbox.checked);
    }

    function activatePane(index) {
        if (!segmented || !segWrap) return;

        const buttons = qsa('.seg-btn', segmented);
        const panes = buttons
            .map((btn) => $(btn.dataset.target))
            .filter(Boolean);

        if (!buttons[index] || !panes[index]) return;

        segmented.dataset.active = String(index);
        segWrap.dataset.active = String(index);

        buttons.forEach((btn, i) => {
            const active = i === index;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
            btn.setAttribute('tabindex', active ? '0' : '-1');
        });

        panes.forEach((pane, i) => {
            const active = i === index;
            pane.classList.toggle('is-active', active);
            pane.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        setTimeout(() => repaintEditorsInPane(panes[index]), 120);
    }

    function initSegmented() {
        if (!segmented) return;

        const buttons = qsa('.seg-btn', segmented);

        buttons.forEach((btn, index) => {
            btn.addEventListener('click', () => activatePane(index));
        });

        activatePane(0);
    }

    async function api(action, payload = {}) {
        const formData = new FormData();
        formData.append('action', action);

        Object.entries(payload).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((item) => formData.append(key, item));
            } else {
                formData.append(key, value ?? '');
            }
        });

        const response = await fetch('db/project.php', {
            method: 'POST',
            body: formData,
        });

        let data;
        try {
            data = await response.json();
        } catch (_) {
            throw new Error('Réponse serveur invalide.');
        }

        if (!response.ok) {
            throw new Error(data?.message || 'Erreur serveur.');
        }

        if (!data?.success) {
            throw new Error(data?.message || 'Erreur inconnue.');
        }

        return data;
    }

    function scrollToForm() {
        const target = adminLeft || form;

        target.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }

    function focusFormTitle() {
        const titleInput = $('project-title');
        if (!titleInput) return;

        setTimeout(() => {
            titleInput.focus();
            titleInput.select?.();
        }, 350);
    }

    function fillTextFields(project = {}) {
        Object.entries(textFields).forEach(([key, element]) => {
            if (!element) return;
            element.value = project[key] ?? '';
        });
    }

    function fillEditorFields(project = {}) {
        Object.entries(editorFields).forEach(([key, editorId]) => {
            setEditorContent(editorId, project[key] ?? '');
        });
    }

    function resetFormCompletely() {
        form.reset();
        clearCategories();
        clearEditors();
        activatePane(0);

        if (idInput) idInput.value = '';
        if (actionInput) actionInput.value = 'create_project';

        currentMode = 'create';

        if (formTitle) {
            formTitle.textContent = 'Ajouter un projet';
        }

        if (submitBtn) {
            submitBtn.textContent = 'Sauvegarder';
            submitBtn.disabled = false;
        }

        if (cancelBtn) {
            cancelBtn.style.display = 'none';
        }
    }

    function setCreateMode({ scroll = false, focus = false } = {}) {
        resetFormCompletely();

        if (scroll) {
            scrollToForm();
        }

        if (focus) {
            focusFormTitle();
        }
    }

    function setEditMode(project) {
        currentMode = 'edit';

        if (formTitle) {
            formTitle.textContent = `Modifier : ${project?.title || ''}`;
        }

        if (actionInput) {
            actionInput.value = 'update_project';
        }

        if (idInput) {
            idInput.value = project?.id ?? '';
        }

        if (submitBtn) {
            submitBtn.textContent = 'Mettre à jour';
            submitBtn.disabled = false;
        }

        if (cancelBtn) {
            cancelBtn.style.display = 'inline-flex';
        }

        fillTextFields(project);
        setCategories(project?.category_ids ?? []);
        fillEditorFields(project);
        activatePane(0);

        scrollToForm();
        focusFormTitle();
    }

    function closeCard(card) {
        if (!card) return;

        const button = qs('.card-gache', card);
        const body = qs('.card-body', card);

        card.classList.remove('is-open');
        card.classList.add('is-collapsed');

        if (button) {
            button.setAttribute('aria-expanded', 'false');
        }

        if (body) {
            body.hidden = true;
        }
    }

    function openCard(card) {
        if (!card) return;

        const button = qs('.card-gache', card);
        const body = qs('.card-body', card);

        card.classList.add('is-open');
        card.classList.remove('is-collapsed');

        if (button) {
            button.setAttribute('aria-expanded', 'true');
        }

        if (body) {
            body.hidden = false;
        }
    }

    function closeAllCards() {
        qsa('.project-card.is-open').forEach(closeCard);
    }

    async function handleEdit(projectId) {
        try {
            const data = await api('get_project', { id: projectId });
            setEditMode(data.project);

            const relatedCard = qs(`.project-card[data-project-id="${String(projectId)}"]`);
            if (relatedCard) {
                closeAllCards();
                openCard(relatedCard);
            }
        } catch (error) {
            console.error(error);
            alert(error.message || 'Impossible de charger le projet.');
        }
    }

    async function handleDelete(projectId, button) {
        const confirmed = confirm('Supprimer ce projet ?');
        if (!confirmed) return;

        try {
            await api('delete_project', { id: projectId });

            const card = button.closest('.project-card');
            if (card) {
                card.remove();
            }

            if (String(idInput?.value || '') === String(projectId)) {
                setCreateMode({ scroll: true, focus: true });
            }

            alert('Projet supprimé.');
        } catch (error) {
            console.error(error);
            alert(error.message || 'Impossible de supprimer le projet.');
        }
    }

    adminRight?.addEventListener('click', async (event) => {
        const accordionButton = event.target.closest('.project-card .card-gache');
        if (accordionButton) {
            const card = accordionButton.closest('.project-card');
            const isOpen = card?.classList.contains('is-open');

            closeAllCards();
            if (!isOpen) {
                openCard(card);
            }
            return;
        }

        const editButton = event.target.closest('.project-card .edit-btn');
        if (editButton) {
            event.preventDefault();
            event.stopPropagation();

            const projectId = editButton.dataset.id;
            if (!projectId) return;

            await handleEdit(projectId);
            return;
        }

        const deleteButton = event.target.closest('.project-card .delete-btn');
        if (deleteButton) {
            event.preventDefault();
            event.stopPropagation();

            const projectId = deleteButton.dataset.id;
            if (!projectId) return;

            await handleDelete(projectId, deleteButton);
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!hasCategorySelected()) {
            alert('Choisis au moins une catégorie.');
            return;
        }

        triggerTinySave();

        const formData = new FormData(form);

        if (submitBtn) {
            submitBtn.disabled = true;
        }

        try {
            const response = await fetch('db/project.php', {
                method: 'POST',
                body: formData,
            });

            let data;
            try {
                data = await response.json();
            } catch (_) {
                throw new Error('Réponse serveur invalide.');
            }

            if (!response.ok || !data?.success) {
                throw new Error(data?.message || 'Erreur serveur.');
            }

            alert(currentMode === 'edit' ? 'Projet modifié.' : 'Projet créé.');
            window.location.reload();
        } catch (error) {
            console.error(error);
            alert(error.message || 'Impossible de sauvegarder le projet.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }
    });

    cancelBtn?.addEventListener('click', () => {
        setCreateMode({ scroll: true, focus: true });
    });

    bootTinyMCE();
    initSegmented();
    setCreateMode();
});