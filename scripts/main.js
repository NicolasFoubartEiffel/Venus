document.addEventListener('DOMContentLoaded', () => {
    const grid = document.querySelector('.project-grid');
    if (!grid) return;

    const modal = document.getElementById('project-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalSubtext = document.getElementById('modal-subtext');
    const modalDesc = document.getElementById('modal-description');

    if (!modal || !modalTitle || !modalSubtext || !modalDesc) return;

    let currentData = null;
    let currentTile = null; // tuile active (pour style is-active)

    // feedback UI (loading/active)
    const setTileLoading = (tile, on) => {
        if (!tile) return;
        tile.classList.toggle('is-loading', !!on);
    };

    const setTileActive = (tile, on) => {
        document.querySelectorAll('.project-tile.is-active')
            .forEach(t => t.classList.remove('is-active'));
        if (tile && on) tile.classList.add('is-active');
    };

    // decode HTML depuis data-* (car tes data-* sont htmlspecialchars côté PHP)
    const decodeHtml = (s) => {
        const div = document.createElement('div');
        div.innerHTML = s ?? '';
        return div.textContent ?? '';
    };

    const getSafeHtml = (maybeEncodedHtml) => {
        // on décode ce qui vient du data-attr
        const html = decodeHtml(maybeEncodedHtml || '');
        return html.trim();
    };

    const renderSection = (label, title, html) => {
        const t = (title || '').trim();
        const body = (html || '').trim();

        return `
            <div class="card-section">
                <div class="label">${label}</div>
                <div class="value">${t ? t : '<span class="muted">Non renseigné</span>'}</div>
                <div class="wysiwyg wysiwyg--compact" style="margin-top:10px;">
                    ${body ? body : '<span class="muted"><em>Aucune description</em></span>'}
                </div>
            </div>
        `;
    };

    const renderProject = () => {
        const body = (currentData?.description || '').trim();

        return `
        <div class="card-section">
            <div class="section-title section-title--center">
                Description avancée
            </div>

            <div class="wysiwyg" style="margin-top:12px;">
                ${body ? body : '<span class="muted"><em>Aucune description</em></span>'}
            </div>
        </div>
    `;
    };

    const renderInfoBlockWithProject = (title, html) => {
        const t = (title || '').trim();
        const body = (html || '').trim();

        return `
        <div class="card-section">
            <div class="section-title">
                ${t ? t : '<span class="muted">Non renseigné</span>'}
            </div>

            <div class="wysiwyg wysiwyg--compact" style="margin-top:12px;">
                ${body ? body : '<span class="muted"><em>Aucune description</em></span>'}
            </div>
        </div>

        <hr style="border:0;border-top:1px solid rgba(0,0,0,.08);margin:18px 0;">

        ${renderProject()}
    `;
    };

    const renderContact = () =>
        renderInfoBlockWithProject(currentData?.contact, currentData?.contact_desc);

    const renderInfo1 = () =>
        renderInfoBlockWithProject(currentData?.info1_title, currentData?.info1_desc);

    const renderInfo2 = () =>
        renderInfoBlockWithProject(currentData?.info2_title, currentData?.info2_desc);

    const getTpl = (tile, sel) => (tile.querySelector(sel)?.innerHTML || '').trim();

    const openModal = (tile, view = 'project') => {
        currentData = {
            title: tile.dataset.title || '',
            subtext: tile.dataset.subtext || '',
            contact: tile.dataset.contact || '',
            info1_title: tile.dataset.info1Title || '',
            info2_title: tile.dataset.info2Title || '',

            contact_desc: getTpl(tile, '.tpl-contact-desc'),
            info1_desc:   getTpl(tile, '.tpl-info1-desc'),
            info2_desc:   getTpl(tile, '.tpl-info2-desc'),
            description:  getTpl(tile, '.tpl-description'),
        };

        modalTitle.textContent = currentData.title;
        modalSubtext.textContent = currentData.subtext;

        if (view === 'contact') modalDesc.innerHTML = renderContact();
        else if (view === 'info1') modalDesc.innerHTML = renderInfo1();
        else if (view === 'info2') modalDesc.innerHTML = renderInfo2();
        else modalDesc.innerHTML = renderProject();

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        currentTile = tile;
        setTileActive(tile, true);
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        modalTitle.textContent = '';
        modalSubtext.textContent = '';
        modalDesc.innerHTML = '';
        currentData = null;

        setTileActive(null, false);
        currentTile = null;
    };

    // Fermer (backdrop + croix)
    modal.addEventListener('click', (e) => {
        if (e.target.closest('[data-close="1"]')) closeModal();
    });

    // ESC
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });

    // Click sur tuile => ouvre la modal
    grid.addEventListener('click', (e) => {
        const tile = e.target.closest('.project-tile');
        if (!tile) return;

        // Détecter quel bouton (si click sur bouton)
        const btn = e.target.closest('.tile-btn');
        const buttons = btn ? Array.from(tile.querySelectorAll('.tile-btn')) : [];
        const idx = btn ? buttons.indexOf(btn) : -1;

        // view par défaut
        let view = 'project';
        if (idx === 0) view = 'contact';
        if (idx === 1) view = 'info1';
        if (idx === 2) view = 'info2';

        e.preventDefault();

        // éviter double-clic si déjà ouvert sur la même tuile + même vue "project"
        if (modal.classList.contains('is-open') && currentTile === tile && view === 'project') return;

        setTileLoading(tile, true);
        try {
            openModal(tile, view);
        } finally {
            setTileLoading(tile, false);
        }
    });

    // ===== Filtre catégories (toggle) =====
    const pills = Array.from(document.querySelectorAll('.cat-pill[data-category-id]'));
    const tiles = Array.from(document.querySelectorAll('.project-tile[data-category-ids]'));

    const setActivePill = (pill) => {
        pills.forEach(p => {
            const isActive = (p === pill);
            p.classList.toggle('is-active', isActive);
            p.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    };

    const showAll = () => {
        tiles.forEach(t => (t.style.display = ''));
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

    let current = 'all';

    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            const id = pill.dataset.categoryId;

            // toggle: reclick sur la même => all
            if (current === id) {
                current = 'all';
                const allPill = pills.find(p => p.dataset.categoryId === 'all');
                if (allPill) setActivePill(allPill);
                showAll();
                return;
            }

            current = id;
            setActivePill(pill);

            if (id === 'all') showAll();
            else filterByCategoryId(id);
        });
    });
});