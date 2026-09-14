// components/CreditTable.jsx
import React from 'react';
import CreditRow from './CreditRow';
import '../../styles/components/CreditTable.css';

const CreditTable = ({ credits, sortBy, sortOrder, onSort, loading }) => {
  if (!credits || credits.length === 0) {
    return (
      <div className="credit-table-empty">
        <p>Aucun crédit trouvé</p>
      </div>
    );
  }

  return (
    <div className="credit-table-wrapper">
      <div className="credit-table">
        {/* En-têtes */}
        <div className="credit-table-header">
          <div className="credit-header-cell" style={{ minWidth: '120px' }}>
            Numéro
          </div>
          <div className="credit-header-cell" style={{ minWidth: '180px' }}>
            Client
          </div>
          <div className="credit-header-cell" style={{ minWidth: '130px' }}>
            Montant total
          </div>
          <div className="credit-header-cell" style={{ minWidth: '130px' }}>
            Montant dû
          </div>
          <div className="credit-header-cell" style={{ minWidth: '120px' }}>
            Échéance
          </div>
          <div className="credit-header-cell" style={{ minWidth: '100px' }}>
            Statut
          </div>
          <div className="credit-header-cell" style={{ minWidth: '140px' }}>
            Progression
          </div>
        </div>

        {/* Corps du tableau */}
        <div className={`credit-table-body ${loading ? 'loading' : ''}`}>
          {credits.map((credit) => (
            <CreditRow key={credit.id} credit={credit} />
          ))}
        </div>
      </div>
    </div>
  );
};

export default CreditTable;

/* ============================================
   styles/components/CreditTable.css
   ============================================ */

/*
.credit-table-wrapper {
  width: 100%;
  overflow-x: auto;
  background: var(--bg-primary);
  border-radius: var(--border-radius-lg);
  border: 1px solid var(--border-color);
}

.credit-table {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.credit-table-header {
  display: flex;
  background: var(--bg-secondary);
  position: sticky;
  top: 0;
  z-index: 10;
}

.credit-header-cell {
  padding: 16px 20px;
  text-align: left;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border-color);
  white-space: nowrap;
  user-select: none;
}

.credit-table-body {
  display: flex;
  flex-direction: column;
  background: var(--bg-primary);
}

.credit-table-body.loading {
  opacity: 0.5;
}

.credit-row {
  display: flex;
  cursor: pointer;
  transition: background var(--transition-speed) var(--transition-timing);
}

.credit-row:hover {
  background: var(--bg-secondary);
}

.credit-row:not(:last-child) {
  border-bottom: 1px solid var(--border-color);
}

.credit-cell {
  padding: 16px 20px;
  font-size: var(--font-size-md);
  color: var(--text-primary);
}

.credit-cell.text-right {
  text-align: right;
}

.credit-number {
  font-weight: var(--font-weight-medium);
  color: var(--primary);
}

.customer-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.customer-name {
  font-weight: var(--font-weight-medium);
}

.customer-id {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.amount-due {
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
}

.due-date {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.overdue-badge {
  display: inline-block;
  padding: 2px 6px;
  background: var(--danger-light);
  border-radius: 4px;
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  color: var(--danger);
}

.progress-container {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.progress-bar {
  flex: 1;
  height: 6px;
  background: var(--bg-tertiary);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 3px;
  transition: width var(--transition-speed) var(--transition-timing);
}

.progress-text {
  min-width: 45px;
  text-align: right;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--text-secondary);
}

.credit-table-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-2xl);
  color: var(--text-tertiary);
  text-align: center;
}

.credit-table-empty p {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-medium);
}

@media (max-width: 1024px) {
  .credit-table-wrapper {
    border-radius: var(--border-radius);
  }
  
  .credit-header-cell,
  .credit-cell {
    padding: 12px 16px;
    font-size: var(--font-size-sm);
  }
}
*/