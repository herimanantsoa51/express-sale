// src/components/statistics/ProductsTable.jsx
import React, { useState } from 'react';
import { ExternalLink, ChevronLeft, ChevronRight } from 'lucide-react';
import { formatCurrency, formatNumber } from '../../utils/formatters';

const ProductsTable = ({ products, pagination, onPageChange, navigate }) => {
  const [currentPage, setCurrentPage] = useState(pagination?.current_page || 1);

  const handlePageChange = (newPage) => {
    if (newPage >= 1 && newPage <= pagination.last_page) {
      setCurrentPage(newPage);
      onPageChange(newPage);
    }
  };

  const getPageNumbers = () => {
    const pages = [];
    const maxPages = 5;
    const lastPage = pagination.last_page;

    if (lastPage <= maxPages) {
      for (let i = 1; i <= lastPage; i++) {
        pages.push(i);
      }
    } else {
      if (currentPage <= 3) {
        pages.push(1, 2, 3, 4, '...', lastPage);
      } else if (currentPage >= lastPage - 2) {
        pages.push(1, '...', lastPage - 3, lastPage - 2, lastPage - 1, lastPage);
      } else {
        pages.push(1, '...', currentPage - 1, currentPage, currentPage + 1, '...', lastPage);
      }
    }

    return pages;
  };

  return (
    <div>
      <div className="stats-products-table">
        {products.map((product, index) => (
          <div
            key={product.product_id}
            className="stats-product-card"
            onClick={() => navigate(`/produits/${product.product_id}`)}
          >
            <div className="stats-product-rank">
              {pagination ? (pagination.from + index) : (index + 1)}
            </div>
            <div className="stats-product-info">
              <h4>{product.product_name}</h4>
              <span className="stats-product-category">{product.category_name}</span>
            </div>
            <div className="stats-product-stats">
              <div className="stats-product-stat">
                <span className="stats-product-stat-value">
                  {formatCurrency(product.total_revenue)}
                </span>
                <span className="stats-product-stat-label">Chiffre d'affaires</span>
              </div>
              <div className="stats-product-stat">
                <span className="stats-product-stat-value">
                  {formatNumber(product.total_quantity)}
                </span>
                <span className="stats-product-stat-label">Unités vendues</span>
              </div>
              <ExternalLink size={16} />
            </div>
          </div>
        ))}
      </div>

      {/* Pagination */}
      {pagination && pagination.last_page > 1 && (
        <div className="stats-pagination">
          <div className="pagination-info">
            Affichage de {pagination.from} à {pagination.to} sur {pagination.total} produits
          </div>

          <div className="pagination-controls">
            <button
              onClick={() => handlePageChange(currentPage - 1)}
              disabled={currentPage === 1}
              className="pagination-btn"
            >
              <ChevronLeft size={16} />
            </button>

            {getPageNumbers().map((page, index) => (
              <button
                key={index}
                onClick={() => typeof page === 'number' && handlePageChange(page)}
                className={`pagination-btn ${currentPage === page ? 'active' : ''} ${typeof page !== 'number' ? 'dots' : ''}`}
                disabled={typeof page !== 'number'}
              >
                {page}
              </button>
            ))}

            <button
              onClick={() => handlePageChange(currentPage + 1)}
              disabled={currentPage === pagination.last_page}
              className="pagination-btn"
            >
              <ChevronRight size={16} />
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProductsTable;