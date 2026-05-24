import ServiceHealthGrid from '../components/dashboard/ServiceHealthGrid';
import RecentEventsList from '../components/services/RecentEventsList';
import { toast } from 'sonner';

export default function ServicesPage() {
  const copyToClipboard = (text: string, message: string) => {
    navigator.clipboard.writeText(text);
    toast.success(message);
  };

  const handleBurst = () => {
    copyToClipboard(
      'docker compose --profile simulator run --rm simulator-lms python simulator.py --burst',
      'Comando burst copiado al portapapeles'
    );
  };

  const handleContinuous = () => {
    copyToClipboard(
      'docker compose --profile simulator run --rm simulator-lms',
      'Comando de modo continuo copiado'
    );
  };

  return (
    <div className="animate-in fade-in duration-200 space-y-6">
      <h1 className="text-2xl font-semibold tracking-tight text-foreground">Servicios y Control</h1>

      <div className="h-[320px]">
        <ServiceHealthGrid />
      </div>

      <div className="grid grid-cols-12 gap-6">
        <div className="col-span-12 lg:col-span-8 bg-card border border-border p-6 rounded-2xl flex flex-col">
          <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground mb-4">Control del Simulador LMS</h3>
          <p className="text-sm text-foreground/80 mb-6 flex-1">
            El simulador genera eventos sintéticos de actividad estudiantil emulando 3 LMS (Moodle, Canvas, Open edX)
            hacia el servicio de Event Ingestion. Para demo local, copia y ejecuta estos comandos en tu terminal.
          </p>

          <div className="flex flex-col sm:flex-row gap-4 mt-auto">
            <button
              onClick={handleBurst}
              className="flex-1 bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-400 hover:to-violet-400 text-white font-medium py-2.5 px-4 rounded-xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95"
            >
              Copiar comando burst (100 eventos)
            </button>
            <button
              onClick={handleContinuous}
              className="flex-1 bg-muted hover:bg-muted/80 text-foreground border border-border font-medium py-2.5 px-4 rounded-xl transition-all active:scale-95"
            >
              Copiar comando continuo (2s/evento)
            </button>
          </div>
        </div>

        <div className="col-span-12 lg:col-span-4 h-[400px]">
          <RecentEventsList />
        </div>
      </div>
    </div>
  );
}