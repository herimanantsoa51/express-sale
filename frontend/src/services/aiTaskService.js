import api from './api';

const aiTaskService = {
  create: async (data) => {
    const response = await api.post('/ai/tasks', data);
    return response.data;
  },

  get: async (id) => {
    const response = await api.get(`/ai/tasks/${id}`);
    return response.data;
  },

  list: async (params) => {
    const response = await api.get('/ai/tasks', { params });
    return response.data;
  },

  markExecuted: async (id) => {
    const response = await api.patch(`/ai/tasks/${id}/executed`);
    return response.data;
  },

  chatSync: async (data) => {
    const response = await api.post('/ai/chat-sync', data);
    return response.data;
  },
  langgraphQuery: async (data) => {
    const response = await api.post('/ai/langgraph/query', data);
    return response.data;
  },

  ragStatus: async () => {
    const response = await api.get('/ai/rag/status');
    return response.data;
  },

  ragRefresh: async () => {
    const response = await api.post('/ai/rag/refresh');
    return response.data;
  },

  ragManifest: async () => {
    const response = await api.get('/ai/rag/manifest');
    return response.data;
  },
};

export default aiTaskService;
