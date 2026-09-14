import React from 'react';
import './StatusBadge.css';

/**
 * Mapping des statuts vers leurs labels et couleurs
 */
const STATUS_CONFIG = {
  pending: {
    label: 'En attente',
    className: 'status-badge--warning'
  },
  confirmed: {
    label: 'Confirmé',
    className: 'status-badge--info'
  },
  partial_paid: {
    label: 'Part. payé',
    className: 'status-badge--purple'
  },
  completed: {
    label: 'Terminé',
    className: 'status-badge--success'
  },
  expired: {
    label: 'Expiré',
    className: 'status-badge--danger'
  },
  cancelled: {
    label: 'Annulé',
    className: 'status-badge--grey'
  }
};

/**
 * Badge de statut avec couleurs
 */
const StatusBadge = ({ status, withDot = false, className = '' }) => {
  const config = STATUS_CONFIG[status] || {
    label: status,
    className: 'status-badge--default'
  };

  return (
    <span className={`status-badge ${config.className} ${className}`}>
      {withDot && <span className="status-badge__dot"></span>}
      <span>{config.label}</span>
    </span>
  );
};

export default StatusBadge;
