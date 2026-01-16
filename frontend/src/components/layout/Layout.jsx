
// ============================================
// components/layout/Layout.jsx
// ============================================

import { Outlet } from 'react-router-dom';
import Header from './Header';
import Sidebar from './Sidebar';
import '../../styles/Layout.css';

const Layout = () => {
  return (
    <div className="layout">
      <Header />
      <div className="layout-container">
        <Sidebar />
        <main className="layout-content">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default Layout;

/* Layout.css */
/*
.layout {
  min-height: 100vh;
  background-color: var(--bg-secondary);
}

.layout-container {
  display: flex;
}

.layout-content {
  flex: 1;
  margin-left: var(--sidebar-width);
  padding: var(--spacing-lg);
  min-height: calc(100vh - var(--header-height));
  animation: fadeIn 0.3s ease-in-out;
}

@media (max-width: 768px) {
  .layout-content {
    margin-left: 0;
    padding: var(--spacing-md);
  }
}
*/