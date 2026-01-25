import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { 
  ArrowLeft, CreditCard, X, AlertTriangle, Clock, Calendar, 
  User, Package, Receipt, Wallet, Loader2, Image, Download, CheckCircle2,Printer
} from 'lucide-react';
import reservationsService from '../../services/reservationsService';
import StatusBadge from '../../components/reservations/StatusBadge';
import PaymentModal from '../../components/reservations/PaymentModal';
import { formatCurrency, formatDate, calculatePercentage, daysUntil } from '../../utils/formatters';
import invoiceService from '../../services/invoiceService';
import './ReservationDetailPage.css';
import { toast } from 'react-toastify';
import printService from '../../services/printService';

const ReservationDetailPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [reservation, setReservation] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
  const [actionLoading, setActionLoading] = useState(false);
  const [isDownloadingReceipt, setIsDownloadingReceipt] = useState(false);
  const [isDownloadingInvoice, setIsDownloadingInvoice] = useState(false);
  const [isPrintingInvoice,setIsPrintingInvoice]=useState(false);
  const [isPrintingReceipt,setIsPrintingReceipt]=useState(false);

  useEffect(() => {
    fetchReservation();
  }, [id]);

  const fetchReservation = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await reservationsService.getReservationById(id);
      setReservation(response.data || response);
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement');
    } finally {
      setLoading(false);
    }
  };

  const handleCompleteReservation = async (data) => {
    setActionLoading(true);
    try {
      await reservationsService.completeReservation(id, data);
      await fetchReservation();
      setIsPaymentModalOpen(false);
    } catch (err) {

      console.error(err);
      throw err;
    } finally {
      setActionLoading(false);
    }
  };

  const handlePrintInvoice=async()=>{
    if (isPrintingInvoice) return;
    setIsPrintingInvoice(true);
    toast.info('Envoi de la facture à l\'imprimante...');
    try {
      await printService.printReservation(reservation.id);
      toast.success('Facture envoyée à l\'imprimante');
    } catch (error) {
      console.log(error);
      toast.error('Erreur lors de l\'impression de la facture');
    } finally {
      setIsPrintingInvoice(false);
    }
  };
  const handlePrintReceipt=async(e)=>{
    e.stopPropagation();
    if (isPrintingReceipt) return;
    setIsPrintingReceipt(true);
    toast.info('Envoi du reçu à l\'imprimante...');
    try {
      await printService.printReservationReceipt(
        reservation.id,
        reservation.sale_info.sale_number
      );
      toast.success('Reçu envoyé à l\'imprimante');
    } catch (error) {
      console.log(error);
      toast.error('Erreur lors de l\'impression du reçu');
    } finally {
      setIsPrintingReceipt(false);
    }
  };
  const handleCancel = async () => {
    const reason = window.prompt('Raison de l\'annulation :');
    if (!reason) return;
    setActionLoading(true);
    try {
      await reservationsService.cancelReservation(id, reason);
      await fetchReservation();
    } catch (err) {
      alert('Erreur lors de l\'annulation');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDownloadReceipt = async (e) => {
    e.stopPropagation();
    if (isDownloadingReceipt) return;
    setIsDownloadingReceipt(true);
    toast.info('Téléchargement du reçu en cours...');
    try {
      await invoiceService.downloadReservationReceipt(
        reservation.id,
        reservation.sale_info.sale_number
      );
      toast.success('Reçu téléchargé avec succès');
    } catch (error) {
      console.log(error);
      toast.error('Erreur lors du téléchargement du reçu');
    } finally {
      setIsDownloadingReceipt(false);
    }
  };

  const handleDownloadInvoice = async () => {
    if (isDownloadingInvoice) return;
    setIsDownloadingInvoice(true);
    toast.info('Téléchargement de la facture en cours...');
    try {
      await invoiceService.downloadReservation(
        reservation.id,
        reservation.sale_info.sale_number
      );
      toast.success('Facture téléchargée avec succès');
    } catch (error) {
      console.log(error);
      toast.error('Erreur lors du téléchargement de la facture');
    } finally {
      setIsDownloadingInvoice(false);
    }
  };

  const handleTransactionClick = (transactionId) => {
    navigate(`/transactions/${transactionId}`);
  };

  if (loading) {
    return (
      <div className="reservation-detail-page">
        <div className="reservation-detail-loading">
          <Loader2 size={32} className="spinner-icon" />
          <p>Chargement...</p>
        </div>
      </div>
    );
  }

  if (error || !reservation) {
    return (
      <div className="reservation-detail-page">
        <div className="reservation-detail-error">
          <AlertTriangle size={48} />
          <h3>Erreur</h3>
          <p>{error || 'Réservation introuvable'}</p>
          <button onClick={() => navigate('/reservations')}>Retour</button>
        </div>
      </div>
    );
  }

  const saleNumber = reservation.sale_info?.sale_number || '';
  const items = reservation.items || [];
  const transactions = reservation.transactions || [];
  const customer = reservation.customer;
  const paymentPercentage = calculatePercentage(reservation.deposit_amount, reservation.total_amount);
  const daysRemaining = daysUntil(reservation.expiry_date);
  const isExpiringSoon = daysRemaining <= 7 && daysRemaining > 0;
  const isExpired = daysRemaining < 0;
  const canComplete = ['pending', 'confirmed', 'partial_paid'].includes(reservation.status) && reservation.remaining_amount > 0;

  return (
    <div className="reservation-detail-page">
      <div className="reservation-detail-header">
        <button className="back-btn" onClick={() => navigate('/reservations')}>
          <ArrowLeft size={18} /><span>Retour</span>
        </button>
        <div className="reservation-detail-header__title">
          <h1>{saleNumber}</h1>
          <StatusBadge status={reservation.status} />
        </div>
        <div className="reservation-detail-header__actions">
          <button 
            className="action-btn action-btn--secondary" 
            onClick={handleDownloadInvoice}
            disabled={isDownloadingInvoice}
            title="Télécharger la facture de réservation"
          >
            {isDownloadingInvoice ? (
              <Loader2 size={18} className="spin" />
            ) : (
              <Download size={18} />
            )}
            <span>Télécharger</span>
          </button>
          <button 
            className="action-btn action-btn--secondary" 
            onClick={handlePrintInvoice}
            disabled={isPrintingInvoice}
            title="Imprimer la facture de réservation"
          >
            {isPrintingInvoice ? (
              <Loader2 size={18} className="spin" />
            ) : (
              <Printer size={18} />
            )}
            <span>Imprimer</span>
          </button>
          {canComplete && (
            <button className="action-btn action-btn--primary" onClick={() => setIsPaymentModalOpen(true)} disabled={actionLoading}>
              <CreditCard size={18} /><span>Compléter</span>
            </button>
          )}
          {reservation.status !== 'cancelled' && reservation.status !== 'completed' && (
            <button className="action-btn action-btn--danger" onClick={handleCancel} disabled={actionLoading}>
              <X size={18} /><span>Annuler</span>
            </button>
          )}
        </div>
      </div>

      {isExpiringSoon && (
        <div className="reservation-alert reservation-alert--warning">
          <AlertTriangle size={20} />
          <span>Expire dans {daysRemaining} jour{daysRemaining > 1 ? 's' : ''}</span>
        </div>
      )}

      {isExpired && (
        <div className="reservation-alert reservation-alert--danger">
          <Clock size={20} /><span>Réservation expirée</span>
        </div>
      )}

      {reservation.status === 'cancelled' && reservation.cancellation_reason && (
        <div className="reservation-alert reservation-alert--danger">
          <X size={20} />
          <span>Annulée : {reservation.cancellation_reason}</span>
        </div>
      )}

      <div className="reservation-detail-content">
        <div className="reservation-detail-main">
          <div className="detail-card">
            <h2 className="detail-card__title"><Calendar size={18} /><span>Informations</span></h2>
            <div className="detail-card__content">
              <div className="detail-row">
                <span className="detail-label">Date de réservation</span>
                <span className="detail-value">{formatDate(reservation.reservation_date, 'long')}</span>
              </div>
              <div className="detail-row">
                <span className="detail-label">Expiration</span>
                <span className="detail-value">{formatDate(reservation.expiry_date, 'long')}</span>
              </div>
            </div>
          </div>

          <div className="detail-card">
            <h2 className="detail-card__title"><User size={18} /><span>Client</span></h2>
            <div className="detail-card__content">
              <div className="customer-header">
                <div className="customer-avatar">{customer?.name?.charAt(0) || '?'}</div>
                <div>
                  <div className="customer-name">{customer?.name}</div>
                  <div className="customer-code">{customer?.customer_number}</div>
                </div>
              </div>
            </div>
          </div>

          {items.length > 0 && (
            <div className="detail-card">
              <h2 className="detail-card__title"><Package size={18} /><span>Articles ({items.length})</span></h2>
              <div className="items-list">
                {items.map((item, idx) => (
                  <div key={idx} className="item-row">
                    <div className="item-image">
                      {item.image_url ? (
                        <img src={item.image_url} alt={item.product?.name} />
                      ) : (
                        <div className="item-image-placeholder"><Image size={20} /></div>
                      )}
                    </div>
                    <div className="item-info">
                      <div className="item-name">{item.product?.name}</div>
                      {item.variant?.attributes?.length > 0 && (
                        <div className="item-attributes">
                          {item.variant.attributes.map((a, i) => (
                            <span key={i} className="attr-tag">{a.attribute_type}: {a.attribute_value}</span>
                          ))}
                        </div>
                      )}
                    </div>
                    <div className="item-qty">x{item.quantity}</div>
                    <div className="item-price">{formatCurrency(item.line_total)}</div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {transactions.length > 0 && (
            <div className="detail-card">
              <h2 className="detail-card__title">
                <Receipt size={18} />
                <span>Paiements ({transactions.length})</span>
              </h2>
              <div className="transactions-list">
                {transactions.map((tx, idx) => {
                  const isComplete = tx.id === reservation.transaction_complete_id;
                  
                  return (
                    <div 
                      key={idx} 
                      className={`transaction-row ${isComplete ? 'transaction-row--complete' : ''} transaction-row--clickable`}
                      onClick={() => handleTransactionClick(tx.id)}
                    >
                      <div className="transaction-icon">
                        <Wallet size={16} />
                      </div>
                      
                      <div className="transaction-info">
                        <div className="transaction-date">
                          {formatDate(tx.transaction_date)}
                        </div>
                        <div className="transaction-account">
                          {tx.account?.name}
                        </div>
                        
                        {isComplete && (
                          <div className="transaction-footer">
                            <span className="transaction-complete-badge">
                              <CheckCircle2 size={12} />
                              Paiement final
                            </span>
                            <button
                              className="download-btn"
                              onClick={handleDownloadReceipt}
                              disabled={isDownloadingReceipt}
                              title="Télécharger le reçu"
                            >
                              {isDownloadingReceipt ? (
                                <>
                                  <Loader2 size={14} className="spin" />
                                  <span>Téléchargement...</span>
                                </>
                              ) : (
                                <>
                                  <Download size={14} />
                                  <span>Télécharger</span>
                                </>
                              )}
                            </button>

                            <button
                              className="download-btn"
                              onClick={handlePrintReceipt}
                              disabled={isPrintingReceipt}
                              title="Imprimer le reçu"
                            >
                              {isPrintingReceipt ? (
                                <>
                                  <Loader2 size={14} className="spin" />
                                  <span>Téléchargement...</span>
                                </>
                              ) : (
                                <>
                                  <Printer size={14} />
                                  <span>Imprimer</span>
                                </>
                              )}
                            </button>
                          </div>
                        )}
                      </div>
                      
                      <div className="transaction-amount">
                        +{formatCurrency(tx.amount)}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}
        </div>

        <div className="reservation-detail-sidebar">
          <div className="detail-card detail-card--highlight">
            <h2 className="detail-card__title"><CreditCard size={18} /><span>Résumé</span></h2>
            <div className="detail-card__content">
              <div className="amount-row">
                <span>Total</span>
                <span className="amount-value--large">{formatCurrency(reservation.total_amount)}</span>
              </div>
              <div className="amount-row">
                <span>Payé</span>
                <span className="amount-value--success">{formatCurrency(reservation.deposit_amount)}</span>
              </div>
              <div className="amount-row amount-row--separator">
                <span>Reste</span>
                <span className="amount-value--danger">{formatCurrency(reservation.remaining_amount)}</span>
              </div>
              <div className="payment-progress">
                <div className="payment-progress__bar">
                  <div className="payment-progress__fill" style={{ width: `${paymentPercentage}%` }}></div>
                </div>
                <span>{paymentPercentage}%</span>
              </div>
              {canComplete && (
                <button className="action-btn action-btn--primary action-btn--full" onClick={() => setIsPaymentModalOpen(true)}>
                  <CreditCard size={18} /><span>Compléter</span>
                </button>
              )}
            </div>
          </div>
        </div>
      </div>

      <PaymentModal
        isOpen={isPaymentModalOpen}
        onClose={() => setIsPaymentModalOpen(false)}
        reservation={{ ...reservation, sale_number: saleNumber }}
        onSubmit={handleCompleteReservation}
      />
    </div>
  );
};

export default ReservationDetailPage;