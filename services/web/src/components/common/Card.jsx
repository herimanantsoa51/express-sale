// ============================================
// components/common/Card.jsx
// ============================================

import '../../styles/Card.css';

const Card = ({ 
  children, 
  title, 
  subtitle,
  actions,
  hoverable = false,
  className = '',
  ...props 
}) => {
  const cardClassName = [
    'card',
    hoverable ? 'card-hoverable' : '',
    className,
  ].filter(Boolean).join(' ');

  return (
    <div className={cardClassName} {...props}>
      {(title || subtitle || actions) && (
        <div className="card-header">
          <div className="card-header-text">
            {title && <h3 className="card-title">{title}</h3>}
            {subtitle && <p className="card-subtitle">{subtitle}</p>}
          </div>
          {actions && <div className="card-actions">{actions}</div>}
        </div>
      )}
      <div className="card-body">{children}</div>
    </div>
  );
};

export default Card;

