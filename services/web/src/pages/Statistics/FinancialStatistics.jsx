// src/pages/FinancialStatistics.jsx
import React, { useState, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  LineChart, Line, BarChart, Bar, PieChart, Pie, Cell,
  AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip,
  ResponsiveContainer, Legend, ComposedChart
} from 'recharts';
import {
  TrendingUp, TrendingDown, DollarSign, AlertCircle, Loader2,
  Activity, PieChart as PieChartIcon, Package, AlertTriangle,
  CheckCircle, Minus, CreditCard, Calendar
} from 'lucide-react';
import statisticsService from '../../services/statisticsService';
import KPICard from '../../components/statistics/KPICard';
import Section from '../../components/statistics/Section';
import ChartContainer from '../../components/statistics/ChartContainer';
import CustomTooltip from '../../components/statistics/CustomTooltip';
import DateRangeFilter from '../../components/statistics/DateRangeFilter';
import { formatCurrency, formatNumber } from '../../utils/formatters';
import NavigationButtons from '../../components/statistics/NavigationButtons';
import '../../styles/FinancialStatistics.css';

const CHART_COLORS = {
  revenue: '#30d158',
  costs: '#ff9f0a',
  grossProfit: '#0071e3',
  netProfit: '#5ac8fa',
  expenses: '#ff3b30',
  losses: '#bf5af2',
  immediate: '#0071e3',
  credit: '#ff9f0a',
  reservation: '#5ac8fa'
};

const FinancialStatistics = () => {
  const navigate = useNavigate();
  const [dashboard, setDashboard] = useState(null);
  const [timeline, setTimeline] = useState(null);
  const [profits, setProfits] = useState(null);
  const [expenses, setExpenses] = useState(null);
  const [losses, setLosses] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  const currentRequestRef = useRef(null);

  const handleFilterChange = async (params) => {
    if (!params?.start_date || !params?.end_date) return;
    
    // Annuler la requête précédente si elle existe
    if (currentRequestRef.current) {
      currentRequestRef.current = null;
    }
    
    const requestId = Date.now();
    currentRequestRef.current = requestId;
    
    setLoading(true);
    setError(null);
    
    try {
      const start = new Date(params.start_date);
      const end = new Date(params.end_date);
      const diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24));
      
      let grouping = 'day';
      if (diffDays > 365) grouping = 'month';
      else if (diffDays > 90) grouping = 'week';
      
      const apiParams = {
        start_date: params.start_date,
        end_date: params.end_date
      };
      
      const [dashboardData, timelineData, profitsData, expensesData, lossesData] = await Promise.all([
        statisticsService.financialDashboard(apiParams),
        statisticsService.financialTimeline(params.start_date, params.end_date, grouping),
        statisticsService.profitsOverview(apiParams),
        statisticsService.expensesOverview(apiParams),
        statisticsService.lossesOverview(apiParams)
      ]);

      // Vérifier si cette requête est toujours d'actualité
      if (currentRequestRef.current !== requestId) {
        return; // Une nouvelle requête a été lancée, ignorer celle-ci
      }

      setDashboard(dashboardData);
      setTimeline(timelineData);
      setProfits(profitsData);
      setExpenses(expensesData);
      setLosses(lossesData);
    } catch (err) {
      if (currentRequestRef.current !== requestId) {
        return;
      }
      console.error('Error loading financial data:', err);
      setError('Impossible de charger les données financières');
    } finally {
      if (currentRequestRef.current === requestId) {
        setLoading(false);
      }
    }
  };

  if (error) {
    return (
      <div className="stats-error">
        <AlertCircle size={48} />
        <p>{error}</p>
        <button onClick={() => window.location.reload()} className="stats-retry-btn">
          Réessayer
        </button>
      </div>
    );
  }

  const revenue = dashboard?.revenue;
  const costs = dashboard?.costs;
  const profitsSummary = dashboard?.profits;
  const expensesSummary = dashboard?.expenses;
  const lossesSummary = dashboard?.losses;

  return (
    <div className="stats-container">
      {loading && (
        <div className="stats-loading-overlay">
          <div className="stats-loading-spinner-container">
            <Loader2 className="stats-loading-spinner" size={48} />
            <p>Chargement des statistiques financières...</p>
          </div>
        </div>
      )}

      {/* Date Range Filter */}
      <DateRangeFilter 
        onFilterChange={handleFilterChange}
        initialPeriod="month"
      />

      {/* Navigation Buttons */}
      <NavigationButtons />

      {!dashboard ? (
        <div className="stats-empty">
          <Calendar size={48} />
          <p>Sélectionnez une période pour voir les statistiques financières</p>
        </div>
      ) : (
        <>
          {/* KPI Cards - Revenus & Bénéfices */}
          <div className="stats-grid">
            <KPICard
              icon={<DollarSign size={24} />}
              label="Revenus encaissés"
              value={formatCurrency(revenue.total)}
              meta={`Total facturé: ${formatCurrency(revenue.immediate + revenue.credit + revenue.reservation)}`}
              color="success"
            />
            <KPICard
              icon={<TrendingUp size={24} />}
              label="Bénéfice brut"
              value={formatCurrency(profitsSummary.summary.gross_profit)}
              meta={`Marge: ${profitsSummary.summary.gross_margin}%`}
              progress={profitsSummary.summary.gross_margin}
              color="primary"
            />
            <KPICard
              icon={<Activity size={24} />}
              label="Bénéfice net"
              value={formatCurrency(profitsSummary.summary.net_profit)}
              meta={`Marge: ${profitsSummary.summary.net_margin}%`}
              progress={profitsSummary.summary.net_margin}
              color="info"
            />
            <KPICard
              icon={<TrendingDown size={24} />}
              label="Coûts totaux"
              value={formatCurrency(costs.total + expensesSummary.actual.total)}
              meta={`Produits: ${formatCurrency(costs.total)} | Opérations: ${formatCurrency(expensesSummary.actual.total)}`}
              color="warning"
            />
          </div>

          {/* Graphique temporel - 2 courbes (Brut & Net) */}
          {timeline?.data?.length > 0 && (
            <Section
              icon={<Activity size={24} />}
              title="Évolution des bénéfices"
              description="Bénéfice brut (revenus - coûts) et bénéfice net (après dépenses opérationnelles)"
            >
              <ChartContainer>
                <ResponsiveContainer width="100%" height={400}>
                  <ComposedChart data={timeline.data}>
                    <defs>
                      <linearGradient id="colorGrossProfit" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor={CHART_COLORS.grossProfit} stopOpacity={0.3}/>
                        <stop offset="95%" stopColor={CHART_COLORS.grossProfit} stopOpacity={0}/>
                      </linearGradient>
                      <linearGradient id="colorNetProfit" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor={CHART_COLORS.netProfit} stopOpacity={0.3}/>
                        <stop offset="95%" stopColor={CHART_COLORS.netProfit} stopOpacity={0}/>
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.05)" />
                    <XAxis dataKey="period" stroke="var(--text-secondary)" style={{ fontSize: '12px' }} />
                    <YAxis stroke="var(--text-secondary)" style={{ fontSize: '12px' }} tickFormatter={formatCurrency} />
                    <Tooltip content={<CustomTooltip />} />
                    <Legend />
                    <Area 
                      type="monotone" 
                      dataKey="gross_profit" 
                      stroke={CHART_COLORS.grossProfit} 
                      strokeWidth={2} 
                      fillOpacity={1} 
                      fill="url(#colorGrossProfit)" 
                      name="Bénéfice brut" 
                    />
                    <Area 
                      type="monotone" 
                      dataKey="net_profit" 
                      stroke={CHART_COLORS.netProfit} 
                      strokeWidth={2} 
                      fillOpacity={1} 
                      fill="url(#colorNetProfit)" 
                      name="Bénéfice net" 
                    />
                    <Line 
                      type="monotone" 
                      dataKey="revenue" 
                      stroke={CHART_COLORS.revenue} 
                      strokeWidth={1} 
                      strokeDasharray="5 5"
                      dot={false}
                      name="Revenus" 
                    />
                  </ComposedChart>
                </ResponsiveContainer>
              </ChartContainer>
            </Section>
          )}

          {/* Répartition Revenus par type */}
          <Section
            icon={<PieChartIcon size={24} />}
            title="Répartition des revenus"
            description="Distribution des revenus encaissés par type de vente"
          >
            <div className="stats-chart-grid">
              <ChartContainer className="stats-chart-half">
                <ResponsiveContainer width="100%" height={300}>
                  <PieChart>
                    <Pie
                      data={[
                        { name: 'Immédiates', value: revenue.immediate, color: CHART_COLORS.immediate },
                        { name: 'Crédits', value: revenue.credit, color: CHART_COLORS.credit },
                        { name: 'Réservations', value: revenue.reservation, color: CHART_COLORS.reservation }
                      ]}
                      cx="50%"
                      cy="50%"
                      labelLine={false}
                      label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                      outerRadius={100}
                      dataKey="value"
                    >
                      {[revenue.immediate, revenue.credit, revenue.reservation].map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={Object.values(CHART_COLORS)[index]} />
                      ))}
                    </Pie>
                    <Tooltip content={<CustomTooltip />} />
                  </PieChart>
                </ResponsiveContainer>
              </ChartContainer>

              <div className="financial-legend">
                {[
                  { name: 'Ventes immédiates', value: revenue.immediate, color: CHART_COLORS.immediate },
                  { name: 'Paiements crédits', value: revenue.credit, color: CHART_COLORS.credit },
                  { name: 'Réservations', value: revenue.reservation, color: CHART_COLORS.reservation }
                ].map((item, index) => (
                  <div key={index} className="financial-legend-item">
                    <div className="financial-legend-color" style={{ backgroundColor: item.color }} />
                    <div className="financial-legend-info">
                      <span className="financial-legend-name">{item.name}</span>
                      <span className="financial-legend-value">{formatCurrency(item.value)}</span>
                      <span className="financial-legend-percent">
                        {revenue.total > 0 ? ((item.value / revenue.total) * 100).toFixed(1) : 0}% du total
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </Section>

          {/* Bénéfices par type de vente */}
          {profits && (
            <Section
              icon={<TrendingUp size={24} />}
              title="Rentabilité par type de vente"
              description="Analyse des marges et bénéfices pour chaque canal de vente"
            >
              <div className="profit-cards-grid">
                {Object.entries(profits.by_type).map(([type, data]) => (
                  <div key={type} className="profit-type-card">
                    <div className="profit-type-header">
                      <span className="profit-type-name">
                        {type === 'immediate' ? 'Ventes immédiates' : 
                         type === 'credit' ? 'Crédits' : 'Réservations'}
                      </span>
                      <span className={`profit-type-margin ${data.margin > 30 ? 'positive' : data.margin > 15 ? 'neutral' : 'negative'}`}>
                        {data.margin}% marge
                      </span>
                    </div>
                    <div className="profit-type-values">
                      <div className="profit-value-item">
                        <span className="profit-value-label">Revenus</span>
                        <span className="profit-value">{formatCurrency(data.revenue)}</span>
                      </div>
                      <Minus size={16} className="profit-separator" />
                      <div className="profit-value-item">
                        <span className="profit-value-label">Coûts</span>
                        <span className="profit-value cost">{formatCurrency(data.costs)}</span>
                      </div>
                      <span className="profit-equals">=</span>
                      <div className="profit-value-item">
                        <span className="profit-value-label">Bénéfice</span>
                        <span className="profit-value profit">{formatCurrency(data.profit)}</span>
                      </div>
                    </div>
                    <div className="profit-progress-bar">
                      <div 
                        className="profit-progress-fill" 
                        style={{ width: `${Math.min(data.margin, 100)}%` }}
                      />
                    </div>
                  </div>
                ))}
              </div>
            </Section>
          )}

          {/* Top produits rentables */}
          {profits?.by_product?.length > 0 && (
            <Section
              icon={<Package size={24} />}
              title="Top 20 produits les plus rentables"
              description="Classement par bénéfice généré (revenus - coûts)"
            >
              <ChartContainer>
                <ResponsiveContainer width="100%" height={400}>
                  <BarChart data={profits.by_product.slice(0, 10)}>
                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.05)" />
                    <XAxis 
                      dataKey="product_name" 
                      stroke="var(--text-secondary)" 
                      style={{ fontSize: '12px' }} 
                      angle={-45} 
                      textAnchor="end" 
                      height={120} 
                    />
                    <YAxis stroke="var(--text-secondary)" style={{ fontSize: '12px' }} tickFormatter={formatCurrency} />
                    <Tooltip content={<CustomTooltip />} />
                    <Legend />
                    <Bar dataKey="revenue" fill={CHART_COLORS.revenue} radius={[8, 8, 0, 0]} name="Revenus" />
                    <Bar dataKey="costs" fill={CHART_COLORS.costs} radius={[8, 8, 0, 0]} name="Coûts" />
                    <Bar dataKey="profit" fill={CHART_COLORS.grossProfit} radius={[8, 8, 0, 0]} name="Bénéfice" />
                  </BarChart>
                </ResponsiveContainer>
              </ChartContainer>
            </Section>
          )}

          {/* Dépenses vs Prévisionnel */}
          {expenses && (
            <Section
              icon={<CreditCard size={24} />}
              title="Dépenses opérationnelles"
              description="Comparaison entre dépenses réelles et budget prévisionnel"
            >
              <div className="expenses-comparison">
                <div className="expenses-card">
                  <div className="expenses-header">
                    <CheckCircle size={20} className="expenses-icon actual" />
                    <span>Dépenses réelles</span>
                  </div>
                  <div className="expenses-value">{formatCurrency(expenses.actual.total)}</div>
                  <div className="expenses-categories">
                    {expenses.actual.by_category.slice(0, 5).map((cat, idx) => (
                      <div key={idx} className="expense-category-item">
                        <span className="expense-category-name">{cat.category_name}</span>
                        <span className="expense-category-amount">{formatCurrency(cat.amount)}</span>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="expenses-card">
                  <div className="expenses-header">
                    <AlertTriangle size={20} className="expenses-icon planned" />
                    <span>Budget prévisionnel</span>
                  </div>
                  <div className="expenses-value">{formatCurrency(expenses.planned.total)}</div>
                  <div className="expenses-variance">
                    <span className={expenses.comparison.difference > 0 ? 'negative' : 'positive'}>
                      {expenses.comparison.difference > 0 ? '+' : ''}
                      {formatCurrency(Math.abs(expenses.comparison.difference))}
                    </span>
                    <span className="variance-label">
                      ({expenses.comparison.variance_percent > 0 ? '+' : ''}
                      {expenses.comparison.variance_percent}% vs prévu)
                    </span>
                  </div>
                </div>
              </div>
            </Section>
          )}
           {/* Répartition des dépenses totales */}
            {dashboard && (
            <Section
                icon={<Package size={24} />}
                title="Répartition des dépenses"
                description="Détail des sorties d'argent par catégorie"
            >
                <div className="expenses-breakdown-grid">
                {/* Coûts d'approvisionnement */}
                <div className="expense-breakdown-card">
                    <div className="expense-breakdown-header">
                    <Package size={20} className="expense-icon procurement" />
                    <span>Approvisionnements</span>
                    </div>
                    <div className="expense-breakdown-value">
                    {formatCurrency(dashboard.procurement.total)}
                    </div>
                    <div className="expense-breakdown-meta">
                    {dashboard.procurement.transaction_count} transactions
                    </div>
                    <div className="expense-breakdown-percent">
                    {dashboard.total_expenses_breakdown.total > 0
                        ? ((dashboard.procurement.total / dashboard.total_expenses_breakdown.total) * 100).toFixed(1)
                        : 0}% du total
                    </div>
                </div>

                {/* Dépenses opérationnelles */}
                <div className="expense-breakdown-card">
                    <div className="expense-breakdown-header">
                    <CreditCard size={20} className="expense-icon operational" />
                    <span>Dépenses opérationnelles</span>
                    </div>
                    <div className="expense-breakdown-value">
                    {formatCurrency(dashboard.expenses.actual.total)}
                    </div>
                    <div className="expense-breakdown-meta">
                    Loyer, salaires, électricité, etc.
                    </div>
                    <div className="expense-breakdown-percent">
                    {dashboard.total_expenses_breakdown.total > 0
                        ? ((dashboard.expenses.actual.total / dashboard.total_expenses_breakdown.total) * 100).toFixed(1)
                        : 0}% du total
                    </div>
                </div>

                {/* Pertes */}
                <div className="expense-breakdown-card">
                    <div className="expense-breakdown-header">
                    <AlertTriangle size={20} className="expense-icon losses" />
                    <span>Pertes</span>
                    </div>
                    <div className="expense-breakdown-value">
                    {formatCurrency(dashboard.losses.summary.total_losses)}
                    </div>
                    <div className="expense-breakdown-meta">
                    Stock + crédits non recouvrés
                    </div>
                    <div className="expense-breakdown-percent">
                    {dashboard.total_expenses_breakdown.total > 0
                        ? ((dashboard.losses.summary.total_losses / dashboard.total_expenses_breakdown.total) * 100).toFixed(1)
                        : 0}% du total
                    </div>
                </div>

                {/* Total */}
                <div className="expense-breakdown-card total">
                    <div className="expense-breakdown-header">
                    <DollarSign size={20} className="expense-icon total" />
                    <span>Total dépenses</span>
                    </div>
                    <div className="expense-breakdown-value">
                    {formatCurrency(dashboard.total_expenses_breakdown.total)}
                    </div>
                    <div className="expense-breakdown-meta">
                    Toutes catégories confondues
                    </div>
                </div>
                </div>
            </Section>
            )}
          {/* Pertes */}
          {losses && (
            <Section
              icon={<AlertTriangle size={24} />}
              title="Pertes et manques à gagner"
              description="Stock perdu, crédits non recouvrés et ventes annulées"
            >
              <div className="losses-grid">
                <div className="loss-summary-card">
                  <div className="loss-summary-header">
                    <AlertTriangle size={24} className="loss-icon" />
                    <span>Pertes totales</span>
                  </div>
                  <div className="loss-summary-value">{formatCurrency(lossesSummary.summary.total_losses)}</div>
                  <div className="loss-breakdown">
                    <div className="loss-item">
                      <span className="loss-label">Pertes de stock</span>
                      <span className="loss-amount">{formatCurrency(lossesSummary.summary.total_value)}</span>
                    </div>
                    <div className="loss-item">
                      <span className="loss-label">Crédits non recouvrés</span>
                      <span className="loss-amount">{formatCurrency(lossesSummary.summary.defaulted_credits)}</span>
                    </div>
                  </div>
                </div>

                {lossesSummary.by_type?.length > 0 && (
                  <div className="loss-types-card">
                    <h4>Pertes par type</h4>
                    <div className="loss-types-list">
                      {lossesSummary.by_type.map((loss, idx) => (
                        <div key={idx} className="loss-type-item">
                          <span className="loss-type-label">
                            {getLossTypeLabel(loss.type)}
                          </span>
                          <div className="loss-type-details">
                            <span className="loss-type-qty">{loss.quantity} unités</span>
                            <span className="loss-type-value">{formatCurrency(loss.value)}</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </Section>
          )}
        </>
      )}
    </div>
  );
};

const getLossTypeLabel = (type) => {
  const labels = {
    breakage: '💥 Casse',
    theft: '🚨 Vol',
    expiry: '📅 Péremption',
    damage: '⚠️ Dommage',
    inventory_shortage: '📊 Écart inventaire',
    other: '📝 Autre'
  };
  return labels[type] || type;
};

export default FinancialStatistics;