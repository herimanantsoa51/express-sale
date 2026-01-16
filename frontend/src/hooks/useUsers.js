import { useState, useEffect } from 'react';
import usersService from '../services/usersService';

/**
 * Hook personnalisé pour gérer les utilisateurs
 */
const useUsers = () => {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchUsers = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await usersService.getUsers();
      setUsers(response.data || response);
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement des utilisateurs');
      setUsers([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  return {
    users,
    loading,
    error,
    refetch: fetchUsers
  };
};

export default useUsers;
