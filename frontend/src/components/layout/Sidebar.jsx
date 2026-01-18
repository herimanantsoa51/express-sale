// ============================================
// components/layout/Sidebar.jsx
// ============================================

import { NavLink } from 'react-router-dom';
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
  Euro
} from 'lucide-react';

const Sidebar = () => {
  const { isAdmin } = useAuth();

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
      roles: ['admin', 'vendeur']
    },
    {
      label: 'Mouvements de stock',
      icon: ArrowUpDown,
      path: '/mouvements-stock',
      roles: ['admin', 'vendeur']
    },
    {
      label: 'Locations',
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
      path: '/utilisateurs',
      roles: ['admin'],
    },
  ];

  // Filtrer selon rôle
  const visibleItems = menuItems.filter(item => {
    if (isAdmin()) return true;
    return item.roles.includes('vendeur');
  });

  return (
    <aside className="sidebar">
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
  );
};

export default Sidebar;