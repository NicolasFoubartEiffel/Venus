document.addEventListener('DOMContentLoaded', () => {
    const createProjectForm = document.getElementById('create-project-form');
    const createCategoryForm = document.getElementById('create-category-form');

    if (createProjectForm) {
        createProjectForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create_projet');

            fetch('db/project.php', {
                method: 'POST',
                body: formData,
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Projet créé avec succès');
                        location.reload();
                    } else {
                        alert('Erreur : ' + (data.message || 'Inconnue'));
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Erreur serveur');
                });
        });
    }

    if (createCategoryForm) {
        createCategoryForm.addEventListener('submit', function (e) {
            const radioOui = document.querySelector('input[name="important"][value="1"]:checked');
            const importantCategory = JSON.parse(this.dataset.important || "[]");

            if (radioOui && importantCategory.length > 0) {
                const message = `La catégorie prioritaire actuelle (${importantCategory[0].name}) sera remplacée par cette nouvelle catégorie. Voulez-vous continuer ?`;
                if (!confirm(message)) {
                    e.preventDefault();
                    return;
                }
            }

            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create_category');

            fetch('db/project.php', {
                method: 'POST',
                body: formData,
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Catégorie créée');
                        location.reload();
                    } else {
                        alert('Erreur : ' + (data.message || 'Inconnue'));
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Erreur serveur');
                });
        });
    }
});
