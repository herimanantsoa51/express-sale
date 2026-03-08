import React, { useState, useEffect, useCallback } from 'react';
import {
  Brain, Plus, Trash2, Star, Zap, Eye, EyeOff, Check, X, Loader2,
  ChevronDown, ChevronRight, RotateCcw, ArrowLeft, Shield, Globe,
  Key, Activity, AlertTriangle, Server, ExternalLink, Settings2
} from 'lucide-react';
import { toast } from 'react-toastify';
import { useNavigate } from 'react-router-dom';
import aiProviderService from '../../services/aiProviderService';
import '../../styles/AiProviderConfig.css';

const PROVIDER_ICONS = {
  openai: (
    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
      <path d="M22.282 9.821a5.985 5.985 0 0 0-.516-4.91 6.046 6.046 0 0 0-6.51-2.9A6.065 6.065 0 0 0 4.981 4.18a5.985 5.985 0 0 0-3.998 2.9 6.046 6.046 0 0 0 .743 7.097 5.98 5.98 0 0 0 .51 4.911 6.051 6.051 0 0 0 6.515 2.9A5.985 5.985 0 0 0 13.26 24a6.056 6.056 0 0 0 5.772-4.206 5.99 5.99 0 0 0 3.997-2.9 6.056 6.056 0 0 0-.747-7.073zM13.26 22.43a4.476 4.476 0 0 1-2.876-1.04l.141-.081 4.779-2.758a.795.795 0 0 0 .392-.681v-6.737l2.02 1.168a.071.071 0 0 1 .038.052v5.583a4.504 4.504 0 0 1-4.494 4.494zM3.6 18.304a4.47 4.47 0 0 1-.535-3.014l.142.085 4.783 2.759a.771.771 0 0 0 .78 0l5.843-3.369v2.332a.08.08 0 0 1-.033.062L9.74 19.95a4.5 4.5 0 0 1-6.14-1.646zM2.34 7.896a4.485 4.485 0 0 1 2.366-1.973V11.6a.766.766 0 0 0 .388.676l5.815 3.355-2.02 1.168a.076.076 0 0 1-.071 0l-4.83-2.786A4.504 4.504 0 0 1 2.34 7.872zm16.597 3.855l-5.833-3.387L15.119 7.2a.076.076 0 0 1 .071 0l4.83 2.791a4.494 4.494 0 0 1-.676 8.105v-5.678a.79.79 0 0 0-.407-.667zm2.01-3.023l-.141-.085-4.774-2.782a.776.776 0 0 0-.785 0L9.409 9.23V6.897a.066.066 0 0 1 .028-.061l4.83-2.787a4.5 4.5 0 0 1 6.68 4.66zm-12.64 4.135l-2.02-1.164a.08.08 0 0 1-.038-.057V6.075a4.5 4.5 0 0 1 7.375-3.453l-.142.08L8.704 5.46a.795.795 0 0 0-.393.681zm1.097-2.365l2.602-1.5 2.607 1.5v2.999l-2.597 1.5-2.607-1.5z" />
    </svg>
  ),
  github: (
    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
      <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12" />
    </svg>
  ),
  anthropic: (
    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
      <path d="M13.827 3.52h3.603L24 20.48h-3.603l-6.57-16.96zm-7.258 0h3.767L16.906 20.48h-3.674l-1.633-4.327H6.066l-1.63 4.327H.862L6.57 3.52zm1.04 3.872L5.2 13.727h4.822L7.61 7.392z" />
    </svg>
  ),
  google: (
    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
      <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" /><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" /><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" /><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
    </svg>
  ),
  mistral: (
    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
      <rect x="0" y="2" width="5" height="5" /><rect x="9.5" y="2" width="5" height="5" /><rect x="19" y="2" width="5" height="5" /><rect x="0" y="7" width="5" height="5" /><rect x="4.75" y="7" width="5" height="5" /><rect x="9.5" y="7" width="5" height="5" /><rect x="19" y="7" width="5" height="5" /><rect x="0" y="12" width="5" height="5" /><rect x="9.5" y="12" width="5" height="5" /><rect x="14.25" y="12" width="5" height="5" /><rect x="19" y="12" width="5" height="5" />
    </svg>
  ),
  groq: <Zap size={20} />,
  cohere: <Brain size={20} />,
  custom: <Server size={20} />,
};

const AiProviderConfig = () => {
  const navigate = useNavigate();
  const [configs, setConfigs] = useState([]);
  const [providers, setProviders] = useState({});
  const [isLoading, setIsLoading] = useState(true);
  const [expandedId, setExpandedId] = useState(null);
  const [showAddForm, setShowAddForm] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [testingId, setTestingId] = useState(null);
  const [refreshingId, setRefreshingId] = useState(null);
  const [deletingId, setDeletingId] = useState(null);
  const [savingId, setSavingId] = useState(null);
  const [showKeyFor, setShowKeyFor] = useState(null);
  const [testResults, setTestResults] = useState({});
  const [loadingProviders, setLoadingProviders] = useState(false);

  // Form state
  const [form, setForm] = useState({
    provider: '',
    label: '',
    api_key: '',
    base_url: '',
    default_model: '',
    is_active: true,
    is_default: false,
    rate_limit_rpm: '',
    rate_limit_rpd: '',
    daily_token_limit: '',
  });

  const loadData = useCallback(async () => {
    try {
      setIsLoading(true);
      const data = await aiProviderService.index();
      setConfigs(data.configs || []);
      setProviders(data.providers || {});
    } catch (error) {
      toast.error('Erreur lors du chargement des configurations AI');
    } finally {
      setIsLoading(false);
    }
  }, []);

  const ensureProvidersLoaded = useCallback(async () => {
    if (Object.keys(providers || {}).length > 0) return;
    try {
      setLoadingProviders(true);
      const defs = await aiProviderService.definitions();
      setProviders(defs.providers || {});
    } catch (error) {
      toast.error('Impossible de charger les definitions des providers');
    } finally {
      setLoadingProviders(false);
    }
  }, [providers]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const resetForm = () => {
    setForm({
      provider: '', label: '', api_key: '', base_url: '', default_model: '',
      is_active: true, is_default: false, rate_limit_rpm: '', rate_limit_rpd: '', daily_token_limit: '',
    });
  };

  const handleSelectProvider = (key) => {
    const def = providers[key];
    setForm(prev => ({
      ...prev,
      provider: key,
      label: def?.name || '',
      base_url: def?.base_url || '',
      rate_limit_rpm: def?.default_limits?.rpm || '',
      rate_limit_rpd: def?.default_limits?.rpd || '',
      daily_token_limit: def?.default_limits?.tokens || '',
      default_model: def?.models?.[0]?.id || '',
    }));
  };

  const handleSaveNew = async () => {
    if (!form.provider) {
      toast.warning('Veuillez selectionner un provider');
      return;
    }
    try {
      setSavingId('new');
      const payload = { ...form };
      if (!payload.base_url) delete payload.base_url;
      if (!payload.rate_limit_rpm) delete payload.rate_limit_rpm;
      if (!payload.rate_limit_rpd) delete payload.rate_limit_rpd;
      if (!payload.daily_token_limit) delete payload.daily_token_limit;
      await aiProviderService.store(payload);
      toast.success('Provider ajoute avec succes');
      setShowAddForm(false);
      resetForm();
      await loadData();
    } catch (error) {
      const msg = error.response?.data?.errors
        ? Object.values(error.response.data.errors).flat().join(', ')
        : 'Erreur lors de la creation';
      toast.error(msg);
    } finally {
      setSavingId(null);
    }
  };

  const handleStartEdit = (config) => {
    setEditingId(config.id);
    setForm({
      provider: config.provider,
      label: config.label || '',
      api_key: '',
      base_url: config.base_url || '',
      default_model: config.default_model || '',
      is_active: config.is_active,
      is_default: config.is_default,
      rate_limit_rpm: config.rate_limit_rpm || '',
      rate_limit_rpd: config.rate_limit_rpd || '',
      daily_token_limit: config.daily_token_limit || '',
    });
  };

  const handleSaveEdit = async (id) => {
    try {
      setSavingId(id);
      const payload = { ...form };
      if (!payload.base_url) delete payload.base_url;
      if (!payload.api_key) delete payload.api_key;
      if (!payload.rate_limit_rpm) delete payload.rate_limit_rpm;
      if (!payload.rate_limit_rpd) delete payload.rate_limit_rpd;
      if (!payload.daily_token_limit) delete payload.daily_token_limit;
      delete payload.provider;
      await aiProviderService.update(id, payload);
      toast.success('Configuration mise a jour');
      setEditingId(null);
      resetForm();
      await loadData();
    } catch (error) {
      const msg = error.response?.data?.errors
        ? Object.values(error.response.data.errors).flat().join(', ')
        : 'Erreur lors de la mise a jour';
      toast.error(msg);
    } finally {
      setSavingId(null);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Supprimer cette configuration ?')) return;
    try {
      setDeletingId(id);
      await aiProviderService.destroy(id);
      toast.success('Configuration supprimee');
      if (expandedId === id) setExpandedId(null);
      await loadData();
    } catch {
      toast.error('Erreur lors de la suppression');
    } finally {
      setDeletingId(null);
    }
  };

  const handleTest = async (id) => {
    try {
      setTestingId(id);
      const result = await aiProviderService.testConnection(id);
      setTestResults(prev => ({
        ...prev,
        [id]: {
          success: !!result.success,
          message: result.message || (result.success ? 'Connexion réussie' : 'Échec du test'),
          details: result.details || null,
          tested_at: new Date().toISOString(),
        },
      }));
      if (result.success) {
        toast.success(result.message);
        // Mettre à jour automatiquement les modèles si dispo
        if (result.details?.models) {
          await loadData();
        }
      } else {
        toast.error(result.message);
      }
    } catch (error) {
      const message = error.response?.data?.message || 'Echec du test de connexion';
      setTestResults(prev => ({
        ...prev,
        [id]: {
          success: false,
          message,
          details: error.response?.data?.details || null,
          tested_at: new Date().toISOString(),
        },
      }));
      toast.error(message);
    } finally {
      setTestingId(null);
    }
  };

  const handleRefreshModels = async (id) => {
    try {
      setRefreshingId(id);
      const result = await aiProviderService.refreshModels(id);
      if (result.success) {
        toast.success(result.message || 'Modèles mis à jour');
        await loadData();
      } else {
        toast.error(result.message || 'Échec mise à jour modèles');
      }
    } catch (error) {
      toast.error(error.response?.data?.message || 'Échec mise à jour modèles');
    } finally {
      setRefreshingId(null);
    }
  };

  const handleSetDefault = async (id) => {
    try {
      await aiProviderService.setDefault(id);
      toast.success('Provider par defaut mis a jour');
      await loadData();
    } catch {
      toast.error('Erreur');
    }
  };

  const handleResetUsage = async (id) => {
    try {
      await aiProviderService.resetUsage(id);
      toast.success('Compteurs reinitialises');
      await loadData();
    } catch {
      toast.error('Erreur');
    }
  };

  const getProviderDef = (key) => providers[key] || {};

  if (isLoading) {
    return (
      <div className="aip__loading">
        <Loader2 size={32} className="aip__spinner" />
      </div>
    );
  }

  return (
    <div className="aip">
      <div className="aip__content">
        {/* Header */}
        <div className="aip__header">
          <div className="aip__header-left">
            <button onClick={() => navigate('/parametres')} className="aip__back-btn">
              <ArrowLeft size={18} />
            </button>
            <div>
              <h1 className="aip__title">Providers AI</h1>
              <p className="aip__subtitle">Configurez vos cles API et providers d'intelligence artificielle</p>
            </div>
          </div>
          <button onClick={() => { setShowAddForm(true); resetForm(); ensureProvidersLoaded(); }} className="aip__add-btn">
            <Plus size={18} />
            <span>Ajouter</span>
          </button>
        </div>

        {/* Add Form */}
        {showAddForm && (
          <div className="aip__card aip__card--add">
            <div className="aip__card-header">
              <div className="aip__card-header-left">
                <Plus size={20} />
                <h2 className="aip__card-title">Nouveau provider</h2>
              </div>
              <button onClick={() => { setShowAddForm(false); resetForm(); }} className="aip__icon-btn">
                <X size={18} />
              </button>
            </div>

            {/* Provider selection grid */}
            {!form.provider ? (
              Object.keys(providers || {}).length > 0 ? (
                <div className="aip__provider-grid">
                  {Object.entries(providers).map(([key, def]) => (
                    <button
                      key={key}
                      className="aip__provider-option"
                      onClick={() => handleSelectProvider(key)}
                    >
                      <div className="aip__provider-option-icon" style={{ color: def.color }}>
                        {PROVIDER_ICONS[key] || <Brain size={20} />}
                      </div>
                      <span className="aip__provider-option-name">{def.name}</span>
                      {!def.is_paid && <span className="aip__badge aip__badge--free">Gratuit</span>}
                    </button>
                  ))}
                </div>
              ) : (
                <div className="aip__empty">
                  <div className="aip__empty-icon">
                    <Server size={42} />
                  </div>
                  <h3 className="aip__empty-title">Aucun provider disponible</h3>
                  <p className="aip__empty-text">
                    Rechargez la liste ou creez un provider custom.
                  </p>
                  <div className="aip__form-actions">
                    <button
                      onClick={ensureProvidersLoaded}
                      disabled={loadingProviders}
                      className="aip__btn aip__btn--secondary"
                    >
                      {loadingProviders ? <Loader2 size={16} className="aip__spinner" /> : <RotateCcw size={16} />}
                      <span>Recharger</span>
                    </button>
                    <button
                      onClick={() => handleSelectProvider('custom')}
                      className="aip__btn aip__btn--primary"
                    >
                      <Plus size={16} />
                      <span>Custom</span>
                    </button>
                  </div>
                </div>
              )
            ) : (
              <div className="aip__form">
                <div className="aip__form-provider-selected">
                  <div className="aip__provider-option-icon" style={{ color: getProviderDef(form.provider).color }}>
                    {PROVIDER_ICONS[form.provider] || <Brain size={20} />}
                  </div>
                  <span>{getProviderDef(form.provider).name}</span>
                  <button onClick={() => setForm(prev => ({ ...prev, provider: '' }))} className="aip__link-btn">
                    Changer
                  </button>
                </div>

                <ProviderForm
                  form={form}
                  setForm={setForm}
                  providerDef={getProviderDef(form.provider)}
                  showApiKey={true}
                  availableModels={null}
                />

                <div className="aip__form-actions">
                  <button onClick={() => { setShowAddForm(false); resetForm(); }} className="aip__btn aip__btn--secondary">
                    Annuler
                  </button>
                  <button onClick={handleSaveNew} disabled={savingId === 'new'} className="aip__btn aip__btn--primary">
                    {savingId === 'new' ? <Loader2 size={16} className="aip__spinner" /> : <Check size={16} />}
                    <span>Enregistrer</span>
                  </button>
                </div>
              </div>
            )}
          </div>
        )}

        {/* Empty State */}
        {configs.length === 0 && !showAddForm && (
          <div className="aip__empty">
            <div className="aip__empty-icon">
              <Brain size={48} />
            </div>
            <h3 className="aip__empty-title">Aucun provider configure</h3>
            <p className="aip__empty-text">
              Ajoutez une configuration pour connecter un service d'intelligence artificielle.
            </p>
            <button onClick={() => { setShowAddForm(true); resetForm(); ensureProvidersLoaded(); }} className="aip__btn aip__btn--primary">
              <Plus size={16} />
              <span>Ajouter un provider</span>
            </button>
          </div>
        )}

        {/* Config List */}
        <div className="aip__list">
          {configs.map((config) => {
            const isExpanded = expandedId === config.id;
            const isEditing = editingId === config.id;

            return (
              <div key={config.id} className={`aip__card ${config.is_default ? 'aip__card--default' : ''} ${!config.is_active ? 'aip__card--inactive' : ''}`}>
                {/* Card Header */}
                <div className="aip__card-header aip__card-header--clickable" onClick={() => {
                  if (!isEditing) setExpandedId(isExpanded ? null : config.id);
                }}>
                  <div className="aip__card-header-left">
                    <div className="aip__provider-icon" style={{ backgroundColor: config.provider_color + '18', color: config.provider_color }}>
                      {PROVIDER_ICONS[config.provider] || <Brain size={20} />}
                    </div>
                    <div className="aip__card-info">
                      <div className="aip__card-name-row">
                        <h3 className="aip__card-name">{config.label || config.provider_name}</h3>
                        {config.is_default && (
                          <span className="aip__badge aip__badge--default">
                            <Star size={10} />
                            Defaut
                          </span>
                        )}
                        {!config.is_active && (
                          <span className="aip__badge aip__badge--inactive">Inactif</span>
                        )}
                        {config.is_rate_limited && (
                          <span className="aip__badge aip__badge--warning">
                            <AlertTriangle size={10} />
                            Limite
                          </span>
                        )}
                      </div>
                      <div className="aip__card-meta">
                        <span>{config.provider_name}</span>
                        {config.default_model && (
                          <>
                            <span className="aip__meta-dot" />
                            <span>{config.default_model}</span>
                          </>
                        )}
                        {config.has_key && (
                          <>
                            <span className="aip__meta-dot" />
                            <span className="aip__meta-key">
                              <Key size={11} />
                              Configuree
                            </span>
                          </>
                        )}
                      </div>
                    </div>
                  </div>
                  <div className="aip__card-header-right">
                    {config.usage_percentage !== null && config.usage_percentage !== undefined && (
                      <div className="aip__usage-mini">
                        <div className="aip__usage-mini-bar">
                          <div
                            className={`aip__usage-mini-fill ${config.usage_percentage > 80 ? 'aip__usage-mini-fill--danger' : config.usage_percentage > 50 ? 'aip__usage-mini-fill--warning' : ''}`}
                            style={{ width: `${Math.min(config.usage_percentage, 100)}%` }}
                          />
                        </div>
                        <span className="aip__usage-mini-label">{config.usage_percentage}%</span>
                      </div>
                    )}
                    {isExpanded ? <ChevronDown size={18} /> : <ChevronRight size={18} />}
                  </div>
                </div>

                {/* Expanded Content */}
                {isExpanded && (
                  <div className="aip__card-body">
                    {isEditing ? (
                      <div className="aip__form">
                        <ProviderForm
                          form={form}
                          setForm={setForm}
                          providerDef={getProviderDef(config.provider)}
                          showApiKey={true}
                          isEdit={true}
                          maskedKey={config.masked_key}
                          availableModels={config.available_models}
                        />
                        <div className="aip__form-actions">
                          <button onClick={() => { setEditingId(null); resetForm(); }} className="aip__btn aip__btn--secondary">
                            Annuler
                          </button>
                          <button onClick={() => handleSaveEdit(config.id)} disabled={savingId === config.id} className="aip__btn aip__btn--primary">
                            {savingId === config.id ? <Loader2 size={16} className="aip__spinner" /> : <Check size={16} />}
                            <span>Sauvegarder</span>
                          </button>
                        </div>
                      </div>
                    ) : (
                      <>
                        {/* Test Result */}
                        {testResults[config.id] && (
                          <div className={`aip__test-result ${testResults[config.id].success ? 'aip__test-result--ok' : 'aip__test-result--fail'}`}>
                            <div className="aip__test-result-title">
                              {testResults[config.id].success ? 'Test réussi' : 'Test échoué'}
                            </div>
                            <div className="aip__test-result-message">
                              {testResults[config.id].message}
                            </div>
                            {testResults[config.id].details && (
                              <pre className="aip__test-result-details">
                                {JSON.stringify(testResults[config.id].details, null, 2)}
                              </pre>
                            )}
                          </div>
                        )}

                        {/* Details */}
                        <div className="aip__detail-grid">
                          <DetailItem icon={<Globe size={15} />} label="URL de base" value={config.base_url || 'Par defaut'} />
                          <DetailItem icon={<Brain size={15} />} label="Modele" value={config.default_model || 'Non defini'} />
                          <DetailItem
                            icon={<Key size={15} />}
                            label="Cle API"
                            value={
                              config.has_key ? (
                                <span className="aip__key-display">
                                  {showKeyFor === config.id ? config.masked_key : '••••••••••••••••'}
                                  <button onClick={(e) => { e.stopPropagation(); setShowKeyFor(showKeyFor === config.id ? null : config.id); }} className="aip__icon-btn aip__icon-btn--sm">
                                    {showKeyFor === config.id ? <EyeOff size={13} /> : <Eye size={13} />}
                                  </button>
                                </span>
                              ) : (
                                <span className="aip__text-muted">Non configuree</span>
                              )
                            }
                          />
                          <DetailItem icon={<Shield size={15} />} label="Statut" value={config.is_active ? 'Actif' : 'Inactif'} />
                        </div>

                        {/* Usage */}
                        {(config.rate_limit_rpd || config.daily_token_limit) && (
                          <div className="aip__usage-section">
                            <h4 className="aip__usage-title">
                              <Activity size={15} />
                              Utilisation
                            </h4>
                            <div className="aip__usage-grid">
                              {config.daily_token_limit && (
                                <UsageBar
                                  label="Tokens"
                                  used={config.tokens_used_today || 0}
                                  limit={config.daily_token_limit}
                                  percentage={config.usage_percentage}
                                />
                              )}
                              {config.rate_limit_rpd && (
                                <UsageBar
                                  label="Requetes / jour"
                                  used={config.requests_today || 0}
                                  limit={config.rate_limit_rpd}
                                  percentage={config.requests_percentage}
                                />
                              )}
                              {config.rate_limit_rpm && (
                                <DetailItem icon={<Zap size={15} />} label="Limite RPM" value={`${config.rate_limit_rpm} req/min`} />
                              )}
                            </div>
                          </div>
                        )}

                        {/* Models */}
                        {config.available_models?.length > 0 && (
                          <div className="aip__models-section">
                            <h4 className="aip__models-title">Modeles disponibles</h4>
                            <div className="aip__models-grid">
                              {config.available_models.map((m, i) => (
                                <div key={i} className={`aip__model-tag ${m.id === config.default_model ? 'aip__model-tag--active' : ''}`}>
                                  {typeof m === 'string' ? m : m.name || m.id}
                                  {m.tier && <span className="aip__model-tier">{m.tier}</span>}
                                </div>
                              ))}
                            </div>
                          </div>
                        )}

                        {/* Actions */}
                        <div className="aip__card-actions">
                          <button onClick={() => handleStartEdit(config)} className="aip__btn aip__btn--secondary">
                            <Settings2 size={15} />
                            <span>Modifier</span>
                          </button>
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              if (!config.has_key) {
                                toast.warning('Ajoutez une cle API avant de tester');
                                return;
                              }
                              handleTest(config.id);
                            }}
                            disabled={testingId === config.id}
                            className="aip__btn aip__btn--secondary"
                          >
                            {testingId === config.id ? <Loader2 size={15} className="aip__spinner" /> : <Zap size={15} />}
                            <span>Tester</span>
                          </button>
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              if (!config.has_key) {
                                toast.warning('Ajoutez une cle API avant de rafraichir');
                                return;
                              }
                              handleRefreshModels(config.id);
                            }}
                            disabled={refreshingId === config.id}
                            className="aip__btn aip__btn--secondary"
                          >
                            {refreshingId === config.id ? <Loader2 size={15} className="aip__spinner" /> : <RotateCcw size={15} />}
                            <span>Rafraichir modeles</span>
                          </button>
                          {!config.is_default && (
                            <button
                              onClick={(e) => {
                                e.stopPropagation();
                                handleSetDefault(config.id);
                              }}
                              className="aip__btn aip__btn--secondary"
                            >
                              <Star size={15} />
                              <span>Defaut</span>
                            </button>
                          )}
                          {(config.tokens_used_today > 0 || config.requests_today > 0) && (
                            <button
                              onClick={(e) => {
                                e.stopPropagation();
                                handleResetUsage(config.id);
                              }}
                              className="aip__btn aip__btn--secondary"
                            >
                              <RotateCcw size={15} />
                              <span>Reset</span>
                            </button>
                          )}
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              handleDelete(config.id);
                            }}
                            disabled={deletingId === config.id}
                            className="aip__btn aip__btn--danger"
                          >
                            {deletingId === config.id ? <Loader2 size={15} className="aip__spinner" /> : <Trash2 size={15} />}
                          </button>
                        </div>

                        {/* Timestamps */}
                        {config.last_used_at && (
                          <p className="aip__timestamp">
                            Derniere utilisation : {new Date(config.last_used_at).toLocaleString('fr-FR')}
                          </p>
                        )}
                      </>
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

/* ── Sub-components ── */

const ProviderForm = ({ form, setForm, providerDef, showApiKey, isEdit, maskedKey, availableModels }) => {
  const handleChange = (field, value) => setForm(prev => ({ ...prev, [field]: value }));
  const models = Array.isArray(availableModels) && availableModels.length > 0
    ? availableModels
    : (providerDef?.models || []);

  return (
    <div className="aip__form-fields">
      <div className="aip__form-row">
        <div className="aip__form-group">
          <label className="aip__label">Label</label>
          <input
            type="text"
            className="aip__input"
            value={form.label}
            onChange={(e) => handleChange('label', e.target.value)}
            placeholder="Nom personnalise"
          />
        </div>
        <div className="aip__form-group">
          <label className="aip__label">Modele par defaut</label>
          {models?.length > 0 ? (
            <select
              className="aip__select"
              value={form.default_model}
              onChange={(e) => handleChange('default_model', e.target.value)}
            >
              <option value="">-- Selectionner --</option>
              {models.map((m) => {
                const id = typeof m === 'string' ? m : (m.id || m.name);
                const name = typeof m === 'string' ? m : (m.name || m.id);
                return (
                  <option key={id} value={id}>
                    {name}{m.tier ? ` (${m.tier})` : ''}
                  </option>
                );
              })}
            </select>
          ) : (
            <input
              type="text"
              className="aip__input"
              value={form.default_model}
              onChange={(e) => handleChange('default_model', e.target.value)}
              placeholder="ex: gpt-4o"
            />
          )}
        </div>
      </div>

      {showApiKey && (
        <div className="aip__form-group">
          <label className="aip__label">
            <Key size={14} />
            Cle API
            {isEdit && maskedKey && (
              <span className="aip__label-hint">(actuelle : {maskedKey})</span>
            )}
          </label>
          <input
            type="password"
            className="aip__input"
            value={form.api_key}
            onChange={(e) => handleChange('api_key', e.target.value)}
            placeholder={isEdit ? 'Laisser vide pour conserver' : `${providerDef?.key_prefix || ''}...`}
            autoComplete="off"
          />
        </div>
      )}

      <div className="aip__form-group">
        <label className="aip__label">URL de base</label>
        <input
          type="url"
          className="aip__input"
          value={form.base_url}
          onChange={(e) => handleChange('base_url', e.target.value)}
          placeholder={providerDef?.base_url || 'https://...'}
        />
      </div>

      <div className="aip__form-row aip__form-row--3">
        <div className="aip__form-group">
          <label className="aip__label">RPM</label>
          <input
            type="number"
            className="aip__input"
            value={form.rate_limit_rpm}
            onChange={(e) => handleChange('rate_limit_rpm', e.target.value)}
            placeholder="Req/min"
          />
        </div>
        <div className="aip__form-group">
          <label className="aip__label">RPD</label>
          <input
            type="number"
            className="aip__input"
            value={form.rate_limit_rpd}
            onChange={(e) => handleChange('rate_limit_rpd', e.target.value)}
            placeholder="Req/jour"
          />
        </div>
        <div className="aip__form-group">
          <label className="aip__label">Tokens / jour</label>
          <input
            type="number"
            className="aip__input"
            value={form.daily_token_limit}
            onChange={(e) => handleChange('daily_token_limit', e.target.value)}
            placeholder="Limite"
          />
        </div>
      </div>

      <div className="aip__form-row">
        <label className="aip__toggle">
          <input type="checkbox" checked={form.is_active} onChange={(e) => handleChange('is_active', e.target.checked)} />
          <span className="aip__toggle-track" />
          <span className="aip__toggle-label">Actif</span>
        </label>
        <label className="aip__toggle">
          <input type="checkbox" checked={form.is_default} onChange={(e) => handleChange('is_default', e.target.checked)} />
          <span className="aip__toggle-track" />
          <span className="aip__toggle-label">Definir par defaut</span>
        </label>
      </div>
    </div>
  );
};

const DetailItem = ({ icon, label, value }) => (
  <div className="aip__detail-item">
    <span className="aip__detail-icon">{icon}</span>
    <div className="aip__detail-content">
      <span className="aip__detail-label">{label}</span>
      <span className="aip__detail-value">{value}</span>
    </div>
  </div>
);

const UsageBar = ({ label, used, limit, percentage }) => (
  <div className="aip__usage-bar-container">
    <div className="aip__usage-bar-header">
      <span className="aip__usage-bar-label">{label}</span>
      <span className="aip__usage-bar-count">{(used || 0).toLocaleString('fr-FR')} / {(limit || 0).toLocaleString('fr-FR')}</span>
    </div>
    <div className="aip__usage-bar">
      <div
        className={`aip__usage-bar-fill ${(percentage || 0) > 80 ? 'aip__usage-bar-fill--danger' : (percentage || 0) > 50 ? 'aip__usage-bar-fill--warning' : ''}`}
        style={{ width: `${Math.min(percentage || 0, 100)}%` }}
      />
    </div>
  </div>
);

export default AiProviderConfig;
