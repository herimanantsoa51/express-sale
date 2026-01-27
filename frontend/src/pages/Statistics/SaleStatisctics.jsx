// src/pages/SalesStatistics.jsx
import React, { useState, useCallback, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  LineChart, Line, BarChart, Bar, PieChart, Pie, Cell,
  AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip,
  ResponsiveContainer, Legend
} from 'recharts';
import {
  TrendingUp, DollarSign, ShoppingCart, CreditCard, Calendar,
  Clock, CheckCircle, AlertCircle, Loader2, Package, Users,
  Tag, Percent, Activity, PieChart as PieChartIcon
} from 'lucide-react';
import statisticsService from '../../services/statisticsService';
import KPICard from '../../components/statistics/KPICard';
import Section from '../../components/statistics/Section';
import ChartContainer from '../../components/statistics/ChartContainer';
import DiscountCard from '../../components/statistics/DiscountCard';
import SummaryCard from '../../components/statistics/SummaryCard';
import CustomTooltip from '../../components/statistics/CustomTooltip';
import DateRangeFilter from '../../components/statistics/DateRangeFilter';
import { formatCurrency, formatNumber } from '../../utils/formatters';
import NavigationButtons from '../../components/statistics/NavigationButtons';
import '../../styles/SalesStatistics.css';

const CHART_COLORS = {
  primary: '#0071e3',
  success: '#30d158',
  warning: '#ff9f0a',
  danger: '#ff3b30',
  info: '#0a84ff',
  purple: '#bf5af2',
  pink: '#ff2d55',
  teal: '#5ac8fa'
};

const SalesStatistics = () => {
  const navigate = useNavigate();
  const [overview, setOverview] = useState(null);
  const [timeline, setTimeline] = useState(null);
  const [categories, setCategories] = useState(null);
  const [sellers, setSellers] = useState(null);
  const [paymentMethods, setPaymentMethods] = useState(null);
  const [discounts, setDiscounts] = useState(null);
  const [credits, setCredits] = useState(null);
  const [reservations, setReservations] = useState(null);
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
      
      const [
        overviewData, timelineData, categoriesData,
        sellersData, paymentData, discountsData, creditsData, reservationsData
      ] = await Promise.all([
        statisticsService.overviewSale(params),
        statisticsService.timeline(params.start_date, params.end_date, grouping),
        statisticsService.byCategory(params),
        statisticsService.bySeller(params),
        statisticsService.byPaymentMethod(params),
        statisticsService.discounts(params),
        statisticsService.credits(params),
        statisticsService.reservations(params)
      ]);

      // Vérifier si cette requête est toujours d'actualité
      if (currentRequestRef.current !== requestId) {
        return; // Une nouvelle requête a été lancée, ignorer celle-ci
      }

      setOverview(overviewData);
      setTimeline(timelineData);
      setCategories(categoriesData);
      setSellers(sellersData);
      setPaymentMethods(paymentData);
      setDiscounts(discountsData);
      setCredits(creditsData);
      setReservations(reservationsData);
    } catch (err) {
      if (currentRequestRef.current !== requestId) {
        return;
      }
      console.error('Error loading statistics:', err);
      setError('Impossible de charger les statistiques');
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

  const kpis = overview?.kpis;
  const by_type = overview?.by_type;
  const collectionRate = kpis ? ((kpis.total_paid / kpis.total_revenue) * 100).toFixed(1) : 0;

  const salesTypeData = by_type ? [
    { name: 'Immédiate', value: by_type.immediate.revenue, count: by_type.immediate.count, color: CHART_COLORS.primary },
    { name: 'Crédit', value: by_type.credit.revenue, count: by_type.credit.count, color: CHART_COLORS.warning },
    { name: 'Réservation', value: by_type.reservation.revenue, count: by_type.reservation.count, color: CHART_COLORS.info }
  ] : [];

  const categoryChartData = categories?.categories.slice(0, 8).map(cat => ({
    name: cat.name,
    revenue: cat.total_revenue,
    quantity: cat.total_quantity
  })) || [];

  const paymentMethodData = paymentMethods?.payment_methods.map(method => ({
    name: method.method,
    value: method.total_amount,
    percentage: method.percentage
  })) || [];

  return (
    <div className="stats-container">
      {loading && (
      <div className="stats-loading-overlay">
        <div className="stats-loading-spinner-container">
          <Loader2 className="stats-loading-spinner" size={48} />
          <p>Chargement des statistiques...</p>
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

      {!overview ? (
        <div className="stats-empty">
          <Calendar size={48} />
          <p>Sélectionnez une période pour voir les statistiques</p>
        </div>
      ) : (
        <>
          {/* KPI Cards */}
          <div className="stats-grid">
            <KPICard
              icon={<DollarSign size={24} />}
              label="CA facturé"
              value={formatCurrency(kpis.total_invoiced)}
              meta={`${kpis.total_sales_count} ventes`}
              color="info"
            />
            
            <KPICard
              icon={<TrendingUp size={24} />}
              label="CA encaissé"
              value={formatCurrency(kpis.total_collected)}
              color="success"
            />
            
            <KPICard
              icon={<Activity size={24} />}
              label="Reste à encaisser"
              value={formatCurrency(kpis.total_pending)}
              meta={`${((kpis.total_pending/kpis.total_invoiced)*100).toFixed(1)}% du CA`}
              progress={((kpis.total_pending/kpis.total_invoiced)*100).toFixed(1)}
              color="warning"
            />
            
            <KPICard
              icon={<Package size={24} />}
              label="Panier moyen"
              value={formatCurrency(kpis.average_sale_value)}
              meta={`Remises: ${formatCurrency(kpis.total_discount_given)}`}
              color="primary"
            />
          </div>

          {/* Timeline Chart */}
          {timeline?.data?.length > 0 && (
            <Section
              icon={<Activity size={24} />}
              title="Évolution du chiffre d'affaires"
              description="Suivi quotidien du CA total et des montants encaissés"
            >
              <ChartContainer>
                <ResponsiveContainer width="100%" height={350}>
                  <AreaChart data={timeline.data}>
                    <defs>
                      <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor={CHART_COLORS.primary} stopOpacity={0.3}/>
                        <stop offset="95%" stopColor={CHART_COLORS.primary} stopOpacity={0}/>
                      </linearGradient>
                      <linearGradient id="colorPaid" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor={CHART_COLORS.success} stopOpacity={0.3}/>
                        <stop offset="95%" stopColor={CHART_COLORS.success} stopOpacity={0}/>
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.05)" />
                    <XAxis dataKey="period" stroke="var(--text-secondary)" style={{ fontSize: '12px' }} />
                    <YAxis stroke="var(--text-secondary)" style={{ fontSize: '12px' }} tickFormatter={formatCurrency} />
                    <Tooltip content={<CustomTooltip />} />
                    <Legend />
                    <Area type="monotone" dataKey="revenue" stroke={CHART_COLORS.primary} strokeWidth={2} fillOpacity={1} fill="url(#colorRevenue)" name="CA Total" />
                    <Area type="monotone" dataKey="total_paid" stroke={CHART_COLORS.success} strokeWidth={2} fillOpacity={1} fill="url(#colorPaid)" name="Encaissé" />
                  </AreaChart>
                </ResponsiveContainer>
              </ChartContainer>
            </Section>
          )}

          {/* Sales Type Distribution */}
          {/* <Section
            icon={<PieChartIcon size={24} />}
            title="Répartition par type de vente"
            description="Distribution du CA entre ventes immédiates, crédits et réservations"
          >
            <div className="stats-chart-grid">
              <ChartContainer className="stats-chart-half">
                <ResponsiveContainer width="100%" height={300}>
                  <PieChart>
                    <Pie
                      data={salesTypeData}
                      cx="50%"
                      cy="50%"
                      labelLine={false}
                      label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                      outerRadius={100}
                      dataKey="value"
                    >
                      {salesTypeData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip content={<CustomTooltip />} />
                  </PieChart>
                </ResponsiveContainer>
              </ChartContainer>
              <div className="stats-type-legend">
                {salesTypeData.map((type, index) => (
                  <div key={index} className="stats-type-legend-item">
                    <div className="stats-type-legend-color" style={{ backgroundColor: type.color }} />
                    <div className="stats-type-legend-info">
                      <span className="stats-type-legend-name">{type.name}</span>
                      <span className="stats-type-legend-value">{formatCurrency(type.value)}</span>
                      <span className="stats-type-legend-count">{formatNumber(type.count)} ventes</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </Section> */}

          {/* Categories & Sellers */}
          <div className="stats-double-section">
            {categories && categoryChartData.length > 0 && (
              <Section
                icon={<Tag size={24} />}
                title="Ventes par catégorie"
                description="Performance des catégories de produits"
              >
                <ChartContainer>
                  <ResponsiveContainer width="100%" height={350}>
                    <BarChart data={categoryChartData}>
                      <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.05)" />
                      <XAxis dataKey="name" stroke="var(--text-secondary)" style={{ fontSize: '12px' }} angle={-45} textAnchor="end" height={100} />
                      <YAxis stroke="var(--text-secondary)" style={{ fontSize: '12px' }} tickFormatter={formatCurrency} />
                      <Tooltip content={<CustomTooltip />} />
                      <Bar dataKey="revenue" fill={CHART_COLORS.info} radius={[8, 8, 0, 0]} name="CA" />
                    </BarChart>
                  </ResponsiveContainer>
                </ChartContainer>
              </Section>
            )}

            {sellers?.sellers?.length > 0 && (
              <Section
                icon={<Users size={24} />}
                title="Performance des vendeurs"
                description="Classement par chiffre d'affaires généré"
              >
                <ChartContainer>
                  <ResponsiveContainer width="100%" height={350}>
                    <BarChart data={sellers.sellers}>
                      <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.05)" />
                      <XAxis dataKey="seller_name" stroke="var(--text-secondary)" style={{ fontSize: '12px' }} angle={-45} textAnchor="end" height={100} />
                      <YAxis stroke="var(--text-secondary)" style={{ fontSize: '12px' }} tickFormatter={formatCurrency} />
                      <Tooltip content={<CustomTooltip />} />
                      <Legend />
                      <Bar dataKey="total_revenue" fill={CHART_COLORS.success} radius={[8, 8, 0, 0]} name="CA Total" />
                      <Bar dataKey="average_sale" fill={CHART_COLORS.purple} radius={[8, 8, 0, 0]} name="Panier moyen" />
                    </BarChart>
                  </ResponsiveContainer>
                </ChartContainer>
              </Section>
            )}
          </div>

          {/* Payment Methods */}
          {paymentMethods && paymentMethodData.length > 0 && (
            <Section
              icon={<CreditCard size={24} />}
              title="Méthodes de paiement"
              description="Répartition des paiements par méthode utilisée"
            >
              <ChartContainer>
                <ResponsiveContainer width="100%" height={300}>
                  <PieChart>
                    <Pie
                      data={paymentMethodData}
                      cx="50%"
                      cy="50%"
                      labelLine={false}
                      label={({ name, percentage }) => `${name}: ${percentage}%`}
                      outerRadius={110}
                      dataKey="value"
                    >
                      {paymentMethodData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={Object.values(CHART_COLORS)[index % Object.values(CHART_COLORS).length]} />
                      ))}
                    </Pie>
                    <Tooltip content={<CustomTooltip />} />
                  </PieChart>
                </ResponsiveContainer>
              </ChartContainer>
            </Section>
          )}

          {/* Discounts */}
          {discounts && (
            <Section
              icon={<Percent size={24} />}
              title="Analyse des remises"
              description="Impact des remises sur le chiffre d'affaires"
            >
              <div className="stats-discount-grid">
                <DiscountCard
                  label="Total des remises"
                  value={formatCurrency(discounts.summary.total_discount_given)}
                  meta={`Impact: ${discounts.summary.discount_impact}% du CA`}
                />
                <DiscountCard
                  label="Ventes avec remise"
                  value={formatNumber(discounts.summary.sales_with_discount)}
                  meta={`Taux: ${discounts.summary.discount_rate}%`}
                />
                <DiscountCard
                  label="Remise moyenne"
                  value={formatCurrency(discounts.summary.average_discount)}
                  meta={`Max: ${formatCurrency(discounts.summary.max_discount)}`}
                />
              </div>
            </Section>
          )}

          {/* Credits & Reservations */}
          <div className="stats-double-section">
            {credits && (
              <div className="stats-section stats-section-half">
                <Section
                  icon={<TrendingUp size={24} />}
                  title="Crédits"
                  description="Suivi des ventes à crédit et recouvrement"
                >
                  <div className="stats-summary-cards">
                    <SummaryCard value={formatNumber(credits.summary.total_credits)} label="Total crédits" />
                    <SummaryCard value={formatCurrency(credits.summary.total_credit_amount)} label="Montant total" />
                    <SummaryCard value={`${credits.summary.collection_rate}%`} label="Taux de recouvrement" variant="success" />
                    <SummaryCard value={formatNumber(credits.summary.overdue_count)} label="En retard" variant="warning" />
                  </div>
                </Section>
              </div>
            )}

            {reservations && (
              <div className="stats-section stats-section-half">
                <Section
                  icon={<Package size={24} />}
                  title="Réservations"
                  description="Suivi des réservations et taux de finalisation"
                >
                  <div className="stats-summary-cards">
                    <SummaryCard value={formatNumber(reservations.summary.total_reservations)} label="Total réservations" />
                    <SummaryCard value={formatCurrency(reservations.summary.total_amount)} label="Montant total" />
                    <SummaryCard value={`${reservations.summary.completion_rate}%`} label="Taux de finalisation" variant="success" />
                    <SummaryCard value={formatNumber(reservations.summary.active_count)} label="En cours" variant="warning" />
                  </div>
                </Section>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
};

export default SalesStatistics;