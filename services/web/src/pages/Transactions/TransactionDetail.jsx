import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'motion/react';
import {
  ArrowLeft,
  ArrowRight,
  RefreshCw,
  AlertCircle,
  XCircle,
  RotateCcw,
  Wallet,
  ShoppingCart,
  Truck,
  Package,
  Tag,
  FileText,
  ChevronRight,
  AlertTriangle,
  User,
  Calendar,
  Hash
} from 'lucide-react';
import transactionService from '../../services/transactionService';
import '../../styles/TransactionDetail.css';

// === HELPER FUNCTIONS ===
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(Math.abs(amount));
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  const madagascarTime = new Date(date.getTime() + (3 * 60 * 60 * 1000));
  
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(madagascarTime);
};

const getCategoryConfig = (category) => {
  const config = {
    'income': { label: 'Revenu', className: 'income', prefix: '+' },
    'expense': { label: 'Dépense', className: 'expense', prefix: '-' },
    'transfer': { label: 'Transfert', className: 'transfer', prefix: '' },
    'adjustment': { label: 'Ajustement', className: 'adjustment', prefix: '' }
  };
  return config[category] || { label: 'Transaction', className: '', prefix: '' };
};

// === STATUS BANNER COMPONENT ===
const StatusBanner = ({ status, cancelledBy, reversesTransaction, navigate }) => {
  if (status.is_cancelled) {
    return (
      <motion.div
        className="td-status-banner cancelled"
        initial={{ opacity: 0, y: -10 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="td-status-banner-icon">
          <XCircle size={20} />
        </div>
        <div className="td-status-banner-content">
          <p className="td-status-banner-title">Transaction annulée</p>
          <p className="td-status-banner-text">
            {cancelledBy?.transaction_date 
              ? `Annulée le ${formatDateTime(cancelledBy.transaction_date)}`
              : 'Cette transaction a été annulée'}
          </p>
          {cancelledBy?.id && (
            <span 
              className="td-status-banner-link"
              onClick={() => navigate(`/transactions/${cancelledBy.id}`)}
            >
              Voir la transaction d'annulation
            </span>
          )}
        </div>
      </motion.div>
    );
  }

  if (status.is_reversal) {
    return (
      <motion.div
        className="td-status-banner reversal"
        initial={{ opacity: 0, y: -10 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="td-status-banner-icon">
          <RotateCcw size={20} />
        </div>
        <div className="td-status-banner-content">
          <p className="td-status-banner-title">Transaction d'annulation</p>
          <p className="td-status-banner-text">
            Cette transaction annule une autre transaction
          </p>
          {reversesTransaction?.id && (
            <span 
              className="td-status-banner-link"
              onClick={() => navigate(`/transactions/${reversesTransaction.id}`)}
            >
              Voir la transaction originale ({reversesTransaction.reference_number})
            </span>
          )}
        </div>
      </motion.div>
    );
  }

  return null;
};

// === RELATED CARD COMPONENT ===
const RelatedCard = ({ type, icon: Icon, iconClass, label, name, detail, onClick }) => (
  <motion.div
    className="td-related-card"
    onClick={onClick}
    whileHover={{ scale: 1.01 }}
    whileTap={{ scale: 0.99 }}
  >
    <div className="td-related-card-header">
      <div className={`td-related-card-icon ${iconClass}`}>
        <Icon size={18} />
      </div>
      <div className="td-related-card-title">
        <p className="td-related-card-type">{label}</p>
        <p className="td-related-card-name">{name}</p>
      </div>
      <ChevronRight size={16} className="td-related-card-chevron" />
    </div>
    {detail && <p className="td-related-card-detail">{detail}</p>}
  </motion.div>
);

// === CANCEL MODAL COMPONENT ===
const CancelModal = ({ transaction, isOpen, onClose, onConfirm, isLoading }) => {
  const [step, setStep] = useState(1);
  const [confirmText, setConfirmText] = useState('');
  const requiredText = 'ANNULER';

  const handleClose = () => {
    setStep(1);
    setConfirmText('');
    onClose();
  };

  const handleFirstConfirm = () => {
    setStep(2);
  };

  const handleFinalConfirm = () => {
    if (confirmText === requiredText) {
      onConfirm();
    }
  };

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <motion.div
        className="td-modal-overlay"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        onClick={handleClose}
      >
        <motion.div
          className="td-modal"
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0, scale: 0.95 }}
          onClick={(e) => e.stopPropagation()}
        >
          <div className="td-modal-header">
            <div className="td-modal-icon">
              {step === 1 ? <AlertTriangle size={28} /> : <XCircle size={28} />}
            </div>
            <h3 className="td-modal-title">
              {step === 1 ? 'Annuler cette transaction ?' : 'Confirmer l\'annulation'}
            </h3>
            <p className="td-modal-subtitle">
              {step === 1 
                ? 'Cette action créera une transaction inverse'
                : 'Cette action est irréversible'}
            </p>
          </div>

          <div className="td-modal-body">
            <div className="td-modal-info">
              <div className="td-modal-info-row">
                <span className="td-modal-info-label">Référence</span>
                <span className="td-modal-info-value">{transaction.reference_number}</span>
              </div>
              <div className="td-modal-info-row">
                <span className="td-modal-info-label">Montant</span>
                <span className="td-modal-info-value">{transaction.formatted_amount}</span>
              </div>
              <div className="td-modal-info-row">
                <span className="td-modal-info-label">Compte</span>
                <span className="td-modal-info-value">{transaction.account?.name}</span>
              </div>
            </div>

            {step === 1 && (
              <div className="td-modal-warning">
                <AlertTriangle size={18} className="td-modal-warning-icon" />
                <p className="td-modal-warning-text">
                  L'annulation créera une nouvelle transaction qui inversera les effets 
                  de cette transaction sur le solde du compte.
                </p>
              </div>
            )}

            {step === 2 && (
              <div className="td-modal-confirm-input">
                <label className="td-modal-confirm-label">
                  Tapez <strong>{requiredText}</strong> pour confirmer
                </label>
                <input
                  type="text"
                  className="td-modal-confirm-field"
                  value={confirmText}
                  onChange={(e) => setConfirmText(e.target.value.toUpperCase())}
                  placeholder={requiredText}
                  autoFocus
                />
              </div>
            )}
          </div>

          <div className="td-modal-actions">
            <button className="td-modal-btn td-modal-btn-cancel" onClick={handleClose}>
              Annuler
            </button>
            {step === 1 ? (
              <button className="td-modal-btn td-modal-btn-danger" onClick={handleFirstConfirm}>
                Continuer
              </button>
            ) : (
              <button 
                className="td-modal-btn td-modal-btn-danger"
                onClick={handleFinalConfirm}
                disabled={confirmText !== requiredText || isLoading}
              >
                {isLoading ? 'Annulation...' : 'Confirmer l\'annulation'}
              </button>
            )}
          </div>
        </motion.div>
      </motion.div>
    </AnimatePresence>
  );
};

// === MAIN COMPONENT ===
const TransactionDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [transaction, setTransaction] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [cancelling, setCancelling] = useState(false);

  useEffect(() => {
    const fetchTransaction = async () => {
      try {
        setLoading(true);
        setError(null);
        const response = await transactionService.getTransaction(id);
        setTransaction(response.data);
      } catch (err) {
        console.error('Erreur:', err);
        setError('Impossible de charger les détails de la transaction.');
      } finally {
        setLoading(false);
      }
    };

    if (id) fetchTransaction();
  }, [id]);

  const handleCancelTransaction = async () => {
    try {
      setCancelling(true);
      await transactionService.cancelTransaction(id);
      // Recharger la transaction pour voir le nouveau statut
      const response = await transactionService.getTransaction(id);
      setTransaction(response.data);
      setShowCancelModal(false);
    } catch (err) {
      console.error('Erreur annulation:', err);
      alert('Erreur lors de l\'annulation de la transaction');
    } finally {
      setCancelling(false);
    }
  };

  const getSaleLink = (saleContext) => {
    if (!saleContext) return null;
    switch (saleContext.type) {
      case 'immediate':
        return `/ventes/immediates/${saleContext.sale_id}`;
      case 'credit':
        return `/ventes/credits/${saleContext.credit_id}`;
      case 'reservation':
        return `/ventes/reservations/${saleContext.reservation_id}`;
      default:
        return null;
    }
  };

  const getSaleLabel = (saleContext) => {
    if (!saleContext) return '';
    switch (saleContext.type) {
      case 'immediate':
        return 'Vente immédiate';
      case 'credit':
        return 'Vente à crédit';
      case 'reservation':
        return 'Réservation';
      default:
        return 'Vente';
    }
  };

  // Loading state
  if (loading) {
    return (
      <div className="transaction-detail-page">
        <div className="td-loading">
          <RefreshCw size={32} className="td-loading-spinner" />
          <p className="td-loading-text">Chargement de la transaction...</p>
        </div>
      </div>
    );
  }

  // Error state
  if (error || !transaction) {
    return (
      <div className="transaction-detail-page">
        <div className="td-error">
          <div className="td-error-icon">
            <AlertCircle size={32} />
          </div>
          <h3 className="td-error-title">Transaction introuvable</h3>
          <p className="td-error-text">{error || 'Cette transaction n\'existe pas ou a été supprimée.'}</p>
          <button className="td-error-btn" onClick={() => navigate(-1)}>
            <ArrowLeft size={18} />
            Retour
          </button>
        </div>
      </div>
    );
  }

  const categoryConfig = getCategoryConfig(transaction.transaction_type?.category);
  const isCancelled = transaction.status?.is_cancelled;
  const isReversal = transaction.status?.is_reversal;
  const canCancel = !isCancelled && !isReversal;

  return (
    <div className="transaction-detail-page">
      {/* Back Button */}
      <motion.button
        className="td-back-button"
        onClick={() => navigate(-1)}
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        whileHover={{ x: -4 }}
      >
        <ArrowLeft size={18} />
        Retour
      </motion.button>

      {/* Status Banner */}
      <StatusBanner
        status={transaction.status}
        cancelledBy={transaction.cancelled_by_transaction}
        reversesTransaction={transaction.reverses_transaction}
        navigate={navigate}
      />

      {/* Main Card */}
      <motion.div
        className="td-main-card"
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        {/* Header */}
        <div className="td-header">
          <div className={`td-header-badge ${categoryConfig.className}`}>
            {transaction.transaction_type?.display_name || categoryConfig.label}
          </div>
          <p className={`td-amount ${categoryConfig.className} ${isCancelled ? 'cancelled' : ''}`}>
            {categoryConfig.prefix}{formatAmount(transaction.amount)}
            <span className="td-amount-currency">Ar</span>
          </p>
          <p className="td-reference">{transaction.reference_number}</p>
          <p className="td-date">{formatDateTime(transaction.transaction_date)}</p>
        </div>

        {/* Balance Change */}
        <div className="td-balance-change">
          <div className="td-balance-item">
            <p className="td-balance-label">Solde avant</p>
            <p className="td-balance-value">{formatAmount(transaction.balance_before)} Ar</p>
          </div>
          <ArrowRight size={20} className="td-balance-arrow" />
          <div className="td-balance-item">
            <p className="td-balance-label">Solde après</p>
            <p className="td-balance-value">{formatAmount(transaction.balance_after)} Ar</p>
          </div>
        </div>

        {/* Details Section */}
        <div className="td-details-section">
          <h3 className="td-section-title">Informations</h3>
          
          <div className="td-detail-row">
            <span className="td-detail-label">Compte</span>
            <span 
              className="td-detail-value link"
              onClick={() => navigate(`/comptes/${transaction.account?.id}`)}
            >
              {transaction.account?.name}
              <ChevronRight size={14} />
            </span>
          </div>

          <div className="td-detail-row">
            <span className="td-detail-label">Type de compte</span>
            <span className="td-detail-value">{transaction.account?.type}</span>
          </div>

          <div className="td-detail-row">
            <span className="td-detail-label">Catégorie</span>
            <span className="td-detail-value">{transaction.transaction_type?.category}</span>
          </div>

          {transaction.recipient_name && (
            <div className="td-detail-row">
              <span className="td-detail-label">Bénéficiaire</span>
              <span className="td-detail-value">{transaction.recipient_name}</span>
            </div>
          )}

          {transaction.description && (
            <div className="td-detail-row">
              <span className="td-detail-label">Description</span>
              <span className="td-detail-value">{transaction.description}</span>
            </div>
          )}
        </div>

        {/* Meta Info */}
        <div className="td-meta">
          {transaction.created_by && (
            <div className="td-meta-item">
              <User size={14} />
              <span>{transaction.created_by.name}</span>
            </div>
          )}
          <div className="td-meta-item">
            <Calendar size={14} />
            <span>{formatDateTime(transaction.created_at)}</span>
          </div>
          <div className="td-meta-item">
            <Hash size={14} />
            <span>ID: {transaction.id}</span>
          </div>
        </div>
      </motion.div>

      {/* Related Items */}
      {(transaction.related_account || transaction.supplier || transaction.freight_forwarder || 
        transaction.stock_receipt || transaction.expense_category || transaction.sale_context) && (
        <motion.div
          className="td-related-section"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
        >
          <div className="td-related-grid">
            {/* Related Account */}
            {transaction.related_account && (
              <RelatedCard
                icon={Wallet}
                iconClass="account"
                label="Compte associé"
                name={transaction.related_account.name}
                onClick={() => navigate(`/comptes/${transaction.related_account.id}`)}
              />
            )}

            {/* Supplier */}
            {transaction.supplier && (
              <RelatedCard
                icon={Package}
                iconClass="supplier"
                label="Fournisseur"
                name={transaction.supplier.name}
                onClick={() => navigate(`/fournisseurs/${transaction.supplier.id}`)}
              />
            )}

            {/* Freight Forwarder */}
            {transaction.freight_forwarder && (
              <RelatedCard
                icon={Truck}
                iconClass="forwarder"
                label="Transitaire"
                name={transaction.freight_forwarder.name}
                onClick={() => navigate(`/transitaires/${transaction.freight_forwarder.id}`)}
              />
            )}

            {/* Stock Receipt */}
            {transaction.stock_receipt && (
              <RelatedCard
                icon={Package}
                iconClass="stock"
                label="Réapprovisionnement"
                name={transaction.stock_receipt.receipt_number}
                detail={`Coût total: ${formatAmount(transaction.stock_receipt.total_cost)} Ar`}
                onClick={() => navigate(`/reapprovisionnements/${transaction.stock_receipt.id}`)}
              />
            )}

            {/* Expense Category */}
            {transaction.planned_expense && (
              <RelatedCard
                icon={Tag}
                iconClass="expense"
                label="Dépense Previsionnelle"
                name={transaction.planned_expense.title}
                onClick={() => navigate("/depenses/planifie/"+transaction.planned_expense.id)}
              />
            )}

            {/* Sale Context */}
            {transaction.sale_context && getSaleLink(transaction.sale_context) && (
              <RelatedCard
                icon={ShoppingCart}
                iconClass="sale"
                label={getSaleLabel(transaction.sale_context)}
                name={transaction.sale_context.sale_number}
                onClick={() => navigate(getSaleLink(transaction.sale_context))}
              />
            )}
          </div>
        </motion.div>
      )}

      {/* Notes */}
      {transaction.notes && (
        <motion.div
          className="td-notes-card"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
        >
          <div className="td-notes-header">
            <FileText size={16} className="td-notes-icon" />
            <span className="td-notes-title">Notes</span>
          </div>
          <p className="td-notes-content">{transaction.notes}</p>
        </motion.div>
      )}

      {/* Cancel Action */}
      {canCancel && (
        <motion.div
          className="td-actions"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
        >
          <button
            className="td-cancel-btn"
            onClick={() => setShowCancelModal(true)}
          >
            <XCircle size={18} />
            Annuler cette transaction
          </button>
        </motion.div>
      )}

      {/* Cancel Modal */}
      <CancelModal
        transaction={transaction}
        isOpen={showCancelModal}
        onClose={() => setShowCancelModal(false)}
        onConfirm={handleCancelTransaction}
        isLoading={cancelling}
      />
    </div>
  );
};

export default TransactionDetail;
