// ============================================
// pages/Suppliers/SupplierForm.jsx - APPLE DESIGN
// ============================================

import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Plus, Edit2, Save, FileText, Image as ImageIcon, X, AlertCircle } from 'lucide-react';
import supplierService from '../../services/supplierService';
import { fileService } from '../../services/fileService';
import CoordinateSelect from '../../components/CoordinateSelect';
import '../../styles/SupplierForm.css';

const SupplierForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEditMode = Boolean(id);

  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [imageUploading, setImageUploading] = useState(false);
  const [errors, setErrors] = useState({});
  const [serverError, setServerError] = useState(null);
  const [dataLoaded, setDataLoaded] = useState(false);

  const [formData, setFormData] = useState({
    name: '',
    coordinate_id: null,
    wechat: '',
    profile: '',
    contact: '',
    accessibility_notes: '',
    logo_url: '',
    is_active: true
  });

  const [imagePreview, setImagePreview] = useState(null);
  const [coordinateData, setCoordinateData] = useState(null);
  const [imageRemoved, setImageRemoved] = useState(false);

  useEffect(() => {
    if (isEditMode) {
      loadSupplier();
    } else {
      setDataLoaded(true);
    }
  }, [id, isEditMode]);

  const loadSupplier = async () => {
    try {
      setLoading(true);
      setDataLoaded(false);
      setServerError(null);
      setImageRemoved(false);
      
      const response = await supplierService.getSupplier(id);
      const data = response?.data || response;
      
      if (!data) {
        throw new Error('Aucune donnée reçue de l\'API');
      }
      
      if (data.coordinate) {
        setCoordinateData(data.coordinate);
      }
      
      const coordinateIdToUse = data.coordinate_id || (data.coordinate?.id || null);
      const imageUrl = data.logo_url || '';
      
      const newFormData = {
        name: data.name || '',
        coordinate_id: coordinateIdToUse,
        wechat: data.wechat || '',
        profile: data.profile || '',
        contact: data.contact || '',
        accessibility_notes: data.accessibility_notes || '',
        logo_url: imageUrl,
        is_active: data.is_active !== false
      };
      
      setFormData(newFormData);
      
      setTimeout(() => {
        setDataLoaded(true);
      }, 100);
      
    } catch (err) {
      console.error('❌ Erreur lors du chargement:', err);
      setServerError('Erreur lors du chargement du fournisseur');
      setDataLoaded(true);
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
    if (errors[name]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  const handleCoordinateChange = (coordinateId) => {
    setFormData(prev => ({
      ...prev,
      coordinate_id: coordinateId
    }));
    
    setCoordinateData(null);
    
    if (errors.coordinate_id) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors.coordinate_id;
        return newErrors;
      });
    }
  };

  const handleImageSelect = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      setErrors(prev => ({ ...prev, logo_url: 'Veuillez sélectionner une image valide' }));
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      setErrors(prev => ({ ...prev, logo_url: 'L\'image ne doit pas dépasser 5 Mo' }));
      return;
    }

    try {
      setImageUploading(true);
      setImageRemoved(false);
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors.logo_url;
        return newErrors;
      });

      const reader = new FileReader();
      reader.onload = (e) => {
        setFormData(prev => ({ 
          ...prev, 
          logo_url: e.target.result,
          logo_file: file
        }));
        setImageUploading(false);
      };
      reader.onerror = () => {
        setImageUploading(false);
        setErrors(prev => ({ ...prev, logo_url: 'Erreur lors du chargement de l\'image' }));
      };
      reader.readAsDataURL(file);

    } catch (err) {
      console.error('❌ Erreur upload:', err);
      setImageUploading(false);
      setErrors(prev => ({ ...prev, logo_url: 'Erreur lors du chargement de l\'image' }));
    }
  };

  const handleRemoveImage = () => {
    if (isEditMode && formData.logo_url && !formData.logo_file) {
      if (!window.confirm('Voulez-vous vraiment supprimer le logo de ce fournisseur ?')) {
        return;
      }
    }
    
    setFormData(prev => ({ 
      ...prev, 
      logo_url: '',
      logo_file: null
    }));
    setImageRemoved(true);
  };

  const validateForm = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Le nom est requis';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    try {
      setSubmitting(true);
      setServerError(null);

      let logoUrl = null;

      // Upload nouvelle image si présente
      if (formData.logo_file) {
        try {
          const compressedFile = await fileService.compressImage(formData.logo_file, 800, 0.85);
          const uploadResponse = await fileService.uploadImage(compressedFile);
          
          if (uploadResponse && uploadResponse.url) {
            logoUrl = uploadResponse.url;
          }
        } catch (uploadError) {
          console.error('Erreur upload image:', uploadError);
          throw new Error('Erreur lors de l\'upload de l\'image');
        }
      } else if (!imageRemoved && formData.logo_url) {
        // Garder l'ancienne URL si pas de nouvelle image et pas supprimée
        logoUrl = formData.logo_url;
      }

      const submitData = {
        name: formData.name,
        coordinate_id: formData.coordinate_id || null,
        wechat: formData.wechat || '',
        profile: formData.profile || '',
        contact: formData.contact || '',
        accessibility_notes: formData.accessibility_notes || '',
        is_active: formData.is_active,
        logo_url: logoUrl
      };

      if (isEditMode) {
        await supplierService.updateSupplier(id, submitData);
      } else {
        await supplierService.createSupplier(submitData);
      }

      navigate('/fournisseurs');
    } catch (err) {
      console.error('❌ Erreur soumission:', err);
      
      let errorMessage = 'Erreur lors de la sauvegarde';
      if (err.response?.data?.errors) {
        const errors = Object.values(err.response.data.errors).flat();
        errorMessage = errors.join('\n');
      } else if (err.response?.data?.message) {
        errorMessage = err.response.data.message;
      } else if (err.message) {
        errorMessage = err.message;
      }
      
      setServerError(errorMessage);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="sp-page">
        <div className="sp-loading">
          <div className="sp-spinner"></div>
          <p>Chargement des données du fournisseur...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="sp-page">
      {/* Header */}
      <div className="sp-header">
        <button 
          className="sp-back-btn"
          onClick={() => navigate('/fournisseurs')}
          disabled={submitting}
        >
          <ArrowLeft size={20} />
        </button>

        <div className="sp-header-content">
          <h1 className="sp-title">
            {isEditMode ? 'Modifier le fournisseur' : 'Nouveau fournisseur'}
          </h1>
          <p className="sp-subtitle">
            {isEditMode 
              ? 'Mettez à jour les informations du fournisseur' 
              : 'Ajoutez un nouveau fournisseur à votre réseau'}
          </p>
        </div>
      </div>

      {/* Formulaire */}
      <form className="sp-form" onSubmit={handleSubmit}>
        <div className="sp-grid">
          {/* Section Informations */}
          <div className="sp-card">
            <div className="sp-card-header">
              <FileText size={20} className="sp-card-icon" />
              <div>
                <h2 className="sp-card-title">Informations générales</h2>
                <p className="sp-card-desc">Détails du fournisseur</p>
              </div>
            </div>

            <div className="sp-fields">
              {/* Nom */}
              <div className="sp-field">
                <label className="sp-label sp-required">Nom du fournisseur</label>
                <input
                  type="text"
                  name="name"
                  className={`sp-input ${errors.name ? 'sp-error' : ''}`}
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Guangzhou Fashion Co."
                  disabled={submitting}
                />
                {errors.name && (
                  <div className="sp-error-msg">
                    <AlertCircle size={14} />
                    {errors.name}
                  </div>
                )}
              </div>

              {/* Localisation */}
              <div className="sp-field">
                <label className="sp-label">Localisation</label>
                {coordinateData && (
                  <div className="sp-current-location">
                    Sélectionnez une nouvelle localisation pour modifier
                  </div>
                )}
                
                {dataLoaded ? (
                  <CoordinateSelect
                    value={formData.coordinate_id || (coordinateData?.id || null)}
                    onChange={handleCoordinateChange}
                    error={errors.coordinate_id}
                    disabled={submitting}
                    label=""
                    required={false}
                    showAddNew={true}
                  />
                ) : (
                  <div className="sp-coord-loading">
                    <div className="sp-spinner-sm"></div>
                    <span>Chargement...</span>
                  </div>
                )}
              </div>

              {/* Contact */}
              <div className="sp-field">
                <label className="sp-label">Contact</label>
                <input
                  type="text"
                  name="contact"
                  className="sp-input"
                  value={formData.contact}
                  onChange={handleChange}
                  placeholder="+86 138 1234 5678"
                  disabled={submitting}
                />
              </div>

              {/* WeChat */}
              <div className="sp-field">
                <label className="sp-label">WeChat ID</label>
                <input
                  type="text"
                  name="wechat"
                  className="sp-input"
                  value={formData.wechat}
                  onChange={handleChange}
                  placeholder="fashion_supplier_gz"
                  disabled={submitting}
                />
              </div>

              {/* Statut */}
              <label className="sp-checkbox">
                <input
                  type="checkbox"
                  name="is_active"
                  checked={formData.is_active}
                  onChange={handleChange}
                  disabled={submitting}
                />
                <span className="sp-checkbox-label">Fournisseur actif</span>
              </label>
            </div>
          </div>

          {/* Section Logo & Détails */}
          <div className="sp-card">
            <div className="sp-card-header">
              <ImageIcon size={20} className="sp-card-icon" />
              <div>
                <h2 className="sp-card-title">Logo & détails</h2>
                <p className="sp-card-desc">Informations complémentaires</p>
              </div>
            </div>

            <div className="sp-fields">
              {/* Logo */}
              <div className="sp-field">
                <label className="sp-label">Logo</label>
                <div className="sp-image-wrapper">
                  {formData.logo_url ? (
                    <div className="sp-image-preview">
                      <img 
                        src={formData.logo_url} 
                        alt="Logo"
                        onError={(e) => {
                          e.target.style.display = 'none';
                          e.target.nextElementSibling.style.display = 'flex';
                        }}
                      />
                      <div className="sp-image-fallback">
                        <ImageIcon size={24} />
                        <span>Image non disponible</span>
                      </div>
                      <button
                        type="button"
                        className="sp-image-remove"
                        onClick={handleRemoveImage}
                        disabled={submitting || imageUploading}
                      >
                        <X size={16} />
                      </button>
                    </div>
                  ) : (
                    <div className="sp-image-upload">
                      <input
                        type="file"
                        id="sp-logo-input"
                        className="sp-image-input"
                        accept="image/*"
                        onChange={handleImageSelect}
                        disabled={submitting || imageUploading}
                      />
                      <label htmlFor="sp-logo-input" className="sp-image-label">
                        {imageUploading ? (
                          <>
                            <div className="sp-spinner-sm"></div>
                            <span>Chargement...</span>
                          </>
                        ) : (
                          <>
                            <ImageIcon size={32} strokeWidth={1.5} />
                            <span className="sp-image-text">Ajouter un logo</span>
                            <span className="sp-image-hint">PNG, JPG • Max 5 Mo</span>
                          </>
                        )}
                      </label>
                    </div>
                  )}
                </div>
                {errors.logo_url && (
                  <div className="sp-error-msg">
                    <AlertCircle size={14} />
                    {errors.logo_url}
                  </div>
                )}
              </div>

              {/* Profil */}
              <div className="sp-field">
                <label className="sp-label">Profil / Description</label>
                <textarea
                  name="profile"
                  className="sp-textarea"
                  value={formData.profile}
                  onChange={handleChange}
                  placeholder="Description du fournisseur, spécialités..."
                  rows="4"
                  disabled={submitting}
                />
              </div>

              {/* Notes d'accessibilité */}
              <div className="sp-field">
                <label className="sp-label">Notes d'accessibilité</label>
                <textarea
                  name="accessibility_notes"
                  className="sp-textarea"
                  value={formData.accessibility_notes}
                  onChange={handleChange}
                  placeholder="Horaires, conditions d'accès..."
                  rows="4"
                  disabled={submitting}
                />
              </div>
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="sp-actions">
          <button
            type="button"
            className="sp-btn sp-btn-secondary"
            onClick={() => navigate('/fournisseurs')}
            disabled={submitting}
          >
            Annuler
          </button>
          <button
            type="submit"
            className="sp-btn sp-btn-primary"
            disabled={submitting || imageUploading}
          >
            {submitting ? (
              <>
                <div className="sp-spinner-sm"></div>
                Enregistrement...
              </>
            ) : (
              <>
                {isEditMode ? (
                  <>
                    <Save size={18} />
                    Enregistrer
                  </>
                ) : (
                  <>
                    <Plus size={18} />
                    Créer
                  </>
                )}
              </>
            )}
          </button>
        </div>
      </form>

      {/* Erreur serveur */}
      {serverError && (
        <div className="sp-toast">
          <AlertCircle size={18} />
          {serverError}
        </div>
      )}
    </div>
  );
};

export default SupplierForm;