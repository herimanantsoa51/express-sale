// ============================================
// src/components/Sales/SalesTable.jsx
// Tableau moderne des ventes
// ============================================

import SaleRow from './SaleRow';
import styles from '../../styles/Sales/SalesTable.module.css';

const SalesTable = ({ sales, loading }) => {
  if (!sales || sales.length === 0) {
    return (
      <div className={styles.empty}>
        <p>Aucune vente trouvée</p>
      </div>
    );
  }

  return (
    <div className={styles.tableWrapper}>
      <div className={styles.table}>
        {/* En-têtes */}
        <div className={styles.tableHeader}>
          <div className={styles.headerCell} style={{ minWidth: '140px' }}>
            Numéro
          </div>
          <div className={styles.headerCell} style={{ minWidth: '160px' }}>
            Date
          </div>
          <div className={styles.headerCell} style={{ minWidth: '180px' }}>
            Client
          </div>
          <div className={styles.headerCell} style={{ minWidth: '140px' }}>
            Montant
          </div>
          <div className={styles.headerCell} style={{ minWidth: '160px' }}>
            Référence
          </div>
        </div>

        {/* Corps du tableau */}
        <div className={`${styles.tableBody} ${loading ? styles.loading : ''}`}>
          {sales.map((sale) => (
            <SaleRow key={sale.id} sale={sale} />
          ))}
        </div>
      </div>
    </div>
  );
};

export default SalesTable;