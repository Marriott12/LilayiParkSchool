const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Payment = sequelize.define('Payment', {
  payID: {
    type: DataTypes.STRING(10),
    primaryKey: true,
    allowNull: false
  },
  pupilID: {
    type: DataTypes.STRING(10),
    allowNull: false,
    references: {
      model: 'Pupil',
      key: 'pupilID'
    }
  },
  classID: {
    type: DataTypes.STRING(10),
    allowNull: false,
    references: {
      model: 'Class',
      key: 'classID'
    }
  },
  pmtAmt: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false
  },
  balance: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false
  },
  paymentDate: {
    type: DataTypes.DATEONLY,
    allowNull: false
  },
  remark: {
    type: DataTypes.TEXT,
    allowNull: true
  }
}, {
  tableName: 'Payment',
  timestamps: true,
  hooks: {
    beforeCreate: async (payment) => {
      if (!payment.payID) {
        const lastPayment = await Payment.findOne({
          order: [['payID', 'DESC']],
          attributes: ['payID']
        });
        
        let nextId = 1;
        if (lastPayment && lastPayment.payID) {
          const currentId = parseInt(lastPayment.payID.substring(3));
          nextId = currentId + 1;
        }
        
        payment.payID = `PAY${String(nextId).padStart(3, '0')}`;
      }
    }
  }
});

module.exports = Payment;
