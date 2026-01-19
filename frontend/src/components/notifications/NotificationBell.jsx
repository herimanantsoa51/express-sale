// ============================================
// components/notifications/NotificationBell.jsx
// ============================================

import { useState, useEffect, useCallback, useRef } from 'react';
import { Bell } from 'lucide-react';
import { motion } from 'framer-motion';
import { useNavigate } from 'react-router-dom';
import NotificationModal from './NotificationModal';
import notificationsService from '../../services/notificationsService';
import '../../styles/NotificationBell.css';

const NotificationBell = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [count, setCount] = useState(0);
  const [hasNew, setHasNew] = useState(false);
  const navigate = useNavigate();
  const audioContextRef = useRef(null);
  const previousCountRef = useRef(0);

  const playNotificationSound = useCallback(() => {
    if (!audioContextRef.current) {
      audioContextRef.current = new (window.AudioContext || window.webkitAudioContext)();
    }

    const ctx = audioContextRef.current;
    const oscillator = ctx.createOscillator();
    const gainNode = ctx.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(ctx.destination);

    oscillator.frequency.setValueAtTime(800, ctx.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(600, ctx.currentTime + 0.1);

    gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);

    oscillator.start(ctx.currentTime);
    oscillator.stop(ctx.currentTime + 0.3);
  }, []);

  const loadCount = useCallback(async () => {
    try {
      const data = await notificationsService.getCount();
      const newCount = data.active || 0;
      
      if (newCount > previousCountRef.current && previousCountRef.current !== 0) {
        setHasNew(true);
        playNotificationSound();
        setTimeout(() => setHasNew(false), 3000);
      }
      
      previousCountRef.current = newCount;
      setCount(newCount);
    } catch (error) {
      console.error('Erreur compteur notifications:', error);
    }
  }, [playNotificationSound]);

  useEffect(() => {
    loadCount();
    const interval = setInterval(loadCount, 30000); // Refresh toutes les 30 secondes
    return () => clearInterval(interval);
  }, [loadCount]);

  const handleNavigate = (path) => {
    navigate(path);
  };

  const handleOpenModal = () => {
    setIsOpen(true);
    setHasNew(false);
  };

  return (
    <>
      <button
        className={`notification-bell-btn ${hasNew ? 'notification-bell-pulse' : ''}`}
        onClick={handleOpenModal}
        title="Notifications"
      >
        <Bell size={20} />
        {count > 0 && (
          <motion.span
            className="notification-badge"
            initial={{ scale: 0 }}
            animate={{ scale: 1 }}
            transition={{ type: 'spring', stiffness: 500, damping: 15 }}
          >
            {count > 99 ? '99+' : count}
          </motion.span>
        )}
      </button>

      <NotificationModal
        isOpen={isOpen}
        onClose={() => setIsOpen(false)}
        onNavigate={handleNavigate}
        onUpdate={loadCount}
      />
    </>
  );
};

export default NotificationBell;