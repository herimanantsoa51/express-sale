// components/Credits/CancelCreditModal.jsx
import React from 'react';
import { XCircle, AlertTriangle, Package, Loader } from 'lucide-react';
import '../../styles/components/CancelCreditModal.css';

const CancelCreditModal = ({ 
  isOpen, 
  onClose, 
  onConfirm, 
  creditNumber, 
  hasConfirmedTransactions,
  isLoading 
}) => {
  if (!isOpen) return null;

  return (
    <div className="cancel-modal-overlay" onClick={onClose}>
      <div className="cancel-modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="cancel-modal-header">
          <div className="cancel-modal-icon danger">
            <XCircle size={24} />
          </div>
          <h2 className="cancel-modal-title">Annuler le crédit</h2>
          <button className="cancel-modal-close" onClick={onClose}>
            ×
          </button>
        </div>

        <div className="cancel-modal-body">
          <div className="cancel-warning-box">
            <AlertTriangle size={20} />
            <div>
              <p className="cancel-warning-title">Attention : Action irréversible</p>
              <p className="cancel-warning-text">
                Vous êtes sur le point d'annuler le crédit <strong>{creditNumber}</strong>
              </p>
            </div>
          </div>

          <div className="cancel-info-section">
            <div className="cancel-info-item">
              <Package size={18} />
              <div>
                <p className="cancel-info-title">Restock des produits</p>
                <p className="cancel-info-text">
                  Les produits vendus dans ce crédit seront automatiquement remis en stock.
                </p>
              </div>
            </div>

            <div className="cancel-info-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
              </svg>
              <div>
                <p className="cancel-info-title">Pas d'annulation de transactions</p>
                <p className="cancel-info-text">
                  Les transactions monétaires ne seront <strong>pas annulées</strong> automatiquement.
                </p>
              </div>
            </div>
          </div>

          {hasConfirmedTransactions && (
            <div className="cancel-alert-box">
              <AlertTriangle size={18} />
              <div>
                <p className="cancel-alert-title">Transactions confirmées détectées</p>
                <p className="cancel-alert-text">
                  Ce crédit contient des paiements confirmés. Vous devrez annuler manuellement 
                  les transactions concernées si nécessaire.
                </p>
              </div>
            </div>
          )}
        </div>

        <div className="cancel-modal-footer">
          <button 
            className="cancel-modal-btn secondary" 
            onClick={onClose}
            disabled={isLoading}
          >
            Annuler
          </button>
          <button 
            className="cancel-modal-btn danger" 
            onClick={onConfirm}
            disabled={isLoading}
          >
            {isLoading ? (
              <>
                <Loader size={16} className="btn-spinner" />
                Annulation en cours...
              </>
            ) : (
              <>
                <XCircle size={16} />
                Confirmer l'annulation
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

export default CancelCreditModal;