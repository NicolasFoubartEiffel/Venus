document.addEventListener('DOMContentLoaded', () => {
    const grid = document.querySelector('.project-grid');
    if (!grid) return;

    const modal = document.getElementById('project-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalSubtext = document.getElementById('modal-subtext');
    const modalDesc = document.getElementById('modal-description');
    const modalActionButtons = Array.from(document.querySelectorAll('[data-modal-action]'));

    if (!modal || !modalTitle || !modalSubtext || !modalDesc) return;

    let currentTile = null;
    let currentData = null;
    let currentView = 'project';

    const hasContent = (value) => String(value || '').trim() !== '';

    const forceLinksTargetBlank = (container) => {
        if (!container) return;

        container.querySelectorAll('a').forEach((link) => {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        });
    };

    const getTpl = (tile, selector) =>
        (tile.querySelector(selector)?.innerHTML || '').trim();

    const renderMuted = (text) => `<span class="muted">${text}</span>`;
    const renderEmptyBlock = () => '<span class="muted"><em>Non renseigné</em></span>';

    const buildTileData = (tile) => ({
        title: tile.dataset.title || '',
        subtext: tile.dataset.subtext || '',

        contact_title: tile.dataset.contactTitle || '',
        resources_title: tile.dataset.resourcesTitle || '',
        description_title: tile.dataset.descriptionTitle || '',

        contact_details: getTpl(tile, '.tpl-contact-details'),
        resources_details: getTpl(tile, '.tpl-resources-details'),
        description_details: getTpl(tile, '.tpl-description-details'),

        project_description: getTpl(tile, '.tpl-project-description'),
    });

    const getTileButtonMetaByIndex = (index) => {
        if (index === 0) {
            return { key: 'contact_title', label: 'Contact', view: 'contact' };
        }
        if (index === 1) {
            return { key: 'resources_title', label: 'Ressources documentaires', view: 'resources' };
        }
        if (index === 2) {
            return { key: 'description_title', label: 'Description avancée', view: 'description' };
        }
        return { key: '', label: '', view: 'project' };
    };

    const getModalActionMeta = (action) => {
        if (action === 'mailto') {
            return { key: 'contact_title', label: 'Contact', view: 'contact' };
        }
        if (action === 'doc') {
            return { key: 'resources_title', label: 'Ressources documentaires', view: 'resources' };
        }
        if (action === 'open') {
            return { key: 'description_title', label: 'Description avancée', view: 'description' };
        }
        return { key: '', label: '', view: 'project' };
    };

    const setButtonAvailability = (button, value, titleWhenAvailable = '') => {
        if (!button) return;

        const available = hasContent(value);

        button.classList.toggle('is-disabled', !available);

        if ('disabled' in button) {
            button.disabled = !available;
        }

        if (available) {
            button.removeAttribute('aria-disabled');
            button.setAttribute('title', titleWhenAvailable || '');
        } else {
            button.setAttribute('aria-disabled', 'true');
            button.setAttribute('title', 'Non renseigné');
        }
    };

    const updateTileButtons = (tile) => {
        if (!tile) return;

        const data = buildTileData(tile);
        const buttons = Array.from(tile.querySelectorAll('.tile-btn'));

        buttons.forEach((button, index) => {
            const meta = getTileButtonMetaByIndex(index);
            const value = meta.key ? data[meta.key] : '';
            setButtonAvailability(button, value, meta.label);
        });
    };

    const updateAllTileButtons = () => {
        document.querySelectorAll('.project-tile').forEach(updateTileButtons);
    };

    const updateModalActionButtons = () => {
        if (!currentData) return;

        modalActionButtons.forEach((button) => {
            const meta = getModalActionMeta(button.dataset.modalAction);
            const value = meta.key ? currentData[meta.key] : '';
            setButtonAvailability(button, value, meta.label);
        });
    };

    const setModalActionActive = (view) => {
        modalActionButtons.forEach((button) => {
            const meta = getModalActionMeta(button.dataset.modalAction);
            const active = meta.view === view;

            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    const setTileActive = (tile, active) => {
        document.querySelectorAll('.project-tile.is-active').forEach((el) => {
            el.classList.remove('is-active');
        });

        if (tile && active) {
            tile.classList.add('is-active');
        }
    };

    const renderProjectView = () => {
        const body = (currentData?.project_description || '').trim();

        return `
            <div class="card-section">
                <div class="section-title section-title--center">Description avancée</div>
                <div class="wysiwyg" style="margin-top:12px;">
                    ${body || renderEmptyBlock()}
                </div>
            </div>
        `;
    };

    const renderInfoView = (title, body) => {
        const safeTitle = (title || '').trim();
        const safeBody = (body || '').trim();

        return `
            <div class="card-section">
                <div class="section-title">
                    ${safeTitle || renderMuted('Non renseigné')}
                </div>
                <div class="wysiwyg wysiwyg--compact" style="margin-top:12px;">
                    ${safeBody || renderEmptyBlock()}
                </div>
            </div>
        `;
    };

    const renderView = (view) => {
        switch (view) {
            case 'contact':
                return renderInfoView(
                    currentData?.contact_title,
                    currentData?.contact_details
                );

            case 'resources':
                return renderInfoView(
                    currentData?.resources_title,
                    currentData?.resources_details
                );

            case 'description':
                return renderInfoView(
                    currentData?.description_title,
                    currentData?.description_details
                );

            case 'project':
            default:
                return renderProjectView();
        }
    };

    const updateModalContent = (view) => {
        if (!currentData) return;

        currentView = view;
        modalTitle.textContent = currentData.title || '';
        modalSubtext.textContent = currentData.subtext || '';
        modalDesc.innerHTML = renderView(view);

        forceLinksTargetBlank(modalDesc);
        updateModalActionButtons();
        setModalActionActive(view);
    };

    const openModal = (tile, view = 'project') => {
        currentTile = tile;
        currentData = buildTileData(tile);

        updateTileButtons(tile);
        updateModalContent(view);

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        setTileActive(tile, true);
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        modalTitle.textContent = '';
        modalSubtext.textContent = '';
        modalDesc.innerHTML = '';

        currentTile = null;
        currentData = null;
        currentView = 'project';

        setTileActive(null, false);
        setModalActionActive(null);
    };

    updateAllTileButtons();

    modal.addEventListener('click', (e) => {
        if (e.target.closest('[data-close="1"]')) {
            closeModal();
            return;
        }

        const actionButton = e.target.closest('[data-modal-action]');
        if (!actionButton || !currentData) return;

        if (
            actionButton.disabled ||
            actionButton.classList.contains('is-disabled') ||
            actionButton.getAttribute('aria-disabled') === 'true'
        ) {
            e.preventDefault();
            return;
        }

        const meta = getModalActionMeta(actionButton.dataset.modalAction);
        updateModalContent(meta.view);
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    grid.addEventListener('click', (e) => {
        const tile = e.target.closest('.project-tile');
        if (!tile) return;

        const button = e.target.closest('.tile-btn');

        if (
            button &&
            (
                button.disabled ||
                button.classList.contains('is-disabled') ||
                button.getAttribute('aria-disabled') === 'true'
            )
        ) {
            e.preventDefault();
            return;
        }

        let view = 'project';

        if (button) {
            const buttons = Array.from(tile.querySelectorAll('.tile-btn'));
            const index = buttons.indexOf(button);
            view = getTileButtonMetaByIndex(index).view;
        }

        e.preventDefault();

        if (
            modal.classList.contains('is-open') &&
            currentTile === tile &&
            currentView === view
        ) {
            return;
        }

        openModal(tile, view);
    });

    const pills = Array.from(document.querySelectorAll('.cat-pill[data-category-id]'));
    const tiles = Array.from(document.querySelectorAll('.project-tile[data-category-ids]'));

    let currentCategory = 'all';

    const setActivePill = (activePill) => {
        pills.forEach((pill) => {
            const isActive = pill === activePill;
            pill.classList.toggle('is-active', isActive);
            pill.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    };

    const showAllTiles = () => {
        tiles.forEach((tile) => {
            tile.style.display = '';
        });
    };

    const filterByCategoryId = (categoryId) => {
        const wanted = String(categoryId);

        tiles.forEach((tile) => {
            const ids = (tile.dataset.categoryIds || '')
                .split(',')
                .map((v) => v.trim())
                .filter(Boolean);

            tile.style.display = ids.includes(wanted) ? '' : 'none';
        });
    };

    pills.forEach((pill) => {
        pill.addEventListener('click', () => {
            const id = pill.dataset.categoryId;

            if (currentCategory === id) {
                currentCategory = 'all';
                const allPill = pills.find((p) => p.dataset.categoryId === 'all');
                if (allPill) setActivePill(allPill);
                showAllTiles();
                return;
            }

            currentCategory = id;
            setActivePill(pill);

            if (id === 'all') {
                showAllTiles();
            } else {
                filterByCategoryId(id);
            }
        });
    });
});