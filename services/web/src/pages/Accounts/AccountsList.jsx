import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Search, Plus, ArrowLeftRight, RefreshCw, Wallet, Landmark, Smartphone,
  Banknote, ChevronRight, TrendingUp, PieChart, Filter, X,
  ChevronLeft, ChevronsLeft, ChevronsRight
} from 'lucide-react';
import accountService from '../../services/accountService';
import '../../styles/AccountsList.css';

// === TYPE DISTRIBUTION ===
const TypeDistribution = ({ byType }) => {
  if (!byType || byType.length === 0) return null;

  const getTypeIcon = (code) => {
    const icons = { 'CASH': Banknote, 'MOBILE_MONEY': Smartphone, 'BANK': Landmark };
    return icons[code] || Wallet;
  };

  const getTypeColor = (code) => {
    const colors = { 'CASH': 'var(--success)', 'MOBILE_MONEY': 'var(--warning)', 'BANK': 'var(--info)' };
    return colors[code] || 'var(--primary)';
  };

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-MG', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);
  };

  return (
    <div className="type-distribution">
      <div className="type-distribution-header">
        <PieChart size={18} />
        <span>Répartition par type</span>
      </div>
      <div className="type-distribution-grid">
        {byType.map((type, index) => {
          const Icon = getTypeIcon(type.type_code);
          const color = getTypeColor(type.type_code);
          const percentage = type.percentage || 0;
          
          return (
            <motion.div
              key={type.type_id}
              className="type-distribution-item"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.1 }}
            >
              <div className="type-item-header">
                <div className="type-item-icon" style={{ color }}>
                  <Icon size={18} />
                </div>
                <span className="type-item-name">{type.type_name}</span>
              </div>
              <div className="type-item-balance">{formatAmount(type.total_balance)} Ar</div>
              <div className="type-item-meta">
                <span>{type.account_count} compte{type.account_count > 1 ? 's' : ''}</span>
                <span className="type-item-percentage">{percentage.toFixed(1)}%</span>
              </div>
              <div className="type-item-bar">
                <motion.div 
                  className="type-item-bar-fill"
                  style={{ backgroundColor: color }}
                  initial={{ width: 0 }}
                  animate={{ width: `${percentage}%` }}
                  transition={{ duration: 0.8, ease: "easeOut" }}
                />
              </div>
            </motion.div>
          );
        })}
      </div>
    </div>
  );
};

// === ACCOUNT CARD ===
const AccountCard = ({ account, onClick, index }) => {
  const getAccountTypeConfig = (type) => {
    const typeMap = {
      'Espèces': { icon: Banknote, className: 'cash' },
      'Mobile Money': { icon: Smartphone, className: 'mobile' },
      'Banque': { icon: Landmark, className: 'bank' },
    };
    return typeMap[type] || { icon: Wallet, className: '' };
  };

  const { icon: Icon, className } = getAccountTypeConfig(account.account_type);

  const formatBalance = (balance) => {
    return new Intl.NumberFormat('fr-MG', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(balance);
  };

  return (
    <motion.div 
      className="account-card"
      onClick={onClick}
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3, delay: index * 0.05 }}
      whileHover={{ y: -4 }}
      whileTap={{ scale: 0.98 }}
    >
      <div className="account-card-header">
        <div className={`account-card-icon ${className}`}>
          <Icon size={22} strokeWidth={1.5} />
        </div>
        <span className="account-type-badge">{account.account_type}</span>
      </div>
      
      <div className="account-card-body">
        <h3 className="account-card-name">{account.name}</h3>
        {account.account_number && (
          <p className="account-card-number">{account.account_number}</p>
        )}
      </div>
      
      <div className="account-card-balance">
        <p className="account-card-balance-label">Solde actuel</p>
        <p className="account-card-balance-value">
          {formatBalance(account.current_balance)} <span className="currency">Ar</span>
        </p>
      </div>
      
      <ChevronRight className="account-card-arrow" size={20} />
    </motion.div>
  );
};

// === ACCOUNT STATS ===
const AccountStats = ({ stats }) => {
  const formatAmount = (amount) => {
    if (amount >= 1000000000) return (amount / 1000000000).toFixed(2) + ' Mrd';
    if (amount >= 1000000) return (amount / 1000000).toFixed(2) + ' M';
    return new Intl.NumberFormat('fr-MG').format(amount);
  };

  const statItems = [
    { label: 'Total des fonds', value: formatAmount(stats.total_money), suffix: 'Ar', icon: Wallet, highlight: true },
    { label: 'Comptes actifs', value: stats.active_accounts, icon: TrendingUp, success: true },
    { label: 'Comptes inactifs', value: stats.inactive_accounts, icon: Wallet },
    { label: 'Solde moyen', value: formatAmount(stats.average_balance), suffix: 'Ar', icon: PieChart },
  ];

  return (
    <div className="accounts-stats">
      {statItems.map((item, index) => {
        const Icon = item.icon;
        return (
          <motion.div 
            key={item.label}
            className={`stat-card ${item.highlight ? 'highlight' : ''} ${item.success ? 'success' : ''}`}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3, delay: index * 0.1 }}
          >
            <div className="stat-card-icon">
              <Icon size={20} strokeWidth={1.5} />
            </div>
            <div className="stat-card-content">
              <p className="stat-card-label">{item.label}</p>
              <p className="stat-card-value">
                {item.value}
                {item.suffix && <span className="stat-suffix">{item.suffix}</span>}
              </p>
            </div>
          </motion.div>
        );
      })}
    </div>
  );
};

// === ACCOUNT FILTERS ===
const AccountFilters = ({ search, onSearchChange, typeFilter, onTypeChange, statusFilter, onStatusChange, accountTypes, onClearFilters }) => {
  const hasFilters = search || typeFilter || statusFilter;

  return (
    <motion.div className="accounts-filters" initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}>
      <div className="filters-row">
        <div className="search-input-wrapper">
          <Search size={18} className="search-icon" />
          <input
            type="text"
            className="search-input"
            placeholder="Rechercher un compte..."
            value={search}
            onChange={(e) => onSearchChange(e.target.value)}
          />
          {search && (
            <button className="search-clear" onClick={() => onSearchChange('')}>
              <X size={16} />
            </button>
          )}
        </div>
        
        <div className="filter-group">
          <div className="filter-select-wrapper">
            <Filter size={16} className="filter-icon" />
            <select className="filter-select" value={typeFilter} onChange={(e) => onTypeChange(e.target.value)}>
              <option value="">Tous les types</option>
              {accountTypes.map((type) => (
                <option key={type.type_id} value={type.type_id}>{type.type_name}</option>
              ))}
            </select>
          </div>
          
          <div className="filter-select-wrapper">
            <select className="filter-select" value={statusFilter} onChange={(e) => onStatusChange(e.target.value)}>
              <option value="">Tous les statuts</option>
              <option value="true">Actifs</option>
              <option value="false">Inactifs</option>
            </select>
          </div>
        </div>

        <AnimatePresence>
          {hasFilters && (
            <motion.button
              className="clear-filters-btn"
              onClick={onClearFilters}
              initial={{ opacity: 0, scale: 0.8 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.8 }}
            >
              <X size={16} />
              Effacer
            </motion.button>
          )}
        </AnimatePresence>
      </div>
    </motion.div>
  );
};

// === MAIN COMPONENT ===
const AccountsList = () => {
  const navigate = useNavigate();
  
  const [accounts, setAccounts] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [meta, setMeta] = useState(null);

  const fetchAccounts = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const params = { page: currentPage, per_page: 12 };
      if (search) params.search = search;
      if (typeFilter) params.type_id = typeFilter;
      if (statusFilter) params.is_active = statusFilter;
      
      const response = await accountService.getAll(params);
      setAccounts(response.data || []);
      setStats(response.stats || null);
      setMeta(response.meta || null);
    } catch (err) {
      console.error('Erreur:', err);
      setError('Impossible de charger les comptes.');
    } finally {
      setLoading(false);
    }
  }, [currentPage, search, typeFilter, statusFilter]);

  useEffect(() => { fetchAccounts(); }, [fetchAccounts]);
  
  useEffect(() => {
    const timer = setTimeout(() => setCurrentPage(1), 300);
    return () => clearTimeout(timer);
  }, [search]);

  const handleClearFilters = () => { 
    setSearch(''); 
    setTypeFilter(''); 
    setStatusFilter(''); 
    setCurrentPage(1); 
  };
  
  const accountTypes = stats?.by_type || [];
  const lastPage = meta ? (Array.isArray(meta.last_page) ? meta.last_page[0] : meta.last_page) : 1;

  const renderContent = () => {
    if (loading) {
      return (
        <motion.div className="accounts-loading" initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
          <RefreshCw className="loading-spinner" size={32} />
          <p>Chargement des comptes...</p>
        </motion.div>
      );
    }

    if (error) {
      return (
        <motion.div className="accounts-empty" initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}>
          <div className="accounts-empty-icon error"><X size={48} /></div>
          <h3>Erreur</h3>
          <p>{error}</p>
          <button className="btn btn-primary" onClick={fetchAccounts}>
            <RefreshCw size={18} />
            Réessayer
          </button>
        </motion.div>
      );
    }

    if (accounts.length === 0) {
      return (
        <motion.div className="accounts-empty" initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}>
          <div className="accounts-empty-icon"><Wallet size={48} /></div>
          <h3>Aucun compte trouvé</h3>
          <p>{search || typeFilter || statusFilter ? 'Aucun compte ne correspond à vos critères.' : 'Créez votre premier compte.'}</p>
          {!search && !typeFilter && !statusFilter && (
            <button className="btn btn-primary" onClick={() => navigate('/comptes/nouveau')}>
              <Plus size={18} />
              Créer un compte
            </button>
          )}
        </motion.div>
      );
    }

    return (
      <>
        <div className="accounts-grid">
          <AnimatePresence mode="popLayout">
            {accounts.map((account, index) => (
              <AccountCard 
                key={account.id} 
                account={account} 
                index={index} 
                onClick={() => navigate(`/comptes/${account.id}`)} 
              />
            ))}
          </AnimatePresence>
        </div>
        {meta && lastPage > 1 && (
          <motion.div className="accounts-pagination" initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
            <button className="pagination-btn" disabled={currentPage === 1} onClick={() => setCurrentPage(1)}>
              <ChevronsLeft size={18} />
            </button>
            <button className="pagination-btn" disabled={currentPage === 1} onClick={() => setCurrentPage((p) => p - 1)}>
              <ChevronLeft size={18} />
            </button>
            <span className="pagination-info">{currentPage} / {lastPage}</span>
            <button className="pagination-btn" disabled={currentPage === lastPage} onClick={() => setCurrentPage((p) => p + 1)}>
              <ChevronRight size={18} />
            </button>
            <button className="pagination-btn" disabled={currentPage === lastPage} onClick={() => setCurrentPage(lastPage)}>
              <ChevronsRight size={18} />
            </button>
          </motion.div>
        )}
      </>
    );
  };

  return (
    <div className="accounts-page">
      <motion.div className="accounts-header" initial={{ opacity: 0, y: -20 }} animate={{ opacity: 1, y: 0 }}>
        <div className="accounts-header-left">
          <h1>Trésorerie</h1>
          <p>Gérez vos comptes et suivez vos soldes</p>
        </div>
        <div className="accounts-header-actions">
          <motion.button className="btn btn-secondary" onClick={() => navigate('/comptes/transfert')} whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.98 }}>
            <ArrowLeftRight size={18} />
            Transfert
          </motion.button>
          <motion.button className="btn btn-secondary" onClick={() => navigate('/comptes/conversion')} whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.98 }}>
            <RefreshCw size={18} />
            Conversion
          </motion.button>
          <motion.button className="btn btn-primary" onClick={() => navigate('/comptes/nouveau')} whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.98 }}>
            <Plus size={18} />
            Nouveau compte
          </motion.button>
        </div>
      </motion.div>

      {stats && <AccountStats stats={stats} />}
      {stats?.by_type && <TypeDistribution byType={stats.by_type} />}

      <AccountFilters
        search={search} 
        onSearchChange={setSearch}
        typeFilter={typeFilter} 
        onTypeChange={(v) => { setTypeFilter(v); setCurrentPage(1); }}
        statusFilter={statusFilter} 
        onStatusChange={(v) => { setStatusFilter(v); setCurrentPage(1); }}
        accountTypes={accountTypes} 
        onClearFilters={handleClearFilters}
      />

      {renderContent()}
    </div>
  );
};

export default AccountsList;
