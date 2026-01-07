const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const PupilClass = sequelize.define('PupilClass', {
  pupilID: {
    type: DataTypes.STRING(10),
    primaryKey: true,
    allowNull: false,
    references: {
      model: 'Pupil',
      key: 'pupilID'
    }
  },
  classID: {
    type: DataTypes.STRING(10),
    primaryKey: true,
    allowNull: false,
    references: {
      model: 'Class',
      key: 'classID'
    }
  },
  enrollmentDate: {
    type: DataTypes.DATEONLY,
    allowNull: true,
    defaultValue: DataTypes.NOW
  }
}, {
  tableName: 'Pupil_Class',
  timestamps: true,
  updatedAt: false
});

module.exports = PupilClass;
