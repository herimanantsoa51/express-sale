// components/TransactionCard.jsx
import React from 'react';
import '../../styles/components/TransactionCard.css';

const TransactionCard = ({ transaction }) => {
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
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getAccountTypeIcon = (type) => {
    if (type === 'Espèces' || type === 'cash') {
      return (
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path 
            d="M8 10.5C9.65685 10.5 11 9.15685 11 7.5C11 5.84315 9.65685 4.5 8 4.5C6.34315 4.5 5 5.84315 5 7.5C5 9.15685 6.34315 10.5 8 10.5Z" 
            stroke="currentColor" 
            strokeWidth="1.5"
          />
          <path 
            d="M2 5.5H14V11.5H2V5.5Z" 
            stroke="currentColor" 
            strokeWidth="1.5"
          />
        </svg>
      );
    }
    return (
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
        <path 
          d="M2.5 7H13.5M10.5 10H11.5M6.5 10H8.5M3 13H13C13.5523 13 14 12.5523 14 12V4C14 3.44772 13.5523 3 13 3H3C2.44772 3 2 3.44772 2 4V12C2 12.5523 2.44772 13 3 13Z" 
          stroke="currentColor" 
          strokeWidth="1.5" 
          strokeLinecap="round"
        />
      </svg>
    );
  };

  return (
    <div className="transaction-card">
      <div className="transaction-icon">
        {getAccountTypeIcon(transaction.account?.type)}
      </div>
      
      <div className="transaction-details">
        <div className="transaction-account">
          {transaction.account?.name}
        </div>
        <div className="transaction-meta">
          <span className="transaction-date">{formatDate(transaction.transaction_date)}</span>
          {transaction.created_by && (
            <>
              <span className="transaction-separator">•</span>
              <span className="transaction-user">{transaction.created_by.name}</span>
            </>
          )}
        </div>
        {transaction.notes && (
          <div className="transaction-notes">{transaction.notes}</div>
        )}
      </div>
      
      <div className="transaction-amount">
        {formatAmount(transaction.amount)}
      </div>
    </div>
  );
};

export default TransactionCard;

/* ============================================
   styles/components/TransactionCard.css
   ============================================ */

/*
.transaction-card {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-md);
  padding: var(--spacing-md);
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  transition: all var(--transition-speed) var(--transition-timing);
}

.transaction-card:hover {
  background: var(--bg-tertiary);
}

.transaction-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: var(--bg-primary);
  border-radius: var(--border-radius);
  color: var(--text-secondary);
  flex-shrink: 0;
}

.transaction-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.transaction-account {
  font-size: var(--font-size-md);
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
}

.transaction-meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--font-size-xs);
  color: var(--text-tertiary);
}

.transaction-separator {
  color: var(--text-tertiary);
}

.transaction-date {
  font-weight: var(--font-weight-medium);
}

.transaction-user {
  font-style: italic;
}

.transaction-notes {
  margin-top: 2px;
  padding: 6px 10px;
  background: var(--bg-primary);
  border-radius: 6px;
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  font-style: italic;
}

.transaction-amount {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--success);
  white-space: nowrap;
}

@media (max-width: 768px) {
  .transaction-card {
    flex-direction: column;
  }
  
  .transaction-amount {
    align-self: flex-start;
  }
}
*/