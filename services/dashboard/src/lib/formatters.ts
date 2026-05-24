export function getRelativeTime(dateString: string): string {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);
  const rtf = new Intl.RelativeTimeFormat('es-CO', { numeric: 'auto' });

  if (diffInSeconds < 60) return rtf.format(-diffInSeconds, 'second');
  if (diffInSeconds < 3600) return rtf.format(-Math.floor(diffInSeconds / 60), 'minute');
  if (diffInSeconds < 86400) return rtf.format(-Math.floor(diffInSeconds / 3600), 'hour');
  return rtf.format(-Math.floor(diffInSeconds / 86400), 'day');
}

export function getSeverityColors(severity: string) {
  switch (severity?.toLowerCase()) {
    case 'critical': return 'bg-destructive/20 text-destructive border-destructive/50';
    case 'warning': return 'bg-amber-500/20 text-amber-500 border-amber-500/50';
    case 'info': return 'bg-blue-500/20 text-blue-400 border-blue-500/50';
    default: return 'bg-muted text-muted-foreground border-border';
  }
}

export function getStatusColors(status: string) {
  switch (status?.toLowerCase()) {
    case 'resolved': return 'bg-emerald-500/20 text-emerald-500 border-emerald-500/50';
    case 'acknowledged': return 'bg-indigo-500/20 text-indigo-400 border-indigo-500/50';
    case 'notified': return 'bg-blue-500/20 text-blue-400 border-blue-500/50';
    case 'triggered': return 'bg-amber-500/20 text-amber-500 border-amber-500/50';
    default: return 'bg-muted text-muted-foreground border-border';
  }
}