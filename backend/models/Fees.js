const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Fees = sequelize.define('Fees', {
  feeID: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  classID: {
    type: DataTypes.STRING(10),
    allowNull: false,
    references: {
      model: 'Class',
      key: 'classID'
    }
  },
  feeAmt: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false
  },
  term: {
    type: DataTypes.STRING(20),
    allowNull: false
  },
  year: {
    type: DataTypes.INTEGER,
    allowNull: false
  }
}, {
  tableName: 'Fees',
  timestamps: true,
  indexes: [
    {
      unique: true,
      fields: ['classID', 'term', 'year']
    }
  ]
});

module.exports = Fees;
