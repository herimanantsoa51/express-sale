import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'motion/react';
import {
  ArrowLeft, Package, DollarSign, TrendingUp, Percent, Edit2,
  Save, RefreshCw, AlertCircle, CheckCircle, X, Info, 
  AlertTriangle, Truck, Tag, Calculator, ShoppingCart, Layers
} from 'lucide-react';
import stockReceiptService from '../../services/stockReceiptService';
import productService from '../../services/productService';
import '../../styles/CostAllocation.css';

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(amount);
};

// === CONFIRMATION MODAL ===
const ConfirmationModal = ({ isOpen, onClose, onConfirm, loading }) => {
  if (!isOpen) return null;

  return (
    <motion.div
      className="ca-modal-overlay"
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      onClick={onClose}
    >
      <motion.div
        className="ca-modal-content"
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 20 }}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="ca-modal-header warning">
          <AlertTriangle size={28} />
          <h2>Attention : Dernière chance</h2>
        </div>

        <div className="ca-modal-body">
          <p className="ca-modal-warning-text">
            Avez-vous terminé tous vos paiements pour cette réception ?
          </p>
          <div className="ca-modal-info-box">
            <Info size={18} />
            <div>
              <strong>Important :</strong> Une fois la répartition des coûts effectuée, 
              vous ne pourrez plus ajouter de paiements. Seule la modification des coûts 
              déjà répartis sera possible.
            </div>
          </div>
          <p className="ca-modal-question">
            Êtes-vous sûr de vouloir continuer ?
          </p>
        </div>

        <div className="ca-modal-footer">
          <button 
            className="ca-btn ca-btn-secondary" 
            onClick={onClose}
            disabled={loading}
          >
            <X size={18} />
            Non, revenir en arrière
          </button>
          <button 
            className="ca-btn ca-btn-danger" 
            onClick={onConfirm}
            disabled={loading}
          >
            {loading ? (
              <>
                <RefreshCw size={18} className="ca-loading-spinner" />
                Chargement...
              </>
            ) : (
              <>
                <CheckCircle size={18} />
                Oui, continuer
              </>
            )}
          </button>
        </div>
      </motion.div>
    </motion.div>
  );
};

// === PRODUCT COST CARD ===
const ProductCostCard = ({ product, onUpdate }) => {
  const [isEditing, setIsEditing] = useState(false);
  const [freightCost, setFreightCost] = useState(product.recommended_freight_cost_per_unit);
  const [otherCosts, setOtherCosts] = useState(product.recommended_other_costs_per_unit);

  const totalCost = parseFloat(product.supplier_unit_cost) + parseFloat(freightCost) + parseFloat(otherCosts);
  const totalQuantity = product.total_quantity;
  const totalFreight = freightCost * totalQuantity;
  const totalOther = otherCosts * totalQuantity;

  const handleSave = () => {
    onUpdate(product.product_id, {
      freight_cost_per_unit: parseFloat(freightCost),
      other_costs_per_unit: parseFloat(otherCosts)
    });
    setIsEditing(false);
  };

  const handleCancel = () => {
    setFreightCost(product.recommended_freight_cost_per_unit);
    setOtherCosts(product.recommended_other_costs_per_unit);
    setIsEditing(false);
  };

  return (
    <motion.div
      className="ca-product-card"
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      layout
    >
      <div className="ca-product-header">
        <div className="ca-product-title">
          <Package size={20} />
          <h3>{product.product_name}</h3>
        </div>
        <div className="ca-product-meta">
          <span className="ca-badge info">
            <Layers size={14} />
            {product.variants_count} variant{product.variants_count > 1 ? 's' : ''}
          </span>
          <span className="ca-badge primary">
            {totalQuantity} unités
          </span>
        </div>
      </div>

      <div className="ca-cost-breakdown">
        <div className="ca-cost-row base">
          <span className="ca-cost-label">Prix fournisseur unitaire</span>
          <span className="ca-cost-value">{formatCurrency(product.supplier_unit_cost)} Ar</span>
        </div>

        <div className="ca-cost-row freight">
          <span className="ca-cost-label">
            <Truck size={16} />
            Coût transport unitaire
          </span>
          {isEditing ? (
            <input
              type="number"
              className="ca-cost-input"
              value={freightCost}
              onChange={(e) => setFreightCost(e.target.value)}
              step="0.01"
              min="0"
            />
          ) : (
            <span className="ca-cost-value">{formatCurrency(freightCost)} Ar</span>
          )}
        </div>

        <div className="ca-cost-row other">
          <span className="ca-cost-label">
            <Tag size={16} />
            Autres coûts unitaires
          </span>
          {isEditing ? (
            <input
              type="number"
              className="ca-cost-input"
              value={otherCosts}
              onChange={(e) => setOtherCosts(e.target.value)}
              step="0.01"
              min="0"
            />
          ) : (
            <span className="ca-cost-value">{formatCurrency(otherCosts)} Ar</span>
          )}
        </div>

        <div className="ca-cost-divider" />

        <div className="ca-cost-row total">
          <span className="ca-cost-label">Coût total unitaire</span>
          <span className="ca-cost-value highlight">{formatCurrency(totalCost)} Ar</span>
        </div>
      </div>

      <div className="ca-totals-grid">
        <div className="ca-total-box">
          <span className="ca-total-label">Transport total</span>
          <span className="ca-total-value freight">{formatCurrency(totalFreight)} Ar</span>
        </div>
        <div className="ca-total-box">
          <span className="ca-total-label">Autres coûts total</span>
          <span className="ca-total-value other">{formatCurrency(totalOther)} Ar</span>
        </div>
      </div>

      <div className="ca-variants-preview">
        <h4>Variantes concernées</h4>
        <div className="ca-variants-list">
          {product.variants.map((variant, idx) => (
            <div key={idx} className="ca-variant-item">
              <div className="ca-variant-attrs">
                {Object.entries(variant.variant_attributes).map(([key, value]) => (
                  <span key={key} className="ca-variant-attr">
                    {key}: {value}
                  </span>
                ))}
              </div>
              <span className="ca-variant-qty">{variant.quantity}x</span>
            </div>
          ))}
        </div>
      </div>

      <div className="ca-product-actions">
        {isEditing ? (
          <>
            <button className="ca-btn ca-btn-sm ca-btn-secondary" onClick={handleCancel}>
              <X size={16} />
              Annuler
            </button>
            <button className="ca-btn ca-btn-sm ca-btn-primary" onClick={handleSave}>
              <Save size={16} />
              Enregistrer
            </button>
          </>
        ) : (
          <button className="ca-btn ca-btn-sm ca-btn-outline" onClick={() => setIsEditing(true)}>
            <Edit2 size={16} />
            Modifier
          </button>
        )}
      </div>
    </motion.div>
  );
};

// === PRICING SECTION ===
const PricingSection = ({ products, onUpdatePrices }) => {
  const [prices, setPrices] = useState({});
  const [margins, setMargins] = useState({});

  useEffect(() => {
    const initialPrices = {};
    const initialMargins = {};
    products.forEach(p => {
      initialPrices[p.product_id] = p.recommended_total_unit_cost * 1.3; // 30% par défaut
      initialMargins[p.product_id] = 30;
    });
    setPrices(initialPrices);
    setMargins(initialMargins);
  }, [products]);

  const handleMarginChange = (productId, margin, cost) => {
    const newMargin = parseFloat(margin) || 0;
    const newPrice = cost * (1 + newMargin / 100);
    setMargins(prev => ({ ...prev, [productId]: newMargin }));
    setPrices(prev => ({ ...prev, [productId]: newPrice }));
  };

  const handlePriceChange = (productId, price, cost) => {
    const newPrice = parseFloat(price) || 0;
    const newMargin = ((newPrice - cost) / cost) * 100;
    setPrices(prev => ({ ...prev, [productId]: newPrice }));
    setMargins(prev => ({ ...prev, [productId]: newMargin }));
  };

  const handleSave = () => {
    const data = {
      products: products.map(p => ({
        id: p.product_id,
        base_price: prices[p.product_id]
      }))
    };
    onUpdatePrices(data);
  };

  return (
    <div className="ca-pricing-section">
      <div className="ca-section-header">
        <div>
          <h2>
            <ShoppingCart size={24} />
            Prix de vente
          </h2>
          <p>Définissez les prix de vente pour chaque produit</p>
        </div>
        <button className="ca-btn ca-btn-success" onClick={handleSave}>
          <Save size={18} />
          Enregistrer les prix
        </button>
      </div>

      <div className="ca-pricing-grid">
        {products.map(product => {
          const cost = product.recommended_total_unit_cost;
          const price = prices[product.product_id] || cost;
          const margin = margins[product.product_id] || 0;
          const profit = price - cost;

          return (
            <div key={product.product_id} className="ca-pricing-card">
              <div className="ca-pricing-header">
                <h4>{product.product_name}</h4>
                <span className="ca-pricing-cost">Coût: {formatCurrency(cost)} Ar</span>
              </div>

              <div className="ca-pricing-inputs">
                <div className="ca-pricing-input-group">
                  <label>
                    <Percent size={16} />
                    Marge (%)
                  </label>
                  <input
                    type="number"
                    className="ca-pricing-input"
                    value={margin.toFixed(2)}
                    onChange={(e) => handleMarginChange(product.product_id, e.target.value, cost)}
                    step="0.1"
                  />
                </div>

                <div className="ca-pricing-input-group">
                  <label>
                    <DollarSign size={16} />
                    Prix de vente (Ar)
                  </label>
                  <input
                    type="number"
                    className="ca-pricing-input"
                    value={price.toFixed(2)}
                    onChange={(e) => handlePriceChange(product.product_id, e.target.value, cost)}
                    step="0.01"
                    min={cost}
                  />
                </div>
              </div>

              <div className="ca-pricing-stats">
                <div className="ca-pricing-stat">
                  <span className="ca-stat-label">Profit unitaire</span>
                  <span className={`ca-stat-value ${profit >= 0 ? 'positive' : 'negative'}`}>
                    {formatCurrency(profit)} Ar
                  </span>
                </div>
                <div className="ca-pricing-stat">
                  <span className="ca-stat-label">Marge</span>
                  <span className={`ca-stat-value ${margin >= 0 ? 'positive' : 'negative'}`}>
                    {margin.toFixed(2)}%
                  </span>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

// === MAIN COMPONENT ===
const CostAllocation = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [receipt, setReceipt] = useState(null);
  const [recommendations, setRecommendations] = useState(null);
  const [method, setMethod] = useState('value');
  const [alert, setAlert] = useState(null);
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [isUpdateMode, setIsUpdateMode] = useState(false);
  const [allocations, setAllocations] = useState({});

  useEffect(() => {
    checkReceiptStatus();
  }, [id]);

  const checkReceiptStatus = async () => {
    try {
      setLoading(true);
      const response = await stockReceiptService.getById(id);
      const receiptData = response.data;
      setReceipt(receiptData);

      // Vérifier si déjà alloué (mode modification)
      if (receiptData.status === 'cost_allocated') {
        setIsUpdateMode(true);
        loadRecommendations();
      } else {
        // Mode création : afficher le modal de confirmation
        setShowConfirmModal(true);
      }
    } catch (err) {
      console.error('Erreur:', err);
      setAlert({ type: 'error', message: 'Impossible de charger la réception' });
    } finally {
      setLoading(false);
    }
  };

  const loadRecommendations = async () => {
    try {
      setLoading(true);
      const response = await stockReceiptService.getCostRecommendations(id, method);
      setRecommendations(response.data);

      // Initialiser les allocations avec les recommandations
      const initialAllocations = {};
      response.data.recommendations.forEach(rec => {
        initialAllocations[rec.product_id] = {
          freight_cost_per_unit: rec.recommended_freight_cost_per_unit,
          other_costs_per_unit: rec.recommended_other_costs_per_unit
        };
      });
      setAllocations(initialAllocations);
    } catch (err) {
      console.error('Erreur:', err);
      setAlert({ type: 'error', message: 'Impossible de charger les recommandations' });
    } finally {
      setLoading(false);
    }
  };

  const handleConfirmStart = () => {
    setShowConfirmModal(false);
    loadRecommendations();
  };

  const handleMethodChange = (newMethod) => {
    setMethod(newMethod);
    loadRecommendations();
  };

  const handleUpdateAllocation = (productId, newValues) => {
    setAllocations(prev => ({
      ...prev,
      [productId]: newValues
    }));
  };

  const handleApplyCosts = async () => {
    try {
      setSubmitting(true);
      setAlert(null);

      const data = {
        allocations: Object.entries(allocations).map(([productId, values]) => ({
          product_id: parseInt(productId),
          freight_cost_per_unit: values.freight_cost_per_unit,
          other_costs_per_unit: values.other_costs_per_unit
        }))
      };

      await stockReceiptService.applyCosts(id, data);

      setAlert({ 
        type: 'success', 
        message: 'Coûts appliqués avec succès ! Vous pouvez maintenant définir les prix de vente.' 
      });

      // Recharger les données
      await checkReceiptStatus();

    } catch (err) {
      console.error('Erreur:', err);
      setAlert({ 
        type: 'error', 
        message: err.response?.data?.message || 'Erreur lors de l\'application des coûts' 
      });
    } finally {
      setSubmitting(false);
    }
  };

  const handleUpdatePrices = async (data) => {
    try {
      setSubmitting(true);
      await productService.updateBasePrices(data);
      setAlert({ type: 'success', message: 'Prix de vente mis à jour avec succès !' });
      
      setTimeout(() => {
        navigate(`/reapprovisionnements/${id}`);
      }, 1500);
    } catch (err) {
      console.error('Erreur:', err);
      setAlert({ type: 'error', message: 'Erreur lors de la mise à jour des prix' });
    } finally {
      setSubmitting(false);
    }
  };

  if (loading && !recommendations) {
    return (
      <div className="ca-loading-screen">
        <RefreshCw className="ca-loading-spinner" size={48} />
        <p>Chargement...</p>
      </div>
    );
  }

  return (
    <div className="ca-page">
      {/* Back button */}
      <motion.button
        className="ca-back-btn"
        onClick={() => navigate(`/reapprovisionnements/${id}`)}
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        whileHover={{ x: -4 }}
      >
        <ArrowLeft size={18} />
        Retour à la réception
      </motion.button>

      {/* Header */}
      <motion.div
        className="ca-header"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="ca-header-icon">
          <Calculator size={32} />
        </div>
        <div>
          <h1>Répartition des coûts</h1>
          <p>Réception {receipt?.receipt_number}</p>
        </div>
        {isUpdateMode && (
          <span className="ca-badge warning">
            <Edit2 size={14} />
            Mode modification
          </span>
        )}
      </motion.div>

      {/* Alert */}
      <AnimatePresence>
        {alert && (
          <motion.div
            className={`ca-alert ${alert.type}`}
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
          >
            {alert.type === 'error' ? <AlertCircle size={18} /> : <CheckCircle size={18} />}
            <span>{alert.message}</span>
            <button className="ca-alert-close" onClick={() => setAlert(null)}>
              <X size={16} />
            </button>
          </motion.div>
        )}
      </AnimatePresence>

      {recommendations && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
        >
          {/* Summary */}
          <div className="ca-summary">
            <div className="ca-summary-card">
              <Truck size={24} className="ca-summary-icon freight" />
              <div>
                <span className="ca-summary-label">Transport total</span>
                <span className="ca-summary-value">
                  {formatCurrency(recommendations.total_expenses.freight_costs)} Ar
                </span>
              </div>
            </div>
            <div className="ca-summary-card">
              <Tag size={24} className="ca-summary-icon other" />
              <div>
                <span className="ca-summary-label">Autres coûts</span>
                <span className="ca-summary-value">
                  {formatCurrency(recommendations.total_expenses.other_costs)} Ar
                </span>
              </div>
            </div>
            <div className="ca-summary-card highlight">
              <DollarSign size={24} className="ca-summary-icon total" />
              <div>
                <span className="ca-summary-label">Total à répartir</span>
                <span className="ca-summary-value">
                  {formatCurrency(recommendations.total_expenses.total_to_allocate)} Ar
                </span>
              </div>
            </div>
          </div>

          {/* Method selector */}
          <div className="ca-method-selector">
            <h3>Méthode de répartition</h3>
            <div className="ca-method-buttons">
              {[
                { key: 'value', label: 'Par valeur', icon: DollarSign },
                { key: 'quantity', label: 'Par quantité', icon: Layers },
                { key: 'weight', label: 'Par poids', icon: Package }
              ].map(m => {
                const Icon = m.icon;
                return (
                  <button
                    key={m.key}
                    className={`ca-method-btn ${method === m.key ? 'active' : ''}`}
                    onClick={() => handleMethodChange(m.key)}
                  >
                    <Icon size={18} />
                    {m.label}
                  </button>
                );
              })}
            </div>
          </div>

          {/* Products */}
          <div className="ca-products-section">
            <h2>Coûts par produit</h2>
            <div className="ca-products-grid">
              {recommendations.recommendations.map(product => (
                <ProductCostCard
                  key={product.product_id}
                  product={{
                    ...product,
                    recommended_freight_cost_per_unit: allocations[product.product_id]?.freight_cost_per_unit || product.recommended_freight_cost_per_unit,
                    recommended_other_costs_per_unit: allocations[product.product_id]?.other_costs_per_unit || product.recommended_other_costs_per_unit
                  }}
                  onUpdate={handleUpdateAllocation}
                />
              ))}
            </div>
          </div>

          {/* Apply button */}
          <div className="ca-actions">
            <button 
              className="ca-btn ca-btn-lg ca-btn-success"
              onClick={handleApplyCosts}
              disabled={submitting}
            >
              {submitting ? (
                <>
                  <RefreshCw size={20} className="ca-loading-spinner" />
                  Application en cours...
                </>
              ) : (
                <>
                  <CheckCircle size={20} />
                  {isUpdateMode ? 'Mettre à jour les coûts' : 'Appliquer les coûts'}
                </>
              )}
            </button>
          </div>

          {/* Pricing section */}
          {isUpdateMode && (
            <PricingSection
              products={recommendations.recommendations}
              onUpdatePrices={handleUpdatePrices}
            />
          )}
        </motion.div>
      )}

      {/* Confirmation Modal */}
      <AnimatePresence>
        {showConfirmModal && (
          <ConfirmationModal
            isOpen={showConfirmModal}
            onClose={() => navigate(`/reapprovisionnements/${id}`)}
            onConfirm={handleConfirmStart}
            loading={loading}
          />
        )}
      </AnimatePresence>
    </div>
  );
};

export default CostAllocation;