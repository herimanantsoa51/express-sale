// ============================================
// src/components/Sales/InvoiceActions.jsx
// Boutons d'actions pour les factures
// ============================================

import { useState } from 'react';
import { FileText, Download, Eye, Mail, Loader } from 'lucide-react';
import invoiceService from '../../services/invoiceService';
import styles from '../../styles/Sales/InvoiceActions.module.css';

const InvoiceActions = ({ saleId, hasCustomerEmail = false }) => {
  const [downloading, setDownloading] = useState(false);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const handleDownload = async () => {
    try {
      setDownloading(true);
      setError(null);
      await invoiceService.download(saleId);
      setSuccess('Facture téléchargée avec succès');
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError('Erreur lors du téléchargement');
    } finally {
      setDownloading(false);
    }
  };

  const handleShow = () => {
    try {
      setError(null);
      invoiceService.show(saleId);
    } catch (err) {
      setError('Erreur lors de l\'ouverture de la facture');
    }
  };

  const handleSendEmail = async () => {
    try {
      setSending(true);
      setError(null);
      await invoiceService.sendByEmail(saleId);
      setSuccess('Facture envoyée par email');
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError('Erreur lors de l\'envoi de l\'email');
    } finally {
      setSending(false);
    }
  };

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <FileText size={20} className={styles.icon} />
        <h3 className={styles.title}>Facture</h3>
      </div>

      <div className={styles.actions}>
        {/* Bouton Visualiser */}
        <button
          onClick={handleShow}
          className={`${styles.btn} ${styles.btnView}`}
          disabled={downloading || sending}
        >
          <Eye size={18} />
          <span>Visualiser</span>
        </button>

        {/* Bouton Télécharger */}
        <button
          onClick={handleDownload}
          className={`${styles.btn} ${styles.btnDownload}`}
          disabled={downloading || sending}
        >
          {downloading ? (
            <>
              <Loader size={18} className={styles.spinner} />
              <span>Téléchargement...</span>
            </>
          ) : (
            <>
              <Download size={18} />
              <span>Télécharger</span>
            </>
          )}
        </button>

        {/* Bouton Envoyer par email (si client a un email) */}
        {hasCustomerEmail && (
          <button
            onClick={handleSendEmail}
            className={`${styles.btn} ${styles.btnEmail}`}
            disabled={downloading || sending}
          >
            {sending ? (
              <>
                <Loader size={18} className={styles.spinner} />
                <span>Envoi...</span>
              </>
            ) : (
              <>
                <Mail size={18} />
                <span>Envoyer</span>
              </>
            )}
          </button>
        )}
      </div>

      {/* Messages de feedback */}
      {error && (
        <div className={styles.error}>
          {error}
        </div>
      )}
      {success && (
        <div className={styles.success}>
          {success}
        </div>
      )}
    </div>
  );
};

export default InvoiceActions;