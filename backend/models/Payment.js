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
  timestamps: true
});

module.exports = Payment;
