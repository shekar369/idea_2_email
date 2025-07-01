import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';

// Define the base URL for your backend API
// In development, this might be http://localhost:8000/api (if PHP server is on 8000)
// In production, this would be your actual domain.
// It's good practice to use an environment variable for this.
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'; // Vite specific env var

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor to add JWT token to headers
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('authToken');
    if (token) {
      // Ensure config.headers is defined
      config.headers = config.headers || {};
      config.headers['Authorization'] = `Bearer ${token}`;
    }
    return config;
  },
  (error: AxiosError) => {
    return Promise.reject(error);
  }
);

// Response interceptor (optional, e.g., for global error handling or token refresh)
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response && error.response.status === 401) {
      // Handle unauthorized errors, e.g., redirect to login
      // This could also be where you attempt token refresh if you implement that
      console.error('API request unauthorized (401):', error.response.data);
      // Example: Trigger logout or redirect
      // useAuth().logout(); // This won't work directly here due to hook rules
      // window.location.href = '/login'; // Simple redirect, but better to handle via AuthContext
      // For now, just logging. AuthContext or components should handle user redirection.
    }
    return Promise.reject(error);
  }
);

export default apiClient;

// --- Auth ---
export const registerUser = (userData: any) => apiClient.post('/auth/register', userData);
export const loginUser = (credentials: any) => apiClient.post('/auth/login', credentials);
// Optional: if backend implements a meaningful logout like token blocklisting
export const logoutUser = () => apiClient.post('/auth/logout');

// --- User ---
export const fetchCurrentUser = () => apiClient.get('/user/me');

// --- Settings ---
export const fetchLlmSettings = () => apiClient.get('/settings/llm');
export const updateLlmSettings = (settingsData: any) => apiClient.post('/settings/llm', settingsData);

// --- Email Generation ---
export const generateNewEmail = (emailData: { rawThoughts: string; tone: string; contextEmail?: string }) =>
  apiClient.post('/email/generate', emailData);
