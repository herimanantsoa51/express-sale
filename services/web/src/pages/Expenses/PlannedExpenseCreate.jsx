// pages/PlannedExpenseCreate.jsx
import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion } from 'motion/react';
import { toast } from 'react-toastify';
import {
  ArrowLeft, Calendar, Repeat, Tag, User, FileText, 
  Check, AlertCircle, Zap, Droplet, Wifi, Building, 
  Users, Truck, Package, Megaphone, Toolbox, Shield, 
  MoreHorizontal, Clock
} from 'lucide-react';
import plannedExpenseService from '../../services/plannedExpenseService';
import expenseService from '../../services/expenseService';
import '../../styles/PlannedExpenseCreate.css';
import { useParams } from 'react-router-dom';

const iconMap = {
  'zap': Zap, 'droplet': Droplet, 'wifi': Wifi, 'building': Building,
  'users': Users, 'truck': Truck, 'package': Package, 'megaphone': Megaphone,
  'tool': Toolbox, 'file-text': FileText, 'shield': Shield, 
  'more-horizontal': MoreHorizontal, 'tag': Tag,
};

const getIconComponent = (iconName) => iconMap[iconName] || Tag;

const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
};

const FREQUENCIES = [
  { value: 'daily', label: 'Quotidienne', icon: '📅', description: 'Chaque jour' },
  { value: 'weekly', label: 'Hebdomadaire', icon: '📆', description: 'Chaque semaine' },
  { value: 'monthly', label: 'Mensuelle', icon: '🗓️', description: 'Chaque mois' },
  { value: 'yearly', label: 'Annuelle', icon: '📊', description: 'Chaque année' }
];

const DAYS_OF_WEEK = [
  { value: 1, label: 'Lundi' },
  { value: 2, label: 'Mardi' },
  { value: 3, label: 'Mercredi' },
  { value: 4, label: 'Jeudi' },
  { value: 5, label: 'Vendredi' },
  { value: 6, label: 'Samedi' },
  { value: 7, label: 'Dimanche' }
];

const PlannedExpenseCreate = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const isEditMode = Boolean(id);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  
  const [formData, setFormData] = useState({
    expense_category_id: '',
    name: '',
    description: '',
    estimated_amount: '',
    frequency: 'monthly',
    day_of_week: null,
    day_of_month: 1,
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    recipient_name: '',
    is_active: true  // ✅ Valeur par défaut OK pour la création
  });

  useEffect(() => {
    loadCategories();
    if (isEditMode) {
      loadExpense();
    }
  }, []);
  
  const loadExpense = async () => {
    try {
      setLoading(true);
      const response = await plannedExpenseService.getById(id);
      const expense = response.data;
      
      console.log('📦 Données reçues:', expense); // ✅ DEBUG
      console.log('🔘 is_active:', expense.is_active); // ✅ DEBUG
      
      setFormData({
        expense_category_id: expense.expense_category.id,
        name: expense.name,
        description: expense.description || '',
        estimated_amount: expense.estimated_amount.toString(),
        frequency: expense.frequency,
        day_of_week: expense.day_of_week || null,
        day_of_month: expense.day_of_month || 1,
        start_date: expense.start_date,
        end_date: expense.end_date || '',
        recipient_name: expense.recipient_name || '',
        is_active: expense.is_active ?? true  // ✅ CORRECTION ICI
      });
      
      console.log('✅ FormData après chargement:', {
        is_active: expense.is_active ?? true
      }); // ✅ DEBUG
      
    } catch (err) {
      console.error('Erreur chargement dépense:', err);
      toast.error('Impossible de charger la dépense');
    } finally {
      setLoading(false);
    }
  };

  const loadCategories = async () => {
    try {
      setLoading(true);
      const response = await expenseService.getExpenseCategories();
      setCategories((response.data || []).filter(cat => cat.is_active));
    } catch (err) {
      console.error('Erreur chargement catégories:', err);
      toast.error('Impossible de charger les catégories');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (field, value) => {
    console.log(`🔄 handleChange appelé - Field: ${field}, Value:`, value, typeof value); // ✅ DEBUG
    
    setFormData(prev => {
      const newData = { ...prev, [field]: value };
      console.log('📝 Nouveau formData:', newData); // ✅ DEBUG
      return newData;
    });
    
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: null }));
    }
  };

  const validateForm = () => {
    const newErrors = {};
    
    if (!formData.expense_category_id) newErrors.expense_category_id = 'Catégorie requise';
    if (!formData.name.trim()) newErrors.name = 'Nom requis';
    if (!formData.estimated_amount || parseFloat(formData.estimated_amount) <= 0) {
      newErrors.estimated_amount = 'Montant invalide';
    }
    if (!formData.start_date) newErrors.start_date = 'Date de début requise';
    
    if (formData.frequency === 'weekly' && !formData.day_of_week) {
      newErrors.day_of_week = 'Jour de la semaine requis';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      toast.error('Veuillez corriger les erreurs');
      return;
    }
    
    try {
      setSubmitting(true);
      
      const payload = {
        expense_category_id: parseInt(formData.expense_category_id),
        name: formData.name,
        description: formData.description || null,
        estimated_amount: parseFloat(formData.estimated_amount),
        frequency: formData.frequency,
        day_of_week: formData.frequency === 'weekly' ? parseInt(formData.day_of_week) : null,
        day_of_month: formData.frequency === 'monthly' ? parseInt(formData.day_of_month) : null,
        start_date: formData.start_date,
        end_date: formData.end_date || null,
        recipient_name: formData.recipient_name || null,
        is_active: formData.is_active
      };
      
      if (isEditMode) {
        console.log('Mise à jour de la charge planifiée avec le payload:', payload);
        await plannedExpenseService.update(id, payload);
        toast.success('Charge planifiée modifiée avec succès !');
      } else {
        await plannedExpenseService.create(payload);
        toast.success('Charge planifiée créée avec succès !');
      }
      
      setTimeout(() => {
        navigate(isEditMode ? `/depenses/planifie/${id}` : '/depenses/planifie');
      }, 1000);
      
    } catch (err) {
      console.error('Erreur:', err);
      
      if (err.response?.data?.errors) {
        const apiErrors = {};
        Object.entries(err.response.data.errors).forEach(([key, messages]) => {
          apiErrors[key] = Array.isArray(messages) ? messages[0] : messages;
        });
        setErrors(apiErrors);
      }
      
      toast.error(err.response?.data?.message || 'Erreur lors de la sauvegarde');
    } finally {
      setSubmitting(false);
    }
  };

  const selectedCategory = categories.find(c => c.id === parseInt(formData.expense_category_id));
  const CategoryIcon = selectedCategory ? getIconComponent(selectedCategory.icon) : Tag;

  return (
    <div className="pec-page-wrapper">
      <motion.button
        className="pec-back-button"
        onClick={() => navigate('/depenses')}
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        whileHover={{ x: -4 }}
      >
        <ArrowLeft size={18} />
        Retour
      </motion.button>

      <motion.div
        className="pec-header"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="pec-header-icon">
          <Clock size={28} />
        </div>
        <div>
          <h1 className="pec-title">{isEditMode ? 'Modifier la charge planifiée' : 'Nouvelle charge planifiée'}</h1>
          <p className="pec-subtitle">{isEditMode ? 'Mettre à jour les informations de la dépense récurrente' : 'Définir une dépense récurrente pour un meilleur suivi budgétaire'}</p>
        </div>
      </motion.div>

      <motion.form
        className="pec-form"
        onSubmit={handleSubmit}
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
      >
        {/* Category Selection */}
        <div className="pec-form-section">
          <h2 className="pec-section-title">Catégorie de dépense</h2>
          
          {loading ? (
            <div className="pec-category-loading">Chargement...</div>
          ) : (
            <div className="pec-category-grid">
              {categories.map((category) => {
                const Icon = getIconComponent(category.icon);
                const isSelected = formData.expense_category_id === category.id;
                
                return (
                  <motion.div
                    key={category.id}
                    className={`pec-category-option ${isSelected ? 'pec-category-selected' : ''}`}
                    onClick={() => handleChange('expense_category_id', category.id)}
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                  >
                    <div className="pec-category-icon">
                      <Icon size={20} />
                    </div>
                    <span className="pec-category-name">{category.name}</span>
                    {isSelected && <Check size={16} className="pec-check-icon" />}
                  </motion.div>
                );
              })}
            </div>
          )}
          {errors.expense_category_id && (
            <p className="pec-field-error">
              <AlertCircle size={14} />
              {errors.expense_category_id}
            </p>
          )}
        </div>

        {/* Basic Info */}
        <div className="pec-form-section">
          <h2 className="pec-section-title">Informations</h2>
          
          <div className="pec-form-row">
            <div className="pec-form-field pec-full-width">
              <label className="pec-field-label">
                Nom de la charge <span className="pec-required">*</span>
              </label>
              <div className="pec-input-wrapper">
                <FileText size={18} className="pec-input-icon" />
                <input
                  type="text"
                  className={`pec-input ${errors.name ? 'pec-input-error' : ''}`}
                  value={formData.name}
                  onChange={(e) => handleChange('name', e.target.value)}
                  placeholder="Ex: Loyer magasin, Électricité..."
                />
              </div>
              {errors.name && (
                <p className="pec-field-error">
                  <AlertCircle size={14} />
                  {errors.name}
                </p>
              )}
            </div>
          </div>

          <div className="pec-form-row">
            <div className="pec-form-field">
              <label className="pec-field-label">
                Montant estimé <span className="pec-required">*</span>
              </label>
              <div className="pec-amount-wrapper">
                <input
                  type="number"
                  className={`pec-amount-input ${errors.estimated_amount ? 'pec-input-error' : ''}`}
                  value={formData.estimated_amount}
                  onChange={(e) => handleChange('estimated_amount', e.target.value)}
                  placeholder="0"
                  min="0"
                  step="0.01"
                />
                <span className="pec-currency">Ar</span>
              </div>
              {errors.estimated_amount && (
                <p className="pec-field-error">
                  <AlertCircle size={14} />
                  {errors.estimated_amount}
                </p>
              )}
            </div>

            <div className="pec-form-field">
              <label className="pec-field-label">Destinataire / Bénéficiaire</label>
              <div className="pec-input-wrapper">
                <User size={18} className="pec-input-icon" />
                <input
                  type="text"
                  className="pec-input"
                  value={formData.recipient_name}
                  onChange={(e) => handleChange('recipient_name', e.target.value)}
                  placeholder="Ex: JIRAMA, Propriétaire..."
                />
              </div>
            </div>
          </div>

          <div className="pec-form-row">
            <div className="pec-form-field pec-full-width">
              <label className="pec-field-label">Description</label>
              <textarea
                className="pec-textarea"
                value={formData.description}
                onChange={(e) => handleChange('description', e.target.value)}
                placeholder="Notes supplémentaires..."
                rows={2}
              />
            </div>
          </div>
        </div>

        {/* Frequency */}
        <div className="pec-form-section">
          <h2 className="pec-section-title">
            <Repeat size={20} />
            Récurrence
          </h2>
          
          <div className="pec-frequency-grid">
            {FREQUENCIES.map((freq) => {
              const isSelected = formData.frequency === freq.value;
              
              return (
                <motion.div
                  key={freq.value}
                  className={`pec-frequency-option ${isSelected ? 'pec-frequency-selected' : ''}`}
                  onClick={() => handleChange('frequency', freq.value)}
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                >
                  <span className="pec-frequency-emoji">{freq.icon}</span>
                  <div>
                    <div className="pec-frequency-label">{freq.label}</div>
                    <div className="pec-frequency-desc">{freq.description}</div>
                  </div>
                  {isSelected && <Check size={18} className="pec-check-icon" />}
                </motion.div>
              );
            })}
          </div>

          {/* Day of week (si hebdomadaire) */}
          {formData.frequency === 'weekly' && (
            <div className="pec-form-field">
              <label className="pec-field-label">
                Jour de la semaine <span className="pec-required">*</span>
              </label>
              <select
                className={`pec-select ${errors.day_of_week ? 'pec-input-error' : ''}`}
                value={formData.day_of_week || ''}
                onChange={(e) => handleChange('day_of_week', e.target.value)}
              >
                <option value="">Choisir un jour</option>
                {DAYS_OF_WEEK.map(day => (
                  <option key={day.value} value={day.value}>{day.label}</option>
                ))}
              </select>
              {errors.day_of_week && (
                <p className="pec-field-error">
                  <AlertCircle size={14} />
                  {errors.day_of_week}
                </p>
              )}
            </div>
          )}

          {/* Day of month (si mensuelle) */}
          {formData.frequency === 'monthly' && (
            <div className="pec-form-field">
              <label className="pec-field-label">Jour du mois</label>
              <input
                type="number"
                className="pec-input"
                min="1"
                max="31"
                value={formData.day_of_month}
                onChange={(e) => handleChange('day_of_month', e.target.value)}
              />
              <p className="pec-field-hint">Entre 1 et 31</p>
            </div>
          )}
        </div>

        {/* Dates */}
        <div className="pec-form-section">
          <h2 className="pec-section-title">
            <Calendar size={20} />
            Période
          </h2>
          
          <div className="pec-form-row">
            <div className="pec-form-field">
              <label className="pec-field-label">
                Date de début <span className="pec-required">*</span>
              </label>
              <input
                type="date"
                className={`pec-input ${errors.start_date ? 'pec-input-error' : ''}`}
                value={formData.start_date}
                onChange={(e) => handleChange('start_date', e.target.value)}
              />
              {errors.start_date && (
                <p className="pec-field-error">
                  <AlertCircle size={14} />
                  {errors.start_date}
                </p>
              )}
            </div>

            <div className="pec-form-field">
              <label className="pec-field-label">Date de fin (optionnel)</label>
              <input
                type="date"
                className="pec-input"
                value={formData.end_date}
                onChange={(e) => handleChange('end_date', e.target.value)}
                min={formData.start_date}
              />
              <p className="pec-field-hint">Laisser vide pour indéfini</p>
            </div>
          </div>
        </div>

        {/* Status - Only in edit mode */}
        {isEditMode && (
          <div className="pec-form-section">
            <h2 className="pec-section-title">Statut</h2>
            
            <div className="pec-form-field">
              <div className="pec-toggle-field">
                <div className="pec-toggle-content">
                  <label className="pec-toggle-label">Charge active</label>
                  <p className="pec-toggle-desc">
                    Désactivez pour archiver cette charge sans la supprimer
                  </p>
                </div>
                <div
                  className={`pec-toggle-switch ${formData.is_active ? 'pec-toggle-active' : ''}`}
                  onClick={() => handleChange('is_active', !formData.is_active)}
                >
                  <div className="pec-toggle-knob" />
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Preview */}
        {formData.estimated_amount && formData.expense_category_id && (
          <motion.div
            className="pec-preview-card"
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
          >
            <div className="pec-preview-header">
              <CategoryIcon size={24} />
              <div>
                <h3 className="pec-preview-title">{formData.name || 'Charge planifiée'}</h3>
                <p className="pec-preview-category">{selectedCategory?.name}</p>
              </div>
            </div>
            <div className="pec-preview-amount">
              {formatAmount(formData.estimated_amount)} Ar
              <span className="pec-preview-frequency">
                / {FREQUENCIES.find(f => f.value === formData.frequency)?.label.toLowerCase()}
              </span>
            </div>
          </motion.div>
        )}

        {/* Actions */}
        <div className="pec-form-actions">
          <button
            type="button"
            className="pec-btn-secondary"
            onClick={() => navigate('/depenses')}
            disabled={submitting}
          >
            Annuler
          </button>
          <button 
            type="submit" 
            className="pec-btn-primary" 
            disabled={submitting}
          >
            {submitting ? (
              <>En cours...</>
            ) : (
              <>
                <Check size={18} />
                {isEditMode ? 'Sauvegarder' : 'Créer la charge'}
              </>
            )}
          </button>
        </div>
      </motion.form>
    </div>
  );
};

export default PlannedExpenseCreate;