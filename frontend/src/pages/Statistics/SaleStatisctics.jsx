// src/pages/SalesStatistics.jsx
import React, { useState, useEffect } from 'react';
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
import ProductsTable from '../../components/statistics/ProductsTable';
import DiscountCard from '../../components/statistics/DiscountCard';
import SummaryCard from '../../components/statistics/SummaryCard';
import CustomTooltip from '../../components/statistics/CustomTooltip';
import DateRangeFilter from '../../components/statistics/DateRangeFilter';
import { formatCurrency, formatNumber } from '../../utils/formatters';
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
    const [topProducts, setTopProducts] = useState(null);
    const [topProductsPagination, setTopProductsPagination] = useState(null);
    const [categories, setCategories] = useState(null);
    const [sellers, setSellers] = useState(null);
    const [paymentMethods, setPaymentMethods] = useState(null);
    const [discounts, setDiscounts] = useState(null);
    const [credits, setCredits] = useState(null);
    const [reservations, setReservations] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    
    // État pour stocker les paramètres actuels
    const [filterParams, setFilterParams] = useState({ 
      period: 'month',
      start_date: '',
      end_date: ''
    });
    
    // Calculer les dates par défaut
    useEffect(() => {
      const calculateDefaultDates = () => {
        const today = new Date();
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - 1);
        
        setFilterParams(prev => ({
          ...prev,
          start_date: startDate.toISOString().split('T')[0],
          end_date: today.toISOString().split('T')[0]
        }));
      };
      
      calculateDefaultDates();
    }, []);
  
    useEffect(() => {
      if (filterParams.start_date && filterParams.end_date) {
        loadAllData();
      }
    }, [filterParams]);
  
    const loadAllData = async () => {
      setLoading(true);
      setError(null);
      try {
        // Déterminer le grouping pour timeline
        const start = new Date(filterParams.start_date);
        const end = new Date(filterParams.end_date);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        let grouping = 'day';
        if (diffDays > 365) {
          grouping = 'month';
        } else if (diffDays > 90) {
          grouping = 'week';
        }
        
        const apiParams = {
          start_date: filterParams.start_date,
          end_date: filterParams.end_date
        };
        
        const [
          overviewData, timelineData, productsData, categoriesData,
          sellersData, paymentData, discountsData, creditsData, reservationsData
        ] = await Promise.all([
          statisticsService.overviewSale(apiParams),
          statisticsService.timeline(
            filterParams.start_date,
            filterParams.end_date,
            grouping
          ),
          statisticsService.topProducts(apiParams),
          statisticsService.byCategory(apiParams),
          statisticsService.bySeller(apiParams),
          statisticsService.byPaymentMethod(apiParams),
          statisticsService.discounts(apiParams),
          statisticsService.credits(apiParams),
          statisticsService.reservations(apiParams)
        ]);
  
        setOverview(overviewData);
        setTimeline(timelineData);
        setTopProducts(productsData.data);
        setTopProductsPagination(productsData.pagination);
        setCategories(categoriesData);
        setSellers(sellersData);
        setPaymentMethods(paymentData);
        setDiscounts(discountsData);
        setCredits(creditsData);
        setReservations(reservationsData);
      } catch (err) {
        console.error('Error loading statistics:', err);
        setError('Impossible de charger les statistiques');
      } finally {
        setLoading(false);
      }
    };
  
    const handleFilterChange = (newParams) => {
        setFilterParams(prev => ({
          ...prev,
          ...newParams
        }));
      };

      
  const handleProductsPageChange = async (page) => {
    try {
      const productsData = await statisticsService.topProducts({ 
        ...filterParams, 
        per_page: 10,
        page 
      });
      setTopProducts(productsData.data);
      setTopProductsPagination(productsData.pagination);
    } catch (err) {
      console.error('Error loading products page:', err);
    }
  };

  const getPeriodLabel = () => {
    if (!filterParams.start_date || !filterParams.end_date) return '';
    
    const start = new Date(filterParams.start_date);
    const end = new Date(filterParams.end_date);
    
    // Formatter les dates
    const options = { 
      day: '2-digit', 
      month: 'short', 
      year: 'numeric' 
    };
    
    return `Du ${start.toLocaleDateString('fr-FR', options)} au ${end.toLocaleDateString('fr-FR', options)}`;
  };

  if (loading) {
    return (
      <div className="stats-loading">
        <Loader2 className="stats-loading-spinner" size={48} />
        <p>Chargement des statistiques...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="stats-error">
        <AlertCircle size={48} />
        <p>{error}</p>
        <button onClick={loadAllData} className="stats-retry-btn">
          Réessayer
        </button>
      </div>
    );
  }

  if (!overview) return null;

  const { kpis, by_type, period } = overview;
  const collectionRate = ((kpis.total_paid / kpis.total_revenue) * 100).toFixed(1);

  const salesTypeData = [
    { name: 'Immédiate', value: by_type.immediate.revenue, count: by_type.immediate.count, color: CHART_COLORS.primary },
    { name: 'Crédit', value: by_type.credit.revenue, count: by_type.credit.count, color: CHART_COLORS.warning },
    { name: 'Réservation', value: by_type.reservation.revenue, count: by_type.reservation.count, color: CHART_COLORS.info }
  ];

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
      {/* Date Range Filter */}
      <DateRangeFilter 
        onFilterChange={handleFilterChange}
        initialPeriod={filterParams.period}
        initialDates={{
          start_date: filterParams.start_date,
          end_date: filterParams.end_date
        }}
      />

      {/* Header */}
      <div className="stats-header">
        <div>
          <h1>Statistiques de ventes</h1>
          <p className="stats-period">
            <Calendar size={16} />
            {getPeriodLabel()}
          </p>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="stats-grid">
        <KPICard
          icon={<DollarSign size={24} />}
          label="Chiffre d'affaires"
          value={formatCurrency(kpis.total_revenue)}
          meta={`${formatNumber(kpis.total_sales_count)} ventes`}
          color="primary"
        />
        <KPICard
          icon={<CheckCircle size={24} />}
          label="Montant encaissé"
          value={formatCurrency(kpis.total_paid)}
          meta={`${collectionRate}% du CA`}
          progress={collectionRate}
          color="success"
        />
        <KPICard
          icon={<Clock size={24} />}
          label="En attente"
          value={formatCurrency(kpis.total_pending)}
          meta={`${((kpis.total_pending / kpis.total_revenue) * 100).toFixed(1)}% du CA`}
          color="warning"
        />
        <KPICard
          icon={<ShoppingCart size={24} />}
          label="Panier moyen"
          value={formatCurrency(kpis.average_sale_value)}
          meta="Par transaction"
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
      <Section
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
      </Section>

      {/* Top Products Table */}
      {topProducts?.length > 0 && (
        <Section
          icon={<Package size={24} />}
          title="Top 10 des produits"
          description="Classement des produits par chiffre d'affaires généré"
        >
          <ProductsTable 
            products={topProducts} 
            pagination={topProductsPagination}
            onPageChange={handleProductsPageChange}
            navigate={navigate} 
          />
        </Section>
      )}

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
    </div>
  );
};

export default SalesStatistics;