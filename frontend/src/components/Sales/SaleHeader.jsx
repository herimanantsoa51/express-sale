// ============================================
// src/components/Sales/SaleHeader.jsx
// En-tête épuré de la vente
// ============================================

import { Calendar, Tag } from 'lucide-react';
import styles from '../../styles/Sales/SaleHeader.module.css';

const SaleHeader = ({ sale }) => {
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric',
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

  return (
    <div className={styles.container}>
      <div className={styles.top}>
        <div className={styles.number}>
          <Tag size={20} />
          <span>{sale.sale_number}</span>
        </div>
        <div className={styles.date}>
          <Calendar size={16} />
          <span>{formatDate(sale.sale_date)}</span>
        </div>
      </div>

      <div className={styles.amounts}>
        <div className={styles.amountRow}>
          <span className={styles.label}>Sous-total</span>
          <span className={styles.value}>{formatAmount(sale.subtotal)} Ar</span>
        </div>
        
        {sale.discount_amount > 0 && (
          <div className={styles.amountRow}>
            <span className={styles.label}>Remise</span>
            <span className={`${styles.value} ${styles.discount}`}>
              -{formatAmount(sale.discount_amount)} Ar
            </span>
          </div>
        )}
        
        <div className={`${styles.amountRow} ${styles.total}`}>
          <span className={styles.label}>Total</span>
          <span className={styles.value}>{formatAmount(sale.total_amount)} Ar</span>
        </div>
      </div>
    </div>
  );
};

export default SaleHeader;