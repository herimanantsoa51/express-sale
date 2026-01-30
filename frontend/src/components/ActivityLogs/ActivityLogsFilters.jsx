import { useState, useEffect } from 'react';
import activityLogsService from '../../services/activityLogsService';

const ActivityLogsFilters = ({ filters, onFilterChange, onRefetch }) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const [categories, setCategories] = useState({});
  const [localFilters, setLocalFilters] = useState({
    user_id: filters.user_id || '',
    action: filters.action || '',
    status: filters.status || '',
    model_type: filters.model_type || '',
    category: filters.category || '',
    date_from: filters.date_from || '',
    date_to: filters.date_to || '',
    search: filters.search || '',
    show_all: filters.show_all || false,
    per_page: filters.per_page || 20
  });

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const data = await activityLogsService.getActionsByCategory();
        setCategories(data);
      } catch (err) {
        console.error('Erreur chargement catégories:', err);
      }
    };
    fetchCategories();
  }, []);

  const handleInputChange = (key, value) => {
    setLocalFilters(prev => ({ ...prev, [key]: value }));
  };

  const handleApplyFilters = () => {
    onFilterChange(localFilters);
  };

  const handleResetFilters = () => {
    const resetFilters = {
      user_id: '',
      action: '',
      status: '',
      model_type: '',
      category: '',
      date_from: '',
      date_to: '',
      search: '',
      show_all: false,
      per_page: 20
    };
    setLocalFilters(resetFilters);
    onFilterChange(resetFilters);
  };

  return (
    <div className="alf-container">
      <div className="alf-header">
        <button 
          className="alf-toggle"
          onClick={() => setIsExpanded(!isExpanded)}
        >
          <span className="alf-toggle-text">Filtres</span>
          <span className={`alf-toggle-icon ${isExpanded ? 'alf-toggle-icon-open' : ''}`}>
            ▼
          </span>
        </button>
        <button 
          className="alf-refresh"
          onClick={onRefetch}
        >
          ↻ Actualiser
        </button>
      </div>

      {isExpanded && (
        <div className="alf-content">
          <div className="alf-grid">
            <div className="alf-field">
              <label className="alf-label">Recherche</label>
              <input
                type="text"
                className="alf-input"
                placeholder="Rechercher dans les descriptions..."
                value={localFilters.search}
                onChange={(e) => handleInputChange('search', e.target.value)}
              />
            </div>

            <div className="alf-field">
              <label className="alf-label">Statut</label>
              <select
                className="alf-select"
                value={localFilters.status}
                onChange={(e) => handleInputChange('status', e.target.value)}
              >
                <option value="">Tous les statuts</option>
                <option value="success">Succès</option>
                <option value="failed">Échec</option>
                <option value="error">Erreur</option>
              </select>
            </div>

            <div className="alf-field">
              <label className="alf-label">Catégorie</label>
              <select
                className="alf-select"
                value={localFilters.category}
                onChange={(e) => handleInputChange('category', e.target.value)}
              >
                <option value="">Toutes les catégories</option>
                {Object.entries(categories).map(([key, cat]) => (
                  <option key={key} value={key}>{cat.label}</option>
                ))}
              </select>
            </div>

            <div className="alf-field">
              <label className="alf-label">Type de modèle</label>
              <input
                type="text"
                className="alf-input"
                placeholder="Ex: Product, Sale, User..."
                value={localFilters.model_type}
                onChange={(e) => handleInputChange('model_type', e.target.value)}
              />
            </div>

            <div className="alf-field">
              <label className="alf-label">Date de début</label>
              <input
                type="date"
                className="alf-input"
                value={localFilters.date_from}
                onChange={(e) => handleInputChange('date_from', e.target.value)}
              />
            </div>

            <div className="alf-field">
              <label className="alf-label">Date de fin</label>
              <input
                type="date"
                className="alf-input"
                value={localFilters.date_to}
                onChange={(e) => handleInputChange('date_to', e.target.value)}
              />
            </div>

            <div className="alf-field">
              <label className="alf-label">Par page</label>
              <select
                className="alf-select"
                value={localFilters.per_page}
                onChange={(e) => handleInputChange('per_page', e.target.value)}
              >
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>

            <div className="alf-field alf-field-checkbox">
              <label className="alf-checkbox-label">
                <input
                  type="checkbox"
                  className="alf-checkbox"
                  checked={localFilters.show_all}
                  onChange={(e) => handleInputChange('show_all', e.target.checked)}
                />
                <span className="alf-checkbox-text">Afficher tous les statuts</span>
              </label>
            </div>
          </div>

          <div className="alf-actions">
            <button 
              className="alf-button alf-button-reset"
              onClick={handleResetFilters}
            >
              Réinitialiser
            </button>
            <button 
              className="alf-button alf-button-apply"
              onClick={handleApplyFilters}
            >
              Appliquer les filtres
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default ActivityLogsFilters;