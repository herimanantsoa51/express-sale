// ============================================
// src/components/Sales/TransactionCard.jsx
// Carte transaction élégante
// ============================================

import { CreditCard, TrendingUp, TrendingDown } from 'lucide-react';
import styles from '../../styles/Sales/TransactionCard.module.css';

const TransactionCard = ({ transaction }) => {
  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  };

  const getAccountTypeLabel = (type) => {
    const types = {
      mobile_money: 'Mobile Money',
      bank: 'Compte Bancaire',
      cash: 'Espèces'
    };
    return types[type] || type;
  };

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <div className={styles.icon}>
          <CreditCard size={18} />
        </div>
        <h3>Transaction</h3>
      </div>

      <div className={styles.content}>
        <div className={styles.reference}>
          <span className={styles.refLabel}>Référence</span>
          <span className={styles.refValue}>{transaction.reference_number}</span>
        </div>

        <div className={styles.account}>
          <div className={styles.accountHeader}>
            <span className={styles.accountName}>{transaction.account.name}</span>
            <span className={styles.accountType}>
              {getAccountTypeLabel(transaction.account.type)}
            </span>
          </div>
          <span className={styles.accountNumber}>
            {transaction.account.account_number}
          </span>
        </div>

        <div className={styles.balances}>
          <div className={styles.balanceRow}>
            <div className={styles.balanceLabel}>
              <TrendingDown size={14} />
              <span>Avant</span>
            </div>
            <span className={styles.balanceValue}>
              {formatAmount(transaction.balance_before)} Ar
            </span>
          </div>

          <div className={styles.balanceRow}>
            <div className={styles.balanceLabel}>
              <TrendingUp size={14} />
              <span>Après</span>
            </div>
            <span className={`${styles.balanceValue} ${styles.success}`}>
              {formatAmount(transaction.balance_after)} Ar
            </span>
          </div>
        </div>

        <div className={styles.type}>
          {transaction.transaction_type.name}
        </div>
      </div>
    </div>
  );
};

export default TransactionCard;