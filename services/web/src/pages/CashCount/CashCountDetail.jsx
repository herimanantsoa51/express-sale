import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { ArrowLeft, Edit, Calendar, User, FileText, Coins, Download, Loader2,Printer } from 'lucide-react';
import cashCountService from '../../services/cashCountService';
import invoiceService from '../../services/invoiceService';
import { useParams, useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import '../../styles/CashCountDetail.css';
import printService from '../../services/printService';

const CashCountDetail = () => {
  const [cashCount, setCashCount] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isDownloading, setIsDownloading] = useState(false);
  const [isPrinting, setIsPrinting] = useState(false);
  const { id } = useParams();
  const navigate = useNavigate();

  useEffect(() => {
    fetchCashCount();
  }, [id]);

  const fetchCashCount = async () => {
    try {
      setLoading(true);
      const response = await cashCountService.getCashCountById(id);
      setCashCount(response.data);
    } catch (error) {
      console.error('Erreur lors du chargement:', error);
      toast.error('Erreur lors du chargement du comptage');
    } finally {
      setLoading(false);
    }
  };
  const handlePrint = async () => {
    if (isPrinting) return;
    setIsPrinting(true);
    toast.info('Envoi du rapport à l\'imprimante...');
    try {
      await printService.printCashCount(
        cashCount.id,
        formatDate(cashCount.count_date)
      );
      toast.success('Rapport envoyé à l\'imprimante');
    } catch (error) {
      console.error('Erreur impression:', error);
      toast.error('Erreur lors de l\'impression du rapport');
    } finally {
      setIsPrinting(false);
    }
  };
  const handleDownloadPDF = async () => {
    if (isDownloading) return;
    setIsDownloading(true);
    toast.info('Téléchargement du rapport en cours...');
    try {
      await invoiceService.downloadCashCount(
        cashCount.id,
        formatDate(cashCount.count_date)
      );
      toast.success('Rapport téléchargé avec succès');
    } catch (error) {
      console.error('Erreur téléchargement:', error);
      toast.error('Erreur lors du téléchargement du rapport');
    } finally {
      setIsDownloading(false);
    }
  };

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
  };

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount);
  };

  if (loading) {
    return (
      <div className="ccdetail-loading-container">
        <div className="ccdetail-spinner"></div>
      </div>
    );
  }

  if (!cashCount) {
    return (
      <div className="ccdetail-error-container">
        <p>Comptage introuvable</p>
      </div>
    );
  }

  return (
    <div className="ccdetail-page">
      <div className="ccdetail-header">
        <div className="ccdetail-header-content">
          <button className="ccdetail-back-button" onClick={() => navigate('/comptages')}>
            <ArrowLeft size={20} />
          </button>
          <motion.h1
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
          >
            Détails du comptage
          </motion.h1>
          <div className="ccdetail-header-actions">
            <button
              className="ccdetail-download-button"
              onClick={handleDownloadPDF}
              disabled={isDownloading}
              title="Télécharger le rapport PDF"
            >
              {isDownloading ? (
                <Loader2 size={18} className="ccdetail-spinner-icon" />
              ) : (
                <Download size={18} />
              )}
              <span>{isDownloading ? 'Téléchargement...' : 'Rapport PDF'}</span>
            </button>
            <button
              className="ccdetail-download-button"
              onClick={handlePrint}
              disabled={isPrinting}
              title="Imprimer"
            >
              {isPrinting ? (
                <Loader2 size={18} className="ccdetail-spinner-icon" />
              ) : (
                <Printer size={18} />
              )}
              <span>{isPrinting ? 'Impression...' : 'Imprimer'}</span>
            </button>
            <button
              className="ccdetail-edit-button"
              onClick={() => navigate(`/comptages/${id}/modifier`)}
            >
              <Edit size={18} />
              <span>Modifier</span>
            </button>
          </div>
        </div>
      </div>

      <div className="ccdetail-content">
        <motion.div
          className="ccdetail-card ccdetail-main-info"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
        >
          <div className="ccdetail-total-section">
            <div className="ccdetail-total-label">Montant total</div>
            <div className="ccdetail-total-amount">{formatAmount(cashCount.total_amount)} Ar</div>
          </div>

          <div className="ccdetail-info-grid">
            <div className="ccdetail-info-item">
              <Calendar size={18} />
              <div>
                <div className="ccdetail-info-label">Date du comptage</div>
                <div className="ccdetail-info-value">{formatDate(cashCount.count_date)}</div>
              </div>
            </div>

            <div className="ccdetail-info-item">
              <User size={18} />
              <div>
                <div className="ccdetail-info-label">Créé par</div>
                <div className="ccdetail-info-value">{cashCount.created_by?.name || 'Inconnu'}</div>
              </div>
            </div>

            {cashCount.notes && (
              <div className="ccdetail-info-item ccdetail-full-width">
                <FileText size={18} />
                <div>
                  <div className="ccdetail-info-label">Notes</div>
                  <div className="ccdetail-info-value">{cashCount.notes}</div>
                </div>
              </div>
            )}
          </div>
        </motion.div>

        <motion.div
          className="ccdetail-card"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
        >
          <div className="ccdetail-card-header">
            <h2>
              <Coins size={20} />
              Détail des coupures
            </h2>
          </div>

          <div className="ccdetail-denominations-list">
            {cashCount.denominations && cashCount.denominations.length > 0 ? (
              cashCount.denominations.map((denom, index) => (
                <motion.div
                  key={index}
                  className="ccdetail-denomination-row"
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: 0.25 + index * 0.03 }}
                >
                  <div className="ccdetail-denom-info">
                    <div className="ccdetail-denom-value">
                      {denom.denomination.toLocaleString('fr-FR')} Ar
                    </div>
                    <div className="ccdetail-denom-quantity">
                      {denom.quantity} {denom.quantity > 1 ? 'billets/pièces' : 'billet/pièce'}
                    </div>
                  </div>
                  <div className="ccdetail-denom-subtotal">
                    {formatAmount(denom.subtotal)} Ar
                  </div>
                </motion.div>
              ))
            ) : (
              <div className="ccdetail-empty-state">
                <p>Aucune coupure enregistrée</p>
              </div>
            )}
          </div>

          <div className="ccdetail-denominations-footer">
            <div className="ccdetail-footer-label">Total général</div>
            <div className="ccdetail-footer-amount">{formatAmount(cashCount.total_amount)} Ar</div>
          </div>
        </motion.div>
      </div>
    </div>
  );
};

export default CashCountDetail;