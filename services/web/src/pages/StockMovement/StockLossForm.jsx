// ============================================
// StockLossForm.jsx - Apple Style avec Dark/Light Mode
// ============================================

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  Save, X, AlertCircle, Package, 
  Search, Shield, ChevronLeft, ChevronRight, Moon, Sun
} from 'lucide-react';
import stockMovementService from '../../services/stockMovementService';
import locationService from '../../services/locationService';
import productVariantLocationService from '../../services/productVariantLocationService';
import './StockLossForm.css';

const StockLossForm = () => {
  const navigate = useNavigate();
  const [theme, setTheme] = useState('light');
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [locations, setLocations] = useState([]);
  const [variants, setVariants] = useState([]);
  const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedLocation, setSelectedLocation] = useState(null);
  const [selectedVariant, setSelectedVariant] = useState(null);
  
  const [formData, setFormData] = useState({
    location_id: '',
    variant_id: '',
    quantity: 1,
    loss_type: '',
    reason: '',
    notes: ''
  });

  const [confirmationInput, setConfirmationInput] = useState('');
  const requiredConfirmation = selectedVariant 
    ? `${selectedVariant.variant.sku} ANNULER` 
    : '';

  const lossTypes = [
    { value: 'breakage', label: 'Casse', icon: '💥', color: 'var(--danger)' },
    { value: 'theft', label: 'Vol', icon: '🚨', color: '#dc2626' },
    { value: 'expiry', label: 'Péremption', icon: '📅', color: 'var(--warning)' },
    { value: 'damage', label: 'Dommage', icon: '⚠️', color: '#f97316' },
    { value: 'inventory_shortage', label: 'Écart', icon: '📊', color: 'var(--primary)' },
    { value: 'other', label: 'Autre', icon: '📝', color: 'var(--text-secondary)' }
  ];

  // ============================================
  // INITIALISATION THÈME
  // ============================================
  useEffect(() => {
    const savedTheme = localStorage.getItem('stock-loss-theme');
    if (savedTheme) {
      setTheme(savedTheme);
      document.documentElement.setAttribute('data-theme', savedTheme);
    } else {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const initialTheme = prefersDark ? 'dark' : 'light';
      setTheme(initialTheme);
      document.documentElement.setAttribute('data-theme', initialTheme);
    }
    loadLocations();
  }, []);

  const toggleTheme = () => {
    const newTheme = theme === 'light' ? 'dark' : 'light';
    setTheme(newTheme);
    localStorage.setItem('stock-loss-theme', newTheme);
    document.documentElement.setAttribute('data-theme', newTheme);
  };

  // ============================================
  // CHARGEMENT DONNÉES
  // ============================================
  useEffect(() => {
    if (formData.location_id) {
      loadVariants(1);
    }
  }, [formData.location_id]);

  useEffect(() => {
    if (formData.location_id && searchTerm !== '') {
      const timer = setTimeout(() => loadVariants(1), 300);
      return () => clearTimeout(timer);
    }
  }, [searchTerm]);

  const loadLocations = async () => {
    try {
      const data = await locationService.getActive();
      setLocations(data);
    } catch (err) {
      setError('Impossible de charger les emplacements');
    }
  };

  const loadVariants = async (page) => {
    try {
      setLoading(true);
      const response = await productVariantLocationService.getByLocation(
        formData.location_id,
        { page, search: searchTerm, available_only: true, per_page: 20 }
      );
      setVariants(response.data);
      setPagination({
        current_page: response.current_page,
        last_page: response.last_page,
        total: response.total
      });
    } catch (err) {
      setError('Impossible de charger les produits');
    } finally {
      setLoading(false);
    }
  };

  // ============================================
  // GESTION QUANTITÉ AVEC SÉCURITÉ STRICTE
  // ============================================
  const handleQuantityChange = (e) => {
    if (!selectedVariant) return;

    const rawValue = e.target.value;
    const maxQuantity = selectedVariant.available_quantity;

    // Accepter uniquement les nombres
    if (rawValue === '' || rawValue === '-') {
      setFormData(prev => ({ ...prev, quantity: '' }));
      return;
    }

    let numValue = parseInt(rawValue);

    // Sécurité stricte
    if (isNaN(numValue) || numValue < 1) {
      numValue = 1;
      setError('La quantité minimum est 1');
      setTimeout(() => setError(null), 2000);
    } else if (numValue > maxQuantity) {
      numValue = maxQuantity;
      setError(`Quantité maximale: ${maxQuantity} unités`);
      setTimeout(() => setError(null), 2000);
    }

    setFormData(prev => ({ ...prev, quantity: numValue }));
  };

  const handleVariantSelect = (variantLocation) => {
    setSelectedVariant(variantLocation);
    setFormData(prev => ({ 
      ...prev, 
      variant_id: variantLocation.variant_id,
      quantity: 1 
    }));
  };

  const canProceedToConfirmation = () => {
    return formData.location_id && 
           formData.variant_id && 
           formData.quantity > 0 &&
           formData.quantity <= selectedVariant?.available_quantity &&
           formData.loss_type &&
           formData.reason.trim();
  };

  const handleSubmit = async () => {
    if (confirmationInput !== requiredConfirmation) {
      setError(`Vous devez saisir exactement : ${requiredConfirmation}`);
      return;
    }

    try {
      setLoading(true);
      await stockMovementService.declareLoss({
        variant_id: parseInt(formData.variant_id),
        location_id: parseInt(formData.location_id),
        quantity: formData.quantity,
        loss_type: formData.loss_type,
        reason: formData.reason,
        notes: formData.notes || null
      });

      navigate('/mouvements-stock', { 
        state: { 
          message: `Perte de ${formData.quantity} unité(s) déclarée`,
          type: 'success'
        }
      });
    } catch (err) {
      setError(err.response?.data?.message || 'Erreur lors de la déclaration');
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-MG', {
      style: 'currency',
      currency: 'MGA',
      minimumFractionDigits: 0
    }).format(amount);
  };

  return (
    <div className="slfa__page">
      <div className="slfa__container">
        {/* HEADER AVEC TOGGLE THÈME */}
        <div className="slfa__header">
          <div className="slfa__header-content">
            <h1 className="slfa__title">🚨 Déclaration de Perte</h1>
            <p className="slfa__subtitle">Action irréversible - Consommation FIFO automatique</p>
          </div>
          <div className="slfa__header-actions">
            <button 
              className="slfa__theme-toggle" 
              onClick={toggleTheme}
              aria-label="Toggle theme"
            >
              {theme === 'light' ? <Moon size={20} /> : <Sun size={20} />}
            </button>
            <button className="slfa__btn-cancel" onClick={() => navigate('/mouvements-stock')}>
              <X size={20} />
              <span>Annuler</span>
            </button>
          </div>
        </div>

        {/* PROGRESS BAR */}
        <div className="slfa__progress">
          <div className={`slfa__progress-step ${step >= 1 ? 'slfa__progress-step--active' : ''}`}>
            <div className="slfa__step-number">1</div>
            <span className="slfa__step-label">Sélection</span>
          </div>
          <div className="slfa__progress-line"></div>
          <div className={`slfa__progress-step ${step >= 2 ? 'slfa__progress-step--active' : ''}`}>
            <div className="slfa__step-number">2</div>
            <span className="slfa__step-label">Confirmation</span>
          </div>
        </div>

        {/* ERROR ALERT */}
        {error && (
          <div className="slfa__alert-error">
            <AlertCircle size={20} />
            <span>{error}</span>
            <button onClick={() => setError(null)} className="slfa__alert-close">
              <X size={16} />
            </button>
          </div>
        )}

        {/* CONTENT */}
        {step === 1 ? (
          <div className="slfa__step-content">
            {/* LOCATION SELECTOR */}
            <div className="slfa__form-section">
              <label className="slfa__label">
                Emplacement <span className="slfa__required">*</span>
              </label>
              <select
                className="slfa__select"
                value={formData.location_id}
                onChange={(e) => {
                  const loc = locations.find(l => l.id === parseInt(e.target.value));
                  setSelectedLocation(loc);
                  setFormData(prev => ({ ...prev, location_id: e.target.value, variant_id: '' }));
                  setSelectedVariant(null);
                }}
              >
                <option value="">Choisir un emplacement</option>
                {locations.map(loc => (
                  <option key={loc.id} value={loc.id}>
                    {loc.name} - {loc.warehouse}
                  </option>
                ))}
              </select>
            </div>

            {/* SEARCH + VARIANTS */}
            {formData.location_id && (
              <>
                <div className="slfa__form-section">
                  <label className="slfa__label">Rechercher un produit</label>
                  <div className="slfa__search-box">
                    <Search size={18} />
                    <input
                      type="text"
                      placeholder="Nom ou SKU..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="slfa__search-input"
                    />
                  </div>
                </div>

                {loading ? (
                  <div className="slfa__loading-state">
                    <div className="slfa__spinner"></div>
                    <p>Chargement...</p>
                  </div>
                ) : variants.length === 0 ? (
                  <div className="slfa__empty-state">
                    <Package size={48} />
                    <p>Aucun produit disponible</p>
                  </div>
                ) : (
                  <>
                    <div className="slfa__variants-grid">
                      {variants.map((vl) => (
                        <div
                          key={vl.id}
                          className={`slfa__variant-card ${selectedVariant?.id === vl.id ? 'slfa__variant-card--selected' : ''}`}
                          onClick={() => handleVariantSelect(vl)}
                        >
                          <div className="slfa__variant-image">
                            {vl.variant.product.image_url ? (
                              <img src={vl.variant.product.image_url} alt={vl.variant.product.name} />
                            ) : (
                              <Package size={32} />
                            )}
                          </div>
                          <div className="slfa__variant-info">
                            <h4 className="slfa__variant-name">{vl.variant.product.name}</h4>
                            <span className="slfa__variant-sku">{vl.variant.sku}</span>
                            <div className="slfa__variant-meta">
                              <span className="slfa__stock-badge">{vl.available_quantity} dispo</span>
                              <span className="slfa__price">{formatCurrency(vl.variant.product.base_price)}</span>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>

                    {pagination.last_page > 1 && (
                      <div className="slfa__pagination">
                        <button
                          className="slfa__pagination-btn"
                          disabled={pagination.current_page === 1}
                          onClick={() => loadVariants(pagination.current_page - 1)}
                        >
                          <ChevronLeft size={18} />
                        </button>
                        <span className="slfa__pagination-text">
                          Page {pagination.current_page} / {pagination.last_page}
                        </span>
                        <button
                          className="slfa__pagination-btn"
                          disabled={pagination.current_page === pagination.last_page}
                          onClick={() => loadVariants(pagination.current_page + 1)}
                        >
                          <ChevronRight size={18} />
                        </button>
                      </div>
                    )}
                  </>
                )}
              </>
            )}

            {/* FORM FIELDS */}
            {selectedVariant && (
              <>
                <div className="slfa__form-section">
                  <label className="slfa__label">
                    Type de perte <span className="slfa__required">*</span>
                  </label>
                  <div className="slfa__loss-types-grid">
                    {lossTypes.map(type => (
                      <button
                        key={type.value}
                        type="button"
                        className={`slfa__loss-type-btn ${formData.loss_type === type.value ? 'slfa__loss-type-btn--active' : ''}`}
                        style={{ '--type-color': type.color }}
                        onClick={() => setFormData(prev => ({ ...prev, loss_type: type.value }))}
                      >
                        <span className="slfa__type-icon">{type.icon}</span>
                        <span className="slfa__type-label">{type.label}</span>
                      </button>
                    ))}
                  </div>
                </div>

                <div className="slfa__form-section">
                  <label className="slfa__label">
                    Quantité <span className="slfa__required">*</span>
                  </label>
                  <input
                    type="number"
                    min="1"
                    max={selectedVariant.available_quantity}
                    value={formData.quantity}
                    onChange={handleQuantityChange}
                    onBlur={(e) => {
                      if (e.target.value === '' || parseInt(e.target.value) < 1) {
                        setFormData(prev => ({ ...prev, quantity: 1 }));
                      }
                    }}
                    className="slfa__input-number"
                  />
                  <span className="slfa__help-text">
                    Maximum disponible: {selectedVariant.available_quantity} unités
                  </span>
                </div>

                <div className="slfa__form-section">
                  <label className="slfa__label">
                    Raison détaillée <span className="slfa__required">*</span>
                  </label>
                  <textarea
                    rows="3"
                    value={formData.reason}
                    onChange={(e) => setFormData(prev => ({ ...prev, reason: e.target.value }))}
                    placeholder="Décrivez les circonstances..."
                    className="slfa__textarea"
                  />
                </div>

                <div className="slfa__form-section">
                  <label className="slfa__label">Notes complémentaires</label>
                  <textarea
                    rows="2"
                    value={formData.notes}
                    onChange={(e) => setFormData(prev => ({ ...prev, notes: e.target.value }))}
                    placeholder="Actions correctives, mesures prises..."
                    className="slfa__textarea"
                  />
                </div>

                <button
                  className="slfa__btn-primary-large"
                  disabled={!canProceedToConfirmation()}
                  onClick={() => setStep(2)}
                >
                  Continuer vers la confirmation
                </button>
              </>
            )}
          </div>
        ) : (
          <div className="slfa__step-content">
            <div className="slfa__confirmation-alert">
              <Shield size={48} />
              <h3 className="slfa__confirmation-title">Confirmation de sécurité</h3>
              <p className="slfa__confirmation-text">
                Cette action est irréversible et consommera automatiquement les batches en FIFO
              </p>
            </div>

            <div className="slfa__summary-card">
              <h3 className="slfa__summary-title">Résumé de la déclaration</h3>
              <div className="slfa__summary-grid">
                <div className="slfa__summary-item">
                  <span>Produit</span>
                  <strong>{selectedVariant.variant.product.name}</strong>
                </div>
                <div className="slfa__summary-item">
                  <span>SKU</span>
                  <strong>{selectedVariant.variant.sku}</strong>
                </div>
                <div className="slfa__summary-item">
                  <span>Emplacement</span>
                  <strong>{selectedLocation.name}</strong>
                </div>
                <div className="slfa__summary-item">
                  <span>Type</span>
                  <strong>{lossTypes.find(t => t.value === formData.loss_type)?.label}</strong>
                </div>
                <div className="slfa__summary-item slfa__summary-item--danger">
                  <span>Quantité perdue</span>
                  <strong>{formData.quantity} unités</strong>
                </div>
                <div className="slfa__summary-item">
                  <span>Stock après</span>
                  <strong>{selectedVariant.available_quantity - formData.quantity} unités</strong>
                </div>
              </div>
            </div>

            <div className="slfa__confirmation-input-section">
              <label className="slfa__confirmation-label">
                Pour confirmer, saisissez exactement : 
                <code className="slfa__confirmation-code">{requiredConfirmation}</code>
              </label>
              <input
                type="text"
                value={confirmationInput}
                onChange={(e) => setConfirmationInput(e.target.value)}
                placeholder="Saisissez ici..."
                className="slfa__confirmation-input"
                autoFocus
              />
            </div>

            <div className="slfa__action-buttons">
              <button className="slfa__btn-secondary-large" onClick={() => setStep(1)}>
                Retour
              </button>
              <button
                className="slfa__btn-danger-large"
                disabled={loading || confirmationInput !== requiredConfirmation}
                onClick={handleSubmit}
              >
                {loading ? (
                  <>
                    <div className="slfa__spinner"></div>
                    Traitement...
                  </>
                ) : (
                  <>
                    <Save size={20} />
                    Confirmer la perte
                  </>
                )}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default StockLossForm;