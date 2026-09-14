import { useState } from 'react';
import activityLogsService from '../../services/activityLogsService';
import '../../styles/ActivityLogsManagement.css';
const ActivityLogsManagement = ({ onRefresh }) => {
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState(null);
  const [showConfirm, setShowConfirm] = useState(null);

  // Form states
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [olderThanDate, setOlderThanDate] = useState('');
  const [deleteStatus, setDeleteStatus] = useState('success');
  const [deleteStatusDateFrom, setDeleteStatusDateFrom] = useState('');
  const [deleteStatusDateTo, setDeleteStatusDateTo] = useState('');
  const [keepDays, setKeepDays] = useState('90');

  const handleDeleteBetweenDates = async () => {
    if (!dateFrom || !dateTo) {
      setError('Veuillez sélectionner les deux dates');
      return;
    }

    setShowConfirm({
      action: 'deleteBetweenDates',
      message: `Supprimer tous les logs entre ${dateFrom} et ${dateTo} ?`,
      data: { date_from: dateFrom, date_to: dateTo }
    });
  };

  const handleDeleteOlderThan = async () => {
    if (!olderThanDate) {
      setError('Veuillez sélectionner une date');
      return;
    }

    setShowConfirm({
      action: 'deleteOlderThan',
      message: `Supprimer tous les logs avant le ${olderThanDate} ?`,
      data: { date: olderThanDate }
    });
  };

  const handleDeleteByStatus = async () => {
    const data = {
      status: deleteStatus,
      ...(deleteStatusDateFrom && { date_from: deleteStatusDateFrom }),
      ...(deleteStatusDateTo && { date_to: deleteStatusDateTo })
    };

    let message = `Supprimer tous les logs avec le statut "${deleteStatus}"`;
    if (deleteStatusDateFrom || deleteStatusDateTo) {
      message += ' entre ';
      if (deleteStatusDateFrom) message += `${deleteStatusDateFrom}`;
      if (deleteStatusDateFrom && deleteStatusDateTo) message += ' et ';
      if (deleteStatusDateTo) message += `${deleteStatusDateTo}`;
    }
    message += ' ?';

    setShowConfirm({
      action: 'deleteByStatus',
      message,
      data
    });
  };

  const handleAutoCleanup = async () => {
    setShowConfirm({
      action: 'autoCleanup',
      message: `Supprimer automatiquement les logs de plus de ${keepDays} jours ?`,
      data: { keep_days: parseInt(keepDays) }
    });
  };

  const executeAction = async () => {
    if (!showConfirm) return;

    setLoading(true);
    setError(null);
    setResult(null);

    try {
      let response;
      
      switch (showConfirm.action) {
        case 'deleteBetweenDates':
          response = await activityLogsService.deleteBetweenDates(showConfirm.data);
          break;
        case 'deleteOlderThan':
          response = await activityLogsService.deleteOlderThan(showConfirm.data);
          break;
        case 'deleteByStatus':
          response = await activityLogsService.deleteByStatus(showConfirm.data);
          break;
        case 'autoCleanup':
          response = await activityLogsService.autoCleanup(showConfirm.data);
          break;
        default:
          throw new Error('Action inconnue');
      }

      setResult(response);
      setShowConfirm(null);
      
      // Reset forms
      setDateFrom('');
      setDateTo('');
      setOlderThanDate('');
      setDeleteStatusDateFrom('');
      setDeleteStatusDateTo('');
      
      // Refresh main table
      if (onRefresh) {
        setTimeout(() => onRefresh(), 1000);
      }
    } catch (err) {
      setError(err.response?.data?.message || err.message || 'Erreur lors de la suppression');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="alm-container">
      <div className="alm-header">
        <h2 className="alm-title">Gestion des logs</h2>
        <p className="alm-subtitle">Opérations de nettoyage et maintenance</p>
      </div>

      {error && (
        <div className="alm-alert alm-alert-error">
          <span className="alm-alert-icon">⚠</span>
          <span className="alm-alert-text">{error}</span>
          <button className="alm-alert-close" onClick={() => setError(null)}>✕</button>
        </div>
      )}

      {result && (
        <div className="alm-alert alm-alert-success">
          <span className="alm-alert-icon">✓</span>
          <div className="alm-alert-content">
            <p className="alm-alert-text">{result.message}</p>
            <p className="alm-alert-detail">
              {result.deleted_count} log(s) supprimé(s)
            </p>
          </div>
          <button className="alm-alert-close" onClick={() => setResult(null)}>✕</button>
        </div>
      )}

      <div className="alm-sections">
        {/* Section 1: Supprimer entre deux dates */}
        <div className="alm-section">
          <div className="alm-section-header">
            <h3 className="alm-section-title">Supprimer entre deux dates</h3>
            <p className="alm-section-desc">
              Supprime tous les logs dans une période donnée
            </p>
          </div>
          <div className="alm-section-content">
            <div className="alm-grid alm-grid-2">
              <div className="alm-field">
                <label className="alm-label">Date de début</label>
                <input
                  type="date"
                  className="alm-input"
                  value={dateFrom}
                  onChange={(e) => setDateFrom(e.target.value)}
                />
              </div>
              <div className="alm-field">
                <label className="alm-label">Date de fin</label>
                <input
                  type="date"
                  className="alm-input"
                  value={dateTo}
                  onChange={(e) => setDateTo(e.target.value)}
                />
              </div>
            </div>
            <button
              className="alm-button alm-button-danger"
              onClick={handleDeleteBetweenDates}
              disabled={!dateFrom || !dateTo}
            >
              Supprimer entre ces dates
            </button>
          </div>
        </div>

        {/* Section 2: Supprimer avant une date */}
        <div className="alm-section">
          <div className="alm-section-header">
            <h3 className="alm-section-title">Supprimer avant une date</h3>
            <p className="alm-section-desc">
              Supprime tous les logs antérieurs à une date
            </p>
          </div>
          <div className="alm-section-content">
            <div className="alm-field">
              <label className="alm-label">Supprimer avant le</label>
              <input
                type="date"
                className="alm-input"
                value={olderThanDate}
                onChange={(e) => setOlderThanDate(e.target.value)}
              />
            </div>
            <button
              className="alm-button alm-button-danger"
              onClick={handleDeleteOlderThan}
              disabled={!olderThanDate}
            >
              Supprimer les logs antérieurs
            </button>
          </div>
        </div>

        {/* Section 3: Supprimer par statut */}
        <div className="alm-section">
          <div className="alm-section-header">
            <h3 className="alm-section-title">Supprimer par statut</h3>
            <p className="alm-section-desc">
              Supprime tous les logs d'un statut spécifique (optionnellement dans une période)
            </p>
          </div>
          <div className="alm-section-content">
            <div className="alm-field">
              <label className="alm-label">Statut à supprimer</label>
              <select
                className="alm-select"
                value={deleteStatus}
                onChange={(e) => setDeleteStatus(e.target.value)}
              >
                <option value="success">Succès</option>
                <option value="failed">Échec</option>
                <option value="error">Erreur</option>
              </select>
            </div>
            <div className="alm-grid alm-grid-2">
              <div className="alm-field">
                <label className="alm-label">Date de début (optionnel)</label>
                <input
                  type="date"
                  className="alm-input"
                  value={deleteStatusDateFrom}
                  onChange={(e) => setDeleteStatusDateFrom(e.target.value)}
                />
              </div>
              <div className="alm-field">
                <label className="alm-label">Date de fin (optionnel)</label>
                <input
                  type="date"
                  className="alm-input"
                  value={deleteStatusDateTo}
                  onChange={(e) => setDeleteStatusDateTo(e.target.value)}
                />
              </div>
            </div>
            <button
              className="alm-button alm-button-danger"
              onClick={handleDeleteByStatus}
            >
              Supprimer par statut
            </button>
          </div>
        </div>

        {/* Section 4: Nettoyage automatique */}
        <div className="alm-section alm-section-featured">
          <div className="alm-section-header">
            <h3 className="alm-section-title">Nettoyage automatique</h3>
            <p className="alm-section-desc">
              Recommandé : Supprime automatiquement les logs anciens
            </p>
          </div>
          <div className="alm-section-content">
            <div className="alm-field">
              <label className="alm-label">
                Conserver les logs des derniers (jours)
              </label>
              <input
                type="number"
                className="alm-input"
                value={keepDays}
                onChange={(e) => setKeepDays(e.target.value)}
                min="1"
                max="365"
              />
              <p className="alm-help-text">
                Par défaut : 90 jours. Les logs plus anciens seront supprimés.
              </p>
            </div>
            <button
              className="alm-button alm-button-primary"
              onClick={handleAutoCleanup}
            >
              Lancer le nettoyage
            </button>
          </div>
        </div>
      </div>

      {/* Confirmation Modal */}
      {showConfirm && (
        <div className="alm-modal-overlay" onClick={() => setShowConfirm(null)}>
          <div className="alm-modal" onClick={(e) => e.stopPropagation()}>
            <div className="alm-modal-header">
              <h3 className="alm-modal-title">Confirmation</h3>
            </div>
            <div className="alm-modal-content">
              <p className="alm-modal-message">{showConfirm.message}</p>
              <p className="alm-modal-warning">
                ⚠ Cette action est irréversible
              </p>
            </div>
            <div className="alm-modal-footer">
              <button
                className="alm-button alm-button-ghost"
                onClick={() => setShowConfirm(null)}
                disabled={loading}
              >
                Annuler
              </button>
              <button
                className="alm-button alm-button-danger"
                onClick={executeAction}
                disabled={loading}
              >
                {loading ? 'Suppression...' : 'Confirmer la suppression'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ActivityLogsManagement;