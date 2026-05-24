import axios from 'axios';

// Instancia base sin baseURL global porque usamos distintos puertos (8000-8005)
export const api = axios.create({
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor para inyectar el token en todas las peticiones (excepto login)
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('access_token');
    if (token && !config.url?.includes('/api/auth/login')) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Interceptor para manejar el 401 (token expirado o inválido)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('access_token');
      localStorage.removeItem('user');
      // Solo redirigir si no estamos ya en /login (evita loops)
      if (!window.location.pathname.includes('/login')) {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);