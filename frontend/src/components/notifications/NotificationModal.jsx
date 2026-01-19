// ============================================
// components/notifications/NotificationModal.jsx
// ============================================

import { useState, useEffect, useCallback, useRef } from 'react';
import { Bell, X, Check, CheckCheck, Trash2, Package, Calendar, CreditCard, AlertCircle, Info, Settings } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import notificationsService from '../../services/notificationsService';
import NotificationPreferences from './NotificationPreferences';
import '../../styles/NotificationModal.css';

const NotificationModal = ({ isOpen, onClose, onNavigate, onUpdate }) => {
  const [showPreferences, setShowPreferences] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(false);
  const [filter, setFilter] = useState('active_only');
  const audioContextRef = useRef(null);

  const playNotificationSound = useCallback(() => {
    if (!audioContextRef.current) {
      audioContextRef.current = new (window.AudioContext || window.webkitAudioContext)();
    }

    const ctx = audioContextRef.current;
    const oscillator = ctx.createOscillator();
    const gainNode = ctx.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(ctx.destination);

    oscillator.frequency.setValueAtTime(600, ctx.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(500, ctx.currentTime + 0.1);

    gainNode.gain.setValueAtTime(0.2, ctx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);

    oscillator.start(ctx.currentTime);
    oscillator.stop(ctx.currentTime + 0.2);
  }, []);

  const loadNotifications = useCallback(async () => {
    setLoading(true);
    try {
      const params = filter === 'all' ? {} : { [filter]: true };
      const data = await notificationsService.getNotifications(params);
      setNotifications(data.data || []);
    } catch (error) {
      console.error('Erreur chargement notifications:', error);
    } finally {
      setLoading(false);
    }
  }, [filter]);

  useEffect(() => {
    if (isOpen) {
      loadNotifications();
    }
  }, [isOpen, loadNotifications]);

  const handleMarkAsRead = async (notification) => {
    try {
      await notificationsService.markAsRead(notification.id);
      playNotificationSound();
      loadNotifications();
      if (onUpdate) onUpdate();
    } catch (error) {
      console.error('Erreur marquage lecture:', error);
    }
  };

  const handleMarkAllAsRead = async () => {
    try {
      await notificationsService.markAllAsRead();
      playNotificationSound();
      loadNotifications();
      if (onUpdate) onUpdate();
    } catch (error) {
      console.error('Erreur marquage tout lu:', error);
    }
  };

  const handleDismiss = async (notification) => {
    try {
      await notificationsService.dismiss(notification.id);
      loadNotifications();
      if (onUpdate) onUpdate();
    } catch (error) {
      console.error('Erreur suppression:', error);
    }
  };

  const handleNotificationClick = (notification) => {
    if (!notification.is_read) {
      handleMarkAsRead(notification);
    }

    const data = notification.data || {};
    let path = null;

    switch (notification.type) {
      case 'stock_low':
      case 'stock_out':
        path = `/produits/${data.product_id}`;
        break;
      case 'reservation_expiring':
        path = `/ventes/reservations/${data.reservation_id}`;
        break;
      case 'credit_due':
        path = `/ventes/credits/${data.credit_id}`;
        break;
    }

    if (path && onNavigate) {
      onNavigate(path);
      onClose();
    }
  };

  const getNotificationIcon = (type, severity) => {
    const iconProps = { size: 20 };
    
    switch (type) {
      case 'stock_low':
      case 'stock_out':
        return <Package {...iconProps} />;
      case 'reservation_expiring':
        return <Calendar {...iconProps} />;
      case 'credit_due':
        return <CreditCard {...iconProps} />;
      default:
        return severity === 'critical' ? <AlertCircle {...iconProps} /> : <Info {...iconProps} />;
    }
  };

  const getSeverityClass = (severity) => {
    switch (severity) {
      case 'critical': return 'notification-severity-critical';
      case 'warning': return 'notification-severity-warning';
      case 'info': return 'notification-severity-info';
      default: return 'notification-severity-info';
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'À l\'instant';
    if (diffMins < 60) return `Il y a ${diffMins} min`;
    if (diffHours < 24) return `Il y a ${diffHours}h`;
    if (diffDays < 7) return `Il y a ${diffDays}j`;
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
  };

  return (
    <AnimatePresence>
      {isOpen && (
        <>
          <motion.div
            className="notification-modal-backdrop"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
          />
          <motion.div
            className="notification-modal"
            initial={{ opacity: 0, scale: 0.95, y: -20 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.95, y: -20 }}
            transition={{ type: 'spring', damping: 25, stiffness: 300 }}
          >
            <div className="notification-modal-header">
              <h3 className="notification-modal-title">Notifications</h3>
              <div className="notification-modal-actions">
                <button
                  className="notification-action-btn"
                  onClick={() => setShowPreferences(true)}
                  title="Préférences"
                >
                  <Settings size={18} />
                </button>
                {notifications.some(n => !n.is_read) && (
                  <button
                    className="notification-action-btn"
                    onClick={handleMarkAllAsRead}
                    title="Tout marquer comme lu"
                  >
                    <CheckCheck size={18} />
                  </button>
                )}
                <button
                  className="notification-close-btn"
                  onClick={onClose}
                >
                  <X size={20} />
                </button>
              </div>
            </div>

            <div className="notification-modal-filters">
              <button
                className={`notification-filter-btn ${filter === 'active_only' ? 'active' : ''}`}
                onClick={() => setFilter('active_only')}
              >
                Actives
              </button>
              <button
                className={`notification-filter-btn ${filter === 'unread_only' ? 'active' : ''}`}
                onClick={() => setFilter('unread_only')}
              >
                Non lues
              </button>
              <button
                className={`notification-filter-btn ${filter === 'all' ? 'active' : ''}`}
                onClick={() => setFilter('all')}
              >
                Toutes
              </button>
            </div>

            <div className="notification-modal-body">
              {loading ? (
                <div className="notification-loading">
                  <div className="notification-spinner" />
                  <p>Chargement...</p>
                </div>
              ) : notifications.length === 0 ? (
                <div className="notification-empty">
                  <Bell size={48} />
                  <p>Aucune notification</p>
                </div>
              ) : (
                <motion.div layout className="notification-list">
                  <AnimatePresence mode="popLayout">
                    {notifications.map((notification) => (
                      <motion.div
                        key={notification.id}
                        layout
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, x: -100 }}
                        className={`notification-item ${!notification.is_read ? 'unread' : ''} ${getSeverityClass(notification.severity)}`}
                        onClick={() => handleNotificationClick(notification)}
                      >
                        <div className="notification-icon">
                          {getNotificationIcon(notification.type, notification.severity)}
                        </div>
                        <div className="notification-content">
                          <div className="notification-header-row">
                            <h4 className="notification-title">{notification.title}</h4>
                            <span className="notification-time">{formatDate(notification.created_at)}</span>
                          </div>
                          <p className="notification-message">{notification.message}</p>
                        </div>
                        <div className="notification-actions-row">
                          {!notification.is_read && (
                            <button
                              className="notification-read-btn"
                              onClick={(e) => {
                                e.stopPropagation();
                                handleMarkAsRead(notification);
                              }}
                              title="Marquer comme lu"
                            >
                              <Check size={16} />
                            </button>
                          )}
                          <button
                            className="notification-dismiss-btn"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleDismiss(notification);
                            }}
                            title="Supprimer"
                          >
                            <Trash2 size={16} />
                          </button>
                        </div>
                      </motion.div>
                    ))}
                  </AnimatePresence>
                </motion.div>
              )}
            </div>
          </motion.div>
        </>
      )}
      
      <NotificationPreferences
        isOpen={showPreferences}
        onClose={() => setShowPreferences(false)}
      />
    </AnimatePresence>
  );
};

export default NotificationModal;