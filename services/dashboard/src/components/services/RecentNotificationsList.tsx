import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Mail, MessageSquare, FileText, CheckCircle2, XCircle, BellRing } from 'lucide-react';
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
    default: return BellRing;
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

export default function RecentNotificationsList() {
  const [notifications, setNotifications] = useState<NotificationSent[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchNotifications = async () => {
    try {
      const res = await fetch('http://localhost:8005/api/notifications/recent?limit=12');
      const data = await res.json();
      setNotifications(data.notifications || []);
    } catch (e) {
      setNotifications([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchNotifications();
    const interval = setInterval(fetchNotifications, 5000);
    return () => clearInterval(interval);
  }, []);

  return (
    <Card className="bg-card border-border">
      <CardHeader className="flex flex-row items-center justify-between pb-3 space-y-0">
        <div className="flex items-center gap-2">
          <BellRing size={16} className="text-indigo-400" />
          <CardTitle className="text-sm font-medium uppercase tracking-wide text-muted-foreground">
            Notificaciones enviadas
          </CardTitle>
        </div>
        <span className="text-[10px] font-mono text-muted-foreground/60">
          actualiza cada 5s
        </span>
      </CardHeader>

      <CardContent className="pt-0">
        {loading ? (
          <div className="py-8 text-center text-xs text-muted-foreground/60">
            Cargando notificaciones...
          </div>
        ) : notifications.length === 0 ? (
          <div className="py-12 text-center">
            <BellRing size={24} className="mx-auto mb-2 text-muted-foreground/30" />
            <p className="text-xs text-muted-foreground/60">
              Aún no se han enviado notificaciones.
            </p>
            <p className="text-[10px] text-muted-foreground/40 mt-1">
              Cuando un incidente dispare, aparecerán aquí.
            </p>
          </div>
        ) : (
          <div className="space-y-1.5 max-h-[400px] overflow-y-auto pr-1">
            {notifications.map((n) => {
              const Icon = channelIcon(n.channel);
              const isFailed = n.status === 'failed';

              return (
                <div
                  key={n.id}
                  className="flex items-start gap-3 p-2.5 rounded-md hover:bg-muted/30 transition-colors group"
                >
                  {/* Icono del canal */}
                  <div className={`mt-0.5 ${channelColor(n.channel)}`}>
                    <Icon size={14} />
                  </div>

                  {/* Contenido principal */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      {/* Dot de severity */}
                      <span className={`w-1.5 h-1.5 rounded-full ${severityDot(n.severity)}`} />

                      {/* Rule code en mono */}
                      <span className="text-xs font-mono font-semibold text-foreground">
                        {n.rule_code}
                      </span>

                      {/* Channel uppercase tag */}
                      <span className="text-[10px] uppercase font-medium tracking-wide text-muted-foreground/80">
                        {n.channel}
                      </span>

                      {/* Status badge */}
                      {isFailed ? (
                        <Badge className="bg-red-500/10 text-red-400 border-red-500/20 text-[10px] font-mono py-0 px-1.5 h-4">
                          <XCircle size={9} className="mr-0.5" /> failed
                        </Badge>
                      ) : (
                        <Badge className="bg-emerald-500/10 text-emerald-400 border-emerald-500/20 text-[10px] font-mono py-0 px-1.5 h-4">
                          <CheckCircle2 size={9} className="mr-0.5" /> sent
                        </Badge>
                      )}
                    </div>

                    {/* Recipient */}
                    <p className="text-[11px] text-muted-foreground/80 mt-0.5 truncate font-mono">
                      → {n.recipient}
                    </p>

                    {/* Error message si falló */}
                    {isFailed && n.error_message && (
                      <p className="text-[10px] text-red-400/80 mt-0.5 italic">
                        ⚠ {n.error_message}
                      </p>
                    )}
                  </div>

                  {/* Timestamp */}
                  <span className="text-[10px] text-muted-foreground/60 font-mono shrink-0">
                    {getRelativeTime(n.sent_at)}
                  </span>
                </div>
              );
            })}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
