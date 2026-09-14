import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { ArrowLeft, Calendar, Save } from 'lucide-react';
import cashCountService from '../../services/cashCountService';
import '../../styles/CashCountForm.css';
import { useParams } from 'react-router-dom';

const DENOMINATIONS = [20000,10000, 5000, 2000, 1000, 500, 200, 100];

const CashCountForm = () => {
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const { id } = useParams();
  const [formData, setFormData] = useState({
    count_date: new Date().toISOString().split('T')[0],
    notes: '',
    denominations: []
  });


  const isEditMode = Boolean(id);

  useEffect(() => {
    if (isEditMode) {
      fetchCashCount();
    } else {
      initializeDenominations();
    }
  }, [isEditMode]);
  

  const fetchCashCount = async () => {
    try {
      setLoading(true);
      const response = await cashCountService.getCashCountById(id);
  
      setFormData({
        count_date: response.data.count_date
          ? response.data.count_date.split('T')[0]
          : '',
        notes: response.data.notes || '',
        denominations: response.data.denominations.map(d => ({
          denomination: d.denomination,
          quantity: d.quantity
        }))
      });
    } catch (error) {
      console.error('Erreur lors du chargement:', error);
    } finally {
      setLoading(false);
    }
  };
  

  const initializeDenominations = () => {
    setFormData({
      count_date: new Date().toISOString().split('T')[0],
      notes: '',
      denominations: DENOMINATIONS.map(denom => ({
        denomination: denom,
        quantity: 0
      }))
    });
  };
  

  const handleQuantityChange = (denomination, value) => {
    const quantity = parseInt(value) || 0;
    setFormData(prev => ({
      ...prev,
      denominations: prev.denominations.map(d =>
        d.denomination === denomination ? { ...d, quantity } : d
      )
    }));
  };

  const calculateTotal = () => {
    return formData.denominations.reduce((sum, d) => sum + (d.denomination * d.quantity), 0);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const validDenominations = formData.denominations.filter(d => d.quantity > 0);
    
    if (validDenominations.length === 0) {
      alert('Veuillez saisir au moins une coupure');
      return;
    }

    try {
      setSubmitting(true);
      const data = {
        count_date: formData.count_date,
        notes: formData.notes,
        denominations: validDenominations
      };

      if (isEditMode) {
        await cashCountService.updateCashCount(id, data);
      } else {
        await cashCountService.storeCashCount(data);
      }

      window.location.href = '/cash-counts';
    } catch (error) {
      console.error('Erreur lors de la sauvegarde:', error);
      alert(error.response?.data?.message || 'Erreur lors de la sauvegarde');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="ccform-loading-container">
        <div className="ccform-spinner"></div>
      </div>
    );
  }

  return (
    <div className="ccform-page">
      <div className="ccform-header">
        <div className="ccform-header-content">
          <button className="ccform-back-button" onClick={() => window.location.href = '/cash-counts'}>
            <ArrowLeft size={20} />
          </button>
          <motion.h1
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
          >
            {isEditMode ? 'Modifier le comptage' : 'Nouveau comptage'}
          </motion.h1>
        </div>
      </div>

      <div className="ccform-content">
        <motion.form
          onSubmit={handleSubmit}
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
        >
          <div className="ccform-section">
            <h2>Informations générales</h2>
            
            <div className="ccform-group">
              <label>Date du comptage</label>
              <div className="ccform-input-with-icon">
                <Calendar size={18} />
                <input
                  type="date"
                  value={formData.count_date}
                  onChange={(e) => setFormData({ ...formData, count_date: e.target.value })}
                  required
                />
              </div>
            </div>

            <div className="ccform-group">
              <label>Notes (optionnel)</label>
              <textarea
                className="ccform-textarea"
                value={formData.notes}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                placeholder="Ajouter une note..."
                rows={3}
              />
            </div>
          </div>

          <div className="ccform-section">
            <div className="ccform-section-header">
              <h2>Coupures</h2>
              <div className="ccform-total-badge">
                Total: {calculateTotal().toLocaleString('fr-FR')} Ar
              </div>
            </div>

            <div className="ccform-denominations-grid">
              {formData.denominations.map((item, index) => (
                <motion.div
                  key={item.denomination}
                  className="ccform-denomination-item"
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: index * 0.02 }}
                >
                  <div className="ccform-denom-label">
                    {item.denomination.toLocaleString('fr-FR')} Ar
                  </div>
                  <input
                    type="number"
                    min="0"
                    className="ccform-denom-input"
                    value={item.quantity}
                    onChange={(e) => handleQuantityChange(item.denomination, e.target.value)}
                    placeholder="0"
                  />
                  <div className="ccform-denom-subtotal">
                    {(item.denomination * item.quantity).toLocaleString('fr-FR')} Ar
                  </div>
                </motion.div>
              ))}
            </div>
          </div>

          <div className="ccform-actions">
            <button
              type="button"
              className="ccform-btn-secondary"
              onClick={() => window.location.href = '/cash-counts'}
            >
              Annuler
            </button>
            <button
              type="submit"
              className="ccform-btn-primary"
              disabled={submitting}
            >
              {submitting ? (
                <div className="ccform-spinner-small"></div>
              ) : (
                <>
                  <Save size={18} />
                  {isEditMode ? 'Mettre à jour' : 'Enregistrer'}
                </>
              )}
            </button>
          </div>
        </motion.form>
      </div>
    </div>
  );
};

export default CashCountForm;