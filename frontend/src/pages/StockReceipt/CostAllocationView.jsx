import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import {
  ArrowLeft, AlertCircle, X, Truck, Tag, DollarSign, 
  Eye, Package, Calendar, User
} from 'lucide-react';
import stockReceiptService from '../../services/stockReceiptService';
import '../../styles/CostAllocationView.css';

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(amount);
};

const formatDate = (dateString) => {
  if (!dateString) return 'Non validé';
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(dateString));
};

const CostAllocationView = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [loading, setLoading] = useState(true);
  const [allocations, setAllocations] = useState(null);
  const [alert, setAlert] = useState(null);

  useEffect(() => {
    loadAllocatedCosts();
  }, [id]);

  const loadAllocatedCosts = async () => {
    try {
      setLoading(true);
      const response = await stockReceiptService.getAllocatedCosts(id);
      setAllocations(response.data);
    } catch (err) {
      console.error('Erreur:', err);
      setAlert({ 
        type: 'error', 
        message: err.response?.data?.message || 'Impossible de charger les coûts alloués' 
      });
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="cav-loading-screen">
        <div className="cav-loading-spinner" />
        <p>Chargement des coûts alloués...</p>
      </div>
    );
  }

  if (!allocations || !allocations.allocations) {
    return (
      <div className="cav-page">
        <div className="cav-error-state">
          <AlertCircle size={48} />
          <h2>Aucune donnée disponible</h2>
          <p>Les coûts n'ont pas encore été alloués pour cette réception.</p>
          <button 
            className="cav-btn cav-btn-primary"
            onClick={() => navigate(`/reapprovisionnements/${id}`)}
          >
            Retour à la réception
          </button>
        </div>
      </div>
    );
  }

  // Calcul des totaux
  let totalSupplier = 0;
  let totalFreight = 0;
  let totalOther = 0;
  let totalGlobal = 0;
  let totalQuantity = 0;

  allocations.allocations.forEach(product => {
    const qty = product.total_quantity;
    totalQuantity += qty;
    totalSupplier += product.costs.supplier_unit * qty;
    totalFreight += product.costs.freight_unit * qty;
    totalOther += product.costs.other_unit * qty;
    totalGlobal += product.costs.total_unit * qty;
  });

  return (
    <div className="cav-page">
      <motion.button
        className="cav-back-btn"
        onClick={() => navigate(`/reapprovisionnements/${id}`)}
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        whileHover={{ x: -4 }}
      >
        <ArrowLeft size={18} />
        Retour à la réception
      </motion.button>

      <motion.div
        className="cav-header"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="cav-header-icon">
          <Eye size={32} />
        </div>
        <div>
          <h1>Visualisation des coûts alloués</h1>
          <p>Répartition détaillée des coûts par produit</p>
        </div>
      </motion.div>

      {alert && (
        <motion.div
          className={`cav-alert ${alert.type}`}
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
        >
          <AlertCircle size={18} />
          <span>{alert.message}</span>
          <button className="cav-alert-close" onClick={() => setAlert(null)}>
            <X size={16} />
          </button>
        </motion.div>
      )}

      {/* Info validation */}
      {allocations.validated_at && (
        <motion.div
          className="cav-validation-info"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
        >
          <div className="cav-validation-item">
            <Calendar size={20} />
            <div>
              <span className="cav-validation-label">Date de validation</span>
              <span className="cav-validation-value">{formatDate(allocations.validated_at)}</span>
            </div>
          </div>
          {allocations.cost_validated_by && (
          <div className="cav-validation-item">
            <User size={20} />
            <div>
              <span className="cav-validation-label">Validé par</span>
              <span className="cav-validation-value">{allocations.cost_validated_by.name}</span> {/* Afficher le nom */}
            </div>
          </div>
        )}
        </motion.div>
      )}

      {/* Summary Cards */}
      <motion.div
        className="cav-summary"
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
      >
        <div className="cav-summary-card">
          <Package size={24} className="cav-summary-icon supplier" />
          <div>
            <span className="cav-summary-label">Coût fournisseur</span>
            <span className="cav-summary-value">{formatCurrency(totalSupplier)} Ar</span>
          </div>
        </div>
        <div className="cav-summary-card">
          <Truck size={24} className="cav-summary-icon freight" />
          <div>
            <span className="cav-summary-label">Coût transport</span>
            <span className="cav-summary-value">{formatCurrency(totalFreight)} Ar</span>
          </div>
        </div>
        <div className="cav-summary-card">
          <Tag size={24} className="cav-summary-icon other" />
          <div>
            <span className="cav-summary-label">Autres coûts</span>
            <span className="cav-summary-value">{formatCurrency(totalOther)} Ar</span>
          </div>
        </div>
        <div className="cav-summary-card highlight">
          <DollarSign size={24} className="cav-summary-icon total" />
          <div>
            <span className="cav-summary-label">Coût total</span>
            <span className="cav-summary-value">{formatCurrency(totalGlobal)} Ar</span>
          </div>
        </div>
      </motion.div>

      {/* Products Table */}
      <motion.div
        className="cav-table-wrapper"
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
      >
        <div className="cav-table-container">
          <table className="cav-table">
            <thead>
              <tr>
                <th className="cav-sticky-col">Produit</th>
                <th>Quantité</th>
                <th>Variantes</th>
                <th>Coût fourn./u</th>
                <th>Transport/u</th>
                <th>Autres/u</th>
                <th>Coût total/u</th>
                <th>Coût fourn. total</th>
                <th>Transport total</th>
                <th>Autres total</th>
                <th>Coût global</th>
              </tr>
            </thead>
            <tbody>
              {allocations.allocations.map((product, index) => {
                const qty = product.total_quantity;
                const supplierTotal = product.costs.supplier_unit * qty;
                const freightTotal = product.costs.freight_unit * qty;
                const otherTotal = product.costs.other_unit * qty;
                const globalTotal = product.costs.total_unit * qty;

                return (
                  <motion.tr
                    key={product.product_id}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: 0.3 + index * 0.05 }}
                  >
                    <td className="cav-product-cell cav-sticky-col">
                      <div className="cav-product-name">{product.product_name}</div>
                    </td>
                    <td className="cav-qty-cell">{qty}</td>
                    <td className="cav-variants-cell">
                      {product.variants.length} variant{product.variants.length > 1 ? 's' : ''}
                    </td>
                    <td className="cav-cost-cell">{formatCurrency(product.costs.supplier_unit)} Ar</td>
                    <td className="cav-cost-cell freight">{formatCurrency(product.costs.freight_unit)} Ar</td>
                    <td className="cav-cost-cell other">{formatCurrency(product.costs.other_unit)} Ar</td>
                    <td className="cav-total-unit-cell">{formatCurrency(product.costs.total_unit)} Ar</td>
                    <td className="cav-total-cell">{formatCurrency(supplierTotal)} Ar</td>
                    <td className="cav-total-cell freight">{formatCurrency(freightTotal)} Ar</td>
                    <td className="cav-total-cell other">{formatCurrency(otherTotal)} Ar</td>
                    <td className="cav-grand-total-cell">{formatCurrency(globalTotal)} Ar</td>
                  </motion.tr>
                );
              })}
            </tbody>
            <tfoot>
              <tr className="cav-total-row">
                <td className="cav-sticky-col"><strong>TOTAL</strong></td>
                <td className="cav-qty-cell"><strong>{totalQuantity}</strong></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td className="cav-footer-total">{formatCurrency(totalSupplier)} Ar</td>
                <td className="cav-footer-total freight">{formatCurrency(totalFreight)} Ar</td>
                <td className="cav-footer-total other">{formatCurrency(totalOther)} Ar</td>
                <td className="cav-footer-grand-total">{formatCurrency(totalGlobal)} Ar</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </motion.div>

      {/* Variants Details (collapsible sections) */}
      <motion.div
        className="cav-variants-section"
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.4 }}
      >
        <h2>Détails par variante</h2>
        {allocations.allocations.map((product, productIndex) => (
          <VariantDetails 
            key={product.product_id} 
            product={product}
            delay={0.5 + productIndex * 0.1}
          />
        ))}
      </motion.div>
    </div>
  );
};

// Composant pour afficher les détails des variantes
const VariantDetails = ({ product, delay }) => {
  const [isExpanded, setIsExpanded] = useState(false);

  return (
    <motion.div
      className="cav-variant-card"
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay }}
    >
      <div 
        className="cav-variant-header"
        onClick={() => setIsExpanded(!isExpanded)}
      >
        <div className="cav-variant-header-content">
          <h3>{product.product_name}</h3>
          <span className="cav-variant-count">
            {product.variants.length} variante{product.variants.length > 1 ? 's' : ''}
          </span>
        </div>
        <motion.div
          className="cav-expand-icon"
          animate={{ rotate: isExpanded ? 180 : 0 }}
        >
          ▼
        </motion.div>
      </div>

      {isExpanded && (
        <motion.div
          className="cav-variant-body"
          initial={{ height: 0, opacity: 0 }}
          animate={{ height: 'auto', opacity: 1 }}
          exit={{ height: 0, opacity: 0 }}
        >
          <table className="cav-variant-table">
            <thead>
              <tr>
                <th>N° Lot</th>
                <th>Attributs</th>
                <th>Qté initiale</th>
                <th>Qté restante</th>
              </tr>
            </thead>
            <tbody>
              {product.variants.map((variant, index) => {
                const attributes = variant.attributes || {};
                return (
                  <tr key={variant.variant_id || index}>
                    <td className="cav-batch-cell">{variant.batch_number || 'N/A'}</td>
                    <td className="cav-attributes-cell">
                      {Object.entries(attributes).length > 0 ? (
                        Object.entries(attributes).map(([key, value], i) => (
                          <span key={i} className="cav-attribute-tag">
                            {String(key)}: {String(value)}
                          </span>
                        ))
                      ) : (
                        <span className="cav-no-attributes">Aucun attribut</span>
                      )}
                    </td>
                    <td className="cav-qty-cell">{variant.intial_quantity || 0}</td>
                    <td className="cav-qty-cell">
                      <span className={variant.remaining_quantity === 0 ? 'cav-qty-zero' : ''}>
                        {variant.remaining_quantity || 0}
                      </span>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </motion.div>
      )}
    </motion.div>
  );
};

export default CostAllocationView;