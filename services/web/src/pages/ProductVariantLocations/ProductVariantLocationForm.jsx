import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Save, X, AlertCircle, Package, MapPin } from 'lucide-react';
import productVariantLocationService from '../../services/productVariantLocationService';
import locationService from '../../services/locationService';
import productService from '../../services/productService';
import styles from './ProductVariantLocations.module.css';

const ProductVariantLocationForm = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const isEdit = Boolean(id);

  const [formData, setFormData] = useState({
    variant_id: '',
    location_id: '',
    quantity: 0,
    notes: ''
  });

  const [locations, setLocations] = useState([]);
  const [variants, setVariants] = useState([]);
  const [selectedVariant, setSelectedVariant] = useState(null);
  const [selectedLocation, setSelectedLocation] = useState(null);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [submitError, setSubmitError] = useState(null);

  useEffect(() => {
    loadInitialData();
  }, [id]);

  const loadInitialData = async () => {
    try {
      const locationsData = await locationService.getActive();
      setLocations(locationsData);

      // Charger tous les produits avec leurs variantes
      const productsData = await productService.getProducts();
      
      // Extraire toutes les variantes de tous les produits
      const allVariants = [];
      productsData.data?.forEach(product => {
        if (product.variants && product.variants.length > 0) {
          product.variants.forEach(variant => {
            allVariants.push({
              ...variant,
              product: {
                id: product.id,
                name: product.name
              }
            });
          });
        }
      });
      
      setVariants(allVariants);

      if (isEdit) {
        const data = await productVariantLocationService.getById(id);
        setFormData({
          variant_id: data.variant_id,
          location_id: data.location_id,
          quantity: data.quantity,
          notes: data.notes || ''
        });
        setSelectedVariant(data.variant);
        setSelectedLocation(data.location);
      }
    } catch (err) {
      console.error(err);
      setSubmitError('Erreur lors du chargement des données');
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    setErrors(prev => ({ ...prev, [name]: '' }));
  };

  const handleVariantChange = (e) => {
    const variantId = parseInt(e.target.value);
    setFormData(prev => ({ ...prev, variant_id: variantId }));
    const variant = variants.find(v => v.id === variantId);
    setSelectedVariant(variant);
    setErrors(prev => ({ ...prev, variant_id: '' }));
  };

  const handleLocationChange = (e) => {
    const locationId = parseInt(e.target.value);
    setFormData(prev => ({ ...prev, location_id: locationId }));
    const location = locations.find(l => l.id === locationId);
    setSelectedLocation(location);
    setErrors(prev => ({ ...prev, location_id: '' }));
  };

  const validate = () => {
    const newErrors = {};

    if (!formData.variant_id) {
      newErrors.variant_id = 'La variante est requise';
    }

    if (!formData.location_id) {
      newErrors.location_id = 'La location est requise';
    }

    if (formData.quantity < 0) {
      newErrors.quantity = 'La quantité ne peut pas être négative';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validate()) return;

    try {
      setLoading(true);
      setSubmitError(null);

      const dataToSend = {
        ...formData,
        quantity: parseInt(formData.quantity)
      };

      if (isEdit) {
        await productVariantLocationService.update(id, dataToSend);
      } else {
        await productVariantLocationService.create(dataToSend);
      }

      navigate('/localisations-variantes');
    } catch (err) {
      console.error(err);
      setSubmitError(
        err.response?.data?.message || 
        'Erreur lors de l\'enregistrement de la localisation'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>
            {isEdit ? 'Modifier la Localisation' : 'Nouvelle Localisation'}
          </h1>
          <p className={styles.subtitle}>
            {isEdit 
              ? 'Modifiez l\'emplacement d\'une variante de produit' 
              : 'Ajoutez une nouvelle localisation de variante'}
          </p>
        </div>
        <button
          className={styles.btnSecondary}
          onClick={() => navigate('/localisations-variantes')}
        >
          <X size={20} />
          Annuler
        </button>
      </div>

      {submitError && (
        <div className={styles.alertDanger}>
          <AlertCircle size={20} />
          <span>{submitError}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className={styles.form}>
        <div className={styles.formCard}>
          <h2 className={styles.cardTitle}>Informations de Localisation</h2>

          <div className={styles.formRow}>
            <div className={styles.formGroup}>
              <label className={styles.label}>
                Variante de Produit <span className={styles.required}>*</span>
              </label>
              <select
                name="variant_id"
                value={formData.variant_id}
                onChange={handleVariantChange}
                className={errors.variant_id ? styles.inputError : ''}
                disabled={isEdit}
              >
                <option value="">Sélectionnez une variante</option>
                {variants.map(variant => (
                  <option key={variant.id} value={variant.id}>
                    {variant.product?.name} - {variant.sku}
                  </option>
                ))}
              </select>
              {errors.variant_id && (
                <span className={styles.errorText}>{errors.variant_id}</span>
              )}
              {selectedVariant && (
                <div className={styles.selectedInfo}>
                  <Package size={16} />
                  <div>
                    <p><strong>{selectedVariant.product?.name}</strong></p>
                    <p className={styles.subText}>
                      SKU: {selectedVariant.sku} | 
                      Stock: {selectedVariant.stock_quantity} unités
                    </p>
                  </div>
                </div>
              )}
            </div>

            <div className={styles.formGroup}>
              <label className={styles.label}>
                Location <span className={styles.required}>*</span>
              </label>
              <select
                name="location_id"
                value={formData.location_id}
                onChange={handleLocationChange}
                className={errors.location_id ? styles.inputError : ''}
              >
                <option value="">Sélectionnez une location</option>
                {locations.map(location => (
                  <option key={location.id} value={location.id}>
                    {location.name} - {location.warehouse}
                  </option>
                ))}
              </select>
              {errors.location_id && (
                <span className={styles.errorText}>{errors.location_id}</span>
              )}
              {selectedLocation && (
                <div className={styles.selectedInfo}>
                  <MapPin size={16} />
                  <div>
                    <p><strong>{selectedLocation.name}</strong></p>
                    <p className={styles.subText}>
                      {selectedLocation.warehouse} | 
                      Code: {selectedLocation.code}
                      {selectedLocation.capacity && ` | Capacité: ${selectedLocation.capacity}`}
                    </p>
                  </div>
                </div>
              )}
            </div>
          </div>

          <div className={styles.formRow}>
            <div className={styles.formGroup}>
              <label className={styles.label}>
                Quantité <span className={styles.required}>*</span>
              </label>
              <input
                type="number"
                name="quantity"
                value={formData.quantity}
                onChange={handleChange}
                min="0"
                className={errors.quantity ? styles.inputError : ''}
              />
              {errors.quantity && (
                <span className={styles.errorText}>{errors.quantity}</span>
              )}
            </div>
          </div>

          <div className={styles.formGroup}>
            <label className={styles.label}>Notes</label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              rows="4"
              placeholder="Notes additionnelles sur cette localisation..."
            />
          </div>
        </div>

        <div className={styles.formActions}>
          <button
            type="button"
            className={styles.btnSecondary}
            onClick={() => navigate('/localisations-variantes')}
          >
            Annuler
          </button>
          <button
            type="submit"
            className={styles.btnPrimary}
            disabled={loading}
          >
            {loading ? (
              <>
                <div className={styles.spinner}></div>
                Enregistrement...
              </>
            ) : (
              <>
                <Save size={20} />
                {isEdit ? 'Mettre à jour' : 'Créer'}
              </>
            )}
          </button>
        </div>
      </form>
    </div>
  );
};

export default ProductVariantLocationForm;