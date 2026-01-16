// ============================================
// src/components/dashboard/ExpensesBreakdown.jsx
// ============================================

import React from 'react';
import { Package,Megaphone,FileText,Truck,Zap,ShoppingCart,Wrench,Users } from 'lucide-react';
import { formatCurrency } from '../../utils/formatters';
import '../../styles/ExpensesBreakdown.css';

const ExpensesBreakdown = ({ expenses }) => {
  const iconMap = {
    package: Package,
    megaphone: Megaphone,
    'file-text': FileText,
    truck: Truck,
    zap: Zap,
    'shopping-cart': ShoppingCart,
    wrench: Wrench,
    users: Users
  };

  if (!expenses || expenses.length === 0) {
    return (
      <div className="expenses">
        <h3 className="expenses__title">Dépenses</h3>
        <div className="expenses__empty">Aucune dépense enregistrée</div>
      </div>
    );
  }

  return (
    <div className="expenses">
      <h3 className="expenses__title">Dépenses</h3>
      <div className="expenses__list">
        {expenses.map((expense, index) => {
          const Icon = iconMap[expense.icon] || Package;
          return (
            <div key={index} className="expenses__item">
              <div className="expenses__item-left">
                <div className="expenses__item-icon">
                  <Icon size={18} />
                </div>
                <span className="expenses__item-category">{expense.category}</span>
              </div>
              <span className="expenses__item-amount">{formatCurrency(expense.total)}</span>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default ExpensesBreakdown;