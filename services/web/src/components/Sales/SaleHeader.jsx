// ============================================
// src/components/Sales/SaleHeader.jsx
// En-tête de vente avec statut
// ============================================

import { Calendar, Hash } from 'lucide-react';
import styles from '../../styles/Sales/SaleHeader.module.css';

const SaleHeader = ({ sale }) => {
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  };

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  };

  const getStatusInfo = (status) => {
    if (status === 'CANCELLED') {
      return {
        label: 'Annulée',
        className: styles.statusCancelled
      };
    }
    return {
      label: 'Confirmée',
      className: styles.statusConfirmed
    };
  };

  const statusInfo = getStatusInfo(sale.status);

  return (
    <div className={styles.container}>
      <div className={styles.left}>
        <h1 className={styles.title}>Vente immédiate</h1>
        
        <div className={styles.meta}>
          <div className={styles.metaItem}>
            <Hash size={16} />
            <span className={styles.number}>{sale.sale_number}</span>
          </div>
          
          <div className={styles.metaItem}>
            <Calendar size={16} />
            <span>{formatDate(sale.sale_date)}</span>
          </div>
        </div>
      </div>

      <div className={styles.right}>
        <div className={`${styles.status} ${statusInfo.className}`}>
          {statusInfo.label}
        </div>
        
        <div className={styles.amount}>
          <span className={styles.amountLabel}>Montant total</span>
          <span className={styles.amountValue}>
            {formatAmount(sale.total_amount)} Ar
          </span>
        </div>
      </div>
    </div>
  );
};

export default SaleHeader;