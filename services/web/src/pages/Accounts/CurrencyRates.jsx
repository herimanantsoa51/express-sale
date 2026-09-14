import React, { useState, useEffect, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  TrendingUp, Plus, RefreshCw, Wallet, Landmark, Smartphone,
  Banknote, DollarSign, Euro, CircleDollarSign, Check, X,
  Calendar, ChevronLeft, ChevronRight, ChevronsLeft,
  ChevronsRight, Edit2, Save, AlertCircle
} from 'lucide-react';
import accountService from '../../services/accountService';
import currencyService from '../../services/currencyService';
import '../../styles/CurrencyRates.css';

// === CURRENCY ICON ===
const CurrencyIcon = ({ currency, size = 20 }) => {
  const icons = {
    EUR: Euro,
    USD: DollarSign,
    CNY: CircleDollarSign,
    THB: Banknote
  };
  const Icon = icons[currency] || DollarSign;
  return <Icon size={size} />;
};

// === CONVERTED BALANCE CARD ===
const ConvertedBalanceCard = ({
    currency,
    symbol,
    amount = 0,
    rate = 1,
    color
  }) => {
    const numericRate = parseFloat(rate);
    const convertedAmount = amount / numericRate;
  
    return (
      <motion.div className="currency-converted-balance-card">
        <div className="currency-balance-card-header">
          <div
            className="currency-balance-card-icon"
            style={{ backgroundColor: color + '20', color }}
          >
            <CurrencyIcon currency={currency} size={24} />
          </div>
  
          <div className="currency-balance-card-info">
            <p className="currency-balance-card-currency">{currency}</p>
            <p className="currency-balance-card-rate">
              1 {currency} = {numericRate.toLocaleString('fr-FR')} Ar
            </p>
          </div>
        </div>
  
        <div className="currency-balance-card-amount">
          {convertedAmount.toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          })}{' '}
          <span className="currency-symbol">{symbol}</span>
        </div>
      </motion.div>
    );
  };

// === CURRENT RATE DISPLAY ===
const CurrentRateDisplay = ({ rate, totalBalance, byType }) => {
  if (!rate || !rate.rates_in_ariary) return null;

  const currencies = [
    { code: 'EUR', symbol: '€', rate: rate.rates_in_ariary.euro.value, color: '#0071e3' },
    { code: 'USD', symbol: '$', rate: rate.rates_in_ariary.dollar.value, color: '#30d158' },
    { code: 'CNY', symbol: '¥', rate: rate.rates_in_ariary.yuan.value, color: '#ff9f0a' },
    { code: 'THB', symbol: '฿', rate: rate.rates_in_ariary.baht.value, color: '#ff3b30' }
  ];

  const getTypeIcon = (code) => {
    const icons = { CASH: Banknote, MOBILE_MONEY: Smartphone, BANK: Landmark };
    return icons[code] || Wallet;
  };

  return (
    <div className="currency-current-rate-section">
      <div className="currency-section-header">
        <TrendingUp size={20} />
        <h2>Taux de Change Actuel</h2>
        <span className="currency-active-badge">
          <Check size={14} />
          Actif
        </span>
      </div>

      <div className="currency-current-rate-info">
        <div className="currency-rate-meta">
          <Calendar size={16} />
          <span>Depuis le {new Date(rate.effective_date).toLocaleDateString('fr-FR')}</span>
        </div>
      </div>

      {/* Total Balance */}
      <div className="currency-total-balance-section">
        <h3>Solde Total</h3>
        <div className="currency-balance-grid">
          <div className="currency-balance-item currency-ariary-balance">
            <Wallet size={20} />
            <div>
              <p className="currency-balance-label">Ariary (MGA)</p>
              <p className="currency-balance-value">{totalBalance.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} Ar</p>
            </div>
          </div>
          {currencies.map((curr) => (
            <ConvertedBalanceCard 
              key={curr.code}
              currency={curr.code}
              symbol={curr.symbol}
              amount={totalBalance}
              rate={curr.rate}
              color={curr.color}
            />
          ))}
        </div>
      </div>

      {/* By Type */}
      <div className="currency-by-type-section">
        <h3>Par Type de Compte</h3>
        {byType.map((type) => {
          const Icon = getTypeIcon(type.type_code);
          return (
            <div key={type.type_code} className="currency-type-conversion-group">
              <div className="currency-type-header">
                <Icon size={18} />
                <span>{type.type_name}</span>
                <span className="currency-type-total">{type.total_balance.toLocaleString('fr-FR')} Ar</span>
              </div>
              <div className="currency-type-conversions">
                {currencies.map((curr) => {
                  const numericRate = parseFloat(curr.rate);
                  const convertedValue = type.total_balance / numericRate;
                  
                  return (
                    <div key={curr.code} className="currency-type-conversion-item">
                      <CurrencyIcon currency={curr.code} size={16} />
                      <span className="currency-conversion-value">
                        {convertedValue.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} {curr.symbol}
                      </span>
                    </div>
                  );
                })}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

// === NEW RATE FORM ===
const NewRateForm = ({ onSuccess }) => {
  const [formData, setFormData] = useState({
    euro_rate: '',
    yuan_rate: '',
    dollar_rate: '',
    baht_rate: '',
    effective_date: new Date().toISOString().split('T')[0],
    notes: '',
    is_active: true,
    dirham_rate: 0.1
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      await currencyService.storeCurrency(formData);
      setFormData({
        euro_rate: '',
        yuan_rate: '',
        dollar_rate: '',
        baht_rate: '',
        effective_date: new Date().toISOString().split('T')[0],
        notes: '',
        is_active: true,
        dirham_rate: 0.1
      });
      onSuccess();
    } catch (err) {
      setError(err.response?.data?.message || 'Erreur lors de la création du taux');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="currency-new-rate-section">
      <div className="currency-section-header">
        <Plus size={20} />
        <h2>Nouveau Taux de Change</h2>
      </div>

      <form onSubmit={handleSubmit} className="currency-rate-form">
        <div className="currency-form-row">
          <div className="currency-form-group">
            <label>
              <Euro size={16} />
              Taux Euro (EUR)
            </label>
            <input
              type="number"
              step="0.0001"
              min="0.0001"
              value={formData.euro_rate}
              onChange={(e) => setFormData({ ...formData, euro_rate: e.target.value })}
              placeholder="5100.0000"
              required
            />
          </div>

          <div className="currency-form-group">
            <label>
              <DollarSign size={16} />
              Taux Dollar (USD)
            </label>
            <input
              type="number"
              step="0.0001"
              min="0.0001"
              value={formData.dollar_rate}
              onChange={(e) => setFormData({ ...formData, dollar_rate: e.target.value })}
              placeholder="4650.0000"
              required
            />
          </div>

          <div className="currency-form-group">
            <label>
              <CircleDollarSign size={16} />
              Taux Yuan (CNY)
            </label>
            <input
              type="number"
              step="0.0001"
              min="0.0001"
              value={formData.yuan_rate}
              onChange={(e) => setFormData({ ...formData, yuan_rate: e.target.value })}
              placeholder="635.0000"
              required
            />
          </div>

          <div className="currency-form-group">
            <label>
              <Banknote size={16} />
              Taux Baht (THB)
            </label>
            <input
              type="number"
              step="0.0001"
              min="0.0001"
              value={formData.baht_rate}
              onChange={(e) => setFormData({ ...formData, baht_rate: e.target.value })}
              placeholder="135.0000"
              required
            />
          </div>
        </div>

        <div className="currency-form-row">
          <div className="currency-form-group">
            <label>
              <Calendar size={16} />
              Date d'effet
            </label>
            <input
              type="date"
              value={formData.effective_date}
              onChange={(e) => setFormData({ ...formData, effective_date: e.target.value })}
              min={new Date().toISOString().split('T')[0]}
              required
            />
          </div>

          <div className="currency-form-group currency-form-group-full">
            <label>Notes (optionnel)</label>
            <input
              type="text"
              value={formData.notes}
              onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
              placeholder="Notes sur ce taux..."
              maxLength="1000"
            />
          </div>
        </div>

        {error && (
          <div className="currency-form-error">
            <AlertCircle size={16} />
            {error}
          </div>
        )}

        <button type="submit" className="currency-btn currency-btn-primary" disabled={loading}>
          {loading ? <RefreshCw size={18} className="currency-spin" /> : <Plus size={18} />}
          {loading ? 'Création...' : 'Créer le taux'}
        </button>
      </form>
    </div>
  );
};

// === RATE HISTORY ROW ===
const RateHistoryRow = ({ rate, onUpdate }) => {
  const [isEditing, setIsEditing] = useState(false);
  const [editData, setEditData] = useState({
    euro_rate: rate.rates_in_ariary.euro.value,
    yuan_rate: rate.rates_in_ariary.yuan.value,
    dollar_rate: rate.rates_in_ariary.dollar.value,
    baht_rate: rate.rates_in_ariary.baht.value,
    effective_date: rate.effective_date,
    is_active: rate.is_active
  });
  const [loading, setLoading] = useState(false);

  const handleSave = async () => {
    setLoading(true);
    try {
      await currencyService.updateCurrency(rate.id, { ...editData, dirham_rate: 0.1 });
      setIsEditing(false);
      onUpdate();
    } catch (err) {
      console.error(err);
      alert('Erreur lors de la mise à jour');
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    setEditData({
      euro_rate: rate.rates_in_ariary.euro.value,
      yuan_rate: rate.rates_in_ariary.yuan.value,
      dollar_rate: rate.rates_in_ariary.dollar.value,
      baht_rate: rate.rates_in_ariary.baht.value,
      effective_date: rate.effective_date,
      is_active: rate.is_active
    });
    setIsEditing(false);
  };

  if (isEditing) {
    return (
      <tr className="currency-rate-row currency-editing">
        <td>
          <input
            type="date"
            value={editData.effective_date}
            onChange={(e) => setEditData({ ...editData, effective_date: e.target.value })}
            className="currency-edit-input"
          />
        </td>
        <td>
          <input
            type="number"
            step="0.0001"
            value={editData.euro_rate}
            onChange={(e) => setEditData({ ...editData, euro_rate: e.target.value })}
            className="currency-edit-input"
          />
        </td>
        <td>
          <input
            type="number"
            step="0.0001"
            value={editData.dollar_rate}
            onChange={(e) => setEditData({ ...editData, dollar_rate: e.target.value })}
            className="currency-edit-input"
          />
        </td>
        <td>
          <input
            type="number"
            step="0.0001"
            value={editData.yuan_rate}
            onChange={(e) => setEditData({ ...editData, yuan_rate: e.target.value })}
            className="currency-edit-input"
          />
        </td>
        <td>
          <input
            type="number"
            step="0.0001"
            value={editData.baht_rate}
            onChange={(e) => setEditData({ ...editData, baht_rate: e.target.value })}
            className="currency-edit-input"
          />
        </td>
        <td className="currency-status-cell">
          <label className="currency-toggle-switch">
            <input
              type="checkbox"
              checked={editData.is_active}
              onChange={(e) => setEditData({ ...editData, is_active: e.target.checked })}
            />
            <span className="currency-toggle-slider"></span>
          </label>
        </td>
        <td className="currency-actions-cell">
          <button onClick={handleSave} className="currency-btn-icon currency-btn-success" disabled={loading}>
            <Save size={16} />
          </button>
          <button onClick={handleCancel} className="currency-btn-icon currency-btn-danger" disabled={loading}>
            <X size={16} />
          </button>
        </td>
      </tr>
    );
  }

  return (
    <tr className={`currency-rate-row ${rate.is_active ? 'currency-active-row' : ''}`}>
      <td>{new Date(rate.effective_date).toLocaleDateString('fr-FR')}</td>
      <td>{parseFloat(rate.rates_in_ariary.euro.value).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}</td>
      <td>{parseFloat(rate.rates_in_ariary.dollar.value).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}</td>
      <td>{parseFloat(rate.rates_in_ariary.yuan.value).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}</td>
      <td>{parseFloat(rate.rates_in_ariary.baht.value).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}</td>
      <td className="currency-status-cell">
        {rate.is_active ? (
          <span className="currency-status-badge currency-active">
            <Check size={14} />
            Actif
          </span>
        ) : (
          <span className="currency-status-badge currency-inactive">Inactif</span>
        )}
      </td>
      <td className="currency-actions-cell">
        <button onClick={() => setIsEditing(true)} className="currency-btn-icon currency-btn-edit">
          <Edit2 size={16} />
        </button>
      </td>
    </tr>
  );
};

// === RATE HISTORY ===
const RateHistory = ({ rates, meta, filters, onFilterChange, onPageChange, onRefresh }) => {
  return (
    <div className="currency-rate-history-section">
      <div className="currency-section-header">
        <Calendar size={20} />
        <h2>Historique des Taux</h2>
        <button onClick={onRefresh} className="currency-btn currency-btn-secondary currency-btn-sm">
          <RefreshCw size={16} />
        </button>
      </div>

      {/* Filters */}
      <div className="currency-history-filters">
        <div className="currency-filter-row">
          <div className="currency-filter-group">
            <label>Date de début</label>
            <input
              type="date"
              value={filters.from_date}
              onChange={(e) => onFilterChange({ ...filters, from_date: e.target.value })}
            />
          </div>
          <div className="currency-filter-group">
            <label>Date de fin</label>
            <input
              type="date"
              value={filters.to_date}
              onChange={(e) => onFilterChange({ ...filters, to_date: e.target.value })}
            />
          </div>
          <div className="currency-filter-group">
            <label>Mois</label>
            <select value={filters.month} onChange={(e) => onFilterChange({ ...filters, month: e.target.value })}>
              <option value="">Tous</option>
              {Array.from({ length: 12 }, (_, i) => (
                <option key={i + 1} value={i + 1}>
                  {new Date(2000, i).toLocaleDateString('fr-FR', { month: 'long' })}
                </option>
              ))}
            </select>
          </div>
          <div className="currency-filter-group">
            <label>Année</label>
            <input
              type="number"
              value={filters.year}
              onChange={(e) => onFilterChange({ ...filters, year: e.target.value })}
              placeholder="2026"
            />
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="currency-rate-table-wrapper">
        <table className="currency-rate-table">
          <thead>
            <tr>
              <th>Date d'effet</th>
              <th>EUR</th>
              <th>USD</th>
              <th>CNY</th>
              <th>THB</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {rates.length === 0 ? (
              <tr>
                <td colSpan="7" style={{ textAlign: 'center', padding: '32px', color: '#86868b' }}>
                  Aucun taux trouvé
                </td>
              </tr>
            ) : (
              rates.map((rate) => (
                <RateHistoryRow key={rate.id} rate={rate} onUpdate={onRefresh} />
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="currency-pagination">
          <button onClick={() => onPageChange(1)} disabled={meta.current_page === 1}>
            <ChevronsLeft size={18} />
          </button>
          <button onClick={() => onPageChange(meta.current_page - 1)} disabled={meta.current_page === 1}>
            <ChevronLeft size={18} />
          </button>
          <span>{meta.current_page} / {meta.last_page}</span>
          <button onClick={() => onPageChange(meta.current_page + 1)} disabled={meta.current_page === meta.last_page}>
            <ChevronRight size={18} />
          </button>
          <button onClick={() => onPageChange(meta.last_page)} disabled={meta.current_page === meta.last_page}>
            <ChevronsRight size={18} />
          </button>
        </div>
      )}
    </div>
  );
};

// === MAIN COMPONENT ===
const CurrencyRates = () => {
  const [rates, setRates] = useState([]);
  const [meta, setMeta] = useState(null);
  const [currentRate, setCurrentRate] = useState(null);
  const [accountStats, setAccountStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [filters, setFilters] = useState({
    from_date: '',
    to_date: '',
    month: '',
    year: '',
    sort_by: 'effective_date',
    sort_order: 'desc'
  });

  const fetchData = useCallback(async () => {
    setLoading(true);
    try {
      const params = { 
        page: currentPage, 
        per_page: 20,
        ...filters
      };

      Object.keys(params).forEach(key => {
        if (params[key] === '') delete params[key];
      });

      const [ratesRes, accountsRes] = await Promise.all([
        currencyService.getAll(params),
        accountService.getAll()
      ]);

      setRates(ratesRes.data || []);
      setMeta(ratesRes.meta || null);
      setCurrentRate(ratesRes.data?.find(r => r.is_active) || null);
      setAccountStats(accountsRes.stats || null);
    } catch (err) {
      console.error('Erreur:', err);
    } finally {
      setLoading(false);
    }
  }, [currentPage, filters]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleFilterChange = (newFilters) => {
    setFilters(newFilters);
    setCurrentPage(1);
  };

  if (loading) {
    return (
      <div className="currency-page">
        <div className="currency-loading-state">
          <RefreshCw className="currency-spin" size={32} />
          <p>Chargement...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="currency-page">
      <motion.div className="currency-page-header" initial={{ opacity: 0, y: -20 }} animate={{ opacity: 1, y: 0 }}>
        <div>
          <h1>Taux de Change & Conversion</h1>
          <p>Visualisez votre trésorerie en différentes devises</p>
        </div>
      </motion.div>

      <AnimatePresence mode="wait">
        {currentRate && accountStats && (
          <CurrentRateDisplay 
            rate={currentRate}
            totalBalance={accountStats.total_money}
            byType={accountStats.by_type}
          />
        )}

        <NewRateForm onSuccess={fetchData} />

        <RateHistory 
          rates={rates}
          meta={meta}
          filters={filters}
          onFilterChange={handleFilterChange}
          onPageChange={setCurrentPage}
          onRefresh={fetchData}
        />
      </AnimatePresence>
    </div>
  );
};

export default CurrencyRates;