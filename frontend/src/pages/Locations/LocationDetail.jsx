import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  MapPin,
  ArrowLeft,
  Edit2,
  Trash2,
  Package,
  BarChart3,
  Warehouse,
  Clock,
  ArrowRightLeft,
  TrendingUp,
  TrendingDown,
  Search,
  ChevronLeft,
  ChevronRight,
  RefreshCw,
  X,
  XCircle,
  Box,
  Calendar,
  User,
  AlertTriangle
} from 'lucide-react';
import locationService from '../../services/locationService';
import './Locations.css';

const LocationDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [location, setLocation] = useState(null);
  const [statistics, setStatistics] = useState(null);
  const [variants, setVariants] = useState([]);
  const [movements, setMovements] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Tabs
  const [activeTab, setActiveTab] = useState('variants');

  // Filtres variantes
  const [variantSearch, setVariantSearch] = useState('');
  const [variantStockFilter, setVariantStockFilter] = useState('all');
  const [variantPage, setVariantPage] = useState(1);
  const [variantTotal, setVariantTotal] = useState(0);

  // Filtres mouvements
  const [movementType, setMovementType] = useState('');
  const [movementDays, setMovementDays] = useState(30);
  const [movementPage, setMovementPage] = useState(1);
  const [movementTotal, setMovementTotal] = useState(0);

  // Réservations
  const [reservations, setReservations] = useState([]);
  const [reservationsLoading, setReservationsLoading] = useState(false);
  const [totalReserved, setTotalReserved] = useState(0);
  const [reservationPage, setReservationPage] = useState(1);
  const [reservationTotal, setReservationTotal] = useState(0);

  useEffect(() => {
    loadLocation();
    loadStatistics();
    loadReservationCount(); // Charger le nombre de réservations dès le début
  }, [id]);

  useEffect(() => {
    if (activeTab === 'variants') {
      loadVariants();
    } else if (activeTab === 'movements') {
      loadMovements();
    } else if (activeTab === 'reservations') {
      loadReservations();
    }
  }, [activeTab, variantPage, variantSearch, variantStockFilter, movementPage, movementType, movementDays, reservationPage]);

  const loadLocation = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await locationService.getById(id);
      setLocation(data);
    } catch (err) {
      console.error('Erreur chargement location:', err);
      setError('Erreur lors du chargement de la location');
    } finally {
      setLoading(false);
    }
  };

  const loadStatistics = async () => {
    try {
      const data = await locationService.getStatistics(id);
      setStatistics(data);
    } catch (err) {
      console.error('Erreur chargement statistiques:', err);
    }
  };

  const loadVariants = async () => {
    try {
      const params = {
        page: variantPage,
        per_page: 10,
        search: variantSearch || undefined,
        has_stock: variantStockFilter === 'with_stock' ? true : variantStockFilter === 'without_stock' ? false : undefined
      };
      const data = await locationService.getVariantsDetail(id, params);
      setVariants(data.data || []);
      setVariantTotal(data.total || 0);
    } catch (err) {
      console.error('Erreur chargement variantes:', err);
    }
  };

  const loadMovements = async () => {
    try {
      const params = {
        page: movementPage,
        per_page: 15,
        movement_type: movementType || undefined,
        days: movementDays
      };
      const data = await locationService.getStockMovements(id, params);
      setMovements(data.data || []);
      setMovementTotal(data.total || 0);
    } catch (err) {
      console.error('Erreur chargement mouvements:', err);
    }
  };

  const loadReservations = async () => {
    try {
      setReservationsLoading(true);
      const params = {
        page: reservationPage,
        per_page: 10
      };
      const data = await locationService.getActiveReservations(id, params);
      setReservations(data.data || []);
      setTotalReserved(data.total_reserved_quantity || 0);
      setReservationTotal(data.meta?.total || 0);
    } catch (err) {
      console.error('Erreur chargement réservations:', err);
    } finally {
      setReservationsLoading(false);
    }
  };

  const loadReservationCount = async () => {
    try {
      const data = await locationService.getActiveReservations(id, { per_page: 1 });
      setTotalReserved(data.total_reserved_quantity || 0);
    } catch (err) {
      console.error('Erreur chargement nombre réservations:', err);
    }
  };

  const getReservationStatusBadge = (status) => {
    const badges = {
      pending: { label: 'En attente', color: 'warning' },
      confirmed: { label: 'Confirmée', color: 'success' },
      partial_paid: { label: 'Partiellement payée', color: 'info' }
    };
    const badge = badges[status] || { label: status, color: 'neutral' };
    return <span className={`status-badge ${badge.color}`}>{badge.label}</span>;
  };

  const getDaysRemainingBadge = (days) => {
    if (days < 0) {
      return <span className="days-badge expired"><AlertTriangle size={14} /> Expirée</span>;
    }
    if (days === 0) {
      return <span className="days-badge today"><Clock size={14} /> Aujourd'hui</span>;
    }
    if (days <= 3) {
      return <span className="days-badge urgent"><Clock size={14} /> {days}j</span>;
    }
    return <span className="days-badge normal"><Clock size={14} /> {days}j</span>;
  };

  const handleDelete = async () => {
    try {
      const canDeleteResponse = await locationService.canDelete(id);

      if (!canDeleteResponse.can_delete) {
        alert(canDeleteResponse.reason);
        return;
      }

      if (!confirm('Êtes-vous sûr de vouloir supprimer cette location ?')) {
        return;
      }

      await locationService.delete(id);
      navigate('/locations');
    } catch (err) {
      alert(err.response?.data?.message || 'Erreur lors de la suppression');
    }
  };

  const getMovementTypeLabel = (type) => {
    const labels = {
      transfer: 'Transfert',
      receipt: 'Réception',
      sale: 'Vente',
      adjustment: 'Ajustement',
      return: 'Retour'
    };
    return labels[type] || type;
  };

  const getMovementTypeIcon = (type, fromId, toId) => {
    if (type === 'transfer') {
      return <ArrowRightLeft size={16} className="movement-icon transfer" />;
    }
    if (toId == id) {
      return <TrendingUp size={16} className="movement-icon incoming" />;
    }
    return <TrendingDown size={16} className="movement-icon outgoing" />;
  };

  const getOccupancyColor = (percentage) => {
    if (percentage === null || percentage === undefined) return 'neutral';
    if (percentage >= 90) return 'danger';
    if (percentage >= 70) return 'warning';
    return 'success';
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading) {
    return (
      <div className="location-detail-page">
        <div className="loading-container">
          <div className="loading-spinner large"></div>
          <p>Chargement de la location...</p>
        </div>
      </div>
    );
  }

  if (error || !location) {
    return (
      <div className="location-detail-page">
        <div className="error-state">
          <XCircle size={48} />
          <h3>{error || 'Location non trouvée'}</h3>
          <button className="btn-primary" onClick={() => navigate('/locations')}>
            <ArrowLeft size={18} />
            Retour aux locations
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="location-detail-page">
      {/* Header */}
      <div className="detail-header">
        <button className="btn-back" onClick={() => navigate('/locations')}>
          <ArrowLeft size={20} />
          Retour
        </button>

        <div className="header-info">
          <div className="header-title-row">
            <div className="location-icon-large">
              <Warehouse size={32} />
            </div>
            <div>
              <h1 className="detail-title">{location.name}</h1>
              <span className="detail-code">{location.code}</span>
            </div>
            {!location.is_active && (
              <span className="status-badge inactive large">Inactive</span>
            )}
          </div>
          <p className="detail-path">
            <MapPin size={16} />
            {location.warehouse}
            {location.aisle && ` / Allée ${location.aisle}`}
            {location.shelf && ` / Étagère ${location.shelf}`}
            {location.bin && ` / Bac ${location.bin}`}
          </p>
        </div>

        <div className="header-actions">
          <button
            className="btn-secondary"
            onClick={() => navigate(`/locations/${id}/modifier`)}
          >
            <Edit2 size={18} />
            Modifier
          </button>
          <button className="btn-danger" onClick={handleDelete}>
            <Trash2 size={18} />
            Supprimer
          </button>
        </div>
      </div>

      {/* Description */}
      {location.description && (
        <div className="detail-description">
          <p>{location.description}</p>
        </div>
      )}

      {/* Statistiques */}
      <div className="detail-stats-grid">
        <div className="detail-stat-card">
          <div className="stat-icon" style={{ background: 'var(--primary-light)', color: 'var(--primary)' }}>
            <Package size={24} />
          </div>
          <div className="stat-content">
            <div className="stat-value">{statistics?.total_quantity?.toLocaleString() || 0}</div>
            <div className="stat-label">Articles en stock</div>
          </div>
        </div>

        <div className="detail-stat-card">
          <div className="stat-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
            <Box size={24} />
          </div>
          <div className="stat-content">
            <div className="stat-value">{statistics?.variants_with_stock || 0}</div>
            <div className="stat-label">Variantes avec stock</div>
          </div>
        </div>

        <div className="detail-stat-card">
          <div className="stat-icon" style={{ background: 'var(--info-light)', color: 'var(--info)' }}>
            <ArrowRightLeft size={24} />
          </div>
          <div className="stat-content">
            <div className="stat-value">{statistics?.recent_movements_count || 0}</div>
            <div className="stat-label">Mouvements (30j)</div>
          </div>
        </div>

        {location.capacity && (
          <div className={`detail-stat-card occupancy-card ${getOccupancyColor(statistics?.occupancy_percentage)}`}>
            <div className="stat-content">
              <div className="occupancy-header">
                <span className="stat-label">Occupation</span>
                <span className="stat-value">{statistics?.occupancy_percentage?.toFixed(1) || 0}%</span>
              </div>
              <div className="occupancy-bar-large">
                <div
                  className="occupancy-fill"
                  style={{ width: `${Math.min(statistics?.occupancy_percentage || 0, 100)}%` }}
                ></div>
              </div>
              <div className="occupancy-info">
                <span>{statistics?.total_quantity || 0} / {location.capacity}</span>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Top Produits */}
      {statistics?.top_products && statistics.top_products.length > 0 && (
        <div className="top-products-section">
          <h3 className="section-title">
            <BarChart3 size={20} />
            Top 5 Produits
          </h3>
          <div className="top-products-grid">
            {statistics.top_products.map((product, index) => (
              <div key={index} className="top-product-item">
                <span className="product-rank">#{index + 1}</span>
                <div className="product-info">
                  <span className="product-name">{product.product_name}</span>
                  <span className="product-sku">{product.variant_sku}</span>
                </div>
                <span className="product-quantity">{product.quantity}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Tabs */}
      <div className="detail-tabs">
        <button
          className={`tab-btn ${activeTab === 'variants' ? 'active' : ''}`}
          onClick={() => setActiveTab('variants')}
        >
          <Package size={18} />
          Produits ({statistics?.variant_count || 0})
        </button>
        <button
          className={`tab-btn ${activeTab === 'movements' ? 'active' : ''}`}
          onClick={() => setActiveTab('movements')}
        >
          <ArrowRightLeft size={18} />
          Mouvements ({statistics?.movement_count || 0})
        </button>
        <button
          className={`tab-btn ${activeTab === 'reservations' ? 'active' : ''}`}
          onClick={() => setActiveTab('reservations')}
        >
          <Calendar size={18} />
          Réservations ({totalReserved})
        </button>
      </div>

      {/* Contenu des tabs */}
      <div className="tab-content">
        {activeTab === 'variants' && (
          <div className="variants-section">
            {/* Filtres variantes */}
            <div className="section-filters">
              <div className="search-box">
                <Search className="search-icon" size={18} />
                <input
                  type="text"
                  className="search-input"
                  placeholder="Rechercher un produit..."
                  value={variantSearch}
                  onChange={(e) => {
                    setVariantSearch(e.target.value);
                    setVariantPage(1);
                  }}
                />
                {variantSearch && (
                  <button className="clear-search" onClick={() => setVariantSearch('')}>
                    <X size={14} />
                  </button>
                )}
              </div>

              <div className="filter-group">
                <label className="filter-label">Stock:</label>
                <select
                  className="filter-select"
                  value={variantStockFilter}
                  onChange={(e) => {
                    setVariantStockFilter(e.target.value);
                    setVariantPage(1);
                  }}
                >
                  <option value="all">Tous</option>
                  <option value="with_stock">Avec stock</option>
                  <option value="without_stock">Sans stock</option>
                </select>
              </div>

              <button className="btn-icon" onClick={loadVariants} title="Actualiser">
                <RefreshCw size={18} />
              </button>
            </div>

            {/* Liste des variantes */}
            {variants.length === 0 ? (
              <div className="empty-tab-state">
                <Package size={48} />
                <p>Aucun produit dans cette location</p>
              </div>
            ) : (
              <>
                <div className="variants-table">
                  <table>
                    <thead>
                      <tr>
                        <th>Produit</th>
                        <th>SKU</th>
                        <th>Attributs</th>
                        <th className="text-right">Quantité</th>
                      </tr>
                    </thead>
                    <tbody>
                      {variants.map((item) => (
                        <tr key={item.id}>
                          <td>
                            <div className="product-cell">
                              <span className="product-name">{item.variant?.product?.name || 'N/A'}</span>
                            </div>
                          </td>
                          <td>
                            <span className="sku-badge">{item.variant?.sku || 'N/A'}</span>
                          </td>
                          <td>
                            <div className="attributes-cell">
                              {item.variant?.attribute_values?.map((attr, idx) => (
                                <span key={idx} className="attribute-tag">
                                  {attr.attribute_value?.attribute_type?.display_name || 'Attribut'}: {attr.attribute_value?.value || 'N/A'}
                                </span>
                              ))}
                            </div>
                          </td>
                          <td className="text-right">
                            <span className={`quantity-badge ${item.quantity > 0 ? 'has-stock' : 'no-stock'}`}>
                              {item.quantity}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                {/* Pagination */}
                {variantTotal > 10 && (
                  <div className="pagination">
                    <button
                      className="pagination-btn"
                      disabled={variantPage === 1}
                      onClick={() => setVariantPage(p => p - 1)}
                    >
                      <ChevronLeft size={18} />
                    </button>
                    <span className="pagination-info">
                      Page {variantPage} sur {Math.ceil(variantTotal / 10)}
                    </span>
                    <button
                      className="pagination-btn"
                      disabled={variantPage >= Math.ceil(variantTotal / 10)}
                      onClick={() => setVariantPage(p => p + 1)}
                    >
                      <ChevronRight size={18} />
                    </button>
                  </div>
                )}
              </>
            )}
          </div>
        )}

        {activeTab === 'movements' && (
          <div className="movements-section">
            {/* Filtres mouvements */}
            <div className="section-filters">
              <div className="filter-group">
                <label className="filter-label">Type:</label>
                <select
                  className="filter-select"
                  value={movementType}
                  onChange={(e) => {
                    setMovementType(e.target.value);
                    setMovementPage(1);
                  }}
                >
                  <option value="">Tous</option>
                  <option value="transfer">Transfert</option>
                  <option value="receipt">Réception</option>
                  <option value="sale">Vente</option>
                  <option value="adjustment">Ajustement</option>
                  <option value="return">Retour</option>
                </select>
              </div>

              <div className="filter-group">
                <label className="filter-label">Période:</label>
                <select
                  className="filter-select"
                  value={movementDays}
                  onChange={(e) => {
                    setMovementDays(Number(e.target.value));
                    setMovementPage(1);
                  }}
                >
                  <option value={7}>7 derniers jours</option>
                  <option value={30}>30 derniers jours</option>
                  <option value={90}>90 derniers jours</option>
                  <option value={365}>Cette année</option>
                </select>
              </div>

              <button className="btn-icon" onClick={loadMovements} title="Actualiser">
                <RefreshCw size={18} />
              </button>
            </div>

            {/* Liste des mouvements */}
            {movements.length === 0 ? (
              <div className="empty-tab-state">
                <ArrowRightLeft size={48} />
                <p>Aucun mouvement de stock pour cette période</p>
              </div>
            ) : (
              <>
                <div className="movements-list">
                  {movements.map((movement) => (
                    <div key={movement.id} className="movement-item">
                      <div className="movement-icon-wrapper">
                        {getMovementTypeIcon(movement.movement_type, movement.from_location_id, movement.to_location_id)}
                      </div>

                      <div className="movement-content">
                        <div className="movement-header">
                          <span className="movement-type">{getMovementTypeLabel(movement.movement_type)}</span>
                          <span className="movement-date">
                            <Clock size={14} />
                            {formatDate(movement.created_at)}
                          </span>
                        </div>

                        <div className="movement-details">
                          <span className="movement-product">
                            {movement.variant?.product?.name || 'Produit inconnu'}
                            {movement.variant?.sku && ` (${movement.variant.sku})`}
                          </span>
                        </div>

                        {movement.movement_type === 'transfer' && (
                          <div className="movement-locations">
                            <span className="from-location">
                              {movement.from_location?.name || 'N/A'}
                            </span>
                            <ArrowRightLeft size={14} />
                            <span className="to-location">
                              {movement.to_location?.name || 'N/A'}
                            </span>
                          </div>
                        )}

                        {movement.reason && (
                          <p className="movement-reason">{movement.reason}</p>
                        )}
                      </div>

                      <div className={`movement-quantity ${movement.to_location_id == id ? 'positive' : 'negative'}`}>
                        {movement.to_location_id == id ? '+' : '-'}{movement.quantity}
                      </div>
                    </div>
                  ))}
                </div>

                {/* Pagination */}
                {movementTotal > 15 && (
                  <div className="pagination">
                    <button
                      className="pagination-btn"
                      disabled={movementPage === 1}
                      onClick={() => setMovementPage(p => p - 1)}
                    >
                      <ChevronLeft size={18} />
                    </button>
                    <span className="pagination-info">
                      Page {movementPage} sur {Math.ceil(movementTotal / 15)}
                    </span>
                    <button
                      className="pagination-btn"
                      disabled={movementPage >= Math.ceil(movementTotal / 15)}
                      onClick={() => setMovementPage(p => p + 1)}
                    >
                      <ChevronRight size={18} />
                    </button>
                  </div>
                )}
              </>
            )}
          </div>
        )}

        {activeTab === 'reservations' && (
          <div className="reservations-section">
            {reservationsLoading ? (
              <div className="loading-container">
                <div className="loading-spinner"></div>
                <p>Chargement des réservations...</p>
              </div>
            ) : reservations.length === 0 ? (
              <div className="empty-tab-state">
                <Calendar size={48} />
                <p>Aucune réservation active dans cette location</p>
              </div>
            ) : (
              <>
                <div className="reservations-list">
                  <div className="reservations-header">
                    <h3>
                      <Package size={20} />
                      {totalReserved} article(s) réservé(s)
                    </h3>
                    <button className="btn-icon" onClick={loadReservations} title="Actualiser">
                      <RefreshCw size={18} />
                    </button>
                  </div>

                  {reservations.map((item) => (
                    <div key={item.variant_id} className="reservation-variant-card">
                      <div className="variant-info">
                        {item.product_image && (
                          <img src={item.product_image} alt={item.product_name} className="variant-image" />
                        )}
                        <div className="variant-details">
                          <h4 className="product-name">{item.product_name}</h4>
                          <span className="sku-badge">{item.sku}</span>
                          <div className="reserved-quantity">
                            <Package size={16} />
                            <span>{item.reserved_quantity} unité(s) réservée(s)</span>
                          </div>
                        </div>
                      </div>

                      <div className="reservation-info">
                        <div className="reservation-meta">
                          <span className="sale-number">{item.reservation.sale_number}</span>
                          {getReservationStatusBadge(item.reservation.status)}
                          {getDaysRemainingBadge(item.reservation.days_remaining)}
                        </div>
                        <div className="customer-info">
                          <User size={14} />
                          <span>{item.customer.name}</span>
                          {item.customer.phone && (
                            <span className="customer-phone">• {item.customer.phone}</span>
                          )}
                        </div>
                        <div className="reservation-dates">
                          <div className="date-item">
                            <Clock size={14} />
                            <span>{new Date(item.reservation.reservation_date).toLocaleDateString('fr-FR')}</span>
                          </div>
                          <div className="date-item">
                            <Calendar size={14} />
                            <span>Expire: {new Date(item.reservation.expiry_date).toLocaleDateString('fr-FR')}</span>
                          </div>
                        </div>
                      </div>

                      <div className="reservation-actions">
                        <button 
                          className="btn-sm btn-primary"
                          onClick={() => navigate(`/reservations/${item.reservation.id}`)}
                        >
                          Voir réservation
                        </button>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Pagination */}
                {reservationTotal > 10 && (
                  <div className="pagination">
                    <button
                      className="pagination-btn"
                      disabled={reservationPage === 1}
                      onClick={() => setReservationPage(p => p - 1)}
                    >
                      <ChevronLeft size={18} />
                    </button>
                    <span className="pagination-info">
                      Page {reservationPage} sur {Math.ceil(reservationTotal / 10)}
                    </span>
                    <button
                      className="pagination-btn"
                      disabled={reservationPage >= Math.ceil(reservationTotal / 10)}
                      onClick={() => setReservationPage(p => p + 1)}
                    >
                      <ChevronRight size={18} />
                    </button>
                  </div>
                )}
              </>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default LocationDetail;
