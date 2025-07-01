import React from 'react';
import { Link } from 'react-router-dom';
import { AlertTriangle } from 'lucide-react';

const NotFoundPage: React.FC = () => {
  return (
    <div className="flex flex-col items-center justify-center min-h-[calc(100vh-200px)] text-center p-4">
      <AlertTriangle className="w-24 h-24 text-yellow-400 mb-8" />
      <h1 className="text-6xl font-bold text-slate-800 dark:text-white mb-4">404</h1>
      <p className="text-2xl text-slate-600 dark:text-slate-300 mb-8">
        Oops! The page you're looking for doesn't exist.
      </p>
      <Link
        to="/"
        className="px-6 py-3 bg-sky-500 text-white font-semibold rounded-lg shadow-md hover:bg-sky-600 transition-colors duration-150"
      >
        Go back to Homepage
      </Link>
    </div>
  );
};

export default NotFoundPage;
