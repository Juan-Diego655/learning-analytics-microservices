import { useEffect, useState } from 'react';
import { api } from '../../lib/api';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

const SERVICES = [
  { name: 'API Gateway', port: 8000 },
  { name: 'Identity & Access', port: 8001 },
  { name: 'Event Ingestion', port: 8002 },
  { name: 'Telemetry', port: 8003 },
  { name: 'Alerting', port: 8004 },
  { name: 'Notification', port: 8005 },
];

export default function ServiceHealthGrid() {
  const [health, setHealth] = useState<Record<number, { status: string, ms?: number }>>({});

  useEffect(() => {
    const checkHealth = async () => {
      const results: Record<number, any> = {};
      await Promise.all(
        SERVICES.map(async (svc) => {
          const start = performance.now();
          try {
            await api.get(`http://localhost:${svc.port}/api/health`, { timeout: 2000 });
            results[svc.port] = { status: 'ok', ms: Math.round(performance.now() - start) };
          } catch (e) {
            results[svc.port] = { status: 'error' };
          }
        })
      );
      setHealth(results);
    };

    checkHealth();
    const interval = setInterval(checkHealth, 10000);
    return () => clearInterval(interval);
  }, []);

  return (
    <Card className="p-6 h-full flex flex-col">
      <h3 className="text-sm font-medium uppercase tracking-wide text-muted-foreground mb-4">Salud de Servicios</h3>
      <div className="space-y-3 flex-1 overflow-y-auto">
        {SERVICES.map((svc) => {
          const data = health[svc.port];
          const isOk = data?.status === 'ok';
          const isError = data?.status === 'error';

          return (
            <div key={svc.port} className="flex items-center justify-between p-2 rounded-lg hover:bg-muted/50 transition-colors">
              <div className="flex items-center gap-3">
                <div className="relative flex h-3 w-3">
                  {isOk && <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>}
                  <span className={`relative inline-flex rounded-full h-3 w-3 ${isOk ? 'bg-emerald-500' : isError ? 'bg-destructive' : 'bg-muted-foreground'}`}></span>
                </div>
                <span className="text-sm font-medium text-foreground">{svc.name}</span>
              </div>
              <Badge variant="outline" className="font-mono text-xs text-muted-foreground">
                {!data ? '...' : isOk ? `${data.ms}ms` : 'Error'}
              </Badge>
            </div>
          );
        })}
      </div>
    </Card>
  );
}