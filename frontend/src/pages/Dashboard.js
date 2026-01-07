import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { reportService } from '../services';

const Dashboard = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const response = await reportService.getDashboard();
      setStats(response.data);
    } catch (err) {
      setError('Failed to load dashboard statistics');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div className="text-center py-8">Loading...</div>;
  }

  if (error) {
    return <div className="text-center py-8 text-red-600">{error}</div>;
  }

  const statCards = [
    { label: 'Total Pupils', value: stats?.totalPupils || 0, color: 'bg-blue-500', link: '/pupils' },
    { label: 'Total Teachers', value: stats?.totalTeachers || 0, color: 'bg-green-500', link: '/teachers' },
    { label: 'Total Classes', value: stats?.totalClasses || 0, color: 'bg-purple-500', link: '/classes' },
    { label: 'Recent Enrollments', value: stats?.recentEnrollments || 0, color: 'bg-yellow-500', link: '/pupils' },
  ];

  const financeCards = [
    { label: 'Total Fees', value: `K${(stats?.totalFees || 0).toFixed(2)}`, color: 'bg-indigo-500' },
    { label: 'Total Payments', value: `K${(stats?.totalPayments || 0).toFixed(2)}`, color: 'bg-teal-500' },
    { label: 'Outstanding Balance', value: `K${(stats?.outstandingBalance || 0).toFixed(2)}`, color: 'bg-red-500', link: '/payments' },
  ];

  return (
    <div>
      <h1 className="text-3xl font-bold text-gray-800 mb-8">Dashboard</h1>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {statCards.map((card, index) => (
          <Link
            key={index}
            to={card.link}
            className={`${card.color} text-white rounded-lg shadow-lg p-6 hover:opacity-90 transition-opacity`}
          >
            <div className="text-3xl font-bold mb-2">{card.value}</div>
            <div className="text-sm opacity-90">{card.label}</div>
          </Link>
        ))}
      </div>

      {/* Finance Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {financeCards.map((card, index) => (
          <div
            key={index}
            className={`${card.color} text-white rounded-lg shadow-lg p-6`}
          >
            <div className="text-2xl font-bold mb-2">{card.value}</div>
            <div className="text-sm opacity-90">{card.label}</div>
          </div>
        ))}
      </div>

      {/* Quick Actions */}
      <div className="bg-white rounded-lg shadow-lg p-6">
        <h2 className="text-xl font-semibold mb-4">Quick Actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Link
            to="/pupils/new"
            className="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition-colors text-center"
          >
            <div className="text-2xl mb-2">👨‍🎓</div>
            <div className="font-medium text-blue-700">Add Pupil</div>
          </Link>
          <Link
            to="/teachers/new"
            className="bg-green-50 border border-green-200 rounded-lg p-4 hover:bg-green-100 transition-colors text-center"
          >
            <div className="text-2xl mb-2">👨‍🏫</div>
            <div className="font-medium text-green-700">Add Teacher</div>
          </Link>
          <Link
            to="/payments/new"
            className="bg-purple-50 border border-purple-200 rounded-lg p-4 hover:bg-purple-100 transition-colors text-center"
          >
            <div className="text-2xl mb-2">💳</div>
            <div className="font-medium text-purple-700">Record Payment</div>
          </Link>
          <Link
            to="/reports"
            className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 hover:bg-yellow-100 transition-colors text-center"
          >
            <div className="text-2xl mb-2">📈</div>
            <div className="font-medium text-yellow-700">View Reports</div>
          </Link>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
