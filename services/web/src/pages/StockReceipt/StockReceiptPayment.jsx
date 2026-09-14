// === CATEGORY MODAL (Create) ===
const CategoryModal = ({ isOpen, onClose, onSuccess }) => {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    icon: 'tag',
    is_active: true
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [alert, setAlert] = useState(null);

  const availableIcons = [
    'zap', 'droplet', 'wifi', 'building', 'users', 'truck',
    'package', 'megaphone', 'tool', 'file-text', 'shield', 'more-horizontal'
  ];

  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) setErrors(prev => ({ ...prev, [field]: null }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!formData.name.trim()) {
      setErrors({ name: 'Le nom est requis' });
      return;
    }

    try {
      setSubmitting(true);
      setAlert(null);
      await expenseService.storeExpenseCatgory(formData);
      setAlert({ type: 'success', message: 'Catégorie créée avec succès' });
      setTimeout(() => {
        onSuccess();
        onClose();
        setFormData({ name: '', description: '', icon: 'tag', is_active: true });
      }, 1000);
    } catch (err) {
      if (err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      }
      setAlert({ type: 'error', message: err.response?.data?.message || 'Erreur lors de la création' });
    } finally {
      setSubmitting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <motion.div
      className="modal-overlay"
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      onClick={onClose}
    >
      <motion.div
        className="modal-content"
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 20 }}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="modal-header">
          <h2 className="modal-title">Nouvelle catégorie</h2>
          <button className="modal-close" onClick={onClose}>
            <X size={18} />
          </button>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="modal-body">
            {alert && (
              <div className={`form-alert ${alert.type}`}>
                {alert.type === 'error' ? <AlertCircle size={18} /> : <CheckCircle size={18} />}
                <span>{alert.message}</span>
              </div>
            )}

            <div className="form-group">
              <label className="form-label">
                Nom <span className="required">*</span>
              </label>
              <input
                type="text"
                className={`form-input ${errors.name ? 'error' : ''}`}
                value={formData.name}
                onChange={(e) => handleChange('name', e.target.value)}
                placeholder="Ex: Transport, Électricité..."
              />
              {errors.name && (
                <p className="form-error"><AlertCircle size={14} />{errors.name}</p>
              )}
            </div>

            <div className="form-group">
              <label className="form-label">Description</label>
              <textarea
                className="form-textarea"
                value={formData.description}
                onChange={(e) => handleChange('description', e.target.value)}
                placeholder="Description de la catégorie..."
                rows={2}
              />
            </div>

            <div className="form-group">
              <label className="form-label">Icône</label>
              <div className="icon-selector">
                {availableIcons.map((icon) => {
                  const IconComp = getCategoryIcon(icon);
                  return (
                    <div
                      key={icon}
                      className={`icon-option ${formData.icon === icon ? 'selected' : ''}`}
                      onClick={() => handleChange('icon', icon)}
                    >
                      <IconComp size={18} />
                    </div>
                  );
                })}
              </div>
            </div>

            <div className="form-group">
              <div className="toggle-group">
                <span className="toggle-label-text">Catégorie active</span>
                <div
                  className={`toggle-switch ${formData.is_active ? 'active' : ''}`}
                  onClick={() => handleChange('is_active', !formData.is_active)}
                >
                  <div className="toggle-switch-knob" />
                </div>
              </div>
            </div>
          </div>

          <div className="modal-footer">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              Annuler
            </button>
            <button type="submit" className="btn btn-primary" disabled={submitting}>
              {submitting ? (
                <><RefreshCw size={18} className="loading-spinner" /> Création...</>
              ) : (
                <><Plus size={18} /> Créer</>
              )}
            </button>
          </div>
        </form>
      </motion.div>
    </motion.div>
  );
};import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'motion/react';
import {
  ArrowLeft, ArrowRight, Check, RefreshCw, Wallet, Landmark, Smartphone,
  Banknote, AlertCircle, CheckCircle, X, Calendar, User, FileText,
  Truck, Tag, Receipt, Plus, DollarSign, Package, Zap, Droplet, Wifi,
  Building, Users, Megaphone, Toolbox, Shield, MoreHorizontal
} from 'lucide-react';
import stockReceiptService from '../../services/stockReceiptService';
import expenseService from '../../services/expenseService';
import accountService from '../../services/accountService';
import '../../styles/StockReceiptPayment.css';

// === ICON MAPS ===
const accountTypeIcons = {
  'CASH': Banknote,
  'MOBILE_MONEY': Smartphone,
  'BANK': Landmark
};

const categoryIcons = {
  'zap': Zap,
  'droplet': Droplet,
  'wifi': Wifi,
  'building': Building,
  'users': Users,
  'truck': Truck,
  'package': Package,
  'megaphone': Megaphone,
  'tool': Toolbox,
  'file-text': FileText,
  'shield': Shield,
  'more-horizontal': MoreHorizontal,
  'tag': Tag,
};

const getAccountIcon = (code) => accountTypeIcons[code] || Wallet;
const getCategoryIcon = (iconName) => categoryIcons[iconName] || Tag;

// === HELPERS ===
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
};

// === STEP INDICATOR ===
const StepIndicator = ({ currentStep, steps }) => {
  return (
    <div className="step-indicator">
      {steps.map((step, index) => {
        const stepNumber = index + 1;
        const isActive = currentStep === stepNumber;
        const isCompleted = currentStep > stepNumber;
        
        return (
          <div key={step.id} className="step-indicator-item">
            <div className={`step-circle ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}`}>
              {isCompleted ? <Check size={14} /> : stepNumber}
            </div>
            <span className={`step-label ${isActive ? 'active' : ''}`}>{step.label}</span>
            {index < steps.length - 1 && <div className={`step-line ${isCompleted ? 'completed' : ''}`} />}
          </div>
        );
      })}
    </div>
  );
};

// === STEP 1: ACCOUNT SELECTION ===
const StepAccount = ({ accounts, selectedId, onSelect, loading }) => {
  if (loading) {
    return (
      <div className="step-loading">
        <RefreshCw className="loading-spinner" size={24} />
        <p>Chargement des comptes...</p>
      </div>
    );
  }

  return (
    <div className="step-content">
      <div className="step-header">
        <h2>Choisir le compte source</h2>
        <p>Sélectionnez le compte à débiter pour ce paiement</p>
      </div>
      
      <div className="selection-grid">
        {accounts.map((account) => {
          const Icon = getAccountIcon(account.account_type?.code);
          const isSelected = selectedId === account.id;
          
          return (
            <motion.div
              key={account.id}
              className={`selection-card ${isSelected ? 'selected' : ''}`}
              onClick={() => onSelect(account.id)}
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
            >
              <div className={`selection-card-icon ${account.account_type?.code?.toLowerCase() || ''}`}>
                <Icon size={24} />
              </div>
              <div className="selection-card-content">
                <h3>{account.name}</h3>
                <p className="selection-card-type">{account.account_type?.display_name}</p>
                {account.account_number && (
                  <p className="selection-card-number">{account.account_number}</p>
                )}
              </div>
              <div className="selection-card-balance">
                <span className="balance-label">Solde</span>
                <span className="balance-value">{formatAmount(account.current_balance)} Ar</span>
              </div>
              {isSelected && (
                <div className="selection-check">
                  <Check size={16} />
                </div>
              )}
            </motion.div>
          );
        })}
      </div>
      
      {accounts.length === 0 && (
        <div className="step-empty">
          <Wallet size={32} />
          <p>Aucun compte actif disponible</p>
        </div>
      )}
    </div>
  );
};

// === STEP 2: PAYMENT TYPE SELECTION ===
const StepPaymentType = ({ selectedType, onSelect, freightForwarder, receipt }) => {
  // Vérifier si le transitaire a déjà été payé
  const freightAlreadyPaid = receipt?.transactions?.some(t => {
    const hasFreightForwarder = t.freight_forwarder?.id === receipt?.freight_forwarder?.id;
    const notCancelled = !t.status?.is_cancelled && !t.reversed_transaction_id;
    return hasFreightForwarder && notCancelled;
  }) || false;
  
  const paymentTypes = [
    {
      id: 'freight',
      label: 'Paiement transitaire',
      description: freightForwarder 
        ? `Payer ${freightForwarder.name}` 
        : 'Paiement au transitaire',
      icon: Truck,
      color: 'primary',
      disabled: !freightForwarder || freightAlreadyPaid,
      disabledReason: freightAlreadyPaid 
        ? 'Transitaire déjà payé' 
        : 'Aucun transitaire associé'
    },
    {
      id: 'other',
      label: 'Autre dépense',
      description: 'Frais divers liés à cette réception',
      icon: Tag,
      color: 'secondary'
    }
  ];

  return (
    <div className="step-content">
      <div className="step-header">
        <h2>Type de paiement</h2>
        <p>Choisissez le type de dépense pour cette réception</p>
      </div>
      
      <div className="payment-type-grid">
        {paymentTypes.map((type) => {
          const Icon = type.icon;
          const isSelected = selectedType === type.id;
          
          return (
            <motion.div
              key={type.id}
              className={`payment-type-card ${isSelected ? 'selected' : ''} ${type.disabled ? 'disabled' : ''}`}
              onClick={() => !type.disabled && onSelect(type.id)}
              whileHover={!type.disabled ? { scale: 1.02 } : {}}
              whileTap={!type.disabled ? { scale: 0.98 } : {}}
            >
              <div className={`payment-type-icon ${type.color}`}>
                <Icon size={28} />
              </div>
              <div className="payment-type-content">
                <h3>{type.label}</h3>
                <p>{type.description}</p>
                {type.disabled && (
                  <span className="payment-type-disabled-label">
                    <AlertCircle size={14} />
                    {type.disabledReason}
                  </span>
                )}
              </div>
              {isSelected && (
                <div className="payment-type-check">
                  <Check size={16} />
                </div>
              )}
            </motion.div>
          );
        })}
      </div>
    </div>
  );
};

// === STEP 3: DETAILS ===
const StepDetails = ({ formData, onChange, errors, paymentType, categories, loadingCategories, onAddCategory }) => {
  const today = new Date().toISOString().split('T')[0];
  const isOtherType = paymentType === 'other';
  
  return (
    <div className="step-content">
      <div className="step-header">
        <h2>Détails du paiement</h2>
        <p>Renseignez les informations de la transaction</p>
      </div>
      
      <div className="form-grid">
        {/* Montant */}
        <div className="form-group form-group-full">
          <label className="form-label">
            Montant <span className="required">*</span>
          </label>
          <div className="amount-input-wrapper">
            <input
              type="number"
              className={`form-input amount-input ${errors.amount ? 'error' : ''}`}
              value={formData.amount}
              onChange={(e) => onChange('amount', e.target.value)}
              placeholder="0"
              min="0"
              step="0.01"
            />
            <span className="amount-currency">Ar</span>
          </div>
          {errors.amount && (
            <p className="form-error"><AlertCircle size={14} />{errors.amount}</p>
          )}
        </div>

        {/* Date de transaction */}
        <div className="form-group">
          <label className="form-label">
            Date de transaction <span className="required">*</span>
          </label>
          <div className="input-with-icon">
            <Calendar size={18} className="input-icon" />
            <input
              type="date"
              className={`form-input ${errors.transaction_date ? 'error' : ''}`}
              value={formData.transaction_date}
              onChange={(e) => onChange('transaction_date', e.target.value)}
              max={today}
            />
          </div>
          {errors.transaction_date && (
            <p className="form-error"><AlertCircle size={14} />{errors.transaction_date}</p>
          )}
        </div>

        {/* Destinataire (pour type "other" uniquement) */}
        {isOtherType && (
          <div className="form-group">
            <label className="form-label">
              Destinataire / Bénéficiaire <span className="required">*</span>
            </label>
            <div className="input-with-icon">
              <User size={18} className="input-icon" />
              <input
                type="text"
                className={`form-input ${errors.recipient_name ? 'error' : ''}`}
                value={formData.recipient_name}
                onChange={(e) => onChange('recipient_name', e.target.value)}
                placeholder="Ex: JIRAMA, Fournisseur X..."
              />
            </div>
            {errors.recipient_name && (
              <p className="form-error"><AlertCircle size={14} />{errors.recipient_name}</p>
            )}
          </div>
        )}

        {/* Catégorie (pour type "other" uniquement) */}
        {isOtherType && (
          <div className="form-group form-group-full">
            <div className="form-label-with-action">
              <label className="form-label">
                Catégorie de dépense <span className="required">*</span>
              </label>
              <button
                type="button"
                className="add-category-link"
                onClick={onAddCategory}
              >
                <Plus size={14} />
                Nouvelle catégorie
              </button>
            </div>
            {loadingCategories ? (
              <div className="form-input-loading">
                <RefreshCw size={16} className="loading-spinner" />
                <span>Chargement...</span>
              </div>
            ) : (
              <>
                <div className="category-selector-grid">
                  {categories.map((cat) => {
                    const Icon = getCategoryIcon(cat.icon);
                    const isSelected = formData.expense_category_id === cat.id;
                    
                    return (
                      <div
                        key={cat.id}
                        className={`category-selector-card ${isSelected ? 'selected' : ''}`}
                        onClick={() => onChange('expense_category_id', cat.id)}
                      >
                        <div className="category-selector-icon">
                          <Icon size={18} />
                        </div>
                        <span className="category-selector-name">{cat.name}</span>
                        {isSelected && (
                          <div className="category-selector-check">
                            <Check size={14} />
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
                {errors.expense_category_id && (
                  <p className="form-error"><AlertCircle size={14} />{errors.expense_category_id}</p>
                )}
              </>
            )}
          </div>
        )}

        {/* Notes */}
        <div className="form-group form-group-full">
          <label className="form-label">Notes</label>
          <textarea
            className="form-textarea"
            value={formData.notes}
            onChange={(e) => onChange('notes', e.target.value)}
            placeholder="Détails supplémentaires sur ce paiement..."
            rows={3}
          />
        </div>
      </div>
    </div>
  );
};

// === STEP 4: CONFIRMATION ===
const StepConfirmation = ({ formData, account, paymentType, receipt, selectedCategory }) => {
  const AccountIcon = account ? getAccountIcon(account.account_type?.code) : Wallet;
  const PaymentIcon = paymentType === 'freight' ? Truck : Tag;
  
  const recipientName = paymentType === 'freight' 
    ? receipt?.freight_forwarder?.name 
    : formData.recipient_name;
  
  return (
    <div className="step-content">
      <div className="step-header">
        <h2>Confirmation</h2>
        <p>Vérifiez les informations avant de valider</p>
      </div>
      
      <div className="confirmation-card">
        <div className="confirmation-amount">
          <span className="confirmation-amount-label">Montant du paiement</span>
          <span className="confirmation-amount-value">-{formatAmount(formData.amount)} Ar</span>
        </div>
        
        <div className="confirmation-details">
          <div className="confirmation-row">
            <div className="confirmation-item">
              <span className="confirmation-item-label">Réception</span>
              <div className="confirmation-item-value">
                <Package size={16} />
                <span>{receipt?.receipt_number || 'N/A'}</span>
              </div>
            </div>
            
            <div className="confirmation-item">
              <span className="confirmation-item-label">Type de paiement</span>
              <div className="confirmation-item-value">
                <PaymentIcon size={16} />
                <span>{paymentType === 'freight' ? 'Transitaire' : 'Autre dépense'}</span>
              </div>
            </div>
          </div>
          
          <div className="confirmation-row">
            <div className="confirmation-item">
              <span className="confirmation-item-label">Compte source</span>
              <div className="confirmation-item-value">
                <AccountIcon size={16} />
                <span>{account?.name}</span>
              </div>
              <span className="confirmation-item-sub">
                Solde actuel: {formatAmount(account?.current_balance)} Ar
              </span>
            </div>
            
            <div className="confirmation-item">
              <span className="confirmation-item-label">Destinataire</span>
              <div className="confirmation-item-value">
                <User size={16} />
                <span>{recipientName || 'N/A'}</span>
              </div>
            </div>
          </div>
          
          <div className="confirmation-row">
            <div className="confirmation-item">
              <span className="confirmation-item-label">Date</span>
              <div className="confirmation-item-value">
                <Calendar size={16} />
                <span>{new Date(formData.transaction_date).toLocaleDateString('fr-FR', {
                  day: '2-digit',
                  month: 'long',
                  year: 'numeric'
                })}</span>
              </div>
            </div>
            
            {paymentType === 'other' && selectedCategory && (
              <div className="confirmation-item">
                <span className="confirmation-item-label">Catégorie</span>
                <div className="confirmation-item-value">
                  <Tag size={16} />
                  <span>{selectedCategory.name}</span>
                </div>
              </div>
            )}
          </div>
          
          {formData.notes && (
            <div className="confirmation-notes">
              <span className="confirmation-item-label">Notes</span>
              <p>{formData.notes}</p>
            </div>
          )}
        </div>
        
        <div className="confirmation-warning">
          <AlertCircle size={16} />
          <span>Cette opération débitera le compte de <strong>{formatAmount(formData.amount)} Ar</strong></span>
        </div>
      </div>
    </div>
  );
};

// === MAIN COMPONENT ===
const StockReceiptPayment = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  
  // Steps configuration
  const steps = [
    { id: 'account', label: 'Compte' },
    { id: 'type', label: 'Type' },
    { id: 'details', label: 'Détails' },
    { id: 'confirm', label: 'Confirmation' }
  ];
  
  // States
  const [currentStep, setCurrentStep] = useState(1);
  const [receipt, setReceipt] = useState(null);
  const [accounts, setAccounts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loadingReceipt, setLoadingReceipt] = useState(true);
  const [loadingAccounts, setLoadingAccounts] = useState(true);
  const [loadingCategories, setLoadingCategories] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [alert, setAlert] = useState(null);
  const [errors, setErrors] = useState({});
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  
  // Form data
  const [formData, setFormData] = useState({
    account_id: '',
    payment_type: '',
    amount: '',
    transaction_date: new Date().toISOString().split('T')[0],
    recipient_name: '',
    expense_category_id: '',
    notes: ''
  });

  // Load receipt
  useEffect(() => {
    const loadReceipt = async () => {
      try {
        setLoadingReceipt(true);
        const response = await stockReceiptService.getById(id);
        setReceipt(response.data);
        
        // Vérifier si le transitaire a déjà été payé
        const freightPaid = hasFreightPayment(response.data);
        console.log('Freight already paid:', freightPaid); // Debug
        
        // Si le transitaire a déjà été payé, passer directement à "other"
        if (response.data?.freight_forwarder && freightPaid) {
          setFormData(prev => ({ ...prev, payment_type: 'other' }));
        }
      } catch (err) {
        console.error('Erreur chargement réception:', err);
        setAlert({ type: 'error', message: 'Impossible de charger la réception' });
      } finally {
        setLoadingReceipt(false);
      }
    };
    loadReceipt();
  }, [id]);
  
  // Fonction pour vérifier si le transitaire a déjà été payé
  const hasFreightPayment = (receiptData) => {
    if (!receiptData?.transactions || receiptData.transactions.length === 0) return false;
    if (!receiptData?.freight_forwarder?.id) return false;
    
    // Vérifier s'il existe une transaction avec freight_forwarder correspondant
    const hasPaid = receiptData.transactions.some(t => {
      // La transaction doit avoir un freight_forwarder avec le même ID
      const hasFreightForwarder = t.freight_forwarder?.id === receiptData.freight_forwarder.id;
      // Et ne doit pas être annulée
      const notCancelled = !t.status?.is_cancelled && !t.reversed_transaction_id;
      
      console.log('Checking transaction:', {
        transactionId: t.id,
        hasFreightForwarder,
        notCancelled,
        freightForwarderId: t.freight_forwarder?.id,
        expectedId: receiptData.freight_forwarder.id
      });
      
      return hasFreightForwarder && notCancelled;
    });
    
    return hasPaid;
  };

  // Load accounts
  useEffect(() => {
    const loadAccounts = async () => {
      try {
        setLoadingAccounts(true);
        const response = await accountService.getAll({ is_active: true, per_page: 100 });
        setAccounts(response.data || []);
      } catch (err) {
        console.error('Erreur chargement comptes:', err);
        setAlert({ type: 'error', message: 'Impossible de charger les comptes' });
      } finally {
        setLoadingAccounts(false);
      }
    };
    loadAccounts();
  }, []);

  // Load categories when payment_type is 'other'
  const loadCategories = useCallback(async () => {
    if (formData.payment_type !== 'other') return;
    
    try {
      setLoadingCategories(true);
      const response = await expenseService.getExpenseCategories();
      setCategories((response.data || []).filter(cat => cat.is_active));
    } catch (err) {
      console.error('Erreur chargement catégories:', err);
      setAlert({ type: 'error', message: 'Impossible de charger les catégories' });
    } finally {
      setLoadingCategories(false);
    }
  }, [formData.payment_type]);

  useEffect(() => {
    loadCategories();
  }, [loadCategories]);

  // Get selected items
  const selectedAccount = accounts.find(a => a.id === formData.account_id);
  const selectedCategory = categories.find(c => c.id === formData.expense_category_id);

  // Handle form changes
  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: null }));
    }
  };

  const handlePaymentTypeSelect = (type) => {
    setFormData(prev => ({
      ...prev,
      payment_type: type,
      recipient_name: '',
      expense_category_id: ''
    }));
  };

  // Validate current step
  const validateStep = () => {
    const newErrors = {};
    
    if (currentStep === 1 && !formData.account_id) {
      setAlert({ type: 'error', message: 'Veuillez sélectionner un compte' });
      return false;
    }
    
    if (currentStep === 2 && !formData.payment_type) {
      setAlert({ type: 'error', message: 'Veuillez sélectionner un type de paiement' });
      return false;
    }
    
    if (currentStep === 3) {
      if (!formData.amount || parseFloat(formData.amount) <= 0) {
        newErrors.amount = 'Le montant doit être supérieur à zéro';
      }
      if (!formData.transaction_date) {
        newErrors.transaction_date = 'La date est requise';
      }
      
      if (formData.payment_type === 'other') {
        if (!formData.recipient_name?.trim()) {
          newErrors.recipient_name = 'Le destinataire est requis';
        }
        if (!formData.expense_category_id) {
          newErrors.expense_category_id = 'La catégorie est requise';
        }
      }
      
      if (Object.keys(newErrors).length > 0) {
        setErrors(newErrors);
        return false;
      }
      
      if (selectedAccount && parseFloat(formData.amount) > selectedAccount.current_balance) {
        setAlert({ 
          type: 'error', 
          message: `Solde insuffisant. Le compte dispose de ${formatAmount(selectedAccount.current_balance)} Ar` 
        });
        return false;
      }
    }
    
    setAlert(null);
    return true;
  };

  // Navigation
  const handleNext = () => {
    if (validateStep()) {
      setCurrentStep(prev => Math.min(prev + 1, steps.length));
    }
  };

  const handlePrev = () => {
    setAlert(null);
    setCurrentStep(prev => Math.max(prev - 1, 1));
  };

  // Submit
  const handleSubmit = async () => {
    if (!validateStep()) return;
    
    try {
      setSubmitting(true);
      setAlert(null);
      
      const payload = {
        account_id: formData.account_id,
        payment_type: formData.payment_type,
        amount: parseFloat(formData.amount),
        transaction_date: formData.transaction_date,
        notes: formData.notes || null
      };
      
      // Add fields specific to 'other' type
      if (formData.payment_type === 'other') {
        payload.recipient_name = formData.recipient_name;
        payload.expense_category_id = formData.expense_category_id;
      }
      
      await stockReceiptService.recordPayment(id, payload);
      
      setAlert({ type: 'success', message: 'Paiement enregistré avec succès!' });
      
      setTimeout(() => {
        navigate(`/reapprovisionnements/${id}`);
      }, 1500);
      
    } catch (err) {
      console.error('Erreur soumission:', err);
      
      if (err.response?.data?.errors) {
        const apiErrors = {};
        Object.entries(err.response.data.errors).forEach(([key, messages]) => {
          apiErrors[key] = Array.isArray(messages) ? messages[0] : messages;
        });
        setErrors(apiErrors);
      }
      
      setAlert({ 
        type: 'error', 
        message: err.response?.data?.message || 'Erreur lors de l\'enregistrement du paiement' 
      });
    } finally {
      setSubmitting(false);
    }
  };

  // Render current step
  const renderStep = () => {
    switch (currentStep) {
      case 1:
        return (
          <StepAccount
            accounts={accounts}
            selectedId={formData.account_id}
            onSelect={(id) => handleChange('account_id', id)}
            loading={loadingAccounts}
          />
        );
      case 2:
        return (
          <StepPaymentType
            selectedType={formData.payment_type}
            onSelect={handlePaymentTypeSelect}
            freightForwarder={receipt?.freight_forwarder}
            receipt={receipt}
          />
        );
      case 3:
        return (
          <StepDetails
            formData={formData}
            onChange={handleChange}
            errors={errors}
            paymentType={formData.payment_type}
            categories={categories}
            loadingCategories={loadingCategories}
            onAddCategory={() => setShowCategoryModal(true)}
          />
        );
      case 4:
        return (
          <StepConfirmation
            formData={formData}
            account={selectedAccount}
            paymentType={formData.payment_type}
            receipt={receipt}
            selectedCategory={selectedCategory}
          />
        );
      default:
        return null;
    }
  };

  if (loadingReceipt) {
    return (
      <div className="payment-page-loading">
        <RefreshCw className="loading-spinner" size={32} />
        <p>Chargement...</p>
      </div>
    );
  }

  return (
    <div className="stock-receipt-payment-page">
      {/* Back button */}
      <motion.button
        className="back-button"
        onClick={() => navigate(`/reapprovisionnements/${id}`)}
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        whileHover={{ x: -4 }}
      >
        <ArrowLeft size={18} />
        Retour à la réception
      </motion.button>

      {/* Header */}
      <motion.div
        className="form-page-header"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="form-page-header-icon">
          <DollarSign size={28} />
        </div>
        <div>
          <h1>Paiement pour {receipt?.receipt_number}</h1>
          <p>Enregistrer un paiement pour cette réception</p>
        </div>
      </motion.div>

      {/* Step Indicator */}
      <motion.div
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
      >
        <StepIndicator currentStep={currentStep} steps={steps} />
      </motion.div>

      {/* Alert */}
      <AnimatePresence>
        {alert && (
          <motion.div
            className={`form-alert ${alert.type}`}
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
          >
            {alert.type === 'error' ? <AlertCircle size={18} /> : <CheckCircle size={18} />}
            <span>{alert.message}</span>
            <button className="alert-close" onClick={() => setAlert(null)}>
              <X size={16} />
            </button>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Form Card */}
      <motion.div
        className="form-card"
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
      >
        <AnimatePresence mode="wait">
          <motion.div
            key={currentStep}
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -20 }}
            transition={{ duration: 0.2 }}
          >
            {renderStep()}
          </motion.div>
        </AnimatePresence>

        {/* Navigation */}
        <div className="form-navigation">
          <button
            type="button"
            className="btn btn-secondary"
            onClick={handlePrev}
            disabled={currentStep === 1}
          >
            <ArrowLeft size={18} />
            Précédent
          </button>
          
          {currentStep < steps.length ? (
            <button
              type="button"
              className="btn btn-primary"
              onClick={handleNext}
            >
              Suivant
              <ArrowRight size={18} />
            </button>
          ) : (
            <button
              type="button"
              className="btn btn-success"
              onClick={handleSubmit}
              disabled={submitting}
            >
              {submitting ? (
                <>
                  <RefreshCw size={18} className="loading-spinner" />
                  Enregistrement...
                </>
              ) : (
                <>
                  <Check size={18} />
                  Confirmer le paiement
                </>
              )}
            </button>
          )}
        </div>
      </motion.div>

      {/* Category Modal */}
      <AnimatePresence>
        {showCategoryModal && (
          <CategoryModal
            isOpen={showCategoryModal}
            onClose={() => setShowCategoryModal(false)}
            onSuccess={loadCategories}
          />
        )}
      </AnimatePresence>
    </div>
  );
};

export default StockReceiptPayment;