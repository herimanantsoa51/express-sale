import api from './api';

const aiProviderService = {
  index: async () => {
    const response = await api.get('/ai-providers');
    return response.data;
  },

  show: async (id) => {
    const response = await api.get(`/ai-providers/${id}`);
    return response.data;
  },

  store: async (data) => {
    const response = await api.post('/ai-providers', data);
    return response.data;
  },

  update: async (id, data) => {
    const response = await api.put(`/ai-providers/${id}`, data);
    return response.data;
  },

  destroy: async (id) => {
    const response = await api.delete(`/ai-providers/${id}`);
    return response.data;
  },

  testConnection: async (id) => {
    const response = await api.post(`/ai-providers/${id}/test`);
    return response.data;
  },

  setDefault: async (id) => {
    const response = await api.post(`/ai-providers/${id}/set-default`);
    return response.data;
  },

  resetUsage: async (id) => {
    const response = await api.post(`/ai-providers/${id}/reset-usage`);
    return response.data;
  },

  refreshModels: async (id) => {
    const response = await api.post(`/ai-providers/${id}/refresh-models`);
    return response.data;
  },

  definitions: async () => {
    const response = await api.get('/ai-providers/definitions');
    return response.data;
  },
};

export default aiProviderService;
