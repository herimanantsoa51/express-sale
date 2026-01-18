import React from 'react';
import { 
  Store, 
  MessageSquare, 
  Calendar,
  Building2,
  CreditCard,
  Banknote,
  Lock,
  TrendingDown,
  AlertTriangle,
  Check
} from 'lucide-react';

const PaymentSection = ({
  accounts,
  supplierPayment,
  setSupplierPayment,
  supplierTotal
}) => {
  
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

  const selectedAccount = accounts.find(acc => acc.id === parseInt(supplierPayment.accountId));

  return (
    <div style={{
      display: 'flex',
      flexDirection: 'column',
      gap: '24px',
      animation: 'fadeIn 0.5s ease-out'
    }}>
      {/* Payment Card */}
      <div style={{
        background: 'var(--bg-primary)',
        border: '1.5px solid var(--primary)',
        borderRadius: '12px',
        overflow: 'hidden',
        boxShadow: '0 2px 16px rgba(0, 113, 227, 0.08)'
      }}>
        {/* Header */}
        <div style={{
          display: 'flex',
          alignItems: 'center',
          gap: '16px',
          padding: '20px 24px',
          background: 'linear-gradient(135deg, var(--primary-light), rgba(0, 113, 227, 0.03))',
          borderBottom: '1px solid var(--border-color)'
        }}>
          <div style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            width: '48px',
            height: '48px',
            borderRadius: '12px',
            background: 'linear-gradient(135deg, var(--primary), var(--primary-hover))',
            color: 'white',
            boxShadow: '0 4px 12px rgba(0, 113, 227, 0.25)',
            flexShrink: 0
          }}>
            <Store size={24} />
          </div>
          
          <div style={{ flex: 1, minWidth: 0 }}>
            <h4 style={{
              margin: 0,
              fontSize: '18px',
              fontWeight: '700',
              color: 'var(--text-primary)',
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              flexWrap: 'wrap'
            }}>
              Paiement Fournisseur
              <span style={{
                display: 'inline-flex',
                alignItems: 'center',
                background: 'var(--primary)',
                color: 'white',
                fontSize: '11px',
                fontWeight: '700',
                padding: '3px 8px',
                borderRadius: '6px',
                textTransform: 'uppercase',
                letterSpacing: '0.3px'
              }}>
                Obligatoire
              </span>
            </h4>
            <p style={{
              margin: '4px 0 0 0',
              fontSize: '14px',
              color: 'var(--text-secondary)',
              lineHeight: '1.4'
            }}>
              Le paiement au fournisseur doit être effectué immédiatement
            </p>
          </div>
          
          <div style={{ textAlign: 'right', flexShrink: 0 }}>
            <span style={{
              display: 'block',
              fontSize: '11px',
              color: 'var(--text-tertiary)',
              textTransform: 'uppercase',
              letterSpacing: '0.5px',
              marginBottom: '4px',
              fontWeight: '500'
            }}>
              Montant
            </span>
            <span style={{
              display: 'block',
              fontSize: '24px',
              fontWeight: '700',
              color: 'var(--primary)',
              fontVariantNumeric: 'tabular-nums'
            }}>
              {formatCurrency(supplierTotal)} Ar
            </span>
          </div>
        </div>

        {/* Content */}
        <div style={{ padding: '20px 24px' }}>
          <div style={{
            display: 'flex',
            flexDirection: 'column',
            gap: '20px'
          }}>
            {/* Account Selection Label */}
            <div>
              <label style={{
                display: 'block',
                fontSize: '14px',
                fontWeight: '600',
                color: 'var(--text-primary)',
                marginBottom: '12px',
                letterSpacing: '-0.01em'
              }}>
                Choisir le compte de débit
                <span style={{ color: 'var(--danger)', marginLeft: '4px' }}>*</span>
              </label>

              {/* Account Cards Grid */}
              <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))',
                gap: '12px'
              }}>
                {accounts.map(account => {
                  const isSelected = account.id === parseInt(supplierPayment.accountId);
                  const reservedAmount = account.reserved_for_supplier || account.reserved_for_freight || 0;
                  const hasReservedAmount = reservedAmount > 0;
                  const balanceAfter = account.current_balance - supplierTotal;
                  const isInsufficientFunds = balanceAfter < 0;

                  return (
                    <div
                      key={account.id}
                      onClick={() => setSupplierPayment({
                        ...supplierPayment,
                        accountId: account.id.toString()
                      })}
                      style={{
                        position: 'relative',
                        padding: '16px',
                        background: isSelected ? 'var(--primary-light)' : 'var(--bg-secondary)',
                        border: `2px solid ${isSelected ? 'var(--primary)' : isInsufficientFunds ? 'var(--warning)' : 'var(--border-color)'}`,
                        borderRadius: '10px',
                        cursor: 'pointer',
                        transition: 'all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1)',
                        transform: isSelected ? 'translateY(-2px)' : 'none',
                        boxShadow: isSelected ? '0 4px 12px rgba(0, 113, 227, 0.15)' : 'none'
                      }}
                      onMouseEnter={(e) => {
                        if (!isSelected) {
                          e.currentTarget.style.borderColor = 'var(--primary)';
                          e.currentTarget.style.boxShadow = '0 2px 8px rgba(0, 113, 227, 0.1)';
                        }
                      }}
                      onMouseLeave={(e) => {
                        if (!isSelected) {
                          e.currentTarget.style.borderColor = isInsufficientFunds ? 'var(--warning)' : 'var(--border-color)';
                          e.currentTarget.style.boxShadow = 'none';
                        }
                      }}
                    >
                      {/* Selected Check */}
                      {isSelected && (
                        <div style={{
                          position: 'absolute',
                          top: '12px',
                          right: '12px',
                          width: '24px',
                          height: '24px',
                          borderRadius: '50%',
                          background: 'var(--primary)',
                          color: 'white',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          boxShadow: '0 2px 8px rgba(0, 113, 227, 0.3)'
                        }}>
                          <Check size={14} strokeWidth={3} />
                        </div>
                      )}

                      {/* Account Header */}
                      <div style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '12px',
                        marginBottom: '12px'
                      }}>
                        <div style={{
                          width: '40px',
                          height: '40px',
                          borderRadius: '8px',
                          background: isSelected ? 'var(--primary)' : 'var(--bg-tertiary)',
                          color: isSelected ? 'white' : 'var(--primary)',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          flexShrink: 0
                        }}>
                          {getAccountIcon(account.account_type)}
                        </div>
                        
                        <div style={{ flex: 1, minWidth: 0 }}>
                          <div style={{
                            fontSize: '15px',
                            fontWeight: '600',
                            color: 'var(--text-primary)',
                            marginBottom: '2px',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap'
                          }}>
                            {account.name}
                          </div>
                          <div style={{
                            fontSize: '12px',
                            color: 'var(--text-secondary)'
                          }}>
                            {account.account_type?.display_name || 'Compte'}
                          </div>
                        </div>
                      </div>

                      {/* Balance Info */}
                      <div style={{
                        padding: '12px',
                        background: isInsufficientFunds ? 'var(--danger-light)' : 'var(--bg-primary)',
                        borderRadius: '8px',
                        border: `1px solid ${isInsufficientFunds ? 'var(--danger)' : 'var(--border-color)'}`,
                        fontSize: '13px'
                      }}>
                        {/* Reserved Amount */}
                        {hasReservedAmount && (
                          <div style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            marginBottom: '8px',
                            paddingBottom: '8px',
                            borderBottom: '1px dashed var(--border-color)'
                          }}>
                            <span style={{
                              display: 'flex',
                              alignItems: 'center',
                              gap: '6px',
                              color: 'var(--warning)',
                              fontWeight: '500'
                            }}>
                              <Lock size={12} />
                              Réservé
                            </span>
                            <span style={{
                              fontWeight: '600',
                              color: 'var(--warning)'
                            }}>
                              - {formatCurrency(reservedAmount)} Ar
                            </span>
                          </div>
                        )}

                        {/* Current Balance */}
                        <div style={{
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'center',
                          marginBottom: '6px'
                        }}>
                          <span style={{ color: 'var(--text-secondary)' }}>
                            Solde actuel
                          </span>
                          <span style={{
                            fontWeight: '600',
                            color: 'var(--success)'
                          }}>
                            {formatCurrency(account.current_balance)} Ar
                          </span>
                        </div>

                        {/* Debit Amount */}
                        <div style={{
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'center',
                          marginBottom: '8px'
                        }}>
                          <span style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: '6px',
                            color: 'var(--text-secondary)'
                          }}>
                            <TrendingDown size={12} />
                            À débiter
                          </span>
                          <span style={{
                            fontWeight: '600',
                            color: 'var(--danger)'
                          }}>
                            - {formatCurrency(supplierTotal)} Ar
                          </span>
                        </div>

                        {/* Divider */}
                        <div style={{
                          height: '1px',
                          background: 'var(--border-color)',
                          margin: '8px 0'
                        }} />

                        {/* Balance After */}
                        <div style={{
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'center'
                        }}>
                          <span style={{
                            fontWeight: '600',
                            color: 'var(--text-primary)'
                          }}>
                            Solde après
                          </span>
                          <span style={{
                            fontWeight: '700',
                            fontSize: '15px',
                            color: isInsufficientFunds ? 'var(--danger)' : 'var(--success)'
                          }}>
                            {formatCurrency(balanceAfter)} Ar
                          </span>
                        </div>

                        {/* Warning */}
                        {isInsufficientFunds && (
                          <div style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: '6px',
                            marginTop: '8px',
                            padding: '8px',
                            background: 'var(--danger)',
                            color: 'white',
                            borderRadius: '6px',
                            fontSize: '12px',
                            fontWeight: '500'
                          }}>
                            <AlertTriangle size={14} />
                            <span>Fonds insuffisants</span>
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>

              {accounts.length === 0 && (
                <div style={{
                  padding: '40px',
                  textAlign: 'center',
                  background: 'var(--bg-secondary)',
                  borderRadius: '10px',
                  border: '2px dashed var(--border-color)'
                }}>
                  <Banknote size={32} style={{ color: 'var(--text-tertiary)', marginBottom: '12px' }} />
                  <p style={{ color: 'var(--text-tertiary)', margin: 0 }}>
                    Aucun compte disponible
                  </p>
                </div>
              )}
            </div>

            {/* Transaction Date */}
            {selectedAccount && (
              <div style={{
                display: 'flex',
                flexDirection: 'column',
                gap: '8px',
                animation: 'slideUp 0.3s ease-out'
              }}>
                <label style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px',
                  fontSize: '14px',
                  fontWeight: '600',
                  color: 'var(--text-primary)',
                  letterSpacing: '-0.01em'
                }}>
                  <Calendar size={14} />
                  Date de transaction (optionnelle)
                </label>
                <input
                  type="date"
                  style={{
                    width: '100%',
                    padding: '12px 16px',
                    fontSize: '14px',
                    color: 'var(--text-primary)',
                    background: 'var(--bg-secondary)',
                    border: '1.5px solid var(--border-color)',
                    borderRadius: '8px',
                    outline: 'none',
                    transition: 'all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1)',
                    fontFamily: 'var(--font-family)'
                  }}
                  value={supplierPayment.transaction_date || ''}
                  onChange={(e) => setSupplierPayment({
                    ...supplierPayment,
                    transaction_date: e.target.value
                  })}
                  max={new Date().toISOString().split('T')[0]}
                  onFocus={(e) => {
                    e.target.style.borderColor = 'var(--primary)';
                    e.target.style.background = 'var(--bg-primary)';
                    e.target.style.boxShadow = '0 0 0 3px var(--primary-light)';
                  }}
                  onBlur={(e) => {
                    e.target.style.borderColor = 'var(--border-color)';
                    e.target.style.background = 'var(--bg-secondary)';
                    e.target.style.boxShadow = 'none';
                  }}
                />
                <small style={{
                  display: 'block',
                  marginTop: '4px',
                  fontSize: '12px',
                  color: 'var(--text-tertiary)',
                  fontStyle: 'italic',
                  lineHeight: '1.4'
                }}>
                  Laisser vide pour utiliser la date d'aujourd'hui
                </small>
              </div>
            )}

            {/* Notes */}
            {selectedAccount && (
              <div style={{
                display: 'flex',
                flexDirection: 'column',
                gap: '8px',
                animation: 'slideUp 0.3s ease-out 0.1s both'
              }}>
                <label style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px',
                  fontSize: '14px',
                  fontWeight: '600',
                  color: 'var(--text-primary)',
                  letterSpacing: '-0.01em'
                }}>
                  <MessageSquare size={14} />
                  Notes
                </label>
                <input
                  type="text"
                  style={{
                    width: '100%',
                    padding: '12px 16px',
                    fontSize: '14px',
                    color: 'var(--text-primary)',
                    background: 'var(--bg-secondary)',
                    border: '1.5px solid var(--border-color)',
                    borderRadius: '8px',
                    outline: 'none',
                    transition: 'all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1)',
                    fontFamily: 'var(--font-family)'
                  }}
                  placeholder="Notes additionnelles..."
                  value={supplierPayment.notes}
                  onChange={(e) => setSupplierPayment({
                    ...supplierPayment,
                    notes: e.target.value
                  })}
                  onFocus={(e) => {
                    e.target.style.borderColor = 'var(--primary)';
                    e.target.style.background = 'var(--bg-primary)';
                    e.target.style.boxShadow = '0 0 0 3px var(--primary-light)';
                  }}
                  onBlur={(e) => {
                    e.target.style.borderColor = 'var(--border-color)';
                    e.target.style.background = 'var(--bg-secondary)';
                    e.target.style.boxShadow = 'none';
                  }}
                />
              </div>
            )}
          </div>
        </div>
      </div>

      <style>{`
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
        
        @keyframes slideUp {
          from {
            opacity: 0;
            transform: translateY(16px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        @media (max-width: 768px) {
          /* Cards stack on mobile */
        }
      `}</style>
    </div>
  );
};

export default PaymentSection;