// Fonctions utilitaires

// Formater une date
export function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}

// Tronquer un texte
export function truncateText(text, maxLength = 150) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// Afficher une notification
export function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        border-radius: 8px;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Charger un article depuis l'ID dans l'URL
export function getArticleIdFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

// Vérifier si l'utilisateur est admin
export function isAdmin() {
    return localStorage.getItem('adminToken') !== null;
}

// Sauvegarder le token admin
export function setAdminToken(token) {
    localStorage.setItem('adminToken', token);
}

// Récupérer le token admin
export function getAdminToken() {
    return localStorage.getItem('adminToken');
}

// Déconnecter l'admin
export function logout() {
    localStorage.removeItem('adminToken');
    window.location.href = '/login.html';
}

// Vérifier l'authentification et rediriger si non connecté
export function requireAuth() {
    const token = localStorage.getItem('adminToken');
    if (!token) {
        window.location.href = '/login.html';
        return false;
    }
    return true;
}