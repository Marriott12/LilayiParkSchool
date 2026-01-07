const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Pupil = sequelize.define('Pupil', {
  pupilID: {
    type: DataTypes.STRING(10),
    primaryKey: true,
    allowNull: false
  },
  fName: {
    type: DataTypes.STRING(50),
    allowNull: false
  },
  sName: {
    type: DataTypes.STRING(50),
    allowNull: false
  },
  gender: {
    type: DataTypes.ENUM('M', 'F'),
    allowNull: false
  },
  DoB: {
    type: DataTypes.DATEONLY,
    allowNull: false
  },
  homeAddress: {
    type: DataTypes.STRING(200),
    allowNull: false
  },
  homeArea: {
    type: DataTypes.STRING(100),
    allowNull: false
  },
  medCondition: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  medAllergy: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  restrictions: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  prevSch: {
    type: DataTypes.STRING(100),
    allowNull: true
  },
  reason: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  parentID: {
    type: DataTypes.STRING(10),
    allowNull: false,
    references: {
      model: 'Parent',
      key: 'parentID'
    }
  },
  enrollDate: {
    type: DataTypes.DATEONLY,
    allowNull: false
  },
  transport: {
    type: DataTypes.ENUM('Y', 'N'),
    allowNull: false,
    defaultValue: 'N'
  },
  lunch: {
    type: DataTypes.ENUM('Y', 'N'),
    allowNull: false,
    defaultValue: 'N'
  },
  photo: {
    type: DataTypes.ENUM('Y', 'N'),
    allowNull: false,
    defaultValue: 'N'
  },
  passPhoto: {
    type: DataTypes.STRING(255),
    allowNull: true
  }
}, {
  tableName: 'Pupil',
  timestamps: true
});

module.exports = Pupil;
