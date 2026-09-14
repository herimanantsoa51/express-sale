import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Plus, Edit2, Save, FileText, Image as ImageIcon, X, AlertCircle, Plane, Ship } from 'lucide-react';
import freightForwarderService from '../../services/freightForwarderService';
import { fileService } from '../../services/fileService';
import CoordinateSelect from '../../components/CoordinateSelect';
import '../../styles/FreightForwarderForm.css';

const FreightForwarderForm = () => {
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
    type: 'aerien',
    coordinate_id: null,
    logo_url: '',
    contact: '',
    notes: '',
    is_active: true,
    service_score: 0
  });

  const [coordinateData, setCoordinateData] = useState(null);
  const [imageRemoved, setImageRemoved] = useState(false);

  useEffect(() => {
    if (isEditMode) {
      loadForwarder();
    } else {
      setDataLoaded(true);
    }
  }, [id, isEditMode]);

  const loadForwarder = async () => {
    try {
      setLoading(true);
      setDataLoaded(false);
      setServerError(null);
      setImageRemoved(false);
      
      const response = await freightForwarderService.getFreightForwarder(id);
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
        type: data.type || 'aerien',
        coordinate_id: coordinateIdToUse,
        logo_url: imageUrl,
        contact: data.contact || '',
        notes: data.notes || '',
        is_active: data.is_active !== false,
        service_score: parseFloat(data.service_score) || 0
      };
      
      setFormData(newFormData);
      
      setTimeout(() => {
        setDataLoaded(true);
      }, 100);
      
    } catch (err) {
      console.error('❌ Erreur lors du chargement:', err);
      setServerError(`Erreur lors du chargement du transitaire: ${err.message}`);
      setDataLoaded(true);
    } finally {
      setLoading(false);
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

  const handleNumberChange = (e) => {
    const { name, value } = e.target;
    const numValue = parseFloat(value) || 0;
    
    setFormData(prev => ({
      ...prev,
      [name]: numValue
    }));
    
    if (errors[name]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
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
      if (!window.confirm('Voulez-vous vraiment supprimer le logo de ce transitaire ?')) {
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

    if (!formData.type) {
      newErrors.type = 'Le type est requis';
    }

    if (formData.service_score < 0 || formData.service_score > 10) {
      newErrors.service_score = 'La note doit être entre 0 et 10';
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
        type: formData.type,
        coordinate_id: formData.coordinate_id || null,
        contact: formData.contact || '',
        notes: formData.notes || '',
        is_active: formData.is_active,
        service_score: parseFloat(formData.service_score) || 0,
        logo_url: logoUrl
      };

      if (isEditMode) {
        await freightForwarderService.updateFreightForwarder(id, submitData);
      } else {
        await freightForwarderService.createFreightForwarder(submitData);
      }
      
      navigate('/transitaires');
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
      <div className="ff-page">
        <div className="ff-loading">
          <div className="ff-spinner"></div>
          <p>Chargement des données du transitaire...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="ff-page">
      {/* Header avec breadcrumb */}
      <div className="ff-header">
        <button 
          className="ff-back-btn"
          onClick={() => navigate('/transitaires')}
          disabled={submitting}
        >
          <ArrowLeft size={20} />
        </button>

        <div className="ff-header-content">
          <h1 className="ff-title">
            {isEditMode ? 'Modifier le transitaire' : 'Nouveau transitaire'}
          </h1>
          <p className="ff-subtitle">
            {isEditMode 
              ? 'Mettez à jour les informations du transitaire' 
              : 'Ajoutez un nouveau transitaire à votre réseau'}
          </p>
        </div>
      </div>

      {/* Formulaire */}
      <form className="ff-form" onSubmit={handleSubmit}>
        <div className="ff-grid">
          {/* Section Informations */}
          <div className="ff-card">
            <div className="ff-card-header">
              <FileText size={20} className="ff-card-icon" />
              <div>
                <h2 className="ff-card-title">Informations générales</h2>
                <p className="ff-card-desc">Détails du transitaire</p>
              </div>
            </div>

            <div className="ff-fields">
              {/* Nom */}
              <div className="ff-field">
                <label className="ff-label ff-required">Nom du transitaire</label>
                <input
                  type="text"
                  name="name"
                  className={`ff-input ${errors.name ? 'ff-error' : ''}`}
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="China Express Logistics"
                  disabled={submitting}
                />
                {errors.name && (
                  <div className="ff-error-msg">
                    <AlertCircle size={14} />
                    {errors.name}
                  </div>
                )}
              </div>

              {/* Type */}
              <div className="ff-field">
                <label className="ff-label ff-required">Type de transport</label>
                <div className="ff-type-selector">
                  <button
                    type="button"
                    className={`ff-type-btn ${formData.type === 'aerien' ? 'ff-type-active' : ''}`}
                    onClick={() => {
                      setFormData(prev => ({ ...prev, type: 'aerien' }));
                      if (errors.type) {
                        setErrors(prev => {
                          const newErrors = { ...prev };
                          delete newErrors.type;
                          return newErrors;
                        });
                      }
                    }}
                    disabled={submitting}
                  >
                    <Plane size={20} />
                    <span>Aérien</span>
                  </button>
                  <button
                    type="button"
                    className={`ff-type-btn ${formData.type === 'maritime' ? 'ff-type-active' : ''}`}
                    onClick={() => {
                      setFormData(prev => ({ ...prev, type: 'maritime' }));
                      if (errors.type) {
                        setErrors(prev => {
                          const newErrors = { ...prev };
                          delete newErrors.type;
                          return newErrors;
                        });
                      }
                    }}
                    disabled={submitting}
                  >
                    <Ship size={20} />
                    <span>Maritime</span>
                  </button>
                </div>
                {errors.type && (
                  <div className="ff-error-msg">
                    <AlertCircle size={14} />
                    {errors.type}
                  </div>
                )}
              </div>

              {/* Localisation */}
              <div className="ff-field">
                <label className="ff-label">Localisation</label>
                {coordinateData && (
                  <div className="ff-current-location">
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
                  <div className="ff-coord-loading">
                    <div className="ff-spinner-sm"></div>
                    <span>Chargement...</span>
                  </div>
                )}
              </div>

              {/* Contact */}
              <div className="ff-field">
                <label className="ff-label">Contact</label>
                <input
                  type="text"
                  name="contact"
                  className="ff-input"
                  value={formData.contact}
                  onChange={handleChange}
                  placeholder="+86 138 1234 5678"
                  disabled={submitting}
                />
              </div>

              {/* Note de service */}
              {/* <div className="ff-field">
                <label className="ff-label">
                  Note de service
                  <span className="ff-score-value">{formData.service_score.toFixed(1)}/10</span>
                </label>
                <input
                  type="number"
                  name="service_score"
                  min="0"
                  max="10"
                  step="0.1"
                  className={`ff-input ${errors.service_score ? 'ff-error' : ''}`}
                  value={formData.service_score}
                  onChange={handleNumberChange}
                  disabled={submitting}
                />
                {errors.service_score && (
                  <div className="ff-error-msg">
                    <AlertCircle size={14} />
                    {errors.service_score}
                  </div>
                )}
              </div> */}

              {/* Statut */}
              <label className="ff-checkbox">
                <input
                  type="checkbox"
                  name="is_active"
                  checked={formData.is_active}
                  onChange={handleChange}
                  disabled={submitting}
                />
                <span className="ff-checkbox-label">Transitaire actif</span>
              </label>
            </div>
          </div>

          {/* Section Logo & Notes */}
          <div className="ff-card">
            <div className="ff-card-header">
              <ImageIcon size={20} className="ff-card-icon" />
              <div>
                <h2 className="ff-card-title">Logo & notes</h2>
                <p className="ff-card-desc">Informations complémentaires</p>
              </div>
            </div>

            <div className="ff-fields">
              {/* Logo */}
              <div className="ff-field">
                <label className="ff-label">Logo</label>
                <div className="ff-image-wrapper">
                  {formData.logo_url ? (
                    <div className="ff-image-preview">
                      <img 
                        src={formData.logo_url} 
                        alt="Logo"
                        onError={(e) => {
                          e.target.style.display = 'none';
                          e.target.nextElementSibling.style.display = 'flex';
                        }}
                      />
                      <div className="ff-image-fallback">
                        <ImageIcon size={24} />
                        <span>Image non disponible</span>
                      </div>
                      <button
                        type="button"
                        className="ff-image-remove"
                        onClick={handleRemoveImage}
                        disabled={submitting || imageUploading}
                      >
                        <X size={16} />
                      </button>
                    </div>
                  ) : (
                    <div className="ff-image-upload">
                      <input
                        type="file"
                        id="ff-logo-input"
                        className="ff-image-input"
                        accept="image/*"
                        onChange={handleImageSelect}
                        disabled={submitting || imageUploading}
                      />
                      <label htmlFor="ff-logo-input" className="ff-image-label">
                        {imageUploading ? (
                          <>
                            <div className="ff-spinner-sm"></div>
                            <span>Chargement...</span>
                          </>
                        ) : (
                          <>
                            <ImageIcon size={32} strokeWidth={1.5} />
                            <span className="ff-image-text">Ajouter un logo</span>
                            <span className="ff-image-hint">PNG, JPG • Max 5 Mo</span>
                          </>
                        )}
                      </label>
                    </div>
                  )}
                </div>
                {errors.logo_url && (
                  <div className="ff-error-msg">
                    <AlertCircle size={14} />
                    {errors.logo_url}
                  </div>
                )}
              </div>

              {/* Notes */}
              <div className="ff-field">
                <label className="ff-label">Notes</label>
                <textarea
                  name="notes"
                  className="ff-textarea"
                  value={formData.notes}
                  onChange={handleChange}
                  placeholder="Notes sur les services, tarifs, spécialités..."
                  rows="8"
                  disabled={submitting}
                />
              </div>
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="ff-actions">
          <button
            type="button"
            className="ff-btn ff-btn-secondary"
            onClick={() => navigate('/transitaires')}
            disabled={submitting}
          >
            Annuler
          </button>
          <button
            type="submit"
            className="ff-btn ff-btn-primary"
            disabled={submitting || imageUploading}
          >
            {submitting ? (
              <>
                <div className="ff-spinner-sm"></div>
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
        <div className="ff-toast">
          <AlertCircle size={18} />
          {serverError}
        </div>
      )}
    </div>
  );
};

export default FreightForwarderForm;