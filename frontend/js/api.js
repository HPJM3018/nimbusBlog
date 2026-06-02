import { API_URL } from './config.js';
import { getAdminToken } from './utils.js';

// Récupérer tous les articles (avec pagination)
export async function fetchArticles(page = 1, perPage = 10) {
    try {
        const response = await fetch(`${API_URL}/articles?page=${page}&per_page=${perPage}`);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Erreur fetchArticles:', error);
        return { success: false, data: [], meta: { total: 0, current_page: 1, last_page: 1 } };
    }
}

// Récupérer un article par ID
export async function fetchArticleById(id) {
    try {
        const response = await fetch(`${API_URL}/articles/${id}`);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Erreur fetchArticleById:', error);
        return { success: false, data: null };
    }
}

// Créer un article (admin seulement)
export async function createArticle(article) {
    const token = getAdminToken();
    try {
        const response = await fetch(`${API_URL}/admin/articles`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': token ? `Bearer ${token}` : ''
            },
            body: JSON.stringify(article)
        });
        return await response.json();
    } catch (error) {
        console.error('Erreur createArticle:', error);
        return { success: false, message: 'Erreur réseau' };
    }
}

// Mettre à jour un article
export async function updateArticle(id, article) {
    const token = getAdminToken();
    try {
        const response = await fetch(`${API_URL}/admin/articles/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': token ? `Bearer ${token}` : ''
            },
            body: JSON.stringify(article)
        });
        return await response.json();
    } catch (error) {
        console.error('Erreur updateArticle:', error);
        return { success: false, message: 'Erreur réseau' };
    }
}

// Supprimer un article
export async function deleteArticle(id) {
    const token = getAdminToken();
    try {
        const response = await fetch(`${API_URL}/admin/articles/${id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': token ? `Bearer ${token}` : ''
            }
        });
        return await response.json();
    } catch (error) {
        console.error('Erreur deleteArticle:', error);
        return { success: false, message: 'Erreur réseau' };
    }
}