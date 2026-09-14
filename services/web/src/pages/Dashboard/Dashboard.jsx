// ============================================
// src/components/dashboard/Dashboard.jsx
// ============================================

import React, { useState, useEffect } from 'react';
import DashboardHeader from '../../components/dashboard/DashboardHeader';
import StatCard from '../../components/dashboard/StatCard';
import TrendsChart from '../../components/dashboard/TrendsChart';
import ExpensesBreakdown from '../../components/dashboard/ExpensesBreakdown';
import TopProducts from '../../components/dashboard/TopProducts';
import StockCostBreakdown from '../../components/dashboard/StockCostBreakdown'; // ✅ Nouveau composant
import dashboardService from '../../services/dashboardService';
import { formatNumber } from '../../utils/formatters';
import '../../styles/Dashboard.css';

const Dashboard = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [period, setPeriod] = useState('7days');
  const [topProductsSort, setTopProductsSort] = useState('revenue');

  // Fetch des données
  const fetchData = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await dashboardService.getDashboardData({
        date: selectedDate,
        period: period,
        top_products_sort: topProductsSort
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
  }, [selectedDate, period, topProductsSort]);

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
              {[...Array(16)].map((_, i) => ( // ✅ Mis à jour à 16 cartes
                <div key={i} className="stat-card__skeleton" />
              ))}
            </>
          ) : data && (
            <>
              {/* CA Section */}
              <StatCard 
                title="CA Facturé" 
                value={data.summary.ca.facture.value}
                variation={data.summary.ca.facture.vs_yesterday}
                delay={0}
              />
              <StatCard 
                title="CA Encaissé" 
                value={data.summary.ca.encaisse.value}
                variation={data.summary.ca.encaisse.vs_yesterday}
                delay={0.05}
              />

              {/* Ventes Immédiates */}
              <StatCard 
                title="Ventes Immédiates (Revenus)" 
                value={data.summary.immediate_sales.revenue}
                count={data.summary.immediate_sales.count}
                variation={data.summary.immediate_sales.vs_yesterday.revenue}
                countVariation={data.summary.immediate_sales.vs_yesterday.count}
                delay={0.1}
              />
              <StatCard 
                title="Ventes Immédiates (Bénéfice)" 
                value={data.summary.immediate_sales.profit}
                variation={data.summary.immediate_sales.vs_yesterday.profit}
                delay={0.15}
              />

              {/* Crédits */}
              <StatCard 
                title="Nouveaux Crédits Conclus (Revenus)" 
                value={data.summary.credit_sales.new_credits.revenue}
                count={data.summary.credit_sales.new_credits.count}
                variation={data.summary.credit_sales.new_credits.vs_yesterday.revenue}
                countVariation={data.summary.credit_sales.new_credits.vs_yesterday.count}
                delay={0.2}
              />
              <StatCard 
                title="Nouveaux Crédits Conclus (Bénéfice)" 
                value={data.summary.credit_sales.new_credits.profit}
                variation={data.summary.credit_sales.new_credits.vs_yesterday.revenue}
                delay={0.25}
              />
              <StatCard 
                title="Paiements Crédits" 
                value={data.summary.credit_sales.payments.value}
                count={data.summary.credit_sales.payments.count}
                variation={data.summary.credit_sales.payments.vs_yesterday.value}
                countVariation={data.summary.credit_sales.payments.vs_yesterday.count}
                delay={0.3}
              />

              {/* Réservations */}
              <StatCard 
                title="Nouvelles Réservations (Revenus)" 
                value={data.summary.reservation_sales.new_reservations.revenue}
                count={data.summary.reservation_sales.new_reservations.count}
                variation={data.summary.reservation_sales.new_reservations.vs_yesterday.revenue}
                countVariation={data.summary.reservation_sales.new_reservations.vs_yesterday.count}
                delay={0.35}
              />
              <StatCard 
                title="Nouvelles Réservations (Bénéfice)" 
                value={data.summary.reservation_sales.new_reservations.profit}
                variation={data.summary.reservation_sales.new_reservations.vs_yesterday.profit}
                delay={0.4}
              />
              {/* ✅ NOUVEAU : Paiements de Réservations */}
              <StatCard 
                title="Paiements Réservations" 
                value={data.summary.reservation_sales.payments.value}
                count={data.summary.reservation_sales.payments.count}
                variation={data.summary.reservation_sales.payments.vs_yesterday.value}
                countVariation={data.summary.reservation_sales.payments.vs_yesterday.count}
                delay={0.45}
              />

              {/* Bénéfices Globaux */}
              <StatCard 
                title="Bénéfice Brut" 
                value={data.summary.profits.gross_profit.value}
                variation={data.summary.profits.gross_profit.vs_yesterday}
                delay={0.5}
              />
              <StatCard 
                title="Bénéfice Net" 
                value={data.summary.profits.net_profit.value}
                variation={data.summary.profits.net_profit.vs_yesterday}
                delay={0.55}
              />

              {/* Dépenses & Pertes */}
              <StatCard 
                title="Dépenses" 
                value={data.summary.expenses.value}
                variation={data.summary.expenses.vs_yesterday}
                delay={0.6}
              />
              <StatCard 
                title="Pertes Stock" 
                value={data.summary.losses.stock_losses.value}
                variation={data.summary.losses.stock_losses.vs_yesterday}
                delay={0.65}
              />

              {/* Clients */}
              <StatCard 
                title="Nouveaux Clients" 
                value={data.summary.customers.new_customers.count}
                variation={data.summary.customers.new_customers.vs_yesterday}
                formatValue={formatNumber}
                delay={0.7}
              />
              <StatCard 
                title="Clients Récurrents" 
                value={data.summary.customers.returning_customers.count}
                variation={data.summary.customers.returning_customers.vs_yesterday}
                formatValue={formatNumber}
                delay={0.75}
              />

              {/* Stock - Par prix de vente */}
              <StatCard 
                title="Valeur Stock (Prix vente)" 
                value={data.summary.stock_value.value}
                variation="0.0%"
                delay={0.8}
              />
              {/* ✅ NOUVEAU : Stock - Par coût */}
              <StatCard 
                title="Valeur Stock (Coût)" 
                value={data.summary.stock_cost_value.value}
                variation="0.0%"
                delay={0.85}
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
            <div className="dashboard__grid-column">
              <ExpensesBreakdown expenses={data.summary.expenses.breakdown} />
              {/* ✅ NOUVEAU : Détail Stock par coût */}
              <StockCostBreakdown 
                breakdown={data.summary.stock_cost_value.breakdown}
                totalValue={data.summary.stock_cost_value.value}
              />
            </div>
            <div className="dashboard__grid-column">
              <TopProducts 
                products={data.top_products} 
                sortBy={topProductsSort}
                onSortChange={setTopProductsSort}
              />
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default Dashboard;