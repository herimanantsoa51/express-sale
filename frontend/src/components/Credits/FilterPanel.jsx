// components/FilterPanel.jsx
import React, { useState, useEffect } from 'react';
import usersService from '../../services/usersService';
import '../../styles/components/FilterPanel.css';

const FilterPanel = ({ filters, onFilterChange, onReset }) => {
  const [users, setUsers] = useState([]);
  const [isExpanded, setIsExpanded] = useState(false);

  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const data = await usersService.getUsers();
        setUsers(data);
      } catch (error) {
        console.error('Erreur chargement utilisateurs:', error);
      }
    };
    fetchUsers();
  }, []);

  const handleChange = (key, value) => {
    onFilterChange({ [key]: value });
  };

  const activeFiltersCount = Object.entries(filters).filter(([key, value]) => {
    if (key === 'page' || key === 'per_page' || key === 'sort_by' || key === 'sort_order') return false;
    return value !== '' && value !== false && value !== null && value !== undefined;
  }).length;

  return (
    <div className="filter-panel">
      <div className="filter-header">
        <button 
          className="filter-toggle"
          onClick={() => setIsExpanded(!isExpanded)}
        >
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path 
              d="M2.5 5.83333H17.5M5.83333 10H14.1667M8.33333 14.1667H11.6667" 
              stroke="currentColor" 
              strokeWidth="1.5" 
              strokeLinecap="round"
            />
          </svg>
          Filtres
          {activeFiltersCount > 0 && (
            <span className="filter-count">{activeFiltersCount}</span>
          )}
        </button>
        
        {activeFiltersCount > 0 && (
          <button className="filter-reset" onClick={onReset}>
            Réinitialiser
          </button>
        )}
      </div>

      {isExpanded && (
        <div className="filter-content">
          <div className="filter-grid">
            <div className="filter-group">
              <label className="filter-label">Statut</label>
              <select 
                className="filter-select"
                value={filters.status || ''}
                onChange={(e) => handleChange('status', e.target.value)}
              >
                <option value="">Tous</option>
                <option value="active">Actif</option>
                <option value="partial_paid">Partiellement payé</option>
                <option value="completed">Terminé</option>
                <option value="overdue">En retard</option>
                <option value="defaulted">Défaut</option>
                <option value="recovered">Récupéré</option>
              </select>
            </div>

            <div className="filter-group">
              <label className="filter-label">Créé par</label>
              <select 
                className="filter-select"
                value={filters.created_by || ''}
                onChange={(e) => handleChange('created_by', e.target.value)}
              >
                <option value="">Tous</option>
                {users.map(user => (
                  <option key={user.id} value={user.id}>{user.name}</option>
                ))}
              </select>
            </div>

            <div className="filter-group">
              <label className="filter-label">Échéance proche (jours)</label>
              <input 
                type="number"
                className="filter-input"
                placeholder="Ex: 30"
                value={filters.due_soon_days || ''}
                onChange={(e) => handleChange('due_soon_days', e.target.value)}
                min="1"
              />
            </div>
          </div>

          <div className="filter-checkboxes">
            <label className="filter-checkbox">
              <input
                type="checkbox"
                checked={filters.active_only || false}
                onChange={(e) => handleChange('active_only', e.target.checked)}
              />
              <span className="checkbox-label">Crédits actifs uniquement</span>
            </label>

            <label className="filter-checkbox">
              <input
                type="checkbox"
                checked={filters.overdue_only || false}
                onChange={(e) => handleChange('overdue_only', e.target.checked)}
              />
              <span className="checkbox-label">En retard uniquement</span>
            </label>
          </div>
        </div>
      )}
    </div>
  );
};

export default FilterPanel;

/* ============================================
   styles/components/FilterPanel.css
   ============================================ */
/*
.filter-panel {
  background: var(--bg-secondary);
  border-radius: var(--border-radius-lg);
  overflow: hidden;
  transition: all var(--transition-speed) var(--transition-timing);
}

.filter-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md);
  border-bottom: 1px solid var(--border-color);
}

.filter-toggle {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: 8px 16px;
  background: transparent;
  border: none;
  border-radius: var(--border-radius);
  color: var(--text-primary);
  font-size: var(--font-size-md);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-speed) var(--transition-timing);
}

.filter-toggle:hover {
  background: var(--bg-tertiary);
}

.filter-toggle svg {
  color: var(--text-secondary);
}

.filter-count {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  background: var(--primary);
  border-radius: 10px;
  color: white;
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
}

.filter-reset {
  padding: 6px 12px;
  background: transparent;
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-speed) var(--transition-timing);
}

.filter-reset:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
  border-color: var(--text-tertiary);
}

.filter-content {
  padding: var(--spacing-lg);
  animation: fadeIn 0.3s var(--transition-timing);
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-md);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.filter-label {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--text-secondary);
}

.filter-select,
.filter-input {
  padding: 10px 14px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  color: var(--text-primary);
  font-size: var(--font-size-md);
  transition: all var(--transition-speed) var(--transition-timing);
}

.filter-select:focus,
.filter-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--primary-light);
}

.filter-checkboxes {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-md);
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--border-color);
}

.filter-checkbox {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  cursor: pointer;
}

.filter-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  border: 2px solid var(--border-color);
  border-radius: 4px;
  cursor: pointer;
  transition: all var(--transition-speed) var(--transition-timing);
  appearance: none;
  background: var(--bg-primary);
}

.filter-checkbox input[type="checkbox"]:checked {
  background: var(--primary);
  border-color: var(--primary);
  background-image: url("data:image/svg+xml,%3Csvg width='12' height='10' viewBox='0 0 12 10' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 5L4.5 8.5L11 1.5' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: center;
}

.checkbox-label {
  font-size: var(--font-size-md);
  color: var(--text-primary);
  user-select: none;
}

@media (max-width: 768px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }
  
  .filter-checkboxes {
    flex-direction: column;
  }
}
*/