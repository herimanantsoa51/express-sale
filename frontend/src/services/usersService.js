import api from './api';

const usersService = {
  /**
   * Récupère la liste des utilisateurs
   */
  getUsers: async () => {
    const response = await api.get('/users');
    return response.data;
  },

  getAllUsers: async () => {
    const response = await api.get('/users/all');
    return response.data;
  },
  /**
   * Crée un nouvel utilisateur
   */
  createUser: async (userData) => {
    const response = await api.post('/users', userData);
    return response.data;
  },
  /**
   * Met à jour un utilisateur existant
   */
  updateUser: async (id, userData) => {
    const response = await api.put(`/users/${id}`, userData);
    return response.data;
  },
  updateUserStatus: async (id) => {
    const response = await api.patch(`/users/${id}/toggle`);
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