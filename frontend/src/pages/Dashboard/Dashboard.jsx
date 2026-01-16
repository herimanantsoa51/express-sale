// ============================================
// src/components/dashboard/Dashboard.jsx
// ============================================

import React, { useState, useEffect } from 'react';
import DashboardHeader from '../../components/dashboard/DashboardHeader';
import StatCard from '../../components/dashboard/StatCard';
import TrendsChart from '../../components/dashboard/TrendsChart';
import ExpensesBreakdown from '../../components/dashboard/ExpensesBreakdown';
import TopProducts from '../../components/dashboard/TopProducts';
import dashboardService from '../../services/dashboardService';
import { formatNumber } from '../../utils/formatters';
import '../../styles/Dashboard.css';

const Dashboard = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [period, setPeriod] = useState('7days');

  // Fetch des données
  const fetchData = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await dashboardService.getDashboardData({
        date: selectedDate,
        period: period
      });
      setData(response);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [selectedDate, period]);

  // Navigation de date
  const handleDateChange = (direction) => {
    const current = new Date(selectedDate);
    const newDate = new Date(current);
    
    if (direction === 'prev') {
      newDate.setDate(current.getDate() - 1);
    } else {
      newDate.setDate(current.getDate() + 1);
    }
    
    setSelectedDate(newDate.toISOString().split('T')[0]);
  };

  if (error) {
    return (
      <div className="dashboard">
        <div className="dashboard__container">
          <div className="dashboard__error">{error}</div>
        </div>
      </div>
    );
  }

  return (
    <div className="dashboard">
      <div className="dashboard__container">
        {/* Header */}
        {!loading && data && (
          <DashboardHeader
            date={selectedDate}
            onDateChange={handleDateChange}
            period={period}
            onPeriodChange={setPeriod}
            canGoForward={data.navigation?.can_go_forward}
          />
        )}

        {/* KPI Cards */}
        <div className="stat-cards">
          {loading ? (
            <>
              {[...Array(10)].map((_, i) => (
                <div key={i} className="stat-card__skeleton" />
              ))}
            </>
          ) : data && (
            <>
              <StatCard 
                title="CA Possible" 
                value={data.summary.ca.possible.value}
                variation={data.summary.ca.possible.vs_yesterday}
                delay={0}
              />
              <StatCard 
                title="CA Encaissé" 
                value={data.summary.ca.encaisse.value}
                variation={data.summary.ca.encaisse.vs_yesterday}
                delay={0.05}
              />
              <StatCard 
                title="Ventes Immédiates" 
                value={data.summary.immediate_sales.value}
                count={data.summary.immediate_sales.count}
                variation={data.summary.immediate_sales.vs_yesterday.value}
                countVariation={data.summary.immediate_sales.vs_yesterday.count}
                delay={0.1}
              />
              <StatCard 
                title="Nouveaux Crédits" 
                value={data.summary.new_credits.value}
                count={data.summary.new_credits.count}
                variation={data.summary.new_credits.vs_yesterday.value}
                countVariation={data.summary.new_credits.vs_yesterday.count}
                delay={0.15}
              />
              <StatCard 
                title="Paiements Crédits" 
                value={data.summary.credit_payments.value}
                count={data.summary.credit_payments.count}
                variation={data.summary.credit_payments.vs_yesterday.value}
                countVariation={data.summary.credit_payments.vs_yesterday.count}
                delay={0.2}
              />
              <StatCard 
                title="Réservations" 
                value={data.summary.new_reservations.value}
                count={data.summary.new_reservations.count}
                variation={data.summary.new_reservations.vs_yesterday.value}
                countVariation={data.summary.new_reservations.vs_yesterday.count}
                delay={0.25}
              />
              <StatCard 
                title="Dépenses" 
                value={data.summary.expenses.value}
                variation={data.summary.expenses.vs_yesterday}
                delay={0.3}
              />
              <StatCard 
                title="Nouveaux Clients" 
                value={data.summary.customers.new_customers.count}
                variation={data.summary.customers.new_customers.vs_yesterday}
                formatValue={formatNumber}
                delay={0.35}
              />
              <StatCard 
                title="Clients Récurrents" 
                value={data.summary.customers.returning_customers.count}
                variation={data.summary.customers.returning_customers.vs_yesterday}
                formatValue={formatNumber}
                delay={0.4}
              />
              <StatCard 
                title="Valeur du Stock" 
                value={data.summary.stock_value.value}
                variation="0.0%"
                delay={0.45}
              />
            </>
          )}
        </div>

        {/* Graphique de tendances */}
        {!loading && data && (
          <TrendsChart 
            data={data.trends.data}
            period={period}
            key={`${selectedDate}-${period}`}
          />
        )}

        {/* Grid: Dépenses + Top Produits */}
        {!loading && data && (
          <div className="dashboard__grid">
            <ExpensesBreakdown expenses={data.summary.expenses.breakdown} />
            <TopProducts products={data.top_products} />
          </div>
        )}
      </div>
    </div>
  );
};

export default Dashboard;