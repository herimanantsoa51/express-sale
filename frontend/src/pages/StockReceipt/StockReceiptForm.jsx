import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  ArrowLeft, ArrowRight, Package, Truck, CreditCard, 
  Calendar, AlertCircle, Save, Check, ShoppingCart,
  ClipboardList, CheckCircle2, Loader2
} from 'lucide-react';
import '../../styles/StockReceiptFormStyle.css';

// Services
import productService from '../../services/productService';
import supplierService from '../../services/supplierService';
import freightForwarderService from '../../services/freightForwarderService';
import currencyService from '../../services/currencyService';
import accountService from '../../services/accountService';
import stockReceiptService from '../../services/stockReceiptService';
import stockPaymentService from '../../services/stockPaymentService';

// Components
import ProductSearch from './stockReceiptFormComponents/ProductSearch';
import VariantSelector from './stockReceiptFormComponents/VariantSelector';
import ItemsTable from './stockReceiptFormComponents/ItemsTable';
import SupplierSelector from './stockReceiptFormComponents/SupplierSelector';
import FreightForwarderSelector from './stockReceiptFormComponents/FreightForwarderSelector';
import PaymentSection from './stockReceiptFormComponents/PaymentSection';
import CurrencySelector from './stockReceiptFormComponents/CurrencySelector';

const STEPS = [
  { 
    id: 1, 
    title: 'Devise & Produits', 
    icon: Package,
    description: 'Choisissez la devise et sélectionnez les produits à commander'
  },
  { 
    id: 2, 
    title: 'Fournisseur & Transport', 
    icon: Truck,
    description: 'Choisissez le fournisseur et le mode de livraison'
  },
  { 
    id: 3, 
    title: 'Paiement', 
    icon: CreditCard,
    description: 'Configurez les options de paiement'
  }
];

const StockReceiptForm = () => {
  const navigate = useNavigate();
  
  const [currentStep, setCurrentStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);
  const [currencyRates, setCurrencyRates] = useState(null);
  const [categories, setCategories] = useState([]);
  const [coordinates, setCoordinates] = useState([]);
  const [selectedCurrency, setSelectedCurrency] = useState('EUR');
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [variants, setVariants] = useState([]);
  const [items, setItems] = useState([]);
  const [suppliers, setSuppliers] = useState([]);
  const [selectedSupplier, setSelectedSupplier] = useState(null);
  const [freightForwarders, setFreightForwarders] = useState([]);
  const [selectedFreightForwarder, setSelectedFreightForwarder] = useState(null);
  const [freightCost, setFreightCost] = useState('');
  const [expectedDeliveryDate, setExpectedDeliveryDate] = useState('');
  const [notes, setNotes] = useState('');
  const [accounts, setAccounts] = useState([]);
  const [paySupplierNow, setPaySupplierNow] = useState(true);
  const [supplierPayment, setSupplierPayment] = useState({
    accountId: '',
    transaction_date: '',
    referenceNumber: '',
    notes: ''
  });
  const [freightPayment, setFreightPayment] = useState({
    accountId: '',
    transaction_date: '',
    referenceNumber: '',
    notes: ''
  });
  const STORAGE_KEY = 'stockReceiptFormDraft';

// Fonction pour sauvegarder l'état
const saveFormState = (state) => {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      ...state,
      timestamp: new Date().toISOString()
    }));
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
  }
};

// Fonction pour charger l'état
const loadFormState = () => {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
      const data = JSON.parse(saved);
      // Vérifier si les données ont moins de 24h
      const timestamp = new Date(data.timestamp);
      const now = new Date();
      const hoursDiff = (now - timestamp) / (1000 * 60 * 60);
      
      if (hoursDiff < 24) {
        return data;
      } else {
        localStorage.removeItem(STORAGE_KEY);
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement:', error);
  }
  return null;
};
  useEffect(() => {
    loadInitialData();
    
    // Charger les données sauvegardées
    const savedState = loadFormState();
    if (savedState) {
      setCurrentStep(savedState.currentStep || 1);
      setSelectedCurrency(savedState.selectedCurrency || 'EUR');
      setItems(savedState.items || []);
      setSelectedSupplier(savedState.selectedSupplier || null);
      setSelectedFreightForwarder(savedState.selectedFreightForwarder || null);
      setFreightCost(savedState.freightCost || '');
      setExpectedDeliveryDate(savedState.expectedDeliveryDate || '');
      setNotes(savedState.notes || '');
      setSupplierPayment(savedState.supplierPayment || {
        accountId: '',
        transaction_date: '',
        referenceNumber: '',
        notes: ''
      });
    }
  }, []);
  // Sauvegarder automatiquement les changements
useEffect(() => {
  if (!initialLoading && !success) {
    const stateToSave = {
      currentStep,
      selectedCurrency,
      items,
      selectedSupplier,
      selectedFreightForwarder,
      freightCost,
      expectedDeliveryDate,
      notes,
      supplierPayment
    };
    saveFormState(stateToSave);
  }
}, [currentStep, selectedCurrency, items, selectedSupplier, selectedFreightForwarder, 
    freightCost, expectedDeliveryDate, notes, supplierPayment, initialLoading, success]);
  const loadInitialData = async () => {
    try {
      setInitialLoading(true);
      const [ratesRes, suppliersRes, freightRes, accountsRes, categoriesRes] = await Promise.all([
        currencyService.getCurrent(),
        supplierService.getSuppliers({ status: 'active' }),
        freightForwarderService.getFreightForwarders(),
        accountService.getAll({ is_active: true }),
        productService.getCategories()
      ]);
      
      setCurrencyRates(ratesRes.data || ratesRes);
      setSuppliers(suppliersRes.data || suppliersRes);
      setFreightForwarders(freightRes.data || freightRes);
      setAccounts(accountsRes.data || accountsRes);
      
      // Gérer la réponse des catégories (peut être directement un tableau ou un objet avec data)
      const cats = categoriesRes.data || categoriesRes;
      setCategories(Array.isArray(cats) ? cats : []);
      
      const allCoordinates = [];
      const coordIds = new Set();
      
      const suppliers = suppliersRes.data || suppliersRes;
      const forwarders = freightRes.data || freightRes;
      
      [...suppliers, ...forwarders].forEach(item => {
        if (item.coordinate && !coordIds.has(item.coordinate.id)) {
          coordIds.add(item.coordinate.id);
          allCoordinates.push(item.coordinate);
        }
      });
      
      setCoordinates(allCoordinates);
    } catch (err) {
      setError('Erreur lors du chargement des données');
      console.error('❌ Error loading initial data:', err);
    } finally {
      setInitialLoading(false);
    }
  };
  
  const getSelectedCurrencyRate = () => {
    if (!currencyRates?.rates_in_ariary) return 1;
    
    const currencyMap = {
      'EUR': 'euro',
      'USD': 'dollar',
      'CNY': 'yuan',
      'MAD': 'dirham',
      'THB': 'baht'
    };
    
    const rateKey = currencyMap[selectedCurrency];
    return currencyRates.rates_in_ariary[rateKey]?.value || 1;
  };
  
  const handleProductSelect = async (product) => {
    setSelectedProduct(product);
    setError(null);
    
    try {
      console.log('🔍 Fetching variants for product:', product.id);
      const variantsData = await productService.getVariants(product.id);
      console.log('📦 Variants received:', variantsData);
      
      // L'API peut retourner soit un tableau directement, soit un objet avec data
      const variants = Array.isArray(variantsData) ? variantsData : (variantsData?.data || []);
      
      if (variants.length === 0) {
        console.warn('⚠️ No variants found for product:', product.name);
        setError(`Le produit "${product.name}" n'a pas de variantes configurées. Veuillez d'abord créer des variantes pour ce produit.`);
        setSelectedProduct(null);
        setTimeout(() => setError(null), 5000);
        return;
      }
      
      setVariants(variants);
    } catch (err) {
      console.error('❌ Error loading variants:', err);
      setError('Erreur lors du chargement des variantes');
    }
  };
  
  const handleCloseVariantSelector = () => {
    setSelectedProduct(null);
    setVariants([]);
  };
  
  const handleVariantSelect = (variant) => {
    const exists = items.find(item => item.variantId === variant.id);
    if (exists) {
      setError('Cette variante est déjà ajoutée');
      setTimeout(() => setError(null), 3000);
      return;
    }
    
    const newItem = {
      variantId: variant.id,
      productId: selectedProduct.id,
      productName: selectedProduct.name,
      productImage: selectedProduct.image_url || variant.image_path,
      variantSku: variant.sku,
      variantAttributes: variant.attribute_values || [],
      quantity: 1,
      priceInCurrency: '',
      priceInAriary: 0,
      subtotal: 0
    };
    
    setItems([...items, newItem]);
  };
  
  const updateItem = (index, field, value) => {
    const newItems = [...items];
    newItems[index][field] = value;
    
    if (field === 'priceInCurrency') {
      const price = parseFloat(newItems[index].priceInCurrency) || 0;
      const rate = getSelectedCurrencyRate();
      const productId = newItems[index].productId;
      
      newItems.forEach((item, itemIndex) => {
        if (item.productId === productId && itemIndex !== index) {
          newItems[itemIndex].priceInCurrency = value;
          const itemPrice = parseFloat(value) || 0;
          newItems[itemIndex].priceInAriary = itemPrice * rate;
          const qty = parseInt(newItems[itemIndex].quantity) || 0;
          newItems[itemIndex].subtotal = qty * newItems[itemIndex].priceInAriary;
        }
      });
      
      newItems[index].priceInAriary = price * rate;
    }
    
    if (field === 'quantity' || field === 'priceInCurrency') {
      const qty = parseInt(newItems[index].quantity) || 0;
      newItems[index].subtotal = qty * newItems[index].priceInAriary;
    }
    
    setItems(newItems);
  };
  
  const handleCurrencyChange = (newCurrency) => {
    setSelectedCurrency(newCurrency);
    
    const currencyMap = {
      'EUR': 'euro',
      'USD': 'dollar',
      'CNY': 'yuan',
      'MAD': 'dirham',
      'THB': 'baht'
    };
    
    const rateKey = currencyMap[newCurrency];
    const newRate = currencyRates?.rates_in_ariary?.[rateKey]?.value || 1;
    
    const updatedItems = items.map(item => {
      const price = parseFloat(item.priceInCurrency) || 0;
      const priceInAriary = price * newRate;
      const qty = parseInt(item.quantity) || 0;
      
      return {
        ...item,
        priceInAriary,
        subtotal: qty * priceInAriary
      };
    });
    
    setItems(updatedItems);
  };
  
  const removeItem = (index) => {
    setItems(items.filter((_, i) => i !== index));
  };
  
  const calculateTotalInCurrency = () => {
    return items.reduce((sum, item) => {
      const price = parseFloat(item.priceInCurrency) || 0;
      const qty = parseInt(item.quantity) || 0;
      return sum + (price * qty);
    }, 0);
  };
  
  const calculateTotalInAriary = () => {
    return items.reduce((sum, item) => sum + item.subtotal, 0);
  };
  
  const calculateFreightCostAriary = () => {
    if (!freightCost) return 0;
    const rate = getSelectedCurrencyRate();
    return parseFloat(freightCost) * rate;
  };
  
  const validateStep = (step) => {
    setError(null);
    
    switch (step) {
      case 1:
        if (items.length === 0) {
          setError('Veuillez ajouter au moins un article');
          return false;
        }
        for (const item of items) {
          if (!item.quantity || item.quantity <= 0) {
            setError('Toutes les quantités doivent être supérieures à 0');
            return false;
          }
          if (!item.priceInCurrency || parseFloat(item.priceInCurrency) <= 0) {
            setError('Tous les prix doivent être supérieurs à 0');
            return false;
          }
        }
        return true;
        
      case 2:
        if (!selectedSupplier) {
          setError('Veuillez sélectionner un fournisseur');
          return false;
        }
        if (!expectedDeliveryDate) {
          setError('Veuillez sélectionner une date de livraison prévue');
          return false;
        }
        return true;
        
        case 3:
          if (!supplierPayment.accountId) {
            setError('Veuillez sélectionner un compte pour le paiement fournisseur');
            return false;
          }
          return true;
        
      default:
        return true;
    }
  };
  // Ajouter cette fonction avant le return, avec les autres fonctions helper

const hasInsufficientFunds = () => {
  if (!supplierPayment.accountId) return false;
  
  const selectedAccount = accounts.find(acc => acc.id === parseInt(supplierPayment.accountId));
  if (!selectedAccount) return false;
  
  const supplierTotal = calculateTotalInAriary();
  const balanceAfter = selectedAccount.current_balance - supplierTotal;
  
  return balanceAfter < 0;
};
  const goToNextStep = () => {
    if (validateStep(currentStep)) {
      setCurrentStep(prev => Math.min(prev + 1, STEPS.length));
    }
  };
  
  const goToPreviousStep = () => {
    setError(null);
    setCurrentStep(prev => Math.max(prev - 1, 1));
  };
  
  const goToStep = (step) => {
    if (step <= currentStep) {
      setError(null);
      setCurrentStep(step);
    }
  };
  
  const handleSubmit = async () => {
    setError(null);
    
    if (!validateStep(3)) {
      return;
    }
    
    try {
      setLoading(true);
      
      const receiptData = {
        supplier_id: selectedSupplier.id,
        freight_forwarder_id: selectedFreightForwarder?.id || null,
        expected_delivery_date: expectedDeliveryDate || null,
        notes: notes || '',
        items: items.map(item => ({
          variant_id: item.variantId,
          quantity_ordered: item.quantity,
          quantity_received: 0,
          unit_cost_ariary: item.priceInAriary,
          notes: null
        }))
      };
      
      const receiptResponse = await stockReceiptService.create(receiptData);
      const receiptId = receiptResponse.data.id;
      
      if (paySupplierNow) {
        const supplierPaymentData = {
          stock_receipt_id: receiptId,
          account_id: parseInt(supplierPayment.accountId),
          reference_number: supplierPayment.referenceNumber || null,
          notes: supplierPayment.notes || null
        };
        
        if (supplierPayment.transaction_date) {
          supplierPaymentData.transaction_date = supplierPayment.transaction_date;
        }
        
        await stockPaymentService.paySupplier(supplierPaymentData);
      }
      
      // Effacer les données sauvegardées après succès
      localStorage.removeItem(STORAGE_KEY);
      
      setSuccess(true);
      setTimeout(() => {
        navigate(`reapprovisionnements/${receiptId}`);
      }, 2000);
      
    } catch (err) {
      setError(err.response?.data?.message || 'Erreur lors de la création du bon de commande');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };
  
  const getCurrencySymbol = () => {
    const symbols = { 'EUR': '€', 'USD': '$', 'CNY': '¥', 'MAD': 'MAD', 'THB': '฿' };
    return symbols[selectedCurrency] || selectedCurrency;
  };
  
  if (initialLoading) {
    return (
      <div className="srf-page">
        <div className="srf-loading-container">
          <div className="srf-loading-spinner srf-loading-lg"></div>
          <p className="srf-loading-text">Chargement des données...</p>
        </div>
      </div>
    );
  }
  
  if (success) {
    return (
      <div className="srf-page">
        <div className="srf-success-container">
          <div className="srf-success-icon">
            <CheckCircle2 size={64} />
          </div>
          <h2 className="srf-success-title">Bon de commande créé avec succès !</h2>
          <p className="srf-success-message">Vous allez être redirigé vers la liste des réapprovisionnements...</p>
        </div>
      </div>
    );
  }
  
  return (
    <div className="srf-page">
      <div className="srf-header">
        <button 
          className="srf-btn-back" 
          onClick={() => navigate(-1)}
          disabled={loading}
        >
          <ArrowLeft size={16} />
          Retour
        </button>
        
        <div className="srf-header-content">
          <h1 className="srf-title">
            <ShoppingCart className="srf-title-icon" size={32} />
            Nouveau Réapprovisionnement
          </h1>
          <p className="srf-subtitle">
            Créez un bon de commande pour réapprovisionner votre stock en quelques étapes simples
          </p>
        </div>
      </div>
      
      <div className="srf-stepper-container">
        <div className="srf-stepper">
          {STEPS.map((step, index) => {
            const StepIcon = step.icon;
            const isActive = currentStep === step.id;
            const isCompleted = currentStep > step.id;
            const isClickable = step.id <= currentStep;
            
            return (
              <React.Fragment key={step.id}>
                <div 
                  className={`srf-step ${isActive ? 'srf-step-active' : ''} ${isCompleted ? 'srf-step-completed' : ''} ${isClickable ? 'srf-step-clickable' : ''}`}
                  onClick={() => isClickable && goToStep(step.id)}
                >
                  <div className="srf-step-indicator">
                    {isCompleted ? (
                      <Check size={20} />
                    ) : (
                      <StepIcon size={20} />
                    )}
                  </div>
                  <div className="srf-step-content">
                    <span className="srf-step-title">{step.title}</span>
                    <span className="srf-step-description">{step.description}</span>
                  </div>
                </div>
                {index < STEPS.length - 1 && (
                  <div className={`srf-step-connector ${isCompleted ? 'srf-connector-completed' : ''}`} />
                )}
              </React.Fragment>
            );
          })}
        </div>
      </div>
      
      {error && (
        <div className="srf-error-banner">
          <AlertCircle size={20} />
          <span>{error}</span>
          <button 
            type="button" 
            className="srf-error-close"
            onClick={() => setError(null)}
          >
            ×
          </button>
        </div>
      )}
      
      <div className="srf-content-container">
        {currentStep === 1 && (
          <div className="srf-panel" key="step-1">
            <div className="srf-panel-header">
              <div className="srf-panel-icon">
                <Package size={24} />
              </div>
              <div className="srf-panel-info">
                <h2>Devise et Sélection des Produits</h2>
                <p>Choisissez d'abord la devise de la commande, puis sélectionnez les produits et variantes à commander.</p>
                <p className="srf-required-info"><span className="srf-required-asterisk">*</span> Champs obligatoires</p>
              </div>
            </div>
            
            <CurrencySelector
              currencyRates={currencyRates}
              selectedCurrency={selectedCurrency}
              onCurrencyChange={handleCurrencyChange}
            />
            
            {selectedProduct && (
              <div className="srf-variant-overlay">
                <div className="srf-variant-modal">
                  <VariantSelector
                    product={selectedProduct}
                    variants={variants}
                    selectedVariantIds={items.map(i => i.variantId)}
                    onVariantSelect={handleVariantSelect}
                    onClose={handleCloseVariantSelector}
                  />
                </div>
              </div>
            )}
            
            <ProductSearch 
              onProductSelect={handleProductSelect}
              disabled={loading}
              categories={categories}
            />
            
            {items.length > 0 && (
              <div className="srf-items-section">
                <div className="srf-items-header">
                  <h3>
                    <ClipboardList size={20} />
                    Articles sélectionnés ({items.length})
                  </h3>
                </div>
                <ItemsTable
                  items={items}
                  currency={selectedCurrency}
                  currencySymbol={getCurrencySymbol()}
                  onUpdateItem={updateItem}
                  onRemoveItem={removeItem}
                />
              </div>
            )}
          </div>
        )}
        
        {currentStep === 2 && (
          <div className="srf-panel" key="step-2">
            <div className="srf-panel-header">
              <div className="srf-panel-icon">
                <Truck size={24} />
              </div>
              <div className="srf-panel-info">
                <h2>Fournisseur et Transport</h2>
                <p>Sélectionnez le fournisseur de votre commande et configurez les options de livraison.</p>
                <p className="srf-required-info"><span className="srf-required-asterisk">*</span> Champs obligatoires</p>
              </div>
            </div>
            
            <div className="srf-form-grid">
              <div className="srf-form-group">
                <label className="srf-form-label">
                  <Calendar size={16} />
                  Date de livraison prévue
                  <span className="srf-required-asterisk">*</span>
                </label>
                <input
                  type="date"
                  className="srf-form-input"
                  value={expectedDeliveryDate}
                  onChange={(e) => setExpectedDeliveryDate(e.target.value)}
                  min={new Date().toISOString().split('T')[0]}
                  required
                />
              </div>
              
              <div className="srf-form-group srf-form-full-width">
                <label className="srf-form-label">Notes de commande</label>
                <textarea
                  className="srf-form-textarea"
                  placeholder="Instructions spéciales, remarques..."
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  rows={3}
                />
              </div>
            </div>
            
            <SupplierSelector
              suppliers={suppliers}
              selectedSupplierId={selectedSupplier?.id}
              onSupplierSelect={setSelectedSupplier}
              coordinates={coordinates}
            />
            
            <FreightForwarderSelector
              freightForwarders={freightForwarders}
              selectedFreightForwarderId={selectedFreightForwarder?.id}
              onFreightForwarderSelect={setSelectedFreightForwarder}
              freightCost={freightCost}
              onFreightCostChange={setFreightCost}
              currency={selectedCurrency}
              currencySymbol={getCurrencySymbol()}
              currencyRate={getSelectedCurrencyRate()}
              coordinates={coordinates}
            />
          </div>
        )}
        
        {currentStep === 3 && (
          <div className="srf-panel" key="step-3">
            <div className="srf-panel-header">
              <div className="srf-panel-icon">
                <CreditCard size={24} />
              </div>
              <div className="srf-panel-info">
                <h2>Options de Paiement</h2>
                <p>Choisissez de payer maintenant ou plus tard. Tous les paiements sont effectués en Ariary.</p>
                <p className="srf-required-info"><span className="srf-required-asterisk">*</span> Champs obligatoires</p>
              </div>
            </div>
            
            <div className="srf-order-summary">
              <h3>Récapitulatif de la commande</h3>
              <div className="srf-summary-grid">
                <div className="srf-summary-item">
                  <span className="srf-summary-label">Articles</span>
                  <span className="srf-summary-value">{items.length} produit{items.length > 1 ? 's' : ''}</span>
                </div>
                <div className="srf-summary-item">
                  <span className="srf-summary-label">Total articles ({getCurrencySymbol()})</span>
                  <span className="srf-summary-value">
                    {new Intl.NumberFormat('fr-FR').format(calculateTotalInCurrency())} {getCurrencySymbol()}
                  </span>
                </div>
                <div className="srf-summary-item">
                  <span className="srf-summary-label">Total articles (Ariary)</span>
                  <span className="srf-summary-value srf-summary-highlight">
                    {new Intl.NumberFormat('fr-FR').format(calculateTotalInAriary())} Ar
                  </span>
                </div>
                {selectedFreightForwarder && freightCost && (
                  <>
                    <div className="srf-summary-item">
                      <span className="srf-summary-label">Transport ({getCurrencySymbol()})</span>
                      <span className="srf-summary-value">
                        {new Intl.NumberFormat('fr-FR').format(parseFloat(freightCost) || 0)} {getCurrencySymbol()}
                      </span>
                    </div>
                    <div className="srf-summary-item">
                      <span className="srf-summary-label">Transport (Ariary)</span>
                      <span className="srf-summary-value">
                        {new Intl.NumberFormat('fr-FR').format(calculateFreightCostAriary())} Ar
                      </span>
                    </div>
                  </>
                )}
                <div className="srf-summary-item srf-summary-total">
                  <span className="srf-summary-label">Total général (Ariary)</span>
                  <span className="srf-summary-value">
                    {new Intl.NumberFormat('fr-FR').format(
                      calculateTotalInAriary() + (selectedFreightForwarder && freightCost ? calculateFreightCostAriary() : 0)
                    )} Ar
                  </span>
                </div>
              </div>
            </div>
            
            <PaymentSection
              accounts={accounts}
              supplierPayment={supplierPayment}
              setSupplierPayment={setSupplierPayment}
              supplierTotal={calculateTotalInAriary()}
            />
          </div>
        )}
      </div>
      
      <div className="srf-actions">
        <div className="srf-actions-left">
          {currentStep > 1 && (
            <button
              type="button"
              className="srf-btn-secondary"
              onClick={goToPreviousStep}
              disabled={loading}
            >
              <ArrowLeft size={16} />
              Précédent
            </button>
          )}
        </div>
        
        <div className="srf-actions-right">
          {currentStep < STEPS.length ? (
            <button
              type="button"
              className="srf-btn-primary"
              onClick={goToNextStep}
              disabled={loading}
            >
              Suivant
              <ArrowRight size={16} />
            </button>
          ) : (
            <button
            type="button"
            className="srf-btn-primary srf-btn-submit"
            onClick={handleSubmit}
            disabled={loading || hasInsufficientFunds()}
          >
            {loading ? (
              <>
                <Loader2 size={16} className="srf-spinning" />
                Création en cours...
              </>
            ) : hasInsufficientFunds() ? (
              <>
                <AlertCircle size={16} />
                Fonds insuffisants
              </>
            ) : (
              <>
                <Save size={16} />
                Créer le Réapprovisionnement
              </>
            )}
          </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default StockReceiptForm;