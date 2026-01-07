const { Attendance, Pupil } = require('../models');

// Get all attendance records
const getAllAttendance = async (req, res) => {
  try {
    const { pupilID, term, year } = req.query;
    const where = {};

    if (pupilID) where.pupilID = pupilID;
    if (term) where.term = term;
    if (year) where.year = parseInt(year);

    const attendance = await Attendance.findAll({
      where,
      include: [{ model: Pupil, as: 'pupil' }],
      order: [['year', 'DESC'], ['term', 'ASC']]
    });
    
    res.json(attendance);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Get attendance by ID
const getAttendanceById = async (req, res) => {
  try {
    const attendance = await Attendance.findByPk(req.params.id, {
      include: [{ model: Pupil, as: 'pupil' }]
    });

    if (!attendance) {
      return res.status(404).json({ error: 'Attendance record not found' });
    }

    res.json(attendance);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Create new attendance record
const createAttendance = async (req, res) => {
  try {
    const attendance = await Attendance.create(req.body);
    res.status(201).json(attendance);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Update attendance
const updateAttendance = async (req, res) => {
  try {
    const attendance = await Attendance.findByPk(req.params.id);
    
    if (!attendance) {
      return res.status(404).json({ error: 'Attendance record not found' });
    }

    await attendance.update(req.body);
    res.json(attendance);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Delete attendance
const deleteAttendance = async (req, res) => {
  try {
    const attendance = await Attendance.findByPk(req.params.id);
    
    if (!attendance) {
      return res.status(404).json({ error: 'Attendance record not found' });
    }

    await attendance.destroy();
    res.json({ message: 'Attendance record deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

module.exports = {
  getAllAttendance,
  getAttendanceById,
  createAttendance,
  updateAttendance,
  deleteAttendance
};
