import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'motion/react';
import {
  ArrowLeft, DollarSign, AlertCircle, CheckCircle, X, Info, 
  AlertTriangle, Truck, Tag, Calculator, ShoppingCart, 
  TrendingUp, Package, Layers, Percent
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
                <div className="ca-loading-spinner" />
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

// === PRICE UPDATE MODAL ===
const PriceUpdateModal = ({ isOpen, onClose, onConfirm, onSkip, loading, changedProducts }) => {
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
          <TrendingUp size={28} />
          <h2>Mise à jour des prix de vente</h2>
        </div>

        <div className="ca-modal-body">
          <p className="ca-modal-warning-text">
            Voulez-vous mettre à jour les prix de vente des produits ?
          </p>
          <div className="ca-modal-info-box">
            <Info size={18} />
            <div>
              <strong>Produits concernés :</strong> {changedProducts} produit(s) avec de nouveaux prix
            </div>
          </div>
          <p className="ca-modal-question">
            Les coûts seront sauvegardés dans tous les cas. Souhaitez-vous également mettre à jour les prix de vente ?
          </p>
        </div>

        <div className="ca-modal-footer">
          <button 
            className="ca-btn ca-btn-secondary" 
            onClick={onSkip}
            disabled={loading}
          >
            <X size={18} />
            Non, garder les prix actuels
          </button>
          <button 
            className="ca-btn ca-btn-success" 
            onClick={onConfirm}
            disabled={loading}
          >
            {loading ? (
              <>
                <div className="ca-loading-spinner" />
                Enregistrement...
              </>
            ) : (
              <>
                <CheckCircle size={18} />
                Oui, mettre à jour les prix
              </>
            )}
          </button>
        </div>
      </motion.div>
    </motion.div>
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
  const [method, setMethod] = useState('price');
  const [alert, setAlert] = useState(null);
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [showPriceUpdateModal, setShowPriceUpdateModal] = useState(false);
  const [isUpdateMode, setIsUpdateMode] = useState(false);
  
  // États pour les coûts et prix
  const [productCosts, setProductCosts] = useState({});
  const [productPrices, setProductPrices] = useState({});
  const [productMargins, setProductMargins] = useState({});
  const [pricesChanged, setPricesChanged] = useState({});

  useEffect(() => {
    checkReceiptStatus();
  }, [id]);

  const checkReceiptStatus = async () => {
    try {
      setLoading(true);
      const response = await stockReceiptService.getById(id);
      const receiptData = response.data;
      setReceipt(receiptData);

      if (receiptData.status === 'cost_allocated') {
        setIsUpdateMode(true);
        await loadRecommendations();
      } else {
        setShowConfirmModal(true);
      }
    } catch (err) {
      console.error('Erreur:', err);
      setAlert({ type: 'error', message: 'Impossible de charger la réception' });
    } finally {
      setLoading(false);
    }
  };

  const loadRecommendations = async (selectedMethod = method) => {
    try {
      setLoading(true);
      const response = await stockReceiptService.getCostRecommendations(id, selectedMethod);
      setRecommendations(response.data);

      // Initialiser avec les recommandations
      const costs = {};
      const prices = {};
      const margins = {};
      const changed = {};
      
      response.data.recommendations.forEach(rec => {
        costs[rec.product_id] = {
          freight: parseFloat(rec.recommended_freight_cost_per_unit),
          other: parseFloat(rec.recommended_other_costs_per_unit),
          supplier: parseFloat(rec.supplier_unit_cost),
          total: parseFloat(rec.recommended_total_unit_cost)
        };
        
        const currentPrice = parseFloat(rec.product_current_base_price) || 0;
        const defaultPrice = rec.recommended_total_unit_cost * 1.3;
        
        prices[rec.product_id] = defaultPrice;
        margins[rec.product_id] = 30;
        changed[rec.product_id] = false;
      });
      
      setProductCosts(costs);
      setProductPrices(prices);
      setProductMargins(margins);
      setPricesChanged(changed);
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

  const handleMethodChange = async (newMethod) => {
    setMethod(newMethod);
    await loadRecommendations(newMethod);
  };

  const redistributeCosts = (updatedProductId, field, newValue) => {
    const totalFreight = recommendations.total_expenses.freight_costs;
    const totalOther = recommendations.total_expenses.other_costs;
    
    const newCosts = { ...productCosts };
    const parsedValue = parseFloat(newValue) || 0;
    newCosts[updatedProductId] = { 
      ...newCosts[updatedProductId], 
      [field]: parsedValue 
    };
    
    let totalAllocated = 0;
    Object.keys(newCosts).forEach(pid => {
      const product = recommendations.recommendations.find(r => r.product_id === parseInt(pid));
      if (product) {
        totalAllocated += newCosts[pid][field] * product.total_quantity;
      }
    });
    
    const targetTotal = field === 'freight' ? totalFreight : totalOther;
    const diff = targetTotal - totalAllocated;
    
    if (Math.abs(diff) > 0.01) {
      const otherProducts = Object.keys(newCosts).filter(pid => pid !== updatedProductId.toString());
      let totalOtherQty = 0;
      
      otherProducts.forEach(pid => {
        const product = recommendations.recommendations.find(r => r.product_id === parseInt(pid));
        if (product) {
          totalOtherQty += product.total_quantity;
        }
      });
      
      if (totalOtherQty > 0) {
        otherProducts.forEach(pid => {
          const product = recommendations.recommendations.find(r => r.product_id === parseInt(pid));
          if (product) {
            const currentTotal = newCosts[pid][field] * product.total_quantity;
            const newTotal = currentTotal + (diff * product.total_quantity / totalOtherQty);
            newCosts[pid][field] = Math.max(0, newTotal / product.total_quantity);
          }
        });
      }
    }
    
    Object.keys(newCosts).forEach(pid => {
      newCosts[pid].total = newCosts[pid].supplier + newCosts[pid].freight + newCosts[pid].other;
    });
    
    return newCosts;
  };

  const handleCostBlur = (productId, field) => {
    const updated = redistributeCosts(productId, field, productCosts[productId][field]);
    setProductCosts(updated);
    
    if (productMargins[productId]) {
      const newTotal = updated[productId].total;
      const newPrice = newTotal * (1 + productMargins[productId] / 100);
      setProductPrices(prev => ({ ...prev, [productId]: newPrice }));
    }
  };

  const handleCostChange = (productId, field, value) => {
    setProductCosts(prev => ({
      ...prev,
      [productId]: {
        ...prev[productId],
        [field]: parseFloat(value) || 0,
        total: prev[productId].supplier + 
               (field === 'freight' ? (parseFloat(value) || 0) : prev[productId].freight) +
               (field === 'other' ? (parseFloat(value) || 0) : prev[productId].other)
      }
    }));
  };

  const handleMarginChange = (productId, margin) => {
    setProductMargins(prev => ({ ...prev, [productId]: parseFloat(margin) || 0 }));
  };

  const handleMarginBlur = (productId) => {
    const margin = productMargins[productId] || 0;
    const cost = productCosts[productId]?.total || 0;
    const newPrice = cost * (1 + margin / 100);
    setProductPrices(prev => ({ ...prev, [productId]: newPrice }));
    
    const product = recommendations.recommendations.find(r => r.product_id === productId);
    const currentPrice = parseFloat(product?.product_current_base_price) || 0;
    setPricesChanged(prev => ({ ...prev, [productId]: Math.abs(newPrice - currentPrice) > 0.01 }));
  };

  const handlePriceChange = (productId, price) => {
    setProductPrices(prev => ({ ...prev, [productId]: parseFloat(price) || 0 }));
    
    const product = recommendations.recommendations.find(r => r.product_id === productId);
    const currentPrice = parseFloat(product?.product_current_base_price) || 0;
    setPricesChanged(prev => ({ ...prev, [productId]: Math.abs(parseFloat(price) - currentPrice) > 0.01 }));
  };

  const handlePriceBlur = (productId) => {
    const price = productPrices[productId] || 0;
    const cost = productCosts[productId]?.total || 0;
    const newMargin = cost > 0 ? ((price - cost) / cost) * 100 : 0;
    setProductMargins(prev => ({ ...prev, [productId]: newMargin }));
  };

  const handleSaveClick = () => {
    const changedCount = Object.values(pricesChanged).filter(Boolean).length;
    if (changedCount > 0) {
      setShowPriceUpdateModal(true);
    } else {
      handleSaveAll(false);
    }
  };

  const handleSaveAll = async (updatePrices) => {
    try {
      setSubmitting(true);
      setAlert(null);

      // Sauvegarder les coûts
      const costData = {
        allocations: Object.entries(productCosts).map(([productId, costs]) => ({
          product_id: parseInt(productId),
          freight_cost_per_unit: costs.freight,
          other_costs_per_unit: costs.other
        }))
      };
      await stockReceiptService.applyCosts(id, costData);

      // Sauvegarder les prix si demandé
      if (updatePrices) {
        const priceData = {
          products: Object.entries(productPrices)
            .filter(([pid]) => pricesChanged[pid])
            .map(([productId, price]) => ({
              id: parseInt(productId),
              base_price: price
            }))
        };
        
        if (priceData.products.length > 0) {
          await productService.updateBasePrices(priceData);
        }
      }

      // Nettoyer le localStorage
      localStorage.removeItem(`cost-allocation-${id}`);

      setAlert({ 
        type: 'success', 
        message: updatePrices 
          ? 'Coûts et prix enregistrés avec succès !' 
          : 'Coûts enregistrés avec succès !'
      });
      
      setTimeout(() => {
        navigate(`/reapprovisionnements/${id}`);
      }, 1500);
    } catch (err) {
      console.error('Erreur:', err);
      setAlert({ type: 'error', message: err.response?.data?.message || 'Erreur lors de l\'enregistrement' });
    } finally {
      setSubmitting(false);
      setShowPriceUpdateModal(false);
    }
  };

  if (loading && !recommendations) {
    return (
      <div className="ca-loading-screen">
        <div className="ca-loading-spinner" />
        <p>Chargement...</p>
      </div>
    );
  }

  const totalToAllocate = recommendations?.total_expenses?.total_to_allocate || 0;

  // Calcul des totaux
  let grandTotalProfit = 0;
  let grandTotalRevenue = 0;
  let grandTotalCost = 0;

  recommendations?.recommendations.forEach(product => {
    const costs = productCosts[product.product_id] || {};
    const price = productPrices[product.product_id] || 0;
    const profit = (price - (costs.total || 0)) * product.total_quantity;
    const revenue = price * product.total_quantity;
    const totalCost = (costs.total || 0) * product.total_quantity;
    
    grandTotalProfit += profit;
    grandTotalRevenue += revenue;
    grandTotalCost += totalCost;
  });

  return (
    <div className="ca-page">
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
      </motion.div>

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
                  {formatCurrency(totalToAllocate)} Ar
                </span>
              </div>
            </div>
          </div>

          {/* Method selector */}
          <div className="ca-method-selector">
            <h3>Méthode de répartition</h3>
            <div className="ca-method-buttons">
              {[
                { key: 'price', label: 'Par prix', icon: DollarSign },
                { key: 'quantity', label: 'Par quantité', icon: Layers },
                { key: 'weight', label: 'Par pondération prix×quantité', icon: Package }
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

          {/* Products Table */}
          <div className="ca-table-wrapper">
            <div className="ca-table-container">
              <table className="ca-table">
                <thead>
                  <tr>
                    <th className="ca-sticky-col">Produit</th>
                    <th>Qté</th>
                    <th>Prix actuel</th>
                    <th>Prix fourn.</th>
                    <th>Transport/u</th>
                    <th>% Transp.</th>
                    <th>Autres/u</th>
                    <th>% Autres</th>
                    <th>Coût tot/u</th>
                    <th>Coût total</th>
                    <th>Marge %</th>
                    <th>Prix vente</th>
                    <th>Profit/u</th>
                    <th>Profit tot.</th>
                  </tr>
                </thead>
                <tbody>
                  {recommendations.recommendations.map(product => {
                    const costs = productCosts[product.product_id] || {};
                    const price = productPrices[product.product_id] || 0;
                    const margin = productMargins[product.product_id] || 0;
                    const profit = price - (costs.total || 0);
                    const totalProfit = profit * product.total_quantity;
                    
                    const freightTotal = (costs.freight || 0) * product.total_quantity;
                    const otherTotal = (costs.other || 0) * product.total_quantity;
                    const grandTotal = (costs.total || 0) * product.total_quantity;
                    
                    const freightPercent = recommendations.total_expenses.freight_costs > 0 
                      ? (freightTotal / recommendations.total_expenses.freight_costs) * 100 
                      : 0;
                    const otherPercent = recommendations.total_expenses.other_costs > 0 
                      ? (otherTotal / recommendations.total_expenses.other_costs) * 100 
                      : 0;
                    
                    const currentPrice = parseFloat(product.product_current_base_price) || 0;
                    const isPriceChanged = pricesChanged[product.product_id];
                    
                    return (
                      <tr key={product.product_id}>
                        <td className="ca-product-cell ca-sticky-col">
                          <div className="ca-product-name">{product.product_name}</div>
                          <div className="ca-product-variants">{product.variants_count} variant(s)</div>
                        </td>
                        <td className="ca-qty-cell">{product.total_quantity}</td>
                        <td className="ca-current-price-cell">
                          {formatCurrency(currentPrice)} Ar
                        </td>
                        <td className="ca-cost-cell">{formatCurrency(costs.supplier || 0)} Ar</td>
                        <td className="ca-input-cell">
                          <input
                            type="number"
                            className="ca-table-input"
                            value={costs.freight || 0}
                            onChange={(e) => handleCostChange(product.product_id, 'freight', e.target.value)}
                            onBlur={() => handleCostBlur(product.product_id, 'freight')}
                            step="0.01"
                            min="0"
                          />
                        </td>
                        <td className="ca-percent-cell">
                          <div className="ca-percent-bar">
                            <div 
                              className="ca-percent-fill freight" 
                              style={{ width: `${Math.min(freightPercent, 100)}%` }}
                            />
                            <span className="ca-percent-text">{freightPercent.toFixed(1)}%</span>
                          </div>
                        </td>
                        <td className="ca-input-cell">
                          <input
                            type="number"
                            className="ca-table-input"
                            value={costs.other || 0}
                            onChange={(e) => handleCostChange(product.product_id, 'other', e.target.value)}
                            onBlur={() => handleCostBlur(product.product_id, 'other')}
                            step="0.01"
                            min="0"
                          />
                        </td>
                        <td className="ca-percent-cell">
                          <div className="ca-percent-bar">
                            <div 
                              className="ca-percent-fill other" 
                              style={{ width: `${Math.min(otherPercent, 100)}%` }}
                            />
                            <span className="ca-percent-text">{otherPercent.toFixed(1)}%</span>
                          </div>
                        </td>
                        <td className="ca-total-cell">{formatCurrency(costs.total || 0)} Ar</td>
                        <td className="ca-grand-total-cell">{formatCurrency(grandTotal)} Ar</td>
                        <td className="ca-input-cell">
                          <input
                            type="number"
                            className="ca-table-input"
                            value={margin}
                            onChange={(e) => handleMarginChange(product.product_id, e.target.value)}
                            onBlur={() => handleMarginBlur(product.product_id)}
                            step="0.1"
                          />
                        </td>
                        <td className="ca-input-cell">
                          <input
                            type="number"
                            className={`ca-table-input ${isPriceChanged ? 'ca-price-changed' : ''}`}
                            value={price}
                            onChange={(e) => handlePriceChange(product.product_id, e.target.value)}
                            onBlur={() => handlePriceBlur(product.product_id)}
                            step="0.01"
                            min={costs.total || 0}
                          />
                        </td>
                        <td className={`ca-profit-cell ${profit >= 0 ? 'positive' : 'negative'}`}>
                          {formatCurrency(profit)} Ar
                        </td>
                        <td className={`ca-total-profit-cell ${totalProfit >= 0 ? 'positive' : 'negative'}`}>
                          {formatCurrency(totalProfit)} Ar
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
                <tfoot>
                  <tr className="ca-total-row">
                    <td className="ca-sticky-col"><strong>TOTAL</strong></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td className="ca-footer-total">{formatCurrency(grandTotalCost)} Ar</td>
                    <td></td>
                    <td className="ca-footer-total">{formatCurrency(grandTotalRevenue)} Ar</td>
                    <td></td>
                    <td className={`ca-footer-profit ${grandTotalProfit >= 0 ? 'positive' : 'negative'}`}>
                      {formatCurrency(grandTotalProfit)} Ar
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          {/* Save Button */}
          <div className="ca-actions">
            <button 
              className="ca-btn ca-btn-lg ca-btn-success"
              onClick={handleSaveClick}
              disabled={submitting}
            >
              {submitting ? (
                <>
                  <div className="ca-loading-spinner" />
                  Enregistrement...
                </>
              ) : (
                <>
                  <CheckCircle size={20} />
                  Enregistrer tout
                </>
              )}
            </button>
          </div>
        </motion.div>
      )}

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

      <AnimatePresence>
        {showPriceUpdateModal && (
          <PriceUpdateModal
            isOpen={showPriceUpdateModal}
            onClose={() => setShowPriceUpdateModal(false)}
            onConfirm={() => handleSaveAll(true)}
            onSkip={() => handleSaveAll(false)}
            loading={submitting}
            changedProducts={Object.values(pricesChanged).filter(Boolean).length}
          />
        )}
      </AnimatePresence>
    </div>
  );
};

export default CostAllocation;