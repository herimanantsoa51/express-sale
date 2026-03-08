import React, { useCallback, useEffect, useState } from 'react';
import { ArrowLeft, Loader2, Search, RefreshCcw, Activity, CheckCircle2, XCircle, Clock, ChevronDown, ChevronUp } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import aiTaskService from '../../services/aiTaskService';
import '../../styles/AiTaskHistory.css';

const STATUS_STYLES = {
  queued: { icon: <Clock size={14} />, className: 'ait__status--queued', label: 'En attente' },
  processing: { icon: <Clock size={14} />, className: 'ait__status--processing', label: 'En cours' },
  completed: { icon: <CheckCircle2 size={14} />, className: 'ait__status--completed', label: 'Terminé' },
  executed: { icon: <CheckCircle2 size={14} />, className: 'ait__status--executed', label: 'Exécuté' },
  failed: { icon: <XCircle size={14} />, className: 'ait__status--failed', label: 'Échec' },
};

const AiTaskHistory = () => {
  const navigate = useNavigate();
  const [tasks, setTasks] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [filters, setFilters] = useState({ status: '', intent: '' });
  const [page, setPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [expanded, setExpanded] = useState({});

  const loadTasks = useCallback(async (pageNumber = 1) => {
    try {
      setIsLoading(true);
      const data = await aiTaskService.list({
        ...filters,
        page: pageNumber,
        per_page: 20,
      });
      setTasks(data.tasks || []);
      setPagination(data.pagination || null);
      setPage(pageNumber);
    } catch (error) {
      toast.error('Erreur lors du chargement des tâches AI');
    } finally {
      setIsLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    loadTasks(1);
  }, [loadTasks]);

  const handleSearch = (e) => {
    e.preventDefault();
    loadTasks(1);
  };

  const renderStatus = (status) => {
    const def = STATUS_STYLES[status] || { className: 'ait__status--queued', label: status };
    return (
      <span className={`ait__status ${def.className}`}>
        {def.icon}
        {def.label}
      </span>
    );
  };

  const toggleDetails = (id) => {
    setExpanded((prev) => ({ ...prev, [id]: !prev[id] }));
  };

  const renderTrace = (trace) => {
    if (!Array.isArray(trace) || trace.length === 0) return null;
    return (
      <div className="ait__trace">
        {trace.map((step, idx) => (
          <div key={idx} className="ait__trace-row">
            <div className="ait__trace-step">#{idx + 1} {step.step || 'step'}</div>
            <pre className="ait__trace-json">{JSON.stringify(step, null, 2)}</pre>
          </div>
        ))}
      </div>
    );
  };

  return (
    <div className="ait">
      <div className="ait__content">
        <div className="ait__header">
          <div className="ait__header-left">
            <button onClick={() => navigate('/parametres')} className="ait__back-btn">
              <ArrowLeft size={18} />
            </button>
            <div>
              <h1 className="ait__title">Historique AI</h1>
              <p className="ait__subtitle">Suivez les demandes, statuts et réponses des tâches AI</p>
            </div>
          </div>
          <button onClick={() => loadTasks(1)} className="ait__refresh-btn">
            <RefreshCcw size={16} />
            <span>Rafraîchir</span>
          </button>
        </div>

        <form className="ait__filters" onSubmit={handleSearch}>
          <div className="ait__filter-group">
            <label className="ait__label">Statut</label>
            <select
              className="ait__select"
              value={filters.status}
              onChange={(e) => setFilters(prev => ({ ...prev, status: e.target.value }))}
            >
              <option value="">Tous</option>
              <option value="queued">En attente</option>
              <option value="processing">En cours</option>
              <option value="completed">Terminé</option>
              <option value="executed">Exécuté</option>
              <option value="failed">Échec</option>
            </select>
          </div>
          <div className="ait__filter-group ait__filter-group--grow">
            <label className="ait__label">Recherche</label>
            <div className="ait__search">
              <Search size={16} />
              <input
                type="text"
                className="ait__input"
                placeholder="Intent, mots-clés..."
                value={filters.intent}
                onChange={(e) => setFilters(prev => ({ ...prev, intent: e.target.value }))}
              />
            </div>
          </div>
          <button type="submit" className="ait__btn">
            <Search size={16} />
            <span>Filtrer</span>
          </button>
        </form>

        {isLoading ? (
          <div className="ait__loading">
            <Loader2 size={28} className="ait__spinner" />
          </div>
        ) : tasks.length === 0 ? (
          <div className="ait__empty">
            <Activity size={44} />
            <h3>Aucune tâche</h3>
            <p>Lancez une demande AI pour la voir ici.</p>
          </div>
        ) : (
          <div className="ait__list">
            {tasks.map((task) => (
              <div key={task.id} className="ait__card">
                <div className="ait__card-header">
                  <div>
                    <div className="ait__card-title">{task.intent}</div>
                    <div className="ait__card-meta">
                      <span>ID #{task.id}</span>
                      <span className="ait__dot" />
                      <span>{task.model || 'Modèle n/a'}</span>
                      <span className="ait__dot" />
                      <span>{new Date(task.created_at).toLocaleString('fr-FR')}</span>
                    </div>
                  </div>
                  {renderStatus(task.status)}
                </div>

                <div className="ait__card-actions">
                  <button className="ait__btn" type="button" onClick={() => toggleDetails(task.id)}>
                    {expanded[task.id] ? <ChevronUp size={14} /> : <ChevronDown size={14} />}
                    <span>{expanded[task.id] ? 'Masquer les logs' : 'Voir les logs'}</span>
                  </button>
                </div>

                {task.error_message && (
                  <div className="ait__error">
                    <XCircle size={14} />
                    <span>{task.error_message}</span>
                  </div>
                )}

                {task.tasks?.length > 0 && (
                  <div className="ait__tasks">
                    {task.tasks.map((t, idx) => (
                      <div key={idx} className="ait__task-row">
                        <span className="ait__task-type">{t.type}</span>
                        <span className="ait__task-action">{t.action}</span>
                      </div>
                    ))}
                  </div>
                )}

                {expanded[task.id] && (
                  <div className="ait__details">
                    <div className="ait__details-grid">
                      <div>
                        <div className="ait__details-label">Réponse</div>
                        <pre className="ait__details-pre">{task.raw_response?.response || 'n/a'}</pre>
                      </div>
                      <div>
                        <div className="ait__details-label">Meta</div>
                        <pre className="ait__details-pre">
                          {JSON.stringify({
                            source: task.raw_response?.source,
                            language: task.raw_response?.language,
                            faq_score: task.raw_response?.faq_score,
                            model: task.model,
                            created_at: task.created_at,
                          }, null, 2)}
                        </pre>
                      </div>
                    </div>
                    <div className="ait__details-label">Trace LangGraph</div>
                    {renderTrace(task.raw_response?.trace)}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}

        {pagination && pagination.last_page > 1 && (
          <div className="ait__pagination">
            <button
              className="ait__btn"
              onClick={() => loadTasks(Math.max(page - 1, 1))}
              disabled={page <= 1}
            >
              Précédent
            </button>
            <span>Page {pagination.current_page} / {pagination.last_page}</span>
            <button
              className="ait__btn"
              onClick={() => loadTasks(Math.min(page + 1, pagination.last_page))}
              disabled={page >= pagination.last_page}
            >
              Suivant
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default AiTaskHistory;
