// ============================================
// src/components/dashboard/DashboardHeader.jsx
// ============================================

import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { formatDate } from '../../utils/formatters';
import '../../styles/DashboardHeader.css';

const DashboardHeader = ({ date, onDateChange, period, onPeriodChange, canGoForward }) => {
  const periods = [
    { value: '7days', label: '7 jours' },
    { value: '1month', label: '1 mois' },
    { value: '2months', label: '2 mois' },
    { value: '3months', label: '3 mois' }
  ];

  return (
    <div className="header">
      <div className="header__date-nav">
        <button
          onClick={() => onDateChange('prev')}
          className="header__nav-btn"
        >
          <ChevronLeft size={18} />
        </button>

        <div className="header__date-display">
          {formatDate(date)}
        </div>

        <button
          onClick={() => onDateChange('next')}
          disabled={!canGoForward}
          className="header__nav-btn"
        >
          <ChevronRight size={18} />
        </button>
      </div>

      <div className="header__period-selector">
        {periods.map(p => (
          <button
            key={p.value}
            onClick={() => onPeriodChange(p.value)}
            className={`header__period-btn ${period === p.value ? 'header__period-btn--active' : ''}`}
          >
            {p.label}
          </button>
        ))}
      </div>
    </div>
  );
};

export default DashboardHeader;