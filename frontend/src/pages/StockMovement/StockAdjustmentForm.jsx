import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Save, X, AlertCircle, TrendingUp, TrendingDown, Package, MapPin } from 'lucide-react';
import stockMovementService from '../../services/stockMovementService';
import locationService from '../../services/locationService';
import productVariantLocationService from '../../services/productVariantLocationService';
import styles from './StockMovement.module.css';

const StockAdjustmentForm = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [locations, setLocations] = useState([]);
  const [variants, setVariants] = useState([]);
  const [selectedVariant, setSelectedVariant] = useState(null);
  const [selectedLocation, setSelectedLocation] = useState(null);
  const [currentQuantity, setCurrentQuantity] = useState(0);

  const [formData, setFormData] = useState({
    variant_id: '',
    location_id: '',
    quantity: 0,
    reason: '',
    notes: ''
  });

  const [adjustmentType, setAdjustmentType] = useState('increase');

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

  const loadLocations = async () => {
    try {
      const data = await locationService.getActive();
      setLocations(data);
    } catch (err) {
      console.error(err);
    }
  };

  const loadVariants = async () => {
    try {
      const data = await productVariantLocationService.getByLocation(formData.location_id);
      setVariants(data);
    } catch (err) {
      console.error(err);
    }
  };

  const loadCurrentQuantity = async () => {
    try {
      const variantLocations = await productVariantLocationService.getByVariant(formData.variant_id);
      const location = variantLocations.find(vl => vl.location_id === parseInt(formData.location_id));
      setCurrentQuantity(location?.quantity || 0);
    } catch (err) {
      console.error(err);
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
  };

  const handleVariantChange = (e) => {
    const variantId = e.target.value;
    setFormData(prev => ({ ...prev, variant_id: variantId }));
    const variantLocation = variants.find(vl => vl.variant_id === parseInt(variantId));
    setSelectedVariant(variantLocation?.variant);
    setError(null);
  };

  const handleLocationChange = (e) => {
    const locationId = e.target.value;
    setFormData(prev => ({ ...prev, location_id: locationId, variant_id: '' }));
    const location = locations.find(l => l.id === parseInt(locationId));
    setSelectedLocation(location);
    setSelectedVariant(null);
    setError(null);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.reason.trim()) {
      setError('La raison de l\'ajustement est obligatoire');
      return;
    }

    const adjustedQuantity = adjustmentType === 'increase' 
      ? Math.abs(formData.quantity) 
      : -Math.abs(formData.quantity);

    if (adjustmentType === 'decrease' && Math.abs(adjustedQuantity) > currentQuantity) {
      setError(`Impossible de retirer ${Math.abs(adjustedQuantity)} unités. Disponible: ${currentQuantity}`);
      return;
    }

    try {
      setLoading(true);
      setError(null);

      await stockMovementService.adjustment({
        variant_id: parseInt(formData.variant_id),
        location_id: parseInt(formData.location_id),
        quantity: adjustedQuantity,
        reason: formData.reason,
        notes: formData.notes || null
      });

      navigate('/mouvements-stock');

    } catch (err) {
      setError(err.response?.data?.message || 'Erreur lors de l\'ajustement');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const getNewQuantity = () => {
    const change = parseInt(formData.quantity) || 0;
    return adjustmentType === 'increase' 
      ? currentQuantity + change 
      : currentQuantity - change;
  };

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>Ajustement de Stock</h1>
          <p className={styles.subtitle}>Corriger les quantités de stock</p>
        </div>
        <button
          className={styles.btnSecondary}
          onClick={() => navigate('/mouvements-stock')}
        >
          <X size={20} />
          Annuler
        </button>
      </div>

      {error && (
        <div className={styles.alertDanger}>
          <AlertCircle size={20} />
          <span>{error}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className={styles.form}>
        <div className={styles.formCard}>
          <h2 className={styles.cardTitle}>Informations de l'ajustement</h2>

          <div className={styles.formGroup}>
            <label className={styles.label}>
              Type d'ajustement <span className={styles.required}>*</span>
            </label>
            <div className={styles.adjustmentTypeSelector}>
              <button
                type="button"
                className={`${styles.typeButton} ${adjustmentType === 'increase' ? styles.active : ''}`}
                onClick={() => setAdjustmentType('increase')}
              >
                <TrendingUp size={20} />
                Augmentation
              </button>
              <button
                type="button"
                className={`${styles.typeButton} ${adjustmentType === 'decrease' ? styles.active : ''}`}
                onClick={() => setAdjustmentType('decrease')}
              >
                <TrendingDown size={20} />
                Diminution
              </button>
            </div>
          </div>

          <div className={styles.formGroup}>
            <label className={styles.label}>
              Location <span className={styles.required}>*</span>
            </label>
            <select
              name="location_id"
              value={formData.location_id}
              onChange={handleLocationChange}
              required
            >
              <option value="">Sélectionner une location</option>
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
                  {vl.variant?.product?.name} - {vl.variant?.sku} (Actuel: {vl.quantity})
                </option>
              ))}
            </select>
            {selectedVariant && (
              <div className={styles.selectedInfo}>
                <Package size={16} />
                <div>
                  <p><strong>{selectedVariant.product?.name}</strong></p>
                  <p className={styles.subText}>
                    SKU: {selectedVariant.sku} | Quantité actuelle: {currentQuantity} unités
                  </p>
                </div>
              </div>
            )}
          </div>

          <div className={styles.formGroup}>
            <label className={styles.label}>
              Quantité à {adjustmentType === 'increase' ? 'ajouter' : 'retirer'} <span className={styles.required}>*</span>
            </label>
            <input
              type="number"
              name="quantity"
              value={formData.quantity}
              onChange={handleChange}
              min="1"
              max={adjustmentType === 'decrease' ? currentQuantity : undefined}
              required
            />
            {formData.quantity > 0 && (
              <div className={styles.quantityPreview}>
                <span className={styles.currentQty}>Actuel: {currentQuantity}</span>
                <span className={adjustmentType === 'increase' ? styles.increase : styles.decrease}>
                  {adjustmentType === 'increase' ? '+' : '-'}{formData.quantity}
                </span>
                <span className={styles.newQty}>Nouveau: {getNewQuantity()}</span>
              </div>
            )}
          </div>

          <div className={styles.formGroup}>
            <label className={styles.label}>
              Raison de l'ajustement <span className={styles.required}>*</span>
            </label>
            <select
              name="reason"
              value={formData.reason}
              onChange={handleChange}
              required
            >
              <option value="">Sélectionner une raison</option>
              <option value="Inventaire physique">Inventaire physique</option>
              <option value="Produit endommagé">Produit endommagé</option>
              <option value="Produit perdu">Produit perdu</option>
              <option value="Erreur de saisie">Erreur de saisie</option>
              <option value="Retour fournisseur">Retour fournisseur</option>
              <option value="Échantillon">Échantillon</option>
              <option value="Don">Don</option>
              <option value="Autre">Autre</option>
            </select>
          </div>

          <div className={styles.formGroup}>
            <label className={styles.label}>Notes complémentaires</label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              rows="4"
              placeholder="Précisions sur l'ajustement..."
            />
          </div>
        </div>

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
            disabled={loading || !formData.variant_id || !formData.quantity || !formData.reason}
          >
            {loading ? (
              <>
                <div className={styles.spinner}></div>
                Enregistrement...
              </>
            ) : (
              <>
                <Save size={20} />
                Enregistrer l'ajustement
              </>
            )}
          </button>
        </div>
      </form>
    </div>
  );
};

export default StockAdjustmentForm;