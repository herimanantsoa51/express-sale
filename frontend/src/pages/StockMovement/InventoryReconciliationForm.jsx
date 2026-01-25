import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  Save, X, AlertCircle, AlertTriangle, Package, MapPin, 
  CheckCircle, TrendingDown, ClipboardCheck, Info 
} from 'lucide-react';
import stockMovementService from '../../services/stockMovementService';
import locationService from '../../services/locationService';
import productVariantLocationService from '../../services/productVariantLocationService';
import styles from './StockMovement.module.css';

const InventoryReconciliationForm = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [locations, setLocations] = useState([]);
  const [variants, setVariants] = useState([]);
  const [selectedLocation, setSelectedLocation] = useState(null);
  
  // État pour chaque ligne de comptage
  const [inventoryItems, setInventoryItems] = useState([]);
  
  const [formData, setFormData] = useState({
    location_id: '',
    inventory_date: new Date().toISOString().split('T')[0],
    notes: ''
  });

  useEffect(() => {
    loadLocations();
  }, []);

  useEffect(() => {
    if (formData.location_id) {
      loadVariants();
    }
  }, [formData.location_id]);

  const loadLocations = async () => {
    try {
      const data = await locationService.getActive();
      setLocations(data);
    } catch (err) {
      console.error('Erreur chargement locations:', err);
      setError('Impossible de charger les emplacements');
    }
  };

  const loadVariants = async () => {
    try {
      const data = await productVariantLocationService.getByLocation(formData.location_id);
      
      // Initialiser les items d'inventaire avec les quantités système
      const items = data.map(vl => ({
        variant_id: vl.variant_id,
        variant: vl.variant,
        system_quantity: vl.quantity,
        physical_count: vl.quantity, // Par défaut = quantité système
        notes: '',
        status: 'pending' // pending, confirmed, discrepancy
      }));
      
      setInventoryItems(items);
      setVariants(data);
    } catch (err) {
      console.error('Erreur chargement variants:', err);
      setError('Impossible de charger les produits');
    }
  };

  const handleLocationChange = (e) => {
    const locationId = e.target.value;
    setFormData(prev => ({ ...prev, location_id: locationId }));
    const location = locations.find(l => l.id === parseInt(locationId));
    setSelectedLocation(location);
    setError(null);
  };

  const handlePhysicalCountChange = (variantId, value) => {
    const physicalCount = parseInt(value) || 0;
    
    setInventoryItems(prev => prev.map(item => {
      if (item.variant_id === variantId) {
        const difference = physicalCount - item.system_quantity;
        return {
          ...item,
          physical_count: physicalCount,
          status: difference === 0 ? 'confirmed' : 'discrepancy'
        };
      }
      return item;
    }));
  };

  const handleItemNotesChange = (variantId, notes) => {
    setInventoryItems(prev => prev.map(item => 
      item.variant_id === variantId ? { ...item, notes } : item
    ));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Vérifier qu'il y a au moins un item compté
    const countedItems = inventoryItems.filter(item => 
      item.physical_count !== item.system_quantity || item.status === 'confirmed'
    );

    if (countedItems.length === 0) {
      setError('Aucune modification détectée. Modifiez au moins un comptage physique.');
      return;
    }

    // Vérifier que tous les écarts négatifs ont une note
    const discrepanciesWithoutNotes = inventoryItems.filter(item => 
      item.physical_count < item.system_quantity && !item.notes.trim()
    );

    if (discrepanciesWithoutNotes.length > 0) {
      setError('Veuillez ajouter une note pour tous les produits avec stock manquant');
      return;
    }

    try {
      setLoading(true);
      setError(null);

      // Préparer les données pour l'API
      const reconciliationData = {
        location_id: parseInt(formData.location_id),
        inventory_date: formData.inventory_date,
        items: inventoryItems.map(item => ({
          variant_id: item.variant_id,
          system_quantity: item.system_quantity,
          physical_count: item.physical_count,
          notes: item.notes || null
        })),
        notes: formData.notes || null
      };

      await stockMovementService.reconcileInventory(reconciliationData);

      navigate('/mouvements-stock', { 
        state: { 
          message: `Inventaire réconcilié avec succès pour ${selectedLocation.name}`,
          type: 'success'
        }
      });

    } catch (err) {
      setError(err.response?.data?.message || 'Erreur lors de la réconciliation');
      console.error('Erreur:', err);
    } finally {
      setLoading(false);
    }
  };

  // Statistiques
  const stats = {
    total: inventoryItems.length,
    confirmed: inventoryItems.filter(i => i.status === 'confirmed').length,
    discrepancies: inventoryItems.filter(i => i.status === 'discrepancy').length,
    surplus: inventoryItems.filter(i => i.physical_count > i.system_quantity).length,
    shortage: inventoryItems.filter(i => i.physical_count < i.system_quantity).length
  };

  const getDifferenceClass = (difference) => {
    if (difference === 0) return styles.differenceNeutral;
    if (difference > 0) return styles.differencePositive;
    return styles.differenceNegative;
  };

  const getDifferenceIcon = (difference) => {
    if (difference === 0) return <CheckCircle size={16} />;
    if (difference > 0) return <AlertCircle size={16} />;
    return <AlertTriangle size={16} />;
  };

  return (
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>Réconciliation d'Inventaire</h1>
          <p className={styles.subtitle}>
            Comparer le stock physique avec le stock système
          </p>
        </div>
        <button
          className={styles.btnSecondary}
          onClick={() => navigate('/mouvements-stock')}
        >
          <X size={20} />
          Annuler
        </button>
      </div>

      {/* Alerte */}
      {error && (
        <div className={styles.alertDanger}>
          <AlertCircle size={20} />
          <span>{error}</span>
        </div>
      )}

      {/* Info card */}
      <div className={styles.infoCard}>
        <Info size={24} />
        <div>
          <h3>Comment ça marche ?</h3>
          <ul>
            <li><strong>Stock conforme :</strong> Le comptage physique = stock système → aucune action</li>
            <li><strong>Stock excédentaire :</strong> Comptage physique &gt; stock système → <strong className={styles.textDanger}>ALERTE</strong> - Vérifiez avant de valider</li>
            <li><strong>Stock manquant :</strong> Comptage physique &lt; stock système → Déclaration de perte automatique en FIFO</li>
          </ul>
        </div>
      </div>

      <form onSubmit={handleSubmit} className={styles.form}>
        {/* Informations générales */}
        <div className={styles.formCard}>
          <h2 className={styles.cardTitle}>
            <ClipboardCheck size={20} />
            Informations de l'inventaire
          </h2>

          <div className={styles.formRow}>
            <div className={styles.formGroup}>
              <label className={styles.label}>
                Emplacement <span className={styles.required}>*</span>
              </label>
              <select
                name="location_id"
                value={formData.location_id}
                onChange={handleLocationChange}
                required
              >
                <option value="">Sélectionner un emplacement</option>
                {locations.map(loc => (
                  <option key={loc.id} value={loc.id}>
                    {loc.name} - {loc.warehouse}
                  </option>
                ))}
              </select>
              {selectedLocation && (
                <div className={styles.selectedInfo}>
                  <MapPin size={16} />
                  <div>
                    <p><strong>{selectedLocation.name}</strong></p>
                    <p className={styles.subText}>
                      {selectedLocation.warehouse} | Code: {selectedLocation.code}
                    </p>
                  </div>
                </div>
              )}
            </div>

            <div className={styles.formGroup}>
              <label className={styles.label}>
                Date d'inventaire <span className={styles.required}>*</span>
              </label>
              <input
                type="date"
                name="inventory_date"
                value={formData.inventory_date}
                onChange={(e) => setFormData(prev => ({ ...prev, inventory_date: e.target.value }))}
                max={new Date().toISOString().split('T')[0]}
                required
              />
            </div>
          </div>

          <div className={styles.formGroup}>
            <label className={styles.label}>Notes générales</label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={(e) => setFormData(prev => ({ ...prev, notes: e.target.value }))}
              rows="2"
              placeholder="Commentaires généraux sur cet inventaire..."
            />
          </div>
        </div>

        {/* Statistiques */}
        {inventoryItems.length > 0 && (
          <div className={styles.statsGrid}>
            <div className={styles.statCard}>
              <Package size={24} />
              <div className={styles.statContent}>
                <div className={styles.statValue}>{stats.total}</div>
                <div className={styles.statLabel}>Produits</div>
              </div>
            </div>
            <div className={styles.statCard}>
              <CheckCircle size={24} className={styles.iconSuccess} />
              <div className={styles.statContent}>
                <div className={styles.statValue}>{stats.confirmed}</div>
                <div className={styles.statLabel}>Conformes</div>
              </div>
            </div>
            <div className={styles.statCard}>
              <AlertCircle size={24} className={styles.iconWarning} />
              <div className={styles.statContent}>
                <div className={styles.statValue}>{stats.surplus}</div>
                <div className={styles.statLabel}>Excédents</div>
              </div>
            </div>
            <div className={styles.statCard}>
              <AlertTriangle size={24} className={styles.iconDanger} />
              <div className={styles.statContent}>
                <div className={styles.statValue}>{stats.shortage}</div>
                <div className={styles.statLabel}>Manquants</div>
              </div>
            </div>
          </div>
        )}

        {/* Liste des produits */}
        {inventoryItems.length > 0 && (
          <div className={styles.inventoryCard}>
            <h2 className={styles.cardTitle}>
              Comptage physique ({inventoryItems.length} produits)
            </h2>

            <div className={styles.inventoryTable}>
              <div className={styles.inventoryHeader}>
                <div className={styles.colProduct}>Produit</div>
                <div className={styles.colQuantity}>Stock système</div>
                <div className={styles.colQuantity}>Comptage physique</div>
                <div className={styles.colDifference}>Écart</div>
                <div className={styles.colNotes}>Notes</div>
              </div>

              {inventoryItems.map(item => {
                const difference = item.physical_count - item.system_quantity;
                
                return (
                  <div key={item.variant_id} className={styles.inventoryRow}>
                    <div className={styles.colProduct}>
                      <div className={styles.productInfo}>
                        <Package size={16} />
                        <div>
                          <p className={styles.productName}>{item.variant?.product?.name}</p>
                          <p className={styles.productSku}>{item.variant?.sku}</p>
                        </div>
                      </div>
                    </div>

                    <div className={styles.colQuantity}>
                      <span className={styles.systemQty}>{item.system_quantity}</span>
                    </div>

                    <div className={styles.colQuantity}>
                      <input
                        type="number"
                        value={item.physical_count}
                        onChange={(e) => handlePhysicalCountChange(item.variant_id, e.target.value)}
                        min="0"
                        className={styles.countInput}
                      />
                    </div>

                    <div className={styles.colDifference}>
                      <div className={getDifferenceClass(difference)}>
                        {getDifferenceIcon(difference)}
                        <span>
                          {difference > 0 && '+'}
                          {difference}
                        </span>
                      </div>
                    </div>

                    <div className={styles.colNotes}>
                      <input
                        type="text"
                        value={item.notes}
                        onChange={(e) => handleItemNotesChange(item.variant_id, e.target.value)}
                        placeholder={difference < 0 ? "Raison requise" : "Notes optionnelles"}
                        className={difference < 0 && !item.notes ? styles.inputRequired : ''}
                      />
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}

        {/* Actions */}
        {inventoryItems.length > 0 && (
          <div className={styles.formActions}>
            <button
              type="button"
              className={styles.btnSecondary}
              onClick={() => navigate('/mouvements-stock')}
            >
              Annuler
            </button>
            <button
              type="submit"
              className={styles.btnPrimary}
              disabled={loading || stats.discrepancies === 0}
            >
              {loading ? (
                <>
                  <div className={styles.spinner}></div>
                  Réconciliation en cours...
                </>
              ) : (
                <>
                  <Save size={20} />
                  Valider la réconciliation ({stats.discrepancies} écart{stats.discrepancies > 1 ? 's' : ''})
                </>
              )}
            </button>
          </div>
        )}
      </form>
    </div>
  );
};

export default InventoryReconciliationForm;