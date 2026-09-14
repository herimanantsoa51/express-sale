// ============================================
// components/notifications/NotificationPreferences.jsx
// ============================================

import { useState, useEffect } from 'react';
import { Settings, Bell, BellOff, Save, X } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import notificationsService from '../../services/notificationsService';
import '../../styles/NotificationPreferences.css';

const NotificationPreferences = ({ isOpen, onClose }) => {
  const [preferences, setPreferences] = useState([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const typeLabels = {
    stock_low: 'Stock faible',
    stock_out: 'Stock épuisé',
    reservation_expiring: 'Réservations expirant',
    credit_due: 'Échéances de crédit',
    planned_expense_due: 'Dépenses planifiées',
  };
  

  const typeDescriptions = {
    stock_low: 'Alertes lorsque le stock est faible',
    stock_out: 'Alertes lorsque le stock est épuisé',
    reservation_expiring: 'Alertes pour les réservations à échéance',
    credit_due: 'Alertes pour les crédits à payer',
    planned_expense_due: 'Alertes pour les dépenses planifiées à payer',
  };
  

  useEffect(() => {
    if (isOpen) {
      loadPreferences();
    }
  }, [isOpen]);

  const loadPreferences = async () => {
    setLoading(true);
    try {
      const data = await notificationsService.getPreferences();
      setPreferences(data);
    } catch (error) {
      console.error('Erreur chargement préférences:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleToggle = (preferenceId) => {
    setPreferences(prevPrefs =>
      prevPrefs.map(pref =>
        pref.id === preferenceId
          ? { ...pref, enabled: !pref.enabled }
          : pref
      )
    );
  };

  const handleIntervalChange = (preferenceId, value) => {
    setPreferences(prevPrefs =>
      prevPrefs.map(pref =>
        pref.id === preferenceId
          ? { ...pref, reminder_interval_days: parseInt(value) || 1 }
          : pref
      )
    );
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      // Sauvegarder chaque préférence
      for (const pref of preferences) {
        await notificationsService.updatePreference(pref.id, {
          enabled: pref.enabled,
          reminder_interval_days: pref.reminder_interval_days
        });
      }
      
      setSuccessMessage('Préférences enregistrées avec succès !');
      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (error) {
      console.error('Erreur sauvegarde préférences:', error);
      alert('Erreur lors de la sauvegarde des préférences');
    } finally {
      setSaving(false);
    }
  };

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <motion.div
        className="notif-prefs-backdrop"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        onClick={onClose}
      />
      <motion.div
        className="notif-prefs-modal"
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 20 }}
        transition={{ type: 'spring', damping: 25, stiffness: 300 }}
      >
        <div className="notif-prefs-header">
          <div className="notif-prefs-header-content">
            <Settings size={24} />
            <h3 className="notif-prefs-title">Préférences de notifications</h3>
          </div>
          <button className="notif-prefs-close-btn" onClick={onClose}>
            <X size={20} />
          </button>
        </div>

        <div className="notif-prefs-body">
          {loading ? (
            <div className="notif-prefs-loading">
              <div className="notif-prefs-spinner" />
              <p>Chargement...</p>
            </div>
          ) : (
            <div className="notif-prefs-list">
              {preferences.map((pref) => (
                <div key={pref.id} className="notif-pref-item">
                  <div className="notif-pref-header">
                    <div className="notif-pref-info">
                      <div className="notif-pref-title-row">
                        {pref.enabled ? (
                          <Bell size={18} className="notif-pref-icon-enabled" />
                        ) : (
                          <BellOff size={18} className="notif-pref-icon-disabled" />
                        )}
                        <h4 className="notif-pref-title">
                          {typeLabels[pref.notification_type]}
                        </h4>
                      </div>
                      <p className="notif-pref-description">
                        {typeDescriptions[pref.notification_type]}
                      </p>
                    </div>
                    <label className="notif-pref-toggle">
                      <input
                        type="checkbox"
                        checked={pref.enabled}
                        onChange={() => handleToggle(pref.id)}
                      />
                      <span className="notif-pref-toggle-slider"></span>
                    </label>
                  </div>

                  {pref.enabled && (
                    <motion.div
                      className="notif-pref-interval"
                      initial={{ opacity: 0, height: 0 }}
                      animate={{ opacity: 1, height: 'auto' }}
                      exit={{ opacity: 0, height: 0 }}
                    >
                      <label className="notif-pref-interval-label">
                        Fréquence de rappel (jours)
                      </label>
                      <div className="notif-pref-interval-input-group">
                        <input
                          type="number"
                          min="1"
                          max="30"
                          value={pref.reminder_interval_days}
                          onChange={(e) => handleIntervalChange(pref.id, e.target.value)}
                          className="notif-pref-interval-input"
                        />
                        <span className="notif-pref-interval-unit">
                          jour{pref.reminder_interval_days > 1 ? 's' : ''}
                        </span>
                      </div>
                      <p className="notif-pref-interval-hint">
                        Une notification sera envoyée tous les {pref.reminder_interval_days} jour{pref.reminder_interval_days > 1 ? 's' : ''} pour ce type d'alerte
                      </p>
                    </motion.div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="notif-prefs-footer">
          {successMessage && (
            <motion.div
              className="notif-prefs-success"
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
            >
              {successMessage}
            </motion.div>
          )}
          <button
            className="notif-prefs-save-btn"
            onClick={handleSave}
            disabled={saving}
          >
            {saving ? (
              <>
                <div className="notif-prefs-btn-spinner" />
                Enregistrement...
              </>
            ) : (
              <>
                <Save size={18} />
                Enregistrer les préférences
              </>
            )}
          </button>
        </div>
      </motion.div>
    </AnimatePresence>
  );
};

export default NotificationPreferences;