import React from 'react';
import { Coins, Check, ArrowRight, TrendingUp } from 'lucide-react';
import './CurrencySelector.css';

const CURRENCIES = [
  { code: 'EUR', symbol: '€', name: 'Euro', flag: '🇪🇺' },
  { code: 'USD', symbol: '$', name: 'Dollar US', flag: '🇺🇸' },
  { code: 'CNY', symbol: '¥', name: 'Yuan Chinois', flag: '🇨🇳' },
  { code: 'THB', symbol: '฿', name: 'Baht Thaïlandais', flag: '🇹🇭' }
];

const CurrencySelector = ({ currencyRates, selectedCurrency, onCurrencyChange }) => {
  const getCurrencyRate = (currencyCode) => {
    if (!currencyRates?.rates_in_ariary) return null;
    
    const currencyMap = {
      'EUR': 'euro',
      'USD': 'dollar',
      'CNY': 'yuan',
      'THB': 'baht'
    };
    
    const rateKey = currencyMap[currencyCode];
    return currencyRates.rates_in_ariary[rateKey];
  };

  const formatRate = (value) => {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    }).format(value);
  };

  return (
    <div className="cs-section">
      <div className="cs-header">
        <div className="cs-icon">
          <Coins size={24} />
        </div>
        <div className="cs-title">
          <h3>Devise de la commande</h3>
          <p>Choisissez la devise dans laquelle vous effectuez vos achats. Tous les montants seront convertis en Ariary.</p>
        </div>
      </div>

      <div className="cs-cards">
        {CURRENCIES.map((currency, index) => {
          const rate = getCurrencyRate(currency.code);
          const isSelected = selectedCurrency === currency.code;

          return (
            <div
              key={currency.code}
              className={`cs-card ${isSelected ? 'cs-card-selected' : ''}`}
              onClick={() => onCurrencyChange(currency.code)}
              style={{ animationDelay: `${index * 0.05}s` }}
            >
              <div className="cs-card-header">
                <span className="cs-flag">{currency.flag}</span>
                <span className="cs-code">{currency.code}</span>
                {isSelected && (
                  <div className="cs-check">
                    <Check size={16} />
                  </div>
                )}
              </div>
              
              <div className="cs-card-body">
                <span className="cs-name">{currency.name}</span>
                <span className="cs-symbol">{currency.symbol}</span>
              </div>

              {rate && (
                <div className="cs-rate">
                  <div className="cs-rate-label">
                    <TrendingUp size={12} />
                    <span>1 {currency.symbol}</span>
                  </div>
                  <ArrowRight size={12} className="cs-rate-arrow" />
                  <span className="cs-rate-value">{formatRate(rate.value)} Ar</span>
                </div>
              )}
            </div>
          );
        })}
      </div>

      {currencyRates && (
        <div className="cs-info">
          <div className="cs-info-content">
            <span className="cs-info-label">Taux de change en vigueur depuis le</span>
            <span className="cs-info-date">
              {new Date(currencyRates.effective_date).toLocaleDateString('fr-FR', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
              })}
            </span>
          </div>
        </div>
      )}
    </div>
  );
};

export default CurrencySelector;