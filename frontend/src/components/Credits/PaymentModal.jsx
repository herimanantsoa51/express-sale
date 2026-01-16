// components/PaymentModal.jsx
import React, { useState, useEffect } from 'react';
import accountService from '../../services/accountService';
import '../../styles/components/PaymentModal.css';

const PaymentModal = ({ isOpen, onClose, installment, onSubmit }) => {
  const [accounts, setAccounts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    amount: '',
    account_id: '',
    notes: ''
  });
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (isOpen) {
      fetchAccounts();
      setFormData({
        amount: installment?.remaining_amount || '',
        account_id: '',
        notes: ''
      });
      setErrors({});
    }
  }, [isOpen, installment]);

  const fetchAccounts = async () => {
    try {
      const data = await accountService.getCashAccounts();
      setAccounts(data.data || []);
    } catch (error) {
      console.error('Erreur chargement comptes:', error);
    }
  };

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount) + ' Ar';
  };

  const validateForm = () => {
    const newErrors = {};
    
    if (!formData.amount || parseFloat(formData.amount) <= 0) {
      newErrors.amount = 'Montant invalide';
    }
    
    if (parseFloat(formData.amount) > installment?.remaining_amount) {
      newErrors.amount = 'Montant supérieur au reste à payer';
    }
    
    if (!formData.account_id) {
      newErrors.account_id = 'Veuillez sélectionner un compte';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) return;
    
    setLoading(true);
    try {
      await onSubmit({
        amount: parseFloat(formData.amount),
        account_id: parseInt(formData.account_id),
        notes: formData.notes || null
      });
      onClose();
    } catch (error) {
      setErrors({ submit: error.message || 'Erreur lors du paiement' });
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: null }));
    }
  };

  if (!isOpen) return null;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2 className="modal-title">Effectuer un paiement</h2>
          <button className="modal-close" onClick={onClose} aria-label="Fermer">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path 
                d="M18 6L6 18M6 6L18 18" 
                stroke="currentColor" 
                strokeWidth="2" 
                strokeLinecap="round"
              />
            </svg>
          </button>
        </div>

        <div className="modal-body">
          <div className="payment-summary">
            <div className="summary-item">
              <span className="summary-label">Échéance n°</span>
              <span className="summary-value">{installment?.installment_number}</span>
            </div>
            <div className="summary-item">
              <span className="summary-label">Montant total dû</span>
              <span className="summary-value">{formatAmount(installment?.amount_due || 0)}</span>
            </div>
            <div className="summary-item highlight">
              <span className="summary-label">Reste à payer</span>
              <span className="summary-value">{formatAmount(installment?.remaining_amount || 0)}</span>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="payment-form">
            <div className="form-group">
              <label className="form-label">
                Montant à payer <span className="required">*</span>
              </label>
              <input
                type="number"
                className={`form-input ${errors.amount ? 'error' : ''}`}
                value={formData.amount}
                onChange={(e) => handleChange('amount', e.target.value)}
                placeholder="0.00"
                step="0.01"
                min="0"
                max={installment?.remaining_amount}
              />
              {errors.amount && (
                <span className="form-error">{errors.amount}</span>
              )}
            </div>

            <div className="form-group">
              <label className="form-label">
                Compte de paiement <span className="required">*</span>
              </label>
              <select
                className={`form-select ${errors.account_id ? 'error' : ''}`}
                value={formData.account_id}
                onChange={(e) => handleChange('account_id', e.target.value)}
              >
                <option value="">Sélectionner un compte</option>
                {accounts.map((account) => (
                  <option key={account.id} value={account.id}>
                    {account.name}
                    {account.account_number && ` (${account.account_number})`}
                  </option>
                ))}
              </select>
              {errors.account_id && (
                <span className="form-error">{errors.account_id}</span>
              )}
            </div>

            <div className="form-group">
              <label className="form-label">Notes (optionnel)</label>
              <textarea
                className="form-textarea"
                value={formData.notes}
                onChange={(e) => handleChange('notes', e.target.value)}
                placeholder="Ajoutez une note..."
                rows="3"
              />
            </div>

            {errors.submit && (
              <div className="form-error-message">{errors.submit}</div>
            )}

            <div className="modal-actions">
              <button
                type="button"
                className="btn-secondary"
                onClick={onClose}
                disabled={loading}
              >
                Annuler
              </button>
              <button
                type="submit"
                className="btn-primary"
                disabled={loading}
              >
                {loading ? 'Traitement...' : 'Valider le paiement'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default PaymentModal;

/* ============================================
   styles/components/PaymentModal.css
   ============================================ */

/*
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: var(--backdrop-blur);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-md);
  z-index: var(--z-modal);
  animation: fadeIn 0.2s var(--transition-timing);
}

.modal-content {
  width: 100%;
  max-width: 520px;
  background: var(--bg-primary);
  border-radius: var(--border-radius-xl);
  box-shadow: var(--shadow-xl);
  max-height: 90vh;
  overflow-y: auto;
  animation: scaleIn 0.3s var(--transition-smooth);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-lg) var(--spacing-lg) var(--spacing-md);
  border-bottom: 1px solid var(--border-color);
}

.modal-title {
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
  margin: 0;
}

.modal-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  background: transparent;
  border: none;
  border-radius: 50%;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all var(--transition-speed) var(--transition-timing);
}

.modal-close:hover {
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.modal-body {
  padding: var(--spacing-lg);
}

.payment-summary {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-md);
  background: var(--bg-secondary);
  border-radius: var(--border-radius);
  margin-bottom: var(--spacing-lg);
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-item.highlight {
  padding-top: var(--spacing-sm);
  border-top: 1px solid var(--border-color);
}

.summary-label {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.summary-value {
  font-size: var(--font-size-md);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
}

.summary-item.highlight .summary-value {
  font-size: var(--font-size-lg);
  color: var(--warning);
}

.payment-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.form-label {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
}

.required {
  color: var(--danger);
}

.form-input,
.form-select,
.form-textarea {
  padding: 12px 16px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  color: var(--text-primary);
  font-size: var(--font-size-md);
  transition: all var(--transition-speed) var(--transition-timing);
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  background: var(--bg-primary);
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--primary-light);
}

.form-input.error,
.form-select.error,
.form-textarea.error {
  border-color: var(--danger);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
  font-family: var(--font-family);
}

.form-error {
  font-size: var(--font-size-xs);
  color: var(--danger);
  margin-top: 4px;
}

.form-error-message {
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--danger-light);
  border-radius: var(--border-radius);
  color: var(--danger);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
}

.modal-actions {
  display: flex;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-md);
}

.btn-primary,
.btn-secondary {
  flex: 1;
  padding: 12px 20px;
  border: none;
  border-radius: var(--border-radius);
  font-size: var(--font-size-md);
  font-weight: var(--font-weight-semibold);
  cursor: pointer;
  transition: all var(--transition-speed) var(--transition-timing);
}

.btn-primary {
  background: var(--primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: var(--bg-secondary);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
}

.btn-secondary:hover:not(:disabled) {
  background: var(--bg-tertiary);
  border-color: var(--text-tertiary);
}

@media (max-width: 768px) {
  .modal-content {
    max-width: 100%;
    border-radius: var(--border-radius-lg);
  }
  
  .modal-actions {
    flex-direction: column;
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
*/