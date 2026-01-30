// ============================================
// services/networkConfig.js - Configuration réseau
// ============================================

/**
 * Service pour gérer la configuration réseau de l'application
 */
const NetworkConfigService = {
    /**
     * Clés localStorage
     */
    KEYS: {
      API_URL: 'app_api_url',
      FRONTEND_URL: 'app_frontend_url',
    },
  
    /**
     * Obtenir l'URL de l'API configurée
     */
    getApiUrl() {
      const savedUrl = localStorage.getItem(this.KEYS.API_URL);
      return savedUrl || import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
    },
  
    /**
     * Définir l'URL de l'API
     */
    setApiUrl(url) {
      // Nettoyer l'URL (enlever trailing slash)
      const cleanUrl = url.replace(/\/$/, '');
      localStorage.setItem(this.KEYS.API_URL, cleanUrl);
      
      // Recharger la page pour appliquer les changements
      window.location.reload();
    },
  
    /**
     * Obtenir l'URL du frontend configurée
     */
    getFrontendUrl() {
      const savedUrl = localStorage.getItem(this.KEYS.FRONTEND_URL);
      if (savedUrl) return savedUrl;
      
      // Par défaut, utiliser l'URL actuelle
      return window.location.origin;
    },
  
    /**
     * Définir l'URL du frontend
     */
    setFrontendUrl(url) {
      const cleanUrl = url.replace(/\/$/, '');
      localStorage.setItem(this.KEYS.FRONTEND_URL, cleanUrl);
    },
  
    /**
     * Construire l'URL de l'API à partir d'une IP et d'un port
     */
    buildApiUrl(ip, port = '8000') {
      return `http://${ip}:${port}/api`;
    },
  
    /**
     * Construire l'URL du frontend à partir d'une IP et d'un port
     */
    buildFrontendUrl(ip, port = '3000') {
      return `http://${ip}:${port}`;
    },
  
    /**
     * Extraire IP et port d'une URL
     */
    parseUrl(url) {
      try {
        const urlObj = new URL(url);
        return {
          ip: urlObj.hostname,
          port: urlObj.port || (urlObj.protocol === 'https:' ? '443' : '80'),
        };
      } catch (error) {
        return { ip: '', port: '' };
      }
    },
  
    /**
     * Détecter automatiquement l'IP du serveur
     */
    async detectServerIp() {
      try {
        // Essayer d'obtenir l'IP depuis l'URL actuelle
        const currentUrl = window.location.hostname;
        if (currentUrl !== 'localhost' && currentUrl !== '127.0.0.1') {
          return currentUrl;
        }
        
        // Sinon retourner localhost
        return 'localhost';
      } catch (error) {
        console.error('Erreur détection IP:', error);
        return 'localhost';
      }
    },
  
    /**
     * Réinitialiser aux valeurs par défaut
     */
    reset() {
      localStorage.removeItem(this.KEYS.API_URL);
      localStorage.removeItem(this.KEYS.FRONTEND_URL);
      window.location.reload();
    },
  };
  
  export default NetworkConfigService;