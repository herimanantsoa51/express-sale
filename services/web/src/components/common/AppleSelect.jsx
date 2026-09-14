import React, { useState, useRef, useEffect } from 'react';
import { ChevronDown, Check } from 'lucide-react';
import '../../styles/AppleSelect.css';

const AppleSelect = ({
  value,
  onChange,
  options = [],
  placeholder = 'Sélectionner...',
  label,
  error,
  helperText,
  disabled = false,
  size = 'medium',
  fullWidth = false,
  className = '',
  ...props
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [isFocused, setIsFocused] = useState(false);
  const selectRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (selectRef.current && !selectRef.current.contains(event.target)) {
        setIsOpen(false);
        setIsFocused(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleSelect = (optionValue) => {
    if (optionValue !== value) {
      onChange(optionValue);
    }
    setIsOpen(false);
    setIsFocused(false);
  };

  const getSelectedLabel = () => {
    if (value === '') return placeholder;
    const selectedOption = options.find(opt => opt.value === value);
    return selectedOption ? selectedOption.label : placeholder;
  };

  const baseClass = 'apple-select-wrapper';
  const sizeClass = `apple-select-${size}`;
  const errorClass = error ? 'apple-select-error' : '';
  const disabledClass = disabled ? 'apple-select-disabled' : '';
  const focusedClass = isFocused ? 'apple-select-focused' : '';
  const fullWidthClass = fullWidth ? 'apple-select-fullwidth' : '';
  const openClass = isOpen ? 'apple-select-open' : '';

  const wrapperClasses = [
    baseClass,
    sizeClass,
    errorClass,
    disabledClass,
    focusedClass,
    fullWidthClass,
    openClass,
    className
  ].filter(Boolean).join(' ');

  const triggerClasses = [
    'apple-select-trigger',
    disabled ? 'apple-select-trigger-disabled' : ''
  ].filter(Boolean).join(' ');

  return (
    <div 
      className={wrapperClasses}
      ref={selectRef}
      {...props}
    >
      {label && (
        <label className="apple-select-label">
          {label}
        </label>
      )}

      <div className="apple-select-container">
        <button
          type="button"
          className={triggerClasses}
          onClick={() => !disabled && setIsOpen(!isOpen)}
          onFocus={() => !disabled && setIsFocused(true)}
          onBlur={() => setIsFocused(false)}
          disabled={disabled}
          aria-haspopup="listbox"
          aria-expanded={isOpen}
        >
          <span className="apple-select-value">
            {getSelectedLabel()}
          </span>
          <ChevronDown className="apple-select-arrow" size={20} />
        </button>

        {isOpen && (
          <div className="apple-select-dropdown">
            <div className="apple-select-dropdown-inner">
              {options.map((option) => {
                const isSelected = option.value === value;
                const optionClasses = [
                  'apple-select-option',
                  isSelected ? 'apple-select-option-selected' : '',
                  option.disabled ? 'apple-select-option-disabled' : ''
                ].filter(Boolean).join(' ');

                return (
                  <div
                    key={option.value}
                    className={optionClasses}
                    onClick={() => !option.disabled && handleSelect(option.value)}
                    role="option"
                    aria-selected={isSelected}
                    aria-disabled={option.disabled}
                  >
                    {option.icon && (
                      <span className="apple-select-option-icon">
                        {option.icon}
                      </span>
                    )}
                    
                    <span className="apple-select-option-label">
                      {option.label}
                    </span>
                    
                    {isSelected && (
                      <Check className="apple-select-option-check" size={18} />
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        )}
      </div>

      {(error || helperText) && (
        <div className="apple-select-message">
          {error ? error : helperText}
        </div>
      )}
    </div>
  );
};

export default AppleSelect;