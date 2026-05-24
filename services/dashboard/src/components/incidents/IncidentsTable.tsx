import { api } from '../../lib/api';
import { getRelativeTime, getSeverityColors, getStatusColors } from '../../lib/formatters';
import { toast } from 'sonner';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface IncidentsTableProps {
  incidents: any[];
  isLoading: boolean;
  onRefresh: () => void;
  onRowClick: (incident: any) => void;
}

export default function IncidentsTable({ incidents, isLoading, onRefresh, onRowClick }: IncidentsTableProps) {

  const handleAction = async (id: string, action: 'acknowledge' | 'resolve', e: React.MouseEvent) => {
    e.stopPropagation();
    try {
      await api.post(`http://localhost:8004/api/incidents/${id}/${action}`);
      toast.success(`Incidente ${action === 'resolve' ? 'resuelto' : 'reconocido'}`);
      onRefresh();
    } catch (error) {
      toast.error('Error al procesar la acción');
    }
  };

  if (isLoading) return <div className="p-8 text-center text-muted-foreground animate-pulse">Cargando...</div>;

  if (incidents.length === 0) {
    return <div className="p-12 text-center text-muted-foreground">Bandeja limpia. No hay incidentes.</div>;
  }

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Severidad</TableHead>
            <TableHead>Regla</TableHead>
            <TableHead>Estudiante / Curso</TableHead>
            <TableHead>Ocurrencia</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead className="text-right">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {incidents.map((inc) => (
            <TableRow key={inc.incident_id} onClick={() => onRowClick(inc)} className="cursor-pointer hover:bg-muted/50">
              <TableCell>
                <Badge variant="outline" className={`uppercase ${getSeverityColors(inc.severity)}`}>
                  {inc.severity}
                </Badge>
              </TableCell>
              <TableCell className="font-mono text-xs">{inc.rule_code}</TableCell>
              <TableCell>
                <div className="font-mono text-xs">{inc.student_external_id || '—'}</div>
                <div className="font-mono text-[10px] text-muted-foreground truncate max-w-[150px]">{inc.course_external_id || '—'}</div>
              </TableCell>
              <TableCell className="text-muted-foreground text-sm">
                {getRelativeTime(inc.triggered_at)}
              </TableCell>
              <TableCell>
                <Badge variant="outline" className={`uppercase ${getStatusColors(inc.status)}`}>
                  {inc.status}
                </Badge>
              </TableCell>
              <TableCell className="text-right">
                {inc.status === 'notified' && (
                  <Button size="sm" variant="outline" onClick={(e) => handleAction(inc.incident_id, 'acknowledge', e)}>
                    Ack
                  </Button>
                )}
                {inc.status === 'acknowledged' && (
                  <Button size="sm" variant="default" onClick={(e) => handleAction(inc.incident_id, 'resolve', e)}>
                    Resolve
                  </Button>
                )}
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}