import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'motion/react';
import {
  Plus, RefreshCw, TrendingDown, PieChart, Calendar, X,
  ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight,
  Receipt, FolderPlus, AlertCircle, CheckCircle, Info,
  Zap, Droplet, Wifi, Building, Users, Truck, Package,
  Megaphone, Toolbox, FileText, Shield, MoreHorizontal, Tag,
  Clock, Hash
} from 'lucide-react';
import expenseService from '../../services/expenseService';
import accountService from '../../services/accountService';
import '../../styles/ExpenseList.css';

// === ICON MAP ===
const iconMap = {
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

const getIconComponent = (iconName) => {
  return iconMap[iconName] || Tag;
};

// === HELPERS ===
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }).format(date);
};

const getRelativeTime = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  
  if (diffDays === 0) return "Aujourd'hui";
  if (diffDays === 1) return 'Hier';
  if (diffDays < 7) return `Il y a ${diffDays} jours`;
  if (diffDays < 30) return `Il y a ${Math.floor(diffDays / 7)} sem.`;
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short' }).format(date);
};

// === CATEGORY DETAIL MODAL ===
const CategoryDetailModal = ({ category, isOpen, onClose }) => {
  if (!isOpen || !category) return null;

  const IconComp = getIconComponent(category.icon);

  return (
    <motion.div
      className="modal-overlay"
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      onClick={onClose}
    >
      <motion.div
        className="modal-content modal-sm"
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 20 }}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="modal-header">
          <div className="category-detail-header">
            <div className="category-detail-icon">
              <IconComp size={24} />
            </div>
            <h2 className="modal-title">{category.name}</h2>
          </div>
          <button className="modal-close" onClick={onClose}>
            <X size={18} />
          </button>
        </div>

        <div className="modal-body">
          <div className="category-detail-info">
            <div className="category-detail-row">
              <span className="category-detail-label">Description</span>
              <p className="category-detail-value">
                {category.description || 'Aucune description'}
              </p>
            </div>
            
            <div className="category-detail-row">
              <span className="category-detail-label">Statut</span>
              <span className={`category-status ${category.is_active ? 'active' : 'inactive'}`}>
                {category.is_active ? 'Active' : 'Inactive'}
              </span>
            </div>

            <div className="category-detail-row">
              <span className="category-detail-label">Créée le</span>
              <span className="category-detail-date">
                <Clock size={14} />
                {formatDate(category.created_at)}
              </span>
            </div>
          </div>
        </div>

        <div className="modal-footer">
          <button className="btn btn-secondary" onClick={onClose}>
            Fermer
          </button>
        </div>
      </motion.div>
    </motion.div>
  );
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
                  const IconComp = getIconComponent(icon);
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

// === EXPENSE ITEM ===
const ExpenseItem = ({ expense, category, index, onNavigate }) => {
  const IconComp = category ? getIconComponent(category.icon) : Tag;
  
  return (
    <motion.div
      className="expense-item"
      initial={{ opacity: 0, x: -20 }}
      animate={{ opacity: 1, x: 0 }}
      transition={{ delay: index * 0.03 }}
      onClick={() => onNavigate(`/transactions/${expense.id}`)}
      whileHover={{ x: 2 }}
      whileTap={{ scale: 0.995 }}
    >
      <div className="expense-item-icon">
        <IconComp size={18} />
      </div>
      
      <div className="expense-item-main">
        <div className="expense-item-recipient">{expense.recipient_name || 'Sans destinataire'}</div>
        <div className="expense-item-meta">
          <span className="expense-item-category-tag">
            {category?.name || 'Non catégorisé'}
          </span>
          <span className="expense-item-separator">•</span>
          <span className="expense-item-account">
            {expense.account?.name}
          </span>
        </div>
      </div>
      
      <div className="expense-item-right">
        <div className="expense-item-amount">-{formatAmount(expense.amount)} Ar</div>
        <div className="expense-item-date">{getRelativeTime(expense.transaction_date)}</div>
      </div>

      <div className="expense-item-chevron">
        <ChevronRight size={16} />
      </div>
    </motion.div>
  );
};

// === CATEGORY STATS ===
const CategoryStats = ({ stats, categories, onCategoryClick }) => {
  if (!stats || stats.length === 0) return null;

  return (
    <motion.div
      className="category-stats"
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: 0.1 }}
    >
      <div className="category-stats-header">
        <div className="category-stats-title">
          <PieChart size={18} />
          <span>Dépenses par catégorie</span>
        </div>
      </div>
      <div className="category-stats-grid">
        {stats.map((stat, index) => {
          // Trouver la catégorie complète par ID
          const fullCategory = categories.find(c => c.id === stat.category?.id);
          const IconComp = fullCategory ? getIconComponent(fullCategory.icon) : Tag;
          
          return (
            <motion.div
              key={stat.category?.id || index}
              className="category-stat-item"
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.1 + index * 0.05 }}
              onClick={() => fullCategory && onCategoryClick(fullCategory)}
              style={{ cursor: fullCategory ? 'pointer' : 'default' }}
            >
              <div className="category-stat-header">
                <div className="category-stat-icon">
                  <IconComp size={14} />
                </div>
                <span className="category-stat-name">
                  {stat.category?.name || 'Non catégorisé'}
                </span>
                {fullCategory && (
                  <Info size={12} className="category-stat-info" />
                )}
              </div>
              <div className="category-stat-amount">-{formatAmount(stat.total_amount)} Ar</div>
              <div className="category-stat-count">{stat.transactions_count} transaction(s)</div>
            </motion.div>
          );
        })}
      </div>
    </motion.div>
  );
};

// === MAIN COMPONENT ===
const ExpenseList = () => {
  const navigate = useNavigate();
  
  const [expenses, setExpenses] = useState([]);
  const [categories, setCategories] = useState([]);
  const [accounts, setAccounts] = useState([]);
  const [summary, setSummary] = useState(null);
  const [statsByCategory, setStatsByCategory] = useState([]);
  const [loading, setLoading] = useState(true);
  const [meta, setMeta] = useState(null);
  
  // Filters
  const [currentPage, setCurrentPage] = useState(1);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [accountFilter, setAccountFilter] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  
  // Modals
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(null);

  // Load categories
  const loadCategories = useCallback(async () => {
    try {
      const response = await expenseService.getExpenseCategories();
      setCategories(response.data || []);
    } catch (err) {
      console.error('Erreur chargement catégories:', err);
    }
  }, []);

  // Load accounts
  const loadAccounts = useCallback(async () => {
    try {
      const response = await accountService.getAll({ is_active: true, per_page: 100 });
      setAccounts(response.data || []);
    } catch (err) {
      console.error('Erreur chargement comptes:', err);
    }
  }, []);

  // Load expenses
  const loadExpenses = useCallback(async () => {
    try {
      setLoading(true);
      const params = { page: currentPage, per_page: 15 };
      if (startDate) params.start_date = startDate;
      if (endDate) params.end_date = endDate;
      if (accountFilter) params.account_id = accountFilter;
      if (categoryFilter) params.expense_category_id = categoryFilter;

      const response = await expenseService.geExpenseTransactions(params);
      setExpenses(response.data || []);
      setSummary(response.summary || null);
      setStatsByCategory(response.stats_by_category || []);
      setMeta(response.meta || null);
    } catch (err) {
      console.error('Erreur chargement dépenses:', err);
    } finally {
      setLoading(false);
    }
  }, [currentPage, startDate, endDate, accountFilter, categoryFilter]);

  useEffect(() => {
    loadCategories();
    loadAccounts();
  }, [loadCategories, loadAccounts]);

  useEffect(() => {
    loadExpenses();
  }, [loadExpenses]);

  useEffect(() => {
    setCurrentPage(1);
  }, [startDate, endDate, accountFilter, categoryFilter]);

  // Get category for expense - chercher par expense_category_id dans les stats
  const getCategoryForExpense = useCallback((expense) => {
    // D'abord essayer de trouver dans les categories chargées
    // On doit mapper via les stats_by_category car l'expense n'a pas expense_category_id directement
    // Utiliser la relation via account et les stats
    const statForExpense = statsByCategory.find(stat => {
      // Logique de correspondance basée sur les données disponibles
      return stat.category?.id && categories.find(c => c.id === stat.category.id);
    });
    
    if (statForExpense) {
      return categories.find(c => c.id === statForExpense.category?.id);
    }
    
    return null;
  }, [categories, statsByCategory]);

  // Créer un map des catégories par ID pour accès rapide
  const categoryMap = categories.reduce((acc, cat) => {
    acc[cat.id] = cat;
    return acc;
  }, {});

  // Améliorer getCategoryForExpense avec le filtre de catégorie actuel
  const getCategoryForExpenseImproved = useCallback((expense) => {
    // Si on filtre par catégorie, utiliser cette catégorie
    if (categoryFilter) {
      return categoryMap[parseInt(categoryFilter)] || null;
    }
    
    // Sinon, essayer de trouver via expense_category_id si présent
    if (expense.expense_category_id) {
      return categoryMap[expense.expense_category_id] || null;
    }
    
    return null;
  }, [categoryFilter, categoryMap]);

  const handleClearFilters = () => {
    setStartDate('');
    setEndDate('');
    setAccountFilter('');
    setCategoryFilter('');
    setCurrentPage(1);
  };

  const handleCategoryClick = (category) => {
    setSelectedCategory(category);
  };

  const hasFilters = startDate || endDate || accountFilter || categoryFilter;
  const lastPage = meta?.last_page || 1;

  return (
    <div className="expense-page">
      {/* Header */}
      <motion.div
        className="expense-header"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="expense-header-left">
          <h1>Dépenses</h1>
          <p>Gérez et suivez vos dépenses opérationnelles</p>
        </div>
        <div className="expense-header-actions">
          <motion.button
            className="btn btn-secondary"
            onClick={() => setShowCategoryModal(true)}
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
          >
            <FolderPlus size={18} />
            Nouvelle catégorie
          </motion.button>
          <motion.button
            className="btn btn-primary"
            onClick={() => navigate('/depenses/nouveau')}
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
          >
            <Plus size={18} />
            Nouvelle dépense
          </motion.button>
        </div>
      </motion.div>

      {/* Summary Cards */}
      {summary && (
        <motion.div
          className="expense-summary"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
        >
          <div className="summary-card highlight">
            <div className="summary-card-icon">
              <TrendingDown size={24} />
            </div>
            <div className="summary-card-content">
              <p className="summary-card-label">Total des dépenses</p>
              <p className="summary-card-value">{formatAmount(summary.total_expense)} Ar</p>
            </div>
          </div>
          <div className="summary-card">
            <div className="summary-card-icon">
              <PieChart size={24} />
            </div>
            <div className="summary-card-content">
              <p className="summary-card-label">Catégories utilisées</p>
              <p className="summary-card-value">{summary.categories_count}</p>
            </div>
          </div>
        </motion.div>
      )}

      {/* Category Stats */}
      <CategoryStats 
        stats={statsByCategory} 
        categories={categories} 
        onCategoryClick={handleCategoryClick}
      />

      {/* Filters */}
      <motion.div
        className="expense-filters"
        initial={{ opacity: 0, y: -10 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="filters-row">
          <div className="filter-group">
            <div className="date-filter">
              <Calendar size={16} />
              <input
                type="date"
                className="date-input"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
                placeholder="Date début"
              />
            </div>
            <div className="date-filter">
              <Calendar size={16} />
              <input
                type="date"
                className="date-input"
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
                placeholder="Date fin"
              />
            </div>
          </div>

          <div className="filter-group">
            <select
              className="filter-select"
              value={accountFilter}
              onChange={(e) => setAccountFilter(e.target.value)}
            >
              <option value="">Tous les comptes</option>
              {accounts.map(account => (
                <option key={account.id} value={account.id}>{account.name}</option>
              ))}
            </select>

            <select
              className="filter-select"
              value={categoryFilter}
              onChange={(e) => setCategoryFilter(e.target.value)}
            >
              <option value="">Toutes catégories</option>
              {categories.map(cat => (
                <option key={cat.id} value={cat.id}>{cat.name}</option>
              ))}
            </select>
          </div>

          <AnimatePresence>
            {hasFilters && (
              <motion.button
                className="clear-filters-btn"
                onClick={handleClearFilters}
                initial={{ opacity: 0, scale: 0.8 }}
                animate={{ opacity: 1, scale: 1 }}
                exit={{ opacity: 0, scale: 0.8 }}
              >
                <X size={16} />
                Effacer
              </motion.button>
            )}
          </AnimatePresence>
        </div>
      </motion.div>

      {/* Expense List */}
      <motion.div
        className="expense-list"
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
      >
        <div className="expense-list-header">
          <div className="expense-list-title">
            <Receipt size={18} />
            <span>Transactions</span>
            {meta && <span className="expense-list-count">({meta.total})</span>}
          </div>
        </div>

        {loading ? (
          <div className="expense-loading">
            <RefreshCw className="loading-spinner" size={32} />
            <p>Chargement des dépenses...</p>
          </div>
        ) : expenses.length === 0 ? (
          <div className="expense-empty">
            <div className="expense-empty-icon">
              <Receipt size={32} />
            </div>
            <h3>Aucune dépense trouvée</h3>
            <p>
              {hasFilters 
                ? 'Aucune dépense ne correspond à vos critères.'
                : 'Commencez par enregistrer une dépense.'}
            </p>
            {!hasFilters && (
              <button className="btn btn-primary" onClick={() => navigate('/depenses/nouveau')}>
                <Plus size={18} />
                Nouvelle dépense
              </button>
            )}
          </div>
        ) : (
          <>
            <div className="expense-items">
              <AnimatePresence mode="popLayout">
                {expenses.map((expense, index) => (
                  <ExpenseItem
                    key={expense.id}
                    expense={expense}
                    category={getCategoryForExpenseImproved(expense)}
                    index={index}
                    onNavigate={navigate}
                  />
                ))}
              </AnimatePresence>
            </div>

            {meta && lastPage > 1 && (
              <div className="expense-pagination">
                <button
                  className="pagination-btn"
                  disabled={currentPage === 1}
                  onClick={() => setCurrentPage(1)}
                >
                  <ChevronsLeft size={18} />
                </button>
                <button
                  className="pagination-btn"
                  disabled={currentPage === 1}
                  onClick={() => setCurrentPage(p => p - 1)}
                >
                  <ChevronLeft size={18} />
                </button>
                <span className="pagination-info">{currentPage} / {lastPage}</span>
                <button
                  className="pagination-btn"
                  disabled={currentPage === lastPage}
                  onClick={() => setCurrentPage(p => p + 1)}
                >
                  <ChevronRight size={18} />
                </button>
                <button
                  className="pagination-btn"
                  disabled={currentPage === lastPage}
                  onClick={() => setCurrentPage(lastPage)}
                >
                  <ChevronsRight size={18} />
                </button>
              </div>
            )}
          </>
        )}
      </motion.div>

      {/* Category Create Modal */}
      <AnimatePresence>
        {showCategoryModal && (
          <CategoryModal
            isOpen={showCategoryModal}
            onClose={() => setShowCategoryModal(false)}
            onSuccess={loadCategories}
          />
        )}
      </AnimatePresence>

      {/* Category Detail Modal */}
      <AnimatePresence>
        {selectedCategory && (
          <CategoryDetailModal
            category={selectedCategory}
            isOpen={!!selectedCategory}
            onClose={() => setSelectedCategory(null)}
          />
        )}
      </AnimatePresence>
    </div>
  );
};

export default ExpenseList;
