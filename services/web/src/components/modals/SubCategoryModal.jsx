import { useState, useEffect } from 'react';
import { X, FolderTree, AlertCircle, ChevronDown, Upload, Trash2 } from 'lucide-react';
import api from '../../services/api';
import { fileService } from '../../services/fileService';
import '../../styles/CategoryModal.css';

const SubCategoryModal = ({ 
  isOpen, 
  onClose, 
  onSubmit, 
  parentCategoryId = null,
  parentCategoryName = '',
  subCategory = null, 
  isEdit = false 
}) => {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    parent_id: parentCategoryId || '',
    image_url: '',
  });
  
  const [previewImage, setPreviewImage] = useState('');
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [parentCategories, setParentCategories] = useState([]);
  const [uploadingImage, setUploadingImage] = useState(false);

  // Charger les catégories parent
  useEffect(() => {
    if (isOpen) {
      loadParentCategories();
    }
  }, [isOpen]);

  // Initialiser les données
  useEffect(() => {
    if (isEdit && subCategory) {
      setFormData({
        name: subCategory.name || '',
        description: subCategory.description || '',
        parent_id: subCategory.parent_id || parentCategoryId || '',
        image_url: subCategory.image_url || '',
      });
      if (subCategory.image_url) {
        setPreviewImage(subCategory.image_url);
      }
    } else if (isOpen) {
      setFormData(prev => ({
        ...prev,
        parent_id: parentCategoryId || prev.parent_id
      }));
    }
  }, [isEdit, subCategory, isOpen, parentCategoryId]);

  const loadParentCategories = async () => {
    try {
      const response = await api.get('/categories', {
        params: { parent_id: 'null' } // Récupérer uniquement les catégories principales
      });
      setParentCategories(response.data);
    } catch (error) {
      console.error('Erreur chargement catégories:', error);
    }
  };

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

  const handleImageUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    try {
      setUploadingImage(true);
      
      // Optionnel : compresser l'image
      const compressedFile = await fileService.compressImage(file);
      
      // Uploader vers le serveur
      const response = await fileService.uploadImage(compressedFile);
      
      if (response.success) {
        setPreviewImage(response.url);
        setFormData(prev => ({ ...prev, image_url: response.url }));
      } else {
        alert('Erreur lors de l\'upload de l\'image');
      }
      
    } catch (error) {
      console.error('Erreur upload image:', error);
      alert('Erreur lors du téléchargement de l\'image');
    } finally {
      setUploadingImage(false);
    }
  };

  const handleRemoveImage = async () => {
    if (formData.image_url) {
      try {
        // Extraire le chemin du fichier depuis l'URL
        const path = formData.image_url.split('/storage/')[1];
        if (path) {
          await fileService.deleteImage(path);
        }
      } catch (error) {
        console.error('Erreur suppression image:', error);
        // Continuer quand même pour supprimer le preview
      }
    }
    
    setPreviewImage('');
    setFormData(prev => ({ ...prev, image_url: '' }));
  };


  const validate = () => {
    const newErrors = {};
    
    if (!formData.name.trim()) {
      newErrors.name = 'Nom de la sous-catégorie requis';
    }
    
    if (!formData.parent_id) {
      newErrors.parent_id = 'Catégorie parent requise';
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
      // Appel direct à l'API
      let response;
      if (isEdit) {
        response = await api.put(`/categories/${subCategory.id}`, formData);
      } else {
        response = await api.post('/categories', formData);
      }
      
      if (onSubmit) {
        onSubmit(response.data);
      }
      handleClose();
      alert(isEdit ? 'Sous-catégorie modifiée avec succès !' : 'Sous-catégorie créée avec succès !');
    } catch (error) {
      console.error('Erreur:', error);
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
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
      parent_id: parentCategoryId || '',
      image_url: '',
    });
    setPreviewImage('');
    setErrors({});
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="category-modal-overlay" onClick={handleClose}>
      <div className="category-modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="category-modal-header">
          <h2 className="category-modal-title">
            <FolderTree size={24} />
            {isEdit ? 'Modifier la sous-catégorie' : 'Nouvelle sous-catégorie'}
          </h2>
          <button className="category-modal-close" onClick={handleClose}>
            <X size={20} />
          </button>
        </div>

        <div className="category-modal-body">
          <div className="category-input-group">
            <label className="category-label required">
              Catégorie parent
              <span className="category-helper">
                {parentCategoryId && parentCategoryName 
                  ? `Catégorie présélectionnée: ${parentCategoryName}`
                  : 'Sélectionnez la catégorie principale'
                }
              </span>
            </label>
            <div className="select-wrapper">
              <select
                name="parent_id"
                value={formData.parent_id}
                onChange={handleChange}
                className={`category-input category-select ${errors.parent_id ? 'category-input-error' : ''}`}
                disabled={!!parentCategoryId && !isEdit} // Désactivé si parent présélectionné et création
              >
                <option value="">Sélectionnez une catégorie...</option>
                {parentCategories.map(category => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
              <ChevronDown size={16} className="select-icon" />
            </div>
            {errors.parent_id && (
              <span className="category-error-text">
                <AlertCircle size={14} />
                {errors.parent_id}
              </span>
            )}
          </div>

          <div className="category-input-group">
            <label className="category-label required">
              Nom de la sous-catégorie
              <span className="category-helper">Ex: Smartphones, Pantalons, Tables</span>
            </label>
            <input
              type="text"
              name="name"
              value={formData.name}
              onChange={handleChange}
              placeholder="Nom de la sous-catégorie"
              className={`category-input ${errors.name ? 'category-input-error' : ''}`}
              autoFocus
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
              <span className="category-helper">Description optionnelle</span>
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleChange}
              placeholder="Description de la sous-catégorie..."
              className="category-input category-textarea"
              rows="3"
            />
          </div>

          <div className="category-input-group">
            <label className="category-label">Image de la sous-catégorie</label>
            <div className="image-upload-wrapper">
              {previewImage ? (
                <div className="image-preview">
                  <img src={previewImage} alt="Preview" />
                  <button 
                    type="button" 
                    className="btn-remove-image"
                    onClick={handleRemoveImage}
                    disabled={loading}
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
                            <span>Upload en cours...</span>
                        </>
                        ) : (
                        <>
                            <Upload size={24} />
                            <span>Cliquez pour uploader une image</span>
                            <span className="image-hint">PNG, JPG, WEBP (max 5MB)</span>
                        </>
                        )}
                    </label>
                    </div>
              )}
            </div>
            {formData.image_url && (
              <input
                type="hidden"
                name="image_url"
                value={formData.image_url}
              />
            )}
          </div>


          <div className="category-info-box">
            <AlertCircle size={16} />
            <p>
              {isEdit 
                ? 'Les modifications seront appliquées immédiatement.'
                : 'La sous-catégorie sera disponible immédiatement après création.'
              }
            </p>
          </div>

          <div className="category-modal-actions">
            <button
              type="button"
              className="category-btn category-btn-secondary"
              onClick={handleClose}
              disabled={loading}
            >
              Annuler
            </button>
            <button
              type="button"
              className="category-btn category-btn-primary"
              onClick={handleSubmit}
              disabled={loading}
            >
              {loading 
                ? (isEdit ? 'Modification...' : 'Création...') 
                : (isEdit ? 'Modifier la sous-catégorie' : 'Créer la sous-catégorie')
              }
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SubCategoryModal;