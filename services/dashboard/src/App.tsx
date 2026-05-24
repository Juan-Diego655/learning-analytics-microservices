import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'sonner';

import MainLayout from './layout/MainLayout';
import ProtectedRoute from './layout/ProtectedRoute';

import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import IncidentsPage from './pages/IncidentsPage';
import MetricsPage from './pages/MetricsPage';
import RulesPage from './pages/RulesPage';
import ServicesPage from './pages/ServicesPage';

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<LoginPage />} />

        <Route element={<ProtectedRoute />}>
          <Route element={<MainLayout />}>
            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            <Route path="/dashboard" element={<DashboardPage />} />
            <Route path="/incidents" element={<IncidentsPage />} />
            <Route path="/metrics" element={<MetricsPage />} />
            <Route path="/rules" element={<RulesPage />} />
            <Route path="/services" element={<ServicesPage />} />
          </Route>
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>

      <Toaster theme="dark" position="top-right" richColors />
    </BrowserRouter>
  );
}