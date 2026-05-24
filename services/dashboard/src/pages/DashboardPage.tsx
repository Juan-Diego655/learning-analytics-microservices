import { useEffect, useState } from 'react';
import { api } from '../lib/api';
import { Activity, BarChart3, AlertTriangle, AlertOctagon } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid } from 'recharts';
import CriticalBanner from '../components/dashboard/CriticalBanner';
import ServiceHealthGrid from '../components/dashboard/ServiceHealthGrid';

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
      console.error("Error fetching dashboard data", error);
    }
  };

  useEffect(() => {
    fetchData();
    const interval = setInterval(fetchData, 10000);
    return () => clearInterval(interval);
  }, []);

  const activeIncidents = incidents.filter(i => i.status !== 'resolved');
  const criticalIncidents = activeIncidents.filter(i => i.severity === 'critical');

  const chartData = [
    { name: 'Moodle', eventos: metrics?.events_by_lms?.moodle || 0 },
    { name: 'Canvas', eventos: metrics?.events_by_lms?.canvas || 0 },
    { name: 'Open edX', eventos: metrics?.events_by_lms?.openedx || 0 },
  ];

  const KpiCard = ({ title, value, icon: Icon, colorClass }: any) => (
    <div className="col-span-12 sm:col-span-6 lg:col-span-3 bg-card border border-border rounded-2xl p-6 hover:shadow-lg transition-all">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground">{title}</h3>
        <Icon size={18} className={colorClass} />
      </div>
      <p className="text-3xl font-semibold text-foreground tabular-nums">{value ?? '—'}</p>
    </div>
  );

  return (
    <div className="animate-in fade-in duration-200">
      <CriticalBanner incidents={criticalIncidents} />

      <div className="grid grid-cols-12 gap-4 mb-6">
        <KpiCard title="Eventos" value={metrics?.events_processed_total} icon={Activity} colorClass="text-indigo-400" />
        <KpiCard title="Ventanas" value={metrics?.active_windows} icon={BarChart3} colorClass="text-blue-400" />
        <KpiCard title="Alertas activas" value={activeIncidents.length} icon={AlertTriangle} colorClass={activeIncidents.length > 0 ? 'text-amber-500' : 'text-muted-foreground'} />
        <KpiCard title="Alertas críticas" value={criticalIncidents.length} icon={AlertOctagon} colorClass={criticalIncidents.length > 0 ? 'text-destructive' : 'text-muted-foreground'} />
      </div>

      <div className="grid grid-cols-12 gap-4 mb-6">
        <div className="col-span-12 lg:col-span-8 bg-card border border-border rounded-2xl p-6">
          <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground mb-6">Eventos por LMS</h3>
          <div className="h-[250px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData} margin={{ top: 0, right: 0, left: -20, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" vertical={false} />
                <XAxis dataKey="name" stroke="hsl(var(--muted-foreground))" fontSize={12} tickLine={false} axisLine={false} />
                <YAxis stroke="hsl(var(--muted-foreground))" fontSize={12} tickLine={false} axisLine={false} />
                <Tooltip
                  cursor={{ fill: 'hsl(var(--muted) / 0.4)' }}
                  contentStyle={{
                    backgroundColor: 'hsl(var(--card))',
                    borderColor: 'hsl(var(--border))',
                    borderRadius: '8px',
                    color: 'hsl(var(--foreground))',
                  }}
                />
                <Bar dataKey="eventos" fill="#6366f1" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
        <div className="col-span-12 lg:col-span-4">
          <ServiceHealthGrid />
        </div>
      </div>
    </div>
  );
}