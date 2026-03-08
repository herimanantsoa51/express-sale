// ============================================
// src/App.jsx - Avec contrôle d'accès par rôle
// ============================================

import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { ToastContainer, Slide } from 'react-toastify';

// 🔥 OBLIGATOIRE : CSS de base Toastify
import 'react-toastify/dist/ReactToastify.css';

// 🎨 CSS custom Toastify (APRÈS le CSS officiel)
import './styles/customToastify.css';

// Contexts
import { AuthProvider } from './context/AuthContext';
import { ThemeProvider } from './context/ThemeContext';
import { AiTaskProvider } from './context/AiTaskContext';

// Layout & routes protégées
import ProtectedRoute from './components/ProtectedRoute';
import RoleRoute from './components/RoleRoute';
import Layout from './components/layout/Layout';

// Pages publiques
import Login from './pages/Login';
import Unauthorized from './pages/Unauthorized';

// Pages principales
import Dashboard from './pages/Dashboard/Dashboard';

// Produits
import ProductsList from './pages/Products/ProductsList';
import ProductDetails from './pages/Products/ProductDetails';
import ProductForm from './pages/Products/ProductForm';
import VariantForm from './pages/Products/VariantForm';

// Fournisseurs
import SuppliersList from './pages/Suppliers/SupplierList';
import SupplierDetails from './pages/Suppliers/SupplierDetails';
import SupplierForm from './pages/Suppliers/SupplierForm';

// Transitaires
import FreightForwardersList from './pages/FreightForwarders/FreightForwardersList';
import FreightForwarderDetails from './pages/FreightForwarders/FreightForwarderDetails';
import FreightForwarderForm from './pages/FreightForwarders/FreightForwarderForm';

// Réapprovisionnements
import StockReceiptForm from './pages/StockReceipt/StockReceiptForm';
import StockReceiptDetails from './pages/StockReceipt/StockReceiptDetails';
import StockReceiptList from './pages/StockReceipt/StockReceiptList';
import StockReceiptRating from './pages/StockReceipt/StockReceiptRating';
import StockReceiptPayment from './pages/StockReceipt/StockReceiptPayment';
import CostAllocation from './pages/StockReceipt/CostAllocation';
import CostAllocationView from './pages/StockReceipt/CostAllocationView';

// Localisations
import ProductVariantLocationsList from './pages/ProductVariantLocations/ProductVariantLocationsList';
import ProductVariantLocationForm from './pages/ProductVariantLocations/ProductVariantLocationForm';
import LocationVariantsView from './pages/ProductVariantLocations/LocationVariantsView';

// Mouvements de stock
import StockMovementList from './pages/StockMovement/StockMovementList';
import StockTransferForm from './pages/StockMovement/StockTransferForm';
import StockLossForm from './pages/StockMovement/StockLossForm';

// Locations
import { LocationsList, LocationDetail, LocationForm } from './pages/Locations';

// Clients
import CustomerList from './pages/Customers/CustomerList';
import CustomerDetails from './pages/Customers/CustomerDetails';

// Ventes
import QuickSalePage from './pages/Sales/QuickSalePage';
import ImmediateSalesList from './pages/Sales/ImmediateSalesList';
import ImmediateSaleDetail from './pages/Sales/ImmediateSaleDetail';
import CreditListPage from './pages/Sales/CreditLIstPage';
import CreditDetailPage from './pages/Sales/CreditDetailPage';
import ReservationsPage from './pages/Sales/ReservationsPage';
import ReservationDetailPage from './pages/Sales/ReservationDetailPage';

// Comptes
import AccountForm from './pages/Accounts/AccountForm';
import AccountsList from './pages/Accounts/AccountsList';
import AccountDetail from './pages/Accounts/AccountDetail';
import AccountTransfer from './pages/Accounts/AccountTransfer';
import CurrencyRates from './pages/Accounts/CurrencyRates';
import ActivityLogs from './pages/ActivityLogs/ActivityLogs';

// Dépenses
import ExpenseList from './pages/Expenses/ExpenseList';
import ExpenseCreate from './pages/Expenses/ExpenseCreate';
import PlannedExpensesList from './pages/Expenses/PlannedExpensesList';
import PlannedExpenseDetail from './pages/Expenses/PlannedExpenseDetail';
// Transactions
import TransactionDetail from './pages/Transactions/TransactionDetail';

// Statistiques
import SalesStatistics from './pages/Statistics/SaleStatisctics';

// Utilisateurs
import UsersPage from './pages/Users/userPage';

// Comptages
import CashCountList from './pages/CashCount/CashCountList';
import CashCountForm from './pages/CashCount/CashCountForm';
import CashCountDetail from './pages/CashCount/CashCountDetail';

// Paramètres
import CompanyConfiguration from './pages/Settings/CompanyConfiguration';
import AiProviderConfig from './pages/Settings/AiProviderConfig';
import AiTaskHistory from './pages/Settings/AiTaskHistory';
import InventoryReconciliationForm from './pages/StockMovement/InventoryReconciliationForm';
import PlannedExpenseCreate from './pages/Expenses/PlannedExpenseCreate';
import FinancialStatistics from './pages/Statistics/FinancialStatistics';

// Styles globaux
import './styles/variables.css';
import './styles/reset.css';
import './styles/global.css';

function App() {
  return (
    <BrowserRouter>
      <ThemeProvider>
        <AuthProvider>
          <AiTaskProvider>
            {/* ToastContainer DANS les providers et le Router */}
            <ToastContainer
              position="top-right"
              autoClose={4000}
              hideProgressBar={false}
              newestOnTop
              closeOnClick
              pauseOnFocusLoss
              draggable
              pauseOnHover
              theme="auto"
              transition={Slide}
              limit={3}
              closeButton
              icon
              style={{ zIndex: 99999 }}
            />
            
            <Routes>
              {/* Routes publiques */}
              <Route path="/login" element={<Login />} />
              <Route path="/unauthorized" element={<Unauthorized />} />

              {/* Routes protégées */}
              <Route
                path="/"
                element={
                  <ProtectedRoute>
                    <Layout />
                  </ProtectedRoute>
                }
              >
                {/* Dashboard - Accessible à tous */}
                <Route index element={<Navigate to="/dashboard" replace />} />
                <Route path="dashboard" element={<Dashboard />} />
              
              {/* === PRODUITS - Admin + Vendeur === */}
              <Route path="produits">
                <Route index element={<ProductsList />} />
                <Route path="nouveau" element={
                  <RoleRoute allowedRoles={['admin','vendeur']}>
                    <ProductForm />
                  </RoleRoute>
                } />
                <Route path=":id" element={<ProductDetails />} />
                <Route path=":id/modifier" element={
                  <RoleRoute allowedRoles={['admin','vendeur']}>
                    <ProductForm />
                  </RoleRoute>
                } />
                <Route path=":productId/variante/nouvelle" element={
                  <RoleRoute allowedRoles={['admin','vendeur']}>
                    <VariantForm />
                  </RoleRoute>
                } />
                <Route path=":productId/variante/:variantId/modifier" element={
                  <RoleRoute allowedRoles={['admin','vendeur']}>
                    <VariantForm />
                  </RoleRoute>
                } />
              </Route>

              {/* === FOURNISSEURS - Admin uniquement === */}
              <Route path="fournisseurs">
                <Route index element={
                  <RoleRoute allowedRoles={['admin']}>
                    <SuppliersList />
                  </RoleRoute>
                } />
                <Route path="nouveau" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <SupplierForm />
                  </RoleRoute>
                } />
                <Route path=":id" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <SupplierDetails />
                  </RoleRoute>
                } />
                <Route path=":id/modifier" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <SupplierForm />
                  </RoleRoute>
                } />
              </Route>

              {/* === TRANSITAIRES - Admin uniquement === */}
              <Route path="transitaires">
                <Route index element={
                  <RoleRoute allowedRoles={['admin']}>
                    <FreightForwardersList />
                  </RoleRoute>
                } />
                <Route path="nouveau" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <FreightForwarderForm />
                  </RoleRoute>
                } />
                <Route path=":id" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <FreightForwarderDetails />
                  </RoleRoute>
                } />
                <Route path=":id/modifier" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <FreightForwarderForm />
                  </RoleRoute>
                } />
              </Route>

              {/* === RÉAPPROVISIONNEMENTS - Admin + Vendeur === */}
              <Route path="reapprovisionnements">
                <Route index element={<RoleRoute allowedRoles={['admin']}><StockReceiptList/></RoleRoute>}/>
                <Route path='nouveau' element={
                  <RoleRoute allowedRoles={['admin']}>
                    <StockReceiptForm />
                  </RoleRoute>
                } />
                <Route path=':id' element={<RoleRoute allowedRoles={['admin']}>
                  <StockReceiptDetails/>
                </RoleRoute>}/>
                <Route path=':id/evaluation' element={
                  <RoleRoute allowedRoles={['admin']}>
                    <StockReceiptRating/>
                  </RoleRoute>
                }/>
                <Route path=':id/paiements' element={
                  <RoleRoute allowedRoles={['admin']}>
                    <StockReceiptPayment/>
                  </RoleRoute>
                }/>
                <Route path=':id/cout-repartition' element={
                  <RoleRoute allowedRoles={['admin']}>
                    <CostAllocation/>
                  </RoleRoute>
                }/>
                <Route path=':id/cout-repartition/detail' element={
                  <RoleRoute allowedRoles={['admin']}>
                    <CostAllocationView/>
                  </RoleRoute>
                }/>
              </Route>

              {/* === LOCALISATIONS VARIANTES - Admin + Vendeur === */}
              <Route path="localisations-variantes">
                <Route index element={<ProductVariantLocationsList />} />
                <Route path="nouvelle" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <ProductVariantLocationForm />
                  </RoleRoute>
                } />
                <Route path=":id/modifier" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <ProductVariantLocationForm />
                  </RoleRoute>
                } />
              </Route>
              
              {/* === VUE PAR LOCATION - Admin + Vendeur === */}
              <Route path="locations/:locationId/variantes" element={<LocationVariantsView />} />

              {/* === MOUVEMENTS DE STOCK - Admin + Vendeur === */}
              <Route path="mouvements-stock">
                <Route index element={<RoleRoute allowedRoles={['admin']}><StockMovementList /></RoleRoute>} />
                <Route path="transfert" element={<RoleRoute allowedRoles={['admin']}><StockTransferForm /></RoleRoute>} />
                <Route path="perte" element={<RoleRoute allowedRoles={['admin']}><StockLossForm /></RoleRoute>} />
                <Route path="reconciliation" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <InventoryReconciliationForm />
                  </RoleRoute>
                } />
              </Route>

              {/* === LOCATIONS - Admin + Vendeur === */}
              <Route path="locations">
                <Route index element={<LocationsList />} />
                <Route path="nouveau" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <LocationForm />
                  </RoleRoute>
                } />
                <Route path=":id" element={<LocationDetail />} />
                <Route path=":id/modifier" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <LocationForm />
                  </RoleRoute>
                } />
              </Route>

              {/* === CLIENTS - Admin uniquement === */}
              <Route path="clients">
                <Route index element={
                  <RoleRoute allowedRoles={['admin','vendeur']}>
                    <CustomerList/>
                  </RoleRoute>
                } />
                <Route path=":id" element={
                  <RoleRoute allowedRoles={['admin','vendeur']}>
                    <CustomerDetails/>
                  </RoleRoute>
                } />
              </Route>

              {/* === VENTES - Admin + Vendeur === */}
              <Route path="ventes">
                <Route path="rapide" element={<QuickSalePage />} />
                <Route path="immediates">
                  <Route index element={<ImmediateSalesList />} />
                  <Route path=":id" element={<ImmediateSaleDetail />} />
                </Route>
                <Route path='credits'>
                  <Route index element={<CreditListPage/>} />
                  <Route path=':id' element={<CreditDetailPage/>} />
                </Route>
                <Route path='reservations'>
                  <Route index element={<ReservationsPage/>} />
                  <Route path=':id' element={<ReservationDetailPage/>} />
                </Route>
              </Route>

              {/* === TRÉSORERIE - Admin uniquement === */}
              <Route path='comptes'>
                <Route index element={
                  <RoleRoute allowedRoles={['admin']}>
                    <AccountsList/>
                  </RoleRoute>
                }/>
                <Route path="nouveau" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <AccountForm />
                  </RoleRoute>
                } />
                <Route path=":id" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <AccountDetail />
                  </RoleRoute>
                } />
                <Route path=":id/modifier" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <AccountForm />
                  </RoleRoute>
                } />
                <Route path="transfert" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <AccountTransfer />
                  </RoleRoute>
                } />
                <Route path='conversion' element={
                  <RoleRoute allowedRoles={['admin']}>
                    <CurrencyRates/>
                  </RoleRoute>
                }/>
              </Route>

              {/* === DÉPENSES - Admin uniquement === */}
              <Route path="depenses">
                <Route index element={
                  <RoleRoute allowedRoles={['admin']}>
                    <ExpenseList/>
                  </RoleRoute>
                }/>
                <Route path="nouveau" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <ExpenseCreate/>
                  </RoleRoute>
                }/>
                <Route path="planifie/nouveau" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <PlannedExpenseCreate/>
                  </RoleRoute>
                }/>
                <Route path="planifie/modifier/:id" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <PlannedExpenseCreate/>
                  </RoleRoute>
                }/>
                <Route path="planifie" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <PlannedExpensesList/>
                  </RoleRoute>
                }/>
                <Route path="planifie/:id" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <PlannedExpenseDetail/>
                  </RoleRoute>
                }/>
              </Route>

              {/* === TRANSACTIONS - Admin + Vendeur === */}
              <Route path='transactions'>
                <Route path=':id' element={<TransactionDetail />} />
              </Route>

              {/* === STATISTIQUES - Admin uniquement === */}
              <Route path="statistiques">
                <Route index element={
                  <RoleRoute allowedRoles={['admin']}>
                    <SalesStatistics/>
                  </RoleRoute>
                } />
                <Route path="financieres" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <FinancialStatistics/>
                  </RoleRoute>
                } />
              </Route>

              {/* === UTILISATEURS - Admin uniquement === */}
              <Route path="utilisateurs">
                <Route index element={
                  <RoleRoute allowedRoles={['admin']}>
                    <UsersPage/>
                  </RoleRoute>
                } />
              </Route>

              {/* === COMPTAGES - Admin + Vendeur === */}
              <Route path='comptages'>
                <Route index element={<CashCountList/>} />
                <Route path='nouveau' element={<CashCountForm />} />
                <Route path=':id' element={<CashCountDetail/>} />
                <Route path=':id/modifier' element={<CashCountForm />} />
              </Route>

              {/* === PARAMÈTRES - Admin uniquement === */}
              <Route path='parametres'>
                <Route index element={
                  <RoleRoute allowedRoles={['admin','vendeur']}>
                    <CompanyConfiguration/>
                  </RoleRoute>
                } />
                <Route path="ai-providers" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <AiProviderConfig/>
                  </RoleRoute>
                } />
                <Route path="ai-tasks" element={
                  <RoleRoute allowedRoles={['admin']}>
                    <AiTaskHistory/>
                  </RoleRoute>
                } />
              </Route>

              {/* === JOURNAUX D'ACTIVITÉ - Admin uniquement === */}
              <Route path='journaux-activite'>
                <Route index element={
                  <RoleRoute allowedRoles={['admin']}>
                    <ActivityLogs/>
                  </RoleRoute>
                } />
              </Route>

              {/* Route 404 pour les pages protégées */}
              <Route path="*" element={<Navigate to="/dashboard" replace />} />
            </Route>

            {/* Route 404 globale */}
            <Route path="*" element={<Navigate to="/login" replace />} />
          </Routes>
          </AiTaskProvider>
        </AuthProvider>
      </ThemeProvider>
    </BrowserRouter>
  );
}

export default App;
