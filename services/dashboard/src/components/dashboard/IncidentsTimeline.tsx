import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ResponsiveContainer, LineChart, Line, XAxis, YAxis, Tooltip } from 'recharts';
import { AlertTriangle } from 'lucide-react';

const generateTimelineMock = () => {
  return Array.from({ length: 24 }, (_, i) => {
    const horaStr = `${String(i).padStart(2, '0')}:00`;
    const esPico = i >= 9 && i <= 12;
    return {
      name: horaStr,
      critical: esPico ? Math.floor(Math.random() * 3) : Math.floor(Math.random() * 1),
      warning: esPico ? Math.floor(Math.random() * 6) + 3 : Math.floor(Math.random() * 3),
      info: Math.floor(Math.random() * 5) + 1,
    };
  });
};

const timelineData = generateTimelineMock();

export default function IncidentsTimeline() {
  return (
    <Card className="col-span-12 md:col-span-4 bg-card border-border shadow-sm flex flex-col justify-between">
      <CardHeader className="pb-2">
        <div className="flex items-center gap-2">
          <AlertTriangle size={16} className="text-amber-500" />
          <CardTitle className="text-sm font-medium uppercase tracking-wide text-muted-foreground">
            Incidentes 24h
          </CardTitle>
        </div>
      </CardHeader>
      <CardContent className="h-[200px] w-full pt-4">
        <ResponsiveContainer width="100%" height="100%">
          <LineChart data={timelineData} margin={{ top: 5, right: 5, left: -25, bottom: 0 }}>
            <XAxis
              dataKey="name"
              stroke="#71717a"
              fontSize={10}
              tickLine={false}
              axisLine={false}
              ticks={['00:00', '06:00', '12:00', '18:00', '23:00']}
            />
            <YAxis
              stroke="#71717a"
              fontSize={10}
              tickLine={false}
              axisLine={true}
            />
            <Tooltip
              contentStyle={{
                backgroundColor: '#16161f',
                border: '1px solid #252533',
                borderRadius: '8px',
                fontSize: '11px',
                fontFamily: 'JetBrains Mono, monospace',
                color: '#e5e7eb',
              }}
              itemStyle={{ padding: '2px 0' }}
            />
            <Line type="monotone" dataKey="critical" stroke="#ef4444" strokeWidth={2} dot={false} activeDot={{ r: 4 }} />
            <Line type="monotone" dataKey="warning" stroke="#f59e0b" strokeWidth={2} dot={false} activeDot={{ r: 4 }} />
            <Line type="monotone" dataKey="info" stroke="#3b82f6" strokeWidth={2} dot={false} activeDot={{ r: 4 }} />
          </LineChart>
        </ResponsiveContainer>
      </CardContent>
    </Card>
  );
}