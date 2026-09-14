import React from 'react';
import { Trash2, Package, Image as ImageIcon, ShoppingBag, Hash, DollarSign } from 'lucide-react';
import './ItemsTable.css';

const ItemsTable = ({ items, currency, currencySymbol, onUpdateItem, onRemoveItem }) => {
  const calculateTotalCurrency = () => {
    return items.reduce((sum, item) => {
      const price = parseFloat(item.priceInCurrency) || 0;
      const qty = parseInt(item.quantity) || 0;
      return sum + (price * qty);
    }, 0);
  };

  const calculateTotalAriary = () => {
    return items.reduce((sum, item) => sum + item.subtotal, 0);
  };

  if (items.length === 0) {
    return (
      <div className="it-empty">
        <div className="it-empty-icon">
          <Package size={48} />
        </div>
        <h4>Aucun article ajouté</h4>
        <p>Recherchez et sélectionnez des produits pour commencer votre commande</p>
      </div>
    );
  }

  return (
    <div className="it-wrapper">
      <div className="it-container">
        <table className="it-table">
          <thead className="it-thead">
            <tr>
              <th>Produit</th>
              <th><Hash size={14} /> Quantité</th>
              <th><DollarSign size={14} /> Prix ({currencySymbol})</th>
              <th>Équivalent (Ar)</th>
              <th>Sous-total (Ar)</th>
              <th></th>
            </tr>
          </thead>
          <tbody className="it-tbody">
            {items.map((item, index) => (
              <tr key={index} className="it-row">
                <td className="it-product-cell">
                  <div className="it-product-info">
                    <div className="it-image-wrapper">
                      {item.productImage ? (
                        <img 
                          src={item.productImage} 
                          alt={item.productName}
                          className="it-image"
                        />
                      ) : (
                        <div className="it-image-placeholder">
                          <ImageIcon size={20} />
                        </div>
                      )}
                    </div>
                    <div className="it-details">
                      <p className="it-product-name">{item.productName}</p>
                      <p className="it-sku">SKU: {item.variantSku}</p>
                      {item.variantAttributes?.length > 0 && (
                        <div className="it-attributes">
                          {item.variantAttributes.map((attr, idx) => (
                            <span key={idx} className="it-attribute-badge">
                              {attr.attribute_type?.display_name || attr.attribute_type?.name || attr.attribute_name}: {attr.value}
                            </span>
                          ))}
                        </div>
                      )}
                    </div>
                  </div>
                </td>
                <td className="it-qty-cell">
                  <input
                    type="number"
                    className="it-qty-input"
                    min="1"
                    value={item.quantity}
                    onChange={(e) => onUpdateItem(index, 'quantity', e.target.value)}
                  />
                </td>
                <td className="it-price-cell">
                  <div className="it-price-wrapper">
                    <input
                      type="number"
                      className="it-price-input"
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                      value={item.priceInCurrency}
                      onChange={(e) => onUpdateItem(index, 'priceInCurrency', e.target.value)}
                    />
                    <span className="it-price-currency">{currencySymbol}</span>
                  </div>
                </td>
                <td className="it-ariary-cell">
                  <span className="it-ariary-value">
                    {item.priceInAriary.toLocaleString('fr-FR', {
                      minimumFractionDigits: 0,
                      maximumFractionDigits: 0
                    })} <span className="it-currency-label">Ar</span>
                  </span>
                </td>
                <td className="it-subtotal-cell">
                  <span className="it-subtotal-value">
                    {item.subtotal.toLocaleString('fr-FR', {
                      minimumFractionDigits: 0,
                      maximumFractionDigits: 0
                    })} <span className="it-currency-label">Ar</span>
                  </span>
                </td>
                <td className="it-actions-cell">
                  <button
                    type="button"
                    className="it-remove-btn"
                    onClick={() => onRemoveItem(index)}
                    title="Supprimer cet article"
                  >
                    <Trash2 size={16} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="it-totals">
        <div className="it-total-row">
          <div className="it-total-label">
            <ShoppingBag size={16} />
            <span>Nombre d'articles</span>
          </div>
          <span className="it-total-value">{items.length}</span>
        </div>
        <div className="it-total-row">
          <div className="it-total-label">
            <Package size={16} />
            <span>Quantité totale</span>
          </div>
          <span className="it-total-value">
            {items.reduce((sum, item) => sum + parseInt(item.quantity || 0), 0)} unités
          </span>
        </div>
        <div className="it-total-row">
          <div className="it-total-label">
            <DollarSign size={16} />
            <span>Total ({currencySymbol})</span>
          </div>
          <span className="it-total-value">
            {calculateTotalCurrency().toLocaleString('fr-FR', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            })} {currencySymbol}
          </span>
        </div>
        <div className="it-total-row it-total-main">
          <div className="it-total-label">
            <span>Total (Ariary)</span>
          </div>
          <span className="it-total-value it-total-highlight">
            {calculateTotalAriary().toLocaleString('fr-FR', {
              minimumFractionDigits: 0,
              maximumFractionDigits: 0
            })} Ar
          </span>
        </div>
      </div>
    </div>
  );
};

export default ItemsTable;