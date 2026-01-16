import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { 
  ArrowLeft, Save, Plus, X, Package, Sparkles,
  FolderPlus, FolderTree, ChevronDown, Upload, Trash2
} from 'lucide-react';
import { fileService } from '../../services/fileService';
import productService from '../../services/productService';
import CategoryModal from '../../components/modals/CategoryModal';
import SubCategoryModal from '../../components/modals/SubCategoryModal';
import AttributeModal from '../../components/modals/AttributeModal';
import '../../styles/ProductForm.css';

const ProductForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEditing = !!id;

  const [loading, setLoading] = useState(false);
  const [uploadingImage, setUploadingImage] = useState(false);
  const [loadingProduct, setLoadingProduct] = useState(false);
  const [categories, setCategories] = useState([]);
  const [subcategories, setSubcategories] = useState([]);
  const [attributeTypes, setAttributeTypes] = useState([]);
  const [showAttributeModal, setShowAttributeModal] = useState(false);
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [showSubCategoryModal, setShowSubCategoryModal] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [selectedSubCategory, setSelectedSubCategory] = useState(null);
  const [imageRemoved, setImageRemoved] = useState(false);
  
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    category_id: '',
    subcategory_id: '',
    base_price: '',
    image: null,
    image_preview: '',
    is_active: true,
  });

  const [errors, setErrors] = useState({});
  const [selectedAttributes, setSelectedAttributes] = useState([]);
  const [currentProduct, setCurrentProduct] = useState(null);

  useEffect(() => {
    loadInitialData();
  }, []);

  useEffect(() => {
    if (id) {
      loadProduct();
    }
  }, [id]);

  useEffect(() => {
    if (formData.category_id) {
      loadSubcategories(formData.category_id);
      const selected = categories.find(cat => cat.id == formData.category_id);
      setSelectedCategory(selected);
    } else {
      setSubcategories([]);
      setSelectedCategory(null);
      setSelectedSubCategory(null);
      setFormData(prev => ({ ...prev, subcategory_id: '' }));
    }
  }, [formData.category_id, categories]);

  useEffect(() => {
    if (formData.subcategory_id) {
      const selected = subcategories.find(cat => cat.id == formData.subcategory_id);
      setSelectedSubCategory(selected);
    } else {
      setSelectedSubCategory(null);
    }
  }, [formData.subcategory_id, subcategories]);

  const loadInitialData = async () => {
    try {
      const [categoriesData, attributeTypesData] = await Promise.all([
        productService.getCategories(),
        productService.getAttributeTypes(),
      ]);
      
      const mainCategories = categoriesData.filter(cat => !cat.parent_id);
      setCategories(mainCategories);
      setAttributeTypes(attributeTypesData);
    } catch (error) {
      console.error('Erreur chargement données:', error);
    }
  };

  const loadSubcategories = async (categoryId) => {
    try {
      const response = await productService.getCategories({ parent_id: categoryId });
      setSubcategories(response);
    } catch (error) {
      console.error('Erreur chargement sous-catégories:', error);
      setSubcategories([]);
    }
  };

  const loadProduct = async () => {
    try {
      setLoadingProduct(true);
      setImageRemoved(false); 
      const product = await productService.getProduct(id);
      
      setCurrentProduct(product);
      
      const imageUrl = product.image_url || '';
      
      setFormData({
        name: product.name || '',
        description: product.description || '',
        category_id: product.category_id || '',
        subcategory_id: product.subcategory_id || '',
        base_price: product.base_price || '',
        image: null,
        image_preview: imageUrl,
        is_active: product.is_active ?? true,
      });
    
      if (product.attributes && Array.isArray(product.attributes)) {
        if (product.attributes.length > 0) {
          const attrs = product.attributes.map((attr) => ({
            attribute_type_id: attr.attribute_type_id,
            is_required: attr.is_required ?? true,
            name: attr.attribute_type?.display_name || 
                  attr.attribute_type?.name || 
                  `Attribut ${attr.attribute_type_id}`,
          }));
          
          setSelectedAttributes(attrs);
        } else {
          setSelectedAttributes([]);
        }
      } else {
        setSelectedAttributes([]);
      }
      
    } catch (error) {
      console.error('Erreur chargement produit:', error);
      alert('Erreur lors du chargement du produit');
      navigate('/produits');
    } finally {
      setLoadingProduct(false);
    }
  };
  
  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
    
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const handleImageUpload = async (e) => {
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
    if (isEditing && currentProduct?.image_url) {
      if (!window.confirm('Voulez-vous vraiment supprimer l\'image de ce produit ?')) {
        return;
      }
    }
    setFormData(prev => ({ 
      ...prev, 
      image: null,
      image_preview: ''
    }));
    setImageRemoved(true);
  };

  const handleAddAttribute = () => {
    const availableTypes = attributeTypes.filter(
      type => !selectedAttributes.find(attr => attr.attribute_type_id === type.id)
    );

    if (availableTypes.length === 0) {
      alert('Tous les attributs disponibles sont déjà ajoutés. Créez-en un nouveau !');
      return;
    }

    const firstAvailable = availableTypes[0];
    const newAttr = {
      attribute_type_id: firstAvailable.id,
      is_required: true,
      name: firstAvailable.display_name || firstAvailable.name,
    };

    setSelectedAttributes(prev => [...prev, newAttr]);
  };

  const handleRemoveAttribute = (index) => {
    setSelectedAttributes(prev => prev.filter((_, i) => i !== index));
  };

  const handleAttributeChange = (index, field, value) => {
    setSelectedAttributes(prev => prev.map((attr, i) => {
      if (i === index) {
        if (field === 'attribute_type_id') {
          const type = attributeTypes.find(t => t.id === parseInt(value));
          return {
            ...attr,
            attribute_type_id: parseInt(value),
            name: type?.display_name || type?.name || '',
          };
        }
        return { ...attr, [field]: value };
      }
      return attr;
    }));
  };

  const handleCreateAttribute = async (data) => {
    try {
      const existingAttribute = attributeTypes.find(
        attr => attr.name.toLowerCase() === data.name.toLowerCase()
      );
      
      if (existingAttribute) {
        alert('Un attribut avec ce nom existe déjà !');
        return;
      }

      const newAttribute = await productService.createAttributeType(data);
      
      const updatedTypes = await productService.getAttributeTypes();
      setAttributeTypes(updatedTypes);
      
      setSelectedAttributes(prev => [...prev, {
        attribute_type_id: newAttribute.id,
        is_required: true,
        name: newAttribute.display_name || newAttribute.name,
      }]);
      
      alert('Attribut créé avec succès !');
      setShowAttributeModal(false);
    } catch (error) {
      console.error('Erreur création attribut:', error);
      if (error.response?.data?.errors?.name) {
        alert('Un attribut avec ce nom existe déjà !');
      } else {
        alert(error.response?.data?.message || 'Erreur lors de la création de l\'attribut');
      }
      throw error;
    }
  };

  const handleCategoryCreated = (newCategory) => {
    loadInitialData();
    setFormData(prev => ({ ...prev, category_id: newCategory.id }));
    alert('Catégorie créée avec succès !');
  };

  const handleSubCategoryCreated = (newSubCategory) => {
    if (formData.category_id) {
      loadSubcategories(formData.category_id);
    }
    setFormData(prev => ({ ...prev, subcategory_id: newSubCategory.id }));
    alert('Sous-catégorie créée avec succès !');
  };

  const validate = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Nom requis';
    }

    if (!formData.category_id) {
      newErrors.category_id = 'Catégorie requise';
    }

    if (!formData.base_price || parseFloat(formData.base_price) < 0) {
      newErrors.base_price = 'Prix invalide';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const uploadImageIfNeeded = async () => {
    if (!formData.image) {
      return formData.image_preview || null;
    }

    try {
      setUploadingImage(true);
      const uploadResponse = await fileService.uploadImage(formData.image);
      
      if (uploadResponse.success) {
        return uploadResponse.url;
      } else {
        throw new Error('Upload échoué');
      }
    } catch (error) {
      console.error('Erreur upload image:', error);
      throw error;
    } finally {
      setUploadingImage(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
  
    if (!validate()) {
      return;
    }
  
    setLoading(true);
  
    try {
      let imageUrl = null;
      
      if (formData.image) {
        const uploadResponse = await uploadImageIfNeeded();
        if (uploadResponse) {
          imageUrl = uploadResponse;
        }
      } else if (!imageRemoved && formData.image_preview) {
        imageUrl = formData.image_preview;
      }
  
      const data = {
        name: formData.name,
        description: formData.description || '',
        category_id: parseInt(formData.category_id),
        base_price: parseFloat(formData.base_price),
        is_active: formData.is_active,
        attributes: selectedAttributes.map(attr => ({
          attribute_type_id: parseInt(attr.attribute_type_id),
          is_required: attr.is_required,
        })),
      };
  
      if (formData.subcategory_id && formData.subcategory_id !== '' && formData.subcategory_id !== 'null') {
        data.subcategory_id = parseInt(formData.subcategory_id);
      }
  
      data.image_url = imageUrl;
  
      let response;
      if (isEditing) {
        response = await productService.updateProduct(id, data);
        alert('Produit modifié avec succès !');
      } else {
        response = await productService.createProduct(data);
        alert('Produit créé avec succès !');
      }
      
      navigate('/produits');
      
    } catch (error) {
      console.error('Erreur détaillée:', error);
      
      if (error.response) {
        let errorMessage = 'Erreur lors de la sauvegarde';
        if (error.response.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat();
          errorMessage = errors.join('\n');
        } else if (error.response.data?.message) {
          errorMessage = error.response.data.message;
        } else if (error.response.data?.error) {
          errorMessage = error.response.data.error;
        }
        
        alert(`Erreur:\n${errorMessage}`);
      } else {
        alert('Erreur de connexion au serveur');
      }
    } finally {
      setLoading(false);
    }
  };

  const availableAttributeTypes = attributeTypes.filter(
    type => !selectedAttributes.find(attr => attr.attribute_type_id === type.id)
  );

  if (loadingProduct) {
    return (
      <div className="pf-page">
        <div className="pf-header">
          <button className="pf-btn-back" onClick={() => navigate('/produits')}>
            <ArrowLeft size={20} />
            Retour
          </button>
          <h1 className="pf-title">
            <Package size={32} />
            Chargement du produit...
          </h1>
        </div>
        <div className="pf-loading-container">
          <div className="pf-spinner pf-spinner-large"></div>
          <p>Chargement des données du produit...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="pf-page">
      <div className="pf-header">
        <button className="pf-btn-back" onClick={() => navigate('/produits')}>
          <ArrowLeft size={20} />
          Retour
        </button>
        <h1 className="pf-title">
          <Package size={32} />
          {isEditing ? 'Modifier le produit' : 'Nouveau produit'}
        </h1>
      </div>

      <form onSubmit={handleSubmit} className="pf-form">
        <div className="pf-grid">
          <div className="pf-section">
            <div className="pf-section-header">
              <h2 className="pf-section-title">Informations générales</h2>
              <p className="pf-section-subtitle">Renseignez les détails de base du produit</p>
            </div>

            <div className="pf-fields">
              <div className="pf-form-group">
                <label className="pf-label pf-required">Nom du produit</label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Ex: Stan Smith"
                  className={`pf-input ${errors.name ? 'pf-error' : ''}`}
                  autoFocus
                  disabled={loading}
                />
                {errors.name && <span className="pf-error-text">{errors.name}</span>}
              </div>

              <div className="pf-form-group">
                <label className="pf-label">Description</label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  placeholder="Décrivez le produit..."
                  className="pf-textarea"
                  rows={4}
                  disabled={loading}
                />
              </div>

              <div className="pf-form-group">
                <label className="pf-label pf-required">Catégorie</label>
                <div className="pf-select-action">
                  <div className="pf-select-wrapper">
                    <select
                      name="category_id"
                      value={formData.category_id}
                      onChange={handleChange}
                      className={`pf-select ${errors.category_id ? 'pf-error' : ''}`}
                      disabled={loading}
                    >
                      <option value="">Sélectionner une catégorie</option>
                      {categories.map(cat => (
                        <option key={cat.id} value={cat.id}>
                          {cat.name}
                        </option>
                      ))}
                    </select>
                    <ChevronDown className="pf-select-arrow" />
                  </div>
                  <button 
                    type="button" 
                    className="pf-btn-add"
                    onClick={() => setShowCategoryModal(true)}
                    disabled={loading}
                  >
                    <FolderPlus size={16} />
                    Nouvelle
                  </button>
                </div>
                {errors.category_id && <span className="pf-error-text">{errors.category_id}</span>}
              </div>

              <div className="pf-form-group">
                <label className="pf-label">Sous-catégorie</label>
                <div className="pf-select-action">
                  <div className="pf-select-wrapper">
                    <select
                      name="subcategory_id"
                      value={formData.subcategory_id}
                      onChange={handleChange}
                      className="pf-select"
                      disabled={!formData.category_id || loading}
                    >
                      <option value="">
                        {!formData.category_id 
                          ? 'Choisissez d\'abord une catégorie' 
                          : subcategories.length === 0 
                          ? 'Aucune sous-catégorie'
                          : 'Sélectionner...'
                        }
                      </option>
                      {subcategories.map(cat => (
                        <option key={cat.id} value={cat.id}>
                          {cat.name}
                        </option>
                      ))}
                    </select>
                    <ChevronDown className="pf-select-arrow" />
                  </div>
                  <button 
                    type="button" 
                    className="pf-btn-add"
                    onClick={() => setShowSubCategoryModal(true)}
                    disabled={!formData.category_id || loading}
                  >
                    <FolderTree size={16} />
                    Nouvelle
                  </button>
                </div>
              </div>

              <div className="pf-form-group">
                <label className="pf-label pf-required">Prix de base (Ar)</label>
                <input
                  type="number"
                  name="base_price"
                  value={formData.base_price}
                  onChange={handleChange}
                  placeholder="0"
                  className={`pf-input ${errors.base_price ? 'pf-error' : ''}`}
                  min="0"
                  step="1"
                  disabled={loading}
                />
                {errors.base_price && <span className="pf-error-text">{errors.base_price}</span>}
              </div>

              <div className="pf-form-group">
                <label className="pf-label">Image du produit</label>
                <div className="pf-image-wrapper">
                  {formData.image_preview ? (
                    <div className="pf-image-preview">
                      <img 
                        src={formData.image_preview} 
                        alt="Preview" 
                        onError={(e) => {
                          e.target.style.display = 'none';
                          e.target.nextElementSibling.style.display = 'flex';
                        }}
                      />
                      <div className="pf-image-fallback">
                        <Package size={24} />
                        <span>Image non disponible</span>
                      </div>
                      <button 
                        type="button" 
                        className="pf-btn-remove-image"
                        onClick={handleRemoveImage}
                        disabled={uploadingImage || loading}
                      >
                        <Trash2 size={18} />
                      </button>
                    </div>
                  ) : (
                    <div className="pf-image-upload-area">
                      <input
                        type="file"
                        id="pf-image-upload"
                        accept="image/*"
                        onChange={handleImageUpload}
                        className="pf-image-input"
                        disabled={uploadingImage || loading}
                      />
                      <label htmlFor="pf-image-upload" className="pf-image-label">
                        {uploadingImage ? (
                          <>
                            <div className="pf-spinner"></div>
                            <span>Chargement...</span>
                          </>
                        ) : (
                          <>
                            <Upload size={24} />
                            <span>Cliquez pour sélectionner une image</span>
                            <span className="pf-hint">PNG, JPG, WEBP (max 5MB)</span>
                          </>
                        )}
                      </label>
                    </div>
                  )}
                </div>
              </div>

              <div className="pf-form-group">
                <label className="pf-checkbox-label">
                  <input
                    type="checkbox"
                    name="is_active"
                    checked={formData.is_active}
                    onChange={handleChange}
                    className="pf-checkbox-input"
                    disabled={loading}
                  />
                  <span className="pf-checkbox-custom"></span>
                  <span>Produit actif</span>
                </label>
              </div>
            </div>
          </div>

          <div className="pf-section">
            <div className="pf-section-header">
              <h2 className="pf-section-title">Attributs & Variantes</h2>
              <p className="pf-section-subtitle">Définissez les caractéristiques variables du produit</p>
            </div>

            <div className="pf-attributes-section">
              <div className="pf-attributes-header">
                <div className="pf-attributes-title">
                  <h3>Attributs configurés</h3>
                  <span className="pf-attributes-count">{selectedAttributes.length} attribut(s)</span>
                </div>
                <div className="pf-attributes-actions">
                  <button 
                    type="button" 
                    className="pf-btn-attr"
                    onClick={() => setShowAttributeModal(true)}
                    disabled={loading}
                  >
                    <Sparkles size={16} />
                    Nouveau type
                  </button>
                  <button 
                    type="button" 
                    className="pf-btn-attr pf-btn-attr-secondary"
                    onClick={handleAddAttribute}
                    disabled={availableAttributeTypes.length === 0 || loading}
                  >
                    <Plus size={16} />
                    Ajouter
                  </button>
                </div>
              </div>

              {selectedAttributes.length === 0 ? (
                <div className="pf-empty-attributes">
                  <div className="pf-empty-icon">
                    <Package size={48} />
                  </div>
                  <h4>Aucun attribut défini</h4>
                  <p>Ajoutez des attributs pour créer des variantes de produit</p>
                </div>
              ) : (
                <div className="pf-attributes-list">
                  {selectedAttributes.map((attr, index) => (
                    <div key={index} className="pf-attribute-item">
                      <div className="pf-attribute-header">
                        <span className="pf-attribute-index">#{index + 1}</span>
                        <span className="pf-attribute-name">{attr.name}</span>
                      </div>
                      <div className="pf-attribute-controls">
                        <div className="pf-select-wrapper">
                          <select
                            value={attr.attribute_type_id}
                            onChange={(e) => handleAttributeChange(index, 'attribute_type_id', e.target.value)}
                            className="pf-select"
                            disabled={loading}
                          >
                            <option value={attr.attribute_type_id}>{attr.name}</option>
                            {availableAttributeTypes.map(type => (
                              <option key={type.id} value={type.id}>
                                {type.display_name || type.name}
                              </option>
                            ))}
                          </select>
                          <ChevronDown className="pf-select-arrow" />
                        </div>
                        <label className="pf-checkbox-label pf-checkbox-small">
                          <input
                            type="checkbox"
                            checked={attr.is_required}
                            onChange={(e) => handleAttributeChange(index, 'is_required', e.target.checked)}
                            className="pf-checkbox-input"
                            disabled={loading}
                          />
                          <span className="pf-checkbox-custom"></span>
                          <span>Obligatoire</span>
                        </label>
                        <button 
                          type="button" 
                          className="pf-btn-remove-attr"
                          onClick={() => handleRemoveAttribute(index)}
                          disabled={loading}
                        >
                          <X size={16} />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}

              <div className="pf-attributes-info">
                <p>Les attributs permettent de créer des variantes avec des stocks séparés.</p>
              </div>
            </div>

            <div className="pf-actions">
              <button 
                type="button" 
                className="pf-btn-secondary"
                onClick={() => navigate('/produits')}
                disabled={loading || uploadingImage}
              >
                Annuler
              </button>
              <button 
                type="submit" 
                className="pf-btn-primary"
                disabled={loading || uploadingImage}
              >
                {loading ? (
                  <>
                    <div className="pf-spinner pf-spinner-small"></div>
                    <span>Enregistrement...</span>
                  </>
                ) : (
                  <>
                    <Save size={18} />
                    <span>{isEditing ? 'Modifier' : 'Créer'}</span>
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      </form>

      <AttributeModal
        isOpen={showAttributeModal}
        onClose={() => setShowAttributeModal(false)}
        onSubmit={handleCreateAttribute}
      />

      <CategoryModal
        isOpen={showCategoryModal}
        onClose={() => setShowCategoryModal(false)}
        onSubmit={handleCategoryCreated}
      />

      <SubCategoryModal
        isOpen={showSubCategoryModal}
        onClose={() => setShowSubCategoryModal(false)}
        onSubmit={handleSubCategoryCreated}
        parentCategoryId={formData.category_id}
        parentCategoryName={selectedCategory?.name}
        categories={categories}
      />
    </div>
  );
};

export default ProductForm;