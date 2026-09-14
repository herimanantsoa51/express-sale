// components/TransactionCard.jsx
import React from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Download,
  Banknote,
  CreditCard,
  ChevronRight,
  Loader2,
  Printer
} from 'lucide-react';


import invoiceService from '../../services/invoiceService';
import '../../styles/components/TransactionCard.css';
import printService from '../../services/printService';
import { toast } from 'react-toastify';

const TransactionCard = ({ transaction }) => {
  const navigate = useNavigate();
  const [isDownloading, setIsDownloading] = React.useState(false);
  const [isPrinting, setIsPrinting] = React.useState(false);  

  const handleNavigate = () => {
    navigate(`/transactions/${transaction.id}`);
  };

  const handleDownload = async (e) => {
    e.stopPropagation(); // ⛔ empêche la navigation
    if (isDownloading) return;
    setIsDownloading(true);
    try {
      await invoiceService.downloadTransactionInstallment(
        transaction.installment_transaction_id,
        transaction.reference
      );
    } catch (error) {
        console.log(error)
    }finally {
      setIsDownloading(false);
    }
    
  };

  const handlePrint = async (e) => {
    e.stopPropagation(); // ⛔ empêche la navigation
    if (isPrinting) return;
    setIsPrinting(true);
    try {
      await printService.printInstallmentTransaction(
        transaction.installment_transaction_id
      );
      toast.success('Reçu envoyé à l\'imprimante');
    } catch (error) { 
        console.log(error)
        toast.error('Erreur lors de l\'impression du reçu');
    }finally {
      setIsPrinting(false);
    } 
  };
  const formatAmount = (amount) =>
    new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount) + ' Ar';

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const AccountIcon =
    transaction.account?.type === 'Espèces' ||
    transaction.account?.type === 'cash'
      ? Banknote
      : CreditCard;

  return (
    <div
      className="transaction-card clickable"
      onClick={handleNavigate}
      role="button"
      tabIndex={0}
    >
      {/* Icône compte */}
      <div className="transaction-icon">
        <AccountIcon size={18} />
      </div>

      {/* Détails */}
      <div className="transaction-details">
        <div className="transaction-account">
          {transaction.account?.name}
        </div>

        <div className="transaction-meta">
          <span className="transaction-date">
            {formatDate(transaction.transaction_date)}
          </span>
          {transaction.created_by && (
            <>
              <span className="transaction-separator">•</span>
              <span className="transaction-user">
                {transaction.created_by.name}
              </span>
            </>
          )}
        </div>

        {transaction.notes && (
          <div className="transaction-notes">
            {transaction.notes}
          </div>
        )}
      </div>

      {/* Montant + actions */}
      <div className="transaction-right">
        <div className="transaction-amount">
          {formatAmount(transaction.amount)}
        </div>

        <button
          className="download-btn"
          onClick={handleDownload}
          title="Télécharger le reçu"
        >
          {isDownloading ? (
            <Loader2 size={16} className="spin" />
          ) : (
            <Download size={16} />
          )}
        </button>

        <button
          className="download-btn"
          onClick={handlePrint}
          title="Imprimer le reçu"
        >
          {isPrinting ? (
            <Loader2 size={16} className="spin" />
          ) : (
            <Printer size={16} />
          )}
        </button>

        <ChevronRight
          size={18}
          className="chevron"
        />
      </div>
    </div>
  );
};

export default TransactionCard;
