// hooks/useCredits.js
import { useState, useEffect, useCallback } from 'react';
import creditService from '../services/creditService';

const useCredits = (initialFilters = {}) => {
  const [credits, setCredits] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState({
    search: '',
    status: '',
    active_only: false,
    overdue_only: false,
    due_soon_days: '',
    created_by: '',
    sort_by: 'due_date',
    sort_order: 'asc',
    page: 1,
    per_page: 20,
    ...initialFilters
  });

  // Fonction pour charger les crédits
  const fetchCredits = useCallback(async () => {
    setLoading(true);
    setError(null);
    
    try {
      // Nettoyer les filtres vides
      const cleanFilters = Object.entries(filters).reduce((acc, [key, value]) => {
        if (value !== '' && value !== null && value !== undefined && value !== false) {
          acc[key] = value;
        }
        return acc;
      }, {});
      console.log('Fetching immediate sales with filters:', cleanFilters);
      const response = await creditService.getCredits(cleanFilters);
      setCredits(response.data || []);
      setMeta(response.meta || null);
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement des crédits');
      setCredits([]);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  // Charger les crédits au montage et quand les filtres changent
  useEffect(() => {
    fetchCredits();
  }, [fetchCredits]);

  // Fonction pour mettre à jour les filtres
  const updateFilters = useCallback((newFilters) => {
    setFilters(prev => ({ ...prev, ...newFilters, page: 1 }));
  }, []);

  // Fonction pour réinitialiser les filtres
  const resetFilters = useCallback(() => {
    setFilters({
      search: '',
      status: '',
      active_only: false,
      overdue_only: false,
      due_soon_days: '',
      created_by: '',
      sort_by: 'due_date',
      sort_order: 'asc',
      page: 1,
      per_page: 20
    });
  }, []);

  // Fonction pour changer de page
  const changePage = useCallback((page) => {
    setFilters(prev => ({ ...prev, page }));
  }, []);

  // Fonction pour changer le tri
  const changeSort = useCallback((sortBy) => {
    setFilters(prev => ({
      ...prev,
      sort_by: sortBy,
      sort_order: prev.sort_by === sortBy && prev.sort_order === 'asc' ? 'desc' : 'asc'
    }));
  }, []);

  // Fonction pour rafraîchir les données
  const refresh = useCallback(() => {
    fetchCredits();
  }, [fetchCredits]);

  return {
    credits,
    meta,
    loading,
    error,
    filters,
    updateFilters,
    resetFilters,
    changePage,
    changeSort,
    refresh
  };
};

export default useCredits;