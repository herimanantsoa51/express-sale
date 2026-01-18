// ============================================
// src/App.jsx
// ============================================

import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { ThemeProvider } from './context/ThemeContext';
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

import SuppliersList from './pages/Suppliers/SupplierList';
import SupplierDetails from './pages/Suppliers/SupplierDetails';
import SupplierForm from './pages/Suppliers/SupplierForm';

import FreightForwardersList from './pages/FreightForwarders/FreightForwardersList';
import FreightForwarderDetails from './pages/FreightForwarders/FreightForwarderDetails';
import FreightForwarderForm from './pages/FreightForwarders/FreightForwarderForm';
import StockReceiptForm from './pages/StockReceipt/StockReceiptForm';
import StockReceiptDetails from './pages/StockReceipt/StockReceiptDetails';
import StockReceiptList from './pages/StockReceipt/StockReceiptList';
import StockReceiptRating from './pages/StockReceipt/StockReceiptRating';

// Localisations de variantes
import ProductVariantLocationsList from './pages/ProductVariantLocations/ProductVariantLocationsList';
import ProductVariantLocationForm from './pages/ProductVariantLocations/ProductVariantLocationForm';
import LocationVariantsView from './pages/ProductVariantLocations/LocationVariantsView';

// Mouvements de stock
import StockMovementList from './pages/StockMovement/StockMovementList';
import StockTransferForm from './pages/StockMovement/StockTransferForm';
import StockAdjustmentForm from './pages/StockMovement/StockAdjustmentForm';

// Locations (Emplacements de stockage)
import { LocationsList, LocationDetail, LocationForm } from './pages/Locations';
import CustomerList from './pages/Customers/CustomerList';
import CustomerDetails from './pages/Customers/CustomerDetails';

// Ventes
import QuickSalePage from './pages/Sales/QuickSalePage';
import ImmediateSalesList from './pages/Sales/ImmediateSalesList';
import ImmediateSaleDetail from './pages/Sales/ImmediateSaleDetail';
import CreditListPage from './pages/Sales/CreditLIstPage';
import CreditDetailPage from './pages/Sales/CreditDetailPage';
import AccountForm from './pages/Accounts/AccountForm';
// Import styles globaux
import './styles/variables.css';
import './styles/reset.css';
import './styles/global.css';
import ReservationsPage from './pages/Sales/ReservationsPage';
import ReservationDetailPage from './pages/Sales/ReservationDetailPage';
import AccountsList from './pages/Accounts/AccountsList';
import AccountDetail from './pages/Accounts/AccountDetail';
import AccountTransfer from './pages/Accounts/AccountTransfer';
import ExpenseList from './pages/Expenses/ExpenseList';
import ExpenseCreate from './pages/Expenses/ExpenseCreate';
import TransactionDetail from './pages/Transactions/TransactionDetail';
import CurrencyRates from './pages/Accounts/CurrencyRates';
import SalesStatistics from './pages/Statistics/SaleStatisctics';
import UsersPage from './pages/Users/userPage';
import CashCountDetail from './pages/CashCount/CashCountDetail';
import CashCountForm from './pages/CashCount/CashCountForm';
import CashCountList from './pages/CashCount/CashCountList';
import StockReceiptPayment from './pages/StockReceipt/StockReceiptPayment';
function App() {
  return (
    <ThemeProvider>
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
                <Route path="ajustement" element={<StockAdjustmentForm />} />
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