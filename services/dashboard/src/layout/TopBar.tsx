// src/components/topbar/TopBar.tsx
import { useLocation } from 'react-router-dom';
import NotificationBell from '../components/topbar/NotificationBell';

export default function TopBar() {
  const location = useLocation();

  const getTitle = () => {
    const path = location.pathname;
    if (path.includes('dashboard')) return 'Vista General';
    if (path.includes('incidents')) return 'Gestión de Incidentes';
    if (path.includes('rules')) return 'Catálogo de Reglas';
    if (path.includes('metrics')) return 'Telemetría';
    if (path.includes('services')) return 'Control de Servicios';
    return 'Panel';
  };

  return (
    // CAMBIO: Se subió el z-10 a z-40 para evitar que los elementos del dashboard se encimen sobre el dropdown
    <header className="h-14 border-b border-border bg-gradient-to-r from-[#151226] via-card/80 to-card backdrop-blur flex items-center justify-between px-6 sticky top-0 z-40 shrink-0">
      <div className="flex items-center gap-4">
        <h2 className="text-sm font-semibold text-foreground tracking-wide">{getTitle()}</h2>
      </div>
      <div className="flex items-center gap-2">
        <NotificationBell />
      </div>
    </header>
  );
}