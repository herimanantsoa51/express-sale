// ============================================
// src/components/dashboard/TrendsChart.jsx
// ============================================

import React from 'react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { formatCurrency, formatDate } from '../../utils/formatters';
import '../../styles/TrendsChart.css';

const TrendsChart = ({ data, period }) => {
  const CustomTooltip = ({ active, payload }) => {
    if (!active || !payload || !payload.length) return null;

    return (
      <div className="trends-chart__tooltip">
        <div className="trends-chart__tooltip-date">
          {formatDate(payload[0].payload.date || payload[0].payload.week)}
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

  return (
    <div className="trends-chart">
      <h3 className="trends-chart__title">Tendances</h3>
      <ResponsiveContainer width="100%" height={400}>
        <AreaChart data={data} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
          <defs>
            <linearGradient id="colorImmediate" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="var(--primary)" stopOpacity={0.1}/>
              <stop offset="95%" stopColor="var(--primary)" stopOpacity={0}/>
            </linearGradient>
            <linearGradient id="colorCredits" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="var(--info)" stopOpacity={0.1}/>
              <stop offset="95%" stopColor="var(--info)" stopOpacity={0}/>
            </linearGradient>
            <linearGradient id="colorReservations" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="var(--warning)" stopOpacity={0.1}/>
              <stop offset="95%" stopColor="var(--warning)" stopOpacity={0}/>
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" stroke="var(--border-color)" strokeOpacity={0.3} />
          <XAxis 
            dataKey={period === '7days' || period === '1month' ? 'date' : 'week'}
            stroke="var(--text-tertiary)"
            fontSize={12}
            tickLine={false}
            axisLine={{ stroke: 'var(--border-color)' }}
          />
          <YAxis
            stroke="var(--text-tertiary)"
            fontSize={12}
            tickLine={false}
            axisLine={{ stroke: 'var(--border-color)' }}
            tickFormatter={(value) => `${(value / 1000000).toFixed(0)}M`}
          />
          <Tooltip content={<CustomTooltip />} />
          <Area
            type="monotone"
            dataKey="immediate_sales"
            stroke="var(--primary)"
            strokeWidth={2}
            fill="url(#colorImmediate)"
            name="Ventes immédiates"
            animationDuration={1000}
            animationEasing="ease-in-out"
          />
          <Area
            type="monotone"
            dataKey="credits"
            stroke="var(--info)"
            strokeWidth={2}
            fill="url(#colorCredits)"
            name="Crédits"
            animationDuration={1000}
            animationEasing="ease-in-out"
          />
          <Area
            type="monotone"
            dataKey="reservations"
            stroke="var(--warning)"
            strokeWidth={2}
            fill="url(#colorReservations)"
            name="Réservations"
            animationDuration={1000}
            animationEasing="ease-in-out"
          />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
};

export default TrendsChart;