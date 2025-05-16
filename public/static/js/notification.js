// Fonction pour afficher une notification
function showNotification(message, type = 'success') {
    let notificationContainer = document.getElementById('notification-container');

    // Créer le conteneur si nécessaire
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.style.position = 'fixed';
        notificationContainer.style.bottom = '20px';
        notificationContainer.style.right = '20px';
        notificationContainer.style.zIndex = '1000';
        document.body.appendChild(notificationContainer);
    }

    // Créer la notification
    const notification = document.createElement('div');
    notification.classList.add('notification', type);
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="close-button" onclick="closeNotification(this.parentElement)">×</button>
    `;

    // Style de base
    notification.style.backgroundColor = getNotificationColor(type);
    notification.style.padding = '15px 20px';
    notification.style.marginTop = '10px';
    notification.style.borderRadius = '5px';
    notification.style.color = 'white';
    notification.style.fontWeight = 'bold';
    notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
    notification.style.opacity = '0';
    notification.style.transition = 'opacity 0.5s ease';

    // Ajouter au conteneur
    notificationContainer.appendChild(notification);

    // Animation d'entrée
    requestAnimationFrame(() => {
        notification.style.opacity = '1';
    });

    // Auto-fermeture
    setTimeout(() => {
        closeNotification(notification);
    }, 5000);
}

// Fonction utilitaire pour obtenir la couleur de fond selon le type
function getNotificationColor(type) {
    switch (type) {
        case 'success': return '#4CAF50';
        case 'error': return '#F44336';
        case 'warning': return '#FF9800';
        default: return '#2196F3'; // info
    }
}

// Fonction pour fermer la notification
function closeNotification(notification) {
    notification.style.opacity = '0';
    notification.addEventListener('transitionend', () => {
        notification.remove();
    });
}