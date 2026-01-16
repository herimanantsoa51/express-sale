// ============================================
// pages/Suppliers/SuppliersList.jsx
// ============================================

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Package, CheckCircle, XCircle, Star, Search, X, Plus, ArrowUpDown, PackageOpen, MapPin } from 'lucide-react';
import supplierService from '../../services/supplierService';
import coordinateService from '../../services/coordinateService';
import '../../styles/SuppliersList.css';

const SuppliersList = () => {
  const navigate = useNavigate();
  const [suppliers, setSuppliers] = useState([]);
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

  useEffect(() => {
    loadSuppliers();
    loadCountries();
  }, []);

  const loadSuppliers = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await supplierService.getSuppliers();
      setSuppliers(data);
    } catch (err) {
      setError('Erreur lors du chargement des fournisseurs');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const loadCountries = async () => {
    try {
      const data = await coordinateService.getCountries();
      setCountries(data);
    } catch (err) {
      console.error('Erreur chargement pays:', err);
    }
  };

  // Fonction pour parser le score de fiabilité
  const parseReliabilityScore = (score) => {
    if (score === null || score === undefined) return 0;
    const num = parseFloat(score);
    return isNaN(num) ? 0 : num;
  };

  // Filtrage et tri
  const filteredSuppliers = suppliers
    .filter(supplier => {
      if (statusFilter === 'active' && !supplier.is_active) return false;
      if (statusFilter === 'inactive' && supplier.is_active) return false;
      
      if (countryFilter && supplier.coordinate?.country !== countryFilter) return false;
      
      if (searchQuery) {
        const search = searchQuery.toLowerCase();
        return (
          supplier.name?.toLowerCase().includes(search) ||
          supplier.contact?.toLowerCase().includes(search) ||
          supplier.wechat?.toLowerCase().includes(search) ||
          supplier.coordinate?.city?.toLowerCase().includes(search) ||
          supplier.coordinate?.country?.toLowerCase().includes(search)
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
        case 'reliability':
          compareValue = parseReliabilityScore(b.reliability_score) - parseReliabilityScore(a.reliability_score);
          break;
        case 'date':
          compareValue = new Date(b.created_at || 0) - new Date(a.created_at || 0);
          break;
        default:
          compareValue = 0;
      }
      
      return sortOrder === 'asc' ? compareValue : -compareValue;
    });

  // Statistiques avec gestion des NaN
  const calculateStats = () => {
    const total = suppliers.length;
    const active = suppliers.filter(s => s.is_active).length;
    const inactive = suppliers.filter(s => !s.is_active).length;
    
    // Calcul de la fiabilité moyenne
    const scores = suppliers.map(s => parseReliabilityScore(s.reliability_score));
    const validScores = scores.filter(score => !isNaN(score) && score !== null);
    const sum = validScores.reduce((acc, score) => acc + score, 0);
    
    let avgReliability = 0;
    if (validScores.length > 0) {
      avgReliability = sum / validScores.length;
    }
    
    // Arrondir à 1 décimale
    avgReliability = Math.round(avgReliability * 10) / 10;
    
    return {
      total,
      active,
      inactive,
      avgReliability
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

  const getReliabilityColor = (score) => {
    const parsedScore = parseReliabilityScore(score);
    if (parsedScore >= 8) return 'success';
    if (parsedScore >= 6) return 'warning';
    return 'danger';
  };

  const getReliabilityLabel = (score) => {
    const parsedScore = parseReliabilityScore(score);
    if (parsedScore >= 8) return 'Excellent';
    if (parsedScore >= 6) return 'Bon';
    if (parsedScore >= 4) return 'Moyen';
    return 'Faible';
  };

  const formatReliabilityScore = (score) => {
    const parsed = parseReliabilityScore(score);
    return isNaN(parsed) ? '0.0' : parsed.toFixed(1);
  };

  if (loading) {
    return (
      <div className="suppliers-list-page">
        <div className="suppliers-list-loading">
          <div className="suppliers-list-spinner large"></div>
          <p>Chargement des fournisseurs...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="suppliers-list-page">
      {/* Header */}
      <div className="suppliers-list-header">
        <div className="suppliers-list-header-content">
          <h1 className="suppliers-list-title">
            <Package className="suppliers-list-title-icon" size={28} />
            Fournisseurs
          </h1>
          <p className="suppliers-list-subtitle">
            Gérez vos fournisseurs et leurs informations
          </p>
        </div>
        <button 
          className="suppliers-list-btn-primary"
          onClick={() => navigate('/fournisseurs/nouveau')}
        >
          <Plus size={18} />
          Nouveau Fournisseur
        </button>
      </div>

      {/* Statistiques */}
      <div className="suppliers-list-stats">
        <div className="suppliers-list-stat-card">
          <div className="suppliers-list-stat-icon" style={{ background: 'var(--primary-light)', color: 'var(--primary)' }}>
            <Package size={24} />
          </div>
          <div className="suppliers-list-stat-info">
            <div className="suppliers-list-stat-value">{stats.total}</div>
            <div className="suppliers-list-stat-label">Total</div>
          </div>
        </div>
        
        <div className="suppliers-list-stat-card">
          <div className="suppliers-list-stat-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
            <CheckCircle size={24} />
          </div>
          <div className="suppliers-list-stat-info">
            <div className="suppliers-list-stat-value">{stats.active}</div>
            <div className="suppliers-list-stat-label">Actifs</div>
          </div>
        </div>
        
        <div className="suppliers-list-stat-card">
          <div className="suppliers-list-stat-icon" style={{ background: 'var(--danger-light)', color: 'var(--danger)' }}>
            <XCircle size={24} />
          </div>
          <div className="suppliers-list-stat-info">
            <div className="suppliers-list-stat-value">{stats.inactive}</div>
            <div className="suppliers-list-stat-label">Inactifs</div>
          </div>
        </div>
        
        <div className="suppliers-list-stat-card">
          <div className="suppliers-list-stat-icon" style={{ background: 'var(--warning-light)', color: 'var(--warning)' }}>
            <Star size={24} />
          </div>
          <div className="suppliers-list-stat-info">
            <div className="suppliers-list-stat-value">{stats.avgReliability}/10</div>
            <div className="suppliers-list-stat-label">Fiabilité Moyenne</div>
          </div>
        </div>
      </div>

      {/* Filtres */}
      <div className="suppliers-list-filters">
        <div className="suppliers-list-search">
          <Search className="suppliers-list-search-icon" size={18} />
          <input
            type="text"
            className="suppliers-list-search-input"
            placeholder="Rechercher par nom, contact, WeChat..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
          {searchQuery && (
            <button 
              className="suppliers-list-clear-btn"
              onClick={() => setSearchQuery('')}
            >
              <X size={14} />
            </button>
          )}
        </div>

        <div className="suppliers-list-filter-group">
          <label className="suppliers-list-filter-label">Statut:</label>
          <select 
            className="suppliers-list-filter-select"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="all">Tous</option>
            <option value="active">Actifs</option>
            <option value="inactive">Inactifs</option>
          </select>
        </div>

        <div className="suppliers-list-filter-group">
          <label className="suppliers-list-filter-label">Pays:</label>
          <select 
            className="suppliers-list-filter-select"
            value={countryFilter}
            onChange={(e) => setCountryFilter(e.target.value)}
          >
            <option value="">Tous les pays</option>
            {countries.map((country) => (
              <option key={country} value={country}>
                {country}
              </option>
            ))}
          </select>
        </div>

        <div className="suppliers-list-filter-group">
          <label className="suppliers-list-filter-label">Trier par:</label>
          <select 
            className="suppliers-list-filter-select"
            value={sortBy}
            onChange={(e) => handleSort(e.target.value)}
          >
            <option value="name">Nom</option>
            <option value="reliability">Fiabilité</option>
            <option value="date">Date d'ajout</option>
          </select>
          <button 
            className="suppliers-list-sort-btn"
            onClick={() => setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc')}
            title={sortOrder === 'asc' ? 'Croissant' : 'Décroissant'}
          >
            <ArrowUpDown size={18} />
          </button>
        </div>
      </div>

      {error && (
        <div className="suppliers-list-error">
          <XCircle size={18} />
          {error}
        </div>
      )}

      {/* Liste des fournisseurs */}
      {filteredSuppliers.length === 0 ? (
        <div className="suppliers-list-empty">
          <PackageOpen className="suppliers-list-empty-icon" size={64} />
          <h3>Aucun fournisseur trouvé</h3>
          <p>
            {searchQuery || statusFilter !== 'all' || countryFilter
              ? 'Essayez de modifier vos filtres de recherche'
              : 'Commencez par ajouter votre premier fournisseur'}
          </p>
          {!searchQuery && statusFilter === 'all' && !countryFilter && (
            <button 
              className="suppliers-list-btn-primary"
              onClick={() => navigate('/fournisseurs/nouveau')}
            >
              <Plus size={18} /> Ajouter un fournisseur
            </button>
          )}
        </div>
      ) : (
        <div className="suppliers-list-grid">
          {filteredSuppliers.map((supplier) => (
            <div 
              key={supplier.id} 
              className="suppliers-list-card"
              onClick={() => navigate(`/fournisseurs/${supplier.id}`)}
            >
              <div className="suppliers-list-logo">
                {supplier.logo_url ? (
                  <img src={supplier.logo_url} alt={supplier.name} />
                ) : (
                  <div className="suppliers-list-logo-placeholder">
                    {supplier.name?.charAt(0).toUpperCase()}
                  </div>
                )}
              </div>

              <div className="suppliers-list-info">
                <div className="suppliers-list-card-header">
                  <h3 className="suppliers-list-name">{supplier.name}</h3>
                  {!supplier.is_active && (
                    <span className="suppliers-list-status-badge inactive">Inactif</span>
                  )}
                </div>

                <div className="suppliers-list-reliability">
                  <span className={`suppliers-list-reliability-score ${getReliabilityColor(supplier.reliability_score)}`}>
                    <Star size={14} /> {formatReliabilityScore(supplier.reliability_score)}/10
                  </span>
                  <span className="suppliers-list-reliability-label">
                    {getReliabilityLabel(supplier.reliability_score)}
                  </span>
                </div>

                {supplier.contact && (
                  <div className="suppliers-list-contact">
                    <span className="suppliers-list-contact-icon">📞</span>
                    <span className="suppliers-list-contact-text">{supplier.contact}</span>
                  </div>
                )}

                {supplier.wechat && (
                  <div className="suppliers-list-contact">
                    <span className="suppliers-list-contact-icon">💬</span>
                    <span className="suppliers-list-contact-text">{supplier.wechat}</span>
                  </div>
                )}

                {supplier.coordinate && (
                  <div className="suppliers-list-contact">
                    <MapPin size={16} className="suppliers-list-contact-icon" />
                    <span className="suppliers-list-contact-text">{supplier.coordinate.full_location}</span>
                  </div>
                )}

                {supplier.profile && (
                  <p className="suppliers-list-profile">{supplier.profile}</p>
                )}
              </div>

              <div className="suppliers-list-footer">
                <button 
                  className="suppliers-list-view-btn"
                  onClick={(e) => {
                    e.stopPropagation();
                    navigate(`/fournisseurs/${supplier.id}`);
                  }}
                >
                  Voir détails →
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default SuppliersList;