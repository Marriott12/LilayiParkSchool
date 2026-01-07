const express = require('express');
const router = express.Router();
const {
  getAllParents,
  getParentById,
  createParent,
  updateParent,
  deleteParent
} = require('../controllers/parentController');
const { auth } = require('../middleware/auth');

router.get('/', auth, getAllParents);
router.get('/:id', auth, getParentById);
router.post('/', auth, createParent);
router.put('/:id', auth, updateParent);
router.delete('/:id', auth, deleteParent);

module.exports = router;
