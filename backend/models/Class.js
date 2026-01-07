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
  timestamps: true,
  hooks: {
    beforeCreate: async (classObj) => {
      if (!classObj.classID) {
        const lastClass = await Class.findOne({
          order: [['classID', 'DESC']],
          attributes: ['classID']
        });
        
        let nextId = 1;
        if (lastClass && lastClass.classID) {
          const currentId = parseInt(lastClass.classID.substring(3));
          nextId = currentId + 1;
        }
        
        classObj.classID = `CLS${String(nextId).padStart(3, '0')}`;
      }
    }
  }
});

module.exports = Class;
