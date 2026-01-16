import { useState, useEffect, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Warehouse,
  MapPin,
  Package,
  Search,
  X,
  ArrowUpDown,
  Plus,
  BarChart3,
  RefreshCw
} from 'lucide-react';
import locationService from '../../services/locationService';
import './LocationList.css';

const LocationList = () => {
  const navigate = useNavigate();

  const [locations, setLocations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Filtres
  const [searchTerm, setSearchTerm] = useState('');
  const [warehouseFilter, setWarehouseFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [sortBy, setSortBy] = useState('name');
  const [sortOrder, setSortOrder] = useState('asc');

  const [warehouses, setWarehouses] = useState([]);

  useEffect(() => {
    loadLocations();
  }, []);

  const loadLocations = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await locationService.getAll();
      setLocations(Array.isArray(data) ? data : []);

      const uniqueWarehouses = [
        ...new Set((data || []).map(l => l.warehouse).filter(Boolean))
      ];
      setWarehouses(uniqueWarehouses);
    } catch (err) {
      console.error(err);
      setError('Impossible de charger les locations');
    } finally {
      setLoading(false);
    }
  };

  // ==========================
  // STATISTIQUES (LOGIQUE ORIGINALE)
  // ==========================
  const stats = useMemo(() => {
    return {
      total: locations.length,
      active: locations.filter(l => l.is_active).length,
      inactive: locations.filter(l => !l.is_active).length,
      totalStock: locations.reduce(
        (sum, l) => sum + (l.statistics?.total_quantity || 0),
        0
      ),
      averageOccupancy: (() => {
        const values = locations
          .filter(l => l.capacity && l.statistics?.occupancy_percentage != null)
          .map(l => l.statistics.occupancy_percentage);

        return values.length
          ? values.reduce((a, b) => a + b, 0) / values.length
          : 0;
      })()
    };
  }, [locations]);

  // ==========================
  // FILTRAGE + TRI
  // ==========================
  const filteredLocations = useMemo(() => {
    let data = [...locations];

    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      data = data.filter(l =>
        l.name?.toLowerCase().includes(term) ||
        l.code?.toLowerCase().includes(term) ||
        l.warehouse?.toLowerCase().includes(term)
      );
    }

    if (warehouseFilter) {
      data = data.filter(l => l.warehouse === warehouseFilter);
    }

    if (statusFilter !== 'all') {
      data = data.filter(l =>
        statusFilter === 'active' ? l.is_active : !l.is_active
      );
    }

    data.sort((a, b) => {
      let aVal = 0;
      let bVal = 0;

      switch (sortBy) {
        case 'name':
          aVal = a.name || '';
          bVal = b.name || '';
          break;
        case 'warehouse':
          aVal = a.warehouse || '';
          bVal = b.warehouse || '';
          break;
        case 'stock':
          aVal = a.statistics?.total_quantity || 0;
          bVal = b.statistics?.total_quantity || 0;
          break;
        case 'occupancy':
          aVal = a.statistics?.occupancy_percentage || 0;
          bVal = b.statistics?.occupancy_percentage || 0;
          break;
        default:
          break;
      }

      if (aVal < bVal) return sortOrder === 'asc' ? -1 : 1;
      if (aVal > bVal) return sortOrder === 'asc' ? 1 : -1;
      return 0;
    });

    return data;
  }, [
    locations,
    searchTerm,
    warehouseFilter,
    statusFilter,
    sortBy,
    sortOrder
  ]);

  const toggleSortOrder = () =>
    setSortOrder(prev => (prev === 'asc' ? 'desc' : 'asc'));

  const getOccupancyColor = (percentage) => {
    if (percentage == null) return 'neutral';
    if (percentage >= 90) return 'danger';
    if (percentage >= 70) return 'warning';
    return 'success';
  };

  // ==========================
  // RENDER
  // ==========================
  if (loading) {
    return (
      <div className="loclist-page">
        <div className="loclist-loading">
          <div className="loclist-spinner large" />
          <p>Chargement des locations...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="loclist-page">
        <div className="loclist-error">
          <Warehouse size={48} />
          <h3>{error}</h3>
          <button className="loclist-btn-primary" onClick={loadLocations}>
            <RefreshCw size={18} /> Réessayer
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="loclist-page">

      {/* HEADER */}
      <div className="loclist-header">
        <div>
          <h1 className="loclist-title">
            <Warehouse size={28} /> Locations
          </h1>
          <p className="loclist-subtitle">
            Gestion des emplacements de stockage
          </p>
        </div>
        <button
          className="loclist-btn-primary"
          onClick={() => navigate('/locations/nouveau')}
        >
          <Plus size={18} /> Nouvelle location
        </button>
      </div>

      {/* STATS */}
      <div className="loclist-stats">
        <StatCard icon={<Warehouse size={24} />} value={stats.total} label="Total" />
        <StatCard icon={<Package size={24} />} value={stats.totalStock.toLocaleString()} label="Stock total" />
        <StatCard icon={<BarChart3 size={24} />} value={stats.active} label="Actives" />
        <StatCard icon={<MapPin size={24} />} value={`${stats.averageOccupancy.toFixed(0)}%`} label="Occupation moyenne" />
      </div>

      {/* FILTERS */}
      <div className="loclist-filters">
        <div className="loclist-search">
          <Search size={16} />
          <input
            placeholder="Rechercher..."
            value={searchTerm}
            onChange={e => setSearchTerm(e.target.value)}
          />
          {searchTerm && (
            <button onClick={() => setSearchTerm('')}>
              <X size={14} />
            </button>
          )}
        </div>

        <select value={warehouseFilter} onChange={e => setWarehouseFilter(e.target.value)}>
          <option value="">Tous les entrepôts</option>
          {warehouses.map(w => (
            <option key={w} value={w}>{w}</option>
          ))}
        </select>

        <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)}>
          <option value="all">Tous</option>
          <option value="active">Actives</option>
          <option value="inactive">Inactives</option>
        </select>

        <select value={sortBy} onChange={e => setSortBy(e.target.value)}>
          <option value="name">Nom</option>
          <option value="warehouse">Entrepôt</option>
          <option value="stock">Stock</option>
          <option value="occupancy">Occupation</option>
        </select>

        <button onClick={toggleSortOrder}>
          <ArrowUpDown size={16} />
        </button>
      </div>

      {/* GRID */}
      <div className="loclist-grid">
        {filteredLocations.map(loc => {
          const occupancy = loc.statistics?.occupancy_percentage;
          return (
            <div
              key={loc.id}
              className="loclist-card"
              onClick={() => navigate(`/locations/${loc.id}`)}
            >
              <h3>{loc.name}</h3>
              <p>{loc.code}</p>

              <div className="loclist-meta">
                <MapPin size={14} /> {loc.warehouse}
              </div>

              <div className="loclist-card-stats">
                <span>
                  <Package size={14} />
                  {loc.statistics?.total_quantity || 0} articles
                </span>

                {loc.capacity && (
                  <span className={`occupancy ${getOccupancyColor(occupancy)}`}>
                    {occupancy?.toFixed(0)}%
                  </span>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

const StatCard = ({ icon, value, label }) => (
  <div className="loclist-stat-card">
    <div className="loclist-stat-icon">{icon}</div>
    <div>
      <div className="loclist-stat-value">{value}</div>
      <div className="loclist-stat-label">{label}</div>
    </div>
  </div>
);

export default LocationList;