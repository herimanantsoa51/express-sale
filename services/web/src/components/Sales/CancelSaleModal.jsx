// ============================================
// src/components/Sales/CancelSaleModal.jsx
// Modal de confirmation d'annulation de vente
// ============================================

import { X, AlertTriangle } from 'lucide-react';
import styles from '../../styles/Sales/CancelSaleModal.module.css';

const CancelSaleModal = ({ isOpen, onClose, onConfirm, saleNumber, isLoading }) => {
  if (!isOpen) return null;

  return (
    <div className={styles.overlay} onClick={onClose}>
      <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
        <button className={styles.closeBtn} onClick={onClose}>
          <X size={20} />
        </button>

        <div className={styles.icon}>
          <AlertTriangle size={48} />
        </div>

        <h2 className={styles.title}>Annuler cette vente ?</h2>

        <p className={styles.message}>
          Vous êtes sur le point d'annuler la vente <strong>{saleNumber}</strong>.
        </p>

        <p className={styles.warning}>
          Cette action est <strong>irréversible</strong> et affectera :
        </p>

        <ul className={styles.list}>
          <li>Le statut de la vente passera à "Annulée"</li>
          <li>Les stocks des produits seront restaurés automatiquement</li>
          <li>La transaction financière devra être annulée séparément</li>
        </ul>

        <div className={styles.actions}>
          <button 
            className={styles.cancelBtn} 
            onClick={onClose}
            disabled={isLoading}
          >
            Non, garder la vente
          </button>
          <button 
            className={styles.confirmBtn} 
            onClick={onConfirm}
            disabled={isLoading}
          >
            {isLoading ? 'Annulation...' : 'Oui, annuler la vente'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default CancelSaleModal;