// ============================================
// src/components/Sales/SellerSection.jsx
// Section vendeur
// ============================================

import { UserCheck } from 'lucide-react';
import styles from '../../styles/Sales/SellerSection.module.css';

const SellerSection = ({ user }) => {
  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <div className={styles.icon}>
          <UserCheck size={18} />
        </div>
        <h3>Vendeur</h3>
      </div>

      <div className={styles.content}>
        <div className={styles.row}>
          <span className={styles.label}>Nom</span>
          <span className={styles.value}>{user.name}</span>
        </div>

        <div className={styles.row}>
          <span className={styles.label}>ID</span>
          <span className={`${styles.value} ${styles.mono}`}>
            #{user.id}
          </span>
        </div>
      </div>
    </div>
  );
};

export default SellerSection;