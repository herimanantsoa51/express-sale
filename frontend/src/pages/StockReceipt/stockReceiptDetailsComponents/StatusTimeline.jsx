import React from 'react';
import {
  CheckCircle2,
  XCircle,
  AlertTriangle,
  Clock,
  Ban
} from 'lucide-react';

const StatusTimeline = ({ currentStatus, expectedDate, actualDate, isDelayed }) => {
  const statuses = ['pending', 'sent', 'in_transit', 'arrived', 'rated', 'cost_allocated', 'validated'];
  const currentIndex = statuses.indexOf(currentStatus);
  const isCancelled = currentStatus === 'cancelled';

  const getStatusIcon = (status, isCompleted, isCurrent) => {
    if (isCancelled) return <Ban size={16} strokeWidth={2.5} />;
    if (isCompleted) return <CheckCircle2 size={16} strokeWidth={2.5} />;
    if (isCurrent) return <Clock size={16} strokeWidth={2.5} />;
    return <div className="srd-timeline-dot-empty" />;
  };

  const statusLabels = {
    pending: 'Créée',
    sent: 'Envoyée',
    in_transit: 'En transit',
    arrived: 'Arrivée',
    rated:'Évaluée',
    cost_allocated:'Coût répartit et alloué',
    validated: 'Validée',
    
  };

  return (
    <div className="srd-status-timeline">
      <div className="srd-timeline-track">
        {statuses.map((status, index) => {
          const isCompleted = !isCancelled && index <= currentIndex;
          const isCurrent = !isCancelled && index === currentIndex;

          return (
            <div 
              key={status} 
              className={`srd-timeline-step ${isCompleted ? 'completed' : ''} ${isCurrent ? 'current' : ''}`}
            >
              <div className="srd-timeline-dot">
                {getStatusIcon(status, isCompleted, isCurrent)}
              </div>
              <span className="srd-timeline-label">{statusLabels[status]}</span>
            </div>
          );
        })}
      </div>
      
      {(isCancelled || isDelayed) && (
        <div className="srd-timeline-info">
          {isCancelled && (
            <div className="srd-timeline-cancelled">
              <XCircle size={14} strokeWidth={2.5} />
              <span>Commande annulée</span>
            </div>
          )}
          {isDelayed && !isCancelled && (
            <div className="srd-timeline-delayed">
              <AlertTriangle size={14} strokeWidth={2.5} />
              <span>Livraison en retard</span>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default StatusTimeline;