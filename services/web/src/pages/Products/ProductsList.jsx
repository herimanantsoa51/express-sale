import React, { useState, useEffect, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useNavigate } from 'react-router-dom';
import '../../styles/ProductsList.css';
import productService from '../../services/productService';
import AttributeManagerModal from './AttributeValuesModal';


// ============================================
// PRODUCT SKELETON COMPONENT
// ============================================

const ProductSkeleton = () => {
  return (
    <div className="product-skeleton">
      <div className="product-skeleton__image" />
      <div className="product-skeleton__content">
        <div className="product-skeleton__title" />
        <div className="product-skeleton__price" />
        <div className="product-skeleton__meta" />
      </div>
    </div>
  );
};

// ============================================
// PRODUCT CARD COMPONENT
// ============================================

const ProductCard = ({ product }) => {
  const navigate = useNavigate();

  const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(price) + ' Ar';
  };

  const handleClick = () => {
    navigate(`/produits/${product.id}`);
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -8 }}
      transition={{ duration: 0.3, ease: [0.25, 0.1, 0.25, 1] }}
      className="product-card"
      onClick={handleClick}
    >
      <div className="product-card__image-wrapper">
        {product.image_url ? (
          <img 
            src={product.image_url} 
            alt={product.name} 
            className="product-card__image" 
          />
        ) : (
          <div className="product-card__image-placeholder">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
              <rect x="3" y="3" width="18" height="18" rx="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <path d="M21 15l-5-5L5 21" />
            </svg>
          </div>
        )}
      </div>

      <div className="product-card__content">
        <div className="product-card__header">
          <h3 className="product-card__title">{product.name}</h3>
          <span className={`product-card__badge ${product.is_active ? 'product-card__badge--active' : 'product-card__badge--inactive'}`}>
            {product.is_active ? 'Active' : 'Inactive'}
          </span>
        </div>

        <div className="product-card__price">{formatPrice(product.base_price)}</div>

        <div className="product-card__meta">
          <span className="product-card__meta-item">{product.category?.name}</span>
          {product.subcategory && (
            <>
              <span className="product-card__meta-separator">•</span>
              <span className="product-card__meta-item">{product.subcategory.name}</span>
            </>
          )}
        </div>
      </div>
    </motion.div>
  );
};

// ============================================
// PRODUCT FILTERS COMPONENT
// ============================================

const ProductFilters = ({ onFiltersChange, loading }) => {
  const [search, setSearch] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [subcategoryId, setSubcategoryId] = useState('');
  const [isActive, setIsActive] = useState(false);
  const [lowStock, setLowStock] = useState(false);
  const [minPrice, setMinPrice] = useState('');
  const [maxPrice, setMaxPrice] = useState('');
  const [categories, setCategories] = useState([]);

  const [subcategories, setSubcategories] = useState([]);

  useEffect(() => {
    loadCategories();
  }, []);

  useEffect(() => {
    if (categoryId) {
      loadSubcategories(categoryId);
    } else {
      setSubcategories([]);
      setSubcategoryId('');
    }
  }, [categoryId]);

  const loadCategories = async () => {
    try {
      const data = await productService.getRootCategories();
      setCategories(data);
    } catch (error) {
      console.error('Error loading categories:', error);
    }
  };

  const loadSubcategories = async (parentId) => {
    try {
      const data = await productService.getSubcategories(parentId);
      setSubcategories(data);
    } catch (error) {
      console.error('Error loading subcategories:', error);
    }
  };

  useEffect(() => {
    const timer = setTimeout(() => {
      applyFilters();
    }, 500);
    return () => clearTimeout(timer);
  }, [search, categoryId, subcategoryId, isActive, lowStock, minPrice, maxPrice]);

  const applyFilters = () => {
    const filters = {};
    if (search.trim()) filters.search = search.trim();
    if (categoryId) filters.category_id = categoryId;
    if (subcategoryId) filters.subcategory_id = subcategoryId;
    if (isActive) filters.is_active = true;
    if (lowStock) filters.low_stock = true;
    if (minPrice) filters.min_price = minPrice;
    if (maxPrice) filters.max_price = maxPrice;
    onFiltersChange(filters);
  };

  const handleReset = () => {
    setSearch('');
    setCategoryId('');
    setSubcategoryId('');
    setIsActive(false);
    setLowStock(false);
    setMinPrice('');
    setMaxPrice('');
    onFiltersChange({});
  };

  return (
    <div className="product-filters">
      <div className="product-filters__grid">
        <div className="product-filters__search-wrapper">
          <svg className="product-filters__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input
            type="text"
            placeholder="Search products"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            disabled={loading}
            className="product-filters__search-input"
          />
        </div>

        <select
          value={categoryId}
          onChange={(e) => setCategoryId(e.target.value)}
          disabled={loading}
          className="product-filters__select"
        >
          <option value="">All Categories</option>
          {categories.map(cat => (
            <option key={cat.id} value={cat.id}>{cat.name}</option>
          ))}
        </select>

        {categoryId && (
          <select
            value={subcategoryId}
            onChange={(e) => setSubcategoryId(e.target.value)}
            disabled={loading}
            className="product-filters__select"
          >
            <option value="">All Subcategories</option>
            {subcategories.map(sub => (
              <option key={sub.id} value={sub.id}>{sub.name}</option>
            ))}
          </select>
        )}

        <div className="product-filters__price-inputs">
          <input
            type="number"
            placeholder="Min price"
            value={minPrice}
            onChange={(e) => setMinPrice(e.target.value)}
            disabled={loading}
            className="product-filters__price-input"
          />
          <span className="product-filters__price-separator">—</span>
          <input
            type="number"
            placeholder="Max price"
            value={maxPrice}
            onChange={(e) => setMaxPrice(e.target.value)}
            disabled={loading}
            className="product-filters__price-input"
          />
        </div>
      </div>

      <div className="product-filters__toggles-row">
        <label className="product-filters__toggle">
          <input
            type="checkbox"
            checked={isActive}
            onChange={(e) => setIsActive(e.target.checked)}
            disabled={loading}
            className="product-filters__checkbox"
          />
          <span className="product-filters__toggle-label">Active products only</span>
        </label>

        <label className="product-filters__toggle">
          <input
            type="checkbox"
            checked={lowStock}
            onChange={(e) => setLowStock(e.target.checked)}
            disabled={loading}
            className="product-filters__checkbox"
          />
          <span className="product-filters__toggle-label">Low stock</span>
        </label>

        <button
          onClick={handleReset}
          disabled={loading}
          className="product-filters__reset-button"
        >
          Reset filters
        </button>
      </div>
    </div>
  );
};

// ============================================
// MAIN PRODUCTS PAGE COMPONENT
// ============================================

const ProductsList = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState({});
  const [pagination, setPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [showAttributeModal, setShowAttributeModal] = useState(false); // <-- Ajoutez cette ligne
  const navigate = useNavigate();
  const loadProducts = useCallback(async (page = 1) => {
    setLoading(true);
    setError(null);
    try {
      const data = await productService.getProducts({ ...filters, page });
      setProducts(data.data);
      setPagination({
        currentPage: data.current_page,
        lastPage: data.last_page,
        total: data.total,
        perPage: data.per_page
      });
      setCurrentPage(page);
    } catch (err) {
      setError('Failed to load products');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    loadProducts(1);
  }, [filters]);

  const handleFiltersChange = (newFilters) => {
    setFilters(newFilters);
    setCurrentPage(1);
  };

  return (
    <div className="products-page">
      <div className="products-page__container">
      <div className="products-page__header">
          <div className="products-page__header-main">
            <h1 className="products-page__title">Produits</h1>
            {pagination && (
              <div className="products-page__header-meta">
                {pagination.total} {pagination.total === 1 ? 'product' : 'products'}
              </div>
            )}
          </div>
          
          <div className="products-page__header-actions">
            <button 
              className="products-page__action-button"
              onClick={() => setShowAttributeModal(true)}
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                <path d="M8 10h8" />
                <path d="M8 14h6" />
              </svg>
              Attributes
            </button>
            
            <button 
              className="products-page__action-button products-page__action-button--primary"
              onClick={() => navigate(`/produits/nouveau`)}
            >
              Nouveau Produit
            </button>
          </div>
        </div>

        <ProductFilters onFiltersChange={handleFiltersChange} loading={loading} />

        {error && (
          <div className="products-page__error-state">
            <p className="products-page__error-text">{error}</p>
          </div>
        )}

        {loading ? (
          <div className="products-page__grid">
            {[...Array(6)].map((_, i) => (
              <ProductSkeleton key={i} />
            ))}
          </div>
        ) : products.length === 0 ? (
          <div className="products-page__empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="products-page__empty-icon">
              <circle cx="12" cy="12" r="10" />
              <path d="M12 8v4m0 4h.01" />
            </svg>
            <h3 className="products-page__empty-title">No products found</h3>
            <p className="products-page__empty-text">Try adjusting your filters</p>
          </div>
        ) : (
          <>
            <motion.div className="products-page__grid" layout>
              <AnimatePresence mode="popLayout">
                {products.map((product) => (
                  <ProductCard key={product.id} product={product} />
                ))}
              </AnimatePresence>
            </motion.div>

            {pagination && pagination.lastPage > 1 && (
              <div className="products-page__pagination">
                <button
                  onClick={() => loadProducts(currentPage - 1)}
                  disabled={currentPage === 1 || loading}
                  className={`products-page__pagination-button ${(currentPage === 1 || loading) ? 'products-page__pagination-button--disabled' : ''}`}
                >
                  Previous
                </button>
                
                <span className="products-page__pagination-info">
                  Page {pagination.currentPage} of {pagination.lastPage}
                </span>

                <button
                  onClick={() => loadProducts(currentPage + 1)}
                  disabled={currentPage === pagination.lastPage || loading}
                  className={`products-page__pagination-button ${(currentPage === pagination.lastPage || loading) ? 'products-page__pagination-button--disabled' : ''}`}
                >
                  Next
                </button>
              </div>
            )}
          </>
        )}
        <AttributeManagerModal 
          isOpen={showAttributeModal}
          onClose={() => setShowAttributeModal(false)}
        />
      </div>
    </div>
  );
};

export default ProductsList;