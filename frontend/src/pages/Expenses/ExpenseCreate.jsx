import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'motion/react';
import {
  ArrowLeft, ArrowRight, Check, RefreshCw, Wallet, Landmark, Smartphone,
  Banknote, AlertCircle, CheckCircle, X, Calendar, User, FileText,
  Zap, Droplet, Wifi, Building, Users, Truck, Package,
  Megaphone, Toolbox, Shield, MoreHorizontal, Tag, Receipt, Plus
} from 'lucide-react';
import expenseService from '../../services/expenseService';
import accountService from '../../services/accountService';
import '../../styles/ExpenseForm.css';
import { useLocation } from 'react-router-dom';
import plannedExpenseService from '../../services/plannedExpenseService';

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
        <p>Sélectionnez le compte à débiter pour cette dépense</p>
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

// === STEP 2: CATEGORY SELECTION ===
  const StepCategory = ({ categories, selectedId, onSelect, loading, onAddCategory, disabled }) => {
    if (loading) {
      return (
        <div className="step-loading">
          <RefreshCw className="loading-spinner" size={24} />
          <p>Chargement des catégories...</p>
        </div>
      );
    }

    if (disabled) {
      const selectedCategory = categories.find(c => c.id === selectedId);
      const Icon = selectedCategory ? getCategoryIcon(selectedCategory.icon) : Tag;
      
      return (
        <div className="step-content">
          <div className="step-header">
            <h2>Type de dépense</h2>
            <p>Catégorie pré-définie par la dépense planifiée</p>
          </div>
          
          <div className="category-grid">
            <div className="category-card selected" style={{ pointerEvents: 'none' }}>
              <div className="category-card-icon">
                <Icon size={20} />
              </div>
              <span className="category-card-name">{selectedCategory?.name}</span>
              <div className="category-check">
                <Check size={14} />
              </div>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="step-content">
        <div className="step-header">
          <h2>Type de dépense</h2>
          <p>Choisissez la catégorie de cette dépense</p>
        </div>
        
        <div className="category-grid">
          {categories.map((category) => {
            const Icon = getCategoryIcon(category.icon);
            const isSelected = selectedId === category.id;
            
            return (
              <motion.div
                key={category.id}
                className={`category-card ${isSelected ? 'selected' : ''}`}
                onClick={() => onSelect(category.id)}
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
              >
                <div className="category-card-icon">
                  <Icon size={20} />
                </div>
                <span className="category-card-name">{category.name}</span>
                {isSelected && (
                  <div className="category-check">
                    <Check size={14} />
                  </div>
                )}
              </motion.div>
            );
          })}
          
          <motion.div
            className="category-card add-category-card"
            onClick={onAddCategory}
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
          >
            <div className="category-card-icon add-icon">
              <Plus size={20} />
            </div>
            <span className="category-card-name">Nouvelle catégorie</span>
          </motion.div>
        </div>
        
        {categories.length === 0 && (
          <div className="step-empty">
            <Tag size={32} />
            <p>Aucune catégorie disponible</p>
            <button className="btn btn-primary btn-sm" onClick={onAddCategory}>
              <Plus size={16} /> Créer une catégorie
            </button>
          </div>
        )}
      </div>
    );
  };
// === STEP 3: DETAILS ===
const StepDetails = ({ formData, onChange, errors }) => {
  const today = new Date().toISOString().split('T')[0];
  
  return (
    <div className="step-content">
      <div className="step-header">
        <h2>Détails de la dépense</h2>
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

        {/* Destinataire */}
        <div className="form-group">
          <label className="form-label">Destinataire / Bénéficiaire</label>
          <div className="input-with-icon">
            <User size={18} className="input-icon" />
            <input
              type="text"
              className="form-input"
              value={formData.recipient_name}
              onChange={(e) => onChange('recipient_name', e.target.value)}
              placeholder="Ex: JIRAMA, Fournisseur X..."
            />
          </div>
        </div>

        {/* Notes */}
        <div className="form-group form-group-full">
          <label className="form-label">Notes</label>
          <textarea
            className="form-textarea"
            value={formData.notes}
            onChange={(e) => onChange('notes', e.target.value)}
            placeholder="Détails supplémentaires sur cette dépense..."
            rows={3}
          />
        </div>
      </div>
    </div>
  );
};

// === STEP 4: CONFIRMATION ===
const StepConfirmation = ({ formData, account, category }) => {
  const AccountIcon = account ? getAccountIcon(account.account_type?.code) : Wallet;
  const CategoryIcon = category ? getCategoryIcon(category.icon) : Tag;
  
  return (
    <div className="step-content">
      <div className="step-header">
        <h2>Confirmation</h2>
        <p>Vérifiez les informations avant de valider</p>
      </div>
      
      <div className="confirmation-card">
        <div className="confirmation-amount">
          <span className="confirmation-amount-label">Montant de la dépense</span>
          <span className="confirmation-amount-value">-{formatAmount(formData.amount)} Ar</span>
        </div>
        
        <div className="confirmation-details">
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
              <span className="confirmation-item-label">Catégorie</span>
              <div className="confirmation-item-value">
                <CategoryIcon size={16} />
                <span>{category?.name}</span>
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
            
            {formData.recipient_name && (
              <div className="confirmation-item">
                <span className="confirmation-item-label">Destinataire</span>
                <div className="confirmation-item-value">
                  <User size={16} />
                  <span>{formData.recipient_name}</span>
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
const ExpenseCreate = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const searchParams = new URLSearchParams(location.search);
  const plannedExpenseId = searchParams.get('plannedExpenseId');
  
  // Steps configuration
  const steps = [
    { id: 'account', label: 'Compte' },
    { id: 'category', label: 'Catégorie' },
    { id: 'details', label: 'Détails' },
    { id: 'confirm', label: 'Confirmation' }
  ];
  
  // States
  const [currentStep, setCurrentStep] = useState(1);
  const [accounts, setAccounts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loadingAccounts, setLoadingAccounts] = useState(true);
  const [loadingCategories, setLoadingCategories] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [alert, setAlert] = useState(null);
  const [errors, setErrors] = useState({});
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [plannedExpense, setPlannedExpense] = useState(null);
  const [loadingPlannedExpense, setLoadingPlannedExpense] = useState(false);
  
  // Form data
  const [formData, setFormData] = useState({
    account_id: '',
    expense_category_id: '',
    amount: '',
    transaction_date: new Date().toISOString().split('T')[0],
    recipient_name: '',
    notes: '',
    planned_expense_id: null // AJOUTER CETTE LIGNE
  });

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

  // Load categories
  const loadCategories = useCallback(async () => {
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
  }, []);

  useEffect(() => {
    loadCategories();
  }, [loadCategories]);
  // Load planned expense if ID is provided
  useEffect(() => {
    const loadPlannedExpense = async () => {
      if (!plannedExpenseId) return;
      
      try {
        setLoadingPlannedExpense(true);
        const response = await plannedExpenseService.getById(plannedExpenseId);
        const planned = response.data;
        
        setPlannedExpense(planned);
        
        // Pré-remplir le formulaire
        setFormData(prev => ({
          ...prev,
          expense_category_id: planned.expense_category.id,
          amount: planned.estimated_amount.toString(),
          recipient_name: planned.recipient_name || '',
          planned_expense_id: planned.id
        }));
        
        // Passer directement à l'étape de sélection du compte
        // La catégorie est déjà définie
      } catch (err) {
        console.error('Erreur chargement dépense planifiée:', err);
        setAlert({ 
          type: 'error', 
          message: 'Impossible de charger la dépense planifiée' 
        });
      } finally {
        setLoadingPlannedExpense(false);
      }
    };
    
    loadPlannedExpense();
  }, [plannedExpenseId]);

  // Get selected account and category
  const selectedAccount = accounts.find(a => a.id === formData.account_id);
  const selectedCategory = categories.find(c => c.id === formData.expense_category_id);

  // Handle form changes
  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: null }));
    }
  };

  // Validate current step
  const validateStep = () => {
    const newErrors = {};
    
    if (currentStep === 1 && !formData.account_id) {
      setAlert({ type: 'error', message: 'Veuillez sélectionner un compte' });
      return false;
    }
    
    if (currentStep === 2 && !formData.expense_category_id) {
      setAlert({ type: 'error', message: 'Veuillez sélectionner une catégorie' });
      return false;
    }
    
    if (currentStep === 3) {
      if (!formData.amount || parseFloat(formData.amount) <= 0) {
        newErrors.amount = 'Le montant doit être supérieur à zéro';
      }
      if (!formData.transaction_date) {
        newErrors.transaction_date = 'La date est requise';
      }
      
      if (Object.keys(newErrors).length > 0) {
        setErrors(newErrors);
        return false;
      }
      
      // Check if amount exceeds balance
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
       // Vérifier si l'utilisateur a modifié la date
      const today = new Date().toISOString().split('T')[0]; // Date du jour au format YYYY-MM-DD
      const userSelectedDate = formData.transaction_date; // Date sélectionnée par l'utilisateur
      
      let transactionDateToSend;
      
      if (userSelectedDate === today) {
        // Si l'utilisateur n'a pas changé la date (c'est aujourd'hui), envoyer l'heure UTC actuelle
        transactionDateToSend = new Date().toISOString(); // Date/heure actuelle en UTC
      } else {
        // Si l'utilisateur a choisi une autre date, utiliser minuit UTC de cette date
        transactionDateToSend = `${userSelectedDate}T00:00:00Z`;
      }
      const payload = {
        account_id: formData.account_id,
        expense_category_id: formData.expense_category_id,
        amount: parseFloat(formData.amount),
        transaction_date: transactionDateToSend,
        recipient_name: formData.recipient_name || null,
        notes: formData.notes || null
      };
      
      // Ajouter l'ID de la dépense planifiée si présent
      if (formData.planned_expense_id) {
        payload.planned_expense_id = formData.planned_expense_id;
      }
      
      await expenseService.storeOperationalTransaction(payload);
      
      // Si c'est une dépense planifiée, marquer comme payée
      if (formData.planned_expense_id) {
        try {
          await plannedExpenseService.markPaid(formData.planned_expense_id);
        } catch (err) {
          console.error('Erreur mise à jour échéance:', err);
        }
      }
      
      setAlert({ type: 'success', message: 'Dépense enregistrée avec succès!' });
      
      setTimeout(() => {
        navigate('/depenses');
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
        message: err.response?.data?.message || 'Erreur lors de l\'enregistrement' 
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
            <StepCategory
              categories={categories}
              selectedId={formData.expense_category_id}
              onSelect={(id) => handleChange('expense_category_id', id)}
              loading={loadingCategories}
              onAddCategory={() => setShowCategoryModal(true)}
              disabled={!!plannedExpense} // Désactiver si dépense planifiée
            />
          );
      case 3:
        return (
          <StepDetails
            formData={formData}
            onChange={handleChange}
            errors={errors}
          />
        );
      case 4:
        return (
          <StepConfirmation
            formData={formData}
            account={selectedAccount}
            category={selectedCategory}
          />
        );
      default:
        return null;
    }
  };

  return (
    <div className="expense-form-page">
      {/* Back button */}
      <motion.button
        className="back-button"
        onClick={() => navigate('/depenses')}
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        whileHover={{ x: -4 }}
      >
        <ArrowLeft size={18} />
        Retour aux dépenses
      </motion.button>

      {/* Header */}
      <motion.div
        className="form-page-header"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="form-page-header-icon">
          <Receipt size={28} />
        </div>
        <div>
          <h1>
            {plannedExpense ? 'Payer une dépense planifiée' : 'Nouvelle dépense'}
          </h1>
          <p>
            {plannedExpense 
              ? `${plannedExpense.name} - ${formatAmount(plannedExpense.estimated_amount)} Ar`
              : 'Enregistrer une dépense opérationnelle'
            }
          </p>
        </div>
        {plannedExpense && (
          <div className="planned-expense-badge">
            <Calendar size={16} />
            Dépense planifiée
          </div>
        )}
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
                  Confirmer la dépense
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

export default ExpenseCreate;