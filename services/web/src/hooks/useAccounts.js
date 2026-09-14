import { useState, useEffect } from 'react';
import accountService from '../services/accountService';

/**
 * Hook personnalisé pour gérer les comptes
 */
const useAccounts = () => {
  const [accounts, setAccounts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchAccounts = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await accountService.getCashAccounts();
      setAccounts(response.data || response);
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement des comptes');
      setAccounts([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAccounts();
  }, []);

  return {
    accounts,
    loading,
    error,
    refetch: fetchAccounts
  };
};

export default useAccounts;
