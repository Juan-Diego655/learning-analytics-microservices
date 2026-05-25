// src/components/topbar/NotificationBell.tsx
import { useEffect, useState, useRef } from 'react';
import { Bell, Mail, MessageSquare, FileText, CheckCircle2, XCircle, BellOff } from 'lucide-react';
import { getRelativeTime } from '../../lib/formatters';

interface NotificationSent {
  id: number;
  incident_id: string;
  rule_code: string;
  severity: 'critical' | 'warning' | 'info';
  channel: 'email' | 'slack' | 'log';
  recipient: string;
  status: 'sent' | 'failed';
  error_message: string | null;
  sent_at: string;
}

const channelIcon = (channel: string) => {
  switch (channel) {
    case 'email': return Mail;
    case 'slack': return MessageSquare;
    case 'log': return FileText;
    default: return Bell;
  }
};

const channelColor = (channel: string) => {
  switch (channel) {
    case 'email': return 'text-indigo-400';
    case 'slack': return 'text-violet-400';
    case 'log': return 'text-blue-400';
    default: return 'text-muted-foreground';
  }
};

const severityDot = (severity: string) => {
  switch (severity) {
    case 'critical': return 'bg-red-500';
    case 'warning': return 'bg-amber-500';
    default: return 'bg-blue-400';
  }
};

const STORAGE_KEY = 'noctua_last_read_notif_id';

export default function NotificationBell() {
  const [notifications, setNotifications] = useState<NotificationSent[]>([]);
  const [open, setOpen] = useState(false);
  const [lastReadId, setLastReadId] = useState<number>(() => {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? parseInt(stored, 10) : 0;
  });
  const ref = useRef<HTMLDivElement>(null);

  const fetchNotifications = async () => {
    try {
      const res = await fetch('http://localhost:8005/api/notifications/recent?limit=15');
      const data = await res.json();
      setNotifications(data.notifications || []);
    } catch (e) {
      // silencioso
    }
  };

  useEffect(() => {
    fetchNotifications();
    const interval = setInterval(fetchNotifications, 5000);
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    if (open) document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const unreadCount = notifications.filter(n => n.id > lastReadId).length;
  const hasFailed = notifications.some(n => n.id > lastReadId && n.status === 'failed');

  const handleOpen = () => {
    setOpen(!open);
    if (!open && notifications.length > 0) {
      const maxId = Math.max(...notifications.map(n => n.id));
      setTimeout(() => {
        setLastReadId(maxId);
        localStorage.setItem(STORAGE_KEY, String(maxId));
      }, 300);
    }
  };

  return (
    <div ref={ref} className="relative">
      <button
        onClick={handleOpen}
        className="relative p-2 rounded-md hover:bg-muted/40 transition-colors group"
        aria-label="Notificaciones"
      >
        <Bell
          size={18}
          className={`transition-colors ${
            unreadCount > 0
              ? 'text-foreground'
              : 'text-muted-foreground group-hover:text-foreground'
          }`}
        />
        {unreadCount > 0 && (
          // CORRECCIÓN: Lógica condicional para el ancho/padding del badge.
          // Usamos 'w-4 h-4 rounded-full' para garantizar círculos perfectos en números únicos
          // y evitar que se compriman. Usamos padding para '9+'.
          <span className={`absolute top-0.5 right-0.5 h-4 rounded-full text-[9px] font-mono font-semibold flex items-center justify-center ${
            // Condicional para el color
            hasFailed
              ? 'bg-red-500 text-white'
              : 'bg-indigo-500 text-white'
          } ${
            // Condicional para el ancho y padding
            unreadCount > 9 ? 'px-1.5 min-w-4' : 'w-4'
          } animate-in zoom-in duration-200`}>
            {unreadCount > 9 ? '9+' : unreadCount}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 top-full mt-2 w-[380px] bg-card border border-border rounded-lg shadow-2xl z-50 animate-in fade-in slide-in-from-top-2 duration-150">
          <div className="flex items-center justify-between px-4 py-3 border-b border-border">
            <div>
              <h3 className="text-sm font-semibold text-foreground">
                Notificaciones
              </h3>
              <p className="text-[10px] text-muted-foreground/70 mt-0.5 font-mono">
                {notifications.length === 0
                  ? 'Sin actividad'
                  : `${notifications.length} recientes · actualiza cada 5s`}
              </p>
            </div>
            <span className="text-[10px] font-mono text-muted-foreground/60 bg-muted/30 px-2 py-1 rounded">
              :8005
            </span>
          </div>

          {notifications.length === 0 ? (
            <div className="py-12 text-center px-4">
              <BellOff size={28} className="mx-auto mb-3 text-muted-foreground/30" />
              <p className="text-xs text-muted-foreground/70">
                Aún no hay notificaciones
              </p>
              <p className="text-[10px] text-muted-foreground/40 mt-1">
                Aparecerán aquí cuando dispare un incidente
              </p>
            </div>
          ) : (
            <div className="max-h-[420px] overflow-y-auto">
              {notifications.map((n) => {
                const Icon = channelIcon(n.channel);
                const isFailed = n.status === 'failed';

                return (
                  <div
                    key={n.id}
                    className="flex items-start gap-3 px-4 py-3 hover:bg-muted/20 border-b border-border/40 last:border-b-0 transition-colors"
                  >
                    <div className={`mt-0.5 ${channelColor(n.channel)}`}>
                      <Icon size={14} />
                    </div>

                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-1.5 flex-wrap">
                        <span className={`w-1.5 h-1.5 rounded-full ${severityDot(n.severity)}`} />
                        <span className="text-xs font-mono font-semibold text-foreground">
                          {n.rule_code}
                        </span>
                        <span className="text-[10px] uppercase font-medium tracking-wide text-muted-foreground/70">
                          via {n.channel}
                        </span>
                        {isFailed ? (
                          <span className="flex items-center gap-0.5 text-[10px] font-mono text-red-400 bg-red-500/10 border border-red-500/20 rounded px-1.5 h-4">
                            <XCircle size={9} /> failed
                          </span>
                        ) : (
                          <span className="flex items-center gap-0.5 text-[10px] font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded px-1.5 h-4">
                            <CheckCircle2 size={9} /> sent
                          </span>
                        )}
                      </div>

                      <p className="text-[11px] text-muted-foreground/80 mt-1 truncate font-mono">
                        → {n.recipient}
                      </p>

                      {isFailed && n.error_message && (
                        <p className="text-[10px] text-red-400/80 mt-0.5 italic">
                          ⚠ {n.error_message}
                        </p>
                      )}
                    </div>

                    <span className="text-[10px] text-muted-foreground/60 font-mono shrink-0 pt-0.5">
                      {getRelativeTime(n.sent_at)}
                    </span>
                  </div>
                );
              })}
            </div>
          )}

          {notifications.length > 0 && (
            <div className="px-4 py-2.5 border-t border-border bg-muted/10 text-center">
              {/* ¡Aquí estaba el error! Faltaba abrir la etiqueta <a> */}
              <a
                href="/services"
                className="text-[11px] text-indigo-400 hover:text-indigo-300 font-medium transition-colors"
              >
                Ver historial completo en Servicios →
              </a>
            </div>
          )}
        </div>
      )}
    </div>
  );
}