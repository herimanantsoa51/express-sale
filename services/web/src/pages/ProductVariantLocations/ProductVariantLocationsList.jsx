import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Plus, Search, Edit2, Trash2, Package, MapPin, AlertCircle } from 'lucide-react';
import productVariantLocationService from '../../services/productVariantLocationService';
import locationService from '../../services/locationService';
import styles from './ProductVariantLocations.module.css';

const ProductVariantLocationsList = () => {
  const navigate = useNavigate();
  const [locations, setLocations] = useState([]);
  const [variantLocations, setVariantLocations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedLocation, setSelectedLocation] = useState('all');
  const [deleteModal, setDeleteModal] = useState({ show: false, id: null, canDelete: false, reason: '' });

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const [locationsData, variantLocationsData] = await Promise.all([
        locationService.getAll(),
        productVariantLocationService.getAll()
      ]);
      setLocations(locationsData);
      setVariantLocations(variantLocationsData);
      setError(null);
    } catch (err) {
      setError('Erreur lors du chargement des données');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    try {
      const canDeleteData = await productVariantLocationService.canDelete(id);
      setDeleteModal({
        show: true,
        id,
        canDelete: canDeleteData.canDelete,
        reason: canDeleteData.reason || ''
      });
    } catch (err) {
      console.error(err);
    }
  };

  const confirmDelete = async () => {
    try {
      await productVariantLocationService.delete(deleteModal.id);
      await loadData();
      setDeleteModal({ show: false, id: null, canDelete: false, reason: '' });
    } catch (err) {
      setError('Erreur lors de la suppression');
      console.error(err);
    }
  };

  const filteredData = variantLocations.filter(vl => {
    const matchesSearch = 
      vl.variant?.sku?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      vl.variant?.product?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      vl.location?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      vl.location?.code?.toLowerCase().includes(searchTerm.toLowerCase());

    const matchesLocation = selectedLocation === 'all' || vl.location_id === parseInt(selectedLocation);

    return matchesSearch && matchesLocation;
  });

  const getTotalByLocation = (locationId) => {
    return variantLocations
      .filter(vl => vl.location_id === locationId)
      .reduce((sum, vl) => sum + (vl.quantity || 0), 0);
  };

  if (loading) {
    return (
      <div className={styles.container}>
        <div className={styles.loadingState}>
          <div className={styles.spinner}></div>
          <p>Chargement des localisations...</p>
        </div>
      </div>
    );
  }

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>Localisations des Variantes</h1>
          <p className={styles.subtitle}>
            Gérez l'emplacement de vos produits dans l'entrepôt
          </p>
        </div>
        <button
          className={styles.btnPrimary}
          onClick={() => navigate('/localisations-variantes/nouvelle')}
        >
          <Plus size={20} />
          Nouvelle Localisation
        </button>
      </div>

      {error && (
        <div className={styles.alert}>
          <AlertCircle size={20} />
          <span>{error}</span>
        </div>
      )}

      <div className={styles.filtersCard}>
        <div className={styles.searchBox}>
          <Search size={20} />
          <input
            type="text"
            placeholder="Rechercher par produit, SKU, location..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>

        <select
          value={selectedLocation}
          onChange={(e) => setSelectedLocation(e.target.value)}
          className={styles.filterSelect}
        >
          <option value="all">Toutes les locations</option>
          {locations.map(loc => (
            <option key={loc.id} value={loc.id}>
              {loc.name} ({getTotalByLocation(loc.id)} unités)
            </option>
          ))}
        </select>
      </div>

      <div className={styles.tableCard}>
        <table className={styles.table}>
          <thead>
            <tr>
              <th>Produit</th>
              <th>SKU</th>
              <th>Location</th>
              <th>Entrepôt</th>
              <th>Emplacement</th>
              <th>Quantité</th>
              <th>Notes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {filteredData.length === 0 ? (
              <tr>
                <td colSpan="8" className={styles.emptyState}>
                  <Package size={48} />
                  <p>Aucune localisation trouvée</p>
                </td>
              </tr>
            ) : (
              filteredData.map(vl => (
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
                    <div className={styles.locationInfo}>
                      <MapPin size={16} />
                      <span>{vl.location?.name}</span>
                    </div>
                  </td>
                  <td>{vl.location?.warehouse || '-'}</td>
                  <td>
                    <span className={styles.locationDetails}>
                      {[
                        vl.location?.aisle && `Allée ${vl.location.aisle}`,
                        vl.location?.shelf && `Ét. ${vl.location.shelf}`,
                        vl.location?.bin && `Bac ${vl.location.bin}`
                      ].filter(Boolean).join(' / ') || '-'}
                    </span>
                  </td>
                  <td>
                    <span className={styles.quantity}>{vl.quantity || 0}</span>
                  </td>
                  <td>
                    <span className={styles.notes}>
                      {vl.notes ? (vl.notes.length > 30 ? `${vl.notes.substring(0, 30)}...` : vl.notes) : '-'}
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
              {deleteModal.canDelete ? (
                <p>Êtes-vous sûr de vouloir supprimer cette localisation ?</p>
              ) : (
                <div className={styles.alertDanger}>
                  <AlertCircle size={20} />
                  <div>
                    <p><strong>Impossible de supprimer</strong></p>
                    <p>{deleteModal.reason}</p>
                  </div>
                </div>
              )}
            </div>
            <div className={styles.modalFooter}>
              <button
                className={styles.btnSecondary}
                onClick={() => setDeleteModal({ show: false, id: null, canDelete: false, reason: '' })}
              >
                Annuler
              </button>
              {deleteModal.canDelete && (
                <button
                  className={styles.btnDanger}
                  onClick={confirmDelete}
                >
                  Supprimer
                </button>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProductVariantLocationsList;