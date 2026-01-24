// ============================================
// src/components/Sales/TransactionSuggestionModal.jsx
// Modal de suggestion d'annulation de transaction
// ============================================

import { CheckCircle, ArrowRight } from 'lucide-react';
import styles from '../../styles/Sales/TransactionSuggestionModal.module.css';

const TransactionSuggestionModal = ({ isOpen, onClose, onGoToTransaction, transactionRef }) => {
  if (!isOpen) return null;

  return (
    <div className={styles.overlay} onClick={onClose}>
      <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
        <div className={styles.icon}>
          <CheckCircle size={48} />
        </div>

        <h2 className={styles.title}>Vente annulée avec succès</h2>

        <p className={styles.message}>
          La vente a été annulée. Voulez-vous également annuler la transaction financière correspondante ?
        </p>

        <div className={styles.transactionInfo}>
          <span className={styles.label}>Transaction</span>
          <span className={styles.value}>{transactionRef}</span>
        </div>

        <div className={styles.actions}>
          <button 
            className={styles.skipBtn} 
            onClick={onClose}
          >
            Non, rester ici
          </button>
          <button 
            className={styles.goBtn} 
            onClick={onGoToTransaction}
          >
            Aller à la transaction
            <ArrowRight size={18} />
          </button>
        </div>
      </div>
    </div>
  );
};

export default TransactionSuggestionModal;