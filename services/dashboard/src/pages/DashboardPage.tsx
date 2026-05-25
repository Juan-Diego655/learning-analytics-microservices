import { useEffect, useState } from 'react';
import { api } from '../lib/api';
import { Activity, BarChart3, AlertTriangle, AlertOctagon } from 'lucide-react';
import { ComposedChart, Bar, Line, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid } from 'recharts';

import CriticalBanner from '../components/dashboard/CriticalBanner';
import ServiceHealthGrid from '../components/dashboard/ServiceHealthGrid';
import ActivityHeatmap from '../components/dashboard/ActivityHeatmap';
import IncidentsTimeline from '../components/dashboard/IncidentsTimeline';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

export default function DashboardPage() {
  const [metrics, setMetrics] = useState<any>(null);
  const [incidents, setIncidents] = useState<any[]>([]);

  const fetchData = async () => {
    try {
      const [metricsRes, incidentsRes] = await Promise.all([
        api.get('http://localhost:8003/api/metrics/summary').catch(() => ({ data: {} })),
        api.get('http://localhost:8004/api/incidents?limit=5').catch(() => ({ data: { incidents: [] } }))
      ]);
      setMetrics(metricsRes.data);
      setIncidents(incidentsRes.data.incidents || []);
    } catch (error) {
      console.error("Error cargando métricas en el panel central", error);
    }
  };

  useEffect(() => {
    fetchData();
    const interval = setInterval(fetchData, 10000);
    return () => clearInterval(interval);
  }, []);

  const activeIncidents = incidents.filter(i => i.status !== 'resolved');
  const criticalIncidents = activeIncidents.filter(i => i.severity === 'critical');

  const lmsMoodle = metrics?.events_by_lms?.moodle || 0;
  const lmsCanvas = metrics?.events_by_lms?.canvas || 0;
  const lmsOpenEdx = metrics?.events_by_lms?.openedx || 0;
  const lmsPromedio = Math.round((lmsMoodle + lmsCanvas + lmsOpenEdx) / 3);

  const chartData = [
    { name: 'Moodle', eventos: lmsMoodle, promedio: lmsPromedio },
    { name: 'Canvas', eventos: lmsCanvas, promedio: lmsPromedio },
    { name: 'Open edX', eventos: lmsOpenEdx, promedio: lmsPromedio },
  ];

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <CriticalBanner incidents={criticalIncidents} />

      {/* FILA 1: KPIs asimétricos estilo "Loud" */}
      <div className="grid grid-cols-12 gap-6">

        {/* KPI Principal Gigante */}
        <Card className="col-span-12 md:col-span-6 bg-card border-border p-6 shadow-sm flex flex-col justify-between">
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Eventos procesados
            </span>
            <Activity size={18} className="text-indigo-400" />
          </div>
          <div className="mt-4 flex items-baseline gap-4 flex-wrap">
            <span className="text-5xl md:text-6xl font-light text-foreground tracking-tight select-all">
              {metrics?.events_processed_total?.toLocaleString() || '0'}
            </span>
            <Badge className="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-full text-xs font-mono py-0.5 px-2 select-none">
              +12% vs hace 1h
            </Badge>
          </div>
          <p className="text-xs text-muted-foreground mt-2 font-medium">
            Total procesados · últimas 24h
          </p>
        </Card>

        {/* KPI Ventanas */}
        <Card className="col-span-4 md:col-span-2 bg-card border-border p-4 shadow-sm flex flex-col justify-between">
          <div className="flex items-center justify-between text-muted-foreground">
            <span className="text-[11px] font-medium uppercase tracking-wide">Ventanas</span>
            <BarChart3 size={14} className="text-blue-400" />
          </div>
          <div className="my-2">
            <span className="text-2xl font-semibold text-foreground">{metrics?.active_windows ?? '—'}</span>
          </div>
          <p className="text-[10px] text-muted-foreground/60">Análisis agregados</p>
        </Card>

        {/* KPI Alertas Activas */}
        <Card className="col-span-4 md:col-span-2 bg-card border-border p-4 shadow-sm flex flex-col justify-between">
          <div className="flex items-center justify-between text-muted-foreground">
            <span className="text-[11px] font-medium uppercase tracking-wide">Alertas activas</span>
            <AlertTriangle size={14} className={activeIncidents.length > 0 ? "text-amber-500" : "text-muted-foreground/40"} />
          </div>
          <div className="my-2">
            <span className="text-2xl font-semibold text-foreground">{activeIncidents.length}</span>
          </div>
          <p className="text-[10px] text-muted-foreground/60">Pendientes de revisión</p>
        </Card>

        {/* KPI Críticas */}
        <Card className="col-span-4 md:col-span-2 bg-card border-border p-4 shadow-sm flex flex-col justify-between">
          <div className="flex items-center justify-between text-muted-foreground">
            <span className="text-[11px] font-medium uppercase tracking-wide">Críticas</span>
            <AlertOctagon size={14} className={criticalIncidents.length > 0 ? "text-red-500 animate-pulse" : "text-muted-foreground/40"} />
          </div>
          <div className="my-2">
            <span className="text-2xl font-semibold text-foreground">{criticalIncidents.length}</span>
          </div>
          <p className="text-[10px] text-muted-foreground/60">Bloqueo inmediato</p>
        </Card>
      </div>

      {/* FILA 2: ComposedChart por LMS + Salud de Servicios */}
      <div className="grid grid-cols-12 gap-6">
        <Card className="col-span-12 md:col-span-8 bg-card border-border p-6">
          <h3 className="text-sm font-medium uppercase tracking-wide text-muted-foreground mb-6">
            Rendimiento y volumen por LMS
          </h3>
          <div className="h-[320px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <ComposedChart data={chartData} margin={{ top: 10, right: 5, left: -25, bottom: 0 }}>
                <defs>
                  <linearGradient id="gradientLMS" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#6366f1" stopOpacity={1} />
                    <stop offset="100%" stopColor="#7c3aed" stopOpacity={0.4} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="#252533" vertical={false} opacity={0.5} />
                <XAxis dataKey="name" stroke="#71717a" fontSize={11} tickLine={false} axisLine={false} />
                <YAxis stroke="#71717a" fontSize={11} tickLine={false} axisLine={false} />
                <Tooltip
                  cursor={{ fill: '#252533', opacity: 0.3 }}
                  content={({ active, payload, label }) => {
                    if (active && payload && payload.length) {
                      return (
                        <div className="bg-card border border-border p-3 rounded-lg shadow-xl text-xs font-mono space-y-1">
                          <p className="font-semibold text-foreground">{label}</p>
                          <p className="text-indigo-400">Eventos: {payload[0].value?.toLocaleString()}</p>
                          {payload[1] && (
                            <p className="text-violet-400">Promedio: {payload[1].value?.toLocaleString()}</p>
                          )}
                        </div>
                      );
                    }
                    return null;
                  }}
                />
                <Bar dataKey="eventos" fill="url(#gradientLMS)" radius={[4, 4, 0, 0]} isAnimationActive={true} />
                <Line type="monotone" dataKey="promedio" stroke="#a78bfa" strokeWidth={2} dot={false} />
              </ComposedChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <div className="col-span-12 md:col-span-4">
          <ServiceHealthGrid />
        </div>
      </div>

      {/* FILA 3: Heatmap + Timeline */}
      <div className="grid grid-cols-12 gap-6">
        <ActivityHeatmap />
        <IncidentsTimeline />
      </div>
    </div>
  );
}