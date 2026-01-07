const express = require('express');
const router = express.Router();
const {
  getAllPayments,
  getPaymentById,
  createPayment,
  updatePayment,
  deletePayment,
  getOutstandingBalances
} = require('../controllers/paymentController');
const { auth } = require('../middleware/auth');

router.get('/', auth, getAllPayments);
router.get('/outstanding', auth, getOutstandingBalances);
router.get('/:id', auth, getPaymentById);
router.post('/', auth, createPayment);
router.put('/:id', auth, updatePayment);
router.delete('/:id', auth, deletePayment);

module.exports = router;
