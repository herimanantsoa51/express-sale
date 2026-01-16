/* ============================================
   QUICK SALE PAGE - Point de vente Apple-style REFONTE
   ============================================ */

   import React, { useState, useEffect, useCallback, useMemo } from 'react';
   import { useNavigate } from 'react-router-dom';
   import { 
     Search, ShoppingCart, Plus, Minus, Trash2, 
     User, Package, Check, ChevronDown, Filter, X, 
     Banknote, Smartphone, MapPin, AlertCircle, AlertTriangle
   } from 'lucide-react';
   import productService from '../../services/productService';
   import saleService from '../../services/saleService';
   import ClientQuickCreateForm from '../../components/ClientQuickCreateForm';
   import './QuickSalePage.css';
   
   const QuickSalePage = () => {
     const navigate = useNavigate();
     
     // Mode de vente
     const [saleMode, setSaleMode] = useState('immediate');
     
     // États produits & filtres
     const [products, setProducts] = useState([]);
     const [productsLoading, setProductsLoading] = useState(false);
     const [productSearch, setProductSearch] = useState('');
     const [categories, setCategories] = useState([]);
     const [selectedCategory, setSelectedCategory] = useState(null);
     const [selectedSubcategory, setSelectedSubcategory] = useState(null);
     const [selectedProduct, setSelectedProduct] = useState(null);
     
     // États client
     const [customerSearch, setCustomerSearch] = useState('');
     const [customerSuggestions, setCustomerSuggestions] = useState([]);
     const [selectedCustomer, setSelectedCustomer] = useState(null);
     const [showCustomerDropdown, setShowCustomerDropdown] = useState(false);
     const [showClientModal, setShowClientModal] = useState(false);
     
     // États panier
     const [cart, setCart] = useState([]);
     const [discount, setDiscount] = useState({ amount: 0, reason: '' });
     const [showDiscountReason, setShowDiscountReason] = useState(false);
     
     // États paiement
     const [paymentMethod, setPaymentMethod] = useState('cash');
     const [accounts, setAccounts] = useState([]);
     const [selectedAccount, setSelectedAccount] = useState(null);
     
     // États crédit
     const [installments, setInstallments] = useState([]);
     const [dueDate, setDueDate] = useState('');
     
     // États réservation
     const [expiryDate, setExpiryDate] = useState('');
     const [depositAmount, setDepositAmount] = useState(0);
     
     // États UI
     const [submitting, setSubmitting] = useState(false);
     const [submitError, setSubmitError] = useState(null);
     const [successMessage, setSuccessMessage] = useState(null);
     const [showConfirmModal, setShowConfirmModal] = useState(false);
   
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
     }, [productSearch, selectedCategory, selectedSubcategory]);
   
     // Charger les comptes quand le payment method change
     useEffect(() => {
       loadAccounts();
     }, [paymentMethod]);
   
     // Charger produits et catégories au montage
     useEffect(() => {
       loadProducts();
       loadCategories();
       loadAccounts();
     }, []);
   
     const loadProducts = async () => {
       try {
         setProductsLoading(true);
         const params = {
           per_page: 50,
           ...(productSearch && { search: productSearch }),
           ...(selectedCategory && { category_id: selectedCategory }),
           ...(selectedSubcategory && { subcategory_id: selectedSubcategory })
         };
         const response = await productService.getForSale(params);
         setProducts(response.data || []);
       } catch (error) {
         console.error('Erreur chargement produits:', error);
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
       }
     };
   
     const searchCustomers = async (query) => {
       try {
         const response = await saleService.searchCustomers(query);
         setCustomerSuggestions(response.data || []);
         setShowCustomerDropdown(true);
       } catch (error) {
         console.error('Erreur recherche clients:', error);
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
       }
     };
   
     const handleSelectCustomer = useCallback((customer) => {
       setSelectedCustomer(customer);
       setCustomerSearch(customer.name);
       setShowCustomerDropdown(false);
     }, []);
   
     const handleAddToCart = useCallback((product, variant, location, quantity) => {
       if (quantity <= 0) return;
   
       const cartItem = {
         variant_id: variant.id,
         location_id: location.location_id,
         quantity: quantity,
         product_name: product.name,
         variant_sku: variant.sku,
         location_name: location.location_name,
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
     }, []);
   
     const handleUpdateCartQuantity = useCallback((index, newQuantity) => {
       if (newQuantity <= 0) {
         setCart(prev => prev.filter((_, i) => i !== index));
       } else {
         setCart(prev => {
           const updated = [...prev];
           const item = updated[index];
           updated[index].quantity = Math.min(newQuantity, item.max_quantity);
           return updated;
         });
       }
     }, []);
   
     const handleRemoveFromCart = useCallback((index) => {
       setCart(prev => prev.filter((_, i) => i !== index));
     }, []);
   
     const subtotal = useMemo(() => {
       return cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
     }, [cart]);
   
     const total = useMemo(() => {
       return Math.max(0, subtotal - (discount.amount || 0));
     }, [subtotal, discount]);
   
     const handleSubmitSale = async () => {
       if (!selectedCustomer) {
         setSubmitError('Veuillez sélectionner un client');
         return;
       }
   
       if (cart.length === 0) {
         setSubmitError('Le panier est vide');
         return;
       }
   
       if (!selectedAccount) {
         setSubmitError('Veuillez sélectionner un compte');
         return;
       }
   
       try {
         setSubmitting(true);
         setSubmitError(null);
   
         const items = cart.map(item => ({
           variant_id: item.variant_id,
           location_id: item.location_id,
           quantity: item.quantity
         }));
   
         const basePayload = {
           customer_id: selectedCustomer.id,
           account_id: selectedAccount,
           payment_method: paymentMethod,
           discount_amount: discount.amount || 0,
           discount_reason: discount.reason || null,
           items
         };
   
         let response;
   
         if (saleMode === 'immediate') {
           response = await saleService.createImmediate(basePayload);
         } else if (saleMode === 'credit') {
          console.log(installments)
          const installmentsTotal = installments.reduce((sum, inst) => sum + parseFloat(inst.amount || 0), 0);
          if(installments.length > 0) {
            if (Math.abs(installmentsTotal - total) > 0.01) {
              setSubmitError(`La somme des échéances (${installmentsTotal.toLocaleString()} Ar) doit être égale au total (${total.toLocaleString()} Ar)`);
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
   
         setSuccessMessage(response.message);
         setShowConfirmModal(false);
         
         setTimeout(() => {
           setCart([]);
           setSelectedCustomer(null);
           setCustomerSearch('');
           setDiscount({ amount: 0, reason: '' });
           setSuccessMessage(null);
           setInstallments([{ due_date: '', amount: 0 }]);
           setDepositAmount(0);
         }, 2000);
   
       } catch (error) {
         setSubmitError(error.response?.data?.message || 'Erreur lors de la création de la vente');
       } finally {
         setSubmitting(false);
       }
     };
   
     const handleClientCreated = (newClient) => {
       setSelectedCustomer(newClient);
       setCustomerSearch(newClient.name);
       setShowClientModal(false);
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
             <div className="mode-indicator" data-mode={saleMode} />
           </div>
         </header>
   
         <div className="quick-sale-container">
           <main className="quick-sale-main">
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
   
                 {(selectedCategory || selectedSubcategory) && (
                   <button
                     className="btn-clear-filters-compact"
                     onClick={() => {
                       setSelectedCategory(null);
                       setSelectedSubcategory(null);
                     }}
                   >
                     <X size={14} />
                   </button>
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
                     onChange={(e) => setDiscount(prev => ({ ...prev, amount: parseFloat(e.target.value) || 0 }))}
                     className="discount-input"
                     min="0"
                     max={subtotal}
                   />
                   <span>Ar</span>
                 </div>
   
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
                   

                { saleMode != 'credit' && (
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
                  )

                }
                
   
                 {saleMode === 'credit' && (
                   <div className="credit-options">
                     <label>Date limite</label>
                     <input
                       type="date"
                       value={dueDate}
                       onChange={(e) => setDueDate(e.target.value)}
                       className="date-input"
                       min={new Date().toISOString().split('T')[0]}
                     />
   
                     <label>Échéances</label>
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
                       onClick={() => setInstallments(prev => [...prev, { due_date: '', amount: 0 }])}
                     >
                       <Plus size={14} /> Ajouter échéance
                     </button>
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
                     />
   
                     <label>Acompte</label>
                     <input
                       type="number"
                       placeholder="0"
                       value={depositAmount || ''}
                       onChange={(e) => setDepositAmount(parseFloat(e.target.value) || 0)}
                       className="deposit-input"
                       min="0"
                       max={total}
                     />
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
                   disabled={submitting || !selectedCustomer || cart.length === 0 || !selectedAccount}
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
           />
         )}
   
         {showConfirmModal && (
           <ConfirmModal
             saleMode={saleMode}
             total={total}
             customer={selectedCustomer}
             itemCount={cart.length}
             onConfirm={handleSubmitSale}
             onCancel={() => setShowConfirmModal(false)}
             submitting={submitting}
           />
         )}
       </div>
     );
   };
   
   // Composant carte produit
   const ProductCard = ({ product, index, onClick }) => {
     return (
       <div
         className="product-card"
         style={{ animationDelay: `${index * 40}ms` }}
         onClick={onClick}
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
   
   // Modal de sélection produit
   const ProductModal = ({ product, onClose, onAddToCart }) => {
     const [selectedVariant, setSelectedVariant] = useState(null);
     const [selectedLocation, setSelectedLocation] = useState(null);
     const [quantity, setQuantity] = useState(1);
   
     useEffect(() => {
       if (product.variants && product.variants.length > 0) {
         setSelectedVariant(product.variants[0]);
       }
     }, [product]);
   
     useEffect(() => {
       if (selectedVariant?.locations && selectedVariant.locations.length > 0) {
         setSelectedLocation(selectedVariant.locations[0]);
         setQuantity(1);
       }
     }, [selectedVariant]);
   
     const handleAdd = () => {
       if (selectedVariant && selectedLocation && quantity > 0) {
         onAddToCart(product, selectedVariant, selectedLocation, quantity);
       }
     };
   
     const maxQuantity = selectedLocation?.quantity || 0;
   
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
   
             {product.variants && product.variants.length > 0 && (
               <div className="modal-section">
                 <h3>Choisir une variante</h3>
                 <div className="variants-grid-modal">
                   {product.variants.map((variant) => (
                     <button
                       key={variant.id}
                       className={`variant-card ${selectedVariant?.id === variant.id ? 'active' : ''}`}
                       onClick={() => setSelectedVariant(variant)}
                     >
                       {variant.image_path && (
                         <img src={variant.image_path} alt="" className="variant-image" />
                       )}
                       <div className="variant-info-modal">
                         <span className="variant-sku-modal">{variant.sku}</span>
                         <div className="variant-attrs-modal">
                           {variant.attributes.map(attr => (
                             <span key={attr.type_id} className="attr-badge">
                               <strong>{attr.type_name}:</strong> {attr.value}
                             </span>
                           ))}
                         </div>
                         <span className="variant-stock-modal">
                           <Package size={14} />
                           {variant.stock_quantity} unités
                         </span>
                       </div>
                     </button>
                   ))}
                 </div>
               </div>
             )}
   
             {selectedVariant && selectedVariant.locations && selectedVariant.locations.length > 0 && (
               <div className="modal-section">
                 <h3>Choisir un emplacement</h3>
                 <div className="locations-grid-modal">
                   {selectedVariant.locations.map((location) => (
                     <button
                       key={location.location_id}
                       className={`location-card ${selectedLocation?.location_id === location.location_id ? 'active' : ''}`}
                       onClick={() => setSelectedLocation(location)}
                       disabled={location.quantity === 0}
                     >
                       <MapPin size={16} />
                       <div>
                         <span className="location-name-modal">{location.location_name}</span>
                         <span className="location-qty-modal">{location.quantity} disponibles</span>
                       </div>
                     </button>
                   ))}
                 </div>
               </div>
             )}
   
             {selectedVariant && selectedLocation && (
               <div className="modal-section">
                 <h3>Quantité</h3>
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
           </div>
   
           <div className="product-modal-footer">
             <button className="btn-cancel-modal" onClick={onClose}>
               Annuler
             </button>
             <button
               className="btn-add-modal"
               onClick={handleAdd}
               disabled={!selectedVariant || !selectedLocation || quantity <= 0 || quantity > maxQuantity}
             >
               <ShoppingCart size={18} />
               Ajouter au panier
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
   
   // Nouveau composant: Modal de confirmation
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
               <span className="detail-value">{customer.name}</span>
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