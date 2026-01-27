import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { motion } from 'motion/react';
import {
  ArrowLeft, Calendar, Clock, User, FileText, TrendingDown,
  Edit, Receipt, ChevronRight, AlertCircle, CheckCircle,
  ChevronLeft, Hash, DollarSign
} from 'lucide-react';
import plannedExpenseService from '../../services/plannedExpenseService';
import '../../styles/PlannedExpenseDetail.css';
import { useNavigate } from 'react-router-dom';

const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  });
};

const getRelativeTime = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  
  if (diffDays === 0) return "Aujourd'hui";
  if (diffDays === 1) return 'Hier';
  if (diffDays < 7) return `Il y a ${diffDays}j`;
  if (diffDays < 30) return `Il y a ${Math.floor(diffDays / 7)}sem`;
  return new Date(dateString).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
};

const getFrequencyLabel = (frequency) => {
  const labels = {
    daily: 'Quotidienne',
    weekly: 'Hebdomadaire',
    monthly: 'Mensuelle',
    yearly: 'Annuelle'
  };
  return labels[frequency] || frequency;
};

const PlannedExpenseDetail = () => {
  const { id } = useParams();
   const navigate = useNavigate();
  const [expense, setExpense] = useState(null);
  const [transactions, setTransactions] = useState([]);
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [pagination, setPagination] = useState(null);
  const [dateFilter, setDateFilter] = useState({ start: '', end: '' });

  useEffect(() => {
    loadExpenseData();
  }, [id]);

  useEffect(() => {
    if (expense) {
      loadTransactions();
    }
  }, [currentPage, dateFilter, expense]);

  const loadExpenseData = async () => {
    try {
      setLoading(true);
      const response = await plannedExpenseService.getById(id);
      setExpense(response.data);
    } catch (error) {
      console.error('Erreur chargement dépense:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadTransactions = async () => {
    try {
      const params = { page: currentPage, per_page: 15 };
      if (dateFilter.start) params.start_date = dateFilter.start;
      if (dateFilter.end) params.end_date = dateFilter.end;

      const response = await plannedExpenseService.getTransactions(id, params);
      setTransactions(response.data);
      setSummary(response.summary);
      setPagination(response.meta);
    } catch (error) {
      console.error('Erreur chargement transactions:', error);
    }
  };

  const handleNavigate = (path) => {
    navigate(path);
  };

  const handlePayNow = () => {
    navigate (`/depenses/nouveau?plannedExpenseId=${id}`);
  };

  if (loading) {
    return (
      <div className="detail-loading-state">
        <div className="detail-spinner"></div>
        <p>Chargement...</p>
      </div>
    );
  }

  if (!expense) {
    return (
      <div className="detail-error-state">
        <AlertCircle size={48} />
        <h3>Dépense planifiée introuvable</h3>
        <button className="detail-btn-secondary" onClick={() => handleNavigate('/depenses/planifie')}>
          Retour à la liste
        </button>
      </div>
    );
  }

  const isOverdue = expense.is_overdue;
  const isUpcoming = expense.days_until_due <= 7 && !isOverdue;

  return (
    <div className="planned-detail-page">
      <motion.button
        className="detail-back-btn"
        onClick={() => handleNavigate('/depenses/planifie')}
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        whileHover={{ x: -4 }}
      >
        <ArrowLeft size={18} />
        Retour aux dépenses planifiées
      </motion.button>

      {/* Header Card */}
      <motion.div
        className="detail-header-card"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="detail-header-main">
          <div className="detail-header-info">
            <div className="detail-title-row">
              <h1>{expense.name}</h1>
              <span className={`detail-status-badge ${isOverdue ? 'overdue' : isUpcoming ? 'upcoming' : 'active'}`}>
                {isOverdue ? 'En retard' : isUpcoming ? `Dans ${expense.days_until_due}j` : 'Dans le temps'}
              </span>
              <span className={`detail-status-indicator ${expense.is_active ? 'active' : 'inactive'}`}>
                    {expense.is_active ? 'Active' : 'Inactive'}
                </span>
            </div>
    
            {expense.description && (
              <p className="detail-description">{expense.description}</p>
            )}

            <div className="detail-meta-row">
              <div className="detail-meta-item">
                <Calendar size={16} />
                <span>{getFrequencyLabel(expense.frequency)}</span>
              </div>
              
              {expense.expense_category && (
                <div className="detail-meta-item">
                  <Hash size={16} />
                  <span>{expense.expense_category.name}</span>
                </div>
              )}
              
              {expense.recipient_name && (
                <div className="detail-meta-item">
                  <User size={16} />
                  <span>{expense.recipient_name}</span>
                </div>
              )}
            </div>
          </div>

          <div className="detail-header-actions">
            <button 
              className="detail-btn-secondary"
              onClick={() => handleNavigate(`/depenses/planifie/modifier/${id}`)}
            >
              <Edit size={18} />
              Modifier
            </button>
            <button 
              className="detail-btn-primary"
              onClick={handlePayNow}
            >
              <DollarSign size={18} />
              Payer maintenant
            </button>
          </div>
        </div>

        <div className="detail-amount-section">
          <div className="detail-amount-main">
            <span className="detail-amount-label">Montant estimé</span>
            <span className="detail-amount-value">{formatAmount(expense.estimated_amount)} Ar</span>
          </div>
          
          {expense.next_due_date && (
            <div className="detail-next-due">
              <Clock size={16} />
              <div>
                <span className="detail-next-due-label">Prochaine échéance</span>
                <span className="detail-next-due-date">{formatDate(expense.next_due_date)}</span>
              </div>
            </div>
          )}
        </div>
      </motion.div>

      {/* Summary Stats */}
      {summary && (
        <motion.div
          className="detail-summary-grid"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
        >
          <div className="detail-stat-card">
            <div className="detail-stat-label">Total payé</div>
            <div className="detail-stat-value">{formatAmount(summary.total_paid)} Ar</div>
          </div>
          
          <div className="detail-stat-card">
            <div className="detail-stat-label">Nombre de paiements</div>
            <div className="detail-stat-value">{summary.transactions_count}</div>
          </div>
          
          <div className={`detail-stat-card ${summary.difference < 0 ? 'negative' : summary.difference > 0 ? 'positive' : ''}`}>
            <div className="detail-stat-label">Écart moyen</div>
            <div className="detail-stat-value">
              {summary.difference > 0 ? '+' : ''}{formatAmount(summary.difference)} Ar
            </div>
          </div>
        </motion.div>
      )}

      {/* Date Filters */}
      <motion.div
        className="detail-filters"
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
      >
        <div className="detail-filter-group">
          <div className="detail-date-filter">
            <Calendar size={16} />
            <input
              type="date"
              value={dateFilter.start}
              onChange={(e) => setDateFilter(prev => ({ ...prev, start: e.target.value }))}
              placeholder="Date début"
            />
          </div>
          
          <div className="detail-date-filter">
            <Calendar size={16} />
            <input
              type="date"
              value={dateFilter.end}
              onChange={(e) => setDateFilter(prev => ({ ...prev, end: e.target.value }))}
              placeholder="Date fin"
            />
          </div>
          
          {(dateFilter.start || dateFilter.end) && (
            <button
              className="detail-clear-filters"
              onClick={() => setDateFilter({ start: '', end: '' })}
            >
              Effacer
            </button>
          )}
        </div>
      </motion.div>

      {/* Transactions List */}
      <motion.div
        className="detail-transactions-section"
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.3 }}
      >
        <div className="detail-transactions-header">
          <div className="detail-transactions-title">
            <Receipt size={18} />
            <span>Historique des paiements</span>
            {pagination && <span className="detail-count">({pagination.total})</span>}
          </div>
        </div>

        {transactions.length === 0 ? (
          <div className="detail-empty-state">
            <Receipt size={32} />
            <h3>Aucun paiement enregistré</h3>
            <p>Cette dépense planifiée n'a pas encore été payée</p>
            <button className="detail-btn-primary" onClick={handlePayNow}>
              <DollarSign size={18} />
              Effectuer le premier paiement
            </button>
          </div>
        ) : (
          <>
            <div className="detail-transactions-list">
              {transactions.map((transaction, index) => (
                <motion.div
                  key={transaction.id}
                  className="detail-transaction-item"
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: index * 0.03 }}
                  onClick={() => handleNavigate(`/transactions/${transaction.id}`)}
                  whileHover={{ x: 2 }}
                >
                  <div className="detail-transaction-icon">
                    <TrendingDown size={18} />
                  </div>
                  
                  <div className="detail-transaction-main">
                    <div className="detail-transaction-recipient">
                      {transaction.recipient_name || 'Sans destinataire'}
                    </div>
                    <div className="detail-transaction-account">
                      {transaction.account?.name}
                    </div>
                  </div>
                  
                  <div className="detail-transaction-right">
                    <div className="detail-transaction-amount">
                      -{formatAmount(transaction.amount)} Ar
                    </div>
                    <div className="detail-transaction-date">
                      {getRelativeTime(transaction.transaction_date)}
                    </div>
                  </div>

                  <div className="detail-transaction-chevron">
                    <ChevronRight size={16} />
                  </div>
                </motion.div>
              ))}
            </div>

            {pagination && pagination.last_page > 1 && (
              <div className="detail-pagination">
                <button
                  className="detail-pagination-btn"
                  disabled={currentPage === 1}
                  onClick={() => setCurrentPage(prev => prev - 1)}
                >
                  <ChevronLeft size={18} />
                </button>

                <div className="detail-pagination-info">
                  Page {currentPage} sur {pagination.last_page}
                </div>

                <button
                  className="detail-pagination-btn"
                  disabled={currentPage === pagination.last_page}
                  onClick={() => setCurrentPage(prev => prev + 1)}
                >
                  <ChevronRight size={18} />
                </button>
              </div>
            )}
          </>
        )}
      </motion.div>
    </div>
  );
};

export default PlannedExpenseDetail;