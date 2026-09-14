// Fonctions utilitaires

export const formatCurrency = (amount) => {
    if (amount === null || amount === undefined || isNaN(amount)) {
      return '0 Ar';
    }
    try {
      return new Intl.NumberFormat('fr-MG', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(Number(amount)) + ' Ar';
    } catch (error) {
      console.error('Error formatting currency:', error, amount);
      return `${amount} Ar`;
    }
  };
  
  export const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return '-';
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      });
    } catch (error) {
      console.error('Error formatting date:', error, dateString);
      return '-';
    }
  };
  
  export const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    try {
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return '-';
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch (error) {
      console.error('Error formatting datetime:', error, dateString);
      return '-';
    }
  };
  
  // Fonction safeGet pour accéder aux propriétés imbriquées sans erreur
  export const safeGet = (obj, path, defaultValue = null) => {
    if (!obj) return defaultValue;
    
    const keys = path.split('.');
    let current = obj;
    
    for (const key of keys) {
      if (current === null || current === undefined) {
        return defaultValue;
      }
      current = current[key];
    }
    
    return current !== undefined ? current : defaultValue;
  };
  
  // Validation des données
  export const validateReceiptData = (data) => {
    if (!data) return false;
    
    const requiredFields = ['id', 'receipt_number', 'status'];
    for (const field of requiredFields) {
      if (!data[field]) {
        console.warn(`Missing required field: ${field}`);
        return false;
      }
    }
    
    return true;
  };
  
  // Filtrage des actions disponibles
  export const filterAvailableActions = (status, receipt) => {
    const actions = [];
    
    if (!status) return actions;
    
    switch (status) {
      case 'pending':
        actions.push('ship', 'cancel');
        break;
      case 'sent':
        actions.push('transit', 'cancel');
        break;
      case 'in_transit':
        actions.push('arrive', 'cancel');
        break;
      case 'arrived':
        actions.push('validate');
        break;
      default:
        break;
    }
    
    return actions;
  };