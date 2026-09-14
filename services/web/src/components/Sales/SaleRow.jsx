// ============================================
// src/components/Sales/SaleRow.jsx
// Ligne de vente cliquable
// ============================================

import { useNavigate } from 'react-router-dom';
import { ChevronRight } from 'lucide-react';
import styles from '../../styles/Sales/SalesTable.module.css';

const SaleRow = ({ sale }) => {
  const navigate = useNavigate();

  // Formater la date
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  };

  // Formater le montant
  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  };

  const handleClick = () => {
    navigate(`/ventes/immediates/${sale.id}`);
  };

  return (
    <div className={styles.row} onClick={handleClick}>
      <div className={styles.cell} style={{ minWidth: '140px' }}>
        <span className={styles.saleNumber}>{sale.sale_number}</span>
      </div>

      <div className={styles.cell} style={{ minWidth: '160px' }}>
        <span className={styles.date}>{formatDate(sale.sale_date)}</span>
      </div>

      <div className={styles.cell} style={{ minWidth: '180px' }}>
        <div className={styles.customer}>
          <span className={styles.customerName}>{sale.customer?.name}</span>
          <span className={styles.customerNumber}>{sale.customer?.customer_number}</span>
        </div>
      </div>

      <div className={styles.cell} style={{ minWidth: '140px' }}>
        <span className={styles.amount}>{formatAmount(sale.total_amount)} Ar</span>
      </div>

      <div className={styles.cell} style={{ minWidth: '160px' }}>
        <span className={styles.reference}>{sale.transaction_reference_number}</span>
      </div>

      <div className={styles.chevron}>
        <ChevronRight size={18} />
      </div>
    </div>
  );
};

export default SaleRow;