import { useNavigate} from 'react-router-dom';
import { 
  Plus, 
  AlertCircle,
  RefreshCw,
  ClipboardList,
  Search,
  X,
  Filter,
  Calendar
} from 'lucide-react';
import useReservations from '../../hooks/useReservations';
import ReservationTable from '../../components/reservations/ReservationTable';
import './ReservationsPage.css';
import { useState } from 'react';

const ReservationsPage = () => {
  const navigate = useNavigate();
  const [showFilters, setShowFilters] = useState(false);
  const {
    reservations,
    loading,
    error,
    meta,
    filters,
    updateFilters,
    resetFilters,
    changePage,
    refetch
  } = useReservations();

  const handleSort = (field, order) => {
    updateFilters({ sort_by: field, sort_order: order });
  };

  const hasActiveFilters = filters.search || filters.status || 
    filters.reservation_date_from || filters.reservation_date_to ||
    filters.expiry_date_from || filters.expiry_date_to ||
    filters.active_only || filters.expired_only;

  return (
    <div className="reservations-page">
      {/* Header */}
      <div className="reservations-page__header">
        <div className="reservations-page__title-section">
          <h1 className="reservations-page__title">Réservations</h1>
          <p className="reservations-page__subtitle">
            Gérez toutes vos réservations
          </p>
        </div>
      </div>

      {/* Filters Bar */}
      <div className="reservations-filters">
        <div className="search-input-wrapper">
          <Search size={18} className="search-icon" />
          <input
            type="text"
            className="search-input"
            placeholder="Rechercher par numéro, client..."
            value={filters.search || ''}
            onChange={(e) => updateFilters({ search: e.target.value })}
          />
          {filters.search && (
            <button className="search-clear" onClick={() => updateFilters({ search: '' })}>
              <X size={16} />
            </button>
          )}
        </div>

        <div className="filters-row">
          <select
            className="filter-select"
            value={filters.status || ''}
            onChange={(e) => updateFilters({ status: e.target.value })}
          >
            <option value="">Tous les statuts</option>
            <option value="pending">En attente</option>
            <option value="confirmed">Confirmée</option>
            <option value="partial_paid">Partiellement payée</option>
            <option value="completed">Terminée</option>
            <option value="cancelled">Annulée</option>
            <option value="expired">Expirée</option>
          </select>

          <button 
            className={`btn btn--ghost ${showFilters ? 'btn--active' : ''}`}
            onClick={() => setShowFilters(!showFilters)}
          >
            <Filter size={16} />
            <span>Filtres</span>
          </button>

          {hasActiveFilters && (
            <button className="btn btn--ghost btn--danger" onClick={resetFilters}>
              <X size={16} />
              <span>Réinitialiser</span>
            </button>
          )}
        </div>
      </div>

      {/* Advanced Filters */}
      {showFilters && (
        <div className="reservations-advanced-filters">
          <div className="filter-group">
            <label className="filter-label">
              <Calendar size={14} />
              Date de réservation
            </label>
            <div className="filter-row">
              <input
                type="date"
                className="filter-date"
                value={filters.reservation_date_from || ''}
                onChange={(e) => updateFilters({ reservation_date_from: e.target.value })}
              />
              <span>à</span>
              <input
                type="date"
                className="filter-date"
                value={filters.reservation_date_to || ''}
                onChange={(e) => updateFilters({ reservation_date_to: e.target.value })}
              />
            </div>
          </div>

          <div className="filter-group">
            <label className="filter-label">
              <Calendar size={14} />
              Date d'expiration
            </label>
            <div className="filter-row">
              <input
                type="date"
                className="filter-date"
                value={filters.expiry_date_from || ''}
                onChange={(e) => updateFilters({ expiry_date_from: e.target.value })}
              />
              <span>à</span>
              <input
                type="date"
                className="filter-date"
                value={filters.expiry_date_to || ''}
                onChange={(e) => updateFilters({ expiry_date_to: e.target.value })}
              />
            </div>
          </div>

          <div className="filter-group">
            <label className="filter-label">Filtres rapides</label>
            <div className="filter-checkboxes">
              <label className="filter-checkbox">
                <input
                  type="checkbox"
                  checked={filters.active_only || false}
                  onChange={(e) => updateFilters({ active_only: e.target.checked, expired_only: false })}
                />
                <span>Actives uniquement</span>
              </label>
              <label className="filter-checkbox">
                <input
                  type="checkbox"
                  checked={filters.expired_only || false}
                  onChange={(e) => updateFilters({ expired_only: e.target.checked, active_only: false })}
                />
                <span>Expirées uniquement</span>
              </label>
            </div>
          </div>
        </div>
      )}

      {/* Toolbar */}
      <div className="reservations-toolbar">
        <div className="reservations-toolbar__left">
          <span className="reservations-toolbar__count">
            {meta.total} réservation{meta.total > 1 ? 's' : ''}
          </span>
        </div>

        <div className="reservations-toolbar__right">
          <button className="btn btn--ghost btn--icon" onClick={refetch} title="Rafraîchir">
            <RefreshCw size={18} />
          </button>
        </div>
      </div>

      {/* Content */}
      <div className="reservations-content">
        {loading && (
          <div className="reservations-loading">
            <div className="spinner"></div>
            <p>Chargement des réservations...</p>
          </div>
        )}

        {error && (
          <div className="reservations-error">
            <AlertCircle size={48} />
            <h3>Erreur</h3>
            <p>{error}</p>
            <button className="btn btn--primary" onClick={refetch}>
              <RefreshCw size={16} />
              <span>Réessayer</span>
            </button>
          </div>
        )}

        {!loading && !error && reservations.length === 0 && (
          <div className="reservations-empty">
            <ClipboardList size={48} />
            <h3>Aucune réservation trouvée</h3>
            <p>
              {hasActiveFilters 
                ? 'Aucun résultat pour ces filtres'
                : 'Commencez par créer votre première réservation'}
            </p>
            {!hasActiveFilters && (
              <button 
                className="btn btn--primary"
                onClick={() => navigate('/sales/new?type=reservation')}
              >
                <Plus size={18} />
                <span>Nouvelle réservation</span>
              </button>
            )}
          </div>
        )}

        {!loading && !error && reservations.length > 0 && (
          <ReservationTable
            reservations={reservations}
            onSort={handleSort}
            sortBy={filters.sort_by}
            sortOrder={filters.sort_order}
          />
        )}
      </div>

      {/* Pagination */}
      {!loading && !error && reservations.length > 0 && meta.last_page > 1 && (
        <div className="reservations-pagination">
          <button
            className="pagination-btn"
            onClick={() => changePage(meta.current_page - 1)}
            disabled={meta.current_page === 1}
          >
            Précédent
          </button>
          
          <div className="pagination-info">
            Page {meta.current_page} sur {meta.last_page}
          </div>

          <button
            className="pagination-btn"
            onClick={() => changePage(meta.current_page + 1)}
            disabled={meta.current_page === meta.last_page}
          >
            Suivant
          </button>
        </div>
      )}
    </div>
  );
};

export default ReservationsPage;
