import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'motion/react';
import {
  ArrowLeft, ArrowDownUp, RefreshCw, Wallet, Landmark, Smartphone,
  Banknote, AlertCircle, CheckCircle, Check, X, Send
} from 'lucide-react';
import accountService from '../../services/accountService';
import '../../styles/AccountTransfer.css';
import { toast } from 'react-toastify';

// === HELPERS ===
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(amount);
};

const getAccountTypeConfig = (typeCode) => {
  const config = {
    'CASH': { icon: Banknote, className: 'cash' },
    'MOBILE_MONEY': { icon: Smartphone, className: 'mobile' },
    'BANK': { icon: Landmark, className: 'bank' }
  };
  return config[typeCode] || { icon: Wallet, className: '' };
};

// === ACCOUNT OPTION ===
const AccountOption = ({ account, selected, disabled, onSelect }) => {
  const config = getAccountTypeConfig(account.account_type?.code);
  const Icon = config.icon;

  return (
    <motion.div
      className={`account-option ${selected ? 'selected' : ''} ${disabled ? 'disabled' : ''}`}
      onClick={() => !disabled && onSelect(account)}
      whileHover={!disabled ? { scale: 1.01 } : {}}
      whileTap={!disabled ? { scale: 0.99 } : {}}
    >
      <div className={`account-option-icon ${config.className}`}>
        <Icon size={22} strokeWidth={1.5} />
      </div>
      <div className="account-option-details">
        <p className="account-option-name">{account.name}</p>
        <p className="account-option-balance">{formatAmount(account.current_balance)} Ar</p>
      </div>
      <div className="account-option-check">
        {selected && <Check size={14} />}
      </div>
    </motion.div>
  );
};

// === ALERT ===
const Alert = ({ type, title, message, onClose }) => {
  return (
    <motion.div
      className={`form-alert ${type}`}
      initial={{ opacity: 0, y: -10 }}
      animate={{ opacity: 1, y: 0 }}
    >
      <div className="form-alert-icon">
        {type === 'error' ? <AlertCircle size={20} /> : <CheckCircle size={20} />}
      </div>
      <div className="form-alert-content">
        <p className="form-alert-title">{title}</p>
        {message && <p className="form-alert-message">{message}</p>}
      </div>
      {onClose && (
        <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'inherit' }}>
          <X size={18} />
        </button>
      )}
    </motion.div>
  );
};

// === MAIN COMPONENT ===
const AccountTransfer = () => {
  const navigate = useNavigate();

  // States
  const [accounts, setAccounts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [alert, setAlert] = useState(null);

  // Form data
  const [fromAccount, setFromAccount] = useState(null);
  const [toAccount, setToAccount] = useState(null);
  const [amount, setAmount] = useState('');
  const [description, setDescription] = useState('');

  // Errors
  const [errors, setErrors] = useState({});

  // Load accounts
  useEffect(() => {
    const loadAccounts = async () => {
      try {
        setLoading(true);
        const response = await accountService.getAll({ is_active: true, per_page: 100 });
        setAccounts(response.data || []);
      } catch (err) {
        console.error('Erreur chargement comptes:', err);
        setAlert({
          type: 'error',
          title: 'Erreur de chargement',
          message: 'Impossible de charger les comptes.'
        });
      } finally {
        setLoading(false);
      }
    };

    loadAccounts();
  }, []);

  // Validate form
  const validateForm = () => {
    const newErrors = {};

    if (!fromAccount) {
      newErrors.from = 'Sélectionnez un compte source';
    }

    if (!toAccount) {
      newErrors.to = 'Sélectionnez un compte destination';
    }

    if (!amount || parseFloat(amount) <= 0) {
      newErrors.amount = 'Le montant doit être supérieur à zéro';
    } else if (fromAccount && parseFloat(amount) > fromAccount.current_balance) {
      newErrors.amount = 'Solde insuffisant sur le compte source';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Handle submit
  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validateForm()) return;

    try {
      setSubmitting(true);
      setAlert(null);

      await accountService.transferBeetweenAccounts({
        from_account_id: fromAccount.id,
        to_account_id: toAccount.id,
        amount: parseFloat(amount),
        description: description || null
      });

      setAlert({
        type: 'success',
        title: 'Transfert effectué',
        message: `${formatAmount(amount)} Ar transférés avec succès.`
      });
      toast.success('Transfert effectué avec succès.');
      // Reset form after delay and redirect
      setTimeout(() => {
        navigate('/comptes');
      }, 2000);

    } catch (err) {
      console.error('Erreur transfert:', err);
      toast.error('Erreur lors du transfert.');
      if (err.response?.data?.errors) {
        const apiErrors = {};
        Object.entries(err.response.data.errors).forEach(([key, messages]) => {
          if (key === 'from_account_id') apiErrors.from = messages[0];
          else if (key === 'to_account_id') apiErrors.to = messages[0];
          else if (key === 'amount') apiErrors.amount = messages[0];
        });
        toast.error(apiErrors.from || apiErrors.to || apiErrors.amount || 'Erreur de validation.');
        setErrors(apiErrors);
      }

      setAlert({
        type: 'error',
        title: 'Erreur',
        message: err.response?.data?.message || 'Une erreur est survenue lors du transfert.'
      });
    } finally {
      setSubmitting(false);
    }
  };

  // Clear error when changing value
  const handleFromSelect = (account) => {
    setFromAccount(account);
    if (toAccount?.id === account.id) setToAccount(null);
    if (errors.from) setErrors(prev => ({ ...prev, from: null }));
  };

  const handleToSelect = (account) => {
    setToAccount(account);
    if (errors.to) setErrors(prev => ({ ...prev, to: null }));
  };

  const handleAmountChange = (value) => {
    setAmount(value);
    if (errors.amount) setErrors(prev => ({ ...prev, amount: null }));
  };

  // Loading state
  if (loading) {
    return (
      <div className="account-transfer-page">
        <motion.div
          className="form-loading"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
        >
          <RefreshCw className="loading-spinner" size={32} />
          <p>Chargement des comptes...</p>
        </motion.div>
      </div>
    );
  }

  // Calculate new balances preview
  const newFromBalance = fromAccount ? fromAccount.current_balance - (parseFloat(amount) || 0) : 0;
  const newToBalance = toAccount ? toAccount.current_balance + (parseFloat(amount) || 0) : 0;

  return (
    <div className="account-transfer-page">
      <motion.button
        className="back-button"
        onClick={() => navigate('/comptes')}
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        whileHover={{ x: -4 }}
      >
        <ArrowLeft size={18} />
        Retour aux comptes
      </motion.button>

      <motion.div
        className="transfer-header"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="transfer-header-icon">
          <ArrowDownUp size={28} />
        </div>
        <h1>Transfert entre comptes</h1>
        <p>Transférez des fonds d'un compte à un autre</p>
      </motion.div>

      <motion.div
        className="transfer-card"
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
      >
        {alert && (
          <Alert
            type={alert.type}
            title={alert.title}
            message={alert.message}
            onClose={() => setAlert(null)}
          />
        )}

        <form onSubmit={handleSubmit}>
          {/* Compte source */}
          <div className="account-selector-group">
            <label className="account-selector-label">
              Compte source <span className="required">*</span>
            </label>
            <div className="account-selector">
              <AnimatePresence>
                {accounts.map((account) => (
                  <AccountOption
                    key={account.id}
                    account={account}
                    selected={fromAccount?.id === account.id}
                    disabled={false}
                    onSelect={handleFromSelect}
                  />
                ))}
              </AnimatePresence>
            </div>
            {errors.from && (
              <p className="form-error">
                <AlertCircle size={14} />
                {errors.from}
              </p>
            )}
          </div>

          {/* Arrow */}
          <div className="transfer-arrow">
            <motion.div
              className="transfer-arrow-icon"
              animate={{ y: [0, 4, 0] }}
              transition={{ duration: 1.5, repeat: Infinity }}
            >
              <ArrowDownUp size={20} />
            </motion.div>
          </div>

          {/* Compte destination */}
          <div className="account-selector-group">
            <label className="account-selector-label">
              Compte destination <span className="required">*</span>
            </label>
            <div className="account-selector">
              <AnimatePresence>
                {accounts.map((account) => (
                  <AccountOption
                    key={account.id}
                    account={account}
                    selected={toAccount?.id === account.id}
                    disabled={fromAccount?.id === account.id}
                    onSelect={handleToSelect}
                  />
                ))}
              </AnimatePresence>
            </div>
            {errors.to && (
              <p className="form-error">
                <AlertCircle size={14} />
                {errors.to}
              </p>
            )}
          </div>

          {/* Montant */}
          <div className="amount-group">
            <label className="amount-label">
              Montant <span className="required">*</span>
            </label>
            <div className="amount-input-wrapper">
              <input
                type="number"
                className={`amount-input ${errors.amount ? 'error' : ''}`}
                value={amount}
                onChange={(e) => handleAmountChange(e.target.value)}
                placeholder="0"
                min="0"
                step="0.01"
              />
              <span className="amount-currency">Ar</span>
            </div>
            <div className="amount-hint">
              {errors.amount ? (
                <p className="form-error" style={{ marginTop: 0 }}>
                  <AlertCircle size={14} />
                  {errors.amount}
                </p>
              ) : (
                <span></span>
              )}
              {fromAccount && (
                <span className="amount-available">
                  Disponible: <span>{formatAmount(fromAccount.current_balance)} Ar</span>
                </span>
              )}
            </div>
          </div>

          {/* Description */}
          <div className="description-group">
            <label className="description-label">Description</label>
            <input
              type="text"
              className="description-input"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Motif du transfert (optionnel)"
            />
          </div>

          {/* Summary */}
          {fromAccount && toAccount && amount && parseFloat(amount) > 0 && (
            <motion.div
              className="transfer-summary"
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: 'auto' }}
            >
              <div className="transfer-summary-row">
                <span className="transfer-summary-label">Nouveau solde {fromAccount.name}</span>
                <span className="transfer-summary-value">{formatAmount(newFromBalance)} Ar</span>
              </div>
              <div className="transfer-summary-row">
                <span className="transfer-summary-label">Nouveau solde {toAccount.name}</span>
                <span className="transfer-summary-value highlight">{formatAmount(newToBalance)} Ar</span>
              </div>
            </motion.div>
          )}

          {/* Actions */}
          <div className="form-actions">
            <motion.button
              type="button"
              className="btn btn-secondary"
              onClick={() => navigate('/comptes')}
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
            >
              Annuler
            </motion.button>
            <motion.button
              type="submit"
              className="btn btn-primary"
              disabled={submitting || !fromAccount || !toAccount || !amount}
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
            >
              {submitting ? (
                <>
                  <RefreshCw size={18} className="loading-spinner" />
                  Transfert en cours...
                </>
              ) : (
                <>
                  <Send size={18} />
                  Effectuer le transfert
                </>
              )}
            </motion.button>
          </div>
        </form>
      </motion.div>
    </div>
  );
};

export default AccountTransfer;
