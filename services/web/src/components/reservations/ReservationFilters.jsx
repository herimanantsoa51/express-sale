import React, { useState } from 'react';
import useUsers from '../../hooks/useUsers';
import './ReservationFilters.css';

/**
 * Composant de filtres avancés pour les réservations
 */
const ReservationFilters = ({ filters, onFiltersChange, onReset }) => {
  const { users } = useUsers();
  const [isExpanded, setIsExpanded] = useState(false);

  const handleChange = (field, value) => {
    onFiltersChange({ [field]: value });
  };

  const hasActiveFilters = Object.entries(filters).some(([key, value]) => {
    if (['sort_by', 'sort_order', 'per_page', 'page'].includes(key)) return false;
    if (typeof value === 'boolean') return value === true;
    return value !== '' && value !== null;
  });

  return (
    <div className="reservation-filters">
      {/* Quick Filters */}
      <div className="reservation-filters__quick">
        <input
          type="text"
          className="filter-search"
          placeholder="🔍 Rechercher (N° vente, client, code...)"
          value={filters.search || ''}
          onChange={(e) => handleChange('search', e.target.value)}
        />

        <select
          className="filter-select"
          value={filters.status || ''}
          onChange={(e) => handleChange('status', e.target.value)}
        >
          <option value="">Tous les statuts</option>
          <option value="pending">En attente</option>
          <option value="confirmed">Confirmé</option>
          <option value="partial_paid">Partiellement payé</option>
          <option value="completed">Terminé</option>
          <option value="expired">Expiré</option>
          <option value="cancelled">Annulé</option>
        </select>

        <button
          className={`filter-toggle ${isExpanded ? 'filter-toggle--active' : ''}`}
          onClick={() => setIsExpanded(!isExpanded)}
        >
          <span>⚙️</span>
          <span>Filtres avancés</span>
          {hasActiveFilters && <span className="filter-toggle__badge">{
            Object.entries(filters).filter(([key, value]) => {
              if (['sort_by', 'sort_order', 'per_page', 'page'].includes(key)) return false;
              if (typeof value === 'boolean') return value === true;
              return value !== '' && value !== null;
            }).length
          }</span>}
        </button>

        {hasActiveFilters && (
          <button
            className="filter-reset"
            onClick={onReset}
          >
            <span>✕</span>
            <span>Réinitialiser</span>
          </button>
        )}
      </div>

      {/* Advanced Filters */}
      {isExpanded && (
        <div className="reservation-filters__advanced">
          <div className="filter-row">
            <div className="filter-group">
              <label className="filter-label">Vendeur</label>
              <select
                className="filter-input"
                value={filters.user_id || ''}
                onChange={(e) => handleChange('user_id', e.target.value)}
              >
                <option value="">Tous les vendeurs</option>
                {users.map(user => (
                  <option key={user.id} value={user.id}>
                    {user.name}
                  </option>
                ))}
              </select>
            </div>

            <div className="filter-group">
              <label className="filter-label">Résultats par page</label>
              <select
                className="filter-input"
                value={filters.per_page || 15}
                onChange={(e) => handleChange('per_page', parseInt(e.target.value))}
              >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>
          </div>

          <div className="filter-row">
            <div className="filter-group">
              <label className="filter-label">Date de réservation (début)</label>
              <input
                type="date"
                className="filter-input"
                value={filters.reservation_date_from || ''}
                onChange={(e) => handleChange('reservation_date_from', e.target.value)}
              />
            </div>

            <div className="filter-group">
              <label className="filter-label">Date de réservation (fin)</label>
              <input
                type="date"
                className="filter-input"
                value={filters.reservation_date_to || ''}
                onChange={(e) => handleChange('reservation_date_to', e.target.value)}
              />
            </div>
          </div>

          <div className="filter-row">
            <div className="filter-group">
              <label className="filter-label">Date d'expiration (début)</label>
              <input
                type="date"
                className="filter-input"
                value={filters.expiry_date_from || ''}
                onChange={(e) => handleChange('expiry_date_from', e.target.value)}
              />
            </div>

            <div className="filter-group">
              <label className="filter-label">Date d'expiration (fin)</label>
              <input
                type="date"
                className="filter-input"
                value={filters.expiry_date_to || ''}
                onChange={(e) => handleChange('expiry_date_to', e.target.value)}
              />
            </div>
          </div>

          <div className="filter-row">
            <label className="filter-checkbox">
              <input
                type="checkbox"
                checked={filters.active_only || false}
                onChange={(e) => handleChange('active_only', e.target.checked)}
              />
              <span>Uniquement les réservations actives</span>
            </label>

            <label className="filter-checkbox">
              <input
                type="checkbox"
                checked={filters.expired_only || false}
                onChange={(e) => handleChange('expired_only', e.target.checked)}
              />
              <span>Uniquement les réservations expirées</span>
            </label>
          </div>

          <div className="filter-row">
            <div className="filter-group">
              <label className="filter-label">Expire dans (jours)</label>
              <input
                type="number"
                className="filter-input"
                placeholder="Ex: 7"
                value={filters.expiring_soon_days || ''}
                onChange={(e) => handleChange('expiring_soon_days', e.target.value ? parseInt(e.target.value) : null)}
                min="1"
              />
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ReservationFilters;
