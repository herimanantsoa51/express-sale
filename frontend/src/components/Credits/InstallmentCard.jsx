// components/InstallmentCard.jsx
import React, { useState } from 'react';
import StatusBadge from './StatusBadge';
import TransactionCard from './TransactionCard';
import { ChevronDown, ChevronUp } from 'lucide-react';
import '../../styles/components/InstallmentCard.css';

const InstallmentCard = ({ installment, onPay, isCreditCancelled }) => {
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

  const canPay = !isCreditCancelled && installment.status !== 'paid' && installment.remaining_amount > 0;
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

        {isCreditCancelled && installment.remaining_amount > 0 && (
          <div className="installment-cancelled-notice">
            <span>Crédit annulé - Paiements désactivés</span>
          </div>
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