import { setAdminToken, logout, isAdmin } from './utils.js';

// Simulation d'authentification (à remplacer par Cognito plus tard)
// Pour le développement, on utilise un mot de passe simple
const ADMIN_EMAIL = 'admin@nimbus.com';
const ADMIN_PASSWORD = 'admin123';

// Connexion
export async function login(email, password) {
    // Simulation d'appel API
    if (email === ADMIN_EMAIL && password === ADMIN_PASSWORD) {
        // Créer un token factice
        const fakeToken = btoa(JSON.stringify({ email, role: 'admin', exp: Date.now() + 3600000 }));
        setAdminToken(fakeToken);
        return { success: true, message: 'Connexion réussie' };
    }
    
    return { success: false, message: 'Email ou mot de passe incorrect' };
}

// Vérifier si l'utilisateur est connecté et rediriger si nécessaire
export function requireAuth() {
    if (!isAdmin()) {
        window.location.href = '/login.html';
        return false;
    }
    return true;
}

// Rediriger vers le dashboard si déjà connecté
export function redirectIfAuthenticated() {
    if (isAdmin()) {
        window.location.href = '/admin/dashboard.html';
        return true;
    }
    return false;
}

// Configurer le bouton de déconnexion dans le header
export function setupLogoutButton() {
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    }
}

// Mettre à jour l'affichage du header selon l'authentification
export function updateHeaderAuth() {
    const authLink = document.getElementById('auth-link');
    const adminLink = document.getElementById('admin-link');
    
    if (isAdmin()) {
        if (authLink) {
            authLink.textContent = 'Déconnexion';
            authLink.href = '#';
            authLink.onclick = (e) => {
                e.preventDefault();
                logout();
            };
        }
        if (adminLink) {
            adminLink.style.display = 'inline-block';
        }
    } else {
        if (authLink) {
            authLink.textContent = 'Connexion';
            authLink.href = '/login.html';
            authLink.onclick = null;
        }
        if (adminLink) {
            adminLink.style.display = 'none';
        }
    }
}