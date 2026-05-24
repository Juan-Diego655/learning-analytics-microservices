import { Bell } from 'lucide-react';
import { useLocation } from 'react-router-dom';
import { useActiveCriticalIncidents } from '../hooks/useActiveCriticalIncidents';
import { Button } from '@/components/ui/button';

export default function TopBar() {
  const location = useLocation();
  const { count } = useActiveCriticalIncidents();

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
    <header className="h-14 border-b border-border bg-gradient-to-r from-[#151226] via-card/80 to-card backdrop-blur flex items-center justify-between px-6 sticky top-0 z-10 shrink-0">
      <div className="flex items-center gap-4">
        <h2 className="text-sm font-semibold text-foreground tracking-wide">{getTitle()}</h2>
      </div>
      <div className="flex items-center gap-2">
        <Button variant="ghost" size="icon" className="relative h-9 w-9">
          <Bell size={16} className="text-muted-foreground" />
          {count > 0 && (
            <span className="absolute top-2 right-2 w-2 h-2 bg-destructive rounded-full animate-pulse border border-card" />
          )}
        </Button>
      </div>
    </header>
  );
}