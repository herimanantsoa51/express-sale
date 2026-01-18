import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { ArrowLeft, Edit, Calendar, User, FileText, Coins } from 'lucide-react';
import cashCountService from '../../services/cashCountService';
import { useParams } from 'react-router-dom';
import '../../styles/CashCountDetail.css';

const CashCountDetail = () => {
  const [cashCount, setCashCount] = useState(null);
  const [loading, setLoading] = useState(true);
  const { id } = useParams();

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
    } finally {
      setLoading(false);
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
          <button className="ccdetail-back-button" onClick={() => window.location.href = '/comptages'}>
            <ArrowLeft size={20} />
          </button>
          <motion.h1
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
          >
            Détails du comptage
          </motion.h1>
          <button
            className="ccdetail-edit-button"
            onClick={() => window.location.href = `/comptages/${id}/modifier`}
          >
            <Edit size={18} />
            <span>Modifier</span>
          </button>
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