document.addEventListener('DOMContentLoaded', function() {
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const conversationId = this.querySelector('input[name="conversation_id"]').value;
            const message = this.querySelector('textarea[name="message"]').value;
            
            const context = {
                service: 'Support',
                action: 'reply',
                data: {
                    conversation_id: conversationId,
                    message: message,
                    is_admin: true
                }
            };

            try {
                const response = await postData(context);
                if (response.status === 'success') {
                    window.location.reload();
                } else {
                    alert('Erreur lors de l\'envoi de la réponse: ' + response.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'envoi de la réponse.');
            }
        });
    }
});