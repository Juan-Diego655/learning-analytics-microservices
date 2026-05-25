import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Activity } from 'lucide-react';

const DIAS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
const HORAS = Array.from({ length: 24 }, (_, i) => i);

const generateMockHeatmap = () => {
  const matrix = [];
  for (let d = 0; d < 7; d++) {
    for (let h = 0; h < 24; h++) {
      let count = 0;
      const esFinDeSemana = d >= 5;

      if (!esFinDeSemana && ((h >= 8 && h <= 12) || (h >= 14 && h <= 18))) {
        count = Math.floor(Math.random() * 15) + 12;
      } else if (!esFinDeSemana && (h === 13 || (h >= 19 && h <= 21))) {
        count = Math.floor(Math.random() * 10) + 5;
      } else {
        count = Math.floor(Math.random() * 4);
      }
      matrix.push({ day: d, hour: h, count });
    }
  }
  return matrix;
};

const heatmapData = generateMockHeatmap();

export default function ActivityHeatmap() {
  const [hoveredCell, setHoveredCell] = useState<{ day: number; hour: number; count: number } | null>(null);

  const getColorClass = (count: number) => {
    if (count === 0) return 'bg-muted/20';
    if (count <= 5) return 'bg-indigo-500/30';
    if (count <= 15) return 'bg-indigo-500/60';
    return 'bg-indigo-500';
  };

  return (
    <Card className="col-span-12 md:col-span-8 bg-card border-border shadow-sm flex flex-col justify-between">
      <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
        <div className="flex items-center gap-2">
          <Activity size={16} className="text-indigo-400" />
          <CardTitle className="text-sm font-medium uppercase tracking-wide text-muted-foreground">
            Actividad por franja horaria
          </CardTitle>
        </div>
        <div className="h-5 text-xs font-mono transition-all duration-150">
          {hoveredCell ? (
            <span className="text-indigo-300 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">
              {DIAS[hoveredCell.day]} {String(hoveredCell.hour).padStart(2, '0')}:00 ·{' '}
              <strong className="text-foreground">{hoveredCell.count}</strong> eventos
            </span>
          ) : (
            <span className="text-muted-foreground/40 italic">Pasa el cursor sobre una celda</span>
          )}
        </div>
      </CardHeader>

      <CardContent className="space-y-4 pt-2">
        <div className="flex flex-col gap-[3px] overflow-x-auto pb-1">
          {DIAS.map((diaNombre, dayIdx) => (
            <div key={diaNombre} className="flex items-center gap-[3px] min-w-[640px]">
              <span className="w-8 text-[11px] font-medium text-muted-foreground select-none">
                {diaNombre}
              </span>

              {HORAS.map((hora) => {
                const cell = heatmapData.find((c) => c.day === dayIdx && c.hour === hora) || { count: 0 };
                return (
                  <div
                    key={hora}
                    onMouseEnter={() => setHoveredCell({ day: dayIdx, hour: hora, count: cell.count })}
                    onMouseLeave={() => setHoveredCell(null)}
                    className={`flex-1 aspect-square rounded-[3px] transition-all duration-150 hover:scale-125 hover:z-10 cursor-crosshair ${getColorClass(
                      cell.count
                    )}`}
                  />
                );
              })}
            </div>
          ))}

          <div className="flex gap-[3px] pl-8 text-[9px] font-mono text-muted-foreground/60 select-none min-w-[640px] pt-1">
            {HORAS.map((hora) => (
              <span key={hora} className="flex-1 text-center">
                {hora % 4 === 0 ? `${String(hora).padStart(2, '0')}` : ''}
              </span>
            ))}
          </div>
        </div>

        <div className="flex items-center justify-end gap-1.5 text-[10px] font-medium text-muted-foreground/80 select-none pt-1">
          <span>Less</span>
          <div className="w-2.5 h-2.5 rounded-[2px] bg-muted/20" />
          <div className="w-2.5 h-2.5 rounded-[2px] bg-indigo-500/30" />
          <div className="w-2.5 h-2.5 rounded-[2px] bg-indigo-500/60" />
          <div className="w-2.5 h-2.5 rounded-[2px] bg-indigo-500" />
          <span>More</span>
        </div>
      </CardContent>
    </Card>
  );
}