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
    const projectList = $('sortable-projects');
    const adminSearchInput = qs('[data-admin-project-search]');
    const adminSearchClear = qs('[data-admin-clear-search]');
    const adminSearchEmpty = qs('[data-admin-search-empty]');
    const densityInputs = qsa('[data-admin-density]');
    const statsToggle = qs('[data-admin-stats-toggle]');
    const statsPanel = $('admin-tracking-stats');
    const statsParams = new URLSearchParams(window.location.search);
    const shouldKeepStatsInView = Boolean(statsPanel) && (
        window.location.hash === '#admin-tracking-stats'
        || statsParams.has('stats')
        || statsParams.has('date_from')
        || statsParams.has('date_to')
    );

    const categoryCheckboxes = qsa('#category-checkboxes input[type="checkbox"][name="category_ids[]"]');

    const textFields = {
        title: $('project-title'),
        subtext: $('project-subtext'),
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
    const densityStorageKey = 'crac-admin-density';

    function setStatsPanelOpen(open) {
        if (!statsPanel || !statsToggle) return;

        statsPanel.hidden = !open;
        statsPanel.classList.toggle('is-open', open);
        statsToggle.classList.toggle('is-active', open);
        statsToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function keepStatsPanelInView() {
        if (!shouldKeepStatsInView || !statsPanel || statsPanel.hidden) return;

        statsPanel.scrollIntoView({ behavior: 'auto', block: 'start' });
    }

    if (statsToggle && statsPanel) {
        setStatsPanelOpen(!statsPanel.hidden);

        statsToggle.addEventListener('click', () => {
            setStatsPanelOpen(statsPanel.hidden);
        });
    }

    function stripFontStylesFromNode(root) {
        if (!root?.querySelectorAll) return;

        const styledNodes = [
            ...(root.matches?.('[style]') ? [root] : []),
            ...root.querySelectorAll('[style]'),
        ];

        const fontAttributeNodes = [
            ...(root.matches?.('[face], [size]') ? [root] : []),
            ...root.querySelectorAll('[face], [size]'),
        ];

        styledNodes.forEach((node) => {
            node.style.removeProperty('font-family');

            if (!node.getAttribute('style')?.trim()) {
                node.removeAttribute('style');
            }
        });

        fontAttributeNodes.forEach((node) => {
            node.removeAttribute('face');
        });
    }

    function stripFontStylesFromHtml(html = '') {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        stripFontStylesFromNode(wrapper);
        return wrapper.innerHTML;
    }

    function getTinyMCE() {
        return window.tinymce || null;
    }

    function normalizeEditorTypography(editor) {
        if (!editor) return;

        const currentContent = editor.getContent();
        const cleanedContent = stripFontStylesFromHtml(currentContent);

        if (cleanedContent !== currentContent) {
            editor.setContent(cleanedContent);
        }
    }

    function normalizeEditorTypographyDom(editor) {
        const body = editor?.getBody?.();
        if (!body) return;

        stripFontStylesFromNode(body);
    }

    function normalizeAllEditorsTypography() {
        const tinymce = getTinyMCE();
        if (!tinymce?.editors) return;

        tinymce.editors.forEach((editor) => {
            normalizeEditorTypographyDom(editor);
            normalizeEditorTypography(editor);
        });
    }

    function triggerTinySave() {
        normalizeAllEditorsTypography();

        const tinymce = getTinyMCE();
        if (tinymce?.triggerSave) {
            tinymce.triggerSave();
        }
    }

    function normalizeSearchText(value = '') {
        return value
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function buildAdminCardIndex(card) {
        const title = qs('.gache-left .title', card)?.textContent || '';

        return {
            card,
            title: title.trim(),
            text: normalizeSearchText(title),
        };
    }

    function getAdminSearchIndex() {
        if (!projectList) return [];

        return qsa('.project-card', projectList).map(buildAdminCardIndex);
    }

    function applyAdminSearch() {
        if (!adminSearchInput) return;

        const query = normalizeSearchText(adminSearchInput.value);
        const terms = query.split(/\s+/).filter(Boolean);
        const index = getAdminSearchIndex();
        const matches = [];

        index.forEach((entry) => {
            const visible = terms.length === 0 || terms.every((term) => entry.text.includes(term));

            entry.card.hidden = !visible;
            entry.card.setAttribute('aria-hidden', visible ? 'false' : 'true');

            if (visible) {
                matches.push(entry);
            }
        });

        if (adminSearchClear) {
            adminSearchClear.hidden = query === '';
        }

        if (adminSearchEmpty) {
            adminSearchEmpty.hidden = query === '' || matches.length > 0;
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
            toolbar: 'undo redo | blocks fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat | code',
            link_default_target: '_blank',
            content_style: 'body { font-family: Segoe UI, sans-serif; font-size: 14px; }',
            paste_preprocess: (_, args) => {
                args.content = stripFontStylesFromHtml(args.content || '');
            },
            setup: (editor) => {
                editor.on('init', () => {
                    const activePane = qs('.seg-pane.is-active');
                    if (activePane && activePane.contains(editor.getElement())) {
                        try {
                            editor.execCommand('mceRepaint');
                        } catch (_) {}
                    }
                });

                editor.on('PastePreProcess', (event) => {
                    event.content = stripFontStylesFromHtml(event.content || '');
                });

                editor.on('PastePostProcess', (event) => {
                    stripFontStylesFromNode(event.node);
                    setTimeout(() => normalizeEditorTypographyDom(editor), 0);
                });

                editor.on('Paste', () => {
                    setTimeout(() => normalizeEditorTypographyDom(editor), 0);
                    setTimeout(() => normalizeEditorTypographyDom(editor), 50);
                });

                editor.on('BeforeGetContent', () => {
                    normalizeEditorTypographyDom(editor);
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

    function setHidden(value = 0) {
        const hiddenValue = String(Number(value) === 1 ? 1 : 0);
        const input = form.querySelector(`input[name="hidden"][value="${hiddenValue}"]`);

        if (input) {
            input.checked = true;
        }
    }

    function resetHidden() {
        setHidden(0);
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
        resetHidden();
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
        setHidden(project?.hidden ?? 0);
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

    function initAdminSearch() {
        if (!adminSearchInput) return;

        adminSearchInput.addEventListener('input', applyAdminSearch);

        adminSearchInput.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            adminSearchInput.value = '';
            applyAdminSearch();
        });

        adminSearchClear?.addEventListener('click', () => {
            adminSearchInput.value = '';
            adminSearchInput.focus();
            applyAdminSearch();
        });

    }

    function applyAdminDensity(mode = 'dense') {
        const density = mode === 'normal' ? 'normal' : 'dense';

        adminRight?.classList.toggle('is-density-dense', density === 'dense');
        adminRight?.classList.toggle('is-density-normal', density === 'normal');

        densityInputs.forEach((input) => {
            input.checked = input.value === density;
        });

        try {
            window.localStorage.setItem(densityStorageKey, density);
        } catch (_) {}
    }

    function initAdminDensity() {
        if (!densityInputs.length) return;

        let savedDensity = 'dense';

        try {
            savedDensity = window.localStorage.getItem(densityStorageKey) || 'dense';
        } catch (_) {}

        applyAdminDensity(savedDensity);

        densityInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (input.checked) {
                    applyAdminDensity(input.value);
                }
            });
        });
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

    async function handleToggleVisibility(projectId, button) {
        const isHidden = button.dataset.hidden === '1';

        const confirmed = confirm(
            isHidden
                ? 'Publier ce projet côté utilisateur ?'
                : 'Dépublier ce projet côté utilisateur ?'
        );

        if (!confirmed) return;

        try {
            button.disabled = true;

            const data = await api('toggle_visibility', { id: projectId });
            const hidden = Number(data.hidden) === 1;

            button.dataset.hidden = hidden ? '1' : '0';
            button.classList.toggle('is-publish', hidden);
            button.classList.toggle('is-unpublish', !hidden);
            button.innerHTML = hidden ? '👁 Publier' : '🚫 Dépublier';
            button.title = hidden ? 'Publier le projet' : 'Dépublier le projet';

            const card = button.closest('.project-card');
            const badge = card?.querySelector('.visibility-badge');

            if (badge) {
                badge.classList.toggle('is-hidden', hidden);
                badge.classList.toggle('is-visible', !hidden);
                badge.textContent = hidden
                    ? 'Masqué côté utilisateur'
                    : 'Visible côté utilisateur';
            }

            if (String(idInput?.value || '') === String(projectId)) {
                setHidden(hidden ? 1 : 0);
            }
        } catch (error) {
            console.error(error);
            alert(error.message || 'Impossible de modifier la visibilité.');
        } finally {
            button.disabled = false;
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

        const publishButton = event.target.closest('.project-card .publish-btn');
        if (publishButton) {
            event.preventDefault();
            event.stopPropagation();

            const projectId = publishButton.dataset.id;
            if (!projectId) return;

            await handleToggleVisibility(projectId, publishButton);
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
    initAdminSearch();
    initAdminDensity();
    setCreateMode();
    requestAnimationFrame(keepStatsPanelInView);
    setTimeout(keepStatsPanelInView, 250);
});
