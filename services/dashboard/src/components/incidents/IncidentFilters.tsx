import { RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card } from '@/components/ui/card';

interface IncidentFiltersProps {
  filters: { status: string; severity: string; rule: string };
  setFilters: (filters: any) => void;
  onRefresh: () => void;
  isLoading: boolean;
}

export default function IncidentFilters({ filters, setFilters, onRefresh, isLoading }: IncidentFiltersProps) {
  const updateFilter = (key: string, value: string) => {
    setFilters({ ...filters, [key]: value });
  };

  return (
    <Card className="flex flex-col sm:flex-row items-center justify-between p-4 mb-6 shadow-sm gap-4">
      <div className="flex flex-wrap items-center gap-4 w-full sm:w-auto">
        <Select value={filters.status} onValueChange={(v) => updateFilter('status', v)}>
          <SelectTrigger className="w-[160px]">
            <SelectValue placeholder="Estado" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Todos los estados</SelectItem>
            <SelectItem value="triggered">Triggered</SelectItem>
            <SelectItem value="notified">Notified</SelectItem>
            <SelectItem value="acknowledged">Acknowledged</SelectItem>
            <SelectItem value="resolved">Resolved</SelectItem>
          </SelectContent>
        </Select>

        <Select value={filters.severity} onValueChange={(v) => updateFilter('severity', v)}>
          <SelectTrigger className="w-[160px]">
            <SelectValue placeholder="Severidad" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Todas</SelectItem>
            <SelectItem value="critical">Critical</SelectItem>
            <SelectItem value="warning">Warning</SelectItem>
            <SelectItem value="info">Info</SelectItem>
          </SelectContent>
        </Select>

        <Select value={filters.rule} onValueChange={(v) => updateFilter('rule', v)}>
          <SelectTrigger className="w-[160px]">
            <SelectValue placeholder="Regla" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Todas las reglas</SelectItem>
            <SelectItem value="R1">R1</SelectItem>
            <SelectItem value="R2">R2</SelectItem>
            <SelectItem value="R3">R3</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Button variant="secondary" onClick={onRefresh} disabled={isLoading} className="w-full sm:w-auto">
        <RefreshCw size={16} className={`mr-2 ${isLoading ? "animate-spin" : ""}`} />
        Refrescar
      </Button>
    </Card>
  );
}