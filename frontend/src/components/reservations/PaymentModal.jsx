import React, { useState, useEffect } from 'react';
import { X, Wallet, Banknote, Smartphone, Check, Loader2 } from 'lucide-react';
import useAccounts from '../../hooks/useAccounts';
import { formatCurrency } from '../../utils/formatters';
import './PaymentModal.css';

/**
 * Modal simple de validation et paiement
 */
const PaymentModal = ({ isOpen, onClose, reservation, onSubmit }) => {
  const { accounts, loading: loadingAccounts } = useAccounts();
  const [formData, setFormData] = useState({
    account_id: '',
    notes: ''
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (isOpen && reservation) {
      setFormData({
        account_id: '',
        notes: ''
      });
      setErrors({});
    }
  }, [isOpen, reservation]);

  const handleClose = () => {
    if (!submitting) {
      onClose();
    }
  };

  const validate = () => {
    const newErrors = {};

    if (!formData.account_id) {
      newErrors.account_id = 'Veuillez sélectionner un compte';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validate()) return;

    setSubmitting(true);
    try {
      await onSubmit({
        account_id: parseInt(formData.account_id),
        notes: formData.notes || null
      });
      handleClose();
    } catch (error) {
      setErrors({ submit: error.message || 'Erreur lors de la validation' });
    } finally {
      setSubmitting(false);
    }
  };

  const handleChange = (field, value) => {
    setFormData({ ...formData, [field]: value });
    if (errors[field]) {
      setErrors({ ...errors, [field]: '' });
    }
  };

  const getAccountIcon = (type) => {
    switch (type) {
      case 'cash':
        return <Banknote size={20} />;
      case 'mobile_money':
        return <Smartphone size={20} />;
      default:
        return <Wallet size={20} />;
    }
  };

  if (!isOpen || !reservation) return null;

  return (
    <>
      <div className="modal-backdrop" onClick={handleClose}></div>

      <div className="payment-modal">
        <div className="modal-header">
          <h2 className="modal-title">Compléter la réservation</h2>
          <button 
            className="modal-close"
            onClick={handleClose}
            disabled={submitting}
            aria-label="Fermer"
          >
            <X size={20} />
          </button>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="modal-content">
            {/* Résumé */}
            <div className="payment-summary">
              <div className="payment-summary__row">
                <span className="payment-summary__label">Réservation</span>
                <span className="payment-summary__value">{reservation.sale_number}</span>
              </div>
              <div className="payment-summary__row">
                <span className="payment-summary__label">Client</span>
                <span className="payment-summary__value">{reservation.customer?.name}</span>
              </div>
              <div className="payment-summary__row">
                <span className="payment-summary__label">Montant total</span>
                <span className="payment-summary__value">
                  {formatCurrency(reservation.total_amount)}
                </span>
              </div>
              <div className="payment-summary__row payment-summary__row--highlight">
                <span className="payment-summary__label">Reste à payer</span>
                <span className="payment-summary__value payment-summary__value--amount">
                  {formatCurrency(reservation.remaining_amount)}
                </span>
              </div>
            </div>

            {/* Sélection du compte */}
            <div className="form-group">
              <label className="form-label">
                Compte de paiement <span className="required">*</span>
              </label>
              {loadingAccounts ? (
                <div className="loading-state">
                  <Loader2 size={20} className="spinner-icon" />
                  <span>Chargement des comptes...</span>
                </div>
              ) : (
                <div className="accounts-list">
                  {accounts.map(account => (
                    <div
                      key={account.id}
                      className={`account-option ${formData.account_id === account.id.toString() ? 'account-option--selected' : ''}`}
                      onClick={() => handleChange('account_id', account.id.toString())}
                    >
                      <div className="account-option__icon">
                        {getAccountIcon(account.type)}
                      </div>
                      <div className="account-option__info">
                        <div className="account-option__name">{account.name}</div>
                        <div className="account-option__type">
                          {account.type === 'cash' ? 'Espèces' : 'Mobile Money'}
                        </div>
                      </div>
                      <div className="account-option__check">
                        {formData.account_id === account.id.toString() && <Check size={18} />}
                      </div>
                    </div>
                  ))}
                </div>
              )}
              {errors.account_id && (
                <span className="form-error">{errors.account_id}</span>
              )}
            </div>

            {/* Notes */}
            <div className="form-group">
              <label className="form-label">Notes (optionnel)</label>
              <textarea
                className="form-textarea"
                value={formData.notes}
                onChange={(e) => handleChange('notes', e.target.value)}
                placeholder="Ajouter une note..."
                rows="3"
              />
            </div>

            {errors.submit && (
              <div className="form-error-message">{errors.submit}</div>
            )}
          </div>

          <div className="modal-footer">
            <button
              type="button"
              className="btn btn--secondary"
              onClick={handleClose}
              disabled={submitting}
            >
              Annuler
            </button>
            <button
              type="submit"
              className="btn btn--primary"
              disabled={submitting || loadingAccounts}
            >
              {submitting ? (
                <>
                  <Loader2 size={18} className="spinner-icon" />
                  <span>Validation...</span>
                </>
              ) : (
                <>
                  <Check size={18} />
                  <span>Compléter</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </>
  );
};

export default PaymentModal;
