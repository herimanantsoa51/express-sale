// ============================================
// src/components/Sales/SaleItemCard.jsx
// Carte article de vente
// ============================================

import { Package, Tag } from 'lucide-react';
import styles from '../../styles/Sales/SaleItemCard.module.css';

const SaleItemCard = ({ item }) => {
  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  };

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <div className={styles.icon}>
          <Package size={16} />
        </div>
        <h4 className={styles.productName}>{item.product.name}</h4>
      </div>

      {/* Variantes / Attributs */}
      {item.variant && item.variant.attributes && item.variant.attributes.length > 0 && (
        <div className={styles.attributes}>
          {item.variant.attributes.map((attr, index) => (
            <div key={index} className={styles.attribute}>
              <span className={styles.attrType}>{attr.attribute_type}</span>
              <span className={styles.attrValue}>{attr.attribute_value}</span>
            </div>
          ))}
        </div>
      )}

      {/* Détails prix */}
      <div className={styles.details}>
        <div className={styles.row}>
          <span className={styles.label}>Prix unitaire</span>
          <span className={styles.value}>{formatAmount(item.unit_price)} Ar</span>
        </div>

        <div className={styles.row}>
          <span className={styles.label}>Quantité</span>
          <span className={`${styles.value} ${styles.quantity}`}>× {item.quantity}</span>
        </div>

        <div className={`${styles.row} ${styles.total}`}>
          <span className={styles.label}>
            <Tag size={14} />
            Total
          </span>
          <span className={styles.value}>{formatAmount(item.line_total)} Ar</span>
        </div>
      </div>
    </div>
  );
};

export default SaleItemCard;