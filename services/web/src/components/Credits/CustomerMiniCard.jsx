// components/CustomerMiniCard.jsx
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { User, Phone, ChevronRight } from 'lucide-react';
import '../../styles/components/CustomerMiniCard.css';

const CustomerMiniCard = ({ customer }) => {
  const navigate = useNavigate();

  if (!customer) {
    return (
      <div className="customer-mini-card">
        <p>Aucune information client</p>
      </div>
    );
  }

  const handleClick = () => {
    navigate(`/clients/${customer.id}`);
  };

  return (
    <div className="customer-mini-card clickable" onClick={handleClick}>
      <div className="customer-avatar">
        <User size={24} />
      </div>
      
      <div className="customer-info-mini">
        <h3 className="customer-name-mini">{customer.name}</h3>
        
        {customer.phone && (
          <div className="customer-contact">
            <Phone size={14} />
            <span>{customer.phone}</span>
          </div>
        )}
        
        {customer.customer_number && (
          <div className="customer-number">
            <span className="number-label">N°</span>
            <span className="number-value">{customer.customer_number}</span>
          </div>
        )}
      </div>

      <ChevronRight className="customer-chevron" size={18} />
    </div>
  );
};

export default CustomerMiniCard;

/* ============================================
   styles/components/CustomerMiniCard.css
   ============================================ */

/*
.customer-mini-card {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-md);
  background: var(--bg-secondary);
  border-radius: var(--border-radius-lg);
  transition: all var(--transition-speed) var(--transition-timing);
}

.customer-mini-card.clickable {
  cursor: pointer;
}

.customer-mini-card.clickable:hover {
  background: var(--bg-hover);
}

.customer-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, var(--primary), var(--info));
  border-radius: 50%;
  color: white;
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-bold);
  flex-shrink: 0;
}

.customer-info-mini {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  flex-grow: 1;
}

.customer-name-mini {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.customer-contact {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.customer-number {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.customer-chevron {
  color: var(--text-secondary);
  flex-shrink: 0;
}
*/