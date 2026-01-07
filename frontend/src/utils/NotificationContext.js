import React, { createContext, useContext, useState } from 'react';

const NotificationContext = createContext();

export const useNotification = () => {
  const context = useContext(NotificationContext);
  if (!context) {
    throw new Error('useNotification must be used within NotificationProvider');
  }
  return context;
};

export const NotificationProvider = ({ children }) => {
  const [notification, setNotification] = useState(null);

  const showNotification = (message, type = 'info') => {
    setNotification({ message, type });
    setTimeout(() => setNotification(null), 5000);
  };

  const showError = (message) => showNotification(message, 'error');
  const showSuccess = (message) => showNotification(message, 'success');
  const showInfo = (message) => showNotification(message, 'info');

  return (
    <NotificationContext.Provider value={{ showNotification, showError, showSuccess, showInfo }}>
      {children}
      {notification && (
        <div
          className={`fixed top-4 right-4 p-4 rounded-lg shadow-lg max-w-md ${
            notification.type === 'error'
              ? 'bg-red-500 text-white'
              : notification.type === 'success'
              ? 'bg-green-500 text-white'
              : 'bg-blue-500 text-white'
          }`}
          style={{ zIndex: 9999 }}
        >
          <div className="flex items-center justify-between">
            <span>{notification.message}</span>
            <button
              onClick={() => setNotification(null)}
              className="ml-4 text-white hover:text-gray-200"
            >
              ✕
            </button>
          </div>
        </div>
      )}
    </NotificationContext.Provider>
  );
};
