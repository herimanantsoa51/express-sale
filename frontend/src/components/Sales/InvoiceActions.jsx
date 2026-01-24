// ============================================
// src/components/Sales/InvoiceActions.jsx
// Boutons d'actions pour les factures
// ============================================

import { useState } from 'react';
import { FileText, Download, Eye, Mail, Loader,Printer } from 'lucide-react';
import invoiceService from '../../services/invoiceService';
import styles from '../../styles/Sales/InvoiceActions.module.css';
import printService from '../../services/printService';
import { toast } from 'react-toastify';

const InvoiceActions = ({ saleId, saleNumber,hasCustomerEmail = false }) => {
  const [downloading, setDownloading] = useState(false);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [isPrinting,setIsPrinting]=useState(false);

  const handleDownload = async () => {
    try {
      setDownloading(true);
      setError(null);
      await invoiceService.downloadSale(saleId,saleNumber);
      setSuccess('Facture téléchargée avec succès');
      toast.success('Facture téléchargée avec succès');
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError('Erreur lors du téléchargement');
    } finally {
      setDownloading(false);
    }
  };

  const handlePrint=async()=>{
    try{
      setIsPrinting(true);
      setError(null);
      await printService.printSale(saleId);
      setSuccess('Facture envoyée à l\'imprimante');
      toast.success('Facture envoyée à l\'imprimante');
      setTimeout(() => setSuccess(null), 3000);
    }catch(err){
      setError('Erreur lors de l\'impression');
    }finally{
      setIsPrinting(false);
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
        {/* Bouton Impression */}
        <button
          onClick={handlePrint}
          className={`${styles.btn} ${styles.btnDownload}`}
          disabled={isPrinting}
        >
          {isPrinting ? (
            <>
              <Loader size={18} className={styles.spinner} />
              <span>Téléchargement...</span>
            </>
          ) : (
            <>
              <Printer size={18} />
              <span>Imprimer</span>
            </>
          )}
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