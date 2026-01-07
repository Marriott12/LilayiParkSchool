const { Fees, Class } = require('../models');

// Get all fees
const getAllFees = async (req, res) => {
  try {
    const { classID, term, year } = req.query;
    const where = {};

    if (classID) where.classID = classID;
    if (term) where.term = term;
    if (year) where.year = parseInt(year);

    const fees = await Fees.findAll({
      where,
      include: [{ model: Class, as: 'class' }],
      order: [['year', 'DESC'], ['term', 'ASC']]
    });
    
    res.json(fees);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Get fee by ID
const getFeeById = async (req, res) => {
  try {
    const fee = await Fees.findByPk(req.params.id, {
      include: [{ model: Class, as: 'class' }]
    });

    if (!fee) {
      return res.status(404).json({ error: 'Fee not found' });
    }

    res.json(fee);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Create new fee
const createFee = async (req, res) => {
  try {
    const fee = await Fees.create(req.body);
    res.status(201).json(fee);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Update fee
const updateFee = async (req, res) => {
  try {
    const fee = await Fees.findByPk(req.params.id);
    
    if (!fee) {
      return res.status(404).json({ error: 'Fee not found' });
    }

    await fee.update(req.body);
    res.json(fee);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Delete fee
const deleteFee = async (req, res) => {
  try {
    const fee = await Fees.findByPk(req.params.id);
    
    if (!fee) {
      return res.status(404).json({ error: 'Fee not found' });
    }

    await fee.destroy();
    res.json({ message: 'Fee deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

module.exports = {
  getAllFees,
  getFeeById,
  createFee,
  updateFee,
  deleteFee
};
