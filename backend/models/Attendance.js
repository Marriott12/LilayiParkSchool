const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Attendance = sequelize.define('Attendance', {
  ID: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  term: {
    type: DataTypes.STRING(20),
    allowNull: false
  },
  year: {
    type: DataTypes.INTEGER,
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
  daysPresent: {
    type: DataTypes.INTEGER,
    defaultValue: 0
  },
  daysAbsent: {
    type: DataTypes.INTEGER,
    defaultValue: 0
  },
  remark: {
    type: DataTypes.TEXT,
    allowNull: true
  }
}, {
  tableName: 'Attendance',
  timestamps: true,
  indexes: [
    {
      unique: true,
      fields: ['pupilID', 'term', 'year']
    }
  ]
});

module.exports = Attendance;
