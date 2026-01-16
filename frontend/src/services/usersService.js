import api from './api';

const usersService = {
  /**
   * Récupère la liste des utilisateurs
   */
  getUsers: async () => {
    const response = await api.get('/users');
    return response.data;
  },

  /**
   * Récupère un utilisateur par son ID
   */
  getUserById: async (id) => {
    const response = await api.get(`/users/${id}`);
    return response.data;
  }
};

export default usersService;