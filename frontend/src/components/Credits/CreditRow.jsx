// components/CreditRow.jsx
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { ChevronRight } from 'lucide-react';
import StatusBadge from './StatusBadge';

const CreditRow = ({ credit }) => {
  const navigate = useNavigate();

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount) + ' Ar';
  };

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  };

  const getProgressColor = (percentage) => {
    if (percentage >= 80) return 'var(--success)';
    if (percentage >= 40) return 'var(--warning)';
    return 'var(--danger)';
  };

  return (
    <div 
      className="credit-row"
      onClick={() => navigate(`/ventes/credits/${credit.id}`)}
    >
      <div className="credit-cell" style={{ minWidth: '120px' }}>
        {/* <div className="credit-number"></div> */}
        {credit.sale_number}
      </div>
      
      <div className="credit-cell" style={{ minWidth: '180px' }}>
        <div className="customer-info">
          <div className="customer-name">{credit.customer?.name}</div>
          <div className="customer-id">{credit.customer?.customer_number}</div>
        </div>
      </div>
      
      <div className="credit-cell" style={{ minWidth: '130px' }}>
        <div className="credit-amount">{formatAmount(credit.total_amount)}</div>
      </div>
      
      <div className="credit-cell" style={{ minWidth: '130px' }}>
        <div className="credit-amount-due">{formatAmount(credit.amount_due)}</div>
      </div>
      
      <div className="credit-cell" style={{ minWidth: '120px' }}>
        <div className="due-date">
          <div>{formatDate(credit.due_date)}</div>
          {credit.is_overdue && (
            <span className="overdue-badge">En retard</span>
          )}
        </div>
      </div>
      
      <div className="credit-cell" style={{ minWidth: '100px' }}>
        <StatusBadge status={credit.status} />
      </div>
      
      <div className="credit-cell" style={{ minWidth: '140px' }}>
        <div className="progress-container">
          <div className="progress-bar">
            <div 
              className="progress-fill"
              style={{ 
                width: `${credit.payment_percentage}%`,
                backgroundColor: getProgressColor(credit.payment_percentage)
              }}
            />
          </div>
          <div className="progress-text">
            {credit.payment_percentage.toFixed(0)}%
          </div>
        </div>
      </div>
      
      <ChevronRight className="credit-chevron" size={18} />
    </div>
  );
};

export default CreditRow;