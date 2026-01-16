import { useState, useEffect, useCallback } from "react";
import { useNavigate } from "react-router-dom";
import stockReceiptService from "../../services/stockReceiptService";
import {
  Package,
  Plus,
  Search,
  Filter,
  ChevronLeft,
  ChevronRight,
  Truck,
  Clock,
  CheckCircle,
  XCircle,
  AlertTriangle,
  Send,
  Ship,
  PackageCheck,
  RefreshCw,
  Calendar,
  Building2,
  Eye,
  TrendingUp,
  Loader2
} from "lucide-react";
import styles from "./StockReceiptList.module.css";

const STATUS_CONFIG = {
  pending: { label: "En attente", icon: Clock, color: "warning" },
  sent: { label: "Envoyé", icon: Send, color: "info" },
  in_transit: { label: "En transit", icon: Ship, color: "primary" },
  arrived: { label: "Arrivé", icon: PackageCheck, color: "success" },
  validated: { label: "Validé", icon: CheckCircle, color: "success" },
  cancelled: { label: "Annulé", icon: XCircle, color: "danger" }
};

export default function StockReceiptList() {
  const navigate = useNavigate();
  
  // États
  const [receipts, setReceipts] = useState([]);
  const [statistics, setStatistics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [statsLoading, setStatsLoading] = useState(true);
  const [error, setError] = useState(null);
  
  
  // Pagination
  const [pagination, setPagination] = useState({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0
  });
  
  // Filtres
  const [filters, setFilters] = useState({
    search: "",
    status: "",
    created_from: "",
    created_to: "",
    delivery_from: "",
    delivery_to: "",
    delayed: false
  });
  const [showFilters, setShowFilters] = useState(false);
  const [searchInput, setSearchInput] = useState("");


  // Charger les statistiques globales
  const loadStatistics = useCallback(async () => {
    try {
      setStatsLoading(true);
      const response = await stockReceiptService.getGlobalStatistics();
      setStatistics(response.data);
    } catch (err) {
      console.error("Erreur chargement statistiques:", err);
    } finally {
      setStatsLoading(false);
    }
  }, []);

  const loadReceipts = useCallback(async (page = 1) => {
    try {
      setLoading(true);
      setError(null);
      
      const params = {
        page,
        per_page: 15, // Utiliser une valeur fixe au lieu de pagination.perPage
        ...(filters.search && { search: filters.search }),
        ...(filters.status && { status: filters.status }),
        ...(filters.created_from && { created_from: filters.created_from }),
        ...(filters.created_to && { created_to: filters.created_to }),
        ...(filters.delivery_from && { delivery_from: filters.delivery_from }),
        ...(filters.delivery_to && { delivery_to: filters.delivery_to }),
        ...(filters.delayed && { delayed: "true" })
      };
      
      const response = await stockReceiptService.getAll(params);
      
      setReceipts(response.data || []);
      setPagination({
        currentPage: response.meta?.current_page || 1,
        lastPage: response.meta?.last_page || 1,
        perPage: response.meta?.per_page || 15,
        total: response.meta?.total || 0
      });
    } catch (err) {
      setError("Erreur lors du chargement des réceptions");
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [filters]); // Ne dépendre que de filters

  // Chargement initial
  useEffect(() => {
    loadStatistics();
    loadReceipts(1);
  }, []);

  // Recharger quand les filtres changent
  useEffect(() => {
    const timeoutId = setTimeout(() => {
      loadReceipts(1);
    }, 300);
    return () => clearTimeout(timeoutId);
  }, [filters, loadReceipts]); // Ajouter loadReceipts en dépendance

  // Gestion de la recherche
  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setFilters(prev => ({ ...prev, search: searchInput }));
  };

  // Réinitialiser les filtres
  const resetFilters = () => {
    setFilters({
      search: "",
      status: "",
      created_from: "",
      created_to: "",
      delivery_from: "",
      delivery_to: "",
      delayed: false
    });
    setSearchInput("");
  };

  // Compter les filtres actifs
  const getActiveFiltersCount = () => {
    let count = 0;
    if (filters.status) count++;
    if (filters.created_from || filters.created_to) count++;
    if (filters.delivery_from || filters.delivery_to) count++;
    if (filters.delayed) count++;
    return count;
  };

  // Formater le montant
  const formatAmount = (amount) => {
    return new Intl.NumberFormat("fr-MG", {
      style: "decimal",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount) + " Ar";
  };

  // Formater la date
  const formatDate = (dateString) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("fr-FR", {
      day: "2-digit",
      month: "short",
      year: "numeric"
    });
  };

  // Vérifier si en retard
  const isDelayed = (receipt) => {
    if (!receipt.expected_delivery_date) return false;
    if (["arrived", "validated", "cancelled"].includes(receipt.status)) return false;
    return new Date(receipt.expected_delivery_date) < new Date();
  };

  // Rendu des statistiques
  const renderStatistics = () => {
    if (statsLoading) {
      return (
        <div className={styles.statsLoading}>
          <Loader2 className={styles.spinIcon} size={24} />
          <span>Chargement des statistiques...</span>
        </div>
      );
    }

    if (!statistics) return null;

    const statsCards = [
      {
        label: "Total",
        value: statistics.total,
        icon: Package,
        color: "primary"
      },
      {
        label: "En cours",
        value: statistics.not_arrived,
        icon: Truck,
        color: "warning",
        highlight: true
      },
      {
        label: "En retard",
        value: statistics.delayed,
        icon: AlertTriangle,
        color: "danger",
        highlight: statistics.delayed > 0
      },
      {
        label: "Ce mois",
        value: statistics.this_month,
        icon: Calendar,
        color: "info"
      }
    ];

    return (
      <div className={styles.statsContainer}>
        <div className={styles.statsGrid}>
          {statsCards.map((stat, index) => (
            <div 
              key={stat.label}
              className={`${styles.statCard} ${styles[`stat${stat.color}`]} ${stat.highlight ? styles.statHighlight : ""}`}
              style={{ animationDelay: `${index * 0.1}s` }}
            >
              <div className={styles.statIcon}>
                <stat.icon size={24} />
              </div>
              <div className={styles.statContent}>
                <span className={styles.statValue}>{stat.value}</span>
                <span className={styles.statLabel}>{stat.label}</span>
              </div>
            </div>
          ))}
        </div>

        <div className={styles.statusBreakdown}>
          <h4 className={styles.breakdownTitle}>Répartition par statut</h4>
          <div className={styles.statusBars}>
            {Object.entries(STATUS_CONFIG).map(([status, config]) => {
              const count = statistics.by_status?.[status] || 0;
              const percentage = statistics.total > 0 ? (count / statistics.total) * 100 : 0;
              
              return (
                <div key={status} className={styles.statusBarItem}>
                  <div className={styles.statusBarHeader}>
                    <span className={styles.statusBarLabel}>
                      <config.icon size={14} />
                      {config.label}
                    </span>
                    <span className={styles.statusBarCount}>{count}</span>
                  </div>
                  <div className={styles.statusBarTrack}>
                    <div 
                      className={`${styles.statusBarFill} ${styles[`bar${config.color}`]}`}
                      style={{ width: `${percentage}%` }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        <div className={styles.valueStats}>
          <div className={styles.valueCard}>
            <TrendingUp size={20} />
            <div>
              <span className={styles.valueLabel}>Valeur en cours</span>
              <span className={styles.valueAmount}>{formatAmount(statistics.total_value_pending || 0)}</span>
            </div>
          </div>
          <div className={styles.valueCard}>
            <CheckCircle size={20} />
            <div>
              <span className={styles.valueLabel}>Valeur validée</span>
              <span className={styles.valueAmount}>{formatAmount(statistics.total_value_validated || 0)}</span>
            </div>
          </div>
        </div>
      </div>
    );
  };

  // Rendu du badge de statut
  const renderStatusBadge = (status) => {
    const config = STATUS_CONFIG[status] || { label: status, icon: Package, color: "default" };
    const Icon = config.icon;
    
    return (
      <span className={`${styles.statusBadge} ${styles[`status${config.color}`]}`}>
        <Icon size={14} />
        {config.label}
      </span>
    );
  };

  // Rendu de la liste
  const renderReceiptsList = () => {
    if (loading) {
      return (
        <div className={styles.loadingState}>
          <Loader2 className={styles.spinIcon} size={40} />
          <p>Chargement des réceptions...</p>
        </div>
      );
    }

    if (error) {
      return (
        <div className={styles.errorState}>
          <AlertTriangle size={40} />
          <p>{error}</p>
          <button onClick={() => loadReceipts(1)} className={styles.retryBtn}>
            <RefreshCw size={16} />
            Réessayer
          </button>
        </div>
      );
    }

    if (receipts.length === 0) {
      return (
        <div className={styles.emptyState}>
          <Package size={60} strokeWidth={1} />
          <h3>Aucune réception trouvée</h3>
          <p>
            {getActiveFiltersCount() > 0 || filters.search
              ? "Essayez de modifier vos filtres"
              : "Commencez par créer une nouvelle réception de stock"}
          </p>
          {(getActiveFiltersCount() > 0 || filters.search) && (
            <button onClick={resetFilters} className={styles.resetBtn}>
              Réinitialiser les filtres
            </button>
          )}
        </div>
      );
    }

    return (
      <div className={styles.tableContainer}>
        <table className={styles.table}>
          <thead>
            <tr>
              <th>N° Réception</th>
              <th>Fournisseur</th>
              <th>Transitaire</th>
              <th>Date prévue</th>
              <th>Statut</th>
              <th>Articles</th>
              <th>Montant</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {receipts.map((receipt, index) => (
              <tr 
                key={receipt.id}
                className={`${styles.tableRow} ${isDelayed(receipt) ? styles.delayedRow : ""}`}
                style={{ animationDelay: `${index * 0.05}s` }}
              >
                <td>
                  <div className={styles.receiptNumber}>
                    <span className={styles.receiptCode}>{receipt.receipt_number}</span>
                    {isDelayed(receipt) && (
                      <span className={styles.delayedBadge}>
                        <AlertTriangle size={12} />
                        Retard
                      </span>
                    )}
                  </div>
                </td>
                <td>
                        <div className={styles.supplierCell}>
                            {receipt.supplier?.logo_url ? (
                            <img 
                                src={receipt.supplier.logo_url} 
                                alt={receipt.supplier.name}
                                className={styles.logoImage}
                                onClick={() => navigate(`/fournisseurs/${receipt.supplier.id}`)}
                            />
                            ) : (
                            <Building2 size={16} className={styles.iconPlaceholder} />
                            )}
                            {receipt.supplier?.id ? (
                            <button
                                onClick={(e) => {
                                e.stopPropagation();
                                navigate(`/fournisseurs/${receipt.supplier.id}`);
                                }}
                                className={styles.linkButton}
                                title={`Voir ${receipt.supplier.name}`}
                            >
                                {receipt.supplier.name}
                            </button>
                            ) : (
                            <span className={styles.noData}>-</span>
                            )}
                        </div>
                        </td>
                        <td>
                        <div className={styles.forwarderCell}>
                            {receipt.freight_forwarder?.logo_url ? (
                            <img 
                                src={receipt.freight_forwarder.logo_url} 
                                alt={receipt.freight_forwarder.name}
                                className={styles.logoImage}
                                onClick={() => navigate(`/transitaires/${receipt.freight_forwarder.id}`)}
                            />
                            ) : (
                            <Truck size={16} className={styles.iconPlaceholder} />
                            )}
                            {receipt.freight_forwarder?.id ? (
                            <button
                                onClick={(e) => {
                                e.stopPropagation();
                                navigate(`/transitaires/${receipt.freight_forwarder.id}`);
                                }}
                                className={styles.linkButton}
                                title={`Voir ${receipt.freight_forwarder.name}`}
                            >
                                {receipt.freight_forwarder.name}
                            </button>
                            ) : (
                            <span className={styles.noData}>-</span>
                            )}
                        </div>
                        </td>
                <td>
                  <div className={styles.dateCell}>
                    <Calendar size={14} />
                    {formatDate(receipt.expected_delivery_date)}
                  </div>
                </td>
                <td>{renderStatusBadge(receipt.status)}</td>
                <td>
                  <div className={styles.itemsCell}>
                    <span className={styles.itemsCount}>{receipt.items_count || 0}</span>
                    <span className={styles.itemsLabel}>articles</span>
                  </div>
                </td>
                <td>
                  <span className={styles.amount}>
                    {formatAmount(receipt.total_cost_ariary)}
                  </span>
                </td>
                <td>
                  <button
                    onClick={() => navigate(`/reapprovisionnements/${receipt.id}`)}
                    className={styles.viewBtn}
                    title="Voir les détails"
                  >
                    <Eye size={18} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  };

  // Rendu de la pagination
  const renderPagination = () => {
    // Extrayez la première valeur des tableaux si nécessaire
    const currentPage = Array.isArray(pagination.currentPage) 
      ? pagination.currentPage[0] 
      : pagination.currentPage;
      
    const lastPage = Array.isArray(pagination.lastPage) 
      ? pagination.lastPage[0] 
      : pagination.lastPage;
      
    const perPage = Array.isArray(pagination.perPage) 
      ? pagination.perPage[0] 
      : pagination.perPage;
      
    const total = Array.isArray(pagination.total) 
      ? pagination.total[0] 
      : pagination.total;
  
    if (lastPage <= 1) return null;
  
    const pages = [];
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(lastPage, startPage + maxVisiblePages - 1);
  
    if (endPage - startPage + 1 < maxVisiblePages) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
  
    for (let i = startPage; i <= endPage; i++) {
      pages.push(i);
    }
  
    return (
      <div className={styles.pagination}>
        <div className={styles.paginationInfo}>
          Affichage de {((currentPage - 1) * perPage) + 1} à{" "}
          {Math.min(currentPage * perPage, total)} sur{" "}
          {total} résultats
        </div>
        <div className={styles.paginationControls}>
          <button
            onClick={() => loadReceipts(currentPage - 1)}
            disabled={currentPage === 1}
            className={styles.pageBtn}
          >
            <ChevronLeft size={18} />
          </button>
          
          {startPage > 1 && (
            <>
              <button onClick={() => loadReceipts(1)} className={styles.pageBtn}>1</button>
              {startPage > 2 && <span className={styles.pageEllipsis}>...</span>}
            </>
          )}
          
          {pages.map(page => (
            <button
              key={page}
              onClick={() => loadReceipts(page)}
              className={`${styles.pageBtn} ${page === currentPage ? styles.pageBtnActive : ""}`}
            >
              {page}
            </button>
          ))}
          
          {endPage < lastPage && (
            <>
              {endPage < lastPage - 1 && <span className={styles.pageEllipsis}>...</span>}
              <button onClick={() => loadReceipts(lastPage)} className={styles.pageBtn}>
                {lastPage}
              </button>
            </>
          )}
          
          <button
            onClick={() => loadReceipts(currentPage + 1)}
            disabled={currentPage === lastPage}
            className={styles.pageBtn}
          >
            <ChevronRight size={18} />
          </button>
        </div>
      </div>
    );
  };

  return (
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <div className={styles.headerContent}>
          <div className={styles.titleSection}>
            <Package size={32} className={styles.headerIcon} />
            <div>
              <h1 className={styles.title}>Réceptions de Stock</h1>
              <p className={styles.subtitle}>
                Gérez vos approvisionnements et suivez leurs statuts
              </p>
            </div>
          </div>
          <button
            onClick={() => navigate("/reapprovisionnements/nouveau")}
            className={styles.addBtn}
          >
            <Plus size={20} />
            Nouvelle réception
          </button>
        </div>
      </div>

      {/* Statistiques */}
      {renderStatistics()}

      {/* Filtres */}
      <div className={styles.filtersSection}>
        <form onSubmit={handleSearchSubmit} className={styles.searchForm}>
          <div className={styles.searchInputWrapper}>
            <Search size={20} className={styles.searchIcon} />
            <input
              type="text"
              placeholder="Rechercher par numéro ou notes..."
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              className={styles.searchInput}
            />
          </div>
          <button type="submit" className={styles.searchBtn}>
            Rechercher
          </button>
        </form>

        <button
          onClick={() => setShowFilters(!showFilters)}
          className={`${styles.filterToggle} ${showFilters ? styles.filterToggleActive : ""}`}
        >
          <Filter size={18} />
          Filtres
          {getActiveFiltersCount() > 0 && (
            <span className={styles.filterBadge}>
              {getActiveFiltersCount()}
            </span>
          )}
        </button>

        <button
          onClick={() => { loadStatistics(); loadReceipts(1); }}
          className={styles.refreshBtn}
          title="Actualiser"
        >
          <RefreshCw size={18} />
        </button>
      </div>

      {/* Panneau de filtres */}
      {showFilters && (
        <div className={styles.filtersPanel}>
            {/* Statut */}
            <div className={styles.filterGroup}>
            <label className={styles.filterLabel}>Statut</label>
            <select
                value={filters.status}
                onChange={(e) => setFilters(prev => ({ ...prev, status: e.target.value }))}
                className={styles.filterSelect}
            >
                <option value="">Tous les statuts</option>
                {Object.entries(STATUS_CONFIG).map(([value, config]) => (
                <option key={value} value={value}>{config.label}</option>
                ))}
            </select>
            </div>

           

            {/* Date de création */}
            <div className={styles.filterGroup}>
            <label className={styles.filterLabel}>Date de création</label>
            <div className={styles.dateRange}>
                <input
                type="date"
                value={filters.created_from}
                onChange={(e) => setFilters(prev => ({ ...prev, created_from: e.target.value }))}
                className={styles.filterInput}
                />
                <span className={styles.dateSeparator}>au</span>
                <input
                type="date"
                value={filters.created_to}
                onChange={(e) => setFilters(prev => ({ ...prev, created_to: e.target.value }))}
                className={styles.filterInput}
                />
            </div>
            </div>

            {/* Date de livraison prévue */}
            <div className={styles.filterGroup}>
            <label className={styles.filterLabel}>Date livraison prévue</label>
            <div className={styles.dateRange}>
                <input
                type="date"
                value={filters.delivery_from}
                onChange={(e) => setFilters(prev => ({ ...prev, delivery_from: e.target.value }))}
                className={styles.filterInput}
                />
                <span className={styles.dateSeparator}>au</span>
                <input
                type="date"
                value={filters.delivery_to}
                onChange={(e) => setFilters(prev => ({ ...prev, delivery_to: e.target.value }))}
                className={styles.filterInput}
                />
            </div>
            </div>

            {/* Retards uniquement */}
            <div className={styles.filterGroup}>
            <label className={styles.filterCheckbox}>
                <input
                type="checkbox"
                checked={filters.delayed}
                onChange={(e) => setFilters(prev => ({ ...prev, delayed: e.target.checked }))}
                />
                <span className={styles.checkboxLabel}>
                <AlertTriangle size={16} />
                Uniquement les retards
                </span>
            </label>
            </div>

            {/* Bouton reset */}
            {(getActiveFiltersCount() > 0 || filters.search) && (
            <button onClick={resetFilters} className={styles.clearFiltersBtn}>
                <XCircle size={16} />
                Effacer les filtres
            </button>
            )}
        </div>
        )}

      {/* Liste */}
      <div className={styles.listSection}>
        {renderReceiptsList()}
        {renderPagination()}
      </div>
    </div>
  );
}