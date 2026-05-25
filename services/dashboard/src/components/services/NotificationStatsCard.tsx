import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Mail, MessageSquare, FileText, BarChart3 } from 'lucide-react';

interface NotificationStats {
  total: number;
  by_channel: Record<string, number>;
  by_status: Record<string, number>;
  by_channel_and_status: Record<string, Record<string, number>>;
  last_sent_at: string | null;
}

export default function NotificationStatsCard() {
  const [stats, setStats] = useState<NotificationStats | null>(null);

  const fetchStats = async () => {
    try {
      const res = await fetch('http://localhost:8005/api/notifications/stats');
      const data = await res.json();
      setStats(data);
    } catch (e) {
      setStats(null);
    }
  };

  useEffect(() => {
    fetchStats();
    const interval = setInterval(fetchStats, 5000);
    return () => clearInterval(interval);
  }, []);

  const totalSent = stats?.by_status?.sent || 0;
  const totalFailed = stats?.by_status?.failed || 0;
  const successRate = stats && stats.total > 0
    ? Math.round((totalSent / stats.total) * 100)
    : 100;

  const channels = [
    { name: 'email', icon: Mail, label: 'Email', color: 'text-indigo-400', bg: 'bg-indigo-500/10' },
    { name: 'slack', icon: MessageSquare, label: 'Slack', color: 'text-violet-400', bg: 'bg-violet-500/10' },
    { name: 'log', icon: FileText, label: 'Log', color: 'text-blue-400', bg: 'bg-blue-500/10' },
  ];

  return (
    <Card className="bg-card border-border">
      <CardHeader className="pb-3">
        <div className="flex items-center gap-2">
          <BarChart3 size={16} className="text-indigo-400" />
          <CardTitle className="text-sm font-medium uppercase tracking-wide text-muted-foreground">
            Stats Notification Dispatcher
          </CardTitle>
        </div>
      </CardHeader>

      <CardContent className="space-y-5">
        {/* Totales */}
        <div className="flex items-end justify-between border-b border-border pb-4">
          <div>
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground/60 mb-1">
              Total enviados
            </p>
            <p className="text-3xl font-light text-foreground">
              {stats?.total ?? 0}
            </p>
          </div>
          <div className="text-right">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground/60 mb-1">
              Success rate
            </p>
            <p className={`text-2xl font-light font-mono ${
              successRate >= 95 ? 'text-emerald-400' : successRate >= 80 ? 'text-amber-400' : 'text-red-400'
            }`}>
              {successRate}%
            </p>
          </div>
        </div>

        {/* Por canal */}
        <div>
          <p className="text-[10px] uppercase tracking-wide text-muted-foreground/60 mb-2">
            Por canal
          </p>
          <div className="grid grid-cols-3 gap-2">
            {channels.map(({ name, icon: Icon, label, color, bg }) => {
              const total = stats?.by_channel?.[name] || 0;
              const sent = stats?.by_channel_and_status?.[name]?.sent || 0;
              const failed = stats?.by_channel_and_status?.[name]?.failed || 0;

              return (
                <div key={name} className={`${bg} rounded-md p-2.5 border border-border/40`}>
                  <div className="flex items-center gap-1.5 mb-1.5">
                    <Icon size={11} className={color} />
                    <span className="text-[10px] font-medium uppercase text-muted-foreground tracking-wide">
                      {label}
                    </span>
                  </div>
                  <p className="text-lg font-semibold text-foreground leading-none">
                    {total}
                  </p>
                  <div className="flex gap-2 mt-1.5 text-[9px] font-mono">
                    <span className="text-emerald-400">✓{sent}</span>
                    {failed > 0 && (
                      <span className="text-red-400">✗{failed}</span>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Last sent */}
        {stats?.last_sent_at && (
          <div className="text-[10px] text-muted-foreground/60 font-mono border-t border-border pt-3">
            Última: {new Date(stats.last_sent_at).toLocaleTimeString('es-CO')}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
