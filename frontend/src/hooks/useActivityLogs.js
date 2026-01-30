import { useState, useEffect, useCallback } from 'react';
import activityLogsService from '../services/activityLogsService';

/**
 * Hook personnalisé pour gérer les logs d'activité
 */
const useActivityLogs = (initialParams = {}) => {
  const [logs, setLogs] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState(initialParams);

  const fetchLogs = useCallback(async (params = filters) => {
    setLoading(true);
    setError(null);
    try {
      const response = await activityLogsService.getLogs(params);
      setLogs(response.data || []);
      setPagination({
        current_page: response.current_page,
        last_page: response.last_page,
        per_page: response.per_page,
        total: response.total,
        from: response.from,
        to: response.to
      });
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement des logs');
      setLogs([]);
      setPagination(null);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    fetchLogs();
  }, [fetchLogs]);

  const updateFilters = (newFilters) => {
    setFilters(prev => ({ ...prev, ...newFilters }));
  };

  const goToPage = (page) => {
    fetchLogs({ ...filters, page });
  };

  return {
    logs,
    pagination,
    loading,
    error,
    filters,
    updateFilters,
    goToPage,
    refetch: fetchLogs
  };
};

export default useActivityLogs;