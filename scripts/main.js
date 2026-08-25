document.addEventListener('DOMContentLoaded', () => {
    const openBasicModal = (modal) => {
        if (!modal) return;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeBasicModal = (modal) => {
        if (!modal) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    const stripFontFamiliesFromNode = (root) => {
        if (!root?.querySelectorAll) return;

        const styledNodes = [
            ...(root.matches?.('[style]') ? [root] : []),
            ...root.querySelectorAll('[style]'),
        ];

        const faceNodes = [
            ...(root.matches?.('[face]') ? [root] : []),
            ...root.querySelectorAll('[face]'),
        ];

        styledNodes.forEach((node) => {
            node.style.removeProperty('font-family');

            if (!node.getAttribute('style')?.trim()) {
                node.removeAttribute('style');
            }
        });

        faceNodes.forEach((node) => {
            node.removeAttribute('face');
        });
    };

    const stripFontFamiliesFromHtml = (html = '') => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        stripFontFamiliesFromNode(wrapper);
        return wrapper.innerHTML;
    };

    const copyTextToClipboard = async (text) => {
        if (navigator.clipboard?.writeText) {
            try {
                await navigator.clipboard.writeText(text);
                return;
            } catch (_) {}
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.top = '-9999px';
        textarea.style.left = '-9999px';

        document.body.appendChild(textarea);
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);

        const copied = document.execCommand('copy');
        textarea.remove();

        if (!copied) {
            throw new Error('Copie impossible');
        }
    };

    // =========================
    // MODAL FIL D'ACTUALITÉS
    // =========================
    const calendarModal = document.getElementById('calendar-modal');
    const openCalendarBtn = document.querySelector('[data-open-calendar]');

    if (calendarModal && openCalendarBtn) {
        const calendarTitle = document.getElementById('calendar-modal-title');
        const calendarSubtext = document.getElementById('calendar-modal-subtext');
        const calendarKicker = document.getElementById('calendar-modal-kicker');
        const calendarListView = document.getElementById('calendar-list-view');
        const calendarDetailView = document.getElementById('calendar-detail-view');
        const calendarContent = document.getElementById('calendar-mail-content');
        const calendarBackBtn = calendarModal.querySelector('[data-calendar-back]');

        const getCurrentCategoryLabel = () => {
            const activePill = document.querySelector('.cat-pill.is-active[data-category-id]');

            if (!activePill || activePill.dataset.categoryId === 'all') {
                return '';
            }

            return activePill.textContent.trim();
        };

        const resetCalendarModal = () => {
            calendarKicker.textContent = 'Vie de l’étudiant';
            calendarTitle.textContent = "Fils d'actualités";
            calendarSubtext.textContent = 'Liste des informations pour la catégorie courante';

            const categoryLabel = getCurrentCategoryLabel();

            calendarKicker.textContent = categoryLabel;
            calendarKicker.hidden = categoryLabel === '';

            calendarListView.hidden = false;
            calendarDetailView.hidden = true;
            calendarBackBtn.hidden = true;
            calendarContent.innerHTML = '';
        };

        const openCalendarDetail = (button) => {
            const title = button.dataset.title || '';
            const date = button.dataset.date || '';
            const sender = button.dataset.sender || '';
            const content = button.dataset.content || '';

            calendarKicker.hidden = false;
            calendarKicker.textContent = 'Message';
            calendarTitle.textContent = title;
            calendarSubtext.textContent = `${date} — ${sender || 'Expéditeur inconnu'}`;

            calendarListView.hidden = true;
            calendarDetailView.hidden = false;
            calendarBackBtn.hidden = false;

            calendarContent.innerHTML = stripFontFamiliesFromHtml(content || '<p><em>Contenu non disponible.</em></p>');
        };

        openCalendarBtn.addEventListener('click', () => {
            resetCalendarModal();
            openBasicModal(calendarModal);
        });

        calendarModal.addEventListener('click', (e) => {
            if (e.target.closest('[data-calendar-close="1"]')) {
                closeBasicModal(calendarModal);
                resetCalendarModal();
                return;
            }

            if (e.target.closest('[data-calendar-back]')) {
                resetCalendarModal();
                return;
            }

            const mailButton = e.target.closest('[data-calendar-mail]');
            if (mailButton) {
                openCalendarDetail(mailButton);
            }
        });
    }

    // =========================
    // MODAL PROJET
    // =========================
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
    let currentView = 'description';

    const hasContent = (value) => String(value || '').trim() !== '';
    const normalizeSearch = (value) =>
        String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();

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

        contact_details: getTpl(tile, '.tpl-contact-details'),
        resources_details: getTpl(tile, '.tpl-resources-details'),
        description_details: getTpl(tile, '.tpl-description-details'),

        project_description: getTpl(tile, '.tpl-project-description'),
    });

    const getTileButtonMetaByIndex = (index) => {
        if (index === 0) {
            return { key: 'description_details', label: 'Description', view: 'description' };
        }

        if (index === 1) {
            return { key: 'resources_details', label: 'Ressources', view: 'resources' };
        }

        if (index === 2) {
            return { key: 'contact_details', label: 'Contact', view: 'contact' };
        }

        return { key: '', label: '', view: 'project' };
    };

    const getModalActionMeta = (action) => {
        if (action === 'mailto') {
            return { key: 'contact_details', label: 'Contact', view: 'contact' };
        }

        if (action === 'open') {
            return { key: 'resources_details', label: 'Ressources', view: 'resources' };
        }

        if (action === 'doc') {
            return { key: 'description_details', label: 'Description', view: 'description' };
        }

        if (action === 'share') {
            return { key: '', label: 'Partager la rubrique', view: 'share' };
        }

        return { key: '', label: '', view: 'project' };
    };

    const trackTileInteraction = (tile, view, source = 'tile') => {
        const projectId = tile?.dataset?.projectId || modal?.dataset?.projectId || '';

        if (!projectId || !['description', 'resources', 'contact'].includes(view)) {
            return;
        }

        const params = new URLSearchParams({
            tile_id: projectId,
            tab_key: view,
            source,
        });

        if (navigator.sendBeacon) {
            const blob = new Blob([params.toString()], {
                type: 'application/x-www-form-urlencoded; charset=UTF-8',
            });

            if (navigator.sendBeacon('db/tracking.php', blob)) {
                return;
            }
        }

        fetch('db/tracking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: params,
            keepalive: true,
        }).catch(() => {});
    };

    const setButtonAvailability = (button, value, titleWhenAvailable = '') => {
        if (!button) return;

        const action = button.dataset.modalAction || '';

        if (action === 'share') {
            button.classList.remove('is-disabled');
            button.removeAttribute('disabled');
            button.removeAttribute('aria-disabled');
            button.setAttribute('title', titleWhenAvailable || 'Partager la rubrique');
            return;
        }

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
            const action = button.dataset.modalAction || '';
            const meta = getModalActionMeta(action);
            const value = meta.key ? currentData[meta.key] : '';

            setButtonAvailability(button, value, meta.label);
        });
    };

    const setModalActionActive = (view) => {
        modalActionButtons.forEach((button) => {
            const meta = getModalActionMeta(button.dataset.modalAction);
            const active = meta.view === view && view !== 'share';

            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            if (active) {
                button.setAttribute('aria-current', 'true');
            } else {
                button.removeAttribute('aria-current');
            }
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

    const renderInfoView = (title, body, options = {}) => {
        const safeTitle = (title || '').trim();
        const safeBody = stripFontFamiliesFromHtml((body || '').trim());
        const showCopyButton = Boolean(options.copyable);

        return `
            <div class="card-section">
                <div class="section-head">
                    <h2 class="section-title">
                        ${safeTitle || renderMuted('Non renseigné')}
                    </h2>
                    ${showCopyButton ? `
                        <!--<button
                                type="button"
                                class="section-copy-btn"
                                data-copy-section
                                aria-label="Copier le contenu"
                                title="Copier le contenu"
                        >
                            Copier
                        </button>-->
                    ` : ''}
                </div>
                <div class="wysiwyg wysiwyg--compact" data-copy-content style="margin-top:12px;">
                    ${safeBody || renderEmptyBlock()}
                </div>
            </div>
        `;
    };

    const renderView = (view) => {
        switch (view) {
            case 'contact':
                return renderInfoView(
                    'Contact',
                    currentData?.contact_details,
                    { copyable: true }
                );

            case 'resources':
                return renderInfoView(
                    'Ressources',
                    currentData?.resources_details,
                    { copyable: true }
                );

            case 'description':
                return renderInfoView(
                    'Description',
                    currentData?.description_details
                );

            default:
                return renderInfoView(
                    'Description',
                    currentData?.description_details
                );
        }
    };

    const updateModalContent = (view, options = {}) => {
        if (!currentData) return;

        currentView = view;
        modalTitle.textContent = currentData.title || '';
        modalDesc.innerHTML = renderView(view);

        forceLinksTargetBlank(modalDesc);
        updateModalActionButtons();
        setModalActionActive(view);

        if (!options.skipTracking) {
            trackTileInteraction(currentTile, view, options.source || 'modal');
        }
    };

    const copyCurrentTileLink = async (button) => {
        if (!currentTile) return;

        const projectId = currentTile.dataset.projectId || modal.dataset.projectId || '';

        if (!projectId) return;

        const url = new URL(window.location.href);
        url.searchParams.set('tile_id', projectId);

        if (['contact', 'resources', 'description'].includes(currentView)) {
            url.searchParams.set('section', currentView);
        } else {
            url.searchParams.delete('section');
        }

        const previousTitle = button.getAttribute('title') || 'Partager la rubrique';
        const previousLabel = button.getAttribute('aria-label') || 'Partager la rubrique';
        const previousText = button.textContent;

        try {
            await copyTextToClipboard(url.toString());
            showCopyToast();
            button.classList.add('is-copied');
            button.setAttribute('title', 'Lien copié !');
            button.setAttribute('aria-label', 'Lien copié !');

            if (previousText.trim() === '🔗') {
                button.textContent = '✅';
            }

            setTimeout(() => {
                button.setAttribute('title', previousTitle);
                button.setAttribute('aria-label', previousLabel);
                button.classList.remove('is-copied');
                if (previousText.trim() === '🔗') {
                    button.textContent = previousText;
                }
            }, 1500);
        } catch (error) {
            console.error('Impossible de copier le lien :', error);

            button.setAttribute('title', 'Copie impossible');
            button.setAttribute('aria-label', 'Copie impossible');

            setTimeout(() => {
                button.setAttribute('title', previousTitle);
                button.setAttribute('aria-label', previousLabel);
            }, 1500);
        }
    };

    const copyModalSectionContent = async (button) => {
        const section = button.closest('.card-section');
        const content = section?.querySelector('[data-copy-content]');
        const text = content?.innerText?.trim() || '';

        if (!text) return;

        const previousText = button.textContent;
        const previousTitle = button.getAttribute('title') || 'Copier le contenu';

        try {
            await copyTextToClipboard(text);
            showCopyToast('Contenu copié dans le presse papier');

            button.classList.add('is-copied');
            button.textContent = 'Copié';
            button.setAttribute('title', 'Contenu copié');

            setTimeout(() => {
                button.classList.remove('is-copied');
                button.textContent = previousText;
                button.setAttribute('title', previousTitle);
            }, 1400);
        } catch (error) {
            console.error('Impossible de copier le contenu :', error);
            button.setAttribute('title', 'Copie impossible');

            setTimeout(() => {
                button.setAttribute('title', previousTitle);
            }, 1400);
        }
    };

    const openModal = (tile, view = 'description', options = {}) => {
        currentTile = tile;
        modal.dataset.projectId = tile.dataset.projectId || '';

        const tileFavorite = tile.querySelector('.favorite-badge');
        const modalFavorite = modal.querySelector('.modal-favorite');
        const modalCategoryBeans = document.getElementById('modal-category-beans');

        if (tileFavorite && modalFavorite) {
            const isFavorite = tileFavorite.classList.contains('is-active');

            modalFavorite.classList.toggle('is-active', isFavorite);
            modalFavorite.textContent = isFavorite ? '★' : '☆';
            modalFavorite.setAttribute(
                'title',
                isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'
            );
            modalFavorite.setAttribute(
                'aria-label',
                isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'
            );
        }

        if (modalCategoryBeans) {
            const tileCategories = tile.querySelector('.tile-categories');

            modalCategoryBeans.innerHTML = tileCategories
                ? tileCategories.innerHTML
                : '';
        }

        currentData = buildTileData(tile);

        updateTileButtons(tile);
        updateModalContent(view, {
            source: options.source || 'tile',
            skipTracking: Boolean(options.skipTracking),
        });

        openBasicModal(modal);
        setTileActive(tile, true);
    };

    const closeModal = () => {
        closeBasicModal(modal);

        modalTitle.textContent = '';
        modalSubtext.textContent = '';
        modalDesc.innerHTML = '';

        currentTile = null;
        currentData = null;
        currentView = 'description';

        setTileActive(null, false);
        setModalActionActive(null);
        delete modal.dataset.projectId;
    };

    updateAllTileButtons();

    modal.addEventListener('click', (e) => {
        if (e.target.closest('[data-close="1"]')) {
            closeModal();
            return;
        }

        const copySectionButton = e.target.closest('[data-copy-section]');
        if (copySectionButton) {
            e.preventDefault();
            copyModalSectionContent(copySectionButton);
            return;
        }

        const actionButton = e.target.closest('[data-modal-action]');
        if (!actionButton || !currentData) return;

        const action = actionButton.dataset.modalAction || '';

        if (action === 'share') {
            e.preventDefault();
            copyCurrentTileLink(actionButton);
            return;
        }

        if (
            actionButton.disabled ||
            actionButton.classList.contains('is-disabled') ||
            actionButton.getAttribute('aria-disabled') === 'true'
        ) {
            e.preventDefault();
            return;
        }

        const meta = getModalActionMeta(action);
        updateModalContent(meta.view, { source: 'modal' });
    });

    window.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;

        if (modal.classList.contains('is-open')) {
            closeModal();
        }

        if (calendarModal?.classList.contains('is-open')) {
            closeBasicModal(calendarModal);
        }
    });

    grid.addEventListener('click', (e) => {
        if (e.target.closest('.favorite-badge')) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }

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

        let view = 'description';

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

        openModal(tile, view, { source: button ? 'tile_tab' : 'tile' });
    });

    const pills = Array.from(document.querySelectorAll('.cat-pill[data-category-id]'));
    const tiles = Array.from(document.querySelectorAll('.project-tile[data-category-ids]'));
    const searchInput = document.querySelector('[data-project-search]');
    const clearSearchButton = document.querySelector('[data-clear-search]');
    const emptyResults = document.querySelector('[data-empty-results]');

    let currentCategory = 'all';
    let currentSearch = '';

    const setActivePill = (activePill) => {
        pills.forEach((pill) => {
            const isActive = pill === activePill;
            pill.classList.toggle('is-active', isActive);
            pill.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    };

    const getTileCategoryIds = (tile) =>
        (tile.dataset.categoryIds || '')
            .split(',')
            .map((v) => v.trim())
            .filter(Boolean);

    const tileMatchesCategory = (tile) =>
        currentCategory === 'all' || getTileCategoryIds(tile).includes(String(currentCategory));

    const tileMatchesSearch = (tile) => {
        if (!currentSearch) return true;

        const searchText = normalizeSearch(tile.dataset.searchText || '');
        return currentSearch
            .split(/\s+/)
            .filter(Boolean)
            .every((term) => searchText.includes(term));
    };

    const applyFilters = () => {
        let visibleCount = 0;

        tiles.forEach((tile) => {
            const visible = tileMatchesCategory(tile) && tileMatchesSearch(tile);
            tile.style.display = visible ? '' : 'none';
            tile.setAttribute('aria-hidden', visible ? 'false' : 'true');

            if (visible) {
                visibleCount += 1;
            }
        });

        if (emptyResults) {
            emptyResults.hidden = visibleCount > 0;
        }

        if (clearSearchButton) {
            clearSearchButton.hidden = currentSearch === '';
        }

    };

    pills.forEach((pill) => {
        pill.addEventListener('click', () => {
            const id = pill.dataset.categoryId;

            if (currentCategory === id) {
                currentCategory = 'all';
                const allPill = pills.find((p) => p.dataset.categoryId === 'all');

                if (allPill) {
                    setActivePill(allPill);
                }

                applyFilters();
                return;
            }

            currentCategory = id;
            setActivePill(pill);
            applyFilters();
        });
    });

    searchInput?.addEventListener('input', () => {
        currentSearch = normalizeSearch(searchInput.value);
        applyFilters();
    });

    clearSearchButton?.addEventListener('click', () => {
        if (!searchInput) return;

        searchInput.value = '';
        currentSearch = '';
        searchInput.focus();
        applyFilters();
    });

    applyFilters();


    const showCopyToast = (message = 'Le lien a été copié dans votre presse papier') => {
        const toast = document.getElementById('copy-toast');

        if (!toast) return;

        toast.textContent = message;
        toast.classList.add('is-visible');
        toast.setAttribute('aria-hidden', 'false');

        clearTimeout(showCopyToast._timer);

        showCopyToast._timer = setTimeout(() => {
            toast.classList.remove('is-visible');
            toast.setAttribute('aria-hidden', 'true');
        }, 2200);
    };

    const openTileFromUrl = () => {
        const url = new URL(window.location.href);
        const tileId = url.searchParams.get('tile_id');
        const requestedSection = url.searchParams.get('section');
        const requestedView = ['contact', 'resources', 'description'].includes(requestedSection)
            ? requestedSection
            : 'description';

        if (!tileId) return;

        // Nettoie l'URL sans recharger la page
        url.searchParams.delete('tile_id');
        url.searchParams.delete('section');

        let pathname = url.pathname;

        // Retire index.php à la fin
        pathname = pathname.replace(/\/index\.php$/, '/');

        const cleanUrl =
            pathname +
            (url.searchParams.toString()
                ? '?' + url.searchParams.toString()
                : '') +
            url.hash;


        history.replaceState({}, '', cleanUrl);

        const tile = document.querySelector(
            `.project-tile[data-project-id="${CSS.escape(tileId)}"]`
        );

        if (!tile) return;

        tile.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        setTimeout(() => {
            openModal(tile, requestedView, { source: 'shared_link' });
        }, 250);
    };

    openTileFromUrl();
});
