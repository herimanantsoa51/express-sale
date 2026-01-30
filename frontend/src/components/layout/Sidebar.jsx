// ============================================
// components/layout/Sidebar.jsx
// ============================================

import { NavLink } from 'react-router-dom';
import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import '../../styles/Sidebar.css';
import {
  ChartColumnStacked,
  ShoppingCart,
  Package,
  Calendar,
  CreditCard,
  Users,
  Factory,
  DollarSign,
  TrendingUp,
  Settings,
  PackagePlus,
  Van,
  ArrowUpDown,
  Warehouse,
  Handbag,
  MoveDown,
  Euro,
  Menu,
  X,
  Logs
} from 'lucide-react';

const Sidebar = () => {
  const { isAdmin } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [isMobile, setIsMobile] = useState(false);

  // Détecter si on est sur mobile
  useEffect(() => {
    const checkMobile = () => {
      setIsMobile(window.innerWidth <= 768);
      if (window.innerWidth > 768) {
        setIsOpen(false); // Fermer le menu si on passe en desktop
      }
    };

    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // Fermer la sidebar quand on clique sur un lien (mobile)
  const handleLinkClick = () => {
    if (isMobile) {
      setIsOpen(false);
    }
  };

  const menuItems = [
    {
      icon: ChartColumnStacked,
      label: 'Dashboard',
      path: '/dashboard',
      roles: ['admin', 'vendeur'],
    },
    {
      icon: ShoppingCart,
      label: 'Point de vente',
      path: '/ventes/rapide',
      roles: ['admin', 'vendeur'],
    },
    {
      icon: Package,
      label: 'Produits',
      path: '/produits',
      roles: ['admin', 'vendeur'],
    },
    {
      label: 'Réapprovisionnement',
      icon: PackagePlus,
      path: '/reapprovisionnements',
      roles: ['admin']
    },
    {
      label: 'Mouvements de stock',
      icon: ArrowUpDown,
      path: '/mouvements-stock',
      roles: ['admin']
    },
    {
      label: 'Emplacements',
      icon: Warehouse,
      path: '/locations',
      roles: ['admin', 'vendeur']
    },
    {
      label: 'Ventes rapides',
      icon: Handbag,
      path: '/ventes/immediates',
      roles: ['admin', 'vendeur']
    },
    {
      icon: Calendar,
      label: 'Réservations',
      path: '/ventes/reservations',
      roles: ['admin', 'vendeur'],
    },
    {
      icon: CreditCard,
      label: 'Crédits',
      path: '/ventes/credits',
      roles: ['admin', 'vendeur'],
    },
    {
      icon: Users,
      label: 'Clients',
      path: '/clients',
      roles: ['admin'],
    },
    {
      icon: Factory,
      label: 'Fournisseurs',
      path: '/fournisseurs',
      roles: ['admin'],
    },
    {
      icon: Van,
      label: 'Transitaires',
      path: '/transitaires',
      roles: ['admin'],
    },
    {
      icon: DollarSign,
      label: 'Trésorerie',
      path: '/comptes',
      roles: ['admin'],
    },
    {
      icon: MoveDown,
      label: 'Dépenses',
      path: '/depenses',
      roles: ['admin'],
    },
    {
      icon: TrendingUp,
      label: 'Statistiques',
      path: '/statistiques',
      roles: ['admin'],
    },
    {
      icon: Euro,
      label: 'Comptages',
      path: '/comptages',
      roles: ['admin','vendeur'],
    },
    {
      icon: Settings,
      label: 'Configuration',
      path: '/parametres',
      roles: ['admin','vendeur'],
    },
    {
      icon: Logs,
      label: "Journaux d'activité",
      path: "/journaux-activite",
      roles: ['admin'],
    }
  ];

  // Filtrer selon rôle
  const visibleItems = menuItems.filter(item => {
    if (isAdmin()) return true;
    return item.roles.includes('vendeur');
  });

  return (
    <>
      {/* Bouton toggle (visible seulement sur mobile) */}
      {isMobile && (
        <button 
          className="sidebar-toggle"
          onClick={() => setIsOpen(!isOpen)}
          aria-label={isOpen ? 'Fermer le menu' : 'Ouvrir le menu'}
        >
          {isOpen ? <X size={24} /> : <Menu size={24} />}
        </button>
      )}

      {/* Overlay pour fermer en cliquant à côté (mobile) */}
      {isMobile && isOpen && (
        <div 
          className="sidebar-overlay"
          onClick={() => setIsOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside className={`sidebar ${isOpen ? 'open' : ''}`}>
        <nav className="sidebar-nav">
          {visibleItems.map((item) => {
            const Icon = item.icon;
            return (
              <NavLink
                key={item.path}
                to={item.path}
                className={({ isActive }) =>
                  isActive ? 'sidebar-link sidebar-link-active' : 'sidebar-link'
                }
                onClick={handleLinkClick}
              >
                <span className="sidebar-icon">
                  <Icon size={18} />
                </span>
                <span className="sidebar-label">{item.label}</span>
              </NavLink>
            );
          })}
        </nav>
      </aside>
    </>
  );
};

export default Sidebar;