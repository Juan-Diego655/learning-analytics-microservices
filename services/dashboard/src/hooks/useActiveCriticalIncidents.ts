import { useState, useEffect } from 'react';
import { api } from '../lib/api';

export function useActiveCriticalIncidents() {
  const [count, setCount] = useState(0);
  const [incidents, setIncidents] = useState<any[]>([]);

  useEffect(() => {
    const fetchCritical = async () => {
      try {
        const res = await api.get('http://localhost:8004/api/incidents?severity=critical');
        const active = (res.data.incidents || []).filter((i: any) => i.status !== 'resolved');
        setIncidents(active);
        setCount(active.length);
      } catch (error) {
        console.error("Error fetching critical incidents", error);
      }
    };

    fetchCritical();
    const interval = setInterval(fetchCritical, 10000);
    return () => clearInterval(interval);
  }, []);

  return { count, incidents };
}