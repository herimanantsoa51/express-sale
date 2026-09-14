/**
 * Formate un nombre en devise
 */
/**
 * Formate un nombre en devise
 */
export const formatCurrency = (amount, currency = 'Ar') => {
  if (amount === null || amount === undefined) return '0,00 Ar';
  
  const number = parseFloat(amount);
  if (isNaN(number)) return '0,00 Ar';
  
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(number) + ' ' + currency;
};
/**
 * Parse un pourcentage string en nombre
 */
export const parsePercentage = (str) => {
  if (!str || str === '0.0%') return 0;
  return parseFloat(str.replace(/[+%]/g, ''));
};

/**
 * Formate une date
 */
export const formatDate = (dateString, format = 'short') => {
  if (!dateString) return '-';
  
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '-';
  
  if (format === 'long') {
    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  }
  
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date);
};

/**
 * Formate un numéro de téléphone
 */
export const formatPhone = (phone) => {
  if (!phone) return '-';
  return phone.replace(/(\d{3})(\d{2})(\d{3})(\d{2})/, '$1 $2 $3 $4');
};

/**
 * Calcule le pourcentage
 */
export const calculatePercentage = (part, total) => {
  if (!total || total === 0) return 0;
  return Math.round((part / total) * 100);
};

/**
 * Calcule le nombre de jours jusqu'à une date
 */
export const daysUntil = (dateString) => {
  if (!dateString) return 0;
  
  const targetDate = new Date(dateString);
  const today = new Date();
  
  // Reset hours to compare only dates
  today.setHours(0, 0, 0, 0);
  targetDate.setHours(0, 0, 0, 0);
  
  const diffTime = targetDate - today;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  return diffDays;
};

/**
 * Vérifie si une date est expirée
 * @param {string|Date} date - Date à vérifier
 * @returns {boolean} True si expirée
 */
export const isExpired = (date) => {
  if (!date) return false;
  return new Date(date) < new Date();
};

/**
 * Formate un nombre avec des espaces comme séparateur de milliers
 * @param {number} num - Nombre à formater
 * @returns {string} Nombre formaté
 */
export const formatNumber = (num) => {
  if (num === null || num === undefined) return '-';
  return new Intl.NumberFormat('fr-FR').format(num);
};

/**
 * Tronque un texte
 */
export const truncate = (text, maxLength = 50) => {
  if (!text) return '';
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
};

/**
 * Télécharge un blob comme fichier
 * @param {Blob} blob - Blob à télécharger
 * @param {string} filename - Nom du fichier
 */
export const downloadBlob = (blob, filename) => {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
};

/**
 * Débounce une fonction
 * @param {Function} func - Fonction à débouncer
 * @param {number} wait - Délai en ms
 * @returns {Function} Fonction débouncée
 */
export const debounce = (func, wait = 300) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

/**
 * Génère une couleur à partir d'un texte (pour les avatars)
 */
export const stringToColor = (str) => {
  if (!str) return '#0071e3';
  
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash);
  }
  
  const hue = hash % 360;
  return `hsl(${hue}, 65%, 50%)`;
};

/**
 * Valide un montant de paiement
 * @param {number|string} amount - Montant à valider
 * @param {number} maxAmount - Montant maximum autorisé
 * @returns {string|null} Message d'erreur ou null si valide
 */
export const validateAmount = (amount, maxAmount) => {
  if (!amount && amount !== 0) {
    return 'Le montant est requis';
  }
  
  const numAmount = parseFloat(amount);
  
  if (isNaN(numAmount)) {
    return 'Montant invalide';
  }
  
  if (numAmount <= 0) {
    return 'Le montant doit être supérieur à 0';
  }
  
  if (maxAmount !== undefined && numAmount > maxAmount) {
    return `Le montant ne peut pas dépasser ${formatCurrency(maxAmount)}`;
  }
  
  return null;
};
