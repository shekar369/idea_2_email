import React from 'react';
import { Outlet } from 'react-router-dom';
import Navbar from '../Navbar'; // Adjusted path

const MainLayout: React.FC = () => {
  return (
    <div className="min-h-screen flex flex-col bg-slate-100 dark:bg-slate-900">
      <Navbar />
      <main className="flex-grow container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <Outlet /> {/* Child routes will render here */}
      </main>
      <footer className="bg-slate-800 text-slate-400 text-center p-4 text-sm">
        © {new Date().getFullYear()} Email Writer AI. All rights reserved.
      </footer>
    </div>
  );
};

export default MainLayout;
