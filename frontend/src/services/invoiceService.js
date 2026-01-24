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
  downloadSale: async (saleId,saleNumber) => {
    try {
      const response = await api.get(`/invoices/${saleId}/download-sale`, {
        responseType: 'blob', // Important pour les fichiers PDF
      });

      // ✅ CORRECTION: Récupérer le nom depuis les headers HTTP
      const contentDisposition = response.headers['content-disposition'];
      let filename = `facture-${saleNumber}.pdf`; // Fallback par défaut
      
      if (contentDisposition) {
        // Extraire le nom du fichier depuis "attachment; filename="...""
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (filenameMatch && filenameMatch[1]) {
          filename = filenameMatch[1].replace(/['"]/g, '');
        }
      }

      // Créer un lien de téléchargement avec le bon nom
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename); // ✅ Utilise le nom du serveur
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
  downloadCredit: async (creditId,saleNumber) => {
    try {
      const response = await api.get(`/invoices/${creditId}/download-credit`, {
        responseType: 'blob', // Important pour les fichiers PDF
      });

      // ✅ CORRECTION: Récupérer le nom depuis les headers HTTP
      const contentDisposition = response.headers['content-disposition'];
      let filename = `facture-${saleNumber}.pdf`; // Fallback par défaut
      
      if (contentDisposition) {
        // Extraire le nom du fichier depuis "attachment; filename="...""
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (filenameMatch && filenameMatch[1]) {
          filename = filenameMatch[1].replace(/['"]/g, '');
        }
      }

      // Créer un lien de téléchargement avec le bon nom
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename); // ✅ Utilise le nom du serveur
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
  downloadTransactionInstallment: async (installmentId,factureNumber) => {
    try {
      const response = await api.get(`/invoices/${installmentId}/download-transaction-installment`, {
        responseType: 'blob', // Important pour les fichiers PDF
      });

      // ✅ CORRECTION: Récupérer le nom depuis les headers HTTP
      const contentDisposition = response.headers['content-disposition'];
      let filename = `Payment-${factureNumber}.pdf`; // Fallback par défaut
      
      if (contentDisposition) {
        // Extraire le nom du fichier depuis "attachment; filename="...""
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (filenameMatch && filenameMatch[1]) {
          filename = filenameMatch[1].replace(/['"]/g, '');
        }
      }

      // Créer un lien de téléchargement avec le bon nom
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename); // ✅ Utilise le nom du serveur
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

  downloadReservation: async (reservationId,reservationNumber) => {
    try {
      const response = await api.get(`/invoices/${reservationId}/download-reservation`, {
        responseType: 'blob', // Important pour les fichiers PDF
      });

      // ✅ CORRECTION: Récupérer le nom depuis les headers HTTP
      const contentDisposition = response.headers['content-disposition'];
      let filename = `Reservation-${reservationNumber}.pdf`; // Fallback par défaut
      
      if (contentDisposition) {
        // Extraire le nom du fichier depuis "attachment; filename="...""
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (filenameMatch && filenameMatch[1]) {
          filename = filenameMatch[1].replace(/['"]/g, '');
        }
      }

      // Créer un lien de téléchargement avec le bon nom
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename); // ✅ Utilise le nom du serveur
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
  downloadReservationReceipt: async (reservationId,reservationNumber) => {
    try {
      const response = await api.get(`/invoices/${reservationId}/download-reservation-receipt`, {
        responseType: 'blob', // Important pour les fichiers PDF
      });

      // ✅ CORRECTION: Récupérer le nom depuis les headers HTTP
      const contentDisposition = response.headers['content-disposition'];
      let filename = `Reservation-Receipt-${reservationNumber}.pdf`; // Fallback par défaut
      
      if (contentDisposition) {
        // Extraire le nom du fichier depuis "attachment; filename="...""
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (filenameMatch && filenameMatch[1]) {
          filename = filenameMatch[1].replace(/['"]/g, '');
        }
      }

      // Créer un lien de téléchargement avec le bon nom
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename); // ✅ Utilise le nom du serveur
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
  downloadCashCount: async (cashCountId,dateCount) => {
    try {
      const response = await api.get(`/invoices/${cashCountId}/download-cash-count`, {
        responseType: 'blob', // Important pour les fichiers PDF
      });

      // ✅ CORRECTION: Récupérer le nom depuis les headers HTTP
      const contentDisposition = response.headers['content-disposition'];
      let filename = `Cash-Count-${dateCount}.pdf`; // Fallback par défaut
      
      if (contentDisposition) {
        // Extraire le nom du fichier depuis "attachment; filename="...""
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (filenameMatch && filenameMatch[1]) {
          filename = filenameMatch[1].replace(/['"]/g, '');
        }
      }

      // Créer un lien de téléchargement avec le bon nom
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename); // ✅ Utilise le nom du serveur
      document.body.appendChild(link);
      link.click();
      
      // Nettoyer
      link.parentNode.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Erreur téléchargement facture:', error);
      throw new Error('Impossible de télécharger la facture');
    }
  }

  
};

export default invoiceService;