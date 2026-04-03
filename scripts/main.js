document.addEventListener('DOMContentLoaded', () => {
    const grid = document.querySelector('.project-grid');
    if (!grid) return;

    const modal = document.getElementById('project-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalSubtext = document.getElementById('modal-subtext');
    const modalDesc = document.getElementById('modal-description');
    const modalActionButtons = Array.from(
        document.querySelectorAll('[data-modal-action]')
    );

    const forceLinksTargetBlank = (container) => {
        if (!container) return;

        container.querySelectorAll('a').forEach(link => {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        });
    };

    if (!modal || !modalTitle || !modalSubtext || !modalDesc) return;

    let currentTile = null;
    let currentData = null;
    let currentView = 'project';

    /* =========================================================
     * Helpers UI
     * ========================================================= */
    const setTileLoading = (tile, on) => {
        if (!tile) return;
        tile.classList.toggle('is-loading', !!on);
    };

    const setTileActive = (tile, on) => {
        document.querySelectorAll('.project-tile.is-active')
            .forEach(t => t.classList.remove('is-active'));

        if (tile && on) {
            tile.classList.add('is-active');
        }
    };

    const setModalActionActive = (view) => {
        const map = {
            contact: 'mailto',
            info1: 'open',
            info2: 'doc',
        };

        modalActionButtons.forEach(btn => {
            const action = btn.dataset.modalAction;
            const isActive = map[view] === action;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    /* =========================================================
     * Helpers contenu
     * ========================================================= */
    const getTpl = (tile, selector) =>
        (tile.querySelector(selector)?.innerHTML || '').trim();

    const buildTileData = (tile) => ({
        title: tile.dataset.title || '',
        subtext: tile.dataset.subtext || '',
        contact: tile.dataset.contact || '',
        info1_title: tile.dataset.info1Title || '',
        info2_title: tile.dataset.info2Title || '',

        contact_desc: getTpl(tile, '.tpl-contact-desc'),
        info1_desc: getTpl(tile, '.tpl-info1-desc'),
        info2_desc: getTpl(tile, '.tpl-info2-desc'),
        description: getTpl(tile, '.tpl-description'),
    });

    const renderEmptyText = (text) =>
        `<span class="muted">${text}</span>`;

    const renderEmptyDescription = () =>
        '<span class="muted"><em>Aucune description</em></span>';

    const renderProject = () => {
        const body = (currentData?.description || '').trim();

        return `
            <div class="card-section">
                <div class="section-title section-title--center">
                    Description avancée
                </div>

                <div class="wysiwyg" style="margin-top:12px;">
                    ${body || renderEmptyDescription()}
                </div>
            </div>
        `;
    };

    const renderInfoBlock = (title, html, withProject = true) => {
        const t = (title || '').trim();
        const body = (html || '').trim();

        return `
            <div class="card-section">
                <div class="section-title">
                    ${t || renderEmptyText('Non renseigné')}
                </div>

                <div class="wysiwyg wysiwyg--compact" style="margin-top:12px;">
                    ${body || renderEmptyDescription()}
                </div>
            </div>

            ${withProject ? `
                <hr style="border:0;border-top:1px solid rgba(0,0,0,.08);margin:18px 0;">
                ${renderProject()}
            ` : ''}
        `;
    };

    const renderView = (view) => {
        switch (view) {
            case 'contact':
                return renderInfoBlock(currentData?.contact, currentData?.contact_desc);

            case 'info1':
                return renderInfoBlock(currentData?.info1_title, currentData?.info1_desc);

            case 'info2':
                return renderInfoBlock(currentData?.info2_title, currentData?.info2_desc);

            case 'project':
            default:
                return renderProject();
        }
    };

    const updateModalContent = (view) => {
        if (!currentData) return;

        currentView = view;
        modalTitle.textContent = currentData.title;
        modalSubtext.textContent = currentData.subtext;
        modalDesc.innerHTML = renderView(view);

        forceLinksTargetBlank(modalDesc);

        setModalActionActive(view);
    };

    /* =========================================================
     * Modal
     * ========================================================= */
    const openModal = (tile, view = 'project') => {
        currentTile = tile;
        currentData = buildTileData(tile);

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

    /* =========================================================
     * Mapping actions / vues
     * ========================================================= */
    const getViewFromTileButtonIndex = (idx) => {
        if (idx === 0) return 'contact';
        if (idx === 1) return 'info1';
        if (idx === 2) return 'info2';
        return 'project';
    };

    const getViewFromModalAction = (action) => {
        if (action === 'mailto') return 'contact';
        if (action === 'open') return 'info1';
        if (action === 'doc') return 'info2';
        return 'project';
    };

    /* =========================================================
     * Events modal
     * ========================================================= */
    modal.addEventListener('click', (e) => {
        if (e.target.closest('[data-close="1"]')) {
            closeModal();
            return;
        }

        const actionBtn = e.target.closest('[data-modal-action]');
        if (!actionBtn || !currentData) return;

        const action = actionBtn.dataset.modalAction;
        const view = getViewFromModalAction(action);

        updateModalContent(view);
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    /* =========================================================
     * Events grid
     * ========================================================= */
    grid.addEventListener('click', (e) => {
        const tile = e.target.closest('.project-tile');
        if (!tile) return;

        const btn = e.target.closest('.tile-btn');
        const buttons = btn ? Array.from(tile.querySelectorAll('.tile-btn')) : [];
        const idx = btn ? buttons.indexOf(btn) : -1;

        const view = getViewFromTileButtonIndex(idx);

        e.preventDefault();

        if (
            modal.classList.contains('is-open') &&
            currentTile === tile &&
            currentView === view
        ) {
            return;
        }

        setTileLoading(tile, true);

        try {
            openModal(tile, view);
        } finally {
            setTileLoading(tile, false);
        }
    });

    /* =========================================================
     * Filtres catégories
     * ========================================================= */
    const pills = Array.from(document.querySelectorAll('.cat-pill[data-category-id]'));
    const tiles = Array.from(document.querySelectorAll('.project-tile[data-category-ids]'));

    let currentCategory = 'all';

    const setActivePill = (pill) => {
        pills.forEach(p => {
            const isActive = (p === pill);
            p.classList.toggle('is-active', isActive);
            p.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    };

    const showAllTiles = () => {
        tiles.forEach(tile => {
            tile.style.display = '';
        });
    };

    const filterByCategoryId = (catId) => {
        const wanted = String(catId);

        tiles.forEach(tile => {
            const ids = (tile.dataset.categoryIds || '')
                .split(',')
                .map(s => s.trim())
                .filter(Boolean);

            tile.style.display = ids.includes(wanted) ? '' : 'none';
        });
    };

    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            const id = pill.dataset.categoryId;

            if (currentCategory === id) {
                currentCategory = 'all';
                const allPill = pills.find(p => p.dataset.categoryId === 'all');
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