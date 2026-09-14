import React from 'react';
import { useNavigate } from 'react-router-dom';
import StatusBadge from './StatusBadge';
import { formatCurrency, formatDate, calculatePercentage, daysUntil } from '../../utils/formatters';
import './ReservationCard.css';

/**
 * Carte de réservation pour la vue grille
 */
const ReservationCard = ({ reservation, onSelect, isSelected }) => {
  const navigate = useNavigate();

  const paymentPercentage = calculatePercentage(
    reservation.deposit_amount,
    reservation.total_amount
  );

  const daysRemaining = daysUntil(reservation.expiry_date);
  const isExpiringSoon = daysRemaining <= 7 && daysRemaining > 0;
  const isExpired = daysRemaining < 0;

  const handleClick = () => {
    navigate(`/reservations/${reservation.id}`);
  };

  const handleCheckboxClick = (e) => {
    e.stopPropagation();
    onSelect(reservation.id);
  };

  return (
    <div 
      className={`reservation-card ${isSelected ? 'reservation-card--selected' : ''}`}
      onClick={handleClick}
    >
      {/* Header with checkbox and status */}
      <div className="reservation-card__header">
        <div className="reservation-card__checkbox" onClick={handleCheckboxClick}>
          <input
            type="checkbox"
            checked={isSelected}
            onChange={() => {}}
            aria-label="Sélectionner"
          />
        </div>
        <StatusBadge status={reservation.status} />
      </div>

      {/* Sale Number */}
      <div className="reservation-card__number">
        <div className="reservation-card__label">N° Vente</div>
        <div className="reservation-card__value reservation-card__value--primary">
          {reservation.sale_number}
        </div>
      </div>

      {/* Customer */}
      <div className="reservation-card__client">
        <div className="reservation-card__client-avatar">
          {reservation.customer.name.charAt(0)}
        </div>
        <div className="reservation-card__client-info">
          <div className="reservation-card__client-name">
            {reservation.customer.name}
          </div>
          <div className="reservation-card__client-code">
            {reservation.customer.code}
          </div>
        </div>
      </div>

      {/* Dates */}
      <div className="reservation-card__dates">
        <div className="reservation-card__date-item">
          <span className="reservation-card__date-icon">📅</span>
          <div>
            <div className="reservation-card__date-label">Réservation</div>
            <div className="reservation-card__date-value">
              {formatDate(reservation.reservation_date)}
            </div>
          </div>
        </div>
        <div className={`reservation-card__date-item ${isExpired ? 'reservation-card__date-item--expired' : ''} ${isExpiringSoon ? 'reservation-card__date-item--warning' : ''}`}>
          <span className="reservation-card__date-icon">⏰</span>
          <div>
            <div className="reservation-card__date-label">Expiration</div>
            <div className="reservation-card__date-value">
              {formatDate(reservation.expiry_date)}
              {!isExpired && daysRemaining >= 0 && (
                <div className="reservation-card__days-remaining">
                  {daysRemaining > 0 ? `${daysRemaining}j` : 'Aujourd\'hui'}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Amounts */}
      <div className="reservation-card__amounts">
        <div className="reservation-card__amount-row">
          <span className="reservation-card__amount-label">Total</span>
          <span className="reservation-card__amount-value">
            {formatCurrency(reservation.total_amount)}
          </span>
        </div>
        <div className="reservation-card__amount-row">
          <span className="reservation-card__amount-label">Acompte</span>
          <span className="reservation-card__amount-value reservation-card__amount-value--success">
            {formatCurrency(reservation.deposit_amount)}
          </span>
        </div>
        <div className="reservation-card__amount-row reservation-card__amount-row--highlight">
          <span className="reservation-card__amount-label">Reste</span>
          <span className="reservation-card__amount-value reservation-card__amount-value--danger">
            {formatCurrency(reservation.remaining_amount)}
          </span>
        </div>
      </div>

      {/* Progress */}
      <div className="reservation-card__progress">
        <div className="reservation-card__progress-bar">
          <div 
            className="reservation-card__progress-fill"
            style={{ width: `${paymentPercentage}%` }}
          ></div>
        </div>
        <div className="reservation-card__progress-label">
          {paymentPercentage}% payé
        </div>
      </div>

      {/* Actions */}
      <div className="reservation-card__actions">
        <button 
          className="reservation-card__action reservation-card__action--primary"
          onClick={handleClick}
        >
          <span>👁️</span>
          <span>Voir détails</span>
        </button>
      </div>
    </div>
  );
};

export default ReservationCard;
