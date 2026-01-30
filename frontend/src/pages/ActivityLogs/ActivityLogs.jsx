import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import useActivityLogs from '../../hooks/useActivityLogs';
import ActivityLogsFilters from '../../components/ActivityLogs/ActivityLogsFilters';
import ActivityLogsTable from '../../components/ActivityLogs/ActivityLogsTable';
import ActivityLogDetailModal from '../../components/ActivityLogs/ActivityLogDetailModal';
import ActivityLogsPagination from '../../components/ActivityLogs/ActivityLogsPagination';
import ActivityLogsManagement from '../../components/ActivityLogs/ActivityLogsManagement';
import '../../styles/ActivityLogs.css';

const ActivityLogs = () => {
    const navigate = useNavigate();
    const { logs, pagination, loading, error, filters, updateFilters, goToPage, refetch } = useActivityLogs();
    const [selectedLog, setSelectedLog] = useState(null);
    const [showDetailModal, setShowDetailModal] = useState(false);
    const [showManagement, setShowManagement] = useState(false);
  
    const handleLogClick = (log) => {
      if (log.frontend_path) {
        navigate(`/${log.frontend_path}`);
      } else {
        setSelectedLog(log);
        setShowDetailModal(true);
      }
    };
  
    const handleViewDetails = (log, event) => {
      event.stopPropagation();
      setSelectedLog(log);
      setShowDetailModal(true);
    };
  
    const handleCloseModal = () => {
      setShowDetailModal(false);
      setSelectedLog(null);
    };
  
    return (
      <div className="al-container">
        <div className="al-header">
          <div className="al-header-content">
            <h1 className="al-title">Journal d'activité</h1>
            <p className="al-subtitle">
              Historique complet des actions effectuées dans le système
            </p>
          </div>
          <button 
            className="al-manage-button"
            onClick={() => setShowManagement(!showManagement)}
          >
            {showManagement ? '← Retour aux logs' : '⚙ Gestion des logs'}
          </button>
        </div>
  
        {showManagement ? (
          <ActivityLogsManagement onRefresh={refetch} />
        ) : (
          <>
            <ActivityLogsFilters 
              filters={filters}
              onFilterChange={updateFilters}
              onRefetch={refetch}
            />
  
            {error && (
              <div className="al-error-banner">
                <div className="al-error-content">
                  <span className="al-error-text">{error}</span>
                </div>
              </div>
            )}
  
            <div className="al-content">
              {loading ? (
                <div className="al-loading">
                  <div className="al-spinner"></div>
                  <p className="al-loading-text">Chargement des logs...</p>
                </div>
              ) : logs.length === 0 ? (
                <div className="al-empty">
                  <div className="al-empty-content">
                    <div className="al-empty-icon">📋</div>
                    <h3 className="al-empty-title">Aucun log trouvé</h3>
                    <p className="al-empty-description">
                      Aucune activité ne correspond à vos critères de recherche
                    </p>
                  </div>
                </div>
              ) : (
                <>
                  <ActivityLogsTable 
                    logs={logs}
                    onLogClick={handleLogClick}
                    onViewDetails={handleViewDetails}
                  />
                  
                  {pagination && (
                    <ActivityLogsPagination 
                      pagination={pagination}
                      onPageChange={goToPage}
                    />
                  )}
                </>
              )}
            </div>
          </>
        )}
  
        {showDetailModal && selectedLog && (
          <ActivityLogDetailModal 
            log={selectedLog}
            onClose={handleCloseModal}
          />
        )}
      </div>
    );
  };
  
  export default ActivityLogs;