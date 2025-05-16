document.addEventListener('DOMContentLoaded', function() {
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const conversationId = this.querySelector('input[name="conversation_id"]').value;
            const message = this.querySelector('textarea[name="message"]').value;
            
            // Envoyer la réponse via AJAX
            fetch('/api/support/reply', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    message: message,
                    is_admin: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.status === 'success') {
                    // Recharger la page pour afficher le nouveau message
                    window.location.reload();
                } else {
                    alert('Erreur lors de l\'envoi de la réponse: ' + data.message);
            }})
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'envoi de la réponse.');
            });
        });
    }
});