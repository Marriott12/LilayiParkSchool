const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Class = sequelize.define('Class', {
  classID: {
    type: DataTypes.STRING(10),
    primaryKey: true,
    allowNull: false
  },
  className: {
    type: DataTypes.STRING(50),
    unique: true,
    allowNull: false
  },
  teacherID: {
    type: DataTypes.STRING(10),
    allowNull: true,
    references: {
      model: 'Teacher',
      key: 'teacherID'
    }
  }
}, {
  tableName: 'Class',
  timestamps: true
});

module.exports = Class;
