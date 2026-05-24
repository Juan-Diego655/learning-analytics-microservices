import { useState, useEffect, useCallback } from 'react';
import { api } from '../lib/api';
import IncidentFilters from '../components/incidents/IncidentFilters';
import IncidentsTable from '../components/incidents/IncidentsTable';
import IncidentDetailDialog from '../components/incidents/IncidentDetailDialog';

export default function IncidentsPage() {
  const [incidents, setIncidents] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [filters, setFilters] = useState({ status: 'all', severity: 'all', rule: 'all' });
  const [selectedIncident, setSelectedIncident] = useState<any | null>(null);

  const fetchIncidents = useCallback(async () => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams();
      params.append('limit', '30');
      if (filters.status !== 'all') params.append('status', filters.status);
      if (filters.severity !== 'all') params.append('severity', filters.severity);
      if (filters.rule !== 'all') params.append('rule_code', filters.rule);

      const response = await api.get(`http://localhost:8004/api/incidents?${params.toString()}`);
      setIncidents(response.data.incidents || []);
    } catch (error) {
      console.error('Error fetching incidents', error);
    } finally {
      setIsLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    fetchIncidents();
  }, [fetchIncidents]);

  return (
    <div className="animate-in fade-in duration-200">
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-semibold tracking-tight text-foreground">Gestión de Incidentes</h1>
      </div>

      <IncidentFilters
        filters={filters}
        setFilters={setFilters}
        onRefresh={fetchIncidents}
        isLoading={isLoading}
      />

      <div className="bg-card border border-border rounded-2xl overflow-hidden">
        <IncidentsTable
          incidents={incidents}
          isLoading={isLoading}
          onRefresh={fetchIncidents}
          onRowClick={(incident) => setSelectedIncident(incident)}
        />
      </div>

      <IncidentDetailDialog
        incident={selectedIncident}
        isOpen={!!selectedIncident}
        onClose={() => setSelectedIncident(null)}
      />
    </div>
  );
}