const express = require('express');
const router = express.Router();
const {
  getDashboardStats,
  getFeePaymentStatus,
  getAttendanceReport,
  getEnrollmentReport,
  getOutstandingBalancesReport
} = require('../controllers/reportController');
const { auth } = require('../middleware/auth');

router.get('/dashboard', auth, getDashboardStats);
router.get('/fee-payment-status', auth, getFeePaymentStatus);
router.get('/attendance', auth, getAttendanceReport);
router.get('/enrollment', auth, getEnrollmentReport);
router.get('/outstanding-balances', auth, getOutstandingBalancesReport);

module.exports = router;
