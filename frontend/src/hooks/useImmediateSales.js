// ============================================
// src/hooks/useImmediateSales.js
// Hook personnalisé pour gérer les ventes immédiates
// ============================================

import { useState, useEffect, useCallback } from 'react';
import immediateSaleService from '../services/immediateSaleService';

const useImmediateSales = (initialFilters = {}) => {
  const [sales, setSales] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState({
    per_page: 10,
    page: 1,
    ...initialFilters
  });

  // Fonction pour charger les ventes
  const fetchSales = useCallback(async () => {
    setLoading(true);
    setError(null);
    
    try {

      const response = await immediateSaleService.getList(filters);
      setSales(response.data);
      setPagination(response.meta);
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement des ventes');
      setSales([]);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  // Charger les ventes au montage et quand les filtres changent
  useEffect(() => {
    fetchSales();
  }, [fetchSales]);

  // Fonction pour mettre à jour les filtres
  const updateFilters = useCallback((newFilters) => {
    setFilters(prev => ({ ...prev, ...newFilters, page: 1 }));
  }, []);

  // Fonction pour changer de page
  const changePage = useCallback((page) => {
    setFilters(prev => ({ ...prev, page }));
  }, []);

  // Fonction pour rafraîchir les données
  const refresh = useCallback(() => {
    fetchSales();
  }, [fetchSales]);

  return {
    sales,
    pagination,
    loading,
    error,
    filters,
    updateFilters,
    changePage,
    refresh
  };
};

export default useImmediateSales;