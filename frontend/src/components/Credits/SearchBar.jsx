// components/SearchBar.jsx
import React, { useState, useEffect } from 'react';
import '../../styles/components/SearchBar.css';

const SearchBar = ({ value, onChange, placeholder = 'Rechercher...' }) => {
  const [localValue, setLocalValue] = useState(value || '');

  useEffect(() => {
    const timer = setTimeout(() => {
      onChange(localValue);
    }, 300);

    return () => clearTimeout(timer);
  }, [localValue, onChange]);

  return (
    <div className="search-bar">
      <svg className="search-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
        <path 
          d="M7 12.5C10.0376 12.5 12.5 10.0376 12.5 7C12.5 3.96243 10.0376 1.5 7 1.5C3.96243 1.5 1.5 3.96243 1.5 7C1.5 10.0376 3.96243 12.5 7 12.5Z" 
          stroke="currentColor" 
          strokeWidth="1.5" 
          strokeLinecap="round" 
          strokeLinejoin="round"
        />
        <path 
          d="M14.5 14.5L11 11" 
          stroke="currentColor" 
          strokeWidth="1.5" 
          strokeLinecap="round" 
          strokeLinejoin="round"
        />
      </svg>
      <input
        type="text"
        className="search-input"
        placeholder={placeholder}
        value={localValue}
        onChange={(e) => setLocalValue(e.target.value)}
      />
      {localValue && (
        <button 
          className="search-clear"
          onClick={() => setLocalValue('')}
          aria-label="Effacer"
        >
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path 
              d="M10.5 3.5L3.5 10.5M3.5 3.5L10.5 10.5" 
              stroke="currentColor" 
              strokeWidth="1.5" 
              strokeLinecap="round"
            />
          </svg>
        </button>
      )}
    </div>
  );
};

export default SearchBar;

/* ============================================
   styles/components/SearchBar.css
   ============================================ */

/*
.search-bar {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 14px;
  color: var(--text-secondary);
  pointer-events: none;
  transition: color var(--transition-speed) var(--transition-timing);
}

.search-input {
  width: 100%;
  padding: 10px 40px 10px 40px;
  background: var(--bg-secondary);
  border: 1px solid transparent;
  border-radius: var(--border-radius);
  color: var(--text-primary);
  font-size: var(--font-size-md);
  transition: all var(--transition-speed) var(--transition-timing);
}

.search-input::placeholder {
  color: var(--text-tertiary);
}

.search-input:focus {
  outline: none;
  background: var(--bg-primary);
  border-color: var(--primary);
  box-shadow: 0 0 0 4px var(--primary-light);
}

.search-input:focus + .search-clear,
.search-bar:hover .search-icon {
  color: var(--text-primary);
}

.search-clear {
  position: absolute;
  right: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  padding: 0;
  background: transparent;
  border: none;
  border-radius: 50%;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all var(--transition-speed) var(--transition-timing);
}

.search-clear:hover {
  color: var(--text-primary);
  background: var(--bg-tertiary);
}
*/