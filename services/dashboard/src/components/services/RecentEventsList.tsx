import { useEffect, useState } from 'react';
import { api } from '../../lib/api';
import { getRelativeTime } from '../../lib/formatters';
import { Database } from 'lucide-react';
import { Card } from '@/components/ui/card';

export default function RecentEventsList() {
  const [events, setEvents] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchRecent = async () => {
      try {
        const res = await api.get('http://localhost:8002/api/events/recent');
        setEvents(res.data.events?.slice(0, 10) || []);
      } catch (e) {
        console.error(e);
      } finally {
        setLoading(false);
      }
    };
    fetchRecent();
    const interval = setInterval(fetchRecent, 5000);
    return () => clearInterval(interval);
  }, []);

  return (
    <Card className="p-6 h-full flex flex-col">
      <div className="flex items-center gap-2 mb-4 text-muted-foreground">
        <Database size={18} />
        <h3 className="text-sm font-medium uppercase tracking-wide">Últimos Eventos</h3>
      </div>

      <div className="flex-1 overflow-y-auto pr-2 space-y-3">
        {loading && events.length === 0 ? (
          <p className="text-sm text-muted-foreground animate-pulse text-center mt-10">Cargando...</p>
        ) : events.map((evt, i) => (
          <div key={i} className="bg-muted/30 p-3 rounded-lg border border-border flex flex-col gap-2">
            <div className="flex items-center justify-between">
              <span className="text-xs font-semibold text-primary">{evt.canonical_event_type || evt.event_type}</span>
              <span className="text-[10px] text-muted-foreground font-mono">{getRelativeTime(evt.occurred_at || evt.created_at)}</span>
            </div>
            <div className="flex flex-wrap gap-2 text-[11px]">
              <span className="px-2 py-0.5 bg-background border border-border rounded font-mono">
                LMS: {evt.lms_source}
              </span>
              <span className="px-2 py-0.5 bg-background border border-border rounded font-mono">
                User: {String(evt.student_external_id || evt.user_external_id || '').slice(0,8)}
              </span>
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}