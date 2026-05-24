import { useEffect, useState } from 'react';
import { api } from '../lib/api';
import { getSeverityColors } from '../lib/formatters';

export default function RulesPage() {
  const [rules, setRules] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    api.get('http://localhost:8004/api/rules')
      .then(res => setRules(res.data.rules || []))
      .catch(err => console.error(err))
      .finally(() => setIsLoading(false));
  }, []);

  return (
    <div className="animate-in fade-in duration-200">
      <h1 className="text-2xl font-semibold tracking-tight text-foreground mb-6">Catálogo de Reglas</h1>

      {isLoading ? (
        <div className="text-muted-foreground animate-pulse">Cargando reglas...</div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {rules.map((rule) => (
            <div key={rule.rule_code} className="bg-card border border-border p-6 rounded-2xl hover:shadow-lg transition-all">
              <div className="flex justify-between items-start mb-4">
                <div>
                  <h2 className="font-mono text-xl text-foreground mb-1">{rule.rule_code}</h2>
                  <p className="text-sm font-medium text-muted-foreground">{rule.name}</p>
                </div>
                <div className="flex flex-col gap-1.5 items-end">
                  <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest border ${getSeverityColors(rule.severity)}`}>
                    {rule.severity}
                  </span>
                  <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest bg-violet-500/10 text-violet-400 border border-violet-500/20">
                    {rule.evaluation_mode || rule.mode}
                  </span>
                </div>
              </div>

              <p className="text-sm text-foreground/80 mb-6">{rule.description}</p>

              <div className="bg-bg border border-border rounded-lg p-3">
                <h4 className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider mb-2">Configuración</h4>
                <pre className="text-xs font-mono text-indigo-300 whitespace-pre-wrap">
                  {JSON.stringify(rule.config, null, 2)}
                </pre>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}