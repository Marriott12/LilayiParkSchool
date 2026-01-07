const { Pupil, Parent, Class, Payment, Attendance } = require('../models');
const { Op } = require('sequelize');

// Get all pupils
const getAllPupils = async (req, res) => {
  try {
    const { search, gender, classID } = req.query;
    const where = {};

    if (search) {
      where[Op.or] = [
        { fName: { [Op.like]: `%${search}%` } },
        { sName: { [Op.like]: `%${search}%` } },
        { pupilID: { [Op.like]: `%${search}%` } }
      ];
    }

    if (gender) {
      where.gender = gender;
    }

    const pupils = await Pupil.findAll({
      where,
      include: [
        { model: Parent, as: 'parent' },
        { model: Class, as: 'classes', through: { attributes: [] } }
      ],
      order: [['pupilID', 'ASC']]
    });

    res.json(pupils);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Get pupil by ID
const getPupilById = async (req, res) => {
  try {
    const pupil = await Pupil.findByPk(req.params.id, {
      include: [
        { model: Parent, as: 'parent' },
        { model: Class, as: 'classes', through: { attributes: [] } },
        { model: Payment, as: 'payments' },
        { model: Attendance, as: 'attendance' }
      ]
    });

    if (!pupil) {
      return res.status(404).json({ error: 'Pupil not found' });
    }

    res.json(pupil);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Create new pupil
const createPupil = async (req, res) => {
  try {
    const pupilData = { ...req.body };
    
    // Handle photo upload
    if (req.file) {
      pupilData.passPhoto = req.file.filename;
      pupilData.photo = 'Y';
    }

    const pupil = await Pupil.create(pupilData);
    res.status(201).json(pupil);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Update pupil
const updatePupil = async (req, res) => {
  try {
    const pupil = await Pupil.findByPk(req.params.id);
    
    if (!pupil) {
      return res.status(404).json({ error: 'Pupil not found' });
    }

    const updateData = { ...req.body };
    
    // Handle photo upload
    if (req.file) {
      updateData.passPhoto = req.file.filename;
      updateData.photo = 'Y';
    }

    await pupil.update(updateData);
    res.json(pupil);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Delete pupil
const deletePupil = async (req, res) => {
  try {
    const pupil = await Pupil.findByPk(req.params.id);
    
    if (!pupil) {
      return res.status(404).json({ error: 'Pupil not found' });
    }

    await pupil.destroy();
    res.json({ message: 'Pupil deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

module.exports = {
  getAllPupils,
  getPupilById,
  createPupil,
  updatePupil,
  deletePupil
};
