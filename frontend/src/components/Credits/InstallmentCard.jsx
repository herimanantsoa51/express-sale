// components/InstallmentCard.jsx
import React, { useState } from 'react';
import StatusBadge from './StatusBadge';
import TransactionCard from './TransactionCard';
import { ChevronDown, ChevronUp } from 'lucide-react';
import '../../styles/components/InstallmentCard.css';

const InstallmentCard = ({ installment, onPay }) => {
  const [showTransactions, setShowTransactions] = useState(false);

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount) + ' Ar';
  };

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    });
  };

  const canPay = installment.status !== 'paid' && installment.remaining_amount > 0;
  const hasTransactions = installment.transactions && installment.transactions.length > 0;

  return (
    <div className={`installment-card ${installment.is_overdue ? 'overdue' : ''}`}>
      <div className="installment-header">
        <div className="installment-title">
          <span className="installment-number">Échéance #{installment.installment_number}</span>
          <StatusBadge status={installment.status} />
        </div>
        <div className="installment-date">{formatDate(installment.due_date)}</div>
      </div>

      <div className="installment-body">
        <div className="installment-info-grid">
          <div className="installment-info-item">
            <div className="info-label">Montant dû</div>
            <div className="info-value">{formatAmount(installment.amount_due)}</div>
          </div>
          
          <div className="installment-info-item">
            <div className="info-label">Payé</div>
            <div className="info-value paid">{formatAmount(installment.amount_paid)}</div>
          </div>
          
          <div className="installment-info-item">
            <div className="info-label">Reste</div>
            <div className="info-value remaining">{formatAmount(installment.remaining_amount)}</div>
          </div>
        </div>

        <div className="installment-progress">
          <div className="progress-bar-full">
            <div 
              className="progress-bar-filled"
              style={{ 
                width: `${installment.payment_percentage}%`,
                backgroundColor: installment.payment_percentage === 100 ? 'var(--success)' : 'var(--primary)'
              }}
            />
          </div>
          <div className="progress-label">
            {installment.payment_percentage.toFixed(0)}%
          </div>
        </div>

        {canPay && (
          <button 
            className="installment-pay-btn"
            onClick={() => onPay(installment)}
          >
            Effectuer un paiement
          </button>
        )}

        {hasTransactions && (
          <div className="installment-transactions-section">
            <button 
              className="transactions-toggle"
              onClick={() => setShowTransactions(!showTransactions)}
            >
              <span>Paiements ({installment.transactions.length})</span>
              {showTransactions ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
            </button>
            
            {showTransactions && (
              <div className="transactions-list">
                {installment.transactions.map((transaction) => (
                  <TransactionCard key={transaction.id} transaction={transaction} />
                ))}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default InstallmentCard;

/* ============================================
   styles/components/InstallmentCard.css
   ============================================ */

/*
.installment-card {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius-lg);
  overflow: hidden;
  transition: all var(--transition-speed) var(--transition-timing);
}

.installment-card.overdue {
  border-color: var(--danger);
  box-shadow: 0 0 0 1px var(--danger-light);
}

.installment-header {
  padding: var(--spacing-lg);
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-color);
}

.installment-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-xs);
}

.installment-number {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
}

.installment-subtitle {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.installment-body {
  padding: var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.installment-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: var(--spacing-md);
}

.installment-info-item {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.info-label {
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  color: var(--text-tertiary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-value {
  font-size: var(--font-size-md);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
}

.info-value.amount {
  color: var(--text-primary);
}

.info-value.paid {
  color: var(--success);
}

.info-value.remaining {
  color: var(--warning);
}

.installment-progress {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.progress-bar-full {
  height: 8px;
  background: var(--bg-tertiary);
  border-radius: 4px;
  overflow: hidden;
}

.progress-bar-filled {
  height: 100%;
  border-radius: 4px;
  transition: width 0.6s var(--transition-smooth);
}

.progress-label {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--text-secondary);
  text-align: right;
}

.installment-pay-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-sm);
  width: 100%;
  padding: 12px 20px;
  background: var(--primary);
  border: none;
  border-radius: var(--border-radius);
  color: white;
  font-size: var(--font-size-md);
  font-weight: var(--font-weight-semibold);
  cursor: pointer;
  transition: all var(--transition-speed) var(--transition-timing);
}

.installment-pay-btn:hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3);
}

.installment-pay-btn:active {
  transform: translateY(0);
}

.installment-transactions {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--border-color);
}

.transactions-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--text-secondary);
}

.transactions-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

@media (max-width: 768px) {
  .installment-info-grid {
    grid-template-columns: 1fr;
  }
}
*/