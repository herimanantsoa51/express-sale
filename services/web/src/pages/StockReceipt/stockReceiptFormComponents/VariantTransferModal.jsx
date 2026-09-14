import React, { useState, useEffect } from 'react';
import { X, ArrowRight, AlertTriangle, Check, Package, Search, Loader2 } from 'lucide-react';
import productService from '../../../services/productService';
import './VariantTransferModal.css';

const VariantTransferModal = ({ isOpen, onClose, sourceItem, receiptId, onTransferSuccess }) => {
  const [step, setStep] = useState(1);
  const [availableVariants, setAvailableVariants] = useState([]);
  const [filteredVariants, setFilteredVariants] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedVariant, setSelectedVariant] = useState(null);
  const [quantity, setQuantity] = useState(1);
  const [loading, setLoading] = useState(false);
  const [transferring, setTransferring] = useState(false);
  const [error, setError] = useState(null);

  // Extraire le variant et la quantité reçue de l'item
  const sourceVariant = sourceItem?.variant;
  const maxQuantity = sourceItem?.quantity_received || 0;

  useEffect(() => {
    if (isOpen && sourceVariant) {
      fetchVariants();
    }
  }, [isOpen, sourceVariant]);

  useEffect(() => {
    if (!searchTerm.trim()) {
      setFilteredVariants(availableVariants);
    } else {
      const term = searchTerm.toLowerCase();
      const filtered = availableVariants.filter(v => 
        v.sku.toLowerCase().includes(term) ||
        v.attribute_values?.some(attr => 
          attr.value.toLowerCase().includes(term)
        )
      );
      setFilteredVariants(filtered);
    }
  }, [searchTerm, availableVariants]);

  const fetchVariants = async () => {
    try {
      setLoading(true);
      setError(null);
      const productId = sourceVariant.product?.id || sourceVariant.product_id;
      const response = await productService.getVariants(productId);
      
      const variants = (response.data || []).filter(v => v.id !== sourceVariant.id);
      setAvailableVariants(variants);
      setFilteredVariants(variants);
    } catch (err) {
      console.error('Error fetching variants:', err);
      setError('Impossible de charger les variants');
    } finally {
      setLoading(false);
    }
  };

  const handleVariantSelect = (variant) => {
    setSelectedVariant(variant);
    setStep(2);
  };

  const handleQuantitySubmit = () => {
    if (quantity <= 0 || quantity > maxQuantity) {
      alert(`La quantité doit être entre 1 et ${maxQuantity}`);
      return;
    }
    setStep(3);
  };

  const handleConfirmTransfer = () => {
    onTransferSuccess({
      from_variant_id: sourceVariant.id,
      to_variant_id: selectedVariant.id,
      quantity: quantity
    });
  };

  const handleBack = () => {
    if (step === 2) {
      setStep(1);
      setSelectedVariant(null);
    } else if (step === 3) {
      setStep(2);
    }
  };

  const handleCloseModal = () => {
    setStep(1);
    setSelectedVariant(null);
    setQuantity(1);
    setSearchTerm('');
    setError(null);
    onClose();
  };

  const getVariantImage = (variant) => {
    return variant.image_path || variant.product?.image_url || null;
  };

  const getColorSwatch = (value) => {
    if (value?.startsWith('#')) {
      return (
        <span 
          className="vtm-color-swatch"
          style={{ backgroundColor: value }}
          title={value}
        />
      );
    }
    return null;
  };

  if (!isOpen) return null;

  return (
    <div className="vtm-overlay" onClick={handleCloseModal}>
      <div className="vtm-container" onClick={e => e.stopPropagation()}>
        {/* Header */}
        <div className="vtm-header">
          <div className="vtm-header-content">
            <div className="vtm-header-icon">
              <Package size={24} strokeWidth={2} />
            </div>
            <div className="vtm-header-text">
              <h3 className="vtm-title">Transférer vers un autre variant</h3>
              <p className="vtm-subtitle">
                {step === 1 && 'Sélectionnez le variant de destination'}
                {step === 2 && 'Indiquez la quantité à transférer'}
                {step === 3 && 'Vérifiez et confirmez le transfert'}
              </p>
            </div>
          </div>
          <button className="vtm-close" onClick={handleCloseModal}>
            <X size={20} strokeWidth={2} />
          </button>
        </div>

        {/* Progress Steps */}
        <div className="vtm-steps">
          <div className={`vtm-step ${step >= 1 ? 'active' : ''} ${step > 1 ? 'completed' : ''}`}>
            <div className="vtm-step-number">
              {step > 1 ? <Check size={16} strokeWidth={3} /> : '1'}
            </div>
            <span>Variant</span>
          </div>
          <div className="vtm-step-line" />
          <div className={`vtm-step ${step >= 2 ? 'active' : ''} ${step > 2 ? 'completed' : ''}`}>
            <div className="vtm-step-number">
              {step > 2 ? <Check size={16} strokeWidth={3} /> : '2'}
            </div>
            <span>Quantité</span>
          </div>
          <div className="vtm-step-line" />
          <div className={`vtm-step ${step >= 3 ? 'active' : ''}`}>
            <div className="vtm-step-number">3</div>
            <span>Confirmation</span>
          </div>
        </div>

        {/* Body */}
        <div className="vtm-body">
          {/* Source Variant Info */}
          <div className="vtm-source-info">
            <span className="vtm-source-label">Variant source</span>
            <div className="vtm-source-variant">
              {getVariantImage(sourceVariant) && (
                <img src={getVariantImage(sourceVariant)} alt={sourceVariant.sku} />
              )}
              <div className="vtm-source-details">
                <span className="vtm-source-sku">{sourceVariant.sku}</span>
                <div className="vtm-source-attrs">
                  {sourceVariant.attribute_values?.map((attr, idx) => (
                    <span key={idx} className="vtm-attr-tag">
                      {getColorSwatch(attr.value)}
                      {attr.attribute_type?.display_name || attr.attribute_type?.name}: {attr.value}
                    </span>
                  ))}
                </div>
              </div>
              <div className="vtm-source-qty">
                <span className="vtm-qty-label">Reçu</span>
                <span className="vtm-qty-value">{maxQuantity}</span>
              </div>
            </div>
          </div>

          {error && (
            <div className="vtm-error">
              <AlertTriangle size={18} strokeWidth={2} />
              <span>{error}</span>
            </div>
          )}

          {/* Step 1: Variant Selection */}
          {step === 1 && (
            <div className="vtm-step-content">
              <div className="vtm-search-box">
                <Search size={18} strokeWidth={2} />
                <input
                  type="text"
                  placeholder="Rechercher par SKU ou attributs..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="vtm-search-input"
                />
              </div>

              {loading ? (
                <div className="vtm-loading">
                  <Loader2 className="vtm-spinner" size={32} strokeWidth={2} />
                  <p>Chargement des variants...</p>
                </div>
              ) : filteredVariants.length === 0 ? (
                <div className="vtm-empty">
                  <Package size={48} strokeWidth={1.5} />
                  <p>Aucun variant disponible</p>
                  <button
                    className="vtm-btn vtm-btn-create-variant"
                    onClick={() => {
                      const productId = sourceVariant.product?.id || sourceVariant.product_id;
                      const currentUrl = window.location.pathname;
                      window.open(`/produits/${productId}/variante/nouvelle?returnTo=${encodeURIComponent(currentUrl)}&openTransferModal=true`, '_blank');
                    }}
                  >
                    <Package size={18} strokeWidth={2} />
                    Créer un nouveau variant
                  </button>
                </div>
              ) : (
                <>
                  <div className="vtm-variants-list">
                    {filteredVariants.map(variant => {
                      const image = getVariantImage(variant);
                      return (
                        <div
                          key={variant.id}
                          className="vtm-variant-card"
                          onClick={() => handleVariantSelect(variant)}
                        >
                          <div className="vtm-variant-image">
                            {image ? (
                              <img src={image} alt={variant.sku} />
                            ) : (
                              <div className="vtm-variant-no-image">
                                <Package size={24} strokeWidth={1.5} />
                              </div>
                            )}
                          </div>
                          <div className="vtm-variant-info">
                            <span className="vtm-variant-sku">{variant.sku}</span>
                            <div className="vtm-variant-attrs">
                              {variant.attribute_values?.map((attr, idx) => (
                                <span key={idx} className="vtm-attr-tag">
                                  {getColorSwatch(attr.value)}
                                  {attr.value}
                                </span>
                              ))}
                            </div>
                            <span className="vtm-variant-stock">
                              Stock: {variant.stock_quantity || 0}
                            </span>
                          </div>
                          <ArrowRight size={20} strokeWidth={2} className="vtm-variant-arrow" />
                        </div>
                      );
                    })}
                  </div>
                  
                  <div className="vtm-create-variant-section">
                    <div className="vtm-divider">
                      <span>ou</span>
                    </div>
                    <button
                      className="vtm-btn vtm-btn-create-variant"
                      onClick={() => {
                        const productId = sourceVariant.product?.id || sourceVariant.product_id;
                        const currentUrl = window.location.pathname;
                        window.open(`/produits/${productId}/variante/nouvelle?returnTo=${encodeURIComponent(currentUrl)}&openTransferModal=true`, '_blank');
                      }}
                    >
                      <Package size={18} strokeWidth={2} />
                      Créer un nouveau variant pour ce produit
                    </button>
                    <p className="vtm-create-hint">
                      Le modal de transfert s'ouvrira automatiquement après la création
                    </p>
                  </div>
                </>
              )}
            </div>
          )}

          {/* Step 2: Quantity */}
          {step === 2 && selectedVariant && (
            <div className="vtm-step-content">
              <div className="vtm-transfer-preview">
                <div className="vtm-transfer-from">
                  <span className="vtm-transfer-label">De</span>
                  <div className="vtm-transfer-variant">
                    {getVariantImage(sourceVariant) && (
                      <img src={getVariantImage(sourceVariant)} alt={sourceVariant.sku} />
                    )}
                    <div>
                      <span className="vtm-transfer-sku">{sourceVariant.sku}</span>
                      <span className="vtm-transfer-qty">{maxQuantity} reçus</span>
                    </div>
                  </div>
                </div>

                <div className="vtm-transfer-arrow">
                  <ArrowRight size={24} strokeWidth={2} />
                </div>

                <div className="vtm-transfer-to">
                  <span className="vtm-transfer-label">Vers</span>
                  <div className="vtm-transfer-variant">
                    {getVariantImage(selectedVariant) && (
                      <img src={getVariantImage(selectedVariant)} alt={selectedVariant.sku} />
                    )}
                    <div>
                      <span className="vtm-transfer-sku">{selectedVariant.sku}</span>
                      <div className="vtm-variant-attrs">
                        {selectedVariant.attribute_values?.map((attr, idx) => (
                          <span key={idx} className="vtm-attr-tag-small">
                            {getColorSwatch(attr.value)}
                            {attr.value}
                          </span>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="vtm-quantity-input">
                <label className="vtm-input-label">
                  Quantité à transférer
                  <span className="vtm-input-hint">Max: {maxQuantity}</span>
                </label>
                <input
                  type="number"
                  min="1"
                  max={maxQuantity}
                  value={quantity}
                  onChange={(e) => {
                    const val = parseInt(e.target.value) || 1;
                    setQuantity(Math.min(val, maxQuantity));
                  }}
                  className="vtm-input"
                  autoFocus
                />
                <div className="vtm-quantity-slider">
                  <input
                    type="range"
                    min="1"
                    max={maxQuantity}
                    value={quantity}
                    onChange={(e) => setQuantity(parseInt(e.target.value))}
                    className="vtm-slider"
                  />
                  <div className="vtm-slider-marks">
                    <span>1</span>
                    <span>{Math.floor(maxQuantity / 2)}</span>
                    <span>{maxQuantity}</span>
                  </div>
                </div>
              </div>

              <div className="vtm-impact-info">
                <div className="vtm-impact-item">
                  <span className="vtm-impact-label">Restant dans source</span>
                  <span className="vtm-impact-value">{maxQuantity - quantity}</span>
                </div>
                <div className="vtm-impact-item">
                  <span className="vtm-impact-label">Ajouté à destination</span>
                  <span className="vtm-impact-value success">+{quantity}</span>
                </div>
              </div>
            </div>
          )}

          {/* Step 3: Confirmation */}
          {step === 3 && selectedVariant && (
            <div className="vtm-step-content">
              <div className="vtm-confirmation-warning">
                <AlertTriangle size={32} strokeWidth={2} />
                <h4>Confirmer le transfert</h4>
                <p>
                  Vous êtes sur le point de transférer <strong>{quantity} unité{quantity > 1 ? 's' : ''}</strong> du variant source vers le variant de destination.
                </p>
              </div>

              <div className="vtm-confirmation-summary">
                <div className="vtm-summary-row">
                  <span className="vtm-summary-label">Variant source</span>
                  <span className="vtm-summary-value">{sourceVariant.sku}</span>
                </div>
                <div className="vtm-summary-row">
                  <span className="vtm-summary-label">Variant destination</span>
                  <span className="vtm-summary-value">{selectedVariant.sku}</span>
                </div>
                <div className="vtm-summary-row highlighted">
                  <span className="vtm-summary-label">Quantité transférée</span>
                  <span className="vtm-summary-value">{quantity}</span>
                </div>
              </div>

              <div className="vtm-confirmation-note">
                <AlertTriangle size={18} strokeWidth={2} />
                <p><strong>Attention :</strong> Cette action est irréversible. Le transfert sera enregistré de manière permanente.</p>
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="vtm-footer">
          <button 
            className="vtm-btn vtm-btn-secondary" 
            onClick={step > 1 ? handleBack : handleCloseModal}
            disabled={transferring}
          >
            {step > 1 ? 'Retour' : 'Annuler'}
          </button>
          <button 
            className="vtm-btn vtm-btn-primary" 
            onClick={step === 2 ? handleQuantitySubmit : handleConfirmTransfer}
            disabled={transferring || (step === 1 && !selectedVariant)}
          >
            {transferring ? (
              <>
                <Loader2 className="vtm-spinner" size={16} strokeWidth={2.5} />
                Transfert...
              </>
            ) : (
              <>
                {step === 3 ? (
                  <>
                    <Check size={16} strokeWidth={2.5} />
                    Confirmer le transfert
                  </>
                ) : (
                  <>
                    Continuer
                    <ArrowRight size={16} strokeWidth={2.5} />
                  </>
                )}
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

export default VariantTransferModal;