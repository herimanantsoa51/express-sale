import React, { useState, useMemo } from 'react';
import { Check, Star, MapPin, Search, Filter, X, Building2 } from 'lucide-react';
import './SupplierSelector.css';

const SupplierSelector = ({ suppliers, selectedSupplierId, onSupplierSelect, coordinates = [] }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCoordinateId, setSelectedCoordinateId] = useState('');

  const filteredSuppliers = useMemo(() => {
    return suppliers.filter(supplier => {
      const matchesSearch = !searchTerm || 
        supplier.name.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesLocation = !selectedCoordinateId || 
        supplier.coordinate?.id === parseInt(selectedCoordinateId);
      
      return matchesSearch && matchesLocation;
    });
  }, [suppliers, searchTerm, selectedCoordinateId]);

  const clearFilters = () => {
    setSearchTerm('');
    setSelectedCoordinateId('');
  };

  const hasActiveFilters = searchTerm || selectedCoordinateId;

  return (
    <div className="selector-section">
      <div className="selector-header">
        <div className="selector-title">
          <Building2 size={24} />
          <div>
            <h3>Fournisseur <span className="required">*</span></h3>
            <p>Sélectionnez le fournisseur pour cette commande. Ce choix est obligatoire.</p>
          </div>
        </div>
      </div>

      <div className="selector-filters">
        <div className="filter-search">
          <Search size={16} />
          <input
            type="text"
            placeholder="Rechercher un fournisseur..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
          {searchTerm && (
            <button type="button" className="filter-clear" onClick={() => setSearchTerm('')}>
              <X size={14} />
            </button>
          )}
        </div>

        <div className="filter-select-wrapper">
          <MapPin size={16} />
          <select
            className="filter-select"
            value={selectedCoordinateId}
            onChange={(e) => setSelectedCoordinateId(e.target.value)}
          >
            <option value="">Toutes les localisations</option>
            {coordinates.map(coord => (
              <option key={coord.id} value={coord.id}>
                {coord.full_location || `${coord.city}, ${coord.country}`}
              </option>
            ))}
          </select>
        </div>

        {hasActiveFilters && (
          <button type="button" className="filter-reset" onClick={clearFilters}>
            <X size={14} />
            Effacer
          </button>
        )}
      </div>

      <div className="selector-results-info">
        <span>{filteredSuppliers.length} fournisseur{filteredSuppliers.length > 1 ? 's' : ''}</span>
      </div>

      <div className="selector-grid">
        {filteredSuppliers.length === 0 ? (
          <div className="selector-empty">
            <Building2 size={48} />
            <p>Aucun fournisseur trouvé</p>
            {hasActiveFilters && (
              <button type="button" onClick={clearFilters}>Effacer les filtres</button>
            )}
          </div>
        ) : (
          filteredSuppliers.map((supplier, index) => {
            const isSelected = selectedSupplierId === supplier.id;
            
            return (
              <div
                key={supplier.id}
                className={`selector-card ${isSelected ? 'selected' : ''}`}
                onClick={() => onSupplierSelect(supplier)}
                style={{ animationDelay: `${index * 0.05}s` }}
              >
                <div className="selector-card-header">
                  {supplier.logo_url ? (
                    <img 
                      src={supplier.logo_url} 
                      alt={supplier.name} 
                      className="selector-card-logo" 
                    />
                  ) : (
                    <div className="selector-card-logo-placeholder">
                      <Building2 size={20} />
                    </div>
                  )}
                  <div className="selector-card-title">
                    <h4>{supplier.name}</h4>
                    {supplier.coordinate && (
                      <span className="selector-card-location">
                        <MapPin size={12} />
                        {supplier.coordinate.full_location || `${supplier.coordinate.city}, ${supplier.coordinate.country}`}
                      </span>
                    )}
                  </div>
                  {isSelected && (
                    <div className="selector-card-check">
                      <Check size={16} />
                    </div>
                  )}
                </div>
                
                <div className="selector-card-footer">
                  <div className="selector-card-score">
                    <Star size={14} fill="currentColor" />
                    <span>{supplier.reliability_score || 5.0}/10</span>
                  </div>
                  {supplier.email && (
                    <span className="selector-card-email">{supplier.email}</span>
                  )}
                </div>
              </div>
            );
          })
        )}
      </div>
    </div>
  );
};

export default SupplierSelector;