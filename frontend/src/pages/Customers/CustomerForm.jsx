/* ============================================
   CUSTOMER FORM - Création & Modification
   ============================================ */

   import React, { useState, useEffect } from 'react';
   import customerService from '../../services/customerService';
   import './customer.css';
   
   const CustomerForm = ({ customer, onClose, onSuccess }) => {
     const isEditing = !!customer;
     
     const [formData, setFormData] = useState({
       name: '',
       phone: '',
       address: '',
       credit_limit: '',
       notes: '',
       is_active: true
     });
   
     const [errors, setErrors] = useState({});
     const [loading, setLoading] = useState(false);
     const [submitError, setSubmitError] = useState(null);
   
     useEffect(() => {
       if (customer) {
         setFormData({
           name: customer.name || '',
           phone: customer.phone || '',
           address: customer.address || '',
           credit_limit: customer.credit_limit || '',
           notes: customer.notes || '',
           is_active: customer.is_active
         });
       }
     }, [customer]);
   
     const validateForm = () => {
       const newErrors = {};
   
       if (!formData.name.trim()) {
         newErrors.name = 'Le nom est requis';
       }
   
       if (formData.phone && !/^[\d\s+()-]+$/.test(formData.phone)) {
         newErrors.phone = 'Format de téléphone invalide';
       }
   
       if (formData.credit_limit && (isNaN(formData.credit_limit) || parseFloat(formData.credit_limit) < 0)) {
         newErrors.credit_limit = 'La limite de crédit doit être un nombre positif';
       }
   
       setErrors(newErrors);
       return Object.keys(newErrors).length === 0;
     };
   
     const handleChange = (e) => {
       const { name, value, type, checked } = e.target;
       setFormData(prev => ({
         ...prev,
         [name]: type === 'checkbox' ? checked : value
       }));
       
       if (errors[name]) {
         setErrors(prev => {
           const newErrors = { ...prev };
           delete newErrors[name];
           return newErrors;
         });
       }
     };
   
     const handleSubmit = async (e) => {
       e.preventDefault();
       
       if (!validateForm()) {
         return;
       }
   
       try {
         setLoading(true);
         setSubmitError(null);
   
         const payload = {
           name: formData.name.trim(),
           phone: formData.phone.trim() || null,
           address: formData.address.trim() || null,
           credit_limit: formData.credit_limit ? parseFloat(formData.credit_limit) : 0,
           notes: formData.notes.trim() || null,
           is_active: formData.is_active
         };
   
         if (isEditing) {
           await customerService.update(customer.id, payload);
         } else {
           await customerService.create(payload);
         }
   
         onSuccess();
       } catch (err) {
         setSubmitError(err.response?.data?.message || 'Une erreur est survenue');
       } finally {
         setLoading(false);
       }
     };
   
     return (
       <div className="modal-overlay scale-in" onClick={onClose}>
         <div className="modal-content" onClick={(e) => e.stopPropagation()}>
           <div className="modal-header">
             <h2>{isEditing ? 'Modifier le client' : 'Nouveau client'}</h2>
             <button className="modal-close" onClick={onClose}>
               <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                 <path d="M5 5L15 15M5 15L15 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
               </svg>
             </button>
           </div>
   
           <form onSubmit={handleSubmit} className="customer-form">
             {submitError && (
               <div className="form-error-banner">
                 <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                   <circle cx="9" cy="9" r="7" stroke="currentColor" strokeWidth="1.5"/>
                   <path d="M9 5V9M9 12V12.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                 </svg>
                 <span>{submitError}</span>
               </div>
             )}
   
             <div className="form-group">
               <label htmlFor="name" className="form-label required">
                 Nom du client
               </label>
               <input
                 type="text"
                 id="name"
                 name="name"
                 value={formData.name}
                 onChange={handleChange}
                 className={`form-input ${errors.name ? 'input-error' : ''}`}
                 placeholder="Jean Dupont"
               />
               {errors.name && <span className="error-message">{errors.name}</span>}
             </div>
   
             <div className="form-row">
               <div className="form-group">
                 <label htmlFor="phone" className="form-label">
                   Téléphone
                 </label>
                 <input
                   type="tel"
                   id="phone"
                   name="phone"
                   value={formData.phone}
                   onChange={handleChange}
                   className={`form-input ${errors.phone ? 'input-error' : ''}`}
                   placeholder="+261 34 00 000 00"
                 />
                 {errors.phone && <span className="error-message">{errors.phone}</span>}
               </div>
   
               <div className="form-group">
                 <label htmlFor="credit_limit" className="form-label">
                   Limite de crédit
                 </label>
                 <input
                   type="number"
                   id="credit_limit"
                   name="credit_limit"
                   value={formData.credit_limit}
                   onChange={handleChange}
                   className={`form-input ${errors.credit_limit ? 'input-error' : ''}`}
                   placeholder="0"
                   min="0"
                   step="0.01"
                 />
                 {errors.credit_limit && <span className="error-message">{errors.credit_limit}</span>}
               </div>
             </div>
   
             <div className="form-group">
               <label htmlFor="address" className="form-label">
                 Adresse
               </label>
               <input
                 type="text"
                 id="address"
                 name="address"
                 value={formData.address}
                 onChange={handleChange}
                 className="form-input"
                 placeholder="Lot IVA 123, Antananarivo"
               />
             </div>
   
             <div className="form-group">
               <label htmlFor="notes" className="form-label">
                 Notes
               </label>
               <textarea
                 id="notes"
                 name="notes"
                 value={formData.notes}
                 onChange={handleChange}
                 className="form-textarea"
                 placeholder="Informations complémentaires..."
                 rows="4"
               />
             </div>
   
             <div className="form-group-toggle">
               <label className="toggle-label">
                 <input
                   type="checkbox"
                   name="is_active"
                   checked={formData.is_active}
                   onChange={handleChange}
                   className="toggle-input"
                 />
                 <span className="toggle-switch"></span>
                 <span className="toggle-text">Client actif</span>
               </label>
             </div>
   
             <div className="form-actions">
               <button type="button" className="btn-secondary" onClick={onClose} disabled={loading}>
                 Annuler
               </button>
               <button type="submit" className="btn-primary" disabled={loading}>
                 {loading ? (
                   <>
                     <svg className="spinner" width="16" height="16" viewBox="0 0 16 16" fill="none">
                       <circle cx="8" cy="8" r="6" stroke="currentColor" strokeWidth="2" strokeDasharray="30" strokeDashoffset="10"/>
                     </svg>
                     Enregistrement...
                   </>
                 ) : (
                   isEditing ? 'Enregistrer' : 'Créer le client'
                 )}
               </button>
             </div>
           </form>
         </div>
       </div>
     );
   };
   
   export default CustomerForm;