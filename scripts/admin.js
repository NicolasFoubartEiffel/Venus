document.addEventListener('DOMContentLoaded', () => {
    // =====================================================
    // 0) Garde-fous + cache DOM (on évite de re-query partout)
    // =====================================================
    const $ = (id) => document.getElementById(id);
    const qs = (sel, root = document) => root.querySelector(sel);
    const qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    const form = $('create-project-form');
    if (!form) return;

    const h2 = $('project-form-title');
    const actionInput = $('project-action');
    const idInput = $('project-id');
    const submitBtn = $('project-submit-btn');
    const cancelBtn = $('project-cancel-btn');

    const adminRight = qs('.admin-right') || document;

    // Catégories (checkbox)
    const categoryCbs = qsa('#category-checkboxes input[type="checkbox"][name="category_ids[]"]');

    // =====================================================
    // 1) Helpers Catégories
    // =====================================================
    const Categories = {
        clear() {
            categoryCbs.forEach((cb) => (cb.checked = false));
        },
        set(ids) {
            const set = new Set((Array.isArray(ids) ? ids : []).map(String));
            categoryCbs.forEach((cb) => (cb.checked = set.has(String(cb.value))));
        },
        hasOne() {
            return !categoryCbs.length || categoryCbs.some((cb) => cb.checked);
        },
    };

    // =====================================================
    // 2) API helper (POST FormData -> JSON)
    // =====================================================
    const api = async (action, payload = {}) => {
        const fd = new FormData();
        fd.append('action', action);

        for (const [k, v] of Object.entries(payload)) {
            if (Array.isArray(v)) v.forEach((x) => fd.append(k, x));
            else fd.append(k, v ?? '');
        }

        const res = await fetch('db/project.php', { method: 'POST', body: fd });
        return res.json();
    };

    // Wrapper “safe” qui gère success/erreur + reload
    const handle = async (promise, okMsg) => {
        try {
            const data = await promise;
            if (!data?.success) throw new Error(data?.message || 'Inconnue');
            alert(okMsg);
            location.reload();
        } catch (err) {
            console.error(err);
            alert('Erreur : ' + (err?.message || 'Serveur'));
        }
    };

    // =====================================================
    // 3) TinyMCE bridge (simplifié)
    // =====================================================
    const Tiny = (() => {
        const tm = () => (window?.tinymce ? window.tinymce : null);
        const editorReady = (ed) => !!(ed && ed.initialized && ed.undoManager);

        // Réessaie quelques fois de set le contenu si l’editor n’est pas encore prêt
        const setWithRetry = (id, html, tries = 20) => {
            const t = tm();
            const val = html ?? '';

            // Toujours sync la textarea (utile si Tiny absent)
            const ta = $(id);
            if (ta) ta.value = val;

            if (!t) return; // Tiny pas chargé

            const ed = t.get(id);
            if (editorReady(ed)) {
                try { ed.setContent(val); } catch (_) {}
                try { ed.undoManager.clear?.(); } catch (_) {}
                try { ed.undoManager.reset?.(); } catch (_) {}
                return;
            }

            if (tries <= 0) return;
            setTimeout(() => setWithRetry(id, val, tries - 1), 80);
        };

        const initWhenAvailable = () => {
            const init = () => {
                const t = tm();
                if (!t) return;

                // déjà initialisé
                if (Array.isArray(t.editors) && t.editors.length) return;

                t.init({
                    selector: 'textarea.tinymce',
                    height: 220,
                    menubar: true,
                    branding: false,
                    plugins:
                        'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount autoresize code',
                    toolbar:
                        'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat | code',
                    autoresize_bottom_margin: 10,
                    content_style: 'body { font-family: Segoe UI, sans-serif; font-size: 14px; }',
                });
            };

            init();
            if (tm()) return;

            // TinyMCE CDN peut arriver après : on “poll” 6s max
            let tries = 0;
            const maxTries = 60;
            const timer = setInterval(() => {
                tries++;
                init();
                if (tm() || tries >= maxTries) clearInterval(timer);
            }, 100);
        };

        const setMany = (map) => {
            for (const [id, html] of Object.entries(map)) setWithRetry(id, html);
        };

        const clearAll = () => {
            qsa('textarea.tinymce').forEach((ta) => (ta.value = ''));

            const t = tm();
            if (!t?.editors) return;

            t.editors.forEach((ed) => {
                if (!editorReady(ed)) return;
                try { ed.setContent(''); } catch (_) {}
                try { ed.undoManager.clear?.(); } catch (_) {}
                try { ed.undoManager.reset?.(); } catch (_) {}
            });
        };

        const triggerSave = () => {
            const t = tm();
            try { t?.triggerSave?.(); } catch (_) {}
        };

        const repaintIn = (pane) => {
            const t = tm();
            if (!pane || !t) return;

            pane.querySelectorAll('textarea.tinymce').forEach((ta) => {
                const ed = t.get(ta.id);
                if (!editorReady(ed)) return;
                try { ed.execCommand?.('mceRepaint'); } catch (_) {}
            });
        };

        return { initWhenAvailable, setMany, clearAll, triggerSave, repaintIn };
    })();

    Tiny.initWhenAvailable();

    // =====================================================
    // 4) Segmented tabs (Contact / Info1 / Info2) + SLIDE PANES
    // - On ne met PLUS "hidden" sur les panes (sinon pas d'animation)
    // - On sync data-active sur .segmented ET .seg-wrap (pour la track)
    // =====================================================
    const seg = qs('.segmented');
    const segWrap = qs('.seg-wrap'); // wrapper du slider (ajouté dans ton HTML)

    const Seg = (() => {
        if (!seg) return { activate: () => {}, activePane: () => null };

        const buttons = qsa('.seg-btn', seg);
        const panes = buttons.map((b) => $(b.dataset.target)).filter(Boolean);

        const activate = (idx) => {
            // 1) indicateur du haut
            seg.dataset.active = String(idx);

            // 2) slider du dessous
            if (segWrap) segWrap.dataset.active = String(idx);

            // 3) boutons
            buttons.forEach((b, i) => {
                const on = i === idx;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
                b.setAttribute('tabindex', on ? '0' : '-1');
            });

            // 4) panes (PAS DE hidden, on utilise aria-hidden)
            panes.forEach((p, i) => {
                const on = i === idx;
                p.classList.toggle('is-active', on);
                p.setAttribute('aria-hidden', on ? 'false' : 'true');
            });

            // TinyMCE aime pas trop les transitions/containers -> repaint après déplacement
            setTimeout(() => Tiny.repaintIn(panes[idx]), 120);
        };

        buttons.forEach((btn, idx) => btn.addEventListener('click', () => activate(idx)));
        activate(0);

        return {
            activate,
            activePane: () => qs('.seg-pane.is-active'),
        };
    })();

    // =====================================================
    // 5) Mapping champs formulaire <-> objet projet
    // =====================================================
    const TEXT_FIELDS = [
        ['project-title', 'title'],
        ['project-subtext', 'subtext'],
        ['project-contact', 'contact'],
        ['info_1_title', 'info_1_title'],
        ['info_2_title', 'info_2_title'],
    ];

    const HTML_FIELDS = {
        'contact-description': 'contact_description',
        'info-1-description': 'info_1_description',
        'info-2-description': 'info_2_description',
        'project-description': 'description',
    };

    const fillText = (project) => {
        TEXT_FIELDS.forEach(([id, key]) => {
            const el = $(id);
            if (el) el.value = project?.[key] ?? '';
        });
    };

    const fillTiny = (project) => {
        const map = {};
        for (const [id, key] of Object.entries(HTML_FIELDS)) {
            map[id] = project?.[key] ?? '';
        }
        Tiny.setMany(map);
        Tiny.repaintIn(Seg.activePane());
    };

    // =====================================================
    // 6) Mode create / edit (1 seule fonction)
    // =====================================================
    const setMode = (mode, project = null) => {
        const isEdit = mode === 'edit';

        if (h2) h2.textContent = isEdit ? `Éditer : ${project?.title || ''}` : 'Ajouter un projet';
        if (actionInput) actionInput.value = isEdit ? 'update_project' : 'create_projet';
        if (idInput) idInput.value = isEdit ? project?.id || '' : '';

        if (submitBtn) submitBtn.textContent = 'Sauvegarder';
        if (cancelBtn) cancelBtn.style.display = isEdit ? 'inline-block' : 'none';

        if (!isEdit) {
            // reset complet
            form.reset();
            Categories.clear();
            Tiny.clearAll();
            Seg.activate(0);
            return;
        }

        // remplir depuis l’objet projet
        fillText(project);
        Categories.set(project?.category_ids);
        fillTiny(project);
        Seg.activate(0);
    };

    // =====================================================
    // 7) Accordéon (cards) + edit/delete via event delegation
    // =====================================================
    const closeCard = (card) => {
        const gache = qs('.card-gache', card);
        const body = qs('.card-body', card);
        card.classList.remove('is-open');
        card.classList.add('is-collapsed');
        gache?.setAttribute('aria-expanded', 'false');
        if (body) body.hidden = true;
    };

    const openCard = (card) => {
        const gache = qs('.card-gache', card);
        const body = qs('.card-body', card);
        card.classList.add('is-open');
        card.classList.remove('is-collapsed');
        gache?.setAttribute('aria-expanded', 'true');
        if (body) body.hidden = false;
    };

    const closeAllCards = () => qsa('.project-card.is-open').forEach(closeCard);

    adminRight.addEventListener('click', async (e) => {
        // Toggle accordéon
        const gacheBtn = e.target.closest('.project-card .card-gache');
        if (gacheBtn) {
            const card = gacheBtn.closest('.project-card');
            const isOpen = card.classList.contains('is-open');
            closeAllCards();
            if (!isOpen) openCard(card);
            return;
        }

        // Supprimer
        const delBtn = e.target.closest('.project-card .delete-btn');
        if (delBtn) {
            e.stopPropagation();
            if (!confirm('Supprimer ce projet ?')) return;
            return handle(api('delete_project', { id: delBtn.dataset.id }), 'Projet supprimé');
        }

        // Éditer
        const editBtn = e.target.closest('.project-card .edit-btn');
        if (editBtn) {
            e.stopPropagation();
            const id = editBtn.dataset.id;

            try {
                const data = await api('get_project', { id });
                if (!data?.success) throw new Error(data?.message || 'Inconnue');

                setMode('edit', data.project);

                const card = qs(`.project-card[data-project-id="${CSS.escape(String(id))}"]`);
                if (card) {
                    closeAllCards();
                    openCard(card);
                }

                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (err) {
                console.error(err);
                alert('Erreur : ' + (err?.message || 'Serveur'));
            }
        }
    });

    // =====================================================
    // 8) Submit / Cancel
    // =====================================================
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // validation simple
        if (!Categories.hasOne()) {
            alert('Choisis au moins une catégorie.');
            return;
        }

        // TinyMCE -> copie le contenu HTML dans les <textarea>
        Tiny.triggerSave();

        // FormData direct (le plus fiable)
        const fd = new FormData(form);

        handle(
            fetch('db/project.php', { method: 'POST', body: fd }).then((r) => r.json()),
            actionInput?.value === 'update_project' ? 'Projet modifié' : 'Projet créé'
        );
    });

    cancelBtn?.addEventListener('click', () => setMode('create'));

    // =====================================================
    // 9) Init
    // =====================================================
    setMode('create');
});