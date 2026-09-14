import React from 'react';
import '../../styles/AppleCard.css';

const AppleCard = ({
  children,
  className = '',
  hoverEffect = 'none',
  padding = 'medium',
  elevation = 'none',
  onClick,
  ...props
}) => {
  const baseClass = 'apple-card';
  const hoverClass = hoverEffect !== 'none' ? `apple-card-hover-${hoverEffect}` : '';
  const paddingClass = `apple-card-padding-${padding}`;
  const elevationClass = elevation !== 'none' ? `apple-card-elevation-${elevation}` : '';
  
  const cardClasses = [
    baseClass,
    hoverClass,
    paddingClass,
    elevationClass,
    className
  ].filter(Boolean).join(' ');

  return (
    <div
      className={cardClasses}
      onClick={onClick}
      role={onClick ? 'button' : undefined}
      tabIndex={onClick ? 0 : undefined}
      {...props}
    >
      {children}
    </div>
  );
};

export default AppleCard;