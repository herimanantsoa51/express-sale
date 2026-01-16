// ============================================
// src/components/Sales/CustomerSection.jsx
// Section client compacte
// ============================================

import { User, Award } from 'lucide-react';
import styles from '../../styles/Sales/CustomerSection.module.css';

const CustomerSection = ({ customer }) => {
  const formatPoints = (points) => {
    return new Intl.NumberFormat('fr-FR').format(points);
  };

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <div className={styles.icon}>
          <User size={18} />
        </div>
        <h3>Client</h3>
      </div>

      <div className={styles.content}>
        <div className={styles.row}>
          <span className={styles.label}>Nom</span>
          <span className={styles.value}>{customer.name}</span>
        </div>

        <div className={styles.row}>
          <span className={styles.label}>Numéro</span>
          <span className={`${styles.value} ${styles.mono}`}>
            {customer.customer_number}
          </span>
        </div>

        <div className={styles.points}>
          <Award size={16} />
          <span>{formatPoints(customer.loyalty_points)} points fidélité</span>
        </div>
      </div>
    </div>
  );
};

export default CustomerSection;