import React, { useState, useRef, useEffect } from 'react';
import { 
  Wallet, 
  ChevronDown, 
  Check, 
  Building2, 
  CreditCard, 
  Banknote,
  TrendingDown,
  AlertTriangle,
  X,
  Lock
} from 'lucide-react';
import './AccountSelector.css';

const AccountSelector = ({ 
  accounts = [], 
  selectedAccountId, 
  onSelect, 
  amount = 0,
  label = "Compte de paiement",
  required = false,
  disabled = false,
  placeholder = "Sélectionner un compte"
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [dropdownPosition, setDropdownPosition] = useState({ top: 0, left: 0, width: 0 });
  const triggerRef = useRef(null);
  const dropdownRef = useRef(null);

  const selectedAccount = accounts.find(acc => acc.id === parseInt(selectedAccountId));
  const balanceAfter = selectedAccount ? selectedAccount.current_balance - amount : 0;
  const isInsufficientFunds = selectedAccount && balanceAfter < 0;
  
  // Vérifier si ce compte a un montant réservé par un autre paiement
  const reservedAmount = selectedAccount?.reserved_for_supplier || selectedAccount?.reserved_for_freight || 0;
  const hasReservedAmount = reservedAmount > 0;

  // Calculer la position du dropdown
  const updateDropdownPosition = () => {
    if (triggerRef.current) {
      const rect = triggerRef.current.getBoundingClientRect();
      const spaceBelow = window.innerHeight - rect.bottom;
      const dropdownHeight = 400;
      
      // Si pas assez d'espace en bas, ouvrir vers le haut
      const shouldOpenUp = spaceBelow < dropdownHeight && rect.top > dropdownHeight;
      
      setDropdownPosition({
        top: shouldOpenUp ? rect.top - dropdownHeight - 8 : rect.bottom + 8,
        left: rect.left,
        width: rect.width
      });
    }
  };

  // Fermer le dropdown si on clique en dehors
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (
        triggerRef.current && 
        !triggerRef.current.contains(event.target) &&
        dropdownRef.current &&
        !dropdownRef.current.contains(event.target)
      ) {
        setIsOpen(false);
      }
    };

    const handleScroll = () => {
      if (isOpen) {
        updateDropdownPosition();
      }
    };

    const handleResize = () => {
      if (isOpen) {
        updateDropdownPosition();
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    window.addEventListener('scroll', handleScroll, true);
    window.addEventListener('resize', handleResize);
    
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      window.removeEventListener('scroll', handleScroll, true);
      window.removeEventListener('resize', handleResize);
    };
  }, [isOpen]);

  // Mettre à jour la position quand le dropdown s'ouvre
  useEffect(() => {
    if (isOpen) {
      updateDropdownPosition();
    }
  }, [isOpen]);

  const getAccountIcon = (accountType) => {
    const typeName = accountType?.name?.toLowerCase() || '';
    if (typeName.includes('bank') || typeName.includes('banque')) {
      return <Building2 size={20} />;
    }
    if (typeName.includes('mobile') || typeName.includes('mvola') || typeName.includes('orange')) {
      return <CreditCard size={20} />;
    }
    return <Banknote size={20} />;
  };

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(value);
  };

  const handleSelect = (account) => {
    onSelect(account.id.toString());
    setIsOpen(false);
  };

  const handleTriggerClick = () => {
    if (!disabled) {
      if (!isOpen) {
        updateDropdownPosition();
      }
      setIsOpen(!isOpen);
    }
  };

  return (
    <div className="account-selector-container">
      {label && (
        <label className={`account-selector-label ${required ? 'required' : ''}`}>
          <Wallet size={16} />
          {label}
        </label>
      )}

      <div 
        ref={triggerRef}
        className={`account-selector-trigger ${isOpen ? 'open' : ''} ${disabled ? 'disabled' : ''} ${isInsufficientFunds ? 'warning' : ''}`}
        onClick={handleTriggerClick}
      >
        {selectedAccount ? (
          <div className="account-selector-selected">
            <div className="account-selector-icon">
              {getAccountIcon(selectedAccount.account_type)}
            </div>
            <div className="account-selector-info">
              <span className="account-selector-name">{selectedAccount.name}</span>
              <span className="account-selector-type">
                {selectedAccount.account_type?.display_name || 'Compte'}
              </span>
            </div>
            <div className="account-selector-balance">
              <span className="balance-amount">{formatCurrency(selectedAccount.current_balance)} Ar</span>
              {hasReservedAmount && (
                <span className="balance-reserved">
                  <Lock size={10} />
                  {formatCurrency(reservedAmount)} réservé
                </span>
              )}
            </div>
          </div>
        ) : (
          <div className="account-selector-placeholder">
            <Wallet size={20} />
            <span>{placeholder}</span>
          </div>
        )}
        <ChevronDown className={`account-selector-chevron ${isOpen ? 'rotated' : ''}`} size={20} />
      </div>

      {/* Affichage du montant à débiter et solde après */}
      {selectedAccount && amount > 0 && (
        <div className={`account-balance-preview ${isInsufficientFunds ? 'insufficient' : ''}`}>
          {hasReservedAmount && (
            <>
              <div className="balance-preview-row reserved">
                <span className="balance-preview-label">
                  <Lock size={14} />
                  Réservé pour {selectedAccount.reserved_for_supplier ? 'fournisseur' : 'transport'}
                </span>
                <span className="balance-preview-value reserved">- {formatCurrency(reservedAmount)} Ar</span>
              </div>
            </>
          )}
          <div className="balance-preview-row">
            <span className="balance-preview-label">
              <TrendingDown size={14} />
              Montant à débiter
            </span>
            <span className="balance-preview-value debit">- {formatCurrency(amount)} Ar</span>
          </div>
          <div className="balance-preview-divider"></div>
          <div className="balance-preview-row">
            <span className="balance-preview-label">Solde après opération</span>
            <span className={`balance-preview-value ${isInsufficientFunds ? 'negative' : 'positive'}`}>
              {formatCurrency(balanceAfter)} Ar
            </span>
          </div>
          {isInsufficientFunds && (
            <div className="balance-preview-warning">
              <AlertTriangle size={14} />
              <span>Fonds insuffisants sur ce compte</span>
            </div>
          )}
        </div>
      )}

      {/* Dropdown des comptes - Position fixed */}
      {isOpen && (
        <div 
          ref={dropdownRef}
          className="account-selector-dropdown"
          style={{
            top: `${dropdownPosition.top}px`,
            left: `${dropdownPosition.left}px`,
            width: `${dropdownPosition.width}px`
          }}
        >
          <div className="account-selector-dropdown-header">
            <span>Sélectionner un compte</span>
            <button 
              type="button" 
              className="account-selector-close"
              onClick={() => setIsOpen(false)}
            >
              <X size={16} />
            </button>
          </div>
          <div className="account-selector-options">
            {accounts.length === 0 ? (
              <div className="account-selector-empty">
                <Wallet size={24} />
                <span>Aucun compte disponible</span>
              </div>
            ) : (
              accounts.map(account => {
                const isSelected = account.id === parseInt(selectedAccountId);
                const accountReserved = account.reserved_for_supplier || account.reserved_for_freight || 0;
                const previewBalance = account.current_balance - amount;
                const wouldBeNegative = amount > 0 && previewBalance < 0;

                return (
                  <div
                    key={account.id}
                    className={`account-selector-option ${isSelected ? 'selected' : ''} ${wouldBeNegative ? 'warning' : ''}`}
                    onClick={() => handleSelect(account)}
                  >
                    <div className="account-option-icon">
                      {getAccountIcon(account.account_type)}
                    </div>
                    <div className="account-option-details">
                      <div className="account-option-header">
                        <span className="account-option-name">{account.name}</span>
                        {isSelected && <Check size={16} className="account-option-check" />}
                      </div>
                      <span className="account-option-type">
                        {account.account_type?.display_name || 'Compte'}
                      </span>
                      <div className="account-option-balances">
                        <span className="account-option-current">
                          Solde: <strong>{formatCurrency(account.current_balance)} Ar</strong>
                          {accountReserved > 0 && (
                            <span className="account-option-reserved-badge">
                              <Lock size={10} />
                              {formatCurrency(accountReserved)} réservé
                            </span>
                          )}
                        </span>
                        {amount > 0 && (
                          <span className={`account-option-after ${wouldBeNegative ? 'negative' : ''}`}>
                            Après: {formatCurrency(previewBalance)} Ar
                          </span>
                        )}
                      </div>
                    </div>
                    {wouldBeNegative && (
                      <AlertTriangle size={16} className="account-option-warning-icon" />
                    )}
                  </div>
                );
              })
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default AccountSelector;
