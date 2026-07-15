document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.querySelector('#create-project-form');
    
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(createForm);
            
            fetch('scripts/create_project.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Projet créé avec succès');
                    window.location.reload(); // Recharger la page pour afficher le projet
                } else {
                    alert('Erreur lors de la création du projet');
                }
            })
            .catch(error => alert('Erreur AJAX : ' + error));
        });
    }
});
