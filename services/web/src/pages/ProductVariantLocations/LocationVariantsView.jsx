import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { MapPin, Package, ArrowLeft, Edit2, Trash2, Plus, AlertCircle } from 'lucide-react';
import locationService from '../../services/locationService';
import productVariantLocationService from '../../services/productVariantLocationService';
import styles from './ProductVariantLocations.module.css';

const LocationVariantsView = () => {
  const navigate = useNavigate();
  const { locationId } = useParams();
  const [location, setLocation] = useState(null);
  const [variantLocations, setVariantLocations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [deleteModal, setDeleteModal] = useState({ show: false, id: null });

  useEffect(() => {
    loadData();
  }, [locationId]);

  const loadData = async () => {
    try {
      setLoading(true);
      const [locationData, variantsData] = await Promise.all([
        locationService.getById(locationId),
        productVariantLocationService.getByLocation(locationId)
      ]);
      setLocation(locationData);
      setVariantLocations(variantsData);
      setError(null);
    } catch (err) {
      setError('Erreur lors du chargement des données');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    setDeleteModal({ show: true, id });
  };

  const confirmDelete = async () => {
    try {
      await productVariantLocationService.delete(deleteModal.id);
      await loadData();
      setDeleteModal({ show: false, id: null });
    } catch (err) {
      setError('Erreur lors de la suppression');
      console.error(err);
    }
  };

  const getTotalQuantity = () => {
    return variantLocations.reduce((sum, vl) => sum + (vl.quantity || 0), 0);
  };

  const getTotalVariants = () => {
    return variantLocations.length;
  };

  const getCapacityUsage = () => {
    if (!location?.capacity) return null;
    const total = getTotalQuantity();
    const percentage = (total / location.capacity) * 100;
    return { total, capacity: location.capacity, percentage: percentage.toFixed(1) };
  };

  if (loading) {
    return (
      <div className={styles.container}>
        <div className={styles.loadingState}>
          <div className={styles.spinner}></div>
          <p>Chargement...</p>
        </div>
      </div>
    );
  }

  if (!location) {
    return (
      <div className={styles.container}>
        <div className={styles.errorState}>
          <AlertCircle size={48} />
          <p>Location introuvable</p>
        </div>
      </div>
    );
  }

  const capacityUsage = getCapacityUsage();

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <button
          className={styles.btnBack}
          onClick={() => navigate('/locations')}
        >
          <ArrowLeft size={20} />
          Retour
        </button>
      </div>

      {error && (
        <div className={styles.alertDanger}>
          <AlertCircle size={20} />
          <span>{error}</span>
        </div>
      )}

      <div className={styles.locationHeader}>
        <div className={styles.locationIcon}>
          <MapPin size={32} />
        </div>
        <div className={styles.locationInfo}>
          <h1 className={styles.locationName}>{location.name}</h1>
          <div className={styles.locationMeta}>
            <span className={styles.badge}>{location.code}</span>
            <span>{location.warehouse}</span>
            {location.aisle && <span>Allée {location.aisle}</span>}
            {location.shelf && <span>Étagère {location.shelf}</span>}
            {location.bin && <span>Bac {location.bin}</span>}
          </div>
          {location.description && (
            <p className={styles.locationDescription}>{location.description}</p>
          )}
        </div>
      </div>

      <div className={styles.statsGrid}>
        <div className={styles.statCard}>
          <div className={styles.statIcon}>
            <Package size={24} />
          </div>
          <div className={styles.statContent}>
            <p className={styles.statLabel}>Variantes Stockées</p>
            <p className={styles.statValue}>{getTotalVariants()}</p>
          </div>
        </div>

        <div className={styles.statCard}>
          <div className={styles.statIcon}>
            <Package size={24} />
          </div>
          <div className={styles.statContent}>
            <p className={styles.statLabel}>Quantité Totale</p>
            <p className={styles.statValue}>{getTotalQuantity()}</p>
          </div>
        </div>

        {capacityUsage && (
          <div className={styles.statCard}>
            <div className={styles.statIcon}>
              <MapPin size={24} />
            </div>
            <div className={styles.statContent}>
              <p className={styles.statLabel}>Capacité Utilisée</p>
              <p className={styles.statValue}>
                {capacityUsage.total} / {capacityUsage.capacity}
              </p>
              <div className={styles.progressBar}>
                <div 
                  className={styles.progressFill} 
                  style={{ width: `${Math.min(capacityUsage.percentage, 100)}%` }}
                />
              </div>
              <p className={styles.statSubtext}>{capacityUsage.percentage}% utilisée</p>
            </div>
          </div>
        )}
      </div>

      <div className={styles.sectionHeader}>
        <h2 className={styles.sectionTitle}>Produits dans cette Location</h2>
        <button
          className={styles.btnPrimary}
          onClick={() => navigate(`/localisations-variantes/nouvelle?location=${locationId}`)}
        >
          <Plus size={20} />
          Ajouter un Produit
        </button>
      </div>

      <div className={styles.tableCard}>
        <table className={styles.table}>
          <thead>
            <tr>
              <th>Produit</th>
              <th>SKU</th>
              <th>Quantité</th>
              <th>Notes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {variantLocations.length === 0 ? (
              <tr>
                <td colSpan="5" className={styles.emptyState}>
                  <Package size={48} />
                  <p>Aucun produit dans cette location</p>
                </td>
              </tr>
            ) : (
              variantLocations.map(vl => (
                <tr key={vl.id}>
                  <td>
                    <button
                      className={styles.linkButton}
                      onClick={() => navigate(`/produits/${vl.variant?.product_id}`)}
                    >
                      {vl.variant?.product?.name || 'N/A'}
                    </button>
                  </td>
                  <td>
                    <button
                      className={styles.linkButton}
                      onClick={() => navigate(`/produits/${vl.variant?.product_id}`)}
                    >
                      <code className={styles.sku}>{vl.variant?.sku || 'N/A'}</code>
                    </button>
                  </td>
                  <td>
                    <span className={styles.quantity}>{vl.quantity || 0}</span>
                  </td>
                  <td>
                    <span className={styles.notes}>
                      {vl.notes ? (vl.notes.length > 50 ? `${vl.notes.substring(0, 50)}...` : vl.notes) : '-'}
                    </span>
                  </td>
                  <td>
                    <div className={styles.actions}>
                      <button
                        className={styles.btnEdit}
                        onClick={() => navigate(`/localisations-variantes/${vl.id}/modifier`)}
                        title="Modifier"
                      >
                        <Edit2 size={18} />
                      </button>
                      <button
                        className={styles.btnDelete}
                        onClick={() => handleDelete(vl.id)}
                        title="Supprimer"
                      >
                        <Trash2 size={18} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {deleteModal.show && (
        <div className={styles.modalOverlay}>
          <div className={styles.modal}>
            <div className={styles.modalHeader}>
              <h3>Confirmation de suppression</h3>
            </div>
            <div className={styles.modalBody}>
              <p>Êtes-vous sûr de vouloir retirer ce produit de cette location ?</p>
            </div>
            <div className={styles.modalFooter}>
              <button
                className={styles.btnSecondary}
                onClick={() => setDeleteModal({ show: false, id: null })}
              >
                Annuler
              </button>
              <button
                className={styles.btnDanger}
                onClick={confirmDelete}
              >
                Supprimer
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default LocationVariantsView;