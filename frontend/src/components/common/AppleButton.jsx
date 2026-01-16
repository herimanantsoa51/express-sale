import React from 'react';
import '../../styles/AppleButton.css';

const AppleButton = ({
  children,
  variant = 'primary',
  size = 'medium',
  icon,
  iconPosition = 'left',
  onClick,
  disabled = false,
  loading = false,
  fullWidth = false,
  className = '',
  iconOnly = false,
  isActive = false,
  type = 'button',
  ...props
}) => {
  const baseClass = 'apple-button';
  const variantClass = `apple-button-${variant}`;
  const sizeClass = `apple-button-${size}`;
  const stateClass = disabled ? 'apple-button-disabled' : '';
  const activeClass = isActive ? 'apple-button-active' : '';
  const widthClass = fullWidth ? 'apple-button-fullwidth' : '';
  const loadingClass = loading ? 'apple-button-loading' : '';

  const buttonClasses = [
    baseClass,
    variantClass,
    sizeClass,
    stateClass,
    activeClass,
    widthClass,
    loadingClass,
    className
  ].filter(Boolean).join(' ');

  const renderIcon = () => {
    if (!icon) return null;
    
    if (typeof icon === 'string') {
      return <span className="apple-button-icon-text">{icon}</span>;
    }
    
    return React.cloneElement(icon, {
      className: 'apple-button-icon',
      size: size === 'small' ? 16 : size === 'large' ? 24 : 20
    });
  };

  return (
    <button
      type={type}
      className={buttonClasses}
      onClick={onClick}
      disabled={disabled || loading}
      data-variant={variant}
      data-size={size}
      {...props}
    >
      {loading && (
        <span className="apple-button-spinner">
          <svg width="16" height="16" viewBox="0 0 16 16">
            <circle cx="8" cy="8" r="7" stroke="currentColor" strokeWidth="2" fill="none" />
            <path d="M15,8 A7,7 0 0,1 8,15" stroke="currentColor" strokeWidth="2" fill="none" />
          </svg>
        </span>
      )}
      
      {!loading && icon && iconPosition === 'left' && renderIcon()}
      
      {!iconOnly && (
        <span className="apple-button-content">
          {children}
        </span>
      )}
      
      {!loading && icon && iconPosition === 'right' && renderIcon()}
      
      {iconOnly && !loading && renderIcon()}
    </button>
  );
};

export default AppleButton;