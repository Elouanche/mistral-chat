document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('contactForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const message = document.getElementById('message').value.trim();
        const orderId = document.getElementById('order_id').value.trim();
        const website = document.getElementById('website').value.trim(); // anti-bot

        if (website !== '') return; // bot détecté

        const context = {
            service: 'Notification',
            action: 'sendEmail',
            data: {
                email_data: {
                    type: 'ticket_support',
                    to: 'jessypiquerel6@gmail.com',
                    subject: `Message de contact - ${name}`,
                    order_id: orderId || 'Aucun ID',
                    base_url: baseUrl,
                    message: `Nom: ${name}\nEmail: ${email}\n${orderId ? `ID de commande: ${orderId}\n` : ''}\n${message}`
                }
            }
        };

        try {
            const result = await postData(context);
            if (result.status === 'success') {
                alert("Message envoyé avec succès !");
                document.getElementById('contactForm').reset();
            } else {
                alert("Une erreur s'est produite : " + result.message);
            }
        } catch (error) {
            console.error("Erreur AJAX :", error);
            alert("Impossible d'envoyer le message.");
        }
    });
});
