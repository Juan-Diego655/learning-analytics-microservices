import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../lib/api';
import { toast } from 'sonner';

export default function LoginPage() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('docente@learning.test');
  const [password, setPassword] = useState('password');
  const [isLoading, setIsLoading] = useState(false);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      const response = await api.post('http://localhost:8000/api/auth/login', { email, password });
      const { access_token, user } = response.data;

      localStorage.setItem('access_token', access_token);
      localStorage.setItem('user', JSON.stringify(user));

      toast.success(`Bienvenido, ${user.name}`);
      navigate('/dashboard');
    } catch (error) {
      console.error(error);
      toast.error('Error al iniciar sesión. Verifica tus credenciales.');
    } finally {
      setIsLoading(false);
    }
  };

  const quickFill = (roleEmail: string) => {
    setEmail(roleEmail);
    setPassword('password');
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-bg p-4">
      <div className="w-full max-w-[400px] bg-card border border-border p-8 rounded-2xl shadow-xl animate-in fade-in zoom-in-95 duration-200">

        <div className="flex flex-col items-center mb-8">
          <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center mb-4 shadow-lg shadow-indigo-500/30">
            <span className="text-white font-bold text-lg">LA</span>
          </div>
          <h1 className="text-2xl font-semibold text-foreground tracking-tight">Learning Analytics</h1>
          <p className="text-sm text-muted-foreground mt-1 text-center">Plataforma Nacional de Monitoreo Educativo</p>
        </div>

        <form onSubmit={handleLogin} className="space-y-4">
          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Correo Electrónico</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full px-3 py-2 bg-bg border border-border rounded-lg text-foreground text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
              required
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Contraseña</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-3 py-2 bg-bg border border-border rounded-lg text-foreground text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
              required
            />
          </div>
          <button
            type="submit"
            disabled={isLoading}
            className="w-full py-2.5 mt-2 bg-primary hover:bg-primary/90 text-primary-foreground font-medium rounded-lg text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? 'Iniciando...' : 'Iniciar sesión'}
          </button>
        </form>

        <div className="mt-8 pt-6 border-t border-border">
          <p className="text-xs text-muted-foreground text-center mb-3">Accesos rápidos de desarrollo</p>
          <div className="flex flex-wrap gap-2 justify-center">
            <button
              type="button"
              onClick={() => quickFill('admin@learning.test')}
              className="text-xs px-2 py-1 bg-muted/40 text-foreground rounded hover:bg-muted transition-colors"
            >
              Admin
            </button>
            <button
              type="button"
              onClick={() => quickFill('docente@learning.test')}
              className="text-xs px-2 py-1 bg-muted/40 text-foreground rounded hover:bg-muted transition-colors"
            >
              Docente
            </button>
            <button
              type="button"
              onClick={() => quickFill('estudiante@learning.test')}
              className="text-xs px-2 py-1 bg-muted/40 text-foreground rounded hover:bg-muted transition-colors"
            >
              Estudiante
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}