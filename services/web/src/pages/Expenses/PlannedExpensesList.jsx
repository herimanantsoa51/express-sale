import React, { useState, useEffect } from 'react';
import plannedExpenseService from '../../services/plannedExpenseService';
import '../../styles/PlannedExpensesList.css';
import { useNavigate } from 'react-router-dom';

const PlannedExpensesList = () => {
  const navigate = useNavigate();
  const [expenses, setExpenses] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [pagination, setPagination] = useState(null);
  const [filters, setFilters] = useState({
    frequency: '',
    active_only: true,
    overdue_only: false
  });

  useEffect(() => {
    loadData();
  }, [currentPage, filters]);

  const loadData = async () => {
    setLoading(true);
    try {
      const params = { page: currentPage };
      
      // N'ajouter les filtres que s'ils sont activés
      if (filters.frequency) params.frequency = filters.frequency;
      if (filters.active_only) params.active_only = 1;
      if (filters.overdue_only) params.overdue_only = 1;

      const [expensesRes, statsRes] = await Promise.all([
        plannedExpenseService.getAll(params),
        plannedExpenseService.getStats()
      ]);
      
      setExpenses(expensesRes.data);
      setPagination(expensesRes.meta);
      setStats(statsRes);
    } catch (error) {
      console.error('Erreur:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-MG', {
      style: 'currency',
      currency: 'MGA',
      minimumFractionDigits: 0
    }).format(amount);
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

  const getStatusClass = (expense) => {
    if (!expense.is_active) return 'inactive';
    if (expense.is_overdue) return 'overdue';
    if (expense.days_until_due <= 7) return 'upcoming';
    return 'active';
  };

  const getStatusLabel = (expense) => {
    if (!expense.is_active) return 'Inactif';
    if (expense.is_overdue) return 'En retard';
    if (expense.days_until_due <= 7) return `Dans ${expense.days_until_due}j`;
    return 'À venir';
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
    setCurrentPage(1);
  };

  const handleNavigate = (path) => {
    navigate(path)
  };

  return (
    <div className="planned-expenses-page">
      <div className="p-page-header">
        <div className="p-header-content">
          <h1>Dépenses Planifiées</h1>
          <button 
            className="p-btn-primary"
            onClick={() => handleNavigate('/depenses/planifie/nouveau')}
          >
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M10 4V16M4 10H16" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
            </svg>
            Nouvelle dépense
          </button>
        </div>
      </div>

      {stats && (
        <div className="stats-grid scale-in">
          <div className="stat-card">
            <div className="stat-label">Revenu mensuel minimum</div>
            <div className="stat-value">{formatAmount(stats.minimum_monthly_income)}</div>
          </div>
          <div className="stat-card">
            <div className="stat-label">Dépenses actives</div>
            <div className="stat-value">{stats.active_count}</div>
          </div>
          <div className="stat-card warning">
            <div className="stat-label">En retard</div>
            <div className="stat-value">{stats.overdue_count}</div>
          </div>
          <div className="stat-card info">
            <div className="stat-label">À venir (7j)</div>
            <div className="stat-value">{stats.upcoming_7_days}</div>
          </div>
        </div>
      )}

      <div className="p-filters-bar fade-in">
        <div className="p-ilter-group">
          <select 
            value={filters.frequency}
            onChange={(e) => handleFilterChange('frequency', e.target.value)}
            className="p-filter-select"
          >
            <option value="">Toutes les fréquences</option>
            <option value="daily">Quotidienne</option>
            <option value="weekly">Hebdomadaire</option>
            <option value="monthly">Mensuelle</option>
            <option value="yearly">Annuelle</option>
          </select>

          <label className="p-filter-checkbox">
            <input
              type="checkbox"
              checked={filters.active_only}
              onChange={(e) => handleFilterChange('active_only', e.target.checked)}
            />
            <span>Actives uniquement</span>
          </label>

          <label className="p-filter-checkbox">
            <input
              type="checkbox"
              checked={filters.overdue_only}
              onChange={(e) => handleFilterChange('overdue_only', e.target.checked)}
            />
            <span>En retard</span>
          </label>
        </div>
      </div>

      {loading ? (
        <div className="p-loading-state">
          <div className="spinner"></div>
          <p>Chargement...</p>
        </div>
      ) : (
        <>
          <div className="expenses-list fade-in">
            {expenses.map(expense => (
              <div
                key={expense.id}
                className={`expense-card ${getStatusClass(expense)}`}
                onClick={() => handleNavigate(`/depenses/planifie/${expense.id}`)}
              >
                <div className="expense-main">
                  <div className="expense-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                      <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round"/>
                      <path d="M2 17L12 22L22 17" stroke="currentColor" strokeWidth="2" strokeLinejoin="round"/>
                      <path d="M2 12L12 17L22 12" stroke="currentColor" strokeWidth="2" strokeLinejoin="round"/>
                    </svg>
                  </div>
                  
                  <div className="expense-info">
                    <div className="expense-header">
                      <h3>{expense.name}</h3>
                      <span className={`status-badge ${getStatusClass(expense)}`}>
                        {getStatusLabel(expense)}
                      </span>
                    </div>
                    
                    {expense.description && (
                      <p className="expense-description">{expense.description}</p>
                    )}
                    
                    <div className="expense-meta">
                      <span className="meta-item">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                          <rect x="3" y="4" width="10" height="9" rx="1" stroke="currentColor" strokeWidth="1.5"/>
                          <path d="M3 7H13M6 2V4M10 2V4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                        </svg>
                        {getFrequencyLabel(expense.frequency)}
                      </span>
                      
                      {expense.expense_category && (
                        <span className="meta-item">
                          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M3 3L8 3L13 8L8 13L3 8V3Z" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round"/>
                          </svg>
                          {expense.expense_category.name}
                        </span>
                      )}
                      
                      {expense.recipient_name && (
                        <span className="meta-item">
                          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="5" r="2.5" stroke="currentColor" strokeWidth="1.5"/>
                            <path d="M3 13C3 10.7909 5.23858 9 8 9C10.7614 9 13 10.7909 13 13" stroke="currentColor" strokeWidth="1.5"/>
                          </svg>
                          {expense.recipient_name}
                        </span>
                      )}
                    </div>
                  </div>
                </div>

                <div className="expense-amount">
                  <div className="amount">{formatAmount(expense.estimated_amount)}</div>
                  {expense.next_due_date && (
                    <div className="next-due">
                      Échéance: {new Date(expense.next_due_date).toLocaleDateString('fr-FR')}
                    </div>
                  )}
                </div>

                <div className="expense-chevron">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M7 4L13 10L7 16" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
              </div>
            ))}

            {expenses.length === 0 && (
              <div className="empty-state">
                <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                  <circle cx="32" cy="32" r="30" stroke="currentColor" strokeWidth="2" opacity="0.2"/>
                  <path d="M32 20V32L40 36" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                </svg>
                <h3>Aucune dépense planifiée</h3>
                <p>Commencez par créer votre première dépense récurrente</p>
              </div>
            )}
          </div>

          {pagination && pagination.last_page > 1 && (
            <div className="pagination">
              <button
                className="pagination-btn"
                disabled={currentPage === 1}
                onClick={() => setCurrentPage(prev => prev - 1)}
              >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path d="M12 4L6 10L12 16" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                </svg>
              </button>

              <div className="pagination-info">
                Page {currentPage} sur {pagination.last_page}
              </div>

              <button
                className="pagination-btn"
                disabled={currentPage === pagination.last_page}
                onClick={() => setCurrentPage(prev => prev + 1)}
              >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path d="M8 4L14 10L8 16" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                </svg>
              </button>
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default PlannedExpensesList;