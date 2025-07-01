import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { LogIn, LogOut, Settings, Edit3, UserPlus } from 'lucide-react'; // Icons

const Navbar: React.FC = () => {
  const { isAuthenticated, logout, user } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    // Optionally call backend logout if implemented
    // apiClient.post('/auth/logout');
    navigate('/login');
  };

  return (
    <nav className="bg-gradient-to-r from-slate-700 to-slate-900 text-white shadow-lg">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          <div className="flex items-center">
            <Link to="/" className="flex items-center text-2xl font-bold text-sky-400 hover:text-sky-300 transition-colors">
              <Edit3 className="w-7 h-7 mr-2" />
              Email<span className="text-white">Writer</span> AI
            </Link>
          </div>
          <div className="flex items-center space-x-3">
            {isAuthenticated ? (
              <>
                <span className="text-sm text-slate-300 hidden sm:block">Welcome, {user?.email}!</span>
                <Link
                  to="/settings"
                  className="flex items-center text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors bg-slate-700 hover:bg-slate-600"
                  title="Settings"
                >
                  <Settings className="w-5 h-5 sm:mr-2" />
                  <span className="hidden sm:inline">Settings</span>
                </Link>
                <button
                  onClick={handleLogout}
                  className="flex items-center text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors bg-red-600 hover:bg-red-700"
                  title="Logout"
                >
                  <LogOut className="w-5 h-5 sm:mr-2" />
                  <span className="hidden sm:inline">Logout</span>
                </button>
              </>
            ) : (
              <>
                <Link
                  to="/login"
                  className="flex items-center text-slate-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-slate-700"
                  title="Login"
                >
                  <LogIn className="w-5 h-5 sm:mr-2" />
                   <span className="hidden sm:inline">Login</span>
                </Link>
                <Link
                  to="/register"
                  className="flex items-center bg-sky-500 hover:bg-sky-600 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors"
                  title="Register"
                >
                  <UserPlus className="w-5 h-5 sm:mr-2" />
                  <span className="hidden sm:inline">Register</span>
                </Link>
              </>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
