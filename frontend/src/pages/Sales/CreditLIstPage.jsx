// pages/CreditListPage.jsx
import React from 'react';
import { Search } from 'lucide-react';
import useCredits from '../../hooks/useCredits';
import FilterPanel from '../../components/Credits/FilterPanel';
import CreditTable from '../../components/Credits/CreditTable';
import Pagination from '../../components/Credits/Pagination';
import '../../styles/components/CreditListPage.css';

const CreditListPage = () => {
  const {
    credits,
    meta,
    loading,
    error,
    filters,
    updateFilters,
    resetFilters,
    changePage,
    changeSort
  } = useCredits();

  // Gestion de la recherche
  const handleSearch = (e) => {
    updateFilters({ search: e.target.value });
  };

  return (
    <div className="credit-list-page">
      {/* En-tête de page */}
      <header className="page-header">
        <div className="title-section">
          <h1>Gestion des crédits</h1>
          <p className="subtitle">
            {meta ? `${meta.total} crédit${meta.total > 1 ? 's' : ''} au total` : ''}
          </p>
        </div>
      </header>

      {/* Barre de recherche et filtres */}
      <div className="controls">
        <div className="search-wrapper">
          <Search className="search-icon" size={18} />
          <input
            type="text"
            placeholder="Rechercher par numéro, client..."
            className="search-input"
            value={filters.search || ''}
            onChange={handleSearch}
          />
        </div>

        <FilterPanel
          filters={filters}
          onFilterChange={updateFilters}
          onReset={resetFilters}
        />
      </div>

      {/* Contenu principal */}
      <div className="content">
        {error && (
          <div className="error">
            <p>{error}</p>
          </div>
        )}

        {loading && !credits.length ? (
          <div className="loading">
            <div className="spinner" />
            <p>Chargement des crédits...</p>
          </div>
        ) : (
          <>
            <CreditTable
              credits={credits}
              sortBy={filters.sort_by}
              sortOrder={filters.sort_order}
              onSort={changeSort}
              loading={loading}
            />
            
            {meta && meta.last_page > 1 && (
              <Pagination
                currentPage={meta.current_page}
                totalPages={meta.last_page}
                onPageChange={changePage}
              />
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default CreditListPage;

/* ============================================
   styles/pages/CreditListPage.css
   ============================================ */

/*
.credit-list-page {
  min-height: 100vh;
  background: var(--bg-primary);
  padding: var(--spacing-xl) 0;
}

.page-container {
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 var(--spacing-lg);
}

.page-header {
  margin-bottom: var(--spacing-xl);
  animation: fadeIn 0.4s var(--transition-timing);
}

.page-header-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.page-title {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
  letter-spacing: -0.028em;
  margin: 0;
}

.page-subtitle {
  font-size: var(--font-size-md);
  color: var(--text-secondary);
  margin: 0;
}

.page-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
  animation: fadeIn 0.5s 0.1s var(--transition-timing) backwards;
}

.filters-section {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  animation: slideIn 0.4s 0.2s var(--transition-timing) backwards;
}

.error-message {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-md);
  background: var(--danger-light);
  border: 1px solid var(--danger);
  border-radius: var(--border-radius);
  color: var(--danger);
  font-size: var(--font-size-md);
  font-weight: var(--font-weight-medium);
  animation: scaleIn 0.3s var(--transition-smooth);
}

@media (max-width: 768px) {
  .credit-list-page {
    padding: var(--spacing-md) 0;
  }
  
  .page-container {
    padding: 0 var(--spacing-md);
  }
  
  .page-title {
    font-size: var(--font-size-xl);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
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