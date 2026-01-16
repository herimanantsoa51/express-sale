import React from 'react';
import '../../styles/AppleBadge.css';

const AppleBadge = ({
  label,
  variant = 'neutral',
  size = 'medium',
  icon,
  className = '',
  ...props
}) => {
  const baseClass = 'apple-badge';
  const variantClass = `apple-badge-${variant}`;
  const sizeClass = `apple-badge-${size}`;
  
  const badgeClasses = [
    baseClass,
    variantClass,
    sizeClass,
    className
  ].filter(Boolean).join(' ');

  return (
    <span className={badgeClasses} {...props}>
      {icon && <span className="apple-badge-icon">{icon}</span>}
      <span className="apple-badge-label">{label}</span>
    </span>
  );
};

export default AppleBadge;