document.addEventListener('DOMContentLoaded', () => {
  const container = document.querySelector('.success-page');
  for (let i = 0; i < 50; i++) {
    const c = document.createElement('div');
    c.classList.add('confetti');
    c.style.left = Math.random() * 100 + 'vw';
    c.style.animationDuration = (Math.random() * 3 + 2) + 's';
    c.style.animationDelay = Math.random() * 5 + 's';
    container.appendChild(c);
  }

  
  // Fonctions pour les actions post-commande
  async function updatePaymentStatus(orderId) {
    try {
      const context = {
        service: 'Payment',
        action: 'updatePaymentStatus',
        data: {
          order_id: orderId,
          status: 'Completed'
        }
      };
      return await postData(context);
    } catch (err) {
      console.error("Erreur de mise à jour du paiement :", err);
    }
  }
  
  async function confirmOrder(orderId, userEmail) {
    try {
      const context = {
        service: 'Notification',
        action: 'sendEmail',
        data: {
          email_data: {
            type: 'order_confirmation',
            to: userEmail,
            subject: 'confirmation de commande',
            order_id: orderId,
            base_url: baseUrl,
          },
        }
      };
      return await postData(context);
    } catch (err) {
      console.error("Erreur d'envoi de confirmation :", err);
    }
  }

  // Exécuter les actions nécessaires selon le type de page de succès
  const type = document.querySelector('.success-page').dataset.type;
  const orderId = document.querySelector('.success-page').dataset.orderId;
  const userEmail = document.querySelector('.success-page').dataset.userEmail;

 
});