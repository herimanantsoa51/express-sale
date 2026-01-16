import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  Search, 
  Plus, 
  Filter, 
  X, 
  Package, 
  Tag,
  ChevronDown,
  Grid3X3,
  List,
  Image as ImageIcon,
  Loader2,
  RefreshCw,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight
} from 'lucide-react';
import productService from '../../../services/productService';
import './ProductSearch.css';

const ProductSearch = ({ onProductSelect, disabled, categories = [] }) => {
  const navigate = useNavigate();
  const [searchTerm, setSearchTerm] = useState('');
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [viewMode, setViewMode] = useState('grid');
  const [showFilters, setShowFilters] = useState(false);
  
  const [pagination, setPagination] = useState({
    current_page: 1,
    per_page: 24,
    total: 0,
    last_page: 1
  });

  const [filters, setFilters] = useState({
    category_id: '',
    subcategory_id: '',
    sort_by: 'name',
    sort_order: 'asc',
    has_variants: true
  });

  const rootCategories = categories.filter(cat => !cat.parent_id);
  const subcategories = filters.category_id 
    ? categories.filter(cat => cat.parent_id === parseInt(filters.category_id))
    : [];

  useEffect(() => {
    loadProducts();
  }, [filters, pagination.current_page]);

  useEffect(() => {
    const timer = setTimeout(() => {
      setPagination(prev => ({ ...prev, current_page: 1 }));
      loadProducts();
    }, 300);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  const loadProducts = async () => {
    try {
      setLoading(true);
      const params = {
        ...filters,
        search: searchTerm || undefined,
        page: pagination.current_page,
        per_page: pagination.per_page,
        is_active: true
      };
      
      Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === undefined) {
          delete params[key];
        }
      });
  
      console.log('🔍 Fetching products with params:', params);
      const response = await productService.getProducts(params);
      console.log('📦 Products response:', response);
      
      // Si le backend retourne has_variants: true, tous les produits ont des variantes
      // Donc on peut afficher tous les produits retournés
      setProducts(response.data || []);
      
      setPagination({
        current_page: response.current_page || 1,
        per_page: response.per_page || pagination.per_page,
        total: response.total || 0,
        last_page: response.last_page || 1
      });
      
    } catch (err) {
      console.error('❌ Error loading products:', err);
      setProducts([]);
      setPagination({
        current_page: 1,
        per_page: pagination.per_page,
        total: 0,
        last_page: 1
      });
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => {
      const newFilters = { ...prev, [key]: value };
      if (key === 'category_id') {
        newFilters.subcategory_id = '';
      }
      return newFilters;
    });
    
    setPagination(prev => ({ ...prev, current_page: 1 }));
  };

  const clearFilters = () => {
    setFilters({
      category_id: '',
      subcategory_id: '',
      sort_by: 'name',
      sort_order: 'asc',
      has_variants: true
    });
    setSearchTerm('');
    setPagination(prev => ({ ...prev, current_page: 1 }));
  };

  const goToPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      setPagination(prev => ({ ...prev, current_page: page }));
    }
  };

  const goToFirstPage = () => goToPage(1);
  const goToLastPage = () => goToPage(pagination.last_page);
  const goToPrevPage = () => goToPage(pagination.current_page - 1);
  const goToNextPage = () => goToPage(pagination.current_page + 1);

  const getPageNumbers = () => {
    const pages = [];
    const maxVisible = 5;
    
    let start = Math.max(1, pagination.current_page - Math.floor(maxVisible / 2));
    let end = Math.min(pagination.last_page, start + maxVisible - 1);
    
    if (end - start + 1 < maxVisible) {
      start = Math.max(1, end - maxVisible + 1);
    }
    
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    
    return pages;
  };

  const getProductImage = (product) => {
    return product.image_url || null;
  };

  const activeFiltersCount = Object.entries(filters).filter(([key, value]) => {
    if (key === 'sort_by' || key === 'sort_order' || key === 'has_variants') return false;
    return value !== '';
  }).length + (searchTerm ? 1 : 0);

  return (
    <div className="ps-wrapper">
      <div className="ps-header">
        <div className="ps-search-bar">
          <div className="ps-search-input-wrapper">
            <Search className="ps-search-icon" size={20} />
            <input
              type="text"
              className="ps-search-input"
              placeholder="Rechercher un produit par nom..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              disabled={disabled}
            />
            {searchTerm && (
              <button 
                type="button"
                className="ps-search-clear"
                onClick={() => setSearchTerm('')}
              >
                <X size={16} />
              </button>
            )}
          </div>
          
          <button 
            type="button"
            className={`ps-filter-btn ${showFilters ? 'ps-filter-active' : ''}`}
            onClick={() => setShowFilters(!showFilters)}
          >
            <Filter size={18} />
            Filtres
            {activeFiltersCount > 0 && (
              <span className="ps-filter-badge">{activeFiltersCount}</span>
            )}
          </button>

          <div className="ps-view-toggle">
            <button
              type="button"
              className={`ps-view-btn ${viewMode === 'grid' ? 'ps-view-active' : ''}`}
              onClick={() => setViewMode('grid')}
              title="Vue grille"
            >
              <Grid3X3 size={18} />
            </button>
            <button
              type="button"
              className={`ps-view-btn ${viewMode === 'list' ? 'ps-view-active' : ''}`}
              onClick={() => setViewMode('list')}
              title="Vue liste"
            >
              <List size={18} />
            </button>
          </div>

          <button
            type="button"
            className="ps-refresh-btn"
            onClick={loadProducts}
            disabled={loading}
            title="Actualiser"
          >
            <RefreshCw size={18} className={loading ? 'ps-spinning' : ''} />
          </button>
        </div>

        {showFilters && (
          <div className="ps-filters-panel">
            <div className="ps-filters-grid">
              <div className="ps-filter-group">
                <label className="ps-filter-label">
                  <Tag size={14} />
                  Catégorie
                </label>
                <select
                  className="ps-filter-select"
                  value={filters.category_id}
                  onChange={(e) => handleFilterChange('category_id', e.target.value)}
                >
                  <option value="">Toutes les catégories</option>
                  {rootCategories.map(cat => (
                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                  ))}
                </select>
              </div>

              <div className="ps-filter-group">
                <label className="ps-filter-label">
                  <Tag size={14} />
                  Sous-catégorie
                </label>
                <select
                  className="ps-filter-select"
                  value={filters.subcategory_id}
                  onChange={(e) => handleFilterChange('subcategory_id', e.target.value)}
                  disabled={!filters.category_id || subcategories.length === 0}
                >
                  <option value="">Toutes les sous-catégories</option>
                  {subcategories.map(subcat => (
                    <option key={subcat.id} value={subcat.id}>{subcat.name}</option>
                  ))}
                </select>
              </div>

              <div className="ps-filter-group">
                <label className="ps-filter-label">Trier par</label>
                <select
                  className="ps-filter-select"
                  value={filters.sort_by}
                  onChange={(e) => handleFilterChange('sort_by', e.target.value)}
                >
                  <option value="name">Nom</option>
                  <option value="created_at">Date de création</option>
                  <option value="base_price">Prix</option>
                </select>
              </div>

              <div className="ps-filter-group">
                <label className="ps-filter-label">Ordre</label>
                <select
                  className="ps-filter-select"
                  value={filters.sort_order}
                  onChange={(e) => handleFilterChange('sort_order', e.target.value)}
                >
                  <option value="asc">Croissant</option>
                  <option value="desc">Décroissant</option>
                </select>
              </div>
            </div>

            {activeFiltersCount > 0 && (
              <button
                type="button"
                className="ps-clear-filters"
                onClick={clearFilters}
              >
                <X size={14} />
                Effacer les filtres
              </button>
            )}
          </div>
        )}
      </div>

      <div className="ps-container">
        {loading ? (
          <div className="ps-loading">
            <Loader2 size={32} className="ps-spinning" />
            <span>Chargement des produits...</span>
          </div>
        ) : products.length === 0 ? (
          <div className="ps-empty">
            <Package size={48} />
            <h4>Aucun produit trouvé</h4>
            <p>Essayez de modifier vos critères de recherche ou créez un nouveau produit.</p>
            <button
              type="button"
              className="ps-create-btn"
              onClick={() => navigate('/produits/nouveau')}
            >
              <Plus size={18} />
              Créer un produit
            </button>
          </div>
        ) : (
          <>
            <div className="ps-products-header">
              <div className="ps-count">
                <span>
                  {pagination.total} produit{pagination.total > 1 ? 's' : ''} trouvé{pagination.total > 1 ? 's' : ''}
                  {pagination.total > pagination.per_page && (
                    <span className="ps-page-indicator">
                      {' '}(page {pagination.current_page} sur {pagination.last_page})
                    </span>
                  )}
                </span>
              </div>
            </div>
            
            <div className={`ps-grid ${viewMode === 'list' ? 'ps-grid-list' : ''}`}>
              {products.map((product, index) => {
                const image = getProductImage(product);

                return (
                  <div
                    key={product.id}
                    className={`ps-card ${viewMode === 'list' ? 'ps-card-list' : ''}`}
                    onClick={() => !disabled && onProductSelect(product)}
                    style={{ animationDelay: `${index * 0.03}s` }}
                  >
                    <div className="ps-card-image">
                      {image ? (
                        <img src={image} alt={product.name} />
                      ) : (
                        <div className="ps-card-no-image">
                          <ImageIcon size={32} />
                        </div>
                      )}
                    </div>
                    
                    <div className="ps-card-content">
                      <h4 className="ps-card-name">{product.name}</h4>
                      
                      <div className="ps-card-meta">
                        <span className="ps-card-category">
                          <Tag size={12} />
                          {product.category?.name || 'Sans catégorie'}
                        </span>
                        {product.subcategory && (
                          <span className="ps-card-subcategory">
                            {product.subcategory.name}
                          </span>
                        )}
                      </div>

                      <div className="ps-card-stats">
                        <div className="ps-stat">
                          <span className="ps-stat-value">
                            {new Intl.NumberFormat('fr-FR').format(product.base_price || 0)}
                          </span>
                          <span className="ps-stat-label">Ar</span>
                        </div>
                      </div>

                      <button 
                        type="button" 
                        className="ps-select-btn"
                        onClick={(e) => {
                          e.stopPropagation();
                          onProductSelect(product);
                        }}
                      >
                        Sélectionner
                        <ChevronDown size={16} />
                      </button>
                    </div>
                  </div>
                );
              })}

              <div 
                className={`ps-card ps-card-add ${viewMode === 'list' ? 'ps-card-list' : ''}`}
                onClick={() => navigate('/produits/nouveau')}
              >
                <div className="ps-add-content">
                  <div className="ps-add-icon">
                    <Plus size={32} />
                  </div>
                  <h4>Nouveau produit</h4>
                  <p>Créer un nouveau produit à réapprovisionner</p>
                </div>
              </div>
            </div>

            {pagination.last_page > 1 && (
              <div className="ps-pagination-container">
                <div className="ps-pagination">
                  <button
                    type="button"
                    className="ps-pagination-btn"
                    onClick={goToFirstPage}
                    disabled={pagination.current_page === 1}
                    title="Première page"
                  >
                    <ChevronsLeft size={16} />
                  </button>
                  
                  <button
                    type="button"
                    className="ps-pagination-btn"
                    onClick={goToPrevPage}
                    disabled={pagination.current_page === 1}
                    title="Page précédente"
                  >
                    <ChevronLeft size={16} />
                  </button>

                  <div className="ps-pagination-pages">
                    {getPageNumbers().map(page => (
                      <button
                        key={page}
                        type="button"
                        className={`ps-pagination-page ${pagination.current_page === page ? 'ps-pagination-active' : ''}`}
                        onClick={() => goToPage(page)}
                      >
                        {page}
                      </button>
                    ))}
                  </div>

                  <button
                    type="button"
                    className="ps-pagination-btn"
                    onClick={goToNextPage}
                    disabled={pagination.current_page === pagination.last_page}
                    title="Page suivante"
                  >
                    <ChevronRight size={16} />
                  </button>
                  
                  <button
                    type="button"
                    className="ps-pagination-btn"
                    onClick={goToLastPage}
                    disabled={pagination.current_page === pagination.last_page}
                    title="Dernière page"
                  >
                    <ChevronsRight size={16} />
                  </button>
                </div>

                <div className="ps-pagination-info">
                  <span>
                    Page {pagination.current_page} sur {pagination.last_page}
                  </span>
                  <span className="ps-pagination-total">
                    ({pagination.total} produit{pagination.total > 1 ? 's' : ''})
                  </span>
                </div>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default ProductSearch;