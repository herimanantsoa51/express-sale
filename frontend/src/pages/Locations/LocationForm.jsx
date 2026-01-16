import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  MapPin,
  ArrowLeft,
  Save,
  Warehouse,
  Hash,
  FileText,
  Layers,
  Box,
  ToggleLeft,
  ToggleRight,
  AlertCircle
} from 'lucide-react';
import locationService from '../../services/locationService';
import './Locations.css';

const LocationForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEditing = Boolean(id);

  const [formData, setFormData] = useState({
    name: '',
    code: '',
    warehouse: '',
    aisle: '',
    shelf: '',
    bin: '',
    description: '',
    capacity: '',
    is_active: true
  });

  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [errors, setErrors] = useState({});

  // Liste des entrepôts existants pour autocomplétion
  const [warehouses, setWarehouses] = useState([]);
  const [showWarehouseSuggestions, setShowWarehouseSuggestions] = useState(false);

  useEffect(() => {
    loadWarehouses();
    if (isEditing) {
      loadLocation();
    }
  }, [id]);

  const loadLocation = async () => {
    try {
      setLoading(true);
      const data = await locationService.getById(id);
      setFormData({
        name: data.name || '',
        code: data.code || '',
        warehouse: data.warehouse || '',
        aisle: data.aisle || '',
        shelf: data.shelf || '',
        bin: data.bin || '',
        description: data.description || '',
        capacity: data.capacity || '',
        is_active: data.is_active ?? true
      });
    } catch (err) {
      console.error('Erreur chargement location:', err);
      setError('Erreur lors du chargement de la location');
    } finally {
      setLoading(false);
    }
  };

  const loadWarehouses = async () => {
    try {
      const data = await locationService.getWarehouses();
      setWarehouses(data || []);
    } catch (err) {
      console.error('Erreur chargement entrepôts:', err);
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));

    // Effacer l'erreur du champ modifié
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Le nom est requis';
    }

    if (!formData.code.trim()) {
      newErrors.code = 'Le code est requis';
    } else if (formData.code.length > 100) {
      newErrors.code = 'Le code ne doit pas dépasser 100 caractères';
    }

    if (!formData.warehouse.trim()) {
      newErrors.warehouse = "L'entrepôt est requis";
    }

    if (formData.capacity && (isNaN(formData.capacity) || Number(formData.capacity) < 0)) {
      newErrors.capacity = 'La capacité doit être un nombre positif';
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
      setSaving(true);
      setError(null);

      const dataToSend = {
        ...formData,
        capacity: formData.capacity ? Number(formData.capacity) : null,
        aisle: formData.aisle || null,
        shelf: formData.shelf || null,
        bin: formData.bin || null,
        description: formData.description || null
      };

      if (isEditing) {
        await locationService.update(id, dataToSend);
      } else {
        await locationService.create(dataToSend);
      }

      navigate('/locations');
    } catch (err) {
      console.error('Erreur sauvegarde:', err);
      if (err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      } else {
        setError(err.response?.data?.message || 'Erreur lors de la sauvegarde');
      }
    } finally {
      setSaving(false);
    }
  };

  const filteredWarehouses = warehouses.filter(w =>
    w.toLowerCase().includes(formData.warehouse.toLowerCase()) &&
    w.toLowerCase() !== formData.warehouse.toLowerCase()
  );

  if (loading) {
    return (
      <div className="location-form-page">
        <div className="loading-container">
          <div className="loading-spinner large"></div>
          <p>Chargement...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="location-form-page">
      {/* Header */}
      <div className="form-header">
        <button className="btn-back" onClick={() => navigate('/locations')}>
          <ArrowLeft size={20} />
          Retour
        </button>

        <div className="header-info">
          <h1 className="form-title">
            <Warehouse className="title-icon" />
            {isEditing ? 'Modifier la location' : 'Nouvelle location'}
          </h1>
          <p className="form-subtitle">
            {isEditing
              ? 'Modifiez les informations de la location'
              : 'Créez un nouvel emplacement de stockage'}
          </p>
        </div>
      </div>

      {/* Message d'erreur global */}
      {error && (
        <div className="error-banner">
          <AlertCircle size={18} />
          <span>{error}</span>
        </div>
      )}

      {/* Formulaire */}
      <form onSubmit={handleSubmit} className="location-form">
        <div className="form-card">
          <h2 className="form-section-title">Informations principales</h2>

          <div className="form-grid">
            {/* Nom */}
            <div className="form-group">
              <label className="form-label">
                <FileText size={16} />
                Nom <span className="required">*</span>
              </label>
              <input
                type="text"
                name="name"
                value={formData.name}
                onChange={handleChange}
                className={`form-input ${errors.name ? 'error' : ''}`}
                placeholder="Ex: Zone A - Électronique"
              />
              {errors.name && <span className="field-error">{errors.name}</span>}
            </div>

            {/* Code */}
            <div className="form-group">
              <label className="form-label">
                <Hash size={16} />
                Code unique <span className="required">*</span>
              </label>
              <input
                type="text"
                name="code"
                value={formData.code}
                onChange={handleChange}
                className={`form-input ${errors.code ? 'error' : ''}`}
                placeholder="Ex: LOC-A01"
              />
              {errors.code && <span className="field-error">{errors.code}</span>}
            </div>

            {/* Entrepôt */}
            <div className="form-group">
              <label className="form-label">
                <Warehouse size={16} />
                Entrepôt <span className="required">*</span>
              </label>
              <div className="autocomplete-wrapper">
                <input
                  type="text"
                  name="warehouse"
                  value={formData.warehouse}
                  onChange={handleChange}
                  onFocus={() => setShowWarehouseSuggestions(true)}
                  onBlur={() => setTimeout(() => setShowWarehouseSuggestions(false), 200)}
                  className={`form-input ${errors.warehouse ? 'error' : ''}`}
                  placeholder="Ex: Entrepôt Principal"
                />
                {showWarehouseSuggestions && filteredWarehouses.length > 0 && (
                  <div className="autocomplete-dropdown">
                    {filteredWarehouses.map((w, index) => (
                      <div
                        key={index}
                        className="autocomplete-item"
                        onClick={() => {
                          setFormData(prev => ({ ...prev, warehouse: w }));
                          setShowWarehouseSuggestions(false);
                        }}
                      >
                        {w}
                      </div>
                    ))}
                  </div>
                )}
              </div>
              {errors.warehouse && <span className="field-error">{errors.warehouse}</span>}
            </div>

            {/* Capacité */}
            <div className="form-group">
              <label className="form-label">
                <Box size={16} />
                Capacité (optionnel)
              </label>
              <input
                type="number"
                name="capacity"
                value={formData.capacity}
                onChange={handleChange}
                className={`form-input ${errors.capacity ? 'error' : ''}`}
                placeholder="Ex: 1000"
                min="0"
              />
              {errors.capacity && <span className="field-error">{errors.capacity}</span>}
              <span className="field-hint">Nombre maximum d'articles pouvant être stockés</span>
            </div>
          </div>
        </div>

        <div className="form-card">
          <h2 className="form-section-title">Emplacement détaillé</h2>
          <p className="form-section-description">
            Précisez l'emplacement exact dans l'entrepôt (optionnel)
          </p>

          <div className="form-grid three-columns">
            {/* Allée */}
            <div className="form-group">
              <label className="form-label">
                <Layers size={16} />
                Allée
              </label>
              <input
                type="text"
                name="aisle"
                value={formData.aisle}
                onChange={handleChange}
                className="form-input"
                placeholder="Ex: A1"
              />
            </div>

            {/* Étagère */}
            <div className="form-group">
              <label className="form-label">
                <Layers size={16} />
                Étagère
              </label>
              <input
                type="text"
                name="shelf"
                value={formData.shelf}
                onChange={handleChange}
                className="form-input"
                placeholder="Ex: 03"
              />
            </div>

            {/* Bac */}
            <div className="form-group">
              <label className="form-label">
                <Box size={16} />
                Bac
              </label>
              <input
                type="text"
                name="bin"
                value={formData.bin}
                onChange={handleChange}
                className="form-input"
                placeholder="Ex: B2"
              />
            </div>
          </div>

          {/* Aperçu du chemin */}
          {(formData.warehouse || formData.aisle || formData.shelf || formData.bin) && (
            <div className="path-preview">
              <span className="path-label">Chemin complet:</span>
              <span className="path-value">
                <MapPin size={14} />
                {[
                  formData.warehouse,
                  formData.aisle && `Allée ${formData.aisle}`,
                  formData.shelf && `Étagère ${formData.shelf}`,
                  formData.bin && `Bac ${formData.bin}`
                ].filter(Boolean).join(' / ') || 'Non défini'}
              </span>
            </div>
          )}
        </div>

        <div className="form-card">
          <h2 className="form-section-title">Description et statut</h2>

          {/* Description */}
          <div className="form-group full-width">
            <label className="form-label">
              <FileText size={16} />
              Description
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleChange}
              className="form-textarea"
              placeholder="Décrivez cette location (type de produits stockés, particularités...)"
              rows={3}
            />
          </div>

          {/* Statut actif */}
          <div className="form-group">
            <label className="form-label">Statut</label>
            <button
              type="button"
              className={`toggle-btn ${formData.is_active ? 'active' : ''}`}
              onClick={() => setFormData(prev => ({ ...prev, is_active: !prev.is_active }))}
            >
              {formData.is_active ? (
                <>
                  <ToggleRight size={24} />
                  <span>Active</span>
                </>
              ) : (
                <>
                  <ToggleLeft size={24} />
                  <span>Inactive</span>
                </>
              )}
            </button>
            <span className="field-hint">
              Une location inactive ne peut pas recevoir de nouveaux articles
            </span>
          </div>
        </div>

        {/* Actions */}
        <div className="form-actions">
          <button
            type="button"
            className="btn-secondary"
            onClick={() => navigate('/locations')}
            disabled={saving}
          >
            Annuler
          </button>
          <button
            type="submit"
            className="btn-primary"
            disabled={saving}
          >
            {saving ? (
              <>
                <div className="loading-spinner small"></div>
                Enregistrement...
              </>
            ) : (
              <>
                <Save size={18} />
                {isEditing ? 'Enregistrer' : 'Créer la location'}
              </>
            )}
          </button>
        </div>
      </form>
    </div>
  );
};

export default LocationForm;
