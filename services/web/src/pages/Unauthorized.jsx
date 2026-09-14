// ============================================
// pages/Unauthorized.jsx
// ============================================

import { useNavigate } from 'react-router-dom';
import { ShieldAlert, ArrowLeft, Home } from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import '../styles/Unauthorized.css';

const Unauthorized = () => {
  const navigate = useNavigate();
  const { user } = useAuth();

  const handleGoBack = () => {
    navigate(-1);
  };

  const handleGoHome = () => {
    navigate('/dashboard');
  };

  return (
    <div className="unauthorized-page">
      {/* Fond animé */}
      <div className="unauthorized-background">
        <div className="unauthorized-circle circle-1"></div>
        <div className="unauthorized-circle circle-2"></div>
      </div>

      {/* Contenu */}
      <div className="unauthorized-content">
        <div className="unauthorized-icon">
          <ShieldAlert size={80} strokeWidth={1.5} />
        </div>

        <h1 className="unauthorized-title">Accès refusé</h1>
        
        <p className="unauthorized-message">
          Vous n'avez pas les permissions nécessaires pour accéder à cette page.
        </p>

        {user && (
          <p className="unauthorized-info">
            Connecté en tant que <strong>{user.username}</strong> ({user.role})
          </p>
        )}

        <div className="unauthorized-actions">
          <button 
            onClick={handleGoBack}
            className="unauthorized-button secondary"
          >
            <ArrowLeft size={18} />
            <span>Retour</span>
          </button>
          
          <button 
            onClick={handleGoHome}
            className="unauthorized-button primary"
          >
            <Home size={18} />
            <span>Accueil</span>
          </button>
        </div>

        <div className="unauthorized-footer">
          <p>Si vous pensez qu'il s'agit d'une erreur, contactez votre administrateur.</p>
        </div>
      </div>
    </div>
  );
};

export default Unauthorized;