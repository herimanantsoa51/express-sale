// ============================================
// src/App.jsx
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

// Layout & routes protégées
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/layout/Layout';

// Pages publiques
import Login from './pages/Login';

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

// Dépenses
import ExpenseList from './pages/Expenses/ExpenseList';
import ExpenseCreate from './pages/Expenses/ExpenseCreate';

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
import InventoryReconciliationForm from './pages/StockMovement/InventoryReconciliationForm';
// Styles globaux
import './styles/variables.css';
import './styles/reset.css';
import './styles/global.css';

function App() {
  return (

    <ThemeProvider>
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
      <AuthProvider>
        <BrowserRouter>
          <Routes>
            {/* Route publique */}
            <Route path="/login" element={<Login />} />

            {/* Routes protégées */}
            <Route
              path="/"
              element={
                <ProtectedRoute>
                  <Layout />
                </ProtectedRoute>
              }
            >
              {/* Dashboard */}
              <Route index element={<Navigate to="/dashboard" replace />} />
              <Route path="dashboard" element={<Dashboard />} />
              
              {/* === PRODUITS === */}
              <Route path="produits">
                <Route index element={<ProductsList />} />
                <Route path="nouveau" element={<ProductForm />} />
                <Route path=":id" element={<ProductDetails />} />
                <Route path=":id/modifier" element={<ProductForm />} />
                <Route path=":productId/variante/nouvelle" element={<VariantForm />} />
                <Route path=":productId/variante/:variantId/modifier" element={<VariantForm />} />
              </Route>
              <Route path="fournisseurs">
                <Route index element={<SuppliersList />} />
                <Route path="nouveau" element={<SupplierForm />} />
                <Route path=":id" element={<SupplierDetails />} />
                <Route path=":id/modifier" element={<SupplierForm />} />
              </Route>

              <Route path="transitaires">
                <Route index element={<FreightForwardersList />} />
                <Route path="nouveau" element={<FreightForwarderForm />} />
                <Route path=":id" element={<FreightForwarderDetails />} />
                <Route path=":id/modifier" element={<FreightForwarderForm />} />
              </Route>

              <Route path="reapprovisionnements">
                <Route index element={<StockReceiptList/>}/>
                <Route path='nouveau' element={<StockReceiptForm />} />
                <Route path=':id' element={<StockReceiptDetails/>}/>
                <Route path=':id/evaluation' element={<StockReceiptRating/>}/>
                <Route path=':id/paiements' element={<StockReceiptPayment/>}/>
                <Route path=':id/cout-repartition' element={<CostAllocation/>}/>
                <Route path=':id/cout-repartition/detail' element={<CostAllocationView/>}/>

              </Route>
              <Route path="localisations-variantes">
                <Route index element={<ProductVariantLocationsList />} />
                <Route path="nouvelle" element={<ProductVariantLocationForm />} />
                <Route path=":id/modifier" element={<ProductVariantLocationForm />} />
              </Route>
              

              {/* === VUE PAR LOCATION === */}
              <Route path="locations/:locationId/variantes" element={<LocationVariantsView />} />

              {/* === MOUVEMENTS DE STOCK === */}
              <Route path="mouvements-stock">
                <Route index element={<StockMovementList />} />
                <Route path="transfert" element={<StockTransferForm />} />
                {/* <Route path="ajustement" element={<StockAdjustmentForm />} /> */}
                <Route path="perte" element={<StockLossForm />} />
                <Route path="reconciliation" element={<InventoryReconciliationForm />} />
              </Route>

              {/* === LOCATIONS (Emplacements de stockage) === */}
              <Route path="locations">
                <Route index element={<LocationsList />} />
                <Route path="nouveau" element={<LocationForm />} />
                <Route path=":id" element={<LocationDetail />} />
                <Route path=":id/modifier" element={<LocationForm />} />
              </Route>
              <Route path="clients">
                <Route index element={<CustomerList/>} />
                <Route path=":id" element={<CustomerDetails/>} />
              </Route>

              {/* === VENTES === */}
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
              <Route path='comptes'>
                <Route index element={<AccountsList/>}/>
                <Route path="nouveau" element={<AccountForm />} />
                <Route path=":id" element={<AccountDetail />} />
                <Route path=":id/modifier" element={<AccountForm />} />
                <Route path="transfert" element={<AccountTransfer />} />
                <Route path='conversion' element={<CurrencyRates/>}/>
              </Route>
              <Route path="depenses">
                <Route index element={<ExpenseList/>}/>
                <Route path="nouveau" element={<ExpenseCreate/>}/>
              </Route>
              <Route path='transactions'>
                <Route path=':id' element={<TransactionDetail />} />
              </Route>
              <Route path="statistiques">
                <Route index element={<SalesStatistics/>} />
              </Route>
              <Route path="utilisateurs">
                <Route index element={<UsersPage/>} />
              </Route>
              <Route path='comptages'>
                <Route index element={<CashCountList/>} />
                <Route path='nouveau' element={<CashCountForm />} />
                <Route path=':id' element={<CashCountDetail/>} />
                <Route path=':id/modifier' element={<CashCountForm />} />
              </Route>
              <Route path='parametres'>
                <Route index element={<CompanyConfiguration/>} />
              </Route>
              

              {/* Route 404 pour les pages protégées */}
              <Route path="*" element={<Navigate to="/dashboard" replace />} />
            </Route>
            {/* Route 404 globale */}
            <Route path="*" element={<Navigate to="/login" replace />} />
          </Routes>
        </BrowserRouter>
      </AuthProvider>
     
    </ThemeProvider>
  );
}

export default App;