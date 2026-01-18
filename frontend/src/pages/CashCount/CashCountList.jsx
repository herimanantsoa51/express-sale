import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Plus, Calendar, Filter, ChevronRight } from 'lucide-react';
import cashCountService from '../../services/cashCountService';
import '../../styles/CashCountList.css';

const CashCountList = () => {
  const [cashCounts, setCashCounts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    from_date: '',
    to_date: '',
    search: ''
  });
  const [showFilters, setShowFilters] = useState(false);
  const [meta, setMeta] = useState(null);

  useEffect(() => {
    fetchCashCounts();
  }, [filters.from_date, filters.to_date]);

  const fetchCashCounts = async () => {
    try {
      setLoading(true);
      const params = {};
      if (filters.from_date) params.from_date = filters.from_date;
      if (filters.to_date) params.to_date = filters.to_date;
      
      const response = await cashCountService.getCashCounts(params);
      setCashCounts(response.data);
      setMeta(response.meta);
    } catch (error) {
      console.error('Erreur lors du chargement:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric'
    });
  };

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount);
  };

  return (
    <div className="cclist-page">
      <div className="cclist-header">
        <div className="cclist-header-content">
          <motion.h1
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.4 }}
          >
            Comptages de caisse
          </motion.h1>
          <motion.button
            className="cclist-btn-primary"
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={() => window.location.href = '/comptages/nouveau'}
          >
            <Plus size={20} />
            Nouveau comptage
          </motion.button>
        </div>
      </div>

      <div className="cclist-content">
        <motion.div
          className="cclist-filters-section"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
        >
          <button
            className="cclist-filter-toggle"
            onClick={() => setShowFilters(!showFilters)}
          >
            <Filter size={18} />
            Filtres
          </button>

          {showFilters && (
            <motion.div
              className="cclist-filters-content"
              initial={{ height: 0, opacity: 0 }}
              animate={{ height: 'auto', opacity: 1 }}
              exit={{ height: 0, opacity: 0 }}
            >
              <div className="cclist-filter-group">
                <label>Date de début</label>
                <div className="cclist-input-with-icon">
                  <Calendar size={18} />
                  <input
                    type="date"
                    value={filters.from_date}
                    onChange={(e) => setFilters({ ...filters, from_date: e.target.value })}
                  />
                </div>
              </div>

              <div className="cclist-filter-group">
                <label>Date de fin</label>
                <div className="cclist-input-with-icon">
                  <Calendar size={18} />
                  <input
                    type="date"
                    value={filters.to_date}
                    onChange={(e) => setFilters({ ...filters, to_date: e.target.value })}
                  />
                </div>
              </div>

              <button
                className="cclist-btn-secondary"
                onClick={() => {
                  setFilters({ from_date: '', to_date: '', search: '' });
                  fetchCashCounts();
                }}
              >
                Réinitialiser
              </button>
            </motion.div>
          )}
        </motion.div>

        {loading ? (
          <div className="cclist-loading-state">
            <div className="cclist-spinner"></div>
          </div>
        ) : (
          <motion.div
            className="cclist-grid"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.2 }}
          >
            {cashCounts.length === 0 ? (
              <div className="cclist-empty-state">
                <p>Aucun comptage trouvé</p>
              </div>
            ) : (
              cashCounts.map((count, index) => (
                <motion.div
                  key={count.id}
                  className="cclist-card"
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: index * 0.05 }}
                  whileHover={{ y: -4 }}
                  onClick={() => window.location.href = `/comptages/${count.id}`}
                >
                  <div className="cclist-card-header">
                    <div className="cclist-date-badge">
                      {formatDate(count.count_date)}
                    </div>
                    <ChevronRight size={20} className="cclist-chevron" />
                  </div>

                  <div className="cclist-card-body">
                    <div className="cclist-amount">
                      {formatAmount(count.total_amount)} Ar
                    </div>
                    {count.notes && (
                      <p className="cclist-notes">{count.notes}</p>
                    )}
                  </div>

                  <div className="cclist-card-footer">
                    <span className="cclist-creator">Par {count.creator?.name}</span>
                    <span className="cclist-timestamp">
                      {new Date(count.created_at).toLocaleDateString('fr-FR')}
                    </span>
                  </div>
                </motion.div>
              ))
            )}
          </motion.div>
        )}

        {meta && meta.last_page > 1 && (
          <div className="cclist-pagination">
            <button disabled={meta.current_page === 1}>Précédent</button>
            <span>Page {meta.current_page} sur {meta.last_page}</span>
            <button disabled={meta.current_page === meta.last_page}>Suivant</button>
          </div>
        )}
      </div>
    </div>
  );
};

export default CashCountList;