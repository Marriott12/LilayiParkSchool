const { Payment, Pupil, Class, Fees } = require('../models');
const { Op } = require('sequelize');

// Get all payments
const getAllPayments = async (req, res) => {
  try {
    const { pupilID, classID } = req.query;
    const where = {};

    if (pupilID) where.pupilID = pupilID;
    if (classID) where.classID = classID;

    const payments = await Payment.findAll({
      where,
      include: [
        { model: Pupil, as: 'pupil' },
        { model: Class, as: 'class' }
      ],
      order: [['paymentDate', 'DESC']]
    });
    
    res.json(payments);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Get payment by ID
const getPaymentById = async (req, res) => {
  try {
    const payment = await Payment.findByPk(req.params.id, {
      include: [
        { model: Pupil, as: 'pupil' },
        { model: Class, as: 'class' }
      ]
    });

    if (!payment) {
      return res.status(404).json({ error: 'Payment not found' });
    }

    res.json(payment);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Create new payment
const createPayment = async (req, res) => {
  try {
    const { pupilID, classID, pmtAmt, paymentDate, remark } = req.body;
    
    // Get the current term's fee for the class
    const currentYear = new Date().getFullYear();
    const fee = await Fees.findOne({
      where: { 
        classID, 
        year: currentYear 
      },
      order: [['feeID', 'DESC']]
    });

    if (!fee) {
      return res.status(404).json({ error: 'No fee found for this class' });
    }

    // Calculate total payments made
    const previousPayments = await Payment.findAll({
      where: { pupilID, classID },
      attributes: [[Payment.sequelize.fn('SUM', Payment.sequelize.col('pmtAmt')), 'total']]
    });

    const totalPaid = parseFloat(previousPayments[0]?.dataValues.total || 0) + parseFloat(pmtAmt);
    const balance = parseFloat(fee.feeAmt) - totalPaid;

    const payment = await Payment.create({
      pupilID,
      classID,
      pmtAmt,
      balance: balance > 0 ? balance : 0,
      paymentDate: paymentDate || new Date(),
      remark
    });

    res.status(201).json(payment);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Update payment
const updatePayment = async (req, res) => {
  try {
    const payment = await Payment.findByPk(req.params.id);
    
    if (!payment) {
      return res.status(404).json({ error: 'Payment not found' });
    }

    await payment.update(req.body);
    res.json(payment);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

// Delete payment
const deletePayment = async (req, res) => {
  try {
    const payment = await Payment.findByPk(req.params.id);
    
    if (!payment) {
      return res.status(404).json({ error: 'Payment not found' });
    }

    await payment.destroy();
    res.json({ message: 'Payment deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Get outstanding balances
const getOutstandingBalances = async (req, res) => {
  try {
    const payments = await Payment.findAll({
      where: {
        balance: { [Op.gt]: 0 }
      },
      include: [
        { model: Pupil, as: 'pupil' },
        { model: Class, as: 'class' }
      ],
      order: [['balance', 'DESC']]
    });
    
    res.json(payments);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

module.exports = {
  getAllPayments,
  getPaymentById,
  createPayment,
  updatePayment,
  deletePayment,
  getOutstandingBalances
};
