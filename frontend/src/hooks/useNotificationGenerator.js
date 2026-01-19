// ============================================
// hooks/useNotificationGenerator.js
// ============================================

import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import notificationsService from '../services/notificationsService';

/**
 * Hook pour générer automatiquement les notifications lors du changement de page
 * Uniquement pour les admins
 */
const useNotificationGenerator = () => {
  const location = useLocation();
  const { isAdmin } = useAuth();

  useEffect(() => {
    const generateNotifications = async () => {
      if (!isAdmin()) return;

      try {
        await notificationsService.generate();
        console.log('Notifications générées avec succès');
      } catch (error) {
        // Erreur silencieuse pour ne pas perturber l'UX
        console.error('Erreur génération notifications:', error);
      }
    };

    generateNotifications();
  }, [location.pathname, isAdmin]);
};

export default useNotificationGenerator;