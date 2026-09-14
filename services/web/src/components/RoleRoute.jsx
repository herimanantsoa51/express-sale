// ============================================
// components/RoleRoute.jsx - Protection par rôle
// ============================================

import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

/**
 * Composant pour protéger une route selon le(s) rôle(s) requis
 * @param {array} allowedRoles - Liste des rôles autorisés ['admin', 'vendeur']
 * @param {node} children - Composant enfant à afficher si autorisé
 */
const RoleRoute = ({ allowedRoles = [], children }) => {
  const { user } = useAuth();

  // Si pas d'utilisateur connecté, rediriger vers login
  if (!user) {
    return <Navigate to="/login" replace />;
  }

  // Si le rôle de l'utilisateur n'est pas dans la liste autorisée
  if (!allowedRoles.includes(user.role)) {
    return <Navigate to="/unauthorized" replace />;
  }

  // L'utilisateur a le bon rôle, afficher le composant
  return children;
};

export default RoleRoute;