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
import InvoiceActions from '../../components/Sales/InvoiceActions'; // 👈 NOUVEAU
import styles from '../../styles/Sales/ImmediateSaleDetail.module.css';

const ImmediateSaleDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [sale, setSale] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

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
          
          {/* 👇 NOUVEAU : Boutons de facture */}
          <InvoiceActions 
            saleId={sale.id}
            hasCustomerEmail={!!sale.customer?.email}
          />
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
    </div>
  );
};

export default ImmediateSaleDetail;