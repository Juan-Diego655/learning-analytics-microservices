import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Clock, CheckCircle, AlertCircle } from 'lucide-react';
import { getSeverityColors, getStatusColors } from '../../lib/formatters';

interface IncidentDetailDialogProps {
  incident: any | null;
  isOpen: boolean;
  onClose: () => void;
}

export default function IncidentDetailDialog({ incident, isOpen, onClose }: IncidentDetailDialogProps) {
  if (!incident) return null;

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Detalle de Incidente</DialogTitle>
          <p className="text-xs font-mono text-muted-foreground mt-1">ID: {incident.incident_id}</p>
        </DialogHeader>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
          <div className="md:col-span-2 space-y-6">
            <div className="flex flex-wrap gap-2">
              <Badge variant="outline" className={`uppercase ${getSeverityColors(incident.severity)}`}>{incident.severity}</Badge>
              <Badge variant="outline" className={`uppercase ${getStatusColors(incident.status)}`}>{incident.status}</Badge>
              <Badge variant="secondary" className="font-mono uppercase">Regla: {incident.rule_code}</Badge>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <Card className="p-4 bg-muted/30">
                <p className="text-xs text-muted-foreground uppercase mb-1">Estudiante</p>
                <p className="font-mono text-sm">{incident.student_external_id || '—'}</p>
              </Card>
              <Card className="p-4 bg-muted/30">
                <p className="text-xs text-muted-foreground uppercase mb-1">Curso</p>
                <p className="font-mono text-sm">{incident.course_external_id || '—'}</p>
              </Card>
            </div>

            <div>
              <h3 className="text-sm font-medium mb-3">Contexto (JSON)</h3>
              <Card className="p-4 bg-black/50 overflow-x-auto border-border">
                <pre className="text-xs font-mono text-indigo-300">
                  {JSON.stringify(incident.trigger_context || {}, null, 2)}
                </pre>
              </Card>
            </div>
          </div>

          <div>
            <h3 className="text-sm font-medium mb-4">Línea de tiempo</h3>
            <div className="relative border-l border-border ml-3 space-y-6">
              <div className="relative pl-6">
                <span className="absolute -left-[13px] top-1 bg-background p-1 rounded-full text-amber-500">
                  <AlertCircle size={16} />
                </span>
                <p className="text-sm font-medium">Triggered</p>
                <p className="text-xs text-muted-foreground font-mono mt-1">{incident.triggered_at}</p>
              </div>

              {(['notified', 'acknowledged', 'resolved'].includes(incident.status)) && (
                <div className="relative pl-6">
                  <span className="absolute -left-[13px] top-1 bg-background p-1 rounded-full text-blue-400">
                    <Clock size={16} />
                  </span>
                  <p className="text-sm font-medium">Notified</p>
                  {incident.notified_at && <p className="text-xs text-muted-foreground font-mono mt-1">{incident.notified_at}</p>}
                </div>
              )}

              {(['acknowledged', 'resolved'].includes(incident.status)) && (
                <div className="relative pl-6">
                  <span className="absolute -left-[13px] top-1 bg-background p-1 rounded-full text-indigo-400">
                    <Clock size={16} />
                  </span>
                  <p className="text-sm font-medium">Acknowledged</p>
                  {incident.acknowledged_at && <p className="text-xs text-muted-foreground font-mono mt-1">{incident.acknowledged_at}</p>}
                </div>
              )}

              {incident.status === 'resolved' && (
                <div className="relative pl-6">
                  <span className="absolute -left-[13px] top-1 bg-background p-1 rounded-full text-emerald-500">
                    <CheckCircle size={16} />
                  </span>
                  <p className="text-sm font-medium">Resolved</p>
                  {incident.resolved_at && <p className="text-xs text-muted-foreground font-mono mt-1">{incident.resolved_at}</p>}
                </div>
              )}
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}