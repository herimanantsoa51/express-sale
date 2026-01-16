/* ============================================
   CUSTOMER LIST - Liste des clients
   ============================================ */

   import React, { useState, useEffect } from 'react';
   import customerService from '../../services/customerService';
   import CustomerForm from './CustomerForm';
   import './customer.css';
   import { useNavigate } from 'react-router-dom';
   
   const CustomerList = () => {
     const navigate = useNavigate();
     
     const [customers, setCustomers] = useState([]);
     const [pagination, setPagination] = useState({
       currentPage: 1,
       lastPage: 1,
       perPage: 15,
       total: 0
     });
     const [loading, setLoading] = useState(true);
     const [error, setError] = useState(null);
     
     const [search, setSearch] = useState('');
     const [statusFilter, setStatusFilter] = useState('');
     const [reliabilityFilter, setReliabilityFilter] = useState('');
     const [page, setPage] = useState(1);
     
     const [showForm, setShowForm] = useState(false);
     const [activeTab, setActiveTab] = useState('customers');
   
     useEffect(() => {
       loadCustomers();
     }, [search, statusFilter, reliabilityFilter, page]);
   
     const loadCustomers = async () => {
       try {
         setLoading(true);
         setError(null);
         
         const params = {};
         if (search) params.search = search;
         if (statusFilter) params.status = statusFilter;
         if (reliabilityFilter) params.reliability = reliabilityFilter;
         params.page = page;
         
         const response = await customerService.getAll(params);
         setCustomers(response.data || []);
         
         if (response.meta) {
           setPagination({
             currentPage: response.meta.current_page,
             lastPage: response.meta.last_page,
             perPage: response.meta.per_page,
             total: response.meta.total
           });
         }
       } catch (err) {
         setError(err.response?.data?.message || 'Erreur lors du chargement des clients');
       } finally {
         setLoading(false);
       }
     };
   
     const handleCreateClick = () => {
       setShowForm(true);
     };
   
     const handleCardClick = (customerId) => {
       navigate(`/clients/${customerId}`);
     };
   
     const handleFormClose = () => {
       setShowForm(false);
     };
   
     const handleFormSuccess = () => {
       setShowForm(false);
       loadCustomers();
     };
   
     const handlePageChange = (newPage) => {
       setPage(newPage);
     };
   
     const handlePreviousPage = () => {
       if (page > 1) {
         setPage(page - 1);
       }
     };
   
     const handleNextPage = () => {
       if (page < pagination.lastPage) {
         setPage(page + 1);
       }
     };
   
     const getReliabilityBadgeClass = (level) => {
       const levelMap = {
         excellent: 'badge-excellent',
         good: 'badge-good',
         average: 'badge-average',
         at_risk: 'badge-at-risk'
       };
       return levelMap[level] || 'badge-average';
     };
   
     const getReliabilityLabel = (level) => {
       const labelMap = {
         excellent: 'Excellent',
         good: 'Bon',
         average: 'Moyen',
         at_risk: 'À risque'
       };
       return labelMap[level] || level;
     };
   
     return (
       <div className="customer-page">
         <div className="customer-header">
           <div className="customer-header-content">
             <h1>Clients</h1>
          
           </div>
         </div>
   
         <div className="customer-filters">
           <div className="search-box">
             <svg className="search-icon" width="18" height="18" viewBox="0 0 18 18" fill="none">
               <circle cx="8" cy="8" r="6" stroke="currentColor" strokeWidth="1.5"/>
               <path d="M12.5 12.5L16 16" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
             </svg>
             <input
               type="text"
               placeholder="Rechercher un client..."
               value={search}
               onChange={(e) => setSearch(e.target.value)}
               className="search-input"
             />
           </div>
   
           <div className="filters-group">
             <select
               value={statusFilter}
               onChange={(e) => setStatusFilter(e.target.value)}
               className="filter-select"
             >
               <option value="">Tous les statuts</option>
               <option value="active">Actifs</option>
               <option value="inactive">Inactifs</option>
             </select>
   
             <select
               value={reliabilityFilter}
               onChange={(e) => setReliabilityFilter(e.target.value)}
               className="filter-select"
             >
               <option value="">Toutes les fiabilités</option>
               <option value="excellent">Excellent</option>
               <option value="good">Bon</option>
               <option value="average">Moyen</option>
               <option value="at_risk">À risque</option>
             </select>
           </div>
         </div>
   
         {loading && (
           <div className="customer-grid">
             {[...Array(6)].map((_, i) => (
               <div key={i} className="customer-card skeleton">
                 <div className="skeleton-header"></div>
                 <div className="skeleton-line"></div>
                 <div className="skeleton-line short"></div>
                 <div className="skeleton-badges"></div>
               </div>
             ))}
           </div>
         )}
   
         {error && (
           <div className="error-state">
             <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
               <circle cx="24" cy="24" r="20" stroke="currentColor" strokeWidth="2"/>
               <path d="M24 16V26M24 32V32.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
             </svg>
             <p>{error}</p>
             <button className="btn-secondary" onClick={loadCustomers}>Réessayer</button>
           </div>
         )}
   
         {!loading && !error && customers.length === 0 && (
           <div className="empty-state">
             <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
               <circle cx="32" cy="24" r="12" stroke="currentColor" strokeWidth="2"/>
               <path d="M12 52C12 42 20 36 32 36C44 36 52 42 52 52" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
             </svg>
             <h3>Aucun client</h3>
             <p>Commencez par créer votre premier client</p>
             <button className="btn-primary" onClick={handleCreateClick}>Créer un client</button>
           </div>
         )}
   
         {!loading && !error && customers.length > 0 && (
           <>
             <div className="customer-grid fade-in">
               {customers.map((customer) => (
                 <div
                   key={customer.id}
                   className="customer-card"
                   onClick={() => handleCardClick(customer.id)}
                 >
                   <div className="customer-card-header">
                     <div className="customer-name-row">
                       <h3>{customer.name}</h3>
                       {!customer.is_active && (
                         <span className="badge badge-inactive">Inactif</span>
                       )}
                     </div>
                     <div className="customer-score">
                       <span className="score-value">{customer.reliability_score.toFixed(1)}</span>
                       <span className="score-max">/10</span>
                     </div>
                   </div>
   
                   {/* Ajout du numéro de client */}
                   {customer.customer_number && (
                     <div className="customer-number">
                       <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                         <rect x="1" y="1" width="12" height="12" rx="2" stroke="currentColor" strokeWidth="1.5"/>
                         <path d="M4 4H10M4 7H10M4 10H7" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                       </svg>
                       <span className="customer-number-text">{customer.customer_number}</span>
                     </div>
                   )}
   
                   <div className="customer-info">
                     {customer.phone && (
                       <div className="info-row">
                         <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                           <path d="M2 2H5L6.5 6L4.5 7C5.5 9 7 10.5 9 11.5L10 9.5L14 11V14C14 14 11 14 8 12C5 10 2 7 2 2Z" fill="currentColor"/>
                         </svg>
                         <span>{customer.phone}</span>
                       </div>
                     )}
                     {customer.address && (
                       <div className="info-row">
                         <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                           <path d="M7 1C4.5 1 2.5 3 2.5 5.5C2.5 9 7 13 7 13C7 13 11.5 9 11.5 5.5C11.5 3 9.5 1 7 1Z" fill="currentColor"/>
                           <circle cx="7" cy="5.5" r="1.5" fill="white"/>
                         </svg>
                         <span className="text-truncate">{customer.address}</span>
                       </div>
                     )}
                   </div>
   
                   <div className="customer-badges">
                     <span className={`badge ${getReliabilityBadgeClass(customer.reliability_level)}`}>
                       {getReliabilityLabel(customer.reliability_level)}
                     </span>
                     {customer.is_vip && (
                       <span className="badge badge-vip">
                         <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                           <path d="M6 1L7.5 4.5L11 5L8.5 7.5L9 11L6 9L3 11L3.5 7.5L1 5L4.5 4.5L6 1Z" fill="currentColor"/>
                         </svg>
                         VIP
                       </span>
                     )}
                     {customer.is_at_risk && (
                       <span className="badge badge-warning">
                         <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                           <path d="M6 2L11 10H1L6 2Z" fill="currentColor"/>
                           <path d="M6 5V7M6 8.5V9" stroke="white" strokeWidth="1.2" strokeLinecap="round"/>
                         </svg>
                         Risque
                       </span>
                     )}
                   </div>
   
                   <div className="customer-stats">
                     <div className="stat-item">
                       <span className="stat-value">{customer.sales_count || 0}</span>
                       <span className="stat-label">Ventes</span>
                     </div>
                     <div className="stat-item">
                       <span className="stat-value">{customer.credits_count || 0}</span>
                       <span className="stat-label">Crédits</span>
                     </div>
                     <div className="stat-item">
                       <span className="stat-value">{customer.reservations_count || 0}</span>
                       <span className="stat-label">Réservations</span>
                     </div>
                   </div>
   
                   {customer.loyalty_points > 0 && (
                     <div className="customer-loyalty">
                       <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                         <circle cx="7" cy="7" r="6" stroke="currentColor" strokeWidth="1.5"/>
                         <path d="M7 4V7L9 9" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                       </svg>
                       <span>{customer.loyalty_points} points fidélité</span>
                     </div>
                   )}
                 </div>
               ))}
             </div>
   
             {pagination.lastPage > 1 && (
               <div className="pagination fade-in">
                 <button
                   className="pagination-btn"
                   onClick={handlePreviousPage}
                   disabled={page === 1}
                 >
                   <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                     <path d="M10 12L6 8L10 4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                   </svg>
                   Précédent
                 </button>
   
                 <div className="pagination-info">
                   <span className="pagination-text">
                     Page <strong>{pagination.currentPage}</strong> sur <strong>{pagination.lastPage}</strong>
                   </span>
                   <span className="pagination-total">
                     {pagination.total} client{pagination.total > 1 ? 's' : ''}
                   </span>
                 </div>
   
                 <button
                   className="pagination-btn"
                   onClick={handleNextPage}
                   disabled={page === pagination.lastPage}
                 >
                   Suivant
                   <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                     <path d="M6 4L10 8L6 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                   </svg>
                 </button>
               </div>
             )}
           </>
         )}
   
         {showForm && (
           <CustomerForm
             onClose={handleFormClose}
             onSuccess={handleFormSuccess}
           />
         )}
       </div>
     );
   };
   
   export default CustomerList;