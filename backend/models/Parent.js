const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Parent = sequelize.define('Parent', {
  parentID: {
    type: DataTypes.STRING(10),
    primaryKey: true,
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
  relation: {
    type: DataTypes.STRING(50),
    allowNull: false
  },
  gender: {
    type: DataTypes.ENUM('M', 'F'),
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
  email1: {
    type: DataTypes.STRING(100),
    allowNull: false,
    validate: {
      isEmail: true
    }
  },
  email2: {
    type: DataTypes.STRING(100),
    allowNull: true,
    validate: {
      isEmail: true
    }
  },
  occupation: {
    type: DataTypes.STRING(100),
    allowNull: true
  },
  workplace: {
    type: DataTypes.STRING(100),
    allowNull: true
  }
}, {
  tableName: 'Parent',
  timestamps: true
});

module.exports = Parent;
