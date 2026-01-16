// components/StatusBadge.jsx
import React from 'react';
import '../../styles/components/StatusBadge.css';

const STATUS_CONFIG = {
  active: { label: 'Actif', variant: 'info' },
  partial_paid: { label: 'Partiellement payé', variant: 'warning' },
  completed: { label: 'Terminé', variant: 'success' },
  overdue: { label: 'En retard', variant: 'danger' },
  defaulted: { label: 'Défaut', variant: 'danger' },
  recovered: { label: 'Récupéré', variant: 'success' },
  pending: { label: 'En attente', variant: 'info' },
  partial: { label: 'Partiel', variant: 'warning' },
  paid: { label: 'Payé', variant: 'success' }
};

const StatusBadge = ({ status }) => {
  const config = STATUS_CONFIG[status] || { label: status, variant: 'info' };
  
  return (
    <span className={`status-badge status-badge-${config.variant}`}>
      {config.label}
    </span>
  );
};

export default StatusBadge;

/* ============================================
   styles/components/StatusBadge.css
   ============================================ */

/*
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  letter-spacing: 0.01em;
  transition: all var(--transition-speed) var(--transition-timing);
}

.status-badge-success {
  background: var(--success-light);
  color: var(--success);
}

.status-badge-warning {
  background: var(--warning-light);
  color: var(--warning);
}

.status-badge-danger {
  background: var(--danger-light);
  color: var(--danger);
}

.status-badge-info {
  background: var(--info-light);
  color: var(--info);
}

.status-badge-primary {
  background: var(--primary-light);
  color: var(--primary);
}
*/