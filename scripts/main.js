document.addEventListener('DOMContentLoaded', function () {


    document.querySelectorAll('.category-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            const catId = pill.dataset.catId;
            const container = document.getElementById(`projects-cat-${catId}`);

            // Si déjà affiché, toggle (fermeture)
            if (container.classList.contains('open')) {
                container.classList.remove('open');
                container.style.maxHeight = '0px';
                return;
            }

            // Sinon, on charge via AJAX
            fetch('get_projects.php?id=' + catId)
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                    container.classList.add('open');
                    container.style.maxHeight = "500px";
                });
        });
    });
});


document.body.addEventListener('click', function (e) {
    // Vérifie si l'élément cliqué est une carte de projet
    if (e.target.closest('.project-card')) {
        // Vérifie si l'élément cliqué est un lien, et l'ignore si c'est le cas
        if (e.target.tagName.toLowerCase() === 'a') return;

        const card = e.target.closest('.project-card');
        card.classList.toggle('open');
    }
});
