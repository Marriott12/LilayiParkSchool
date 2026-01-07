const { Teacher, Class } = require('../models');

// Get all teachers
const getAllTeachers = async (req, res) => {
  try {
    const teachers = await Teacher.findAll({
      include: [{ model: Class, as: 'classes' }],
      order: [['teacherID', 'ASC']]
    });
    res.json(teachers);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Get teacher by ID
const getTeacherById = async (req, res) => {
  try {
    const teacher = await Teacher.findByPk(req.params.id, {
      include: [{ model: Class, as: 'classes' }]
    });

    if (!teacher) {
      return res.status(404).json({ error: 'Teacher not found' });
    }

    res.json(teacher);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Create new teacher
const createTeacher = async (req, res) => {
  try {
    const teacher = await Teacher.create(req.body);
    res.status(201).json(teacher);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Update teacher
const updateTeacher = async (req, res) => {
  try {
    const teacher = await Teacher.findByPk(req.params.id);
    
    if (!teacher) {
      return res.status(404).json({ error: 'Teacher not found' });
    }

    await teacher.update(req.body);
    res.json(teacher);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Delete teacher
const deleteTeacher = async (req, res) => {
  try {
    const teacher = await Teacher.findByPk(req.params.id);
    
    if (!teacher) {
      return res.status(404).json({ error: 'Teacher not found' });
    }

    await teacher.destroy();
    res.json({ message: 'Teacher deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

module.exports = {
  getAllTeachers,
  getTeacherById,
  createTeacher,
  updateTeacher,
  deleteTeacher
};
