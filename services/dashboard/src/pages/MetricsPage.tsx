import { useEffect, useState } from 'react';
import { api } from '../lib/api';
import { Activity } from 'lucide-react';

interface MetricWindow {
  id: number;
  window_start: string;
  window_end: string;
  institution_id?: string;
  course_external_id?: string;
  event_type?: string;
  event_count?: number;
  unique_students?: number;
}

interface MetricsData {
  total_windows: number;
  total_events_processed: number;
  windows: MetricWindow[];
}

export default function MetricsPage() {
  const [data, setData] = useState<MetricsData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get<MetricsData>('http://localhost:8003/api/metrics/windows')
      .then(res => setData(res.data))
      .catch(err => console.error(err))
      .finally(() => setLoading(false));
  }, []);

  const formatTime = (iso?: string) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <h1 className="text-2xl font-semibold tracking-tight text-foreground">Métricas de Telemetría</h1>

      {loading ? (
        <div className="text-sm text-muted-foreground">Cargando ventanas...</div>
      ) : (
        <div className="bg-card border border-border rounded-2xl p-6">
          <div className="flex items-center gap-3 mb-6">
            <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
              <Activity size={20} />
            </div>
            <div>
              <h2 className="text-xs font-medium uppercase tracking-wider text-muted-foreground">Ventanas procesadas</h2>
              <p className="text-lg font-semibold text-foreground">
                {data?.total_windows ?? 0} ventanas · {data?.total_events_processed ?? 0} eventos
              </p>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-muted-foreground uppercase bg-muted/20 border-b border-border">
                <tr>
                  <th className="px-4 py-3 font-medium">ID</th>
                  <th className="px-4 py-3 font-medium">Inicio</th>
                  <th className="px-4 py-3 font-medium">Fin</th>
                  <th className="px-4 py-3 font-medium">Curso</th>
                  <th className="px-4 py-3 font-medium">Tipo</th>
                  <th className="px-4 py-3 font-medium text-right">Eventos</th>
                  <th className="px-4 py-3 font-medium text-right">Estudiantes</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {data?.windows?.map((w) => (
                  <tr key={w.id} className="hover:bg-muted/30 transition-colors">
                    <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{w.id}</td>
                    <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{formatTime(w.window_start)}</td>
                    <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{formatTime(w.window_end)}</td>
                    <td className="px-4 py-3 font-mono text-xs text-foreground truncate max-w-[220px]">{w.course_external_id || '—'}</td>
                    <td className="px-4 py-3">
                      {w.event_type && (
                        <span className="text-xs px-2 py-0.5 rounded bg-primary/10 text-primary border border-primary/20">
                          {w.event_type}
                        </span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-right text-foreground font-medium tabular-nums">{w.event_count ?? 0}</td>
                    <td className="px-4 py-3 text-right text-foreground tabular-nums">{w.unique_students ?? 0}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}