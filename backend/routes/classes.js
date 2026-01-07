const express = require('express');
const router = express.Router();
const {
  getAllClasses,
  getClassById,
  createClass,
  updateClass,
  deleteClass,
  enrollPupil,
  removePupil
} = require('../controllers/classController');
const { auth } = require('../middleware/auth');

router.get('/', auth, getAllClasses);
router.get('/:id', auth, getClassById);
router.post('/', auth, createClass);
router.put('/:id', auth, updateClass);
router.delete('/:id', auth, deleteClass);
router.post('/enroll', auth, enrollPupil);
router.delete('/:classID/pupils/:pupilID', auth, removePupil);

module.exports = router;
