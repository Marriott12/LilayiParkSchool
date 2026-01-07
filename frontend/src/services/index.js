import api from './api';

// Auth services
export const authService = {
  login: (credentials) => api.post('/auth/login', credentials),
  getCurrentUser: () => api.get('/auth/me'),
};

// Pupil services
export const pupilService = {
  getAll: (params) => api.get('/pupils', { params }),
  getById: (id) => api.get(`/pupils/${id}`),
  create: (data) => api.post('/pupils', data, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }),
  update: (id, data) => api.put(`/pupils/${id}`, data, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }),
  delete: (id) => api.delete(`/pupils/${id}`),
};

// Teacher services
export const teacherService = {
  getAll: () => api.get('/teachers'),
  getById: (id) => api.get(`/teachers/${id}`),
  create: (data) => api.post('/teachers', data),
  update: (id, data) => api.put(`/teachers/${id}`, data),
  delete: (id) => api.delete(`/teachers/${id}`),
};

// Parent services
export const parentService = {
  getAll: () => api.get('/parents'),
  getById: (id) => api.get(`/parents/${id}`),
  create: (data) => api.post('/parents', data),
  update: (id, data) => api.put(`/parents/${id}`, data),
  delete: (id) => api.delete(`/parents/${id}`),
};

// Class services
export const classService = {
  getAll: () => api.get('/classes'),
  getById: (id) => api.get(`/classes/${id}`),
  create: (data) => api.post('/classes', data),
  update: (id, data) => api.put(`/classes/${id}`, data),
  delete: (id) => api.delete(`/classes/${id}`),
  enrollPupil: (data) => api.post('/classes/enroll', data),
  removePupil: (classID, pupilID) => api.delete(`/classes/${classID}/pupils/${pupilID}`),
};

// Fee services
export const feeService = {
  getAll: (params) => api.get('/fees', { params }),
  getById: (id) => api.get(`/fees/${id}`),
  create: (data) => api.post('/fees', data),
  update: (id, data) => api.put(`/fees/${id}`, data),
  delete: (id) => api.delete(`/fees/${id}`),
};

// Payment services
export const paymentService = {
  getAll: (params) => api.get('/payments', { params }),
  getById: (id) => api.get(`/payments/${id}`),
  create: (data) => api.post('/payments', data),
  update: (id, data) => api.put(`/payments/${id}`, data),
  delete: (id) => api.delete(`/payments/${id}`),
  getOutstanding: () => api.get('/payments/outstanding'),
};

// Attendance services
export const attendanceService = {
  getAll: (params) => api.get('/attendance', { params }),
  getById: (id) => api.get(`/attendance/${id}`),
  create: (data) => api.post('/attendance', data),
  update: (id, data) => api.put(`/attendance/${id}`, data),
  delete: (id) => api.delete(`/attendance/${id}`),
};

// Report services
export const reportService = {
  getDashboard: () => api.get('/reports/dashboard'),
  getFeePaymentStatus: (params) => api.get('/reports/fee-payment-status', { params }),
  getAttendance: (params) => api.get('/reports/attendance', { params }),
  getEnrollment: (params) => api.get('/reports/enrollment', { params }),
  getOutstandingBalances: () => api.get('/reports/outstanding-balances'),
};
