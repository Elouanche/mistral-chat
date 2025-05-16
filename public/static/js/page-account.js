
document.addEventListener('DOMContentLoaded', () => {

    const deleteModal = document.getElementById('deleteConfirmModal');
    const deleteAccountBtn = document.getElementById('deleteAccountBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    // Ouvre la modale
    deleteAccountBtn.addEventListener('click', () => {
        deleteModal.classList.add('active');
    });

    // Ferme la modale
    function closeModal() {
        deleteModal.classList.remove('active');
    }

    closeModalBtn.addEventListener('click', closeModal);
    cancelDeleteBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (e) => {
        if (e.target === deleteModal) closeModal();
    });

    // Déconnexion
    document.getElementById('logoutBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        const context = {
            service: 'Auth',
            action: 'Logout'
        };

        try {
            const response = await postData(context);
            if (response.status === 'success') {
                window.location.href = '/';
            } else {
                alert('Erreur lors de la déconnexion.');
            }
        } catch (error) {
            console.error('Erreur :', error);
            alert('Erreur de déconnexion.');
        }
    });

    // Suppression
    confirmDeleteBtn.addEventListener('click', async () => {
        const context = {
            service: 'User',
            action: 'delete',
            data: {
                user_id: userId
            }
        };

        try {
            const response = await postData(context);
            if (response.status === 'success') {
                window.location.href = '/';
            } else {
                alert('Échec de la suppression du compte.');
            }
        } catch (error) {
            console.error('Erreur :', error);
            alert('Erreur lors de la suppression.');
        }
    });
});

