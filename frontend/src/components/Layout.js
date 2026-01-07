import React, { useState } from 'react';
import { Outlet, Link, useNavigate, useLocation } from 'react-router-dom';

const Layout = () => {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const navigate = useNavigate();
  const location = useLocation();

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    navigate('/login');
  };

  const menuItems = [
    { path: '/', label: 'Dashboard', icon: '📊' },
    { path: '/pupils', label: 'Pupils', icon: '👨‍🎓' },
    { path: '/teachers', label: 'Teachers', icon: '👨‍🏫' },
    { path: '/parents', label: 'Parents', icon: '👪' },
    { path: '/classes', label: 'Classes', icon: '🏫' },
    { path: '/fees', label: 'Fees', icon: '💰' },
    { path: '/payments', label: 'Payments', icon: '💳' },
    { path: '/attendance', label: 'Attendance', icon: '📝' },
    { path: '/reports', label: 'Reports', icon: '📈' },
  ];

  const isActive = (path) => {
    if (path === '/') return location.pathname === '/';
    return location.pathname.startsWith(path);
  };

  return (
    <div className="flex h-screen bg-gray-100">
      {/* Sidebar */}
      <div className={`bg-primary-800 text-white ${sidebarOpen ? 'w-64' : 'w-20'} transition-all duration-300`}>
        <div className="p-4">
          <div className="flex items-center justify-between mb-8">
            {sidebarOpen && <h1 className="text-xl font-bold">Lilayi Park School</h1>}
            <button
              onClick={() => setSidebarOpen(!sidebarOpen)}
              className="p-2 rounded hover:bg-primary-700"
            >
              {sidebarOpen ? '◀' : '▶'}
            </button>
          </div>
          
          <nav>
            {menuItems.map((item) => (
              <Link
                key={item.path}
                to={item.path}
                className={`flex items-center gap-3 p-3 mb-2 rounded transition-colors ${
                  isActive(item.path)
                    ? 'bg-primary-600'
                    : 'hover:bg-primary-700'
                }`}
              >
                <span className="text-xl">{item.icon}</span>
                {sidebarOpen && <span>{item.label}</span>}
              </Link>
            ))}
          </nav>
        </div>
      </div>

      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <header className="bg-white shadow-sm">
          <div className="px-6 py-4 flex justify-between items-center">
            <h2 className="text-2xl font-semibold text-gray-800">
              School Management Portal
            </h2>
            <div className="flex items-center gap-4">
              <span className="text-gray-600">
                {JSON.parse(localStorage.getItem('user') || '{}').name || 'User'}
              </span>
              <button
                onClick={handleLogout}
                className="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
              >
                Logout
              </button>
            </div>
          </div>
        </header>

        {/* Page Content */}
        <main className="flex-1 overflow-y-auto p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default Layout;
