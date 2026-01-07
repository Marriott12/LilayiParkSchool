const express = require('express');
const router = express.Router();
const {
  getAllPupils,
  getPupilById,
  createPupil,
  updatePupil,
  deletePupil
} = require('../controllers/pupilController');
const { auth } = require('../middleware/auth');
const upload = require('../middleware/upload');

router.get('/', auth, getAllPupils);
router.get('/:id', auth, getPupilById);
router.post('/', auth, upload.single('passPhoto'), createPupil);
router.put('/:id', auth, upload.single('passPhoto'), updatePupil);
router.delete('/:id', auth, deletePupil);

module.exports = router;
