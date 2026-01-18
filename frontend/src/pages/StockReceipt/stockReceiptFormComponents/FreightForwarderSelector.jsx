import React, { useState, useMemo } from 'react';
import { Check, Star, MapPin, Search, X, Truck, ArrowRight, TrendingUp } from 'lucide-react';
import './SupplierSelector.css';

const FreightForwarderSelector = ({ 
  freightForwarders, 
  selectedFreightForwarderId, 
  onFreightForwarderSelect,
  freightCost,
  onFreightCostChange,
  currency,
  currencySymbol,
  currencyRate,
  coordinates = []
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCoordinateId, setSelectedCoordinateId] = useState('');

  const filteredForwarders = useMemo(() => {
    return freightForwarders.filter(forwarder => {
      const matchesSearch = !searchTerm || 
        forwarder.name.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesLocation = !selectedCoordinateId || 
        forwarder.coordinate?.id === parseInt(selectedCoordinateId);
      
      return matchesSearch && matchesLocation;
    });
  }, [freightForwarders, searchTerm, selectedCoordinateId]);

  const clearFilters = () => {
    setSearchTerm('');
    setSelectedCoordinateId('');
  };

  const hasActiveFilters = searchTerm || selectedCoordinateId;

  const freightCostAriary = useMemo(() => {
    if (!freightCost || !currencyRate) return 0;
    return parseFloat(freightCost) * currencyRate;
  }, [freightCost, currencyRate]);

  return (
    <div className="selector-section">
      <div className="selector-header">
        <div className="selector-title">
          <Truck size={24} />
          <div>
            <h3>Transitaire <span className="optional">(optionnel)</span></h3>
            <p>Sélectionnez un transitaire si vous avez besoin d'un service de transport pour cette commande.</p>
          </div>
        </div>
      </div>

      <div className="selector-filters">
        <div className="filter-search">
          <Search size={16} />
          <input
            type="text"
            placeholder="Rechercher un transitaire..."
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
        <span>{filteredForwarders.length + 1} option{filteredForwarders.length > 0 ? 's' : ''}</span>
      </div>

      <div className="selector-grid">
        <div
          className={`selector-card ${!selectedFreightForwarderId ? 'selected' : ''}`}
          onClick={() => onFreightForwarderSelect(null)}
        >
          <div className="selector-card-header">
            <div className="selector-card-logo-placeholder">
              <X size={20} />
            </div>
            <div className="selector-card-title">
              <h4>Aucun transitaire</h4>
              <span className="selector-card-location">Transport direct ou auto-organisé</span>
            </div>
            {!selectedFreightForwarderId && (
              <div className="selector-card-check">
                <Check size={16} />
              </div>
            )}
          </div>
          <div className="selector-card-footer">
            <span className="selector-card-email">Pas de frais de transport externe</span>
          </div>
        </div>

        {filteredForwarders.length === 0 && hasActiveFilters ? (
          <div className="selector-empty" style={{ gridColumn: 'span 1' }}>
            <Truck size={48} />
            <p>Aucun transitaire trouvé</p>
            <button type="button" onClick={clearFilters}>Effacer les filtres</button>
          </div>
        ) : (
          filteredForwarders.map((forwarder, index) => {
            const isSelected = selectedFreightForwarderId === forwarder.id;
            
            return (
              <div
                key={forwarder.id}
                className={`selector-card ${isSelected ? 'selected' : ''}`}
                onClick={() => onFreightForwarderSelect(forwarder)}
                style={{ animationDelay: `${(index + 1) * 0.05}s` }}
              >
                <div className="selector-card-header">
                  {forwarder.logo_url ? (
                    <img 
                      src={forwarder.logo_url} 
                      alt={forwarder.name} 
                      className="selector-card-logo" 
                    />
                  ) : (
                    <div className="selector-card-logo-placeholder">
                      <Truck size={20} />
                    </div>
                  )}
                  <div className="selector-card-title">
                    <h4>{forwarder.name}</h4>
                    {forwarder.coordinate && (
                      <span className="selector-card-location">
                        <MapPin size={12} />
                        {forwarder.coordinate.full_location || `${forwarder.coordinate.city}, ${forwarder.coordinate.country}`}
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
                    <span>{forwarder.service_score || 5.0}/10</span>
                  </div>
                  {forwarder.email && (
                    <span className="selector-card-email">{forwarder.email}</span>
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

export default FreightForwarderSelector;