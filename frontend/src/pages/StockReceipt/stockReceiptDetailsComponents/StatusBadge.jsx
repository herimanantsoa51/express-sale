import React from 'react';
import {
  Clock,
  Send,
  Navigation,
  PackageCheck,
  BadgeCheck,
  Ban
} from 'lucide-react';

const StatusBadge = ({ status }) => {
  const statusConfig = {
    pending: { 
      label: 'En attente', 
      icon: Clock, 
      className: 'srd-status-pending'
    },
    sent: { 
      label: 'Envoyé', 
      icon: Send, 
      className: 'srd-status-sent'
    },
    in_transit: { 
      label: 'En transit', 
      icon: Navigation, 
      className: 'srd-status-transit'
    },
    arrived: { 
      label: 'Arrivé', 
      icon: PackageCheck, 
      className: 'srd-status-arrived'
    },
    validated: { 
      label: 'Validé', 
      icon: BadgeCheck, 
      className: 'srd-status-validated'
    },
    cancelled: { 
      label: 'Annulé', 
      icon: Ban, 
      className: 'srd-status-cancelled'
    }
  };

  const config = statusConfig[status] || statusConfig.pending;
  const Icon = config.icon;

  return (
    <span className={`srd-status-badge ${config.className}`}>
      <Icon size={14} strokeWidth={2.5} />
      {config.label}
    </span>
  );
};

export default StatusBadge;