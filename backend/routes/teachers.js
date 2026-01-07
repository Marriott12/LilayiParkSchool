const express = require('express');
const router = express.Router();
const {
  getAllTeachers,
  getTeacherById,
  createTeacher,
  updateTeacher,
  deleteTeacher
} = require('../controllers/teacherController');
const { auth } = require('../middleware/auth');

router.get('/', auth, getAllTeachers);
router.get('/:id', auth, getTeacherById);
router.post('/', auth, createTeacher);
router.put('/:id', auth, updateTeacher);
router.delete('/:id', auth, deleteTeacher);

module.exports = router;
