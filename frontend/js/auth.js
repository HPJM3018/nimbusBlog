import { API_URL } from './config.js';

export async function login(email, password) {
    try {
        const response = await fetch(`${API_URL}/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            localStorage.setItem('adminToken', data.token);
            return { success: true, message: 'Login successful' };
        }
        
        return { success: false, message: data.message || 'Invalid credentials' };
    } catch (error) {
        console.error('Login error:', error);
        return { success: false, message: 'Connection error' };
    }
}

export function logout() {
    localStorage.removeItem('adminToken');
    window.location.href = '/login.html';
}

export function isAdmin() {
    return localStorage.getItem('adminToken') !== null;
}

export function requireAuth() {
    if (!isAdmin()) {
        window.location.href = '/login.html';
        return false;
    }
    return true;
}

export function redirectIfAuthenticated() {
    if (isAdmin()) {
        window.location.href = '/admin/dashboard.html';
        return true;
    }
    return false;
}

export function setupLogoutButton() {
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    }
}

export function updateHeaderAuth() {
    const authLink = document.getElementById('auth-link');
    const adminLink = document.getElementById('admin-link');
    
    if (isAdmin()) {
        if (authLink) {
            authLink.textContent = 'Logout';
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
            authLink.textContent = 'Login';
            authLink.href = '/login.html';
            authLink.onclick = null;
        }
        if (adminLink) {
            adminLink.style.display = 'none';
        }
    }
}