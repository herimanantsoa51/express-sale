import { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { 
  ArrowLeft, 
  Save, 
  Upload, 
  Trash2, 
  Package, 
  Palette, 
  AlertTriangle, 
  Layers,
  Image as ImageIcon,
  Settings,
  PackageOpen,
  AlertCircle,
  CheckCircle,
  Info
} from "lucide-react";

import productService from "../../services/productService";
import { fileService } from "../../services/fileService";

import "../../styles/VariantForm.css";

const VariantForm = () => {
  const { productId, variantId } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(variantId);

  const [loading, setLoading] = useState(false);
  const [uploadingImage, setUploadingImage] = useState(false);
  const [loadingVariant, setLoadingVariant] = useState(false);
  const [errors, setErrors] = useState({});
  const [attributeTypes, setAttributeTypes] = useState([]);
  const [variantAttributes, setVariantAttributes] = useState([]);
  const [product, setProduct] = useState({});
  const [sku, setSku] = useState("");
  const [imageRemoved, setImageRemoved] = useState(false);
  const [currentVariant, setCurrentVariant] = useState(null);

  const [formData, setFormData] = useState({
    price_adjustment: 0,
    low_stock_threshold: 5,
    image: null,
    image_preview: null,
    stock_quantity: 0,
  });

  useEffect(() => {
    loadInitialData();
  }, [variantId]);

  const loadInitialData = async () => {
    try {
      setLoadingVariant(true);
      
      const productcurrent = await productService.getProduct(productId);
      setProduct(productcurrent);
      
      const types = await productService.getProductAttributeTypes(productId);
      setAttributeTypes(types);

      if (isEdit) {
        await loadVariant(types);
      } else {
        setVariantAttributes(
          types.map(attr => ({
            attribute_type_id: attr.id,
            value: "",
            is_required: attr.is_required ?? true,
            attribute_type: attr
          }))
        );
      }
    } catch (e) {
      console.error('❌ Erreur chargement données:', e);
    } finally {
      setLoadingVariant(false);
    }
  };

  const loadVariant = async (types) => {
    try {
      const variant = await productService.getVariant(productId, variantId);
      console.log('✅ Variante chargée:', variant);
      
      setCurrentVariant(variant);
      setSku(variant.sku || "");
      setImageRemoved(false);
      
      setFormData({
        price_adjustment: variant.price_adjustment ?? 0,
        low_stock_threshold: variant.low_stock_threshold ?? 5,
        stock_quantity: variant.stock_quantity ?? 0,
        image: null,
        image_preview: variant.image_path || null,
      });

      const existingValuesMap = {};
      if (variant.attribute_values && Array.isArray(variant.attribute_values)) {
        variant.attribute_values.forEach(av => {
          existingValuesMap[av.attribute_type_id] = av.value;
        });
      }

      setVariantAttributes(
        types.map(attr => ({
          attribute_type_id: attr.id,
          value: existingValuesMap[attr.id] || "",
          is_required: attr.is_required ?? true,
          attribute_type: attr
        }))
      );

    } catch (e) {
      console.error('❌ Erreur chargement variante:', e);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors(prev => ({ ...prev, [name]: '' }));
  };

  const handleAttributeChange = (index, value) => {
    const copy = [...variantAttributes];
    copy[index].value = value;
    setVariantAttributes(copy);
    if (errors.attributes) setErrors(prev => ({ ...prev, attributes: '' }));
  };

  const handleImageChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    try {
      setUploadingImage(true);
      setImageRemoved(false);
      
      if (file.size > 5 * 1024 * 1024) {
        alert('L\'image ne doit pas dépasser 5MB');
        setUploadingImage(false);
        return;
      }

      const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
      if (!allowedTypes.includes(file.type)) {
        alert('Type de fichier non supporté. Utilisez JPG, PNG, GIF ou WEBP.');
        setUploadingImage(false);
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        setFormData(prev => ({ 
          ...prev, 
          image: file,
          image_preview: e.target.result 
        }));
        setUploadingImage(false);
      };
      reader.onerror = () => {
        setUploadingImage(false);
        alert('Erreur lors du chargement de l\'image');
      };
      reader.readAsDataURL(file);

    } catch (error) {
      console.error('Erreur upload image:', error);
      setUploadingImage(false);
      alert('Erreur lors du chargement de l\'image');
    }
  };

  const handleRemoveImage = () => {
    if (isEdit && formData.image_preview) {
      if (!window.confirm('Voulez-vous vraiment supprimer l\'image de cette variante ?')) {
        return;
      }
    }
    
    setFormData(prev => ({ 
      ...prev, 
      image: null,
      image_preview: null
    }));
    setImageRemoved(true);
  };

  const validate = () => {
    const newErrors = {};

    variantAttributes.forEach((v, index) => {
      const attrType = attributeTypes[index];
      if (attrType?.is_required && (!v.value || !v.value.toString().trim())) {
        newErrors.attributes = `Le champ "${attrType.display_name}" est obligatoire`;
      }
    });

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Remplacer tout le handleSubmit par cette version :

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validate()) return;

    setLoading(true);

    try {
      let imagePath = null;

      if (formData.image) {
        console.log('📤 Upload de la nouvelle image...');
        const compressed = await fileService.compressImage(formData.image);
        const uploaded = await fileService.uploadImage(compressed);
        imagePath = uploaded.url;
      } else if (!imageRemoved && formData.image_preview) {
        imagePath = formData.image_preview;
      }

      const payload = {
        price_adjustment: Number(formData.price_adjustment) || 0,
        low_stock_threshold: Number(formData.low_stock_threshold) || 5,
        stock_quantity: Number(formData.stock_quantity) || 0,
        attributes: variantAttributes
          .filter(a => a.value.trim() !== "")
          .map(a => ({
            attribute_type_id: a.attribute_type_id,
            value: a.value
          }))
      };
      
      payload.image_path = imagePath;
      
      console.log('📤 Payload variante:', {
        ...payload,
        hasImage: !!imagePath,
        imageRemoved: imageRemoved,
        imagePreviewExists: !!formData.image_preview
      });
      
      let result;
      if (isEdit) {
        result = await productService.updateVariant(productId, variantId, payload);
        alert('Variante modifiée avec succès !');
      } else {
        result = await productService.createVariant(productId, payload);
        alert('Variante créée avec succès !');
      }

      if (result.sku) {
        console.log('✅ SKU généré:', result.sku);
        setSku(result.sku);
      }

      // 🔹 Gérer le retour avec ouverture du modal
      const params = new URLSearchParams(window.location.search);
      const returnTo = params.get('returnTo');
      const shouldOpenTransferModal = params.get('openTransferModal') === 'true';
      
      if (returnTo && shouldOpenTransferModal) {
        // Rediriger vers la page de retour avec le paramètre pour ouvrir le modal
        if (window.opener) {
          window.opener.location.href = `${returnTo}?openTransferModal=true`;
          window.close();
        } else {
          window.location.href = `${returnTo}?openTransferModal=true`;
        }
      } else if (returnTo) {
        if (window.opener) {
          window.opener.location.href = returnTo;
          window.close();
        } else {
          window.location.href = returnTo;
        }
      } else {
        navigate(-1);
      }

    } catch (err) {
      console.error('❌ Erreur détaillée:', err);
      
      if (err.response?.data?.errors) {
        const errors = Object.values(err.response.data.errors).flat();
        setErrors({ server: errors.join('\n') });
        alert(`Erreur:\n${errors.join('\n')}`);
      } else if (err.response?.data?.message) {
        setErrors({ server: err.response.data.message });
        alert(err.response.data.message);
      } else {
        setErrors({ server: 'Erreur de connexion au serveur' });
        alert('Erreur de connexion au serveur');
      }
    } finally {
      setLoading(false);
    }
  };

  const calculateTotalPrice = () => {
    const basePrice = parseFloat(product.base_price) || 0;
    const adjustment = parseFloat(formData.price_adjustment) || 0;
    return basePrice + adjustment;
  };

  const getStockStatus = () => {
    const quantity = parseInt(formData.stock_quantity) || 0;
    const threshold = parseInt(formData.low_stock_threshold) || 5;
    
    if (quantity === 0) return { 
      label: "Rupture", 
      color: "vf-danger", 
      icon: <AlertCircle size={20} />
    };
    if (quantity <= threshold) return { 
      label: "Stock faible", 
      color: "vf-warning", 
      icon: <AlertTriangle size={20} />
    };
    if (quantity <= threshold * 2) return { 
      label: "Stock moyen", 
      color: "vf-info", 
      icon: <Info size={20} />
    };
    return { 
      label: "Stock bon", 
      color: "vf-success", 
      icon: <CheckCircle size={20} />
    };
  };

  if (loadingVariant) {
    return (
      <div className="vf-page">
        <div className="vf-header">
          <button className="vf-btn-back" onClick={() => navigate(`/products/${productId}`)}>
            <ArrowLeft size={20} />
            Retour
          </button>
          <h1 className="vf-title">
            <Layers size={32} />
            {isEdit ? 'Modification variante' : 'Nouvelle variante'}
          </h1>
        </div>
        <div className="vf-loading-container">
          <div className="vf-spinner vf-spinner-large"></div>
          <p>Chargement des données...</p>
        </div>
      </div>
    );
  }

  const stockStatus = getStockStatus();
  const totalPrice = calculateTotalPrice();

  return (
    <div className="vf-page">
      <div className="vf-header">
        <button className="vf-btn-back" onClick={() => navigate(`/products/${productId}`)}>
          <ArrowLeft size={20} />
          Retour
        </button>
        <div className="vf-header-content">
          <h1 className="vf-title">
            <Layers size={32} />
            {isEdit ? 'Modifier la variante' : 'Nouvelle variante'}
          </h1>
          <p className="vf-subtitle">
            Produit : <strong>{product.name}</strong>
            {sku && <> • SKU : <code>{sku}</code></>}
          </p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="vf-form">
        <div className="vf-grid">
          <div className="vf-section">
            <div className="vf-section-header">
              <h2 className="vf-section-title">
                <ImageIcon size={24} />
                Visuel & Stock
              </h2>
              <p className="vf-section-subtitle">Image et ajustement de prix de la variante</p>
            </div>

            <div className="vf-fields">
              <div className="vf-form-group">
                <label className="vf-label">Image de la variante</label>
                <div className="vf-image-wrapper">
                  {formData.image_preview ? (
                    <div className="vf-image-preview">
                      <img 
                        src={formData.image_preview} 
                        alt="Preview" 
                        onError={(e) => {
                          e.target.style.display = 'none';
                          e.target.nextElementSibling.style.display = 'flex';
                        }}
                      />
                      <div className="vf-image-fallback">
                        <Package size={24} />
                        <span>Image non disponible</span>
                      </div>
                      <button 
                        type="button" 
                        className="vf-btn-remove-image"
                        onClick={handleRemoveImage}
                        disabled={uploadingImage || loading}
                      >
                        <Trash2 size={18} />
                      </button>
                    </div>
                  ) : (
                    <div className="vf-image-upload-area">
                      <input
                        type="file"
                        id="vf-image-upload"
                        accept="image/*"
                        onChange={handleImageChange}
                        className="vf-image-input"
                        disabled={uploadingImage || loading}
                      />
                      <label htmlFor="vf-image-upload" className="vf-image-label">
                        {uploadingImage ? (
                          <>
                            <div className="vf-spinner"></div>
                            <span>Chargement...</span>
                          </>
                        ) : (
                          <>
                            <Upload size={24} />
                            <span>Cliquez pour sélectionner une image</span>
                            <span className="vf-image-hint">PNG, JPG, WEBP (max 5MB)</span>
                          </>
                        )}
                      </label>
                    </div>
                  )}
                </div>
              </div>

              <div className="vf-stock-section">
                <h3 className="vf-stock-title">
                  <PackageOpen size={18} />
                  Gestion du stock
                </h3>
                
                <div className="vf-stock-grid">
                  <div className="vf-form-group">
                    <label className="vf-label">Seuil d'alerte</label>
                    <input
                      type="number"
                      name="low_stock_threshold"
                      value={formData.low_stock_threshold}
                      onChange={handleChange}
                      min="1"
                      className="vf-input"
                      disabled={loading}
                    />
                    <div className="vf-hint">Alerte quand le stock est inférieur à cette valeur</div>
                  </div>
                </div>

                <div className={`vf-stock-summary ${stockStatus.color}`}>
                  <div className="vf-stock-icon">
                    {stockStatus.icon}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="vf-section">
            <div className="vf-section-header">
              <h2 className="vf-section-title">
                <Settings size={24} />
                Caractéristiques
              </h2>
              <p className="vf-section-subtitle">
                {attributeTypes.length} attribut(s) disponible(s)
              </p>
            </div>

            <div className="vf-fields">
              <div className="vf-attributes-section">
                {variantAttributes.length === 0 ? (
                  <div className="vf-empty-attributes">
                    <div className="vf-empty-icon">
                      <Palette size={48} />
                    </div>
                    <h4>Aucun attribut configuré</h4>
                    <p>Configurez d'abord les attributs dans la fiche produit</p>
                  </div>
                ) : (
                  <div className="vf-attributes-list">
                    {variantAttributes.map((attr, index) => {
                      const attrType = attributeTypes.find(t => t.id === attr.attribute_type_id);
                      return (
                        <div key={attrType?.id || index} className="vf-attribute-item">
                          <div className="vf-attribute-header">
                            <span className="vf-attribute-index">#{index + 1}</span>
                            <span className="vf-attribute-name">
                              {attrType?.display_name || attrType?.name || 'Attribut'}
                              {attr.is_required && <span className="vf-required"> *</span>}
                            </span>
                          </div>
                          <div className="vf-attribute-controls">
                            {(attrType?.input_type === "select" || attrType?.input_type === "multiselect") ? (
                              <div className="vf-select-wrapper">
                                <select
                                  value={attr.value || ""}
                                  onChange={(e) => handleAttributeChange(index, e.target.value)}
                                  className={`vf-select ${!attr.value && attr.is_required ? 'vf-error' : ''}`}
                                  required={attr.is_required}
                                  disabled={loading}
                                >
                                  <option value="">— choisir —</option>
                                  {attrType.values?.map(v => (
                                    <option key={v.id} value={v.value}>
                                      {v.value}
                                    </option>
                                  ))}
                                </select>
                                <span className="vf-select-arrow">▼</span>
                              </div>
                            ) : attrType?.input_type === "color" ? (
                              <div className="vf-color-wrapper">
                                <input
                                  type="color"
                                  value={attr.value || "#000000"}
                                  onChange={(e) => handleAttributeChange(index, e.target.value)}
                                  className="vf-color-input"
                                  required={attr.is_required}
                                  disabled={loading}
                                />
                                <span className="vf-color-value">{attr.value || "Choisir"}</span>
                              </div>
                            ) : (
                              <input
                                type={attrType?.input_type === "number" ? "number" : "text"}
                                value={attr.value || ""}
                                onChange={(e) => handleAttributeChange(index, e.target.value)}
                                className={`vf-input ${!attr.value && attr.is_required ? 'vf-error' : ''}`}
                                placeholder={!attr.is_required ? "(Optionnel)" : "Saisissez une valeur..."}
                                required={attr.is_required}
                                disabled={loading}
                              />
                            )}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                )}

                {errors.attributes && (
                  <div className="vf-error-message">
                    <AlertTriangle size={16} />
                    {errors.attributes}
                  </div>
                )}
              </div>

              <div className="vf-actions">
                <button 
                  type="button" 
                  className="vf-btn-secondary"
                  onClick={() => navigate(-1)}
                  disabled={loading || uploadingImage}
                >
                  Annuler
                </button>
                <button 
                  type="submit" 
                  className="vf-btn-primary"
                  disabled={loading || uploadingImage}
                >
                  {loading ? (
                    <>
                      <div className="vf-spinner vf-spinner-small"></div>
                      <span>Enregistrement...</span>
                    </>
                  ) : (
                    <>
                      <Save size={18} />
                      <span>{isEdit ? 'Modifier la variante' : 'Créer la variante'}</span>
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>

      {errors.server && (
        <div className="vf-server-error">
          <AlertTriangle size={20} />
          <span>{errors.server}</span>
        </div>
      )}
    </div>
  );
};

export default VariantForm;