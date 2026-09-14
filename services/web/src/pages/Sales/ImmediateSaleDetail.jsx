// ============================================
// src/pages/Sales/ImmediateSaleDetail.jsx
// Page détail d'une vente immédiate (MISE À JOUR)
// ============================================

import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import immediateSaleService from '../../services/immediateSaleService';
import SaleHeader from '../../components/Sales/SaleHeader';
import CustomerSection from '../../components/Sales/CustomerSection';
import SellerSection from '../../components/Sales/SellerSection';
import TransactionCard from '../../components/Sales/TransactionCard';
import SaleItemCard from '../../components/Sales/SaleItemCard';
import InvoiceActions from '../../components/Sales/InvoiceActions';
import CancelSaleModal from '../../components/Sales/CancelSaleModal';
import TransactionSuggestionModal from '../../components/Sales/TransactionSuggestionModal';
import styles from '../../styles/Sales/ImmediateSaleDetail.module.css';
import {toast} from 'react-toastify'

const ImmediateSaleDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [sale, setSale] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [showTransactionModal, setShowTransactionModal] = useState(false);
  const [isCancelling, setIsCancelling] = useState(false);

  useEffect(() => {
    const fetchSaleDetail = async () => {
      try {
        setLoading(true);
        const response = await immediateSaleService.getDetail(id);
        setSale(response.data);
      } catch (err) {
        setError(err.message || 'Erreur lors du chargement des détails');
      } finally {
        setLoading(false);
      }
    };

    fetchSaleDetail();
  }, [id]);

  const handleCancelClick = () => {
    setShowCancelModal(true);
  };

  const handleConfirmCancel = async () => {
    setIsCancelling(true);
    try {
      await immediateSaleService.cancelImmediateSale(id);
      
      // Rafraîchir les données
      const response = await immediateSaleService.getDetail(id);
      setSale(response.data);

      // Fermer le modal de confirmation
      setShowCancelModal(false);
      
      // Ouvrir le modal de suggestion de transaction
      setShowTransactionModal(true);
      toast.success('Annulée avec succès');
    } catch (err) {
      toast.error('Erreur');
      console.error(err);
    } finally {
      setIsCancelling(false);
    }
  };

  const handleGoToTransaction = () => {
    navigate(`/transactions/${sale.transaction.id}`);
  };

  if (loading) {
    return (
      <div className={styles.loading}>
        <div className={styles.spinner} />
        <p>Chargement des détails...</p>
      </div>
    );
  }

  if (error || !sale) {
    return (
      <div className={styles.error}>
        <p>{error || 'Vente introuvable'}</p>
        <button onClick={() => navigate('/ventes/immediates')} className={styles.backButton}>
          Retour à la liste
        </button>
      </div>
    );
  }

  return (
    <div className={styles.container}>
      {/* Bouton retour */}
      <button onClick={() => navigate('/ventes/immediates')} className={styles.backBtn}>
        <ArrowLeft size={18} />
        <span>Retour</span>
      </button>

      {/* En-tête */}
      <SaleHeader sale={sale} />

      {/* Grille principale */}
      <div className={styles.grid}>
        {/* Colonne gauche */}
        <div className={styles.leftColumn}>
          <CustomerSection customer={sale.customer} />
          <SellerSection user={sale.user} />
        </div>

        {/* Colonne droite */}
        <div className={styles.rightColumn}>
          <TransactionCard transaction={sale.transaction} />
          
          <InvoiceActions 
            saleId={sale.id}
            saleNumber={sale.sale_number}
            hasCustomerEmail={!!sale.customer?.email}
          />

          {/* Bouton d'annulation */}
          {sale.status === 'CONFIRMED' && (
            <button 
              onClick={handleCancelClick}
              className={styles.cancelButton}
            >
              Annuler la vente
            </button>
          )}
        </div>
      </div>

      {/* Section produits */}
      <div className={styles.itemsSection}>
        <h2 className={styles.sectionTitle}>Articles vendus</h2>
        <div className={styles.itemsGrid}>
          {sale.items.map((item) => (
            <SaleItemCard key={item.id} item={item} />
          ))}
        </div>
      </div>

      {/* Modal de confirmation d'annulation */}
      <CancelSaleModal
        isOpen={showCancelModal}
        onClose={() => setShowCancelModal(false)}
        onConfirm={handleConfirmCancel}
        saleNumber={sale.sale_number}
        isLoading={isCancelling}
      />

      {/* Modal de suggestion de transaction */}
      <TransactionSuggestionModal
        isOpen={showTransactionModal}
        onClose={() => setShowTransactionModal(false)}
        onGoToTransaction={handleGoToTransaction}
        transactionRef={sale.transaction.reference_number}
      />
    </div>
  );
};

export default ImmediateSaleDetail;