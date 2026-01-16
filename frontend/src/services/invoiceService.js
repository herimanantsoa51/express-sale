// ============================================
// src/services/invoiceService.js
// Service dédié à la gestion des factures PDF
// ============================================

import api from './api';

const invoiceService = {
  /**
   * Télécharge la facture PDF d'une vente
   * @param {number} saleId - ID de la vente
   * @returns {Promise<void>}
   */
  download: async (saleId) => {
    try {
      const response = await api.get(`/invoices/${saleId}/download`, {
        responseType: 'blob', // Important pour les fichiers PDF
      });

      // Créer un lien de téléchargement
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `facture-${saleId}.pdf`);
      document.body.appendChild(link);
      link.click();
      
      // Nettoyer
      link.parentNode.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Erreur téléchargement facture:', error);
      throw new Error('Impossible de télécharger la facture');
    }
  },

  /**
   * Ouvre la facture PDF dans un nouvel onglet
   * @param {number} saleId - ID de la vente
   * @returns {void}
   */
  show: (saleId) => {
    const token = localStorage.getItem('token');
    const url = `${import.meta.env.VITE_API_URL}/invoices/${saleId}/show`;
    
    // Ouvrir dans un nouvel onglet avec l'authentification
    window.open(
      `${url}?token=${token}`,
      '_blank',
      'noopener,noreferrer'
    );
  },

  /**
   * Envoie la facture par email au client
   * @param {number} saleId - ID de la vente
   * @returns {Promise<Object>}
   */
  sendByEmail: async (saleId) => {
    const response = await api.post(`/invoices/${saleId}/email`);
    return response.data;
  }
};

export default invoiceService;