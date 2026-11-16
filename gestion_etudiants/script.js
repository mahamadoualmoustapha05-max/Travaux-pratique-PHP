document.querySelectorAll('.delete-link').forEach((link) => {
    link.addEventListener('click', (event) => {
        if (!window.confirm('Confirmer la suppression de cet étudiant ?')) {
            event.preventDefault();
        }
    });
});

const searchInput = document.querySelector('#q');
if (searchInput) {
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLocaleLowerCase();
        document.querySelectorAll('#etudiants-table .etudiant-row').forEach((row) => {
            const text = `${row.dataset.nom || ''} ${row.dataset.prenom || ''}`.toLocaleLowerCase();
            row.hidden = !text.includes(query);
        });
    });
}
