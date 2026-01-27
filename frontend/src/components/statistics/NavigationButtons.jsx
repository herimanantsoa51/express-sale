import '../../styles/StatsNavigationButtons.css';
import { useNavigate, useLocation } from 'react-router-dom';
import { DollarSign, Activity, Package } from 'lucide-react';

const NavigationButtons = () => {
    const navigate = useNavigate();
    const location = useLocation();

    const isActive = (path) => location.pathname === path;

    return (
        <div className="stats-nav-buttons">
             <button
                className={`stats-nav-btn ${isActive('/statistiques') ? 'active' : ''}`}
                onClick={() => navigate('/statistiques')}
            >
                <Activity size={20} />
                <span>Ventes</span>
            </button>
            <button
                className={`stats-nav-btn ${isActive('/statistiques/financieres') ? 'active' : ''}`}
                onClick={() => navigate('/statistiques/financieres')}
            >
                <DollarSign size={20} />
                <span>Finances</span>
            </button>
        </div>
    );
};

export default NavigationButtons;
