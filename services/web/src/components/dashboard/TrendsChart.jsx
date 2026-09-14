// ============================================
// src/components/dashboard/TrendsChart.jsx
// ============================================

import React, { useState } from 'react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';
import { formatCurrency, formatDate } from '../../utils/formatters';
import '../../styles/TrendsChart.css';

const TrendsChart = ({ data, period }) => {
  const [activeView, setActiveView] = useState('revenue'); // 'revenue' ou 'profit'

  // Convertir data en tableau si ce n'est pas déjà le cas
  const chartData = Array.isArray(data) ? data : Object.values(data || {});

  const CustomTooltip = ({ active, payload }) => {
    if (!active || !payload || !payload.length) return null;

    return (
      <div className="trends-chart__tooltip">
        <div className="trends-chart__tooltip-date">
          {formatDate(payload[0].payload.period)}
        </div>
        {payload.map((entry, index) => (
          <div 
            key={index} 
            className="trends-chart__tooltip-item"
            style={{ color: entry.color }}
          >
            {entry.name}: {formatCurrency(entry.value)}
          </div>
        ))}
      </div>
    );
  };

  const revenueConfig = [
    { dataKey: 'immediate_sales', name: 'Ventes immédiates', color: 'var(--primary)', gradientId: 'colorImmediate' },
    { dataKey: 'credits', name: 'Crédits', color: 'var(--info)', gradientId: 'colorCredits' },
    { dataKey: 'reservations', name: 'Réservations', color: 'var(--warning)', gradientId: 'colorReservations' },
    { dataKey: 'total_revenue', name: 'CA Total', color: 'var(--success)', gradientId: 'colorTotal' },
  ];

  const profitConfig = [
    { dataKey: 'immediate_profit', name: 'Bénéfice Immédiates', color: 'var(--primary)', gradientId: 'colorImmediateProfit' },
    { dataKey: 'credit_profit', name: 'Bénéfice Crédits', color: 'var(--info)', gradientId: 'colorCreditProfit' },
    { dataKey: 'reservation_profit', name: 'Bénéfice Réservations', color: 'var(--warning)', gradientId: 'colorReservationProfit' },
    { dataKey: 'gross_profit', name: 'Bénéfice Brut', color: 'var(--success)', gradientId: 'colorGrossProfit' },
    { dataKey: 'net_profit', name: 'Bénéfice Net', color: 'var(--danger)', gradientId: 'colorNetProfit' },
  ];

  const currentConfig = activeView === 'revenue' ? revenueConfig : profitConfig;

  return (
    <div className="trends-chart">
      <div className="trends-chart__header">
        <h3 className="trends-chart__title">Tendances</h3>
        <div className="trends-chart__tabs">
          <button 
            className={`trends-chart__tab ${activeView === 'revenue' ? 'trends-chart__tab--active' : ''}`}
            onClick={() => setActiveView('revenue')}
          >
            Revenus
          </button>
          <button 
            className={`trends-chart__tab ${activeView === 'profit' ? 'trends-chart__tab--active' : ''}`}
            onClick={() => setActiveView('profit')}
          >
            Bénéfices
          </button>
        </div>
      </div>

      <ResponsiveContainer width="100%" height={400}>
        <AreaChart data={chartData} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
          <defs>
            {currentConfig.map((config) => (
              <linearGradient key={config.gradientId} id={config.gradientId} x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor={config.color} stopOpacity={0.15}/>
                <stop offset="95%" stopColor={config.color} stopOpacity={0}/>
              </linearGradient>
            ))}
          </defs>
          
          <CartesianGrid strokeDasharray="3 3" stroke="var(--border-color)" strokeOpacity={0.3} />
          
          <XAxis 
            dataKey="period"
            stroke="var(--text-tertiary)"
            fontSize={12}
            tickLine={false}
            axisLine={{ stroke: 'var(--border-color)' }}
            tickFormatter={(value) => {
              const date = new Date(value);
              return `${date.getDate()}/${date.getMonth() + 1}`;
            }}
          />
          
          <YAxis
            stroke="var(--text-tertiary)"
            fontSize={12}
            tickLine={false}
            axisLine={{ stroke: 'var(--border-color)' }}
            tickFormatter={(value) => {
              if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
              if (value >= 1000) return `${(value / 1000).toFixed(0)}k`;
              return value;
            }}
          />
          
          <Tooltip content={<CustomTooltip />} />
          
          <Legend 
            wrapperStyle={{ paddingTop: '20px' }}
            iconType="line"
          />
          
          {currentConfig.map((config) => (
            <Area
              key={config.dataKey}
              type="monotone"
              dataKey={config.dataKey}
              stroke={config.color}
              strokeWidth={2}
              fill={`url(#${config.gradientId})`}
              name={config.name}
              animationDuration={1000}
              animationEasing="ease-in-out"
            />
          ))}
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
};

export default TrendsChart;