// ============================================
// pages/FreightForwarders/FreightForwardersList.jsx
// ============================================

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Truck, CheckCircle, XCircle, Star, Search, X, Plus, ArrowUpDown, PackageOpen, MapPin } from 'lucide-react';
import freightForwarderService from '../../services/freightForwarderService';
import coordinateService from '../../services/coordinateService';
import '../../styles/FreightForwardersList.css';

const FreightForwardersList = () => {
  const navigate = useNavigate();
  const [forwarders, setForwarders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  // Filtres et tri
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [countryFilter, setCountryFilter] = useState('');
  const [sortBy, setSortBy] = useState('name');
  const [sortOrder, setSortOrder] = useState('asc');
  
  // Liste des pays pour le filtre
  const [countries, setCountries] = useState([]);

  // Charger les transitaires
  useEffect(() => {
    loadFreightForwarders();
    loadCountries();
  }, []);

  const loadFreightForwarders = async () => {
    try {
      setLoading(true);
      setError(null);
      console.log('Chargement des transitaires...');
      const data = await freightForwarderService.getFreightForwarders();
      console.log('Transitaires chargés:', data);
      console.log('Type de données:', typeof data);
      console.log('Est un tableau?', Array.isArray(data));
      
      // Convertir l'objet en tableau si nécessaire
      let forwardersArray = [];
      
      if (Array.isArray(data)) {
        forwardersArray = data;
      } else if (data && typeof data === 'object') {
        // Si c'est un objet, convertir en tableau
        if (data.data && Array.isArray(data.data)) {
          forwardersArray = data.data;
        } else if (data.forwarders && Array.isArray(data.forwarders)) {
          forwardersArray = data.forwarders;
        } else if (data.results && Array.isArray(data.results)) {
          forwardersArray = data.results;
        } else {
          // Extraire les valeurs de l'objet
          forwardersArray = Object.values(data);
        }
      }
      
      console.log('Tableau final:', forwardersArray);
      setForwarders(forwardersArray);
    } catch (err) {
      console.error('Erreur détaillée:', err);
      setError(`Erreur lors du chargement des transitaires: ${err.message}`);
      setForwarders([]); // Réinitialiser à un tableau vide
    } finally {
      setLoading(false);
    }
  };

  // Charger les pays
  const loadCountries = async () => {
    try {
      const data = await coordinateService.getCountries();
      setCountries(data || []);
    } catch (err) {
      console.error('Erreur chargement pays:', err);
    }
  };

  // Fonction pour parser le score de service
  const parseServiceScore = (score) => {
    if (score === null || score === undefined) return 0;
    const num = parseFloat(score);
    return isNaN(num) ? 0 : num;
  };

  // Fonction utilitaire pour convertir les données en tableau
  const getForwardersArray = () => {
    if (Array.isArray(forwarders)) {
      return forwarders;
    } else if (forwarders && typeof forwarders === 'object') {
      // Essayer de convertir l'objet en tableau
      const values = Object.values(forwarders);
      return Array.isArray(values[0]) ? values[0] : values;
    }
    return [];
  };

  // Filtrage et tri - utilisation de la fonction utilitaire
  const forwardersArray = getForwardersArray();
  
  const filteredForwarders = forwardersArray
    .filter(forwarder => {
      if (!forwarder || typeof forwarder !== 'object') return false;
      
      // Filtre par statut
      if (statusFilter === 'active' && !forwarder.is_active) return false;
      if (statusFilter === 'inactive' && forwarder.is_active) return false;
      
      // Filtre par pays
      if (countryFilter && forwarder.coordinate?.country !== countryFilter) return false;
      
      // Filtre par recherche
      if (searchQuery) {
        const search = searchQuery.toLowerCase();
        return (
          forwarder.name?.toLowerCase().includes(search) ||
          forwarder.contact?.toLowerCase().includes(search) ||
          forwarder.coordinate?.city?.toLowerCase().includes(search) ||
          forwarder.coordinate?.country?.toLowerCase().includes(search)
        );
      }
      
      return true;
    })
    .sort((a, b) => {
      let compareValue = 0;
      
      switch (sortBy) {
        case 'name':
          compareValue = (a.name || '').localeCompare(b.name || '');
          break;
        case 'service_score':
          compareValue = parseServiceScore(b.service_score) - parseServiceScore(a.service_score);
          break;
        case 'date':
          const dateA = new Date(a.created_at || 0);
          const dateB = new Date(b.created_at || 0);
          compareValue = dateB - dateA;
          break;
        default:
          compareValue = 0;
      }
      
      return sortOrder === 'asc' ? compareValue : -compareValue;
    });

  // Statistiques
  const calculateStats = () => {
    const validForwarders = forwardersArray;
    const total = validForwarders.length;
    const active = validForwarders.filter(f => f && f.is_active).length;
    const inactive = validForwarders.filter(f => f && !f.is_active).length;
    
    // Calcul de la note moyenne
    const scores = validForwarders.map(f => parseServiceScore(f?.service_score));
    const validScores = scores.filter(score => !isNaN(score) && score !== null);
    const sum = validScores.reduce((acc, score) => acc + score, 0);
    
    let avgScore = 0;
    if (validScores.length > 0) {
      avgScore = sum / validScores.length;
    }
    
    // Arrondir à 1 décimale
    avgScore = Math.round(avgScore * 10) / 10;
    
    return {
      total,
      active,
      inactive,
      avgScore
    };
  };

  const stats = calculateStats();

  const handleSort = (field) => {
    if (sortBy === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(field);
      setSortOrder('asc');
    }
  };

  const getScoreColor = (score) => {
    const parsedScore = parseServiceScore(score);
    if (parsedScore >= 8) return 'success';
    if (parsedScore >= 6) return 'warning';
    return 'danger';
  };

  const getScoreLabel = (score) => {
    const parsedScore = parseServiceScore(score);
    if (parsedScore >= 8) return 'Excellent';
    if (parsedScore >= 6) return 'Bon';
    if (parsedScore >= 4) return 'Moyen';
    return 'Faible';
  };

  const formatServiceScore = (score) => {
    const parsed = parseServiceScore(score);
    return isNaN(parsed) ? '0.0' : parsed.toFixed(1);
  };

  // Ajouter un bouton pour recharger en cas d'erreur
  const handleRetry = () => {
    loadFreightForwarders();
    loadCountries();
  };

  if (loading) {
    return (
      <div className="freight-list-page">
        <div className="freight-list-loading">
          <div className="freight-list-spinner large"></div>
          <p>Chargement des transitaires...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="freight-list-page">
      {/* Header */}
      <div className="freight-list-header">
        <div className="freight-list-header-content">
          <h1 className="freight-list-title">
            <Truck className="freight-list-title-icon" size={28} />
            Transitaires
          </h1>
          <p className="freight-list-subtitle">
            Gérez vos transitaires pour le transport depuis la Chine
          </p>
        </div>
        <button 
          className="freight-list-btn-primary"
          onClick={() => navigate('/transitaires/nouveau')}
        >
          <Plus size={18} />
          Nouveau Transitaire
        </button>
      </div>

      {/* Messages d'erreur */}
      {error && (
        <div className="freight-list-error">
          <XCircle size={18} />
          <div className="freight-list-error-content">
            <span>{error}</span>
            <button className="freight-list-retry-btn" onClick={handleRetry}>
              Réessayer
            </button>
          </div>
        </div>
      )}

      {/* Statistiques */}
      <div className="freight-list-stats">
        <div className="freight-list-stat-card">
          <div className="freight-list-stat-icon" style={{ background: 'var(--primary-light)', color: 'var(--primary)' }}>
            <Truck size={24} />
          </div>
          <div className="freight-list-stat-info">
            <div className="freight-list-stat-value">{stats.total}</div>
            <div className="freight-list-stat-label">Total</div>
          </div>
        </div>
        
        <div className="freight-list-stat-card">
          <div className="freight-list-stat-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
            <CheckCircle size={24} />
          </div>
          <div className="freight-list-stat-info">
            <div className="freight-list-stat-value">{stats.active}</div>
            <div className="freight-list-stat-label">Actifs</div>
          </div>
        </div>
        
        <div className="freight-list-stat-card">
          <div className="freight-list-stat-icon" style={{ background: 'var(--danger-light)', color: 'var(--danger)' }}>
            <XCircle size={24} />
          </div>
          <div className="freight-list-stat-info">
            <div className="freight-list-stat-value">{stats.inactive}</div>
            <div className="freight-list-stat-label">Inactifs</div>
          </div>
        </div>
        
        <div className="freight-list-stat-card">
          <div className="freight-list-stat-icon" style={{ background: 'var(--warning-light)', color: 'var(--warning)' }}>
            <Star size={24} />
          </div>
          <div className="freight-list-stat-info">
            <div className="freight-list-stat-value">{stats.avgScore}/10</div>
            <div className="freight-list-stat-label">Note Moyenne</div>
          </div>
        </div>
      </div>

      {/* Filtres */}
      <div className="freight-list-filters">
        <div className="freight-list-search">
          <Search className="freight-list-search-icon" size={18} />
          <input
            type="text"
            className="freight-list-search-input"
            placeholder="Rechercher par nom, contact, localisation..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
          {searchQuery && (
            <button 
              className="freight-list-clear-btn"
              onClick={() => setSearchQuery('')}
            >
              <X size={14} />
            </button>
          )}
        </div>

        <div className="freight-list-filter-group">
          <label className="freight-list-filter-label">Statut:</label>
          <select 
            className="freight-list-filter-select"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="all">Tous</option>
            <option value="active">Actifs</option>
            <option value="inactive">Inactifs</option>
          </select>
        </div>

        {/* Filtre par pays */}
        <div className="freight-list-filter-group">
          <label className="freight-list-filter-label">Pays:</label>
          <select 
            className="freight-list-filter-select"
            value={countryFilter}
            onChange={(e) => setCountryFilter(e.target.value)}
          >
            <option value="">Tous les pays</option>
            {countries.map((country, index) => (
              <option key={index} value={country}>
                {country}
              </option>
            ))}
          </select>
        </div>

        <div className="freight-list-filter-group">
          <label className="freight-list-filter-label">Trier par:</label>
          <select 
            className="freight-list-filter-select"
            value={sortBy}
            onChange={(e) => handleSort(e.target.value)}
          >
            <option value="name">Nom</option>
            <option value="service_score">Note de Service</option>
            <option value="date">Date d'ajout</option>
          </select>
          <button 
            className="freight-list-sort-btn"
            onClick={() => setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc')}
            title={sortOrder === 'asc' ? 'Croissant' : 'Décroissant'}
          >
            <ArrowUpDown size={18} />
          </button>
        </div>
      </div>

      {/* Liste des transitaires */}
      {filteredForwarders.length === 0 ? (
        <div className="freight-list-empty">
          <PackageOpen className="freight-list-empty-icon" size={64} />
          <h3>
            {forwardersArray.length === 0 ? 'Aucun transitaire enregistré' : 'Aucun transitaire trouvé'}
          </h3>
          <p>
            {error 
              ? 'Une erreur est survenue lors du chargement'
              : searchQuery || statusFilter !== 'all' || countryFilter
                ? 'Essayez de modifier vos filtres de recherche'
                : 'Commencez par ajouter votre premier transitaire'}
          </p>
          <div className="freight-list-empty-actions">
            {error && (
              <button className="freight-list-btn-secondary" onClick={handleRetry}>
                Réessayer le chargement
              </button>
            )}
            <button 
              className="freight-list-btn-primary"
              onClick={() => navigate('/transitaires/nouveau')}
            >
              <Plus size={18} /> Ajouter un transitaire
            </button>
          </div>
        </div>
      ) : (
        <div className="freight-list-grid">
          {filteredForwarders.map((forwarder, index) => {
            // Utiliser index comme clé de secours si forwarder.id n'existe pas
            const key = forwarder?.id || index;
            
            return (
              <div 
                key={key} 
                className="freight-list-card"
                onClick={() => forwarder?.id && navigate(`/transitaires/${forwarder.id}`)}
              >
                {/* Logo */}
                <div className="freight-list-logo">
                  {forwarder?.logo_url ? (
                    <img src={forwarder.logo_url} alt={forwarder.name} />
                  ) : (
                    <div className="freight-list-logo-placeholder">
                      {forwarder?.name?.charAt(0).toUpperCase() || 'T'}
                    </div>
                  )}
                </div>

                {/* Informations */}
                <div className="freight-list-info">
                  <div className="freight-list-card-header">
                    <h3 className="freight-list-name">{forwarder?.name || 'Transitaire sans nom'}</h3>
                    {forwarder && !forwarder.is_active && (
                      <span className="freight-list-status-badge inactive">Inactif</span>
                    )}
                  </div>

                  {/* Note de service */}
                  <div className="freight-list-score">
                    <span className={`freight-list-score-value ${getScoreColor(forwarder?.service_score)}`}>
                      <Star size={14} /> {formatServiceScore(forwarder?.service_score)}/10
                    </span>
                    <span className="freight-list-score-label">
                      {getScoreLabel(forwarder?.service_score)}
                    </span>
                  </div>

                  {/* Localisation */}
                  {forwarder?.coordinate && (
                    <div className="freight-list-detail">
                      <MapPin size={16} className="freight-list-detail-icon" />
                      <span className="freight-list-detail-text">
                        {forwarder.coordinate.full_location || 
                         `${forwarder.coordinate.city || ''}, ${forwarder.coordinate.country || ''}`}
                      </span>
                    </div>
                  )}

                  {/* Contact */}
                  {forwarder?.contact && (
                    <div className="freight-list-detail">
                      <span className="freight-list-detail-icon">📞</span>
                      <span className="freight-list-detail-text">{forwarder.contact}</span>
                    </div>
                  )}

                  {/* Notes */}
                  {forwarder?.notes && (
                    <p className="freight-list-notes">{forwarder.notes}</p>
                  )}
                </div>

                {/* Footer */}
                <div className="freight-list-footer">
                  <button 
                    className="freight-list-view-btn"
                    onClick={(e) => {
                      e.stopPropagation();
                      if (forwarder?.id) {
                        navigate(`/transitaires/${forwarder.id}`);
                      }
                    }}
                    disabled={!forwarder?.id}
                  >
                    Voir détails →
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};

export default FreightForwardersList;