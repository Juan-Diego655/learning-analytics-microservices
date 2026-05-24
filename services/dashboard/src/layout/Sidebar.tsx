import { Link, useLocation } from 'react-router-dom';
import { LayoutDashboard, AlertTriangle, BookOpen, Server, Activity, LogOut } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useActiveCriticalIncidents } from '../hooks/useActiveCriticalIncidents';

export default function Sidebar() {
  const location = useLocation();
  const navigate = useNavigate();
  const { count } = useActiveCriticalIncidents();

  const userStr = localStorage.getItem('user');
  const user = userStr ? JSON.parse(userStr) : { name: 'Usuario', email: 'user@learning.test', role: 'guest' };

  const handleLogout = () => {
    localStorage.removeItem('access_token');
    localStorage.removeItem('user');
    navigate('/login');
  };

  const links = [
    { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard, section: 'monitoreo' },
    { name: 'Incidentes', path: '/incidents', icon: AlertTriangle, section: 'monitoreo', badge: count > 0 ? count : null },
    { name: 'Métricas', path: '/metrics', icon: Activity, section: 'monitoreo' },
    { name: 'Reglas', path: '/rules', icon: BookOpen, section: 'configuracion' },
    { name: 'Servicios', path: '/services', icon: Server, section: 'configuracion' },
  ];

  const monitoreoLinks = links.filter(l => l.section === 'monitoreo');
  const configLinks = links.filter(l => l.section === 'configuracion');

  const renderLink = (link: typeof links[0]) => {
    const Icon = link.icon;
    const active = location.pathname.startsWith(link.path);
    return (
      <Link
        key={link.name}
        to={link.path}
        className={`flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium transition-colors border-l-2 ${
          active
            ? 'bg-primary/10 text-primary border-primary'
            : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground border-transparent'
        }`}
      >
        <div className="flex items-center gap-3">
          <Icon size={16} />
          {link.name}
        </div>
        {link.badge && (
          <span className="bg-destructive text-destructive-foreground text-[10px] font-bold px-1.5 py-0.5 rounded-full">
            {link.badge}
          </span>
        )}
      </Link>
    );
  };

  return (
    <aside className="w-60 bg-card border-r border-border hidden md:flex flex-col justify-between shrink-0">
      <div className="flex flex-col flex-1">
        {/* Logo header */}
        <div className="h-14 flex items-center gap-3 px-6 border-b border-border">
          <div className="w-7 h-7 rounded-md bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center font-bold text-xs text-white shadow-lg shadow-indigo-500/20">
            LA
          </div>
          <span className="font-semibold text-sm tracking-tight text-foreground">Learning Analytics</span>
        </div>

        {/* Nav */}
        <div className="flex-1 px-3 py-6 space-y-6 overflow-y-auto">
          <div>
            <h3 className="px-3 text-[10px] font-semibold text-muted-foreground/70 uppercase tracking-widest mb-2">Monitoreo</h3>
            <nav className="space-y-1">{monitoreoLinks.map(renderLink)}</nav>
          </div>
          <div>
            <h3 className="px-3 text-[10px] font-semibold text-muted-foreground/70 uppercase tracking-widest mb-2">Configuración</h3>
            <nav className="space-y-1">{configLinks.map(renderLink)}</nav>
          </div>
        </div>
      </div>

      {/* User footer */}
      <div className="p-4 border-t border-border">
        <div className="flex items-center gap-3 mb-3">
          <div className="w-8 h-8 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xs font-medium border border-primary/30">
            {user.name?.charAt(0).toUpperCase() || 'U'}
          </div>
          <div className="flex-1 overflow-hidden">
            <p className="text-xs font-medium text-foreground truncate">{user.name}</p>
            <p className="text-[10px] text-muted-foreground truncate capitalize">{user.role}</p>
          </div>
        </div>
        <button
          onClick={handleLogout}
          className="w-full flex items-center justify-center gap-2 py-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors rounded-md hover:bg-muted/50"
        >
          <LogOut size={14} />
          <span>Cerrar sesión</span>
        </button>
      </div>
    </aside>
  );
}