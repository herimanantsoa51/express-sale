// ============================================
// components/CoordinateSelect.jsx - APPLE DESIGN
// ============================================
import { useState, useEffect, useRef } from 'react';
import { Plus, MapPin, Globe } from 'lucide-react';
import coordinateService from '../services/coordinateService';
import '../styles/CoordinateSelect.css';

const CoordinateSelect = ({ 
  value, 
  onChange, 
  error, 
  disabled = false,
  label = "Localisation",
  required = false,
  showAddNew = true
}) => {
  const [countries, setCountries] = useState([]);
  const [cities, setCities] = useState([]);
  const [selectedCountry, setSelectedCountry] = useState('');
  const [selectedCity, setSelectedCity] = useState('');
  const [loading, setLoading] = useState(false);
  const [showNewForm, setShowNewForm] = useState(false);
  const [addMode, setAddMode] = useState('');
  
  const [newCountry, setNewCountry] = useState('');
  const [newCity, setNewCity] = useState('');
  const [creating, setCreating] = useState(false);
  
  const [coordinateData, setCoordinateData] = useState(null);
  const [isLoadingInitial, setIsLoadingInitial] = useState(false);
  
  const isInitializing = useRef(false);

  useEffect(() => {
    loadCountries();
  }, []);

  useEffect(() => {
    if (value && !isInitializing.current && countries.length > 0) {
      loadSelectedCoordinate(value);
    } else if (!value) {
      setSelectedCountry('');
      setSelectedCity('');
      setCoordinateData(null);
      setCities([]);
    }
  }, [value, countries]);

  const loadCountries = async () => {
    try {
      const data = await coordinateService.getCountries();
      setCountries(data);
    } catch (err) {
      console.error('❌ Erreur chargement pays:', err);
    }
  };

  const loadCities = async (country) => {
    try {
      setLoading(true);
      const data = await coordinateService.getCitiesByCountry(country);
      
      const citiesArray = Object.entries(data).map(([id, name]) => ({
        id: parseInt(id),
        name
      }));
      
      setCities(citiesArray);
      return citiesArray;
    } catch (err) {
      console.error('❌ Erreur chargement villes:', err);
      setCities([]);
      return [];
    } finally {
      setLoading(false);
    }
  };

  const loadSelectedCoordinate = async (coordinateId) => {
    if (isInitializing.current) return;

    try {
      isInitializing.current = true;
      setIsLoadingInitial(true);
      
      const coordinate = await coordinateService.getCoordinate(coordinateId);
      
      if (coordinate) {
        setCoordinateData(coordinate);
        
        const citiesArray = await loadCities(coordinate.country);
        
        const cityExists = citiesArray.find(c => c.id === coordinate.id);
        
        if (!cityExists) {
          setCities(prev => [...prev, { id: coordinate.id, name: coordinate.city }]);
        }
        
        setTimeout(() => {
          setSelectedCountry(coordinate.country);
          
          setTimeout(() => {
            setSelectedCity(coordinate.id.toString());
          }, 50);
        }, 50);
      }
    } catch (err) {
      console.error('❌ Erreur chargement coordonnée:', err);
    } finally {
      setTimeout(() => {
        setIsLoadingInitial(false);
        isInitializing.current = false;
      }, 150);
    }
  };

  const handleCountryChange = (e) => {
    const country = e.target.value;
    setSelectedCountry(country);
    setSelectedCity('');
    setCities([]);
    onChange(null);
    
    setCoordinateData(null);
    
    if (country) {
      loadCities(country);
    }
  };

  const handleCityChange = (e) => {
    const cityId = e.target.value;
    setSelectedCity(cityId);
    onChange(cityId ? parseInt(cityId) : null);
  };

  const handleCreateNew = async () => {
    if (addMode === 'city-country') {
      if (!newCountry.trim() || !newCity.trim()) {
        alert('Veuillez renseigner le pays et la ville');
        return;
      }
    } else if (addMode === 'city-only') {
      if (!selectedCountry) {
        alert('Veuillez d\'abord sélectionner un pays');
        return;
      }
      if (!newCity.trim()) {
        alert('Veuillez renseigner la ville');
        return;
      }
    }

    try {
      setCreating(true);
      
      let newCoordinate;
      if (addMode === 'city-country') {
        newCoordinate = await coordinateService.createCoordinate({
          country: newCountry.trim(),
          city: newCity.trim(),
          is_active: true
        });
      } else {
        newCoordinate = await coordinateService.createCoordinate({
          country: selectedCountry,
          city: newCity.trim(),
          is_active: true
        });
      }

      await loadCountries();
      
      if (addMode === 'city-country') {
        setSelectedCountry(newCoordinate.country);
        await loadCities(newCoordinate.country);
      } else {
        await loadCities(selectedCountry);
      }
      
      setSelectedCity(newCoordinate.id.toString());
      onChange(newCoordinate.id);

      setNewCountry('');
      setNewCity('');
      setShowNewForm(false);
      setAddMode('');
    } catch (err) {
      alert(err.response?.data?.message || 'Erreur lors de la création');
    } finally {
      setCreating(false);
    }
  };

  const startAddCityOnly = () => {
    if (!selectedCountry) {
      alert('Veuillez d\'abord sélectionner un pays pour ajouter une ville');
      return;
    }
    setAddMode('city-only');
    setShowNewForm(true);
  };

  const startAddCityCountry = () => {
    setAddMode('city-country');
    setShowNewForm(true);
  };

  const cancelAdd = () => {
    setShowNewForm(false);
    setAddMode('');
    setNewCountry('');
    setNewCity('');
  };

  const getCurrentCityName = () => {
    if (!selectedCity) return null;
    
    const city = cities.find(c => c.id.toString() === selectedCity);
    if (city) return city.name;
    
    if (coordinateData && coordinateData.id.toString() === selectedCity) {
      return coordinateData.city;
    }
    
    return 'Ville sélectionnée';
  };

  return (
    <div className="cs-wrapper">
      {label && (
        <label className={`cs-label ${required ? 'cs-required' : ''}`}>
          <MapPin size={16} />
          {label}
        </label>
      )}

      {isLoadingInitial && (
        <div className="cs-loading">
          <div className="cs-spinner-sm"></div>
          <span>Chargement de la localisation...</span>
        </div>
      )}

      {!showNewForm ? (
        <>
          <div className="cs-grid">
            {/* Pays */}
            <div className="cs-field">
              <select
                className={`cs-select ${error ? 'cs-error' : ''}`}
                value={selectedCountry}
                onChange={handleCountryChange}
                disabled={disabled || isLoadingInitial}
              >
                <option value="">Sélectionner un pays</option>
                {countries.map((country, index) => (
                  <option key={index} value={country}>
                    {country}
                  </option>
                ))}
              </select>
              {selectedCountry && (
                <div className="cs-badge">
                  <Globe size={12} />
                  {selectedCountry}
                </div>
              )}
            </div>

            {/* Ville */}
            <div className="cs-field">
              <select
                className={`cs-select ${error ? 'cs-error' : ''}`}
                value={selectedCity}
                onChange={handleCityChange}
                disabled={disabled || !selectedCountry || loading || isLoadingInitial}
              >
                <option value="">
                  {!selectedCountry ? 'Choisissez d\'abord un pays' : 
                   loading ? 'Chargement des villes...' : 'Sélectionner une ville'}
                </option>
                {cities.map((city) => (
                  <option key={city.id} value={city.id}>
                    {city.name}
                  </option>
                ))}
              </select>
              {selectedCity && !loading && !isLoadingInitial && (
                <div className="cs-badge">
                  <MapPin size={12} />
                  {getCurrentCityName()}
                </div>
              )}
            </div>
          </div>

          {showAddNew && !disabled && (
            <div className="cs-add-buttons">
              <button
                type="button"
                className="cs-add-btn cs-add-btn-secondary"
                onClick={startAddCityOnly}
                disabled={!selectedCountry}
                title={!selectedCountry ? "Sélectionnez d'abord un pays" : "Ajouter une ville dans le pays sélectionné"}
              >
                <Plus size={16} />
                Ajouter une ville
              </button>
              
              <button
                type="button"
                className="cs-add-btn cs-add-btn-primary"
                onClick={startAddCityCountry}
              >
                <Globe size={16} />
                Nouveau pays + ville
              </button>
            </div>
          )}
        </>
      ) : (
        <div className="cs-new-form">
          <div className="cs-new-header">
            <h4 className="cs-new-title">
              {addMode === 'city-only' ? 'Ajouter une nouvelle ville' : 'Ajouter un nouveau pays et une ville'}
            </h4>
            {addMode === 'city-only' && selectedCountry && (
              <div className="cs-new-info">
                Pays : <strong>{selectedCountry}</strong>
              </div>
            )}
          </div>

          <div className="cs-new-grid">
            {addMode === 'city-country' ? (
              <>
                <input
                  type="text"
                  className="cs-input"
                  placeholder="Nouveau pays (ex: Chine)"
                  value={newCountry}
                  onChange={(e) => setNewCountry(e.target.value)}
                  disabled={creating}
                />
                <input
                  type="text"
                  className="cs-input"
                  placeholder="Nouvelle ville (ex: Guangzhou)"
                  value={newCity}
                  onChange={(e) => setNewCity(e.target.value)}
                  disabled={creating}
                />
              </>
            ) : (
              <>
                <div className="cs-country-display">
                  <span className="cs-country-label">Pays</span>
                  <span className="cs-country-value">{selectedCountry}</span>
                </div>
                <input
                  type="text"
                  className="cs-input"
                  placeholder="Nouvelle ville (ex: Marseille)"
                  value={newCity}
                  onChange={(e) => setNewCity(e.target.value)}
                  disabled={creating}
                  autoFocus
                />
              </>
            )}
          </div>

          <div className="cs-new-actions">
            <button
              type="button"
              className="cs-btn cs-btn-cancel"
              onClick={cancelAdd}
              disabled={creating}
            >
              Annuler
            </button>
            <button
              type="button"
              className="cs-btn cs-btn-create"
              onClick={handleCreateNew}
              disabled={creating}
            >
              {creating ? (
                <>
                  <div className="cs-spinner-sm"></div>
                  Création...
                </>
              ) : (
                'Créer'
              )}
            </button>
          </div>
        </div>
      )}

      {error && (
        <div className="cs-error">
          {error}
        </div>
      )}
    </div>
  );
};

export default CoordinateSelect;