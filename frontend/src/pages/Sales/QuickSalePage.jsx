/* ============================================
   QUICK SALE PAGE - Point de vente Apple-style REFONTE
   ============================================ */

   import React, { useState, useEffect, useCallback, useMemo } from 'react';
   import { useNavigate } from 'react-router-dom';
   import { 
     Search, ShoppingCart, Plus, Minus, Trash2, 
     User, Package, Check, ChevronDown, Filter, X, 
     Banknote, Smartphone, MapPin, AlertCircle, AlertTriangle,
     Eye, EyeOff
   } from 'lucide-react';
   import { toast } from 'react-toastify';
   import productService from '../../services/productService';
   import saleService from '../../services/saleService';
   import ClientQuickCreateForm from '../../components/ClientQuickCreateForm';
   import './QuickSalePage.css';
import customerService from '../../services/customerService';
import { useAuth } from '../../context/AuthContext';
   
   const QuickSalePage = () => {
    const navigate = useNavigate();
    const {isAdmin,user}=useAuth()

    // Constantes
    const STORAGE_KEY = 'quick_sale_draft';
    const MAX_STORAGE_TIME = 2 * 60 * 60 * 1000; // 2 heures
    
    // Fonction pour charger depuis localStorage
    const loadFromStorage = useCallback(() => {
      try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (!saved) return null;
        
        const data = JSON.parse(saved);
        
        // Vérifier si les données ne sont pas trop vieilles
        const savedAt = new Date(data.savedAt);
        const now = new Date();
        const age = now - savedAt;
        
        if (age > MAX_STORAGE_TIME) {
          console.log('🗑️ Données trop vieilles, suppression...');
          localStorage.removeItem(STORAGE_KEY);
          return null;
        }
        
        console.log('📂 Données restaurées depuis le stockage');
        return data;
      } catch (error) {
        console.error('❌ Erreur chargement localStorage:', error);
        localStorage.removeItem(STORAGE_KEY);
        return null;
      }
    }, []);
    
    // Mode de vente
    const [saleMode, setSaleMode] = useState(() => {
      const saved = loadFromStorage();
      return saved?.saleMode || 'immediate';
    });
    
    // États produits & filtres
    const [products, setProducts] = useState([]);
    const [productsLoading, setProductsLoading] = useState(false);
    const [productSearch, setProductSearch] = useState(() => {
      const saved = loadFromStorage();
      return saved?.productSearch || '';
    });
    const [categories, setCategories] = useState([]);
    const [selectedCategory, setSelectedCategory] = useState(() => {
      const saved = loadFromStorage();
      return saved?.selectedCategory || null;
    });
    const [selectedSubcategory, setSelectedSubcategory] = useState(() => {
      const saved = loadFromStorage();
      return saved?.selectedSubcategory || null;
    });
    const [selectedProduct, setSelectedProduct] = useState(null);
    
    // États client
    const [customerSearch, setCustomerSearch] = useState(() => {
      const saved = loadFromStorage();
      return saved?.customerSearch || '';
    });
    const [customerSuggestions, setCustomerSuggestions] = useState([]);
    const [selectedCustomer, setSelectedCustomer] = useState(() => {
      const saved = loadFromStorage();
      return saved?.selectedCustomer || null;
    });
    const [showCustomerDropdown, setShowCustomerDropdown] = useState(false);
    const [showClientModal, setShowClientModal] = useState(false);
    const [showCustomerSearch, setShowCustomerSearch] = useState(true);
    
    // États panier
    const [cart, setCart] = useState(() => {
      const saved = loadFromStorage();
      return saved?.cart || [];
    });
    const [discount, setDiscount] = useState(() => {
      const saved = loadFromStorage();
      return saved?.discount || { amount: 0, reason: '' };
    });
    const [showDiscountReason, setShowDiscountReason] = useState(false);
    
    // États paiement
    const [paymentMethod, setPaymentMethod] = useState(() => {
      const saved = loadFromStorage();
      return saved?.paymentMethod || 'cash';
    });
    const [accounts, setAccounts] = useState([]);
    const [selectedAccount, setSelectedAccount] = useState(() => {
      const saved = loadFromStorage();
      return saved?.selectedAccount || null;
    });
    
    // États crédit
    const [installments, setInstallments] = useState(() => {
      const saved = loadFromStorage();
      return saved?.installments || [];
    });
    const [dueDate, setDueDate] = useState(() => {
      const saved = loadFromStorage();
      return saved?.dueDate || '';
    });
    
    // États réservation
    const [expiryDate, setExpiryDate] = useState(() => {
      const saved = loadFromStorage();
      return saved?.expiryDate || '';
    });
    const [depositAmount, setDepositAmount] = useState(() => {
      const saved = loadFromStorage();
      return saved?.depositAmount || 0;
    });
    
    // États UI
    const [submitting, setSubmitting] = useState(false);
    const [submitError, setSubmitError] = useState(null);
    const [successMessage, setSuccessMessage] = useState(null);
    const [showConfirmModal, setShowConfirmModal] = useState(false);
    const [lastSaved, setLastSaved] = useState(null);
    // QuickSalePage.js - Ajoutez ces états
    const [attributes, setAttributes] = useState([]);
    const [selectedAttributeType, setSelectedAttributeType] = useState(() => {
      const saved = loadFromStorage();
      return saved?.selectedAttributeType || null;
    });
    const [selectedAttributeValue, setSelectedAttributeValue] = useState(() => {
      const saved = loadFromStorage();
      return saved?.selectedAttributeValue || null;
    });
    
    // Fonction pour sauvegarder l'état dans localStorage
    const saveToStorage = useCallback((data) => {
      try {
        const saveData = {
          ...data,
          savedAt: new Date().toISOString(),
          version: '1.0'
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(saveData));
        setLastSaved(new Date().toISOString());
        console.log('✅ Données sauvegardées');
      } catch (error) {
        console.error('❌ Erreur sauvegarde localStorage:', error);
      }
    }, []);
    
    // Fonction pour effacer le localStorage
    const clearStorage = useCallback(() => {
      localStorage.removeItem(STORAGE_KEY);
      console.log('🧹 Stockage nettoyé');
    }, []);
    
    // Sauvegarde automatique quand les données importantes changent
    useEffect(() => {
      const saveData = {
        cart,
        selectedCustomer,
        customerSearch,
        saleMode,
        discount,
        selectedAccount,
        paymentMethod,
        installments,
        dueDate,
        expiryDate,
        depositAmount,
        productSearch,
        selectedCategory,
        selectedSubcategory,
        selectedAttributeType,
        selectedAttributeValue,
      };
      
      // Délai pour éviter de sauvegarder trop souvent
      const saveTimer = setTimeout(() => {
        saveToStorage(saveData);
      }, 1000); // Sauvegarde 1 seconde après le dernier changement
      
      return () => clearTimeout(saveTimer);
    }, [
      cart, selectedCustomer, customerSearch, saleMode, discount,
      selectedAccount, paymentMethod, installments, dueDate,
      expiryDate, depositAmount, productSearch, selectedCategory, selectedSubcategory
    ]);
    useEffect(() => {
      if (!isAdmin() && (saleMode === 'credit' || saleMode === 'reservation')) {
        toast.warning('Seuls les administrateurs peuvent accéder à ce mode de vente');
        setSaleMode('immediate');
      }
    }, [saleMode, isAdmin]);
    // Restaurer depuis localStorage au chargement
    useEffect(() => {
      const saved = loadFromStorage();
      
      if (saved && saved.cart && saved.cart.length > 0) {
        // Vous pouvez ajouter une notification toast optionnelle
        toast.info(`Vente en cours restaurée (${saved.cart.length} articles)`);
        
        // Restaurer le timestamp de la dernière sauvegarde
        if (saved.savedAt) {
          setLastSaved(saved.savedAt);
        }
      }
    }, []);
     // Cacher la recherche client en mode vente rapide
     useEffect(() => {
       if (saleMode === 'immediate') {
         setShowCustomerSearch(false);
         setSelectedCustomer(null);
         setCustomerSearch('');
       } else {
         setShowCustomerSearch(true);
       }
     }, [saleMode]);
   
     // Debounce pour recherche client
     useEffect(() => {
       const timer = setTimeout(() => {
         if (customerSearch.length >= 2) {
           searchCustomers(customerSearch);
         } else {
           setCustomerSuggestions([]);
         }
       }, 300);
   
       return () => clearTimeout(timer);
     }, [customerSearch]);
   
     // Debounce pour recherche produit
     useEffect(() => {
       const timer = setTimeout(() => {
         loadProducts();
       }, 300);
   
       return () => clearTimeout(timer);
     }, [productSearch, selectedCategory, selectedSubcategory,
      selectedAttributeType,      // ✅ AJOUTÉ
      selectedAttributeValue       
     ]);
   
     // Charger les comptes quand le payment method change
     useEffect(() => {
       loadAccounts();
     }, [paymentMethod]);
   
     // Charger produits et catégories au montage
     useEffect(() => {
       loadProducts();
       loadCategories();
       loadAccounts();
       loadAttributes();
     }, []);
     const loadAttributes = async () => {
      try {
        console.log('🚀 Chargement des attributs...');
        const response = await productService.getAttributesForSale();
        console.log('✅ Réponse API attributs:', response);
        console.log('📊 Données attributs:', response.data);
        
        // Vérifiez la structure de la réponse
        if (response && response.data) {
          setAttributes(response.data);
          console.log(`📋 ${response.data.length} attributs chargés`);
        } else {
          console.error('❌ Structure de réponse incorrecte:', response);
          setAttributes([]);
        }
      } catch (error) {
        console.error('❌ Erreur chargement attributs:', error);
        console.error('📞 URL appelée:', error.config?.url);
        toast.error('Erreur lors du chargement des attributs');
      }
    };
    const loadProducts = async () => {
      try {
        setProductsLoading(true);
        // Dans votre console navigateur
      console.log('Type ID:', selectedAttributeType); // Doit être un nombre
      console.log('Valeur:', selectedAttributeValue); // Doit être une string
        const params = new URLSearchParams();
        params.append('per_page', 20);
        
        if (productSearch) params.append('search', productSearch);
        if (selectedCategory) params.append('category_id', selectedCategory);
        if (selectedSubcategory) params.append('subcategory_id', selectedSubcategory);
        
        // ✅ CORRECTION : Assurez-vous que la structure est correcte
        if (selectedAttributeType && selectedAttributeValue) {
          // La clé DOIT être l'ID du type d'attribut (nombre)
          // La valeur DOIT être la valeur textuelle de l'attribut
          const attributesFilter = {
            [selectedAttributeType.toString()]: selectedAttributeValue
          };
          
          console.log('🎨 Filtre attributs envoyé:', attributesFilter);
          console.log('📤 JSON stringifié:', JSON.stringify(attributesFilter));
          
          params.append('attributes', JSON.stringify(attributesFilter));
        }
        
        console.log('📡 Chargement produits avec params:', params.toString());
        console.log('🔍 Params décodés:', {
          search: productSearch,
          category: selectedCategory,
          subcategory: selectedSubcategory,
          attributeType: selectedAttributeType,
          attributeValue: selectedAttributeValue
        });
        
        const response = await productService.getForSale(params.toString());
        console.log('📦 Réponse complète:', response);
        
        let productsData = [];
        
        if (Array.isArray(response.data)) {
          productsData = response.data;
        } else if (response.data && Array.isArray(response.data.data)) {
          productsData = response.data.data;
        } else if (response.data && response.data.products) {
          productsData = Array.isArray(response.data.products) 
            ? response.data.products 
            : [];
        }
        
        console.log(`✅ ${productsData.length} produits chargés`);
        setProducts(productsData);
        
      } catch (error) {
        console.error('❌ Erreur chargement produits:', error);
        console.error('📍 Détails erreur:', {
          message: error.message,
          response: error.response?.data,
          status: error.response?.status
        });
        toast.error('Erreur lors du chargement des produits');
        setProducts([]);
      } finally {
        setProductsLoading(false);
      }
    };
   
     const loadCategories = async () => {
       try {
         const response = await productService.getCategoriesForSale();
         setCategories(response.data || []);
       } catch (error) {
         console.error('Erreur chargement catégories:', error);
         toast.error('Erreur lors du chargement des catégories');
       }
     };
   
     const searchCustomers = async (query) => {
       try {
         const response = await saleService.searchCustomers(query);
         setCustomerSuggestions(response.data || []);
         setShowCustomerDropdown(true);
       } catch (error) {
         console.error('Erreur recherche clients:', error);
         toast.error('Erreur lors de la recherche de clients');
       }
     };
   
     const loadAccounts = async () => {
       try {
         const response = await saleService.getCashAccounts();
         const allAccounts = response.data || [];
         
         const filteredAccounts = allAccounts.filter(account => {
           if (paymentMethod === 'cash') {
             return account.type === 'cash';
           } else if (paymentMethod === 'mobile_money') {
             return account.type === 'mobile_money';
           }
           return false;
         });
         
         setAccounts(filteredAccounts);
         if (filteredAccounts.length > 0) {
           setSelectedAccount(filteredAccounts[0].id);
         } else {
           setSelectedAccount(null);
         }
       } catch (error) {
         console.error('Erreur chargement comptes:', error);
         toast.error('Erreur lors du chargement des comptes');
       }
     };
   
     const handleSelectCustomer = useCallback((customer) => {
       setSelectedCustomer(customer);
       setCustomerSearch(customer.name);
       setShowCustomerDropdown(false);
       toast.success(`Client ${customer.name} sélectionné`);
     }, []);
   
     
     const handleAddToCart = useCallback((product, variant, location, quantity) => {
       if (quantity <= 0) return;

        // // Validation supplémentaire
        // if (saleMode !== 'reservation' && location.code !== 'MAGASIN-PRINCIPAL') {
        //   toast.error("Seul le MAGASIN-PRINCIPAL est autorisé pour ce type de vente");
        //   return;
        // }

        // if (saleMode !== 'reservation' && location.quantity <= 0) {
        //   toast.error("Stock épuisé au MAGASIN-PRINCIPAL");
        //   return;
        // }
   
       const cartItem = {
         variant_id: variant.id,
         location_id: location.location_id,
         quantity: quantity,
         product_name: product.name,
         variant_sku: variant.sku,
         location_name: location.location_name,
         location_code: location.location_code,
         unit_price: product.base_price,
         image_url: variant.image_path || product.image_url,
         attributes: variant.attributes,
         max_quantity: location.quantity
       };
   
       setCart(prev => {
         const existingIndex = prev.findIndex(
           item => item.variant_id === variant.id && item.location_id === location.location_id
         );
   
         if (existingIndex >= 0) {
           const updated = [...prev];
           const newQty = updated[existingIndex].quantity + quantity;
           updated[existingIndex].quantity = Math.min(newQty, location.quantity);
           return updated;
         }
   
         return [...prev, cartItem];
       });
       
       setSelectedProduct(null);
       toast.success(`${quantity} ${product.name} ajouté au panier`);
     }, []);
   
     const handleUpdateCartQuantity = useCallback((index, newQuantity) => {
       if (newQuantity <= 0) {
         const itemName = cart[index].product_name;
         setCart(prev => prev.filter((_, i) => i !== index));
         toast.info(`${itemName} retiré du panier`);
       } else {
         setCart(prev => {
           const updated = [...prev];
           const item = updated[index];
           updated[index].quantity = Math.min(newQuantity, item.max_quantity);
           return updated;
         });
       }
     }, [cart]);
   
     const handleRemoveFromCart = useCallback((index) => {
       const itemName = cart[index].product_name;
       setCart(prev => prev.filter((_, i) => i !== index));
       toast.info(`${itemName} retiré du panier`);
     }, [cart]);
   
     const subtotal = useMemo(() => {
       return cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
     }, [cart]);
   
     const total = useMemo(() => {
       return Math.max(0, subtotal - (discount.amount || 0));
     }, [subtotal, discount]);
   
     
   
     const handleSubmitSale = async () => {
      if (cart.length === 0) {
        toast.error('Le panier est vide');
        return;
      }
    
      if (!selectedAccount) {
        toast.error('Veuillez sélectionner un compte');
        return;
      }
    
      // VALIDATION 1: Vérifier que la remise n'est pas supérieure au sous-total
      if (discount.amount > subtotal) {
        toast.error(`La remise (${discount.amount.toLocaleString()} Ar) ne peut pas dépasser le sous-total (${subtotal.toLocaleString()} Ar)`);
        return;
      }
    
      // VALIDATION 2: Vérifier que l'acompte n'est pas supérieur au total (réservation)
      if (saleMode === 'reservation') {
        if (depositAmount > total) {
          toast.error(`L'acompte (${depositAmount.toLocaleString()} Ar) ne peut pas dépasser le total (${total.toLocaleString()} Ar)`);
          return;
        }
        if (!expiryDate) {
          toast.error('Veuillez sélectionner une date limite de retrait');
          return;
        }
      }
    
      // VALIDATION 3: Vérifier les échéances (crédit)
      if (saleMode === 'credit') {
        if (!dueDate) {
          toast.error('Veuillez sélectionner une date limite');
          return;
        }
        
        // Si des échéances sont spécifiées
        if (installments.length > 0) {
          const installmentsTotal = installments.reduce((sum, inst) => 
            sum + parseFloat(inst.amount || 0), 0
          );
          const difference = Math.abs(installmentsTotal - total);
          
          // Tolérance de 1 Ar pour les arrondis
          if (difference > 1) {
            toast.error(`La somme des échéances (${installmentsTotal.toLocaleString()} Ar) doit être égale au total (${total.toLocaleString()} Ar)`);
            return;
          }
          
          // Vérifier que chaque échéance a une date
          const hasEmptyDate = installments.some(inst => !inst.due_date);
          if (hasEmptyDate) {
            toast.error('Toutes les échéances doivent avoir une date');
            return;
          }
        }
      }
   
       try {
         setSubmitting(true);
         setSubmitError(null);
   
         let customer = selectedCustomer;
         
         // Si mode vente rapide et pas de client sélectionné, créer un client anonyme
         if (saleMode === 'immediate' && !customer) {
           try {
            const response = await customerService.create({
              name: "Anonyme",
              is_extra_customer: false
            });
            
            // CORRECTION ICI : Prenez le data de la réponse
            customer = response.data; 
             toast.success('Client anonyme créé');
           } catch (error) {
              console.log(error);
             toast.error('Erreur lors de la création du client anonyme');
             return;
           }
         } else if (!customer) {
           toast.error('Veuillez sélectionner un client');
           return;
         }
   
         const items = cart.map(item => ({
           variant_id: item.variant_id,
           location_id: item.location_id,
           quantity: item.quantity
         }));
   
         const basePayload = {
           customer_id: customer.id,
           account_id: selectedAccount,
           payment_method: paymentMethod,
           discount_amount: discount.amount || 0,
           discount_reason: discount.reason || null,
           items
         };
         let response;
         
         if (saleMode === 'immediate') {
           response = await saleService.createImmediate(basePayload);
           console.log(response);
         } else if (saleMode === 'credit') {
          console.log(installments)
          const installmentsTotal = installments.reduce((sum, inst) => sum + parseFloat(inst.amount || 0), 0);
          if(installments.length > 0) {
            if (Math.abs(installmentsTotal - total) > 0.01) {
              toast.error(`La somme des échéances (${installmentsTotal.toLocaleString()} Ar) doit être égale au total (${total.toLocaleString()} Ar)`);
              return;
            }
          }
   
          const payload = {
            ...basePayload,
            due_date: dueDate,
          };
          
          if (installments.length > 0) {
            payload.installments = installments.map(inst => ({
              due_date: inst.due_date,
              amount: parseFloat(inst.amount),
            }));
          }
   
          response = await saleService.createCredit(payload);
         } else if (saleMode === 'reservation') {
           response = await saleService.createReservation({
             ...basePayload,
             expiry_date: expiryDate,
             deposit_amount: depositAmount || 0
           });
         }
   
         toast.success(response.message);
         setSuccessMessage(response.message);
         setShowConfirmModal(false);
         clearStorage();
         await loadProducts();
         
         setTimeout(() => {
          setSaleMode('immediate');
           setCart([]);
           setSelectedCustomer(null);
           setCustomerSearch('');
           setDiscount({ amount: 0, reason: '' });
           setSuccessMessage(null);
           setInstallments([{ due_date: '', amount: 0 }]);
           setDepositAmount(0);
         }, 2000);
   
       } catch (error) {
         const errorMsg = error.response?.data?.message || 'Erreur lors de la création de la vente';
         toast.error(errorMsg);
         setSubmitError(errorMsg);
       } finally {
         setSubmitting(false);
       }
     };
   
     const handleClientCreated = (newClient) => {
       setSelectedCustomer(newClient);
       setCustomerSearch(newClient.name);
       setShowClientModal(false);
       toast.success(`Client ${newClient.name} créé avec succès`);
     };
     const clearAllFilters = () => {
      setSelectedCategory(null);
      setSelectedSubcategory(null);
      setSelectedAttributeType(null);
      setSelectedAttributeValue(null);
      setProductSearch('');
      toast.success('Tous les filtres ont été effacés');
    };
     const selectedCategoryData = categories.find(cat => cat.id === selectedCategory);
   
     return (
       <div className="quick-sale-page">
         <header className="quick-sale-header">
           <div className="quick-sale-logo">
             <Package size={24} strokeWidth={2} />
             <span>Vente Rapide</span>
           </div>
   
           <div className="sale-mode-switch" role="tablist">
             <button
               role="tab"
               aria-selected={saleMode === 'immediate'}
               className={`mode-tab ${saleMode === 'immediate' ? 'active' : ''}`}
               onClick={() => setSaleMode('immediate')}
             >
               Vente rapide
             </button>
             {isAdmin() && (
              <>
                <button
                  role="tab"
                  aria-selected={saleMode === 'credit'}
                  className={`mode-tab ${saleMode === 'credit' ? 'active' : ''}`}
                  onClick={() => setSaleMode('credit')}
                >
                  Vente à crédit
                </button>
                <button
                  role="tab"
                  aria-selected={saleMode === 'reservation'}
                  className={`mode-tab ${saleMode === 'reservation' ? 'active' : ''}`}
                  onClick={() => setSaleMode('reservation')}
                >
                  Réservation
                </button>
              </>
            )}
             <div className="mode-indicator" data-mode={saleMode} />
           </div>
         </header>
   
         <div className="quick-sale-container">
           <main className="quick-sale-main">
             {/* Section client avec toggle pour vente rapide */}
             {saleMode === 'immediate' ? (
               <section className="customer-section glass-panel">
                 <div className="customer-toggle-header">
                   <button
                     className="btn-toggle-customer"
                     onClick={() => setShowCustomerSearch(!showCustomerSearch)}
                   >
                     {showCustomerSearch ? <EyeOff size={16} /> : <Eye size={16} />}
                     {showCustomerSearch ? 'Cacher' : 'Afficher'} la recherche client
                   </button>
                   {!showCustomerSearch && (
                     <div className="anonymous-customer-info">
                       <span className="anonymous-badge">Vente anonyme</span>
                       <p className="anonymous-hint">Un client anonyme sera automatiquement créé</p>
                     </div>
                   )}
                 </div>
   
                 {showCustomerSearch && (
                   <>
                     <div className="customer-search-wrapper">
                       <User size={18} strokeWidth={2} />
                       <input
                         type="text"
                         placeholder="Rechercher un client..."
                         value={customerSearch}
                         onChange={(e) => setCustomerSearch(e.target.value)}
                         onFocus={() => customerSuggestions.length > 0 && setShowCustomerDropdown(true)}
                         className="customer-search-input"
                       />
                       {selectedCustomer && (
                         <button 
                           className="customer-clear"
                           onClick={() => {
                             setSelectedCustomer(null);
                             setCustomerSearch('');
                           }}
                           aria-label="Effacer"
                         >
                           <X size={16} />
                         </button>
                       )}
                     </div>
   
                     {showCustomerDropdown && customerSuggestions.length > 0 && (
                       <div className="customer-dropdown">
                         {customerSuggestions.map((customer) => (
                           <button
                             key={customer.id}
                             className="customer-suggestion"
                             onClick={() => handleSelectCustomer(customer)}
                           >
                             <div className="customer-info">
                               <span className="customer-name">{customer.name}</span>
                               <span className="customer-code">{customer.customer_number}</span>
                             </div>
                             {customer.loyalty_points > 0 && (
                               <span className="customer-points">
                                 {customer.loyalty_points.toLocaleString()} pts
                               </span>
                             )}
                           </button>
                         ))}
                       </div>
                     )}
   
                     <div className="customer-actions">
                       <button 
                         className="btn-new-customer"
                         onClick={() => setShowClientModal(true)}
                       >
                         <Plus size={16} strokeWidth={2} />
                         Nouveau client
                       </button>
                       {selectedCustomer && (
                         <button 
                           className="btn-view-customer"
                           onClick={() => {
                             sessionStorage.setItem('saleInProgress', JSON.stringify({ cart, customer: selectedCustomer }));
                             navigate(`/clients/${selectedCustomer.id}`);
                           }}
                         >
                           Voir fiche →
                         </button>
                       )}
                     </div>
                   </>
                 )}
               </section>
             ) : (
               <section className="customer-section glass-panel">
                 <div className="customer-search-wrapper">
                   <User size={18} strokeWidth={2} />
                   <input
                     type="text"
                     placeholder="Rechercher un client..."
                     value={customerSearch}
                     onChange={(e) => setCustomerSearch(e.target.value)}
                     onFocus={() => customerSuggestions.length > 0 && setShowCustomerDropdown(true)}
                     className="customer-search-input"
                   />
                   {selectedCustomer && (
                     <button 
                       className="customer-clear"
                       onClick={() => {
                         setSelectedCustomer(null);
                         setCustomerSearch('');
                       }}
                       aria-label="Effacer"
                     >
                       <X size={16} />
                     </button>
                   )}
                 </div>
   
                 {showCustomerDropdown && customerSuggestions.length > 0 && (
                   <div className="customer-dropdown">
                     {customerSuggestions.map((customer) => (
                       <button
                         key={customer.id}
                         className="customer-suggestion"
                         onClick={() => handleSelectCustomer(customer)}
                       >
                         <div className="customer-info">
                           <span className="customer-name">{customer.name}</span>
                           <span className="customer-code">{customer.customer_number}</span>
                         </div>
                         {customer.loyalty_points > 0 && (
                           <span className="customer-points">
                             {customer.loyalty_points.toLocaleString()} pts
                           </span>
                         )}
                       </button>
                     ))}
                   </div>
                 )}
   
                 <div className="customer-actions">
                   <button 
                     className="btn-new-customer"
                     onClick={() => setShowClientModal(true)}
                   >
                     <Plus size={16} strokeWidth={2} />
                     Nouveau client
                   </button>
                   {selectedCustomer && (
                     <button 
                       className="btn-view-customer"
                       onClick={() => {
                         sessionStorage.setItem('saleInProgress', JSON.stringify({ cart, customer: selectedCustomer }));
                         navigate(`/clients/${selectedCustomer.id}`);
                       }}
                     >
                       Voir fiche →
                     </button>
                   )}
                 </div>
               </section>
             )}
   
          <div className="filters-section">
              <div className="product-search-wrapper-compact">
                <Search size={18} strokeWidth={2} />
                <input
                  type="text"
                  placeholder="Rechercher..."
                  value={productSearch}
                  onChange={(e) => setProductSearch(e.target.value)}
                  className="product-search-input-compact"
                />
              </div>

              <div className="category-filters">
                {/* Filtres catégories (existants) */}
                <div className="filter-group-compact">
                  <Filter size={16} />
                  <select
                    value={selectedCategory || ''}
                    onChange={(e) => {
                      setSelectedCategory(e.target.value ? parseInt(e.target.value) : null);
                      setSelectedSubcategory(null);
                    }}
                    className="category-select-compact"
                  >
                    <option value="">Catégories</option>
                    {categories.map(cat => (
                      <option key={cat.id} value={cat.id}>{cat.name}</option>
                    ))}
                  </select>
                </div>

                {selectedCategory && selectedCategoryData?.children?.length > 0 && (
                  <div className="filter-group-compact">
                    <ChevronDown size={16} />
                    <select
                      value={selectedSubcategory || ''}
                      onChange={(e) => setSelectedSubcategory(e.target.value ? parseInt(e.target.value) : null)}
                      className="category-select-compact"
                    >
                      <option value="">Sous-catégories</option>
                      {selectedCategoryData.children.map(subcat => (
                        <option key={subcat.id} value={subcat.id}>{subcat.name}</option>
                      ))}
                    </select>
                  </div>
                )}

                {/* Nouveau filtre pour les attributs */}
                <div className="filter-group-compact">
                  <Filter size={16} />
                  <select
                    value={selectedAttributeType || ''}
                    onChange={(e) => {
                      const attrId = e.target.value ? parseInt(e.target.value) : null;
                      setSelectedAttributeType(attrId);
                      setSelectedAttributeValue(null);
                    }}
                    className="category-select-compact"
                  >
                    <option value="">Attributs</option>
                    {attributes.map(attr => (
                      <option key={attr.id} value={attr.id}>{attr.display_name || attr.name}</option>
                    ))}
                  </select>
                </div>

                {/* Filtre pour les valeurs d'attributs */}
                {selectedAttributeType && (
                  <div className="filter-group-compact">
                    <ChevronDown size={16} />
                    <select
                      value={selectedAttributeValue || ''}
                      onChange={(e) => setSelectedAttributeValue(e.target.value ? e.target.value : null)}
                      className="category-select-compact"
                    >
                      <option value="">Valeurs</option>
                      {(() => {
                        const selectedAttr = attributes.find(attr => attr.id === selectedAttributeType);
                        if (selectedAttr && selectedAttr.values) {
                          return selectedAttr.values.map(value => (
                            <option key={value.id} value={value.value}>
                              {value.value} ({value.product_count || 0})
                            </option>
                          ));
                        }
                        return null;
                      })()}
                    </select>
                  </div>
                )}

                {/* Bouton pour effacer tous les filtres */}
                {(selectedCategory || selectedSubcategory || selectedAttributeType || selectedAttributeValue) && (
                  <button
                    className="btn-clear-filters-compact"
                    onClick={clearAllFilters}
                    title="Effacer tous les filtres"
                  >
                    <X size={14} />
                  </button>
                )}
              </div>

              {/* Affichage des filtres actifs */}
              <div className="active-filters">
                {selectedCategory && (
                  <span className="active-filter">
                    Catégorie: {categories.find(c => c.id === selectedCategory)?.name}
                    <button onClick={() => setSelectedCategory(null)}>
                      <X size={12} />
                    </button>
                  </span>
                )}
                
                {selectedSubcategory && (
                  <span className="active-filter">
                    Sous-catégorie: {selectedCategoryData?.children?.find(s => s.id === selectedSubcategory)?.name}
                    <button onClick={() => setSelectedSubcategory(null)}>
                      <X size={12} />
                    </button>
                  </span>
                )}
                
                {selectedAttributeType && (
                  <span className="active-filter">
                    Attribut: {attributes.find(a => a.id === selectedAttributeType)?.display_name}
                    <button onClick={() => {
                      setSelectedAttributeType(null);
                      setSelectedAttributeValue(null);
                    }}>
                      <X size={12} />
                    </button>
                  </span>
                )}
                
                {selectedAttributeValue && (
                  <span className="active-filter">
                    Valeur: {selectedAttributeValue}
                    <button onClick={() => setSelectedAttributeValue(null)}>
                      <X size={12} />
                    </button>
                  </span>
                )}
              </div>
            </div>
             <div className="products-grid">
               {productsLoading ? (
                 Array.from({ length: 8 }).map((_, i) => (
                   <div key={i} className="product-card skeleton">
                     <div className="skeleton-image" />
                     <div className="skeleton-text" />
                     <div className="skeleton-price" />
                   </div>
                 ))
               ) : products.length === 0 ? (
                 <div className="empty-products">
                   <Package size={48} strokeWidth={1.5} />
                   <p>Aucun produit trouvé</p>
                 </div>
               ) : (
                 products.map((product, index) => (
                   <ProductCard
                     key={product.id}
                     product={product}
                     index={index}
                     onClick={() => setSelectedProduct(product)}
                     saleMode={saleMode}
                   />
                 ))
               )}
             </div>
           </main>
   
           <aside className="cart-sidebar glass-panel">
             <div className="cart-header">
               <ShoppingCart size={22} strokeWidth={2} />
               <h2>Panier</h2>
               <span className="cart-count">{cart.length}</span>
             </div>
   
             <div className="cart-content">
               {cart.length === 0 ? (
                 <div className="empty-cart">
                   <ShoppingCart size={56} strokeWidth={1.5} />
                   <p>Votre panier est vide</p>
                 </div>
               ) : (
                 <div className="cart-items">
                   {cart.map((item, index) => (
                     <CartItem
                       key={`${item.variant_id}-${item.location_id}`}
                       item={item}
                       index={index}
                       onUpdateQuantity={handleUpdateCartQuantity}
                       onRemove={handleRemoveFromCart}
                     />
                   ))}
                 </div>
               )}
             </div>
   
             {cart.length > 0 && (
               <>
                  <div className="cart-discount">
                    <button
                      className="discount-toggle"
                      onClick={() => setShowDiscountReason(!showDiscountReason)}
                    >
                      Remise
                    </button>
                    <input
                      type="number"
                      placeholder="0"
                      value={discount.amount || ''}
                      onChange={(e) => {
                        const value = parseFloat(e.target.value) || 0;
                        // Empêcher la remise d'être supérieure au sous-total
                        setDiscount(prev => ({ 
                          ...prev, 
                          amount: Math.min(value, subtotal) // Maximum = sous-total
                        }));
                      }}
                      className="discount-input"
                      min="0"
                      max={subtotal} // Ajouter l'attribut max
                      step="1"
                    />
                    <span>Ar</span>
                  </div>

                  
                  {discount.amount > subtotal && (
                    <div className="discount-warning">
                      <AlertTriangle size={14} />
                      <span>La remise ne peut pas dépasser {subtotal.toLocaleString()} Ar</span>
                    </div>
                  )}
   
                 {showDiscountReason && (
                   <input
                     type="text"
                     placeholder="Raison de la remise..."
                     value={discount.reason}
                     onChange={(e) => setDiscount(prev => ({ ...prev, reason: e.target.value }))}
                     className="discount-reason-input"
                   />
                 )}
   
                 <div className="cart-totals">
                   <div className="total-row">
                     <span>Sous-total</span>
                     <span>{subtotal.toLocaleString()} Ar</span>
                   </div>
                   {discount.amount > 0 && (
                     <div className="total-row discount-row">
                       <span>Remise</span>
                       <span>- {discount.amount.toLocaleString()} Ar</span>
                     </div>
                   )}
                   <div className="total-row total-final">
                     <span>Total</span>
                     <span>{total.toLocaleString()} Ar</span>
                   </div>
                 </div>
                   
                 {(saleMode !== 'credit' || (saleMode === 'credit' && installments.length === 0)) && (
                   <div className="payment-method">
                     <label>Méthode de paiement</label>
                     <div className="payment-options">
                       <button
                         className={`payment-option ${paymentMethod === 'cash' ? 'active' : ''}`}
                         onClick={() => setPaymentMethod('cash')}
                       >
                         <Banknote size={18} />
                         <span>Espèces</span>
                       </button>
                       <button
                         className={`payment-option ${paymentMethod === 'mobile_money' ? 'active' : ''}`}
                         onClick={() => setPaymentMethod('mobile_money')}
                       >
                         <Smartphone size={18} />
                         <span>Mobile Money</span>
                       </button>
                     </div>
   
                     {accounts.length > 0 ? (
                       <div className="custom-select-wrapper">
                         <select
                           value={selectedAccount || ''}
                           onChange={(e) => setSelectedAccount(parseInt(e.target.value))}
                           className="custom-select"
                         >
                           {accounts.map(account => (
                             <option key={account.id} value={account.id}>
                               {account.name} {account.account_number ? `• ${account.account_number}` : ''}
                             </option>
                           ))}
                         </select>
                         <ChevronDown size={16} className="select-icon" />
                       </div>
                     ) : (
                       <div className="no-account-warning">
                         <AlertCircle size={16} />
                         <span>Aucun compte {paymentMethod === 'cash' ? 'espèces' : 'mobile money'} disponible</span>
                       </div>
                     )}
                   </div>
                 )}
                 
                 
                  {saleMode === 'credit' && (
                    <div className="credit-options">
                      <label>Date limite</label>
                      <input
                        type="date"
                        value={dueDate}
                        onChange={(e) => setDueDate(e.target.value)}
                        className="date-input"
                        min={new Date().toISOString().split('T')[0]}
                        required
                      />

                      <label>Échéances (optionnel)</label>
                      
                      {/* Afficher un message si les échéances ne correspondent pas au total */}
                      {installments.length > 0 && (
                        (() => {
                          const installmentsTotal = installments.reduce((sum, inst) => 
                            sum + parseFloat(inst.amount || 0), 0
                          );
                          const difference = Math.abs(installmentsTotal - total);
                          
                          if (difference > 0.01) {
                            return (
                              <div className="installments-warning">
                                <AlertCircle size={14} />
                                <span>
                                  Somme des échéances : {installmentsTotal.toLocaleString()} Ar
                                  ({difference.toLocaleString()} Ar de différence)
                                </span>
                              </div>
                            );
                          }
                          return (
                            <div className="installments-ok">
                              <Check size={14} />
                              <span>Somme des échéances : {installmentsTotal.toLocaleString()} Ar ✓</span>
                            </div>
                          );
                        })()
                      )}
                      
                      {installments.map((inst, idx) => (
                        <div key={idx} className="installment-row">
                          <input
                            type="date"
                            value={inst.due_date}
                            onChange={(e) => {
                              const updated = [...installments];
                              updated[idx].due_date = e.target.value;
                              setInstallments(updated);
                            }}
                            className="date-input-small"
                            min={new Date().toISOString().split('T')[0]}
                            required
                          />
                          <input
                            type="number"
                            placeholder="Montant"
                            value={inst.amount || ''}
                            onChange={(e) => {
                              const updated = [...installments];
                              updated[idx].amount = parseFloat(e.target.value) || 0;
                              setInstallments(updated);
                            }}
                            className="amount-input-small"
                            min="0"
                            max={total} // Empêcher une échéance > total
                            step="1"
                          />
                          <button
                            className="btn-remove-installment"
                            onClick={() => setInstallments(prev => prev.filter((_, i) => i !== idx))}
                          >
                            <Trash2 size={14} />
                          </button>
                        </div>
                      ))}
                      
                      <button
                        className="btn-add-installment"
                        onClick={() => {
                          // Calculer le montant restant à répartir
                          const currentTotal = installments.reduce((sum, inst) => 
                            sum + parseFloat(inst.amount || 0), 0
                          );
                          const remaining = Math.max(0, total - currentTotal);
                          
                          setInstallments(prev => [...prev, { 
                            due_date: '', 
                            amount: remaining > 0 ? remaining : 0 
                          }]);
                        }}
                      >
                        <Plus size={14} /> Ajouter échéance
                      </button>
                      
                      {/* Bouton pour équilibrer automatiquement */}
                      {installments.length > 1 && (
                        <button
                          className="btn-balance-installments"
                          onClick={() => {
                            const equalAmount = total / installments.length;
                            const balanced = installments.map(inst => ({
                              ...inst,
                              amount: parseFloat(equalAmount.toFixed(2))
                            }));
                            setInstallments(balanced);
                          }}
                        >
                          Répartir équitablement
                        </button>
                      )}
                    </div>
                  )}
   
                  {saleMode === 'reservation' && (
                  <div className="reservation-options">
                    <label>Date limite de retrait</label>
                    <input
                      type="date"
                      value={expiryDate}
                      onChange={(e) => setExpiryDate(e.target.value)}
                      className="date-input"
                      min={new Date().toISOString().split('T')[0]}
                      required
                    />

                    <label>Acompte</label>
                    <div className="deposit-control">
                      <input
                        type="number"
                        placeholder="0"
                        value={depositAmount || ''}
                        onChange={(e) => {
                          const value = parseFloat(e.target.value) || 0;
                          // Limiter l'acompte au maximum du total
                          setDepositAmount(Math.min(value, total));
                        }}
                        className="deposit-input"
                        min="0"
                        max={total}
                        step="1"
                      />
                      <span>Ar</span>
                    </div>
                    
                    {/* Afficher le pourcentage de l'acompte */}
                    {depositAmount > 0 && (
                      <div className="deposit-percentage">
                        <span>
                          {((depositAmount / total) * 100).toFixed(1)}% du total
                          {depositAmount >= total && ' (Paiement complet)'}
                        </span>
                        {depositAmount >= total && (
                          <div className="full-payment-warning">
                            <AlertCircle size={12} />
                            <span>Attention : l'acompte couvre la totalité</span>
                          </div>
                        )}
                      </div>
                    )}
                    
                    {/* Boutons d'acompte prédéfinis */}
                    <div className="deposit-presets">
                      <button
                        className="deposit-preset-btn"
                        onClick={() => setDepositAmount(Math.round(total * 0.1))}
                      >
                        10%
                      </button>
                      <button
                        className="deposit-preset-btn"
                        onClick={() => setDepositAmount(Math.round(total * 0.25))}
                      >
                        25%
                      </button>
                      <button
                        className="deposit-preset-btn"
                        onClick={() => setDepositAmount(Math.round(total * 0.5))}
                      >
                        50%
                      </button>
                      <button
                        className="deposit-preset-btn"
                        onClick={() => setDepositAmount(total)}
                      >
                        100%
                      </button>
                    </div>
                  </div>
                )}
   
                 {submitError && (
                   <div className="submit-error">
                     <AlertCircle size={16} />
                     {submitError}
                   </div>
                 )}
   
                 {successMessage && (
                   <div className="submit-success">
                     <Check size={18} />
                     {successMessage}
                   </div>
                 )}
   
                 <button
                   className={`btn-submit ${saleMode}`}
                   onClick={() => setShowConfirmModal(true)}
                   disabled={submitting || (saleMode !== 'immediate' && !selectedCustomer) || cart.length === 0 || !selectedAccount}
                 >
                   <Check size={18} strokeWidth={2.5} />
                   {saleMode === 'immediate' && 'Valider la vente'}
                   {saleMode === 'credit' && 'Créer le crédit'}
                   {saleMode === 'reservation' && 'Créer la réservation'}
                 </button>
               </>
             )}
           </aside>
         </div>
   
         {showClientModal && (
           <ClientQuickCreateForm
             onSuccess={handleClientCreated}
             onClose={() => setShowClientModal(false)}
           />
         )}
   
         {selectedProduct && (
           <ProductModal
             product={selectedProduct}
             onClose={() => setSelectedProduct(null)}
             onAddToCart={handleAddToCart}
             saleMode={saleMode}
           />
         )}
   
         {showConfirmModal && (
           <ConfirmModal
             saleMode={saleMode}
             total={total}
             customer={saleMode === 'immediate' ? { name: 'Client Anonyme (à créer)' } : selectedCustomer}
             itemCount={cart.length}
             onConfirm={handleSubmitSale}
             onCancel={() => setShowConfirmModal(false)}
             submitting={submitting}
           />
         )}
       </div>
     );
   };
  
   const isAvailableForQuickSale = (product) => {
    // Vérifie si au moins une variante a du stock au MAGASIN-PRINCIPAL
    return product.variants?.some(variant => 
      variant.locations?.some(loc => 
        loc.location_code === 'MAGASIN-PRINCIPAL' && loc.quantity > 0
      )
    );
  };
   // Composant carte produit
   const ProductCard = ({ product, index, onClick,saleMode }) => {
    const available = isAvailableForQuickSale(product);
    const isDisabled = saleMode !== 'reservation' && !available;
     return (
       <div
        className={`product-card ${isDisabled ? 'disabled' : ''}`}
        style={{ animationDelay: `${index * 40}ms` }}
        onClick={ onClick }
       >
          
         <div className="product-image">
           {product.image_url ? (
             <img src={product.image_url} alt={product.name} loading="lazy" />
           ) : (
             <div className="product-placeholder">
               <Package size={36} strokeWidth={1.5} />
             </div>
           )}
         </div>
         
         <div className="product-info">
           <h3 className="product-name">{product.name}</h3>
           <p className="product-price">{product.base_price.toLocaleString()} Ar</p>
           <p className="product-stock">
             {product.total_stock} en stock • {product.variants_count} variant(s)
           </p>
         </div>
       </div>
     );
   };
   const findMainLocation = (locations, mode) => {
    // En mode réservation, on peut choisir n'importe quel emplacement
    if (mode === 'reservation') {
      // Retourne le premier emplacement avec stock, ou null
      return locations.find(loc => loc.quantity > 0) || null;
    }
    
    // Pour vente rapide et crédit : EXCLUSIVEMENT MAGASIN-PRINCIPAL avec stock
    const mainLocation = locations.find(loc => 
      loc.code === 'MAGASIN-PRINCIPAL' && loc.quantity > 0
    );
    
    return mainLocation || null; // Retourne null si pas disponible
  };
  const ProductModal = ({ product, onClose, onAddToCart, saleMode }) => {
    const [selectedVariant, setSelectedVariant] = useState(null);
    const [quantity, setQuantity] = useState(1);
    const [selectedLocation, setSelectedLocation] = useState(null);
    
    useEffect(() => {
      if (product.variants && product.variants.length > 0) {
        const firstVariant = product.variants[0];
        setSelectedVariant(firstVariant);
        
        // Déterminer l'emplacement par défaut selon le mode
        const mainLocation = findMainLocation(firstVariant.locations, saleMode);
        setSelectedLocation(mainLocation);
      }
    }, [product, saleMode]);
  
    // Calculer le stock total par emplacement (toutes variantes confondues)
    const stockByLocation = useMemo(() => {
      const locationMap = {};
      
      product.variants?.forEach(variant => {
        variant.locations?.forEach(location => {
          const key = location.location_id;
          if (!locationMap[key]) {
            locationMap[key] = {
              ...location,
              total_quantity: 0
            };
          }
          locationMap[key].total_quantity += location.quantity;
        });
      });
      
      return Object.values(locationMap);
    }, [product]);
  
    // Quand une variante change, mettre à jour l'emplacement sélectionné
    useEffect(() => {
      if (selectedVariant) {
        const mainLocation = findMainLocation(selectedVariant.locations, saleMode);
        setSelectedLocation(mainLocation);
        setQuantity(1); // Réinitialiser la quantité
      }
    }, [selectedVariant, saleMode]);
  
    const handleAdd = () => {
      if (selectedVariant && selectedLocation) {
        onAddToCart(product, selectedVariant, selectedLocation, quantity);
      }
    };
  
    const maxQuantity = selectedLocation?.quantity || 0;
  
    const canAddToCart = selectedVariant && selectedLocation && quantity > 0 && quantity <= maxQuantity;
  
    return (
      <div className="product-modal-overlay" onClick={onClose}>
        <div className="product-modal" onClick={(e) => e.stopPropagation()}>
          <div className="product-modal-header">
            <h2>{product.name}</h2>
            <button className="modal-close-btn" onClick={onClose}>
              <X size={20} />
            </button>
          </div>
  
          <div className="product-modal-content">
            <div className="modal-price">{product.base_price.toLocaleString()} Ar</div>
  
            {/* SECTION 1: RÉSUMÉ DES STOCKS PAR EMPLACEMENT */}
            <div className="modal-section">
              <h3>
                <MapPin size={16} />
                Stocks par emplacement (total)
              </h3>
              <div className="global-stock-summary">
                {stockByLocation.length === 0 ? (
                  <div className="no-stock-message">
                    <AlertCircle size={16} />
                    <span>Aucun stock disponible</span>
                  </div>
                ) : (
                  <div className="location-grid-summary">
                    {stockByLocation.map(location => (
                      <div 
                        key={location.location_id}
                        className={`location-summary-card ${location.code === 'MAGASIN-PRINCIPAL' ? 'main-location' : ''}`}
                      >
                        <div className="location-summary-header">
                          <MapPin size={14} />
                          <div className="location-summary-info">
                            <span className="location-name">{location.location_name}</span>
                            <span className="location-code">{location.code}</span>
                          </div>
                          {location.code === 'MAGASIN-PRINCIPAL' && (
                            <span className="main-location-badge">Principal</span>
                          )}
                        </div>
                        <div className="location-stock-total">
                          <Package size={14} />
                          <span className="stock-number">{location.total_quantity}</span>
                          <span className="stock-label">unités totales</span>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
  
            {/* SECTION 2: SÉLECTION DE VARIANTE */}
            {product.variants && product.variants.length > 0 && (
              <div className="modal-section">
                <h3>Choisir une variante</h3>
                <div className="variants-grid-modal">
                  {product.variants.map((variant) => {
                    const variantMainLocation = findMainLocation(variant.locations, saleMode);
                    const isAvailable = variantMainLocation !== null;
                    
                    return (
                      <button
                        key={variant.id}
                        className={`variant-card ${selectedVariant?.id === variant.id ? 'active' : ''} ${!isAvailable && saleMode !== 'reservation' ? 'unavailable' : ''}`}
                        onClick={() => setSelectedVariant(variant)}
                        disabled={!isAvailable && saleMode !== 'reservation'}
                      >
                        {variant.image_path && (
                          <img src={variant.image_path} alt="" className="variant-image" />
                        )}
                        <div className="variant-info-modal">
                          <div className="variant-header">
                            <span className="variant-sku-modal">{variant.sku}</span>
                            {!isAvailable && saleMode !== 'reservation' && (
                              <span className="unavailable-badge">
                                <AlertTriangle size={12} />
                                Indisponible
                              </span>
                            )}
                          </div>
                          <div className="variant-attrs-modal">
                            {variant.attributes.map(attr => (
                              <span key={attr.type_id} className="attr-badge">
                                <strong>{attr.type_name}:</strong> {attr.value}
                              </span>
                            ))}
                          </div>
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>
            )}
  
            {/* SECTION 3: STOCKS DE LA VARIANTE SÉLECTIONNÉE */}
            {selectedVariant && selectedVariant.locations && (
              <div className="modal-section">
                <h3>Stocks de cette variante</h3>
                <div className="variant-locations-detail">
                  {selectedVariant.locations.map(location => {
                    const isSelected = selectedLocation?.location_id === location.location_id;
                    const isMainLocation = location.code === 'MAGASIN-PRINCIPAL';
                    const canSelect = saleMode === 'reservation' || isMainLocation;
                    
                    return (
                      <div 
                        key={location.location_id}
                        className={`variant-location-row ${isSelected ? 'selected' : ''} ${!canSelect && saleMode !== 'reservation' ? 'disabled' : ''}`}
                        onClick={() => {
                          if (canSelect || saleMode === 'reservation') {
                            setSelectedLocation(location);
                          }
                        }}
                      >
                        <div className="location-info">
                          <MapPin size={14} />
                          <div>
                            <span className="location-name">{location.location_name}</span>
                            <span className="location-code">{location.code}</span>
                          </div>
                          {isMainLocation && (
                            <span className="main-tag">Principal</span>
                          )}
                        </div>
                        <div className="location-stock">
                          <Package size={14} />
                          <span className={`stock-count ${location.quantity === 0 ? 'out-of-stock' : ''}`}>
                            {location.quantity} unités
                          </span>
                          {saleMode === 'reservation' && (
                            <button 
                              className={`select-location-btn ${isSelected ? 'selected' : ''}`}
                              onClick={(e) => {
                                e.stopPropagation();
                                setSelectedLocation(location);
                              }}
                            >
                              {isSelected ? '✓ Sélectionné' : 'Sélectionner'}
                            </button>
                          )}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}
  
            {/* SECTION 4: QUANTITÉ */}
            {selectedLocation && selectedLocation.quantity > 0 && (
              <div className="modal-section">
                <h3>Quantité</h3>
                <div className="selected-location-info">
                  <div className="selected-location-badge">
                    <MapPin size={14} />
                    <span>
                      <strong>{selectedLocation.location_name}</strong>
                      {selectedLocation.code === 'MAGASIN-PRINCIPAL' && ' (Magasin principal)'}
                    </span>
                    <span className="stock-info">{selectedLocation.quantity} disponibles</span>
                  </div>
                </div>
                <div className="quantity-selector-modal">
                  <button
                    onClick={() => setQuantity(Math.max(1, quantity - 1))}
                    disabled={quantity <= 1}
                  >
                    <Minus size={18} />
                  </button>
                  <input
                    type="number"
                    value={quantity}
                    onChange={(e) => {
                      const val = parseInt(e.target.value) || 1;
                      setQuantity(Math.min(Math.max(1, val), maxQuantity));
                    }}
                    min="1"
                    max={maxQuantity}
                  />
                  <button
                    onClick={() => setQuantity(Math.min(maxQuantity, quantity + 1))}
                    disabled={quantity >= maxQuantity}
                  >
                    <Plus size={18} />
                  </button>
                </div>
              </div>
            )}
  
            {/* MESSAGE D'AVERTISSEMENT */}
            {!selectedLocation || selectedLocation.quantity === 0 ? (
              <div className="modal-warning">
                <AlertTriangle size={16} />
                <span>
                  {saleMode !== 'reservation' 
                    ? "Cette variante n'est pas disponible en vente rapide/crédit (stock épuisé au MAGASIN-PRINCIPAL)"
                    : "Aucun stock disponible pour la réservation"
                  }
                </span>
              </div>
            ) : null}
          </div>
  
          <div className="product-modal-footer">
            <button className="btn-cancel-modal" onClick={onClose}>
              Annuler
            </button>
            <button
              className={`btn-add-modal ${!canAddToCart ? 'disabled' : ''}`}
              onClick={handleAdd}
              disabled={!canAddToCart}
            >
              <ShoppingCart size={18} />
              {saleMode === 'reservation'
                ? `Réserver (${selectedLocation?.location_name || 'Sélectionner'})`
                : `Ajouter au panier (${selectedLocation?.code === 'MAGASIN-PRINCIPAL' ? 'Magasin principal' : selectedLocation?.location_name})`
              }
            </button>
          </div>
        </div>
      </div>
    );
  };
   
   // Composant item panier
   const CartItem = ({ item, index, onUpdateQuantity, onRemove }) => {
     return (
       <div className="cart-item">
         {item.image_url && (
           <img src={item.image_url} alt={item.product_name} className="cart-item-image" />
         )}
         <div className="cart-item-details">
           <h4>{item.product_name}</h4>
           <p className="cart-item-sku">{item.variant_sku}</p>
           {item.attributes && item.attributes.length > 0 && (
             <p className="cart-item-attributes">
               {item.attributes.map(attr => `${attr.type_name}: ${attr.value}`).join(' • ')}
             </p>
           )}
           <p className="cart-item-location">
             <MapPin size={12} />
             {item.location_name}
             {item.location_code === 'MAGASIN-PRINCIPAL' && (
               <span className="main-location-tag"> (Principal)</span>
             )}
           </p>
           <p className="cart-item-price">
             {item.unit_price.toLocaleString()} Ar × {item.quantity} = {(item.unit_price * item.quantity).toLocaleString()} Ar
           </p>
         </div>
         <div className="cart-item-controls">
           <div className="quantity-controls">
             <button
               onClick={() => onUpdateQuantity(index, item.quantity - 1)}
               aria-label="Diminuer"
             >
               <Minus size={12} />
             </button>
             <span>{item.quantity}</span>
             <button
               onClick={() => onUpdateQuantity(index, item.quantity + 1)}
               disabled={item.quantity >= item.max_quantity}
               aria-label="Augmenter"
             >
               <Plus size={12} />
             </button>
           </div>
           <button
             className="btn-remove-item"
             onClick={() => onRemove(index)}
             aria-label="Supprimer"
           >
             <Trash2 size={14} />
           </button>
         </div>
       </div>
     );
   };
   
   // Modal de confirmation
   const ConfirmModal = ({ saleMode, total, customer, itemCount, onConfirm, onCancel, submitting }) => {
     const getModeText = () => {
       switch(saleMode) {
         case 'immediate': return 'cette vente';
         case 'credit': return 'ce crédit';
         case 'reservation': return 'cette réservation';
         default: return 'cette vente';
       }
     };
   
     const getModeColor = () => {
       switch(saleMode) {
         case 'immediate': return 'var(--success)';
         case 'credit': return 'var(--warning)';
         case 'reservation': return 'var(--info)';
         default: return 'var(--success)';
       }
     };
   
     return (
       <div className="confirm-modal-overlay" onClick={onCancel}>
         <div className="confirm-modal" onClick={(e) => e.stopPropagation()}>
           <div className="confirm-modal-icon" style={{ backgroundColor: `${getModeColor()}15`, color: getModeColor() }}>
             <AlertTriangle size={32} strokeWidth={2} />
           </div>
           
           <h2 className="confirm-modal-title">Confirmer {getModeText()}</h2>
           
           <div className="confirm-modal-details">
             <div className="confirm-detail-row">
               <span className="detail-label">Client</span>
               <span className="detail-value">{customer?.name || 'Client Anonyme (à créer)'}</span>
             </div>
             <div className="confirm-detail-row">
               <span className="detail-label">Articles</span>
               <span className="detail-value">{itemCount} produit{itemCount > 1 ? 's' : ''}</span>
             </div>
             <div className="confirm-detail-row total-row">
               <span className="detail-label">Total</span>
               <span className="detail-value">{total.toLocaleString()} Ar</span>
             </div>
           </div>
   
           <p className="confirm-modal-message">
             Voulez-vous vraiment valider {getModeText()} ?
           </p>
   
           <div className="confirm-modal-actions">
             <button className="btn-confirm-cancel" onClick={onCancel} disabled={submitting}>
               Annuler
             </button>
             <button 
               className={`btn-confirm-submit ${saleMode}`} 
               onClick={onConfirm}
               disabled={submitting}
             >
               {submitting ? (
                 <>
                   <div className="spinner" />
                   Traitement...
                 </>
               ) : (
                 <>
                   <Check size={18} strokeWidth={2.5} />
                   Confirmer
                 </>
               )}
             </button>
           </div>
         </div>
       </div>
     );
   };
   
   export default QuickSalePage;