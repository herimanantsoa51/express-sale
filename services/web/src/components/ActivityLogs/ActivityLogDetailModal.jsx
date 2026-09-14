import { useEffect } from 'react';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

const ActivityLogDetailModal = ({ log, onClose }) => {
  useEffect(() => {
    const handleEscape = (e) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };
    
    document.addEventListener('keydown', handleEscape);
    document.body.style.overflow = 'hidden';
    
    return () => {
      document.removeEventListener('keydown', handleEscape);
      document.body.style.overflow = 'unset';
    };
  }, [onClose]);

  const formatDate = (date) => {
    try {
      return format(new Date(date), 'PPpp', { locale: fr });
    } catch {
      return date;
    }
  };

  const renderMetadata = (data) => {
    if (!data) return null;
    
    return (
      <pre className="aldm-json">
        {JSON.stringify(data, null, 2)}
      </pre>
    );
  };

  return (
    <div className="aldm-overlay" onClick={onClose}>
      <div className="aldm-container" onClick={(e) => e.stopPropagation()}>
        <div className="aldm-header">
          <div className="aldm-header-content">
            <h2 className="aldm-title">Détails du log #{log.id}</h2>
            <p className="aldm-subtitle">{log.action}</p>
          </div>
          <button className="aldm-close" onClick={onClose}>
            ✕
          </button>
        </div>

        <div className="aldm-content">
          <div className="aldm-section">
            <h3 className="aldm-section-title">Informations générales</h3>
            <div className="aldm-grid">
              <div className="aldm-field">
                <span className="aldm-field-label">Utilisateur</span>
                <span className="aldm-field-value">
                  {log.user?.name || 'Inconnu'} ({log.user?.role || '-'})
                </span>
              </div>
              
              <div className="aldm-field">
                <span className="aldm-field-label">Action</span>
                <span className="aldm-field-value">{log.action}</span>
              </div>
              
              <div className="aldm-field">
                <span className="aldm-field-label">Statut</span>
                <span className={`aldm-status aldm-status-${log.status}`}>
                  {log.status}
                </span>
              </div>
              
              <div className="aldm-field">
                <span className="aldm-field-label">Date</span>
                <span className="aldm-field-value">{formatDate(log.created_at)}</span>
              </div>
              
              {log.model_type && (
                <div className="aldm-field">
                  <span className="aldm-field-label">Modèle</span>
                  <span className="aldm-field-value">
                    {log.model_type.split('\\').pop()} #{log.model_id}
                  </span>
                </div>
              )}
              
              <div className="aldm-field">
                <span className="aldm-field-label">IP</span>
                <span className="aldm-field-value">{log.ip_address}</span>
              </div>
            </div>
          </div>

          <div className="aldm-section">
            <h3 className="aldm-section-title">Description</h3>
            <p className="aldm-description">{log.description}</p>
          </div>

          {log.metadata && (
            <div className="aldm-section">
              <h3 className="aldm-section-title">Métadonnées</h3>
              {renderMetadata(log.metadata)}
            </div>
          )}

          {log.error_message && (
            <div className="aldm-section aldm-section-error">
              <h3 className="aldm-section-title">Message d'erreur</h3>
              <div className="aldm-error-message">{log.error_message}</div>
            </div>
          )}

          {log.error_trace && (
            <div className="aldm-section aldm-section-error">
              <h3 className="aldm-section-title">Trace de l'erreur</h3>
              <pre className="aldm-error-trace">{log.error_trace}</pre>
            </div>
          )}

          {log.user_agent && (
            <div className="aldm-section">
              <h3 className="aldm-section-title">User Agent</h3>
              <p className="aldm-user-agent">{log.user_agent}</p>
            </div>
          )}
        </div>

        <div className="aldm-footer">
          <button className="aldm-button" onClick={onClose}>
            Fermer
          </button>
        </div>
      </div>
    </div>
  );
};

export default ActivityLogDetailModal;