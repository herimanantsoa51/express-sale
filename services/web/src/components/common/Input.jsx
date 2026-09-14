// ============================================
// components/common/Input.jsx
// ============================================

import '../../styles/Input.css';

const Input = ({ 
  label, 
  error, 
  type = 'text',
  fullWidth = false,
  ...props 
}) => {
  const className = [
    'input',
    error ? 'input-error' : '',
    fullWidth ? 'input-full' : '',
  ].filter(Boolean).join(' ');

  return (
    <div className="input-group">
      {label && <label className="input-label">{label}</label>}
      <input type={type} className={className} {...props} />
      {error && <span className="input-error-text">{error}</span>}
    </div>
  );
};

export default Input;
