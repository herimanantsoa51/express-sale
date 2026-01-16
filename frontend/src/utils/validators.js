/**
 * Valide un montant
 * @param {number} amount - Montant à valider
 * @param {number} max - Montant maximum
 * @returns {object} Résultat de validation
 */
export const validateAmount = (amount, max) => {
  if (!amount || amount <= 0) {
    return { valid: false, message: 'Le montant doit être supérieur à 0' };
  }
  
  if (max && amount > max) {
    return { valid: false, message: `Le montant ne peut pas dépasser ${max}` };
  }
  
  return { valid: true, message: '' };
};

/**
 * Valide un email
 */
export const validateEmail = (email) => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(String(email).toLowerCase());
};

/**
 * Valide un numéro de téléphone
 */
export const validatePhone = (phone) => {
  const re = /^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/;
  return re.test(phone);
};

/**
 * Valide une date
 */
export const validateDate = (dateString) => {
  const date = new Date(dateString);
  return !isNaN(date.getTime());
};

/**
 * Valide une plage de dates
 */
export const validateDateRange = (startDate, endDate) => {
  if (!startDate || !endDate) return true;
  
  const start = new Date(startDate);
  const end = new Date(endDate);
  
  return start <= end;
};

/**
 * Valide un formulaire de paiement
 */
export const validatePaymentForm = (data) => {
  const errors = {};

  // Validation du compte
  if (!data.account_id || data.account_id === '') {
    errors.account_id = 'Veuillez sélectionner un compte';
  }

  // Validation du montant
  if (!data.amount || data.amount <= 0) {
    errors.amount = 'Le montant doit être supérieur à 0';
  } else if (data.max_amount && data.amount > data.max_amount) {
    errors.amount = `Le montant ne peut pas dépasser ${data.max_amount}`;
  }

  return {
    valid: Object.keys(errors).length === 0,
    errors
  };
};
