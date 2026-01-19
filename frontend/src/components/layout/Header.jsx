// ============================================
// components/layout/Header.jsx
// ============================================

import { useAuth } from '../../context/AuthContext';
import { useTheme } from '../../context/ThemeContext';
import Button from '../common/Button';
import NotificationBell from '../notifications/NotificationBell';
import '../../styles/Header.css';
import { BaggageClaim, Sun, Moon } from 'lucide-react';

const Header = () => {
  const { user, logout } = useAuth();
  const { theme, toggleTheme } = useTheme();

  const handleLogout = async () => {
    await logout();
    window.location.href = '/login';
  };

  return (
    <header className="header">
      <div className="header-left">
        <h2 className="header-title">
          <BaggageClaim size={22} /> Boutique
        </h2>
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