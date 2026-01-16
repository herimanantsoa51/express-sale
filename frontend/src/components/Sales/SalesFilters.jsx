// ============================================
// src/components/Sales/SalesFilters.jsx
// Filtres : période et vendeur (dates envoyées en UTC ISO)
// ============================================

import { useEffect, useState } from 'react';
import { Calendar, User, X } from 'lucide-react';
import styles from '../../styles/Sales/SalesFilters.module.css';
import usersService from '../../services/usersService';
const SalesFilters = ({ filters, onFiltersChange }) => {
  const [fromDate, setFromDate] = useState(filters.from_date || '');
  const [toDate, setToDate] = useState(filters.to_date || '');
  const [userId, setUserId] = useState(filters.user_id || '');
  const [users, setUsers] = useState([]);


  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const response = await usersService.getUsers();
        setUsers(response);
      } catch (error) {
        console.error('Erreur lors de la récupération des utilisateurs:', error);
      }
    };
    fetchUsers();
  }, []);
  // Transforme la date locale en ISO UTC
  const toUtcISOString = (localDateTime) => {
    if (!localDateTime) return '';
    const date = new Date(localDateTime);
    return date.toISOString();
  };

  // Appliquer les filtres
  const handleApply = () => {
    onFiltersChange({
      from_date: fromDate ? toUtcISOString(fromDate) : undefined,
      to_date: toDate ? toUtcISOString(toDate) : undefined,
      user_id: userId || undefined
    });
  };

  // Réinitialiser les filtres
  const handleReset = () => {
    setFromDate('');
    setToDate('');
    setUserId('');
    onFiltersChange({
      from_date: undefined,
      to_date: undefined,
      user_id: undefined
    });
  };

  const hasActiveFilters = fromDate || toDate || userId;

  return (
    <div className={styles.container}>
      <div className={styles.filters}>
        {/* Filtre Date de début */}
        <div className={styles.filterGroup}>
          <label className={styles.label}>
            <Calendar size={14} />
            <span>Du</span>
          </label>
          <input
            type="datetime-local"
            value={fromDate}
            onChange={(e) => setFromDate(e.target.value)}
            className={styles.dateInput}
          />
        </div>

        {/* Filtre Date de fin */}
        <div className={styles.filterGroup}>
          <label className={styles.label}>
            <Calendar size={14} />
            <span>Au</span>
          </label>
          <input
            type="datetime-local"
            value={toDate}
            onChange={(e) => setToDate(e.target.value)}
            className={styles.dateInput}
          />
        </div>

        {/* Filtre Vendeur */}
        <div className={styles.filterGroup}>
          <label className={styles.label}>
            <User size={14} />
            <span>Vendeur</span>
          </label>
          <select
            value={userId}
            onChange={(e) => setUserId(e.target.value)}
            className={styles.select}
          >
             <option value="">Tous</option>
             {users.map((user) => (
              <option key={user.id} value={user.id}>
                {user.name}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Actions */}
      <div className={styles.actions}>
        <button onClick={handleApply} className={styles.applyBtn}>
          Appliquer
        </button>

        {hasActiveFilters && (
          <button onClick={handleReset} className={styles.resetBtn}>
            <X size={16} />
            Réinitialiser
          </button>
        )}
      </div>
    </div>
  );
};

export default SalesFilters;
