import { useState, useEffect } from 'react';
import companyInfoService from '../services/companyInfoService';

/**
 * Hook personnalisé pour récupérer les informations de l'entreprise
 */
const useCompanyInfo = () => {
  const [companyInfo, setCompanyInfo] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchCompanyInfo = async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await companyInfoService.index();
      setCompanyInfo(data);
    } catch (err) {
      console.error('Error fetching company info:', err);
      setError(err.message || 'Erreur lors du chargement des informations');
      // Fallback data
      setCompanyInfo({
        name: 'Boutique',
        logo_path: null
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCompanyInfo();
  }, []);

  return {
    companyInfo,
    loading,
    error,
    refetch: fetchCompanyInfo
  };
};

export default useCompanyInfo;