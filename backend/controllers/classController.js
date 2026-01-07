const { Class, Teacher, Pupil, PupilClass } = require('../models');

// Get all classes
const getAllClasses = async (req, res) => {
  try {
    const classes = await Class.findAll({
      include: [
        { model: Teacher, as: 'teacher' },
        { model: Pupil, as: 'pupils', through: { attributes: [] } }
      ],
      order: [['classID', 'ASC']]
    });
    res.json(classes);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Get class by ID
const getClassById = async (req, res) => {
  try {
    const classObj = await Class.findByPk(req.params.id, {
      include: [
        { model: Teacher, as: 'teacher' },
        { model: Pupil, as: 'pupils', through: { attributes: [] } }
      ]
    });

    if (!classObj) {
      return res.status(404).json({ error: 'Class not found' });
    }

    res.json(classObj);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Create new class
const createClass = async (req, res) => {
  try {
    const classObj = await Class.create(req.body);
    res.status(201).json(classObj);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Update class
const updateClass = async (req, res) => {
  try {
    const classObj = await Class.findByPk(req.params.id);
    
    if (!classObj) {
      return res.status(404).json({ error: 'Class not found' });
    }

    await classObj.update(req.body);
    res.json(classObj);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Delete class
const deleteClass = async (req, res) => {
  try {
    const classObj = await Class.findByPk(req.params.id);
    
    if (!classObj) {
      return res.status(404).json({ error: 'Class not found' });
    }

    await classObj.destroy();
    res.json({ message: 'Class deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Enroll pupil in class
const enrollPupil = async (req, res) => {
  try {
    const { pupilID, classID } = req.body;
    
    const enrollment = await PupilClass.create({
      pupilID,
      classID,
      enrollmentDate: new Date()
    });
    
    res.status(201).json(enrollment);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Remove pupil from class
const removePupil = async (req, res) => {
  try {
    const { pupilID, classID } = req.params;
    
    const enrollment = await PupilClass.findOne({
      where: { pupilID, classID }
    });
    
    if (!enrollment) {
      return res.status(404).json({ error: 'Enrollment not found' });
    }
    
    await enrollment.destroy();
    res.json({ message: 'Pupil removed from class successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

module.exports = {
  getAllClasses,
  getClassById,
  createClass,
  updateClass,
  deleteClass,
  enrollPupil,
  removePupil
};
