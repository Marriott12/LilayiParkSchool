const { Parent, Pupil } = require('../models');

// Get all parents
const getAllParents = async (req, res) => {
  try {
    const parents = await Parent.findAll({
      include: [{ model: Pupil, as: 'children' }],
      order: [['parentID', 'ASC']]
    });
    res.json(parents);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Get parent by ID
const getParentById = async (req, res) => {
  try {
    const parent = await Parent.findByPk(req.params.id, {
      include: [{ model: Pupil, as: 'children' }]
    });

    if (!parent) {
      return res.status(404).json({ error: 'Parent not found' });
    }

    res.json(parent);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Create new parent
const createParent = async (req, res) => {
  try {
    const parent = await Parent.create(req.body);
    res.status(201).json(parent);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Update parent
const updateParent = async (req, res) => {
  try {
    const parent = await Parent.findByPk(req.params.id);
    
    if (!parent) {
      return res.status(404).json({ error: 'Parent not found' });
    }

    await parent.update(req.body);
    res.json(parent);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Delete parent
const deleteParent = async (req, res) => {
  try {
    const parent = await Parent.findByPk(req.params.id);
    
    if (!parent) {
      return res.status(404).json({ error: 'Parent not found' });
    }

    await parent.destroy();
    res.json({ message: 'Parent deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

module.exports = {
  getAllParents,
  getParentById,
  createParent,
  updateParent,
  deleteParent
};
