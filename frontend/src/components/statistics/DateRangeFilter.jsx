// src/components/statistics/DateRangeFilter.jsx
import React, { useState, useEffect } from 'react';
import { Calendar, X } from 'lucide-react';
import '../../styles/DateRangeFilter.css';

const DateRangeFilter = ({ onFilterChange, initialPeriod = 'month', initialDates = {} }) => {
  const [selectedPeriod, setSelectedPeriod] = useState(initialPeriod);
  const [startDate, setStartDate] = useState(initialDates.start_date || '');
  const [endDate, setEndDate] = useState(initialDates.end_date || '');
  const [useCustomDates, setUseCustomDates] = useState(false);

  // Synchroniser avec les props
  useEffect(() => {
    if (initialDates.start_date && initialDates.end_date) {
      setStartDate(initialDates.start_date);
      setEndDate(initialDates.end_date);
      setUseCustomDates(true);
    } else {
      setSelectedPeriod(initialPeriod);
      setUseCustomDates(false);
    }
  }, [initialPeriod, initialDates]);

  // Calculer les périodes prédéfinies
  const getPredefinedDates = (period) => {
    const today = new Date();
    const start = new Date();
    
    switch(period) {
      case 'today':
        start.setHours(0, 0, 0, 0);
        return {
          startDate: start.toISOString().split('T')[0],
          endDate: today.toISOString().split('T')[0]
        };
      case 'week':
        start.setDate(start.getDate() - 7);
        return {
          startDate: start.toISOString().split('T')[0],
          endDate: today.toISOString().split('T')[0]
        };
      case 'month':
        start.setMonth(start.getMonth() - 1);
        return {
          startDate: start.toISOString().split('T')[0],
          endDate: today.toISOString().split('T')[0]
        };
      case 'year':
        start.setFullYear(start.getFullYear() - 1);
        return {
          startDate: start.toISOString().split('T')[0],
          endDate: today.toISOString().split('T')[0]
        };
      default:
        return null;
    }
  };

  const handlePeriodChange = (period) => {
    setSelectedPeriod(period);
    setUseCustomDates(false);
    setStartDate('');
    setEndDate('');
    
    // Calculer les dates pour cette période
    const dates = getPredefinedDates(period);
    if (dates) {
      onFilterChange({ 
        period,
        start_date: dates.startDate,
        end_date: dates.endDate
      });
    }
  };

  const handleDateChange = (type, value) => {
    const newStartDate = type === 'start' ? value : startDate;
    const newEndDate = type === 'end' ? value : endDate;

    if (type === 'start') setStartDate(value);
    if (type === 'end') setEndDate(value);

    // Si les deux dates sont remplies, activer le mode custom
    if (newStartDate && newEndDate) {
      setUseCustomDates(true);
      setSelectedPeriod('');
      
      // Attendre un peu pour éviter les appels multiples
      setTimeout(() => {
        onFilterChange({
          period: '',
          start_date: newStartDate,
          end_date: newEndDate
        });
      }, 300);
    }
  };

  const handleClearDates = () => {
    setStartDate('');
    setEndDate('');
    setUseCustomDates(false);
    
    // Revenir à la période par défaut
    const defaultPeriod = 'month';
    setSelectedPeriod(defaultPeriod);
    
    const dates = getPredefinedDates(defaultPeriod);
    if (dates) {
      onFilterChange({ 
        period: defaultPeriod,
        start_date: dates.startDate,
        end_date: dates.endDate
      });
    }
  };

  return (
    <div className="date-range-filter">
      <div className="date-filter-header">
        <Calendar size={18} />
        <span className="date-filter-title">Période</span>
      </div>

      {/* Périodes prédéfinies */}
      <div className="period-buttons">
        {[
          { value: 'today', label: "Aujourd'hui" },
          { value: 'week', label: '7 derniers jours' },
          { value: 'month', label: '30 derniers jours' },
          { value: 'year', label: '365 derniers jours' }
        ].map((period) => (
          <button
            key={period.value}
            className={`period-btn ${!useCustomDates && selectedPeriod === period.value ? 'active' : ''}`}
            onClick={() => handlePeriodChange(period.value)}
          >
            {period.label}
          </button>
        ))}
      </div>

      {/* Séparateur */}
      <div className="date-filter-divider">
        <span>ou</span>
      </div>

      {/* Sélection de dates personnalisées */}
      <div className="custom-date-inputs">
        <div className="date-input-group">
          <label className="date-input-label">Date de début</label>
          <input
            type="date"
            value={startDate}
            onChange={(e) => handleDateChange('start', e.target.value)}
            className="date-input"
            max={endDate || undefined}
          />
        </div>

        <div className="date-input-group">
          <label className="date-input-label">Date de fin</label>
          <input
            type="date"
            value={endDate}
            onChange={(e) => handleDateChange('end', e.target.value)}
            className="date-input"
            min={startDate || undefined}
          />
        </div>

        {(useCustomDates || (startDate && endDate)) && (
          <button
            onClick={handleClearDates}
            className="clear-dates-btn"
            title="Réinitialiser les dates"
          >
            <X size={16} />
          </button>
        )}
      </div>

      {/* Indicateur de filtre actif */}
      {(useCustomDates || selectedPeriod) && (
        <div className="active-filter-indicator">
          <span className="indicator-dot"></span>
          <span className="indicator-text">
            {useCustomDates ? (
              <>
                Du {new Date(startDate).toLocaleDateString('fr-FR', {
                  day: '2-digit',
                  month: 'short',
                  year: 'numeric'
                })} au{' '}
                {new Date(endDate).toLocaleDateString('fr-FR', {
                  day: '2-digit',
                  month: 'short',
                  year: 'numeric'
                })}
              </>
            ) : (
              {
                'today': "Aujourd'hui",
                'week': '7 derniers jours',
                'month': '30 derniers jours',
                'year': '365 derniers jours'
              }[selectedPeriod]
            )}
          </span>
        </div>
      )}
    </div>
  );
};

export default DateRangeFilter;