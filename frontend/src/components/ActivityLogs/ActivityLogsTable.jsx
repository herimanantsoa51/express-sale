import { formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';

const ActivityLogsTable = ({ logs, onLogClick, onViewDetails }) => {
  const getStatusClass = (status) => {
    switch (status) {
      case 'success':
        return 'alt-status-success';
      case 'failed':
        return 'alt-status-failed';
      case 'error':
        return 'alt-status-error';
      default:
        return '';
    }
  };

  const getStatusLabel = (status) => {
    switch (status) {
      case 'success':
        return 'Succès';
      case 'failed':
        return 'Échec';
      case 'error':
        return 'Erreur';
      default:
        return status;
    }
  };

  const formatDate = (date) => {
    try {
      return formatDistanceToNow(new Date(date), { 
        addSuffix: true, 
        locale: fr 
      });
    } catch {
      return date;
    }
  };

  return (
    <div className="alt-wrapper">
      <div className="alt-scroll">
        <table className="alt-table">
          <thead className="alt-thead">
            <tr>
              <th className="alt-th alt-th-user">Utilisateur</th>
              <th className="alt-th alt-th-action">Action</th>
              <th className="alt-th alt-th-description">Description</th>
              <th className="alt-th alt-th-status">Statut</th>
              <th className="alt-th alt-th-date">Date</th>
              <th className="alt-th alt-th-actions">Actions</th>
            </tr>
          </thead>
          <tbody className="alt-tbody">
            {logs.map((log) => (
              <tr 
                key={log.id} 
                className={`alt-tr ${log.frontend_path ? 'alt-tr-clickable' : ''}`}
                onClick={() => onLogClick(log)}
              >
                <td className="alt-td alt-td-user">
                  <div className="alt-user">
                    <div className="alt-user-avatar">
                      {log.user?.name?.charAt(0) || '?'}
                    </div>
                    <div className="alt-user-info">
                      <div className="alt-user-name">{log.user?.name || 'Inconnu'}</div>
                      <div className="alt-user-role">{log.user?.role || '-'}</div>
                    </div>
                  </div>
                </td>
                
                <td className="alt-td alt-td-action">
                  <span className="alt-action-badge">{log.action}</span>
                </td>
                
                <td className="alt-td alt-td-description">
                  <div className="alt-description">
                    {log.description}
                  </div>
                  {log.model_type && (
                    <div className="alt-model-info">
                      {log.model_type.split('\\').pop()} #{log.model_id}
                    </div>
                  )}
                </td>
                
                <td className="alt-td alt-td-status">
                  <span className={`alt-status ${getStatusClass(log.status)}`}>
                    {getStatusLabel(log.status)}
                  </span>
                </td>
                
                <td className="alt-td alt-td-date">
                  <div className="alt-date">
                    {formatDate(log.created_at)}
                  </div>
                </td>
                
                <td className="alt-td alt-td-actions">
                  {(log.metadata || log.error_message) && (
                    <button
                      className="alt-details-btn"
                      onClick={(e) => onViewDetails(log, e)}
                    >
                      Détails
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default ActivityLogsTable;