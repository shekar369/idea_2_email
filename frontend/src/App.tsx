import React from 'react';
import { Routes, Route, Navigate, Outlet, Link } from 'react-router-dom';
import { useAuth } from './contexts/AuthContext';

import React from 'react';
import { Routes, Route, Navigate, Outlet, Link } from 'react-router-dom';
import { useAuth } from './contexts/AuthContext';
import Navbar from './components/Navbar'; // Import Navbar
import MainLayout from './components/layouts/MainLayout'; // Import MainLayout

// Import Page Components
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import SettingsPage from './pages/SettingsPage';
import EmailWriterPage from './pages/EmailWriterPage';
import NotFoundPage from './pages/NotFoundPage';


// ProtectedRoute component
const ProtectedRoute: React.FC = () => {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    // Optional: show a loading spinner or a blank page while auth state is being determined
    return <div className="p-4">Loading authentication status...</div>;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return <Outlet />; // Renders the child route's element
};


function App() {
  return (
    <Routes>
      <Route element={<MainLayout />}> {/* All routes below will use MainLayout */}
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />

        <Route element={<ProtectedRoute />}>
          <Route path="/" element={<EmailWriterPage />} />
          <Route path="/settings" element={<SettingsPage />} />
        </Route>
      </Route>

      <Route path="*" element={<NotFoundPage />} /> {/* Catch-all for 404 */}
    </Routes>
  );
}

export default App;
