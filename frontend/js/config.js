// Configuration du frontend
const CONFIG = {
    // Développement local
    dev: {
        API_URL: 'http://localhost:8000/api'
    },
    // Production (sera mis à jour plus tard)
    prod: {
        API_URL: 'https://api.nimbus-blog.com/api'
    }
};

// Détection automatique de l'environnement
const IS_PRODUCTION = window.location.hostname !== 'localhost' && 
                      !window.location.hostname.includes('127.0.0.1');

const ENV = IS_PRODUCTION ? 'prod' : 'dev';

export const API_URL = CONFIG[ENV].API_URL;