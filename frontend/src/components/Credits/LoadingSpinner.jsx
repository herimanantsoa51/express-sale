// components/LoadingSpinner.jsx
import React from 'react';
import '../../styles/components/LoadingSpinner.css';

const LoadingSpinner = ({ size = 'medium', fullScreen = false }) => {
  return (
    <div className={`loading-spinner-container ${fullScreen ? 'fullscreen' : ''}`}>
      <div className={`loading-spinner ${size}`}>
        <div className="spinner-circle"></div>
      </div>
    </div>
  );
};

export default LoadingSpinner;

/* ============================================
   styles/components/LoadingSpinner.css
   ============================================ */

/*
.loading-spinner-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
}

.loading-spinner-container.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: var(--backdrop-blur);
  z-index: var(--z-modal);
}

.loading-spinner {
  position: relative;
  display: inline-block;
}

.loading-spinner.small {
  width: 20px;
  height: 20px;
}

.loading-spinner.medium {
  width: 32px;
  height: 32px;
}

.loading-spinner.large {
  width: 48px;
  height: 48px;
}

.spinner-circle {
  width: 100%;
  height: 100%;
  border: 2px solid var(--border-color);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
*/