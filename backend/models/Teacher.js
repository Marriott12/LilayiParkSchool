const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Teacher = sequelize.define('Teacher', {
  teacherID: {
    type: DataTypes.STRING(10),
    primaryKey: true,
    allowNull: false
  },
  SSN: {
    type: DataTypes.STRING(20),
    unique: true,
    allowNull: false
  },
  Tpin: {
    type: DataTypes.STRING(20),
    unique: true,
    allowNull: false
  },
  fName: {
    type: DataTypes.STRING(50),
    allowNull: false
  },
  lName: {
    type: DataTypes.STRING(50),
    allowNull: false
  },
  NRC: {
    type: DataTypes.STRING(20),
    unique: true,
    allowNull: false
  },
  phone: {
    type: DataTypes.STRING(20),
    allowNull: false
  },
  email: {
    type: DataTypes.STRING(100),
    unique: true,
    allowNull: false,
    validate: {
      isEmail: true
    }
  },
  gender: {
    type: DataTypes.ENUM('M', 'F'),
    allowNull: false
  },
  tczNo: {
    type: DataTypes.STRING(50),
    allowNull: true
  }
}, {
  tableName: 'Teacher',
  timestamps: true
});

module.exports = Teacher;
