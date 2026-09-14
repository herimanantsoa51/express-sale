// pages/CreditDetailPage.jsx
import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Tag, Percent, Download, FileText, Loader, Printer, XCircle } from 'lucide-react';

import useCreditDetail from '../../hooks/useCreditDetail';
import CustomerMiniCard from '../../components/Credits/CustomerMiniCard';
import InstallmentCard from '../../components/Credits/InstallmentCard';
import CreditProductCard from '../../components/Credits/CreditProductCard';
import PaymentModal from '../../components/Credits/PaymentModal';
import CancelCreditModal from '../../components/Credits/CancelCreditModal';
import LoadingSpinner from '../../components/Credits/LoadingSpinner';
import StatusBadge from '../../components/Credits/StatusBadge';
import '../../styles/components/CreditDetailPage.css';
import invoiceService from '../../services/invoiceService';
import creditService from '../../services/creditService';
import { toast } from 'react-toastify';
import printService from '../../services/printService';

const CreditDetailPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { credit, loading, error, payInstallment } = useCreditDetail(id);
  
  const [selectedInstallment, setSelectedInstallment] = useState(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isCancelModalOpen, setIsCancelModalOpen] = useState(false);
  const [downloading, setDownloading] = useState(false);
  const [isPrinting, setIsPrinting] = useState(false);
  const [isCancelling, setIsCancelling] = useState(false);

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount) + ' Ar';
  };

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric'
    });
  };

  const handleOpenPaymentModal = (installment) => {
    if (credit.status === 'cancelled') {
      toast.warning('Impossible d\'effectuer un paiement sur un crédit annulé');
      return;
    }
    setSelectedInstallment(installment);
    setIsModalOpen(true);
  };

  const handlePayment = async (paymentData) => {
    const result = await payInstallment(selectedInstallment.id, paymentData);
    if (result.success) {
      setIsModalOpen(false);
      setSelectedInstallment(null);
    } else {
      throw new Error(result.error);
    }
  };

  const handleDownloadCredit = async () => {
    if (downloading) {
      return;
    }
    try {
      setDownloading(true);
      await invoiceService.downloadCredit(credit.id, credit.sale_number);
      toast.success('Crédit téléchargé avec succès');
    } catch (err) {
      alert('Impossible de télécharger le crédit');
    } finally {
      setDownloading(false);
    }
  };

  const handlePrintCredit = async () => {
    if (isPrinting) {
      return;
    }
    try {
      setIsPrinting(true);
      await printService.printCredit(credit.id);
      toast.success('Crédit envoyé à l\'imprimante');
    } catch (err) {
      console.log(err);
      toast.error('Impossible d\'imprimer le crédit');
    } finally {
      setIsPrinting(false);
    }
  };

  const handleCancelCredit = async () => {
    if (isCancelling) return;
    
    try {
      setIsCancelling(true);
      await creditService.cancelCredit(credit.id);
      toast.success('Crédit annulé avec succès');
      setIsCancelModalOpen(false);
      // Recharger la page pour voir les changements
      window.location.reload();
    } catch (err) {
      console.error(err);
      toast.error('Impossible d\'annuler le crédit');
    } finally {
      setIsCancelling(false);
    }
  };

  const hasConfirmedTransactions = () => {
    if (!credit.installments) return false;
    return credit.installments.some(installment => 
      installment.transactions?.some(t => t.status === 'confirmed')
    );
  };
  
  if (loading) {
    return <LoadingSpinner fullScreen size="large" />;
  }

  if (error) {
    return (
      <div className="error-page">
        <div className="error-content">
          <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
            <path 
              d="M32 56C45.2548 56 56 45.2548 56 32C56 18.7452 45.2548 8 32 8C18.7452 8 8 18.7452 8 32C8 45.2548 18.7452 56 32 56Z" 
              stroke="currentColor" 
              strokeWidth="2"
            />
            <path 
              d="M32 20V32M32 44H32.02" 
              stroke="currentColor" 
              strokeWidth="2" 
              strokeLinecap="round"
            />
          </svg>
          <h2>Erreur</h2>
          <p>{error}</p>
          <button className="back-button" onClick={() => navigate('/credits')}>
            Retour à la liste
          </button>
        </div>
      </div>
    );
  }

  if (!credit) return null;

  const isCancelled = credit.status === 'cancelled';

  return (
    <div className="credit-detail-page">
      <div className="page-container">
        <button className="back-nav" onClick={() => navigate('/ventes/credits')}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path 
              d="M12.5 15L7.5 10L12.5 5" 
              stroke="currentColor" 
              strokeWidth="1.5" 
              strokeLinecap="round" 
              strokeLinejoin="round"
            />
          </svg>
          Retour
        </button>

        <div className="credit-header">
          <div className="header-main">
            <div className="header-title-section">
              <h1 className="credit-number">{credit.credit_number}</h1>
              <StatusBadge status={credit.status} />
            </div>
            <p className="credit-date">Créé le {formatDate(credit.credit_date)}</p>
          </div>

          <div className="header-actions">
            {!isCancelled && (
              <button
                className="cancel-credit-btn"
                onClick={() => setIsCancelModalOpen(true)}
              >
                <XCircle size={18} />
                <span>Annuler le crédit</span>
              </button>
            )}
            <button
              className="download-credit-btn"
              onClick={handlePrintCredit}
              disabled={isPrinting}
            >
              {isPrinting ? (
                <>
                  <Loader size={18} className="down-spinner" />
                  <span>Impression ...</span>
                </>
              ) : (
                <>
                  <Printer size={18} />
                  <span>Imprimer</span>
                </>
              )}
            </button>
            <button
              className="download-credit-btn"
              onClick={handleDownloadCredit}
              disabled={downloading}
            >
              {downloading ? (
                <>
                  <Loader size={18} className="down-spinner" />
                  <span>Téléchargement...</span>
                </>
              ) : (
                <>
                  <Download size={18} />
                  <span>Télécharger</span>
                </>
              )}
            </button>
          </div>
          
          <div className="header-amounts">
            <div className="amount-card total">
              <div className="amount-label">Montant total</div>
              <div className="amount-value">{formatAmount(credit.total_amount)}</div>
            </div>
            <div className="amount-card paid">
              <div className="amount-label">Montant payé</div>
              <div className="amount-value">{formatAmount(credit.amount_paid)}</div>
            </div>
            <div className="amount-card due">
              <div className="amount-label">Reste à payer</div>
              <div className="amount-value">{formatAmount(credit.amount_due)}</div>
            </div>
          </div>

          {/* Remise */}
          {credit.discount_amount > 0 && (
            <div className="discount-banner">
              <Percent size={18} />
              <span className="discount-label">Remise appliquée:</span>
              <span className="discount-amount">{formatAmount(credit.discount_amount)}</span>
              {credit.discount_reason && (
                <span className="discount-reason">({credit.discount_reason})</span>
              )}
            </div>
          )}
        </div>

        <div className="credit-content">
          <div className="credit-sidebar">
            <section className="info-section">
              <h2 className="section-title">Client</h2>
              <CustomerMiniCard customer={credit.customer} />
            </section>

            <section className="info-section">
              <h2 className="section-title">Informations</h2>
              <div className="info-list">
                <div className="info-item">
                  <span className="info-item-label">Date d'échéance</span>
                  <span className="info-item-value">{formatDate(credit.due_date)}</span>
                </div>
                <div className="info-item">
                  <span className="info-item-label">Dernier paiement</span>
                  <span className="info-item-value">
                    {credit.last_payment_date ? formatDate(credit.last_payment_date) : 'Aucun'}
                  </span>
                </div>
                <div className="info-item">
                  <span className="info-item-label">Échéances restantes</span>
                  <span className="info-item-value">{credit.remaining_installments}</span>
                </div>
                <div className="info-item">
                  <span className="info-item-label">Progression</span>
                  <span className="info-item-value">{(credit.payment_percentage || 0).toFixed(1)}%</span>
                </div>
              </div>
              
              {credit.notes && (
                <div className="notes-box">
                  <div className="notes-label">Notes</div>
                  <div className="notes-content">{credit.notes}</div>
                </div>
              )}
            </section>
          </div>

          <div className="credit-main">
            {/* Produits achetés */}
            {credit.items && credit.items.length > 0 && (
              <section className="products-section">
                <div className="section-header">
                  <h2 className="section-title">
                    Produits achetés ({credit.items.length})
                  </h2>
                </div>
                
                <div className="products-list">
                  {credit.items.map((item) => (
                    <CreditProductCard key={item.id} item={item} />
                  ))}
                </div>

                {/* Totaux */}
                <div className="products-summary">
                  <div className="summary-row">
                    <span className="summary-label">Sous-total</span>
                    <span className="summary-value">{formatAmount(credit.subtotal)}</span>
                  </div>
                  {credit.discount_amount > 0 && (
                    <div className="summary-row discount">
                      <span className="summary-label">
                        <Percent size={14} />
                        Remise
                      </span>
                      <span className="summary-value">- {formatAmount(credit.discount_amount)}</span>
                    </div>
                  )}
                  <div className="summary-row total">
                    <span className="summary-label">
                      <Tag size={14} />
                      Total
                    </span>
                    <span className="summary-value">{formatAmount(credit.total_amount)}</span>
                  </div>
                </div>
              </section>
            )}

            {/* Échéances */}
            <section className="installments-section">
              <div className="section-header">
                <h2 className="section-title">
                  Échéances ({credit.installments?.length || 0})
                </h2>
              </div>
              
              <div className="installments-list">
                {credit.installments && credit.installments.length > 0 ? (
                  credit.installments.map((installment) => (
                    <InstallmentCard
                      key={installment.id}
                      installment={installment}
                      onPay={handleOpenPaymentModal}
                      isCreditCancelled={isCancelled}
                    />
                  ))
                ) : (
                  <div className="empty-state">
                    <p>Aucune échéance disponible</p>
                  </div>
                )}
              </div>
            </section>
          </div>
        </div>
      </div>

      <PaymentModal
        isOpen={isModalOpen}
        onClose={() => {
          setIsModalOpen(false);
          setSelectedInstallment(null);
        }}
        installment={selectedInstallment}
        onSubmit={handlePayment}
      />

      <CancelCreditModal
        isOpen={isCancelModalOpen}
        onClose={() => setIsCancelModalOpen(false)}
        onConfirm={handleCancelCredit}
        creditNumber={credit.credit_number}
        hasConfirmedTransactions={hasConfirmedTransactions()}
        isLoading={isCancelling}
      />
    </div>
  );
};

export default CreditDetailPage;