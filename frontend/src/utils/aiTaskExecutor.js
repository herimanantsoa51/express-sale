import api from '../services/api';
import { toast } from 'react-toastify';

const safeOpen = (url, newTab = true) => {
  if (!url) return;
  if (newTab) {
    window.open(url, '_blank', 'noopener,noreferrer');
  } else {
    window.location.href = url;
  }
};

const normalizePath = (value) => {
  if (!value || typeof value !== 'string') return null;
  const trimmed = value.trim();
  if (!trimmed) return null;
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    return trimmed;
  }
  return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
};

const ROUTE_ALIASES = {
  '/products': '/produits',
  '/product': '/produits',
  '/suppliers': '/fournisseurs',
  '/supplier': '/fournisseurs',
  '/settings': '/parametres',
  '/users': '/utilisateurs',
  '/sales': '/ventes',
  '/expenses': '/depenses',
  '/statistics': '/statistiques',
  '/stock-movements': '/mouvements-stock',
  '/shipments': '/transitaires',
  '/replenishments': '/reapprovisionnements',
  '/customers': '/clients',
  '/ai-providers': '/parametres/ai-providers',
  '/ai-tasks': '/parametres/ai-tasks',
};

const resolveRoute = (rawPath) => {
  const path = normalizePath(rawPath);
  if (!path || path.startsWith('http://') || path.startsWith('https://')) {
    return path;
  }
  if (ROUTE_ALIASES[path]) {
    return ROUTE_ALIASES[path];
  }
  const segments = path.split('/').filter(Boolean);
  if (segments.length === 0) return '/';
  const base = `/${segments[0]}`;
  const mappedBase = ROUTE_ALIASES[base] || base;
  if (mappedBase === base) {
    return path;
  }
  return [mappedBase, ...segments.slice(1)].join('/').replace(/\/+/g, '/');
};

export const executeAiTasks = async (tasks, options = {}) => {
  const {
    navigate,
    handlers = {},
    apiClient = api,
  } = options;

  const results = [];

  for (const task of tasks || []) {
    const type = task?.type;
    const action = task?.action;
    const payload = task?.payload || {};

    try {
      const customHandler = handlers?.[type]?.[action];
      if (customHandler) {
        // eslint-disable-next-line no-await-in-loop
        const res = await customHandler(payload, task);
        results.push({ task, status: 'executed', result: res });
        continue;
      }

      if (type === 'ui_action') {
        if (action === 'redirect' && navigate) {
          const path = resolveRoute(payload.path || payload.route || payload.to || payload.page);
          if (path && (path.startsWith('http://') || path.startsWith('https://'))) {
            safeOpen(path, payload.new_tab !== false);
            results.push({ task, status: 'executed' });
            continue;
          }
          navigate(path || '/', { replace: !!payload.replace });
          results.push({ task, status: 'executed' });
          continue;
        }
        if (action === 'open_url') {
          safeOpen(payload.url, payload.new_tab !== false);
          results.push({ task, status: 'executed' });
          continue;
        }
        if (action === 'set_field' && payload.selector) {
          const el = document.querySelector(payload.selector);
          if (el) {
            const value = payload.value ?? '';
            el.value = value;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
            results.push({ task, status: 'executed' });
            continue;
          }
          results.push({ task, status: 'skipped', reason: 'selector introuvable' });
          continue;
        }
        if (action === 'focus_field' && payload.selector) {
          const el = document.querySelector(payload.selector);
          if (el && typeof el.focus === 'function') {
            el.focus();
            results.push({ task, status: 'executed' });
            continue;
          }
          results.push({ task, status: 'skipped', reason: 'selector introuvable' });
          continue;
        }
        if (action === 'toast') {
          const level = payload.level || 'info';
          const message = payload.message || 'Action';
          if (toast[level]) {
            toast[level](message);
          } else {
            toast.info(message);
          }
          results.push({ task, status: 'executed' });
          continue;
        }
        results.push({ task, status: 'skipped', reason: 'ui_action non supportée' });
        continue;
      }

      if (type === 'data_action') {
        if (action === 'autosave' && payload.endpoint && payload.data) {
          const method = (payload.method || 'post').toLowerCase();
          const request = apiClient[method] || apiClient.post;
          const res = await request(payload.endpoint, payload.data);
          results.push({ task, status: 'executed', result: res?.data });
          continue;
        }
        if (action === 'upsert' && payload.endpoint && payload.data) {
          const method = (payload.method || 'put').toLowerCase();
          const request = apiClient[method] || apiClient.put;
          const res = await request(payload.endpoint, payload.data);
          results.push({ task, status: 'executed', result: res?.data });
          continue;
        }
        if (action === 'validate') {
          const errors = Array.isArray(payload.errors) ? payload.errors : [];
          if (errors.length > 0) {
            toast.error(errors.join(' | '));
            results.push({ task, status: 'executed', result: { errors } });
            continue;
          }
          if (payload.message) {
            toast.info(payload.message);
            results.push({ task, status: 'executed' });
            continue;
          }
          results.push({ task, status: 'skipped', reason: 'validate sans erreurs' });
          continue;
        }
        if (action === 'fetch' && payload.endpoint) {
          const res = await apiClient.get(payload.endpoint, { params: payload.params || {} });
          results.push({ task, status: 'executed', result: res?.data });
          continue;
        }
        results.push({ task, status: 'skipped', reason: 'data_action non supportée' });
        continue;
      }

      if (type === 'insight' || type === 'advisor') {
        const text = payload.text || payload.message || payload.summary;
        if (text) {
          toast.info(text);
          results.push({ task, status: 'executed' });
          continue;
        }
        results.push({ task, status: 'skipped', reason: 'insight/advisor sans message' });
        continue;
      }

      results.push({ task, status: 'skipped', reason: 'type inconnu' });
    } catch (error) {
      results.push({ task, status: 'failed', error });
    }
  }

  return results;
};
