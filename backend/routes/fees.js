const express = require('express');
const router = express.Router();
const {
  getAllFees,
  getFeeById,
  createFee,
  updateFee,
  deleteFee
} = require('../controllers/feeController');
const { auth } = require('../middleware/auth');

router.get('/', auth, getAllFees);
router.get('/:id', auth, getFeeById);
router.post('/', auth, createFee);
router.put('/:id', auth, updateFee);
router.delete('/:id', auth, deleteFee);

module.exports = router;
