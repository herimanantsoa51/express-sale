import React, { useMemo } from 'react';
import { Store, Truck, MessageSquare, AlertCircle, Calendar } from 'lucide-react';
import AccountSelector from '../../../components/AccountSelector/AccountSelector';
import './PaymentSection.css';

const PaymentSection = ({
  accounts,
  paySupplierNow,
  setPaySupplierNow,
  supplierPayment,
  setSupplierPayment,
  payFreightNow,
  setPayFreightNow,
  freightPayment,
  setFreightPayment,
  supplierTotal,
  freightCost,
  hasFreightForwarder
}) => {
  
  const accountsForSupplier = useMemo(() => {
    if (!payFreightNow || !freightPayment.accountId) {
      return accounts;
    }
    
    return accounts.map(account => {
      if (account.id === parseInt(freightPayment.accountId)) {
        return {
          ...account,
          current_balance: account.current_balance - freightCost,
          reserved_for_freight: freightCost
        };
      }
      return account;
    });
  }, [accounts, payFreightNow, freightPayment.accountId, freightCost]);

  const accountsForFreight = useMemo(() => {
    if (!paySupplierNow || !supplierPayment.accountId) {
      return accounts;
    }
    
    return accounts.map(account => {
      if (account.id === parseInt(supplierPayment.accountId)) {
        return {
          ...account,
          current_balance: account.current_balance - supplierTotal,
          reserved_for_supplier: supplierTotal
        };
      }
      return account;
    });
  }, [accounts, paySupplierNow, supplierPayment.accountId, supplierTotal]);

  const isSameAccount = paySupplierNow && payFreightNow && 
    supplierPayment.accountId && freightPayment.accountId &&
    supplierPayment.accountId === freightPayment.accountId;

  const combinedTotal = isSameAccount ? supplierTotal + freightCost : 0;
  
  const selectedAccount = isSameAccount 
    ? accounts.find(acc => acc.id === parseInt(supplierPayment.accountId))
    : null;
  const insufficientForBoth = selectedAccount && selectedAccount.current_balance < combinedTotal;

  return (
    <div className="payment-section">
      {isSameAccount && (
        <div className={`payment-sync-alert ${insufficientForBoth ? 'danger' : 'info'}`}>
          <AlertCircle size={20} />
          <div className="alert-content">
            <strong>
              {insufficientForBoth ? 'Fonds insuffisants !' : 'Même compte sélectionné'}
            </strong>
            <p>
              {insufficientForBoth 
                ? `Le compte "${selectedAccount.name}" n'a que ${new Intl.NumberFormat('fr-FR').format(selectedAccount.current_balance)} Ar mais vous voulez débiter ${new Intl.NumberFormat('fr-FR').format(combinedTotal)} Ar au total.`
                : `Total à débiter sur "${selectedAccount.name}" : ${new Intl.NumberFormat('fr-FR').format(combinedTotal)} Ar (Fournisseur: ${new Intl.NumberFormat('fr-FR').format(supplierTotal)} Ar + Transport: ${new Intl.NumberFormat('fr-FR').format(freightCost)} Ar)`
              }
            </p>
          </div>
        </div>
      )}

      <div className="payment-mandatory-notice">
        <AlertCircle size={20} />
        <div className="notice-content">
          <strong>Information importante</strong>
          <p>Tous les paiements doivent être effectués maintenant. Les paiements différés ne sont pas autorisés pour ce formulaire.</p>
        </div>
      </div>

      <div className="payment-card mandatory">
        <div className="payment-card-header">
          <div className="payment-card-icon supplier">
            <Store size={24} />
          </div>
          <div className="payment-card-title">
            <h4>Paiement Fournisseur <span className="required-badge">Obligatoire</span></h4>
            <p>Le paiement au fournisseur doit être effectué immédiatement</p>
          </div>
          <div className="payment-amount">
            <span className="amount-label">Montant</span>
            <span className="amount-value">
              {new Intl.NumberFormat('fr-FR').format(supplierTotal)} Ar
            </span>
          </div>
        </div>

        <div className="payment-card-content">
          <div className="payment-mandatory-info">
            <p className="mandatory-text">
              ⚠️ Ce paiement est obligatoire et doit être effectué immédiatement.
            </p>
          </div>

          <div className="payment-fields">
            <AccountSelector
              accounts={accountsForSupplier}
              selectedAccountId={supplierPayment.accountId}
              onSelect={(accountId) => setSupplierPayment({
                ...supplierPayment,
                accountId
              })}
              amount={supplierTotal}
              label="Compte de débit"
              required
              placeholder="Choisir le compte à débiter"
              mandatory
            />

            <div className="form-group">
              <label className="form-label">
                <Calendar size={14} />
                Date de transaction (optionnelle)
              </label>
              <input
                type="date"
                className="form-input"
                value={supplierPayment.transaction_date || ''}
                onChange={(e) => setSupplierPayment({
                  ...supplierPayment,
                  transaction_date: e.target.value
                })}
                max={new Date().toISOString().split('T')[0]}
              />
              <small className="form-hint">
                Laisser vide pour utiliser la date d'aujourd'hui
              </small>
            </div>

            <div className="payment-extras">
              <div className="form-group">
                <label className="form-label">
                  <MessageSquare size={14} />
                  Notes
                </label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="Notes additionnelles..."
                  value={supplierPayment.notes}
                  onChange={(e) => setSupplierPayment({
                    ...supplierPayment,
                    notes: e.target.value
                  })}
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      {hasFreightForwarder && (
        <div className="payment-card mandatory">
          <div className="payment-card-header">
            <div className="payment-card-icon freight">
              <Truck size={24} />
            </div>
            <div className="payment-card-title">
              <h4>Paiement Transport <span className="required-badge">Obligatoire</span></h4>
              <p>Les frais de transport doivent être payés maintenant</p>
            </div>
            <div className="payment-amount">
              <span className="amount-label">Montant</span>
              <span className="amount-value">
                {new Intl.NumberFormat('fr-FR').format(freightCost)} Ar
              </span>
            </div>
          </div>

          <div className="payment-card-content">
            <div className="payment-mandatory-info">
              <p className="mandatory-text">
                ⚠️ Ce paiement est obligatoire et doit être effectué immédiatement.
              </p>
            </div>

            <div className="payment-fields">
              <AccountSelector
                accounts={accountsForFreight}
                selectedAccountId={freightPayment.accountId}
                onSelect={(accountId) => setFreightPayment({
                  ...freightPayment,
                  accountId
                })}
                amount={freightCost}
                label="Compte de débit"
                required
                placeholder="Choisir le compte à débiter"
                mandatory
              />

              <div className="form-group">
                <label className="form-label">
                  <Calendar size={14} />
                  Date de transaction (optionnelle)
                </label>
                <input
                  type="date"
                  className="form-input"
                  value={freightPayment.transaction_date || ''}
                  onChange={(e) => setFreightPayment({
                    ...freightPayment,
                    transaction_date: e.target.value
                  })}
                  max={new Date().toISOString().split('T')[0]}
                />
                <small className="form-hint">
                  Laisser vide pour utiliser la date d'aujourd'hui
                </small>
              </div>

              <div className="payment-extras">
                <div className="form-group">
                  <label className="form-label">
                    <MessageSquare size={14} />
                    Notes
                  </label>
                  <input
                    type="text"
                    className="form-input"
                    placeholder="Notes additionnelles..."
                    value={freightPayment.notes}
                    onChange={(e) => setFreightPayment({
                      ...freightPayment,
                      notes: e.target.value
                    })}
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default PaymentSection;