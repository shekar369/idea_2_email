import React, { useState, FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { loginUser } from '../services/api'; // Import loginUser
import { LogIn, Mail, Lock } from 'lucide-react';

const LoginPage: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsLoading(true);

    try {
      const response = await loginUser({ email, password }); // Use the imported function

      if (response.data && response.data.token && response.data.user) {
        login(response.data.token, response.data.user);
        navigate('/'); // Redirect to dashboard or home page
      } else {
        // Should not happen if backend sends correct response format
        throw new Error('Login successful, but received unexpected data format.');
      }

    } catch (err: any) {
      setIsLoading(false);
      const errorMessage = err.response?.data?.error || err.message || 'Login failed. Please try again.';
      setError(errorMessage);
      console.error('Login error:', err);
    }
    // setIsLoading(false); // This will be set by navigate or error handling
  };

  return (
    <div className="flex items-center justify-center min-h-[calc(100vh-150px)] bg-gradient-to-br from-slate-100 to-sky-100 dark:from-slate-800 dark:to-sky-900 p-4">
      <div className="w-full max-w-md bg-white dark:bg-slate-700 shadow-2xl rounded-xl p-8 sm:p-10">
        <div className="text-center mb-8">
          <LogIn className="mx-auto h-12 w-12 text-sky-500" />
          <h2 className="mt-6 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
            Sign in to your account
          </h2>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          {error && (
            <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
              <strong className="font-bold">Error: </strong>
              <span className="block sm:inline">{error}</span>
            </div>
          )}

          <div>
            <label htmlFor="email" className="block text-sm font-medium text-gray-700 dark:text-slate-200">
              Email address
            </label>
            <div className="mt-1 relative rounded-md shadow-sm">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Mail className="h-5 w-5 text-gray-400" aria-hidden="true" />
              </div>
              <input
                id="email"
                name="email"
                type="email"
                autoComplete="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 dark:border-slate-500 rounded-md placeholder-gray-400 dark:placeholder-slate-400 focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm bg-white dark:bg-slate-600 text-gray-900 dark:text-white"
                placeholder="you@example.com"
              />
            </div>
          </div>

          <div>
            <label htmlFor="password" className="block text-sm font-medium text-gray-700 dark:text-slate-200">
              Password
            </label>
            <div className="mt-1 relative rounded-md shadow-sm">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Lock className="h-5 w-5 text-gray-400" aria-hidden="true" />
              </div>
              <input
                id="password"
                name="password"
                type="password"
                autoComplete="current-password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 dark:border-slate-500 rounded-md placeholder-gray-400 dark:placeholder-slate-400 focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm bg-white dark:bg-slate-600 text-gray-900 dark:text-white"
                placeholder="••••••••"
              />
            </div>
          </div>

          {/* <div className="flex items-center justify-between">
            <div className="text-sm">
              <a href="#" className="font-medium text-sky-600 hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">
                Forgot your password? (Not implemented)
              </a>
            </div>
          </div> */}

          <div>
            <button
              type="submit"
              disabled={isLoading}
              className="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {isLoading ? (
                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              ) : (
                'Sign in'
              )}
            </button>
          </div>
        </form>

        <p className="mt-8 text-center text-sm text-gray-600 dark:text-slate-300">
          Not a member?{' '}
          <Link to="/register" className="font-medium text-sky-600 hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">
            Sign up now
          </Link>
        </p>
      </div>
    </div>
  );
};

export default LoginPage;
