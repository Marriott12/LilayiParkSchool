const express = require('express');
const router = express.Router();
const {
  getAllAttendance,
  getAttendanceById,
  createAttendance,
  updateAttendance,
  deleteAttendance
} = require('../controllers/attendanceController');
const { auth } = require('../middleware/auth');

router.get('/', auth, getAllAttendance);
router.get('/:id', auth, getAttendanceById);
router.post('/', auth, createAttendance);
router.put('/:id', auth, updateAttendance);
router.delete('/:id', auth, deleteAttendance);

module.exports = router;
