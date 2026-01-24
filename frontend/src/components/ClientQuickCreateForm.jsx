/* ============================================
   CLIENT QUICK CREATE FORM - Création rapide client
   ============================================ */

import React, { useState } from 'react';
import './ClientQuickCreateForm.css';

const ClientQuickCreateForm = ({ onSuccess, onClose }) => {
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    address: '',
    credit_limit: '',
    notes: '',
    
  });

  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [submitError, setSubmitError] = useState(null);

  const validateForm = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Le nom est requis';
    }

    if (formData.phone && !/^[\d\s+()-]+$/.test(formData.phone)) {
      newErrors.phone = 'Format de téléphone invalide';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    if (errors[name]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    try {
      setLoading(true);
      setSubmitError(null);

      const payload = {
        name: formData.name.trim(),
        phone: formData.phone.trim() || null,
        address: formData.address.trim() || null,
        credit_limit: formData.credit_limit ? parseFloat(formData.credit_limit) : 0,
        notes: formData.notes.trim() || null,
        is_active: true,
        is_extra_customer: true
      };

      const response = await fetch(`${import.meta.env.VITE_API_URL || 'http://localhost:8000/api'}/customers`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify(payload)
      });

      if (!response.ok) {
        throw new Error('Erreur lors de la création du client');
      }

      const data = await response.json();
      onSuccess(data.data);
    } catch (err) {
      setSubmitError(err.message || 'Une erreur est survenue');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="client-quick-modal-overlay" onClick={onClose}>
      <div className="client-quick-modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="client-quick-modal-header">
          <h2>Nouveau client</h2>
          <button className="client-quick-modal-close" onClick={onClose} aria-label="Fermer">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M5 5L15 15M5 15L15 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
            </svg>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="client-quick-form">
          {submitError && (
            <div className="client-quick-error-banner">
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                <circle cx="9" cy="9" r="7" stroke="currentColor" strokeWidth="1.5"/>
                <path d="M9 5V9M9 12V12.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
              </svg>
              <span>{submitError}</span>
            </div>
          )}

          <div className="client-quick-form-group">
            <label htmlFor="name" className="client-quick-label required">
              Nom du client
            </label>
            <input
              type="text"
              id="name"
              name="name"
              value={formData.name}
              onChange={handleChange}
              className={`client-quick-input ${errors.name ? 'input-error' : ''}`}
              placeholder="Jean Dupont"
              autoFocus
            />
            {errors.name && <span className="client-quick-error">{errors.name}</span>}
          </div>

          <div className="client-quick-form-row">
            <div className="client-quick-form-group">
              <label htmlFor="phone" className="client-quick-label">
                Téléphone
              </label>
              <input
                type="tel"
                id="phone"
                name="phone"
                value={formData.phone}
                onChange={handleChange}
                className={`client-quick-input ${errors.phone ? 'input-error' : ''}`}
                placeholder="+261 34 00 000 00"
              />
              {errors.phone && <span className="client-quick-error">{errors.phone}</span>}
            </div>

            <div className="client-quick-form-group">
              <label htmlFor="credit_limit" className="client-quick-label">
                Limite de crédit
              </label>
              <input
                type="number"
                id="credit_limit"
                name="credit_limit"
                value={formData.credit_limit}
                onChange={handleChange}
                className="client-quick-input"
                placeholder="0 Ar"
                min="0"
                step="0.01"
              />
            </div>
          </div>

          <div className="client-quick-form-group">
            <label htmlFor="address" className="client-quick-label">
              Adresse
            </label>
            <input
              type="text"
              id="address"
              name="address"
              value={formData.address}
              onChange={handleChange}
              className="client-quick-input"
              placeholder="Lot IVA 123, Antananarivo"
            />
          </div>

          <div className="client-quick-form-actions">
            <button type="button" className="client-quick-btn-secondary" onClick={onClose} disabled={loading}>
              Annuler
            </button>
            <button type="submit" className="client-quick-btn-primary" disabled={loading}>
              {loading ? (
                <>
                  <svg className="client-quick-spinner" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <circle cx="8" cy="8" r="6" stroke="currentColor" strokeWidth="2" strokeDasharray="30" strokeDashoffset="10"/>
                  </svg>
                  Création...
                </>
              ) : (
                'Créer le client'
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ClientQuickCreateForm;
