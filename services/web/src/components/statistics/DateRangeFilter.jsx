// src/components/statistics/DateRangeFilter.jsx
import React, { useState, useEffect, useRef } from 'react';
import { Calendar, ChevronLeft, ChevronRight } from 'lucide-react';
import '../../styles/DateRangeFilter.css';

const DateRangeFilter = ({ onFilterChange, initialPeriod = 'month' }) => {
  const [currentDate, setCurrentDate] = useState(() => {
    const now = new Date();
    console.log('DateRangeFilter initialized with:', now.toLocaleDateString('fr-FR'));
    return now;
  });
  
  const [periodType, setPeriodType] = useState(initialPeriod);
  const lastParamsRef = useRef(null);
  const hasInitialized = useRef(false);

  const calculatePeriodDates = (type, date) => {
    const d = new Date(date);
    let start, end;
  
    // Helper pour formater en local (pas UTC)
    const formatLocalDate = (date) => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };
  
    switch(type) {
      case 'week':
        const dayOfWeek = d.getDay();
        start = new Date(d);
        start.setDate(d.getDate() - dayOfWeek);
        end = new Date(start);
        end.setDate(start.getDate() + 6);
        break;
  
      case 'month':
        start = new Date(d.getFullYear(), d.getMonth(), 1);
        end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
        break;
  
      case 'year':
        start = new Date(d.getFullYear(), 0, 1);
        end = new Date(d.getFullYear(), 11, 31);
        break;
  
      default:
        start = new Date(d.getFullYear(), d.getMonth(), 1);
        end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    }
  
    const result = {
      start: formatLocalDate(start),
      end: formatLocalDate(end)
    };
  
    console.log('📅 Calculated period:', result);
    return result;
  };
  useEffect(() => {
    const dates = calculatePeriodDates(periodType, currentDate);
    const newParams = {
      period: periodType,
      start_date: dates.start,
      end_date: dates.end
    };

    // Vérifier si les paramètres ont vraiment changé
    const paramsString = JSON.stringify(newParams);
    if (lastParamsRef.current === paramsString) {
      return;
    }

    lastParamsRef.current = paramsString;
    
    // Premier appel immédiat, les suivants avec délai
    if (!hasInitialized.current) {
      hasInitialized.current = true;
      onFilterChange(newParams);
    } else {
      const timer = setTimeout(() => {
        onFilterChange(newParams);
      }, 100);
      return () => clearTimeout(timer);
    }
  }, [periodType, currentDate]);

  const navigate = (direction) => {
    const newDate = new Date(currentDate);
    
    switch(periodType) {
      case 'week':
        newDate.setDate(newDate.getDate() + (direction === 'next' ? 7 : -7));
        break;
      case 'month':
        newDate.setMonth(newDate.getMonth() + (direction === 'next' ? 1 : -1));
        break;
      case 'year':
        newDate.setFullYear(newDate.getFullYear() + (direction === 'next' ? 1 : -1));
        break;
    }
    
    setCurrentDate(newDate);
  };

  const goToToday = () => {
    setCurrentDate(new Date());
  };

  const getPeriodLabel = () => {
    const dates = calculatePeriodDates(periodType, currentDate);
    const start = new Date(dates.start);
    const end = new Date(dates.end);

    const options = { day: '2-digit', month: 'short', year: 'numeric' };

    if (periodType === 'year') {
      return start.getFullYear().toString();
    }

    if (periodType === 'month') {
      return start.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
    }

    return `${start.toLocaleDateString('fr-FR', options)} - ${end.toLocaleDateString('fr-FR', options)}`;
  };

  const isCurrentPeriod = () => {
    const today = new Date();
    const dates = calculatePeriodDates(periodType, today);
    const currentDates = calculatePeriodDates(periodType, currentDate);
    return dates.start === currentDates.start && dates.end === currentDates.end;
  };

  return (
    <div className="date-range-filter">
      <div className="date-filter-header">
        <Calendar size={18} />
        <span className="date-filter-title">Période d'analyse</span>
      </div>

      <div className="period-buttons">
        {[
          { value: 'week', label: 'Semaine' },
          { value: 'month', label: 'Mois' },
          { value: 'year', label: 'Année' }
        ].map((period) => (
          <button
            key={period.value}
            className={`period-btn ${periodType === period.value ? 'active' : ''}`}
            onClick={() => setPeriodType(period.value)}
          >
            {period.label}
          </button>
        ))}
      </div>

      <div className="period-navigator">
        <button 
          className="dt-nav-btn" 
          onClick={() => navigate('prev')}
          title="Période précédente"
        >
          <ChevronLeft size={20} />
        </button>

        <div className="period-display">
          <span className="period-label">{getPeriodLabel()}</span>
        </div>

        <button 
          className="dt-nav-btn" 
          onClick={() => navigate('next')}
          title="Période suivante"
        >
          <ChevronRight size={20} />
        </button>
      </div>

      {!isCurrentPeriod() && (
        <button className="today-btn" onClick={goToToday}>
          Aujourd'hui
        </button>
      )}
    </div>
  );
};

export default DateRangeFilter;