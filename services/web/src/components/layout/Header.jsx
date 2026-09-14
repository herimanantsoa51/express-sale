// ============================================
// components/layout/Header.jsx
// ============================================

import { useAuth } from '../../context/AuthContext';
import { useTheme } from '../../context/ThemeContext';
import useCompanyInfo from '../../hooks/useCompanyInfo';
import Button from '../common/Button';
import NotificationBell from '../notifications/NotificationBell';
import '../../styles/Header.css';
import { BaggageClaim, Sun, Moon } from 'lucide-react';

const Header = () => {
  const { user, logout } = useAuth();
  const { theme, toggleTheme } = useTheme();
  const { companyInfo, loading } = useCompanyInfo();

  const handleLogout = async () => {
    await logout();
    window.location.href = '/login';
  };

  // Construire l'URL complète du logo si présent
  const logoUrl = companyInfo?.logo_path 
  return (
    <header className="header">
      <div className="header-left">
        {loading ? (
          <div className="header-title-skeleton">
            <div className="skeleton-circle"></div>
            <div className="skeleton-text"></div>
          </div>
        ) : (
          <div className="header-title-wrapper">
            {logoUrl ? (
              <img 
                src={logoUrl} 
                alt={companyInfo?.name || 'Logo'} 
                className="header-logo"
                onError={(e) => {
                  // Fallback si l'image ne charge pas
                  e.target.style.display = 'none';
                  e.target.nextElementSibling.style.display = 'flex';
                }}
              />
            ) : null}
            
            {/* Fallback icon si pas de logo */}
            <div 
              className="header-logo-fallback" 
              style={{ display: logoUrl ? 'none' : 'flex' }}
            >
              <BaggageClaim size={22} />
            </div>

            <h2 className="header-title">
              {companyInfo?.name || 'Boutique'}
            </h2>
          </div>
        )}
      </div>

      <div className="header-right">
        <NotificationBell />

        <button
          className="theme-toggle" 
          onClick={toggleTheme} 
          title={theme === 'dark' ? 'Mode clair' : 'Mode sombre'}
          aria-label="Changer de thème"
        >
          {theme === 'dark' ? <Sun size={18} /> : <Moon size={18} />}
        </button>

        <div className="user-info">
          <span className="user-name">{user?.name}</span>
          <span className="user-role">{user?.role}</span>
        </div>

        <Button variant="danger" size="sm" onClick={handleLogout}>
          Déconnexion
        </Button>
      </div>
    </header>
  );
};

export default Header;
