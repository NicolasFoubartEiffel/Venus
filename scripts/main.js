document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('.project-card');
    console.log(cards);

    cards.forEach(card => {
        card.addEventListener('click', function (e) {
            // Évite de suivre un lien si on clique sur un <a>
            if (e.target.tagName.toLowerCase() === 'a') return;

            this.classList.toggle('open');
        });
    });
});


