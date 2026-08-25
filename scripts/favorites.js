const endpoint = 'db/favorites.php';

console.log('favorites.js chargé');

const setFavoriteState = (button, isActive) => {
    if (!button) return;

    button.classList.toggle('is-active', isActive);
    button.textContent = isActive ? '★' : '☆';
    button.title = isActive ? 'Retirer des favoris' : 'Ajouter aux favoris';
    button.setAttribute('aria-label', button.title);
};

const syncFavoriteButtons = (projectId, isActive) => {
    document
        .querySelectorAll(`.favorite-badge[data-project-id="${projectId}"]`)
        .forEach((button) => setFavoriteState(button, isActive));

    const modal = document.getElementById('project-modal');

    if (modal?.dataset.projectId === String(projectId)) {
        setFavoriteState(modal.querySelector('.modal-favorite'), isActive);
    }
};

document.addEventListener('click', async (e) => {
    const favorite = e.target.closest('.favorite-badge, .modal-favorite');

    if (!favorite) return;

    e.preventDefault();
    e.stopPropagation();

    const modal = document.getElementById('project-modal');

    const projectId =
        favorite.dataset.projectId ||
        favorite.closest('.project-tile')?.dataset.projectId ||
        modal?.dataset.projectId;

    if (!projectId || favorite.classList.contains('is-loading')) return;

    favorite.classList.add('is-loading');

    const formData = new FormData();
    formData.append('action', 'toggle_favorite');
    formData.append('project_id', projectId);

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const text = await response.text();
        console.log('RÉPONSE PHP:', text);

        const json = JSON.parse(text);

        if (!json.success) {
            throw new Error(json.message || 'Erreur favoris');
        }

        syncFavoriteButtons(projectId, Boolean(json.is_favorite));

    } catch (error) {
        console.error('ERREUR FAVORI:', error);
        alert(error.message || 'Erreur favoris');
    } finally {
        favorite.classList.remove('is-loading');
    }
}, true);