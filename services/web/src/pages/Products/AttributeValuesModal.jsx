// AttributeManagerModal.jsx
import React, { useState, useEffect, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import '../../styles/ProductsList.css';
import productService from '../../services/productService';

// ============================================
// MODAL OVERLAY COMPONENT
// ============================================

const ModalOverlay = ({ children, isOpen, onClose }) => {
  if (!isOpen) return null;

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 0.2 }}
      className="modal-overlay"
      onClick={onClose}
    >
      <motion.div
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 20 }}
        transition={{ duration: 0.3, ease: [0.25, 0.1, 0.25, 1] }}
        className="modal-content"
        onClick={(e) => e.stopPropagation()}
      >
        {children}
      </motion.div>
    </motion.div>
  );
};

// ============================================
// VALUE ITEM COMPONENT
// ============================================

const ValueItem = ({ value, onEdit, onDelete, isEditing, onSave, onCancel }) => {
  const [editValue, setEditValue] = useState(value.value);

  const handleSave = () => {
    if (editValue.trim()) {
      onSave({ ...value, value: editValue.trim() });
    }
  };

  if (isEditing) {
    return (
      <motion.div
        initial={{ opacity: 0, height: 0 }}
        animate={{ opacity: 1, height: 'auto' }}
        exit={{ opacity: 0, height: 0 }}
        className="value-item value-item--editing"
      >
        <input
          type="text"
          value={editValue}
          onChange={(e) => setEditValue(e.target.value)}
          className="value-item__input"
          autoFocus
          onKeyDown={(e) => {
            if (e.key === 'Enter') handleSave();
            if (e.key === 'Escape') onCancel();
          }}
        />
        <div className="value-item__actions">
          <button
            onClick={handleSave}
            className="value-item__button value-item__button--save"
          >
            Save
          </button>
          <button
            onClick={onCancel}
            className="value-item__button value-item__button--cancel"
          >
            Cancel
          </button>
        </div>
      </motion.div>
    );
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: -10 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -10 }}
      className="value-item"
    >
      <span className="value-item__text">{value.value}</span>
      <div className="value-item__actions">
        <button
          onClick={() => onEdit(value)}
          className="value-item__button value-item__button--edit"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
          </svg>
        </button>
        <button
          onClick={() => onDelete(value.id)}
          className="value-item__button value-item__button--delete"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M3 6h18" />
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
          </svg>
        </button>
      </div>
    </motion.div>
  );
};

// ============================================
// ATTRIBUTE VALUES MODAL
// ============================================

// AttributeValuesModal.jsx - Version corrigée avec APIs réelles
const AttributeValuesModal = ({ attribute, onClose, onUpdate }) => {
    const [values, setValues] = useState([]);
    const [newValue, setNewValue] = useState('');
    const [editingValue, setEditingValue] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
  
    useEffect(() => {
      loadValues();
    }, [attribute.id]);
  
    const loadValues = async () => {
      try {
        setLoading(true);
        const data = await productService.getAttributeValues(attribute.id);
        setValues(data);
      } catch (err) {
        setError('Failed to load attribute values');
        console.error(err);
      } finally {
        setLoading(false);
      }
    };
  
    const handleEditValue = (value) => {
      setEditingValue(value);
    };
  
    const handleAddValue = async () => {
      if (!newValue.trim() || loading) return;
  
      try {
        setLoading(true);
        setError(null);
        
        const newValueObj = {
          value: newValue.trim(),
          sort_order: values.length + 1
        };
  
        const createdValue = await productService.addAttributeValue(
          attribute.id, 
          newValueObj
        );
  
        setValues(prev => [...prev, createdValue]);
        setNewValue('');
      } catch (err) {
        setError(err.response?.data?.message || 'Failed to add value');
        console.error('Error adding value:', err);
      } finally {
        setLoading(false);
      }
    };
  
    const handleSaveValue = async (updatedValue) => {
      try {
        setLoading(true);
        setError(null);
        
        const savedValue = await productService.updateAttributeValue(
          attribute.id,
          updatedValue.id,
          { value: updatedValue.value }
        );
  
        setValues(prev => prev.map(v => 
          v.id === savedValue.id ? savedValue : v
        ));
        setEditingValue(null);
      } catch (err) {
        setError(err.response?.data?.message || 'Failed to update value');
        console.error('Error updating value:', err);
      } finally {
        setLoading(false);
      }
    };
  
    const handleDeleteValue = async (valueId) => {
      if (!window.confirm('Are you sure you want to delete this value?')) return;
  
      try {
        setLoading(true);
        setError(null);
        
        await productService.deleteAttributeValue(attribute.id, valueId);
        
        setValues(prev => prev.filter(v => v.id !== valueId));
      } catch (err) {
        const message = err.response?.data?.message || 'Failed to delete value';
        setError(message);
        
        if (err.response?.status === 409) {
          alert(message);
        }
        console.error('Error deleting value:', err);
      } finally {
        setLoading(false);
      }
    };
  
    const handleCancelEdit = () => {
      setEditingValue(null);
    };
  
    const handleClose = () => {
      if (onUpdate) {
        onUpdate({ ...attribute, values });
      }
      onClose();
    };
  
    return (
      <ModalOverlay isOpen={true} onClose={handleClose}>
        <div className="values-modal">
          <div className="values-modal__header">
            <div className="values-modal__header-content">
              <h3 className="values-modal__title">
                {attribute.display_name} Values
                <span className="values-modal__subtitle">({attribute.input_type})</span>
              </h3>
              <p className="values-modal__description">
                Manage available values for this attribute
              </p>
            </div>
            <button onClick={handleClose} className="values-modal__close-button">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M18 6L6 18" />
                <path d="M6 6l12 12" />
              </svg>
            </button>
          </div>
  
          <div className="values-modal__content">
            {error && (
              <div className="values-modal__error">
                <p className="values-modal__error-text">{error}</p>
              </div>
            )}
  
            <div className="values-modal__add-section">
              <input
                type="text"
                value={newValue}
                onChange={(e) => setNewValue(e.target.value)}
                placeholder="Add new value..."
                className="values-modal__input"
                onKeyDown={(e) => e.key === 'Enter' && handleAddValue()}
                disabled={loading}
              />
              <button
                onClick={handleAddValue}
                disabled={!newValue.trim() || loading}
                className="values-modal__add-button"
              >
                {loading ? 'Adding...' : 'Add'}
              </button>
            </div>
  
            {loading && values.length === 0 ? (
              <div className="values-modal__loading">
                <div className="values-modal__loading-spinner">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                  </svg>
                </div>
                <p>Loading values...</p>
              </div>
            ) : (
              <div className="values-modal__list">
                <AnimatePresence mode="popLayout">
                  {values.map((value) => (
                    <ValueItem
                      key={value.id}
                      value={value}
                      onEdit={handleEditValue}
                      onDelete={handleDeleteValue}
                      isEditing={editingValue?.id === value.id}
                      onSave={handleSaveValue}
                      onCancel={handleCancelEdit}
                    />
                  ))}
                </AnimatePresence>
  
                {values.length === 0 && !loading && (
                  <div className="values-modal__empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    <p>No values defined yet</p>
                    <p className="values-modal__empty-hint">
                      Add values above to create options for this attribute
                    </p>
                  </div>
                )}
              </div>
            )}
          </div>
  
          <div className="values-modal__footer">
            <button onClick={handleClose} className="values-modal__done-button">
              Done
            </button>
          </div>
        </div>
      </ModalOverlay>
    );
  };

// ============================================
// MAIN ATTRIBUTE MANAGER MODAL
// ============================================

const AttributeManagerModal = ({ isOpen, onClose }) => {
  const [attributes, setAttributes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [pagination, setPagination] = useState({
    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
    perPage: 10
  });
  const [selectedAttribute, setSelectedAttribute] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');

  const loadAttributes = useCallback(async (page = 1) => {
    setLoading(true);
    setError(null);
    try {
      const data = await productService.getAttributeTypes();
      
      // Filtrer les attributs localement (dans un vrai projet, cela devrait être fait côté serveur)
      let filtered = data;
      if (searchTerm.trim()) {
        filtered = data.filter(attr => 
          attr.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
          attr.display_name.toLowerCase().includes(searchTerm.toLowerCase())
        );
      }

      // Simuler la pagination côté client
      const startIndex = (page - 1) * pagination.perPage;
      const paginated = filtered.slice(startIndex, startIndex + pagination.perPage);

      setAttributes(paginated);
      setPagination({
        ...pagination,
        currentPage: page,
        totalPages: Math.ceil(filtered.length / pagination.perPage),
        totalItems: filtered.length
      });
    } catch (err) {
      setError('Failed to load attributes');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [searchTerm, pagination.perPage]);

  useEffect(() => {
    if (isOpen) {
      loadAttributes();
    }
  }, [isOpen, searchTerm]);

  const handleAttributeClick = (attribute) => {
    // Ouvrir le modal des valeurs seulement pour les types select/multiselect
    if (['select', 'multiselect'].includes(attribute.input_type)) {
      setSelectedAttribute(attribute);
    }
  };

  const handlePageChange = (page) => {
    if (page >= 1 && page <= pagination.totalPages) {
      loadAttributes(page);
    }
  };

  const getInputTypeBadge = (type) => {
    const types = {
      select: { label: 'Select', color: 'var(--primary)' },
      multiselect: { label: 'Multi', color: 'var(--success)' },
      text: { label: 'Text', color: 'var(--warning)' },
      number: { label: 'Number', color: 'var(--info)' }
    };
    return types[type] || { label: type, color: 'var(--text-secondary)' };
  };

  if (selectedAttribute) {
    return (
      <AttributeValuesModal
        attribute={selectedAttribute}
        onClose={() => setSelectedAttribute(null)}
        onUpdate={(updatedAttr) => {
          setAttributes(prev => prev.map(a => 
            a.id === updatedAttr.id ? updatedAttr : a
          ));
        }}
      />
    );
  }

  return (
    <ModalOverlay isOpen={isOpen} onClose={onClose}>
      <div className="attribute-manager-modal">
        <div className="attribute-manager-modal__header">
          <div className="attribute-manager-modal__header-content">
            <h2 className="attribute-manager-modal__title">Attribute Types</h2>
            <p className="attribute-manager-modal__subtitle">
              Manage product attributes and their values
            </p>
          </div>
          <button onClick={onClose} className="attribute-manager-modal__close-button">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M18 6L6 18" />
              <path d="M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div className="attribute-manager-modal__search">
          <svg className="attribute-manager-modal__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input
            type="text"
            placeholder="Search attributes..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="attribute-manager-modal__search-input"
          />
        </div>

        {error && (
          <div className="attribute-manager-modal__error">
            <p className="attribute-manager-modal__error-text">{error}</p>
          </div>
        )}

        <div className="attribute-manager-modal__content">
          {loading ? (
            <div className="attribute-manager-modal__skeleton-list">
              {[...Array(5)].map((_, i) => (
                <div key={i} className="attribute-manager-modal__skeleton-item">
                  <div className="attribute-manager-modal__skeleton-line" />
                  <div className="attribute-manager-modal__skeleton-line" />
                </div>
              ))}
            </div>
          ) : attributes.length === 0 ? (
            <div className="attribute-manager-modal__empty">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
              </svg>
              <h3 className="attribute-manager-modal__empty-title">No attributes found</h3>
              <p className="attribute-manager-modal__empty-text">
                {searchTerm ? 'Try a different search term' : 'Start by creating your first attribute'}
              </p>
            </div>
          ) : (
            <motion.div layout className="attribute-manager-modal__list">
              <AnimatePresence mode="popLayout">
                {attributes.map((attribute) => {
                  const badge = getInputTypeBadge(attribute.input_type);
                  const hasValues = attribute.values && attribute.values.length > 0;
                  const isSelectType = ['select', 'multiselect'].includes(attribute.input_type);

                  return (
                    <motion.div
                      key={attribute.id}
                      initial={{ opacity: 0, y: 8 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: -8 }}
                      transition={{ duration: 0.2 }}
                      className={`attribute-manager-modal__item ${isSelectType ? 'attribute-manager-modal__item--selectable' : ''}`}
                      onClick={() => isSelectType && handleAttributeClick(attribute)}
                    >
                      <div className="attribute-manager-modal__item-content">
                        <div className="attribute-manager-modal__item-header">
                          <h4 className="attribute-manager-modal__item-title">
                            {attribute.display_name}
                          </h4>
                          <span 
                            className="attribute-manager-modal__item-badge"
                            style={{ backgroundColor: badge.color }}
                          >
                            {badge.label}
                          </span>
                        </div>
                        <p className="attribute-manager-modal__item-name">{attribute.name}</p>
                        
                        {hasValues && (
                          <div className="attribute-manager-modal__item-values">
                            <span className="attribute-manager-modal__item-values-label">
                              {attribute.values.length} value(s):
                            </span>
                            <div className="attribute-manager-modal__item-values-list">
                              {attribute.values.slice(0, 3).map((value, idx) => (
                                <span key={value.id} className="attribute-manager-modal__item-value">
                                  {value.value}
                                  {idx < 2 && idx < attribute.values.length - 1 && ', '}
                                </span>
                              ))}
                              {attribute.values.length > 3 && (
                                <span className="attribute-manager-modal__item-value-more">
                                  +{attribute.values.length - 3} more
                                </span>
                              )}
                            </div>
                          </div>
                        )}

                        {!hasValues && isSelectType && (
                          <div className="attribute-manager-modal__item-empty">
                            <span className="attribute-manager-modal__item-empty-text">
                              No values defined
                            </span>
                          </div>
                        )}
                      </div>

                      {isSelectType && (
                        <div className="attribute-manager-modal__item-arrow">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="m9 18 6-6-6-6" />
                          </svg>
                        </div>
                      )}
                    </motion.div>
                  );
                })}
              </AnimatePresence>
            </motion.div>
          )}
        </div>

        {!loading && attributes.length > 0 && (
          <div className="attribute-manager-modal__pagination">
            <button
              onClick={() => handlePageChange(pagination.currentPage - 1)}
              disabled={pagination.currentPage === 1}
              className={`attribute-manager-modal__pagination-button ${pagination.currentPage === 1 ? 'attribute-manager-modal__pagination-button--disabled' : ''}`}
            >
              Previous
            </button>
            
            <div className="attribute-manager-modal__pagination-info">
              <span className="attribute-manager-modal__pagination-page">
                Page {pagination.currentPage} of {pagination.totalPages}
              </span>
              <span className="attribute-manager-modal__pagination-total">
                ({pagination.totalItems} total)
              </span>
            </div>

            <button
              onClick={() => handlePageChange(pagination.currentPage + 1)}
              disabled={pagination.currentPage === pagination.totalPages}
              className={`attribute-manager-modal__pagination-button ${pagination.currentPage === pagination.totalPages ? 'attribute-manager-modal__pagination-button--disabled' : ''}`}
            >
              Next
            </button>
          </div>
        )}

        <div className="attribute-manager-modal__footer">
          <button onClick={onClose} className="attribute-manager-modal__done-button">
            Done
          </button>
        </div>
      </div>
    </ModalOverlay>
  );
};

export default AttributeManagerModal;