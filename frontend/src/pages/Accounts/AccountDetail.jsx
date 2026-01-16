import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'motion/react';
import {
  ArrowLeft, Edit, Trash2, RefreshCw, Wallet, Landmark, Smartphone,
  Banknote, TrendingUp, TrendingDown, ArrowLeftRight, Calendar,
  ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight,
  AlertCircle, Clock, CheckCircle, XCircle, FileText, RotateCcw,
  ChevronRight as ChevronRightIcon
} from 'lucide-react';
import accountService from '../../services/accountService';
import '../../styles/AccountDetail.css';

// === HELPER FUNCTIONS ===
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(amount);
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }).format(date);
};

const formatDateTime = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
};

const getRelativeTime = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  
  if (diffDays === 0) return "Aujourd'hui";
  if (diffDays === 1) return 'Hier';
  if (diffDays < 7) return `Il y a ${diffDays} jours`;
  if (diffDays < 30) return `Il y a ${Math.floor(diffDays / 7)} sem.`;
  return formatDate(dateString);
};

const getAccountTypeConfig = (code) => {
  const config = {
    'CASH': { icon: Banknote, className: 'cash', label: 'Espèces' },
    'MOBILE_MONEY': { icon: Smartphone, className: 'mobile', label: 'Mobile Money' },
    'BANK': { icon: Landmark, className: 'bank', label: 'Banque' }
  };
  return config[code] || { icon: Wallet, className: '', label: 'Compte' };
};

const getTransactionConfig = (category) => {
  const config = {
    'income': { icon: TrendingUp, className: 'income', prefix: '+' },
    'expense': { icon: TrendingDown, className: 'expense', prefix: '-' },
    'transfer': { icon: ArrowLeftRight, className: 'transfer', prefix: '' },
    'adjustment': { icon: RotateCcw, className: 'adjustment', prefix: '' }
  };
  return config[category] || { icon: FileText, className: '', prefix: '' };
};

// === BALANCE CARD ===
const BalanceCard = ({ account, totals }) => {
  return (
    <motion.div
      className="balance-card"
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: 0.1 }}
    >
      <div className="balance-card-header">
        <div>
          <p className="balance-card-label">Solde actuel</p>
          <p className="balance-card-value">
            {formatAmount(account.current_balance)}
            <span className="currency">Ar</span>
          </p>
        </div>
        <div className="balance-card-initial">
          <p className="balance-card-initial-label">Solde initial</p>
          <p className="balance-card-initial-value">{formatAmount(account.initial_balance)} Ar</p>
        </div>
      </div>
      
      {totals && (
        <div className="balance-card-stats">
          <div className="balance-stat">
            <p className="balance-stat-label">Entrées</p>
            <p className="balance-stat-value income">+{formatAmount(totals.income)} Ar</p>
          </div>
          <div className="balance-stat">
            <p className="balance-stat-label">Sorties</p>
            <p className="balance-stat-value expense">-{formatAmount(totals.expense)} Ar</p>
          </div>
          <div className="balance-stat">
            <p className="balance-stat-label">Transferts</p>
            <p className="balance-stat-value transfer">{formatAmount(totals.transfer)} Ar</p>
          </div>
        </div>
      )}
    </motion.div>
  );
};

// === INFO GRID ===
const InfoGrid = ({ account }) => {
  const infoItems = [
    { label: 'Créé par', value: account.created_by?.name || 'N/A' },
    { label: 'Date de création', value: formatDateTime(account.created_at) },
    { label: 'Dernière mise à jour', value: formatDateTime(account.updated_at) },
  ];

  return (
    <motion.div
      className="account-info-grid"
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: 0.2 }}
    >
      {infoItems.map((item, index) => (
        <div key={index} className="info-card">
          <p className="info-card-label">{item.label}</p>
          <p className="info-card-value">{item.value}</p>
        </div>
      ))}
      {account.notes && (
        <div className="info-card" style={{ gridColumn: '1 / -1' }}>
          <p className="info-card-label">Notes</p>
          <p className="info-card-value notes">{account.notes}</p>
        </div>
      )}
    </motion.div>
  );
};

// === TRANSACTION ITEM ===
const TransactionItem = ({ transaction, index, onNavigate }) => {
  const config = getTransactionConfig(transaction.type?.category);
  const Icon = config.icon;

  return (
    <motion.div
      className="transaction-item clickable"
      initial={{ opacity: 0, x: -20 }}
      animate={{ opacity: 1, x: 0 }}
      transition={{ delay: index * 0.03 }}
      onClick={() => onNavigate(`/transactions/${transaction.id}`)}
      whileHover={{ x: 2 }}
      whileTap={{ scale: 0.995 }}
    >
      <div className={`transaction-icon ${config.className}`}>
        <Icon size={18} />
      </div>
      
      <div className="transaction-details">
        <p className="transaction-recipient">{transaction.recipient_name || 'Transaction'}</p>
        <p className="transaction-category">
          {transaction.expense_category?.name || transaction.type?.name}
        </p>
      </div>
      
      <div className="transaction-amount-wrapper">
        <p className={`transaction-amount ${config.className}`}>
          {config.prefix}{formatAmount(transaction.amount?.value)} Ar
        </p>
        <p className="transaction-balance-after">
          Solde: {formatAmount(transaction.amount?.after)} Ar
        </p>
      </div>
      
      <div className="transaction-date">
        {getRelativeTime(transaction.transaction_date)}
      </div>

      <div className="transaction-chevron">
        <ChevronRight size={16} />
      </div>
    </motion.div>
  );
};

// === TRANSACTIONS SECTION ===
const TransactionsSection = ({ accountId }) => {
  const [transactions, setTransactions] = useState([]);
  const [totals, setTotals] = useState(null);
  const [loading, setLoading] = useState(true);
  const [meta, setMeta] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');

  const fetchTransactions = useCallback(async () => {
    try {
      setLoading(true);
      const params = { page: currentPage, per_page: 15 };
      if (startDate) params.start_date = startDate;
      if (endDate) params.end_date = endDate;

      const response = await accountService.getAccountTransactions(accountId, params);
      setTransactions(response.data || []);
      setTotals(response.totals || null);
      setMeta(response.meta || null);
    } catch (err) {
      console.error('Erreur transactions:', err);
    } finally {
      setLoading(false);
    }
  }, [accountId, currentPage, startDate, endDate]);

  useEffect(() => {
    fetchTransactions();
  }, [fetchTransactions]);

  useEffect(() => {
    setCurrentPage(1);
  }, [startDate, endDate]);

  const lastPage = meta?.last_page || 1;
  const navigate = useNavigate();

  return (
    <motion.div
      className="transactions-section"
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: 0.3 }}
    >
      <div className="transactions-header">
        <div className="transactions-title">
          <Clock size={18} />
          <span>Historique des transactions</span>
          {meta && <span style={{ fontWeight: 'normal', color: 'var(--text-tertiary)' }}>({meta.total})</span>}
        </div>
        
        <div className="transactions-filters">
          <div className="date-filter">
            <Calendar size={16} />
            <input
              type="date"
              className="date-input"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              placeholder="Date début"
            />
          </div>
          <div className="date-filter">
            <Calendar size={16} />
            <input
              type="date"
              className="date-input"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              placeholder="Date fin"
            />
          </div>
        </div>
      </div>

      <div className="transactions-list">
        {loading ? (
          <div className="detail-loading">
            <RefreshCw className="loading-spinner" size={24} />
            <p>Chargement...</p>
          </div>
        ) : transactions.length === 0 ? (
          <div className="transactions-empty">
            <div className="transactions-empty-icon">
              <FileText size={24} />
            </div>
            <p>Aucune transaction trouvée</p>
          </div>
        ) : (
          <AnimatePresence mode="popLayout">
            {transactions.map((transaction, index) => (
              <TransactionItem
                key={transaction.id}
                transaction={transaction}
                index={index}
                onNavigate={navigate}
              />
            ))}
          </AnimatePresence>
        )}
      </div>

      {meta && lastPage > 1 && (
        <div className="transactions-pagination">
          <button
            className="pagination-btn"
            disabled={currentPage === 1}
            onClick={() => setCurrentPage(1)}
          >
            <ChevronsLeft size={18} />
          </button>
          <button
            className="pagination-btn"
            disabled={currentPage === 1}
            onClick={() => setCurrentPage(p => p - 1)}
          >
            <ChevronLeft size={18} />
          </button>
          <span className="pagination-info">{currentPage} / {lastPage}</span>
          <button
            className="pagination-btn"
            disabled={currentPage === lastPage}
            onClick={() => setCurrentPage(p => p + 1)}
          >
            <ChevronRight size={18} />
          </button>
          <button
            className="pagination-btn"
            disabled={currentPage === lastPage}
            onClick={() => setCurrentPage(lastPage)}
          >
            <ChevronsRight size={18} />
          </button>
        </div>
      )}
    </motion.div>
  );
};

// === MAIN COMPONENT ===
const AccountDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  
  const [account, setAccount] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchAccount = async () => {
      try {
        setLoading(true);
        setError(null);
        const response = await accountService.getById(id);
        setAccount(response.data);
      } catch (err) {
        console.error('Erreur:', err);
        setError('Impossible de charger les détails du compte.');
      } finally {
        setLoading(false);
      }
    };

    if (id) fetchAccount();
  }, [id]);

  if (loading) {
    return (
      <div className="account-detail-page">
        <motion.div
          className="detail-loading"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
        >
          <RefreshCw className="loading-spinner" size={32} />
          <p>Chargement du compte...</p>
        </motion.div>
      </div>
    );
  }

  if (error || !account) {
    return (
      <div className="account-detail-page">
        <motion.div
          className="detail-error"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
        >
          <div className="detail-error-icon">
            <AlertCircle size={32} />
          </div>
          <h3>{error || 'Compte introuvable'}</h3>
          <button className="btn btn-primary" onClick={() => navigate('/comptes')}>
            <ArrowLeft size={18} />
            Retour aux comptes
          </button>
        </motion.div>
      </div>
    );
  }

  const typeConfig = getAccountTypeConfig(account.account_type?.code);
  const TypeIcon = typeConfig.icon;

  return (
    <div className="account-detail-page">
      <motion.button
        className="back-button"
        onClick={() => navigate('/comptes')}
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        whileHover={{ x: -4 }}
      >
        <ArrowLeft size={18} />
        Retour aux comptes
      </motion.button>

      <motion.div
        className="account-detail-header"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="account-detail-info">
          <div className={`account-detail-icon ${typeConfig.className}`}>
            <TypeIcon size={28} strokeWidth={1.5} />
          </div>
          <div className="account-detail-title">
            <h1>{account.name}</h1>
            <div className="account-detail-meta">
              <span className="account-type-tag">
                {account.account_type?.display_name}
              </span>
              {account.account_number && (
                <span className="account-number">{account.account_number}</span>
              )}
              <span className={`account-status ${account.is_active ? 'active' : 'inactive'}`}>
                {account.is_active ? (
                  <><CheckCircle size={12} /> Actif</>
                ) : (
                  <><XCircle size={12} /> Inactif</>
                )}
              </span>
            </div>
          </div>
        </div>

        <div className="account-detail-actions">
          <motion.button
            className="btn btn-secondary"
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={() => navigate(`/comptes/${id}/modifier`)}
          >
            <Edit size={18} />
            Modifier
          </motion.button>
        </div>
      </motion.div>

      <BalanceCard account={account} totals={null} />
      <InfoGrid account={account} />
      <TransactionsSection accountId={id} />
    </div>
  );
};

export default AccountDetail;
