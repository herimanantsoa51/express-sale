import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Save, X, AlertCircle, AlertTriangle, Package, MapPin, DollarSign, Layers, TrendingDown } from 'lucide-react';
import stockMovementService from '../../services/stockMovementService';
import locationService from '../../services/locationService';
import productVariantLocationService from '../../services/productVariantLocationService';
import styles from './StockMovement.module.css';

const StockLossForm = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [warning, setWarning] = useState(null);
  const [locations, setLocations] = useState([]);
  const [variants, setVariants] = useState([]);
  const [selectedVariant, setSelectedVariant] = useState(null);
  const [selectedLocation, setSelectedLocation] = useState(null);
  const [currentQuantity, setCurrentQuantity] = useState(0);
  const [estimatedCostImpact, setEstimatedCostImpact] = useState(0);

  const [formData, setFormData] = useState({
    variant_id: '',
    location_id: '',
    quantity: 0,
    loss_type: '',
    reason: '',
    notes: ''
  });

  // Types de perte
  const lossTypes = [
    { value: 'breakage', label: 'Casse', icon: '💥' },
    { value: 'theft', label: 'Vol', icon: '🚨' },
    { value: 'expiry', label: 'Péremption', icon: '📅' },
    { value: 'damage', label: 'Dommage', icon: '⚠️' },
    { value: 'inventory_shortage', label: 'Écart inventaire', icon: '📊' },
    { value: 'other', label: 'Autre', icon: '📝' }
  ];

  useEffect(() => {
    loadLocations();
  }, []);

  useEffect(() => {
    if (formData.location_id) {
      loadVariants();
    }
  }, [formData.location_id]);

  useEffect(() => {
    if (formData.variant_id && formData.location_id) {
      loadCurrentQuantity();
    }
  }, [formData.variant_id, formData.location_id]);

  // Calculer l'impact en coût estimé
  useEffect(() => {
    if (selectedVariant && formData.quantity > 0) {
      // Utiliser le prix de base du produit comme estimation
      const basePrice = selectedVariant.product?.base_price || 0;
      setEstimatedCostImpact(basePrice * formData.quantity);
    } else {
      setEstimatedCostImpact(0);
    }
  }, [selectedVariant, formData.quantity]);

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
      setVariants(data.filter(vl => vl.quantity > 0)); // Seulement les variants avec stock
    } catch (err) {
      console.error('Erreur chargement variants:', err);
      setError('Impossible de charger les produits');
    }
  };

  const loadCurrentQuantity = async () => {
    try {
      const variantLocations = await productVariantLocationService.getByVariant(formData.variant_id);
      const location = variantLocations.find(vl => vl.location_id === parseInt(formData.location_id));
      setCurrentQuantity(location?.quantity || 0);
    } catch (err) {
      console.error('Erreur chargement quantité:', err);
      setCurrentQuantity(0);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    setError(null);
    setWarning(null);
  };

  const handleVariantChange = (e) => {
    const variantId = e.target.value;
    setFormData(prev => ({ ...prev, variant_id: variantId }));
    const variantLocation = variants.find(vl => vl.variant_id === parseInt(variantId));
    setSelectedVariant(variantLocation?.variant);
    setError(null);
    setWarning(null);
  };

  const handleLocationChange = (e) => {
    const locationId = e.target.value;
    setFormData(prev => ({ ...prev, location_id: locationId, variant_id: '' }));
    const location = locations.find(l => l.id === parseInt(locationId));
    setSelectedLocation(location);
    setSelectedVariant(null);
    setCurrentQuantity(0);
    setError(null);
    setWarning(null);
  };

  const handleQuantityChange = (e) => {
    const qty = parseInt(e.target.value) || 0;
    setFormData(prev => ({ ...prev, quantity: qty }));
    
    // Afficher un avertissement si > 50% du stock
    if (qty > currentQuantity * 0.5 && qty <= currentQuantity) {
      setWarning(`⚠️ Attention : vous déclarez une perte importante (${Math.round(qty/currentQuantity*100)}% du stock)`);
    } else {
      setWarning(null);
    }
    
    setError(null);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Validations
    if (!formData.loss_type) {
      setError('Le type de perte est obligatoire');
      return;
    }

    if (!formData.reason.trim()) {
      setError('La raison de la perte est obligatoire');
      return;
    }

    if (formData.quantity <= 0) {
      setError('La quantité doit être supérieure à 0');
      return;
    }

    if (formData.quantity > currentQuantity) {
      setError(`Impossible de déclarer une perte de ${formData.quantity} unités. Stock disponible: ${currentQuantity}`);
      return;
    }

    try {
      setLoading(true);
      setError(null);

      // Appeler l'endpoint de déclaration de perte
      await stockMovementService.declareLoss({
        variant_id: parseInt(formData.variant_id),
        location_id: parseInt(formData.location_id),
        quantity: formData.quantity,
        loss_type: formData.loss_type,
        reason: formData.reason,
        notes: formData.notes || null
      });

      // Rediriger vers la liste des mouvements
      navigate('/mouvements-stock', { 
        state: { 
          message: `Perte de ${formData.quantity} unité(s) déclarée avec succès`,
          type: 'success'
        }
      });

    } catch (err) {
      setError(err.response?.data?.message || 'Erreur lors de la déclaration de perte');
      console.error('Erreur:', err);
    } finally {
      setLoading(false);
    }
  };

  const getNewQuantity = () => {
    return Math.max(0, currentQuantity - (parseInt(formData.quantity) || 0));
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-MG', {
      style: 'currency',
      currency: 'MGA',
      minimumFractionDigits: 0
    }).format(amount);
  };

  return (
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>Déclaration de Perte de Stock</h1>
          <p className={styles.subtitle}>
            Déclarer une perte de stock (casse, vol, péremption, etc.)
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

      {/* Alertes */}
      {error && (
        <div className={styles.alertDanger}>
          <AlertCircle size={20} />
          <span>{error}</span>
        </div>
      )}

      {warning && (
        <div className={styles.alertWarning}>
          <AlertTriangle size={20} />
          <span>{warning}</span>
        </div>
      )}

      {/* Avertissement important */}
      <div className={styles.warningCard}>
        <AlertTriangle size={24} />
        <div>
          <h3>⚠️ Important</h3>
          <p>
            Cette action est <strong>irréversible</strong> et va consommer automatiquement 
            les batches en FIFO (Premier Entré, Premier Sorti). Assurez-vous que les 
            informations sont correctes avant de valider.
          </p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className={styles.form}>
        {/* Informations de base */}
        <div className={styles.formCard}>
          <h2 className={styles.cardTitle}>
            <TrendingDown size={20} />
            Informations de la perte
          </h2>

          {/* Emplacement */}
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

          {/* Produit */}
          <div className={styles.formGroup}>
            <label className={styles.label}>
              Produit <span className={styles.required}>*</span>
            </label>
            <select
              name="variant_id"
              value={formData.variant_id}
              onChange={handleVariantChange}
              required
              disabled={!formData.location_id}
            >
              <option value="">Sélectionner un produit</option>
              {variants.map(vl => (
                <option key={vl.variant_id} value={vl.variant_id}>
                  {vl.variant?.product?.name} - {vl.variant?.sku} (Stock: {vl.quantity})
                </option>
              ))}
            </select>
            {selectedVariant && (
              <div className={styles.selectedInfo}>
                <Package size={16} />
                <div>
                  <p><strong>{selectedVariant.product?.name}</strong></p>
                  <p className={styles.subText}>
                    SKU: {selectedVariant.sku} | Stock actuel: {currentQuantity} unités
                  </p>
                  {selectedVariant.product?.base_price && (
                    <p className={styles.subText}>
                      Prix unitaire estimé: {formatCurrency(selectedVariant.product.base_price)}
                    </p>
                  )}
                </div>
              </div>
            )}
          </div>

          {/* Type de perte */}
          <div className={styles.formGroup}>
            <label className={styles.label}>
              Type de perte <span className={styles.required}>*</span>
            </label>
            <div className={styles.lossTypeGrid}>
              {lossTypes.map(type => (
                <button
                  key={type.value}
                  type="button"
                  className={`${styles.lossTypeButton} ${formData.loss_type === type.value ? styles.active : ''}`}
                  onClick={() => setFormData(prev => ({ ...prev, loss_type: type.value }))}
                >
                  <span className={styles.lossTypeIcon}>{type.icon}</span>
                  <span>{type.label}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Quantité perdue */}
          <div className={styles.formGroup}>
            <label className={styles.label}>
              Quantité perdue <span className={styles.required}>*</span>
            </label>
            <input
              type="number"
              name="quantity"
              value={formData.quantity}
              onChange={handleQuantityChange}
              min="1"
              max={currentQuantity}
              required
              disabled={!formData.variant_id}
            />
            {formData.quantity > 0 && (
              <div className={styles.quantityPreview}>
                <span className={styles.currentQty}>Stock actuel: {currentQuantity}</span>
                <span className={styles.decrease}>
                  -{formData.quantity}
                </span>
                <span className={styles.newQty}>Nouveau stock: {getNewQuantity()}</span>
              </div>
            )}
          </div>

          {/* Impact financier estimé */}
          {estimatedCostImpact > 0 && (
            <div className={styles.costImpactCard}>
              <DollarSign size={20} />
              <div>
                <p className={styles.costImpactLabel}>Impact financier estimé</p>
                <p className={styles.costImpactValue}>{formatCurrency(estimatedCostImpact)}</p>
                <p className={styles.costImpactNote}>
                  Basé sur le prix de base du produit. Les coûts réels FIFO seront calculés automatiquement.
                </p>
              </div>
            </div>
          )}

          {/* Raison */}
          <div className={styles.formGroup}>
            <label className={styles.label}>
              Raison détaillée <span className={styles.required}>*</span>
            </label>
            <textarea
              name="reason"
              value={formData.reason}
              onChange={handleChange}
              rows="3"
              placeholder="Expliquez les circonstances de la perte..."
              required
            />
            <p className={styles.helperText}>
              Décrivez précisément les circonstances (date, heure, cause, responsable éventuel...)
            </p>
          </div>

          {/* Notes complémentaires */}
          <div className={styles.formGroup}>
            <label className={styles.label}>Notes complémentaires</label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              rows="3"
              placeholder="Actions correctives, mesures prises, etc."
            />
          </div>
        </div>

        {/* Résumé avant validation */}
        {formData.variant_id && formData.quantity > 0 && (
          <div className={styles.summaryCard}>
            <h3>
              <Layers size={20} />
              Résumé de la déclaration
            </h3>
            <div className={styles.summaryGrid}>
              <div className={styles.summaryItem}>
                <span className={styles.summaryLabel}>Produit</span>
                <span className={styles.summaryValue}>
                  {selectedVariant?.product?.name} - {selectedVariant?.sku}
                </span>
              </div>
              <div className={styles.summaryItem}>
                <span className={styles.summaryLabel}>Emplacement</span>
                <span className={styles.summaryValue}>{selectedLocation?.name}</span>
              </div>
              <div className={styles.summaryItem}>
                <span className={styles.summaryLabel}>Type de perte</span>
                <span className={styles.summaryValue}>
                  {lossTypes.find(t => t.value === formData.loss_type)?.label || '—'}
                </span>
              </div>
              <div className={styles.summaryItem}>
                <span className={styles.summaryLabel}>Quantité perdue</span>
                <span className={`${styles.summaryValue} ${styles.danger}`}>
                  {formData.quantity} unité{formData.quantity > 1 ? 's' : ''}
                </span>
              </div>
              <div className={styles.summaryItem}>
                <span className={styles.summaryLabel}>Stock après perte</span>
                <span className={styles.summaryValue}>{getNewQuantity()} unités</span>
              </div>
              {estimatedCostImpact > 0 && (
                <div className={styles.summaryItem}>
                  <span className={styles.summaryLabel}>Impact estimé</span>
                  <span className={`${styles.summaryValue} ${styles.danger}`}>
                    {formatCurrency(estimatedCostImpact)}
                  </span>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Actions */}
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
            className={`${styles.btnPrimary} ${styles.btnDanger}`}
            disabled={loading || !formData.variant_id || !formData.quantity || !formData.loss_type || !formData.reason}
          >
            {loading ? (
              <>
                <div className={styles.spinner}></div>
                Enregistrement...
              </>
            ) : (
              <>
                <Save size={20} />
                Confirmer la déclaration de perte
              </>
            )}
          </button>
        </div>
      </form>
    </div>
  );
};

export default StockLossForm;