const { Pupil, Teacher, Parent, Class, Payment, Attendance, Fees } = require('../models');
const { Op } = require('sequelize');

// Dashboard statistics
const getDashboardStats = async (req, res) => {
  try {
    const totalPupils = await Pupil.count();
    const totalTeachers = await Teacher.count();
    const totalClasses = await Class.count();
    
    const currentYear = new Date().getFullYear();
    const totalFees = await Fees.sum('feeAmt', {
      where: { year: currentYear }
    });
    
    const totalPayments = await Payment.sum('pmtAmt', {
      where: {
        paymentDate: {
          [Op.gte]: new Date(currentYear, 0, 1)
        }
      }
    });
    
    const outstandingBalance = await Payment.sum('balance');
    
    // Recent enrollments (last 30 days)
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    
    const recentEnrollments = await Pupil.count({
      where: {
        enrollDate: {
          [Op.gte]: thirtyDaysAgo
        }
      }
    });
    
    res.json({
      totalPupils,
      totalTeachers,
      totalClasses,
      totalFees: totalFees || 0,
      totalPayments: totalPayments || 0,
      outstandingBalance: outstandingBalance || 0,
      recentEnrollments
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Fee payment status report
const getFeePaymentStatus = async (req, res) => {
  try {
    const { classID, term, year } = req.query;
    const currentYear = year || new Date().getFullYear();
    
    const whereClause = { year: parseInt(currentYear) };
    if (classID) whereClause.classID = classID;
    if (term) whereClause.term = term;
    
    const fees = await Fees.findAll({
      where: whereClause,
      include: [
        {
          model: Class,
          as: 'class',
          include: [
            {
              model: Pupil,
              as: 'pupils',
              through: { attributes: [] },
              include: [
                {
                  model: Payment,
                  as: 'payments',
                  where: { classID: { [Op.col]: 'class.classID' } },
                  required: false
                }
              ]
            }
          ]
        }
      ]
    });
    
    res.json(fees);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Attendance report
const getAttendanceReport = async (req, res) => {
  try {
    const { classID, term, year } = req.query;
    const currentYear = year || new Date().getFullYear();
    
    const whereClause = { year: parseInt(currentYear) };
    if (term) whereClause.term = term;
    
    const attendance = await Attendance.findAll({
      where: whereClause,
      include: [
        {
          model: Pupil,
          as: 'pupil',
          include: [
            {
              model: Class,
              as: 'classes',
              through: { attributes: [] },
              ...(classID ? { where: { classID } } : {})
            }
          ]
        }
      ],
      order: [['term', 'ASC'], ['pupilID', 'ASC']]
    });
    
    res.json(attendance);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Student enrollment report
const getEnrollmentReport = async (req, res) => {
  try {
    const { startDate, endDate } = req.query;
    const whereClause = {};
    
    if (startDate) {
      whereClause.enrollDate = {
        [Op.gte]: new Date(startDate)
      };
    }
    
    if (endDate) {
      whereClause.enrollDate = {
        ...whereClause.enrollDate,
        [Op.lte]: new Date(endDate)
      };
    }
    
    const pupils = await Pupil.findAll({
      where: whereClause,
      include: [
        { model: Class, as: 'classes', through: { attributes: [] } },
        { model: Parent, as: 'parent' }
      ],
      order: [['enrollDate', 'DESC']]
    });
    
    res.json(pupils);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Outstanding balances report
const getOutstandingBalancesReport = async (req, res) => {
  try {
    const payments = await Payment.findAll({
      where: {
        balance: { [Op.gt]: 0 }
      },
      include: [
        { 
          model: Pupil, 
          as: 'pupil',
          include: [
            { model: Parent, as: 'parent' }
          ]
        },
        { model: Class, as: 'class' }
      ],
      order: [['balance', 'DESC']]
    });
    
    const totalOutstanding = await Payment.sum('balance', {
      where: { balance: { [Op.gt]: 0 } }
    });
    
    res.json({
      payments,
      totalOutstanding: totalOutstanding || 0
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

module.exports = {
  getDashboardStats,
  getFeePaymentStatus,
  getAttendanceReport,
  getEnrollmentReport,
  getOutstandingBalancesReport
};
