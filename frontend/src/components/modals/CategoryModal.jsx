import { useState, useEffect } from 'react';
import { X, FolderPlus, AlertCircle, Upload, Trash2 } from 'lucide-react';
import productService from '../../services/productService';
import '../../styles/CategoryModal.css';

const CategoryModal = ({ 
  isOpen, 
  onClose, 
  onSubmit, 
  existingCategoryNames = new Set(),
  category = null, 
  isEdit = false 
}) => {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    sort_order: 0,
    image: null,
    image_preview: ''
  });
  
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [uploadingImage, setUploadingImage] = useState(false);

  // Initialiser les données si édition
  useEffect(() => {
    if (isEdit && category) {
      setFormData({
        name: category.name || '',
        description: category.description || '',
        sort_order: category.sort_order || 0,
        image: null,
        image_preview: category.image_url || ''
      });
    } else if (isOpen) {
      // Réinitialiser à l'ouverture
      setFormData({
        name: '',
        description: '',
        sort_order: 0,
        image: null,
        image_preview: ''
      });
      setErrors({});
    }
  }, [isEdit, category, isOpen]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ 
      ...prev, 
      [name]: name === 'sort_order' ? parseInt(value) || 0 : value 
    }));
    
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Vérifier la taille (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('L\'image ne doit pas dépasser 5MB');
      return;
    }

    // Vérifier le type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
      alert('Type de fichier non supporté. Utilisez JPG, PNG, GIF ou WEBP.');
      return;
    }

    setUploadingImage(true);
    
    // Créer un preview local
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
  };

  const handleRemoveImage = () => {
    setFormData(prev => ({ 
      ...prev, 
      image: null,
      image_preview: '' 
    }));
  };

  const validate = () => {
    const newErrors = {};
    
    if (!formData.name.trim()) {
      newErrors.name = 'Nom de la catégorie requis';
    } else if (existingCategoryNames.has(formData.name.toLowerCase().trim())) {
      newErrors.name = 'Une catégorie avec ce nom existe déjà';
    }
    
    if (formData.sort_order < 0) {
      newErrors.sort_order = 'L\'ordre doit être positif';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async () => {
    if (!validate()) return;
    
    setLoading(true);
    try {
      // Préparer FormData pour l'upload d'image
      const formDataToSend = new FormData();
      formDataToSend.append('name', formData.name);
      formDataToSend.append('description', formData.description || '');
      formDataToSend.append('sort_order', formData.sort_order.toString());
      
      // Ajouter l'image si elle existe
      if (formData.image) {
        formDataToSend.append('image', formData.image);
      }

      let response;
      if (isEdit) {
        response = await productService.updateCategory(category.id, formDataToSend);
      } else {
        response = await productService.createCategory(formDataToSend);
      }
      
      if (onSubmit) {
        onSubmit(response); // Passer la réponse complète
      }
      
      handleClose();
      
    } catch (error) {
      console.error('Erreur:', error);
      
      // Gérer les erreurs de validation du serveur
      if (error.response?.data?.errors) {
        const serverErrors = error.response.data.errors;
        const newErrors = {};
        
        if (serverErrors.name) {
          newErrors.name = Array.isArray(serverErrors.name) 
            ? serverErrors.name[0] 
            : serverErrors.name;
        }
        
        if (serverErrors.sort_order) {
          newErrors.sort_order = Array.isArray(serverErrors.sort_order) 
            ? serverErrors.sort_order[0] 
            : serverErrors.sort_order;
        }
        
        setErrors(newErrors);
      } else {
        alert(error.response?.data?.message || 'Erreur lors de l\'opération');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setFormData({
      name: '',
      description: '',
      sort_order: 0,
      image: null,
      image_preview: ''
    });
    setErrors({});
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="category-modal-overlay" onClick={handleClose}>
      <div className="category-modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="category-modal-header">
          <h2 className="category-modal-title">
            <FolderPlus size={24} />
            {isEdit ? 'Modifier la catégorie' : 'Nouvelle catégorie'}
          </h2>
          <button className="category-modal-close" onClick={handleClose}>
            <X size={20} />
          </button>
        </div>

        <div className="category-modal-body">
          <div className="category-input-group">
            <label className="category-label required">
              Nom de la catégorie
              <span className="category-helper">Ex: Électronique, Vêtements, Meubles</span>
            </label>
            <input
              type="text"
              name="name"
              value={formData.name}
              onChange={handleChange}
              placeholder="Nom de la catégorie"
              className={`category-input ${errors.name ? 'category-input-error' : ''}`}
              autoFocus
              disabled={loading}
            />
            {errors.name && (
              <span className="category-error-text">
                <AlertCircle size={14} />
                {errors.name}
              </span>
            )}
          </div>

          <div className="category-input-group">
            <label className="category-label">
              Description
              <span className="category-helper">Description optionnelle de la catégorie</span>
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleChange}
              placeholder="Description de la catégorie..."
              className="category-input category-textarea"
              rows="3"
              disabled={loading}
            />
          </div>

          <div className="category-input-group">
            <label className="category-label">Ordre d'affichage</label>
            <input
              type="number"
              name="sort_order"
              value={formData.sort_order}
              onChange={handleChange}
              min="0"
              className={`category-input ${errors.sort_order ? 'category-input-error' : ''}`}
              disabled={loading}
            />
            {errors.sort_order && (
              <span className="category-error-text">
                <AlertCircle size={14} />
                {errors.sort_order}
              </span>
            )}
          </div>

          <div className="category-input-group">
            <label className="category-label">Image de la catégorie</label>
            <div className="image-upload-wrapper">
              {formData.image_preview ? (
                <div className="image-preview">
                  <img src={formData.image_preview} alt="Preview" />
                  <button 
                    type="button" 
                    className="btn-remove-image"
                    onClick={handleRemoveImage}
                    disabled={loading || uploadingImage}
                  >
                    <Trash2 size={18} />
                  </button>
                </div>
              ) : (
                <div className="image-upload-area">
                  <input
                    type="file"
                    id="category-image-upload"
                    accept="image/*"
                    onChange={handleImageUpload}
                    className="image-input"
                    disabled={uploadingImage || loading}
                  />
                  <label htmlFor="category-image-upload" className="image-upload-label">
                    {uploadingImage ? (
                      <>
                        <div className="loading-spinner"></div>
                        <span>Chargement...</span>
                      </>
                    ) : (
                      <>
                        <Upload size={24} />
                        <span>Cliquez pour sélectionner une image</span>
                        <span className="image-hint">PNG, JPG, WEBP (max 5MB)</span>
                      </>
                    )}
                  </label>
                </div>
              )}
            </div>
          </div>

          <div className="category-info-box">
            <AlertCircle size={16} />
            <p>
              {isEdit 
                ? 'Les modifications seront appliquées immédiatement.'
                : 'La catégorie sera disponible immédiatement après création.'
              }
            </p>
          </div>

          <div className="category-modal-actions">
            <button
              type="button"
              className="category-btn category-btn-secondary"
              onClick={handleClose}
              disabled={loading || uploadingImage}
            >
              Annuler
            </button>
            <button
              type="button"
              className="category-btn category-btn-primary"
              onClick={handleSubmit}
              disabled={loading || uploadingImage}
            >
              {loading 
                ? (isEdit ? 'Modification...' : 'Création...') 
                : (isEdit ? 'Modifier la catégorie' : 'Créer la catégorie')
              }
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CategoryModal;