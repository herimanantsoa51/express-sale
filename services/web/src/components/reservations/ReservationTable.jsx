import React from 'react';
import { useNavigate } from 'react-router-dom';
import { ChevronUp, ChevronDown } from 'lucide-react';
import StatusBadge from './StatusBadge';
import { formatCurrency, formatDate, calculatePercentage, daysUntil } from '../../utils/formatters';
import './ReservationTable.css';

const ReservationTable = ({ 
  reservations, 
  onSort,
  sortBy,
  sortOrder 
}) => {
  const navigate = useNavigate();

  const handleRowClick = (id) => {
    navigate(`/ventes/reservations/${id}`);
  };

  const handleSort = (field) => {
    const newOrder = sortBy === field && sortOrder === 'asc' ? 'desc' : 'asc';
    onSort(field, newOrder);
  };

  const SortIcon = ({ field }) => {
    if (sortBy !== field) return null;
    return sortOrder === 'asc' ? <ChevronUp size={14} /> : <ChevronDown size={14} />;
  };
    
  return (
    <div className="reservation-table-wrapper">
      <table className="reservation-table">
        <thead>
          <tr>
            <th className="th-sortable" onClick={() => handleSort('sale_number')}>
              <span>N° Vente</span>
              <SortIcon field="sale_number" />
            </th>
            <th>Client</th>
            <th className="th-sortable" onClick={() => handleSort('reservation_date')}>
              <span>Date réservation</span>
              <SortIcon field="reservation_date" />
            </th>
            <th className="th-sortable" onClick={() => handleSort('expiry_date')}>
              <span>Expiration</span>
              <SortIcon field="expiry_date" />
            </th>
            <th className="th-right">Total</th>
            <th className="th-right">Acompte</th>
            <th className="th-right">Reste</th>
            <th className="th-center">Statut</th>
          </tr>
        </thead>
        <tbody>
          {reservations.map((reservation) => {
            const daysRemaining = daysUntil(reservation.expiry_date);
            const isExpiringSoon = daysRemaining <= 7 && daysRemaining > 0;
            const isExpired = daysRemaining < 0;

            return (
              <tr 
                key={reservation.id}
                className={`${isExpired ? 'tr-expired' : ''} ${isExpiringSoon ? 'tr-warning' : ''}`}
                onClick={() => handleRowClick(reservation.id)}
              >
                <td className="td-primary">
                  <span className="sale-number">{reservation.sale_number}</span>
                </td>
                <td>
                  <div className="customer-cell">
                    <div className="customer-info">
                      <div className="customer-name">{reservation.customer?.name}</div>
                      <div className="customer-code">{reservation.customer?.code}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span className="date-value">{formatDate(reservation.reservation_date)}</span>
                </td>
                <td>
                  <div className="expiry-cell">
                    <span className={`date-value ${isExpired ? 'date-expired' : ''} ${isExpiringSoon ? 'date-warning' : ''}`}>
                      {formatDate(reservation.expiry_date)}
                    </span>
                    {!isExpired && daysRemaining >= 0 && (
                      <span className={`days-badge ${isExpiringSoon ? 'days-badge--warning' : ''}`}>
                        {daysRemaining}j
                      </span>
                    )}
                  </div>
                </td>
                <td className="td-amount">{formatCurrency(reservation.total_amount)}</td>
                <td className="td-amount td-success">{formatCurrency(reservation.deposit_amount)}</td>
                <td className="td-amount td-danger">{formatCurrency(reservation.remaining_amount)}</td>
                <td className="td-center">
                  <StatusBadge status={reservation.status} />
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
};

export default ReservationTable;
