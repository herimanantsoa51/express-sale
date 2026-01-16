import { useState, useEffect, useCallback } from 'react';
import reservationsService from '../services/reservationsService';

/**
 * Hook personnalisé pour gérer les réservations
 */
const useReservations = () => {
  const [reservations, setReservations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [meta, setMeta] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  });
  const [filters, setFilters] = useState({
    status: '',
    search: '',
    reservation_date_from: '',
    reservation_date_to: '',
    expiry_date_from: '',
    expiry_date_to: '',
    active_only: false,
    expired_only: false,
    sort_by: 'reservation_date',
    sort_order: 'desc',
    per_page: 15,
    page: 1
  });

  // Nettoyer les filtres avant envoi (enlever les valeurs vides)
  const cleanFilters = (filters) => {
    const cleaned = {};
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== '' && value !== null && value !== undefined && value !== false) {
        cleaned[key] = value;
      }
    });
    return cleaned;
  };

  // Fetch reservations
  const fetchReservations = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const cleanedFilters = cleanFilters(filters);
      const response = await reservationsService.getReservations(cleanedFilters);
      setReservations(response.data || []);
      setMeta(response.meta || { current_page: 1, last_page: 1, per_page: 15, total: 0 });
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement');
      setReservations([]);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    fetchReservations();
  }, [fetchReservations]);

  const updateFilters = (newFilters) => {
    setFilters(prev => ({ ...prev, ...newFilters, page: 1 }));
  };

  const resetFilters = () => {
    setFilters({
      status: '',
      search: '',
      reservation_date_from: '',
      reservation_date_to: '',
      expiry_date_from: '',
      expiry_date_to: '',
      active_only: false,
      expired_only: false,
      sort_by: 'reservation_date',
      sort_order: 'desc',
      per_page: 15,
      page: 1
    });
  };

  const changePage = (page) => {
    setFilters(prev => ({ ...prev, page }));
  };

  return {
    reservations,
    loading,
    error,
    meta,
    filters,
    updateFilters,
    resetFilters,
    changePage,
    refetch: fetchReservations
  };
};

export default useReservations;
