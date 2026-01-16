// ============================================
// src/pages/Sales/ImmediateSalesList.jsx
// Page principale : Liste des ventes immédiates
// ============================================

import { Search } from 'lucide-react';
import useImmediateSales from '../../hooks/useImmediateSales';
import SalesFilters from '../../components/Sales/SalesFilters';
import SalesTable from '../../components/Sales/SalesTable';
import Pagination from '../../components/Sales/Pagination';
import styles from '../../styles/Sales/ImmediateSalesList.module.css';

const ImmediateSalesList = () => {
  const {
    sales,
    pagination,
    loading,
    error,
    filters,
    updateFilters,
    changePage
  } = useImmediateSales();

  // Gestion de la recherche
  const handleSearch = (e) => {
    updateFilters({ search: e.target.value });
  };

  return (
    <div className={styles.container}>
      {/* En-tête de page */}
      <header className={styles.pageHeader}>
        <div className={styles.titleSection}>
          <h1>Ventes Immédiates</h1>
          <p className={styles.subtitle}>
            {pagination ? `${pagination.total} vente${pagination.total > 1 ? 's' : ''} au total` : ''}
          </p>
        </div>
      </header>

      {/* Barre de recherche et filtres */}
      <div className={styles.controls}>
        <div className={styles.searchWrapper}>
          <Search className={styles.searchIcon} size={18} />
          <input
            type="text"
            placeholder="Rechercher par numéro, client..."
            className={styles.searchInput}
            value={filters.search || ''}
            onChange={handleSearch}
          />
        </div>

        <SalesFilters 
          filters={filters}
          onFiltersChange={updateFilters}
        />
      </div>

      {/* Contenu principal */}
      <div className={styles.content}>
        {error && (
          <div className={styles.error}>
            <p>{error}</p>
          </div>
        )}

        {loading && !sales.length ? (
          <div className={styles.loading}>
            <div className={styles.spinner} />
            <p>Chargement des ventes...</p>
          </div>
        ) : (
          <>
            <SalesTable sales={sales} loading={loading} />
            
            {pagination && pagination.last_page > 1 && (
              <Pagination
                currentPage={pagination.current_page}
                totalPages={pagination.last_page}
                onPageChange={changePage}
              />
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default ImmediateSalesList;