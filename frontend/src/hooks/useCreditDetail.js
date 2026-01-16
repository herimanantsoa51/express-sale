// hooks/useCreditDetail.js
import { useState, useEffect, useCallback } from 'react';
import creditService from '../services/creditService';

/**
 * Hook pour gérer le détail d'un crédit
 */
const useCreditDetail = (creditId) => {
  const [credit, setCredit] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchCreditDetail = useCallback(async () => {
    if (!creditId) return;
    
    setLoading(true);
    setError(null);
    
    try {

      const response = await creditService.getCreditDetail(creditId);
      setCredit(response.data);
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement du crédit');
      setCredit(null);
    } finally {
      setLoading(false);
    }
  }, [creditId]);

  useEffect(() => {
    fetchCreditDetail();
  }, [fetchCreditDetail]);

  const payInstallment = async (installmentId, paymentData) => {
    try {
      await creditService.payInstallment(creditId, installmentId, paymentData);
      await fetchCreditDetail();
      return { success: true };
    } catch (err) {
      return { 
        success: false, 
        error: err.message || 'Erreur lors du paiement' 
      };
    }
  };

  return {
    credit,
    loading,
    error,
    refetch: fetchCreditDetail,
    payInstallment
  };
};

export default useCreditDetail;