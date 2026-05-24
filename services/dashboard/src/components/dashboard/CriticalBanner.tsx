import { AlertOctagon } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

interface CriticalBannerProps {
  incidents: any[];
}

export default function CriticalBanner({ incidents }: CriticalBannerProps) {
  const navigate = useNavigate();
  if (!incidents || incidents.length === 0) return null;

  const latest = incidents[0];

  return (
    <div className="mb-6 relative overflow-hidden rounded-xl p-[1px] animate-in fade-in slide-in-from-top-4">
      <div className="absolute inset-0 bg-gradient-to-r from-indigo-500 via-purple-500 to-destructive animate-pulse opacity-50" />
      <Card className="relative bg-card/90 backdrop-blur-sm px-6 py-4 border-none flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div className="flex items-center gap-3 text-destructive">
          <AlertOctagon size={24} className="animate-bounce" />
          <div>
            <h3 className="font-semibold text-foreground">Incidente crítico: {latest.rule_code}</h3>
            <p className="text-sm text-muted-foreground">
              Estudiante: <span className="font-mono">{latest.student_external_id?.slice(0,8)}</span>
            </p>
          </div>
        </div>
        <Button variant="destructive" onClick={() => navigate('/incidents')}>
          Ver incidentes
        </Button>
      </Card>
    </div>
  );
}