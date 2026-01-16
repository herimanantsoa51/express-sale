import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { motion } from 'motion/react';
import {
  ArrowLeft, Save, RefreshCw, Wallet, Landmark, Smartphone,
  Banknote, AlertCircle, CheckCircle, X
} from 'lucide-react';
import accountService from '../../services/accountService';
import '../../styles/AccountForm.css';

// === TYPE ICON CONFIG ===
const getTypeConfig = (code) => {
  const config = {
    'CASH': { icon: Banknote, className: 'cash' },
    'MOBILE_MONEY': { icon: Smartphone, className: 'mobile' },
    'BANK': { icon: Landmark, className: 'bank' }
  };
  return config[code] || { icon: Wallet, className: '' };
};

// === TYPE SELECTOR ===
const TypeSelector = ({ types, selectedId, onChange, error }) => {
  return (
    <div className="form-group">
      <label className="form-label">
        Type de compte <span className="required">*</span>
      </label>
      <div className="type-selector">
        {types.map((type) => {
          const config = getTypeConfig(type.code);
          const Icon = config.icon;
          const isSelected = selectedId === type.id;
          
          return (
            <motion.div
              key={type.id}
              className={`type-option ${isSelected ? 'selected' : ''}`}
              onClick={() => onChange(type.id)}
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
            >
              <div className={`type-option-icon ${config.className}`}>
                <Icon size={24} strokeWidth={1.5} />
              </div>
              <span className="type-option-label">{type.display_name}</span>
            </motion.div>
          );
        })}
      </div>
      {error && (
        <p className="form-error">
          <AlertCircle size={14} />
          {error}
        </p>
      )}
    </div>
  );
};

// === TOGGLE SWITCH ===
const ToggleSwitch = ({ checked, onChange, label, hint }) => {
  return (
    <div className="toggle-group">
      <div className="toggle-label">
        <span className="toggle-label-text">{label}</span>
        {hint && <span className="toggle-label-hint">{hint}</span>}
      </div>
      <div
        className={`toggle-switch ${checked ? 'active' : ''}`}
        onClick={() => onChange(!checked)}
      >
        <div className="toggle-switch-knob" />
      </div>
    </div>
  );
};

// === ALERT COMPONENT ===
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
        <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer' }}>
          <X size={18} />
        </button>
      )}
    </motion.div>
  );
};

// === MAIN COMPONENT ===
const AccountForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEditMode = Boolean(id);

  // States
  const [accountTypes, setAccountTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [alert, setAlert] = useState(null);
  
  // Form data
  const [formData, setFormData] = useState({
    account_type_id: '',
    name: '',
    account_number: '',
    initial_balance: '',
    notes: '',
    is_active: true
  });

  // Validation errors
  const [errors, setErrors] = useState({});

  // Load account types and existing account data
  useEffect(() => {
    const loadData = async () => {
      try {
        setLoading(true);
        
        // Load account types
        const typesResponse = await accountService.getTypes();
        setAccountTypes(typesResponse.data || []);

        // If edit mode, load account data
        if (isEditMode) {
          const accountResponse = await accountService.getById(id);
          const account = accountResponse.data;
          
          setFormData({
            account_type_id: account.account_type?.id || '',
            name: account.name || '',
            account_number: account.account_number || '',
            initial_balance: account.initial_balance || '',
            notes: account.notes || '',
            is_active: account.is_active ?? true
          });
        }
      } catch (err) {
        console.error('Erreur chargement:', err);
        setAlert({
          type: 'error',
          title: 'Erreur de chargement',
          message: 'Impossible de charger les données nécessaires.'
        });
      } finally {
        setLoading(false);
      }
    };

    loadData();
  }, [id, isEditMode]);

  // Handle input changes
  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    // Clear error when field changes
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: null }));
    }
  };

  // Validate form
  const validateForm = () => {
    const newErrors = {};

    if (!formData.account_type_id) {
      newErrors.account_type_id = 'Le type de compte est requis';
    }

    if (!formData.name.trim()) {
      newErrors.name = 'Le nom du compte est requis';
    }

    if (!formData.initial_balance && formData.initial_balance !== 0) {
      newErrors.initial_balance = 'Le solde initial est requis';
    } else if (parseFloat(formData.initial_balance) < 0) {
      newErrors.initial_balance = 'Le solde initial ne peut pas être négatif';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Handle form submission
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    try {
      setSubmitting(true);
      setAlert(null);

      const payload = {
        ...formData,
        initial_balance: parseFloat(formData.initial_balance) || 0
      };

      if (isEditMode) {
        await accountService.updateAccount(id, payload);
        setAlert({
          type: 'success',
          title: 'Compte mis à jour',
          message: 'Les modifications ont été enregistrées avec succès.'
        });
        // Navigate after short delay
        setTimeout(() => navigate(`/comptes/${id}`), 1500);
      } else {
        const response = await accountService.storeAccount(payload);
        setAlert({
          type: 'success',
          title: 'Compte créé',
          message: 'Le compte a été créé avec succès.'
        });
        // Navigate to new account
        setTimeout(() => navigate(`/comptes/${response.data?.id || ''}`), 1500);
      }
    } catch (err) {
      console.error('Erreur soumission:', err);
      
      // Handle validation errors from API
      if (err.response?.data?.errors) {
        const apiErrors = {};
        Object.entries(err.response.data.errors).forEach(([key, messages]) => {
          apiErrors[key] = Array.isArray(messages) ? messages[0] : messages;
        });
        setErrors(apiErrors);
      }
      
      setAlert({
        type: 'error',
        title: 'Erreur',
        message: err.response?.data?.message || 'Une erreur est survenue lors de l\'enregistrement.'
      });
    } finally {
      setSubmitting(false);
    }
  };

  // Loading state
  if (loading) {
    return (
      <div className="account-form-page">
        <motion.div
          className="form-loading"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
        >
          <RefreshCw className="loading-spinner" size={32} />
          <p>Chargement...</p>
        </motion.div>
      </div>
    );
  }

  return (
    <div className="account-form-page">
      <motion.button
        className="back-button"
        onClick={() => navigate(isEditMode ? `/comptes/${id}` : '/comptes')}
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        whileHover={{ x: -4 }}
      >
        <ArrowLeft size={18} />
        {isEditMode ? 'Retour au compte' : 'Retour aux comptes'}
      </motion.button>

      <motion.div
        className="form-header"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <h1>{isEditMode ? 'Modifier le compte' : 'Nouveau compte'}</h1>
        <p>{isEditMode ? 'Modifiez les informations du compte' : 'Créez un nouveau compte de trésorerie'}</p>
      </motion.div>

      <motion.div
        className="form-card"
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
          {/* Type de compte */}
          <TypeSelector
            types={accountTypes}
            selectedId={formData.account_type_id}
            onChange={(value) => handleChange('account_type_id', value)}
            error={errors.account_type_id}
          />

          {/* Nom du compte */}
          <div className="form-group">
            <label className="form-label">
              Nom du compte <span className="required">*</span>
            </label>
            <input
              type="text"
              className={`form-input ${errors.name ? 'error' : ''}`}
              value={formData.name}
              onChange={(e) => handleChange('name', e.target.value)}
              placeholder="Ex: Caisse principale, MVola Personnel..."
            />
            {errors.name && (
              <p className="form-error">
                <AlertCircle size={14} />
                {errors.name}
              </p>
            )}
          </div>

          {/* Numéro de compte */}
          <div className="form-group">
            <label className="form-label">Numéro de compte</label>
            <input
              type="text"
              className="form-input"
              value={formData.account_number}
              onChange={(e) => handleChange('account_number', e.target.value)}
              placeholder="Ex: 034 12 345 67, 00001-12345..."
            />
            <p className="form-hint">Optionnel - Numéro de téléphone ou numéro de compte bancaire</p>
          </div>

          {/* Solde initial */}
          <div className="form-group">
            <label className="form-label">
              Solde initial <span className="required">*</span>
            </label>
            <input
              type="number"
              className={`form-input ${errors.initial_balance ? 'error' : ''}`}
              value={formData.initial_balance}
              onChange={(e) => handleChange('initial_balance', e.target.value)}
              placeholder="0"
              min="0"
              step="0.01"
            />
            {errors.initial_balance ? (
              <p className="form-error">
                <AlertCircle size={14} />
                {errors.initial_balance}
              </p>
            ) : (
              <p className="form-hint">Montant en Ariary (Ar)</p>
            )}
          </div>

          {/* Notes */}
          <div className="form-group">
            <label className="form-label">Notes</label>
            <textarea
              className="form-textarea"
              value={formData.notes}
              onChange={(e) => handleChange('notes', e.target.value)}
              placeholder="Informations supplémentaires sur ce compte..."
              rows={3}
            />
          </div>

          {/* Statut actif */}
          <div className="form-group">
            <ToggleSwitch
              checked={formData.is_active}
              onChange={(value) => handleChange('is_active', value)}
              label="Compte actif"
              hint="Un compte inactif n'apparaîtra pas dans les sélections"
            />
          </div>

          {/* Actions */}
          <div className="form-actions">
            <motion.button
              type="button"
              className="btn btn-secondary"
              onClick={() => navigate(isEditMode ? `/comptes/${id}` : '/comptes')}
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
            >
              Annuler
            </motion.button>
            <motion.button
              type="submit"
              className="btn btn-primary"
              disabled={submitting}
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
            >
              {submitting ? (
                <>
                  <RefreshCw size={18} className="loading-spinner" />
                  Enregistrement...
                </>
              ) : (
                <>
                  <Save size={18} />
                  {isEditMode ? 'Enregistrer' : 'Créer le compte'}
                </>
              )}
            </motion.button>
          </div>
        </form>
      </motion.div>
    </div>
  );
};

export default AccountForm;
