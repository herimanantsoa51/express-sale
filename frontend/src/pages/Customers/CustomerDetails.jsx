/* ============================================
   CUSTOMER DETAILS - Détails client avec onglets
   ============================================ */

   import React, { useState, useEffect } from 'react';
   import { useParams, useNavigate } from 'react-router-dom';
   import customerService from '../../services/customerService';
   import CustomerForm from './CustomerForm';
   import './customer.css';
   
   const CustomerDetails = () => {
     const { id } = useParams();
     const navigate = useNavigate();
   
     const [customer, setCustomer] = useState(null);
     const [statistics, setStatistics] = useState(null);
     const [loading, setLoading] = useState(true);
     const [error, setError] = useState(null);
   
     const [activeTab, setActiveTab] = useState('sales');
     
     const [sales, setSales] = useState([]);
     const [credits, setCredits] = useState([]);
     const [reservations, setReservations] = useState([]);
     
     const [salesPage, setSalesPage] = useState(1);
     const [creditsPage, setCreditsPage] = useState(1);
     const [reservationsPage, setReservationsPage] = useState(1);
     
     const [salesPagination, setSalesPagination] = useState(null);
     const [creditsPagination, setCreditsPagination] = useState(null);
     const [reservationsPagination, setReservationsPagination] = useState(null);
     
     const [tabLoading, setTabLoading] = useState(false);
     const [showEditForm, setShowEditForm] = useState(false);
   
     useEffect(() => {
       loadCustomer();

     }, [id]);


   
     useEffect(() => {
       loadTabData();
     }, [activeTab, salesPage, creditsPage, reservationsPage]);
   
     const loadCustomer = async () => {
       try {
         setLoading(true);
         setError(null);
         const data = await customerService.getById(id);
         setCustomer(data.data);
         setStatistics(data.statistics);


         const data1 = await customerService.getSalesImmediate(id, { page: salesPage });
         setSales(data1.data || []);
         setSalesPagination(data1.meta);
       } catch (err) {
         setError(err.response?.data?.message || 'Erreur lors du chargement du client');
       } finally {
         setLoading(false);
       }
     };
   
     const loadTabData = async () => {
       if (!customer) return;
   
       try {
         setTabLoading(true);
   
         if (activeTab === 'sales') {
           const data = await customerService.getSalesImmediate(id, { page: salesPage });
           setSales(data.data || []);
           setSalesPagination(data.meta);
         } else if (activeTab === 'credits') {
           const data = await customerService.getCredits(id, { page: creditsPage });
           setCredits(data.data || []);
           setCreditsPagination(data.meta);
         } else if (activeTab === 'reservations') {
           const data = await customerService.getReservations(id, { page: reservationsPage });
           setReservations(data.data || []);
           setReservationsPagination(data.meta);
         }
       } catch (err) {
         console.error('Erreur chargement onglet:', err);
       } finally {
         setTabLoading(false);
       }
     };
   
     const formatCurrency = (amount) => {
       return new Intl.NumberFormat('fr-FR', {
         minimumFractionDigits: 0,
         maximumFractionDigits: 0
       }).format(amount) + ' Ar';
     };
   
     const formatDate = (dateString) => {
       return new Date(dateString).toLocaleDateString('fr-FR', {
         day: '2-digit',
         month: 'short',
         year: 'numeric'
       });
     };
   
     const formatDateTime = (dateString) => {
       return new Date(dateString).toLocaleDateString('fr-FR', {
         day: '2-digit',
         month: 'short',
         year: 'numeric',
         hour: '2-digit',
         minute: '2-digit'
       });
     };
   
     const getReliabilityBadgeClass = (level) => {
       const levelMap = {
         'Excellent': 'badge-excellent',
         'Bon': 'badge-good',
         'Acceptable': 'badge-average',
         'À risque': 'badge-at-risk'
       };
       return levelMap[level] || 'badge-average';
     };
   
     const getStatusBadge = (status) => {
       const statusMap = {
         completed: { label: 'Complétée', class: 'badge-success' },
         confirmed: { label: 'Confirmée', class: 'badge-info' },
         cancelled: { label: 'Annulée', class: 'badge-danger' },
         pending: { label: 'En attente', class: 'badge-warning' }
       };
       return statusMap[status] || { label: status, class: 'badge-average' };
     };
   
     const handleTabChange = (tab) => {
       setActiveTab(tab);
     };
   
     const handleSaleClick = (saleId) => {
       navigate(`/ventes/immediates/${saleId}`);
     };
   
     const handleCreditClick = (creditId) => {
       navigate(`/ventes/credits/${creditId}`);
     };
   
     const handleReservationClick = (reservationId) => {
    
       navigate(`/ventes/reservations/${reservationId}`);
     };
   
     const handleEditClick = () => {
       setShowEditForm(true);
     };
   
     const handleEditFormClose = () => {
       setShowEditForm(false);
     };
   
     const handleEditFormSuccess = () => {
       setShowEditForm(false);
       loadCustomer();
       loadTabData();
     };
   
     const parseSystemNotes = (notesString) => {
       if (!notesString) return [];
       
       const lines = notesString.split('\n').filter(line => line.trim());
       return lines.map((line, index) => {
         // Essayer de parser le format système [date] contenu
         const match = line.match(/^\[([^\]]+)\]\s+(.+)$/);
         if (match) {
           return {
             id: index,
             date: match[1],
             content: match[2],
             type: 'system'
           };
         }
         
         // Vérifier si c'est un événement système spécifique
         const systemEvents = [
           'Score ajusté',
           'points:',
           'Achat de',
           'Réservation',
           'Crédit',
           'paiement',
           'échéance',
           'retard'
         ];
         
         const isSystemEvent = systemEvents.some(event => line.includes(event));
         
         return {
           id: index,
           date: null,
           content: line,
           type: isSystemEvent ? 'system' : 'manual'
         };
       });
     };
   
     const getNoteIcon = (note) => {
       const content = note.content.toLowerCase();
       
       if (content.includes('points:') || content.includes('points fidélité')) {
         return (
           <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
             <circle cx="8" cy="8" r="6" stroke="currentColor" strokeWidth="1.5"/>
             <path d="M8 5V8L10 10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
           </svg>
         );
       }
       
       if (content.includes('score ajusté') || content.includes('fiabilité')) {
         return (
           <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
             <path d="M8 2L10 6L14 6.5L11 9.5L11.5 13.5L8 11.5L4.5 13.5L5 9.5L2 6.5L6 6L8 2Z" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round"/>
           </svg>
         );
       }
       
       if (content.includes('achat') || content.includes('vente') || content.includes('montant')) {
         return (
           <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
             <rect x="2" y="4" width="12" height="10" rx="1" stroke="currentColor" strokeWidth="1.5"/>
             <path d="M5 2V6M11 2V6M2 8H14" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
           </svg>
         );
       }
       
       if (content.includes('réservation') || content.includes('acompte') || content.includes('expire')) {
         return (
           <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
             <circle cx="8" cy="8" r="6" stroke="currentColor" strokeWidth="1.5"/>
             <path d="M8 4V8L10 10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
           </svg>
         );
       }
       
       if (content.includes('crédit') || content.includes('échéance') || content.includes('paiement')) {
         return (
           <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
             <rect x="2" y="5" width="12" height="8" rx="1" stroke="currentColor" strokeWidth="1.5"/>
             <path d="M5 7H11M8 4V10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
           </svg>
         );
       }
       
       // Icone par défaut pour les notes système
       if (note.type === 'system') {
         return (
           <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
             <circle cx="8" cy="8" r="6" stroke="currentColor" strokeWidth="1.5"/>
             <path d="M8 5V8M8 10.5V11" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
           </svg>
         );
       }
       
       // Icone pour notes manuelles
       return (
         <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
           <rect x="2" y="2" width="12" height="12" rx="1" stroke="currentColor" strokeWidth="1.5"/>
           <path d="M5 5H11M5 8H11M5 11H8" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
         </svg>
       );
     };
   
     const renderSystemNotesTab = () => {
       const systemNotes = parseSystemNotes(customer.notes);
       
       if (systemNotes.length === 0) {
         return (
           <div className="empty-tab">
             <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
               <rect x="8" y="8" width="32" height="32" rx="2" stroke="currentColor" strokeWidth="2"/>
               <path d="M16 20H32M16 26H28M16 32H24" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
             </svg>
             <p>Aucune note système</p>
             <p className="empty-tab-subtitle">Les notes système apparaîtront automatiquement ici</p>
           </div>
         );
       }
   
       return (
         <div className="system-notes-list">
           {systemNotes.map((note) => (
             <div key={note.id} className={`system-note-item ${note.type}`}>
               <div className="system-note-icon">
                 {getNoteIcon(note)}
               </div>
               <div className="system-note-content">
                 <div className="system-note-header">
                   {note.date && (
                     <span className="system-note-date">
                       <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                         <circle cx="6" cy="6" r="5" stroke="currentColor" strokeWidth="1"/>
                         <path d="M6 3V6L8 7.5" stroke="currentColor" strokeWidth="1" strokeLinecap="round"/>
                       </svg>
                       {note.date}
                     </span>
                   )}
                   {note.type === 'system' && (
                     <span className="system-note-badge">
                       <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                         <circle cx="5" cy="5" r="4" fill="var(--primary)" stroke="white" strokeWidth="1"/>
                       </svg>
                       Système
                     </span>
                   )}
                 </div>
                 <p className="system-note-text">{note.content}</p>
               </div>
             </div>
           ))}
         </div>
       );
     };
   
     const renderSalesTab = () => {
       if (sales.length === 0) {
         return (
           <div className="empty-tab">
             <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
               <rect x="6" y="12" width="36" height="30" rx="2" stroke="currentColor" strokeWidth="2"/>
               <path d="M16 6V18M32 6V18M6 22H42" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
             </svg>
             <p>Aucune vente</p>
           </div>
         );
       }
   
       return (
         <>
           <div className="item-list">
             {sales.map((sale) => (
               <div
                 key={sale.id}
                 className="item-card"
                 onClick={() => handleSaleClick(sale.id)}
               >
                 <div className="item-card-main">
                   <h3 className="item-title">{sale.sale_number}</h3>
                   <span className="item-amount">{formatCurrency(sale.total_amount)}</span>
                 </div>
                 <div className="item-card-footer">
                   <span className="item-date">{formatDate(sale.sale_date)}</span>
                   <span className="item-creator">Par {sale.creator.name}</span>
                 </div>
               </div>
             ))}
           </div>
           {salesPagination && salesPagination.last_page > 1 && (
             <div className="tab-pagination">
               <button
                 className="pagination-btn"
                 onClick={() => setSalesPage(salesPage - 1)}
                 disabled={salesPage === 1}
               >
                 <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                   <path d="M10 12L6 8L10 4" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                 </svg>
                 Précédent
               </button>
               <span className="pagination-text">
                 Page {salesPage} sur {salesPagination.last_page}
               </span>
               <button
                 className="pagination-btn"
                 onClick={() => setSalesPage(salesPage + 1)}
                 disabled={salesPage === salesPagination.last_page}
               >
                 Suivant
                 <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                   <path d="M6 4L10 8L6 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                 </svg>
               </button>
             </div>
           )}
         </>
       );
     };
   
     const renderCreditsTab = () => {
       if (credits.length === 0) {
         return (
           <div className="empty-tab">
             <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
               <rect x="6" y="15" width="36" height="24" rx="2" stroke="currentColor" strokeWidth="2"/>
               <path d="M18 24H30M24 18V30" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
             </svg>
             <p>Aucun crédit</p>
           </div>
         );
       }
   
       return (
         <>
           <div className="item-list">
             {credits.map((credit) => (
               <div
                 key={credit.id}
                 className="item-card"
                 onClick={() => handleCreditClick(credit.id)}
               >
                 <div className="item-card-main">
                   <h3 className="item-title">Crédit #{credit.sale_number}</h3>
                   <span className="item-amount">{formatCurrency(credit.total_amount)}</span>
                 </div>
                 <div className="credit-details">
                   <div className="credit-row">
                     <span>Payé:</span>
                     <span className="text-success">{formatCurrency(credit.amount_paid)}</span>
                   </div>
                   <div className="credit-row">
                     <span>Restant:</span>
                     <span className="text-danger">{formatCurrency(credit.amount_due)}</span>
                   </div>
                 </div>
                 <div className="item-card-footer">
                   <span className="item-date">Échéance: {formatDate(credit.due_date)}</span>
                   <span className="item-creator">Par {credit.creator.name}</span>
                 </div>
               </div>
             ))}
           </div>
           {creditsPagination && creditsPagination.last_page > 1 && (
             <div className="tab-pagination">
               <button
                 className="pagination-btn"
                 onClick={() => setCreditsPage(creditsPage - 1)}
                 disabled={creditsPage === 1}
               >
                 <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                   <path d="M10 12L6 8L10 4" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                 </svg>
                 Précédent
               </button>
               <span className="pagination-text">
                 Page {creditsPage} sur {creditsPagination.last_page}
               </span>
               <button
                 className="pagination-btn"
                 onClick={() => setCreditsPage(creditsPage + 1)}
                 disabled={creditsPage === creditsPagination.last_page}
               >
                 Suivant
                 <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                   <path d="M6 4L10 8L6 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                 </svg>
               </button>
             </div>
           )}
         </>
       );
     };
   
     const renderReservationsTab = () => {
       if (reservations.length === 0) {
         return (
           <div className="empty-tab">
             <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
               <circle cx="24" cy="24" r="18" stroke="currentColor" strokeWidth="2"/>
               <path d="M24 12V24L32 28" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
             </svg>
             <p>Aucune réservation</p>
           </div>
         );
       }
   
       return (
         <>
           <div className="item-list">
             {reservations.map((reservation) => (
               <div
                 key={reservation.id}
                 className="item-card"
                 onClick={() => handleReservationClick(reservation.id)}
               >
                 <div className="item-card-main">
                   <div className="item-title-row">
                     <h3 className="item-title">Réservation #{reservation.sale_number}</h3>
                     <span className={`badge ${getStatusBadge(reservation.status).class}`}>
                       {getStatusBadge(reservation.status).label}
                     </span>
                   </div>
                   <span className="item-amount">{formatCurrency(reservation.total_amount)}</span>
                 </div>
                 <div className="credit-details">
                   <div className="credit-row">
                     <span>Acompte:</span>
                     <span className="text-success">{formatCurrency(reservation.deposit_amount)}</span>
                   </div>
                   <div className="credit-row">
                     <span>Restant:</span>
                     <span className="text-warning">{formatCurrency(reservation.remaining_amount)}</span>
                   </div>
                 </div>
                 <div className="item-card-footer">
                   <span className="item-date">Expire: {formatDate(reservation.expiry_date)}</span>
                   <span className="item-creator">Par {reservation.creator.name}</span>
                 </div>
               </div>
             ))}
           </div>
           {reservationsPagination && reservationsPagination.last_page > 1 && (
             <div className="tab-pagination">
               <button
                 className="pagination-btn"
                 onClick={() => setReservationsPage(reservationsPage - 1)}
                 disabled={reservationsPage === 1}
               >
                 <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                   <path d="M10 12L6 8L10 4" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                 </svg>
                 Précédent
               </button>
               <span className="pagination-text">
                 Page {reservationsPage} sur {reservationsPagination.last_page}
               </span>
               <button
                 className="pagination-btn"
                 onClick={() => setReservationsPage(reservationsPage + 1)}
                 disabled={reservationsPage === reservationsPagination.last_page}
               >
                 Suivant
                 <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                   <path d="M6 4L10 8L6 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                 </svg>
               </button>
             </div>
           )}
         </>
       );
     };
   
     if (loading) {
       return (
         <div className="customer-details-page">
           <div className="details-skeleton">
             <div className="skeleton-header"></div>
             <div className="skeleton-stats"></div>
             <div className="skeleton-tabs"></div>
           </div>
         </div>
       );
     }
   
     if (error) {
       return (
         <div className="customer-details-page">
           <div className="error-state">
             <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
               <circle cx="24" cy="24" r="20" stroke="currentColor" strokeWidth="2"/>
               <path d="M24 16V26M24 32V32.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
             </svg>
             <p>{error}</p>
             <button className="btn-primary" onClick={() => navigate('/customers')}>
               Retour aux clients
             </button>
           </div>
         </div>
       );
     }
   
     if (!customer) return null;
   
     return (
       <div className="customer-details-page fade-in">
         <div className="details-header">
           <button className="back-button" onClick={() => navigate('/customers')}>
             <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
               <path d="M12 16L6 10L12 4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
             </svg>
             Retour
           </button>
   
           <div className="details-title-row">
             <div>
               <h1>{customer.name}</h1>
               
               {/* Affichage du numéro de client */}
               <div className="customer-number-display">
                 <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                   <rect x="1" y="1" width="16" height="16" rx="2" stroke="currentColor" strokeWidth="1.5"/>
                   <path d="M4 4H14M4 7H14M4 10H10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                 </svg>
                 <span className="customer-number-label">N° Client:</span>
                 <span className="customer-number-value">{customer.customer_number}</span>
               </div>
               
               <div className="details-meta">
                 {customer.phone && <span>{customer.phone}</span>}
                 {customer.address && <span>{customer.address}</span>}
                 <span>Inscrit le {formatDate(customer.created_at)}</span>
               </div>
             </div>
             <div className="details-actions">
               <div className="details-badges">
                 <span className={`badge ${getReliabilityBadgeClass(customer.reliability_level)}`}>
                   {customer.reliability_level}
                 </span>
                 {statistics?.is_vip && (
                   <span className="badge badge-vip">
                     <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                       <path d="M6 1L7.5 4.5L11 5L8.5 7.5L9 11L6 9L3 11L3.5 7.5L1 5L4.5 4.5L6 1Z" fill="currentColor"/>
                     </svg>
                     VIP
                   </span>
                 )}
                 {!customer.is_active && (
                   <span className="badge badge-inactive">Inactif</span>
                 )}
               </div>
               <button className="btn-primary" onClick={handleEditClick}>
                 <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                   <path d="M11.5 2L14 4.5L5 13.5H2.5V11L11.5 2Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                 </svg>
                 Modifier
               </button>
             </div>
           </div>
         </div>
   
         <div className="stats-grid">
           <div className="stat-card">
             <div className="stat-card-icon" style={{ background: 'var(--primary-light)', color: 'var(--primary)' }}>
               <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                 <path d="M12 2L15 8L22 9L17 14L18 21L12 18L6 21L7 14L2 9L9 8L12 2Z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round"/>
               </svg>
             </div>
             <div className="stat-card-content">
               <span className="stat-card-value">{customer.reliability_score.toFixed(2)}/10</span>
               <span className="stat-card-label">Score de fiabilité</span>
             </div>
           </div>
   
           <div className="stat-card">
             <div className="stat-card-icon" style={{ background: 'var(--success-light)', color: 'var(--success)' }}>
               <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                 <rect x="3" y="6" width="18" height="14" rx="2" stroke="currentColor" strokeWidth="2"/>
                 <path d="M3 10H21M7 6V4M17 6V4" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
               </svg>
             </div>
             <div className="stat-card-content">
               <span className="stat-card-value">{customer.loyalty_points.toLocaleString()}</span>
               <span className="stat-card-label">Points fidélité</span>
             </div>
           </div>
   
           <div className="stat-card">
             <div className="stat-card-icon" style={{ background: 'var(--warning-light)', color: 'var(--warning)' }}>
               <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                 <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="2"/>
                 <path d="M12 7V12L15 15" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
               </svg>
             </div>
             <div className="stat-card-content">
               <span className="stat-card-value">{formatCurrency(statistics?.total_spent || 0)}</span>
               <span className="stat-card-label">Total dépensé</span>
             </div>
           </div>
         </div>
   
         <div className="details-tabs-container">
           <div className="tabs-header">
             <button
               className={`tab-button ${activeTab === 'sales' ? 'active' : ''}`}
               onClick={() => handleTabChange('sales')}
             >
               <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                 <rect x="2" y="4" width="14" height="12" rx="1" stroke="currentColor" strokeWidth="1.5"/>
                 <path d="M6 2V6M12 2V6M2 8H16" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
               </svg>
               Ventes ({customer.sales_count})
             </button>
             <button
               className={`tab-button ${activeTab === 'credits' ? 'active' : ''}`}
               onClick={() => handleTabChange('credits')}
             >
               <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                 <rect x="2" y="5" width="14" height="10" rx="1" stroke="currentColor" strokeWidth="1.5"/>
                 <path d="M6 9H12M9 6V12" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
               </svg>
               Crédits ({customer.credits_count})
             </button>
             <button
               className={`tab-button ${activeTab === 'reservations' ? 'active' : ''}`}
               onClick={() => handleTabChange('reservations')}
             >
               <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                 <circle cx="9" cy="9" r="7" stroke="currentColor" strokeWidth="1.5"/>
                 <path d="M9 5V9L12 11" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
               </svg>
               Réservations ({customer.reservations_count})
             </button>
             <button
               className={`tab-button ${activeTab === 'system-notes' ? 'active' : ''}`}
               onClick={() => handleTabChange('system-notes')}
             >
               <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                 <path d="M4 2H14C15.1 2 16 2.9 16 4V14C16 15.1 15.1 16 14 16H4C2.9 16 2 15.1 2 14V4C2 2.9 2.9 2 4 2Z" 
                   stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                 <path d="M5 6H13M5 9H10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                 <circle cx="13" cy="11" r="2" stroke="currentColor" strokeWidth="1.5"/>
               </svg>
               Historique ({parseSystemNotes(customer.notes).length})
             </button>
           </div>
   
           <div className="tab-content">
             {tabLoading ? (
               <div className="tab-loading">
                 <svg className="spinner" width="32" height="32" viewBox="0 0 32 32" fill="none">
                   <circle cx="16" cy="16" r="12" stroke="currentColor" strokeWidth="3" strokeDasharray="60" strokeDashoffset="20"/>
                 </svg>
               </div>
             ) : (
               <div className="tab-panel fade-in">
                 {activeTab === 'sales' && renderSalesTab()}
                 {activeTab === 'credits' && renderCreditsTab()}
                 {activeTab === 'reservations' && renderReservationsTab()}
                 {activeTab === 'system-notes' && renderSystemNotesTab()}
               </div>
             )}
           </div>
         </div>
   
         {showEditForm && (
           <CustomerForm
             customer={customer}
             onClose={handleEditFormClose}
             onSuccess={handleEditFormSuccess}
           />
         )}
       </div>
     );
   };
   
   export default CustomerDetails;