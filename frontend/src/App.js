import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import PupilList from './pages/Pupils/PupilList';
import PupilForm from './pages/Pupils/PupilForm';
import PupilProfile from './pages/Pupils/PupilProfile';
import TeacherList from './pages/Teachers/TeacherList';
import TeacherForm from './pages/Teachers/TeacherForm';
import ParentList from './pages/Parents/ParentList';
import ParentForm from './pages/Parents/ParentForm';
import ClassList from './pages/Classes/ClassList';
import ClassForm from './pages/Classes/ClassForm';
import ClassRoster from './pages/Classes/ClassRoster';
import FeeList from './pages/Fees/FeeList';
import FeeForm from './pages/Fees/FeeForm';
import PaymentList from './pages/Payments/PaymentList';
import PaymentForm from './pages/Payments/PaymentForm';
import AttendanceList from './pages/Attendance/AttendanceList';
import AttendanceForm from './pages/Attendance/AttendanceForm';
import Reports from './pages/Reports';
import Layout from './components/Layout';

// Protected Route Component
const ProtectedRoute = ({ children }) => {
  const token = localStorage.getItem('token');
  return token ? children : <Navigate to="/login" />;
};

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route
          path="/"
          element={
            <ProtectedRoute>
              <Layout />
            </ProtectedRoute>
          }
        >
          <Route index element={<Dashboard />} />
          
          {/* Pupil Routes */}
          <Route path="pupils" element={<PupilList />} />
          <Route path="pupils/new" element={<PupilForm />} />
          <Route path="pupils/:id" element={<PupilProfile />} />
          <Route path="pupils/:id/edit" element={<PupilForm />} />
          
          {/* Teacher Routes */}
          <Route path="teachers" element={<TeacherList />} />
          <Route path="teachers/new" element={<TeacherForm />} />
          <Route path="teachers/:id/edit" element={<TeacherForm />} />
          
          {/* Parent Routes */}
          <Route path="parents" element={<ParentList />} />
          <Route path="parents/new" element={<ParentForm />} />
          <Route path="parents/:id/edit" element={<ParentForm />} />
          
          {/* Class Routes */}
          <Route path="classes" element={<ClassList />} />
          <Route path="classes/new" element={<ClassForm />} />
          <Route path="classes/:id" element={<ClassRoster />} />
          <Route path="classes/:id/edit" element={<ClassForm />} />
          
          {/* Fee Routes */}
          <Route path="fees" element={<FeeList />} />
          <Route path="fees/new" element={<FeeForm />} />
          <Route path="fees/:id/edit" element={<FeeForm />} />
          
          {/* Payment Routes */}
          <Route path="payments" element={<PaymentList />} />
          <Route path="payments/new" element={<PaymentForm />} />
          
          {/* Attendance Routes */}
          <Route path="attendance" element={<AttendanceList />} />
          <Route path="attendance/new" element={<AttendanceForm />} />
          <Route path="attendance/:id/edit" element={<AttendanceForm />} />
          
          {/* Reports */}
          <Route path="reports" element={<Reports />} />
        </Route>
      </Routes>
    </Router>
  );
}

export default App;
