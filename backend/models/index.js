const sequelize = require('../config/database');
const Teacher = require('./Teacher');
const Parent = require('./Parent');
const Pupil = require('./Pupil');
const Class = require('./Class');
const PupilClass = require('./PupilClass');
const Fees = require('./Fees');
const Payment = require('./Payment');
const Attendance = require('./Attendance');

// Define relationships

// Parent - Pupil (One to Many)
Parent.hasMany(Pupil, { foreignKey: 'parentID', as: 'children' });
Pupil.belongsTo(Parent, { foreignKey: 'parentID', as: 'parent' });

// Teacher - Class (One to Many)
Teacher.hasMany(Class, { foreignKey: 'teacherID', as: 'classes' });
Class.belongsTo(Teacher, { foreignKey: 'teacherID', as: 'teacher' });

// Pupil - Class (Many to Many through PupilClass)
Pupil.belongsToMany(Class, { 
  through: PupilClass, 
  foreignKey: 'pupilID',
  otherKey: 'classID',
  as: 'classes'
});
Class.belongsToMany(Pupil, { 
  through: PupilClass, 
  foreignKey: 'classID',
  otherKey: 'pupilID',
  as: 'pupils'
});

// Class - Fees (One to Many)
Class.hasMany(Fees, { foreignKey: 'classID', as: 'fees' });
Fees.belongsTo(Class, { foreignKey: 'classID', as: 'class' });

// Pupil - Payment (One to Many)
Pupil.hasMany(Payment, { foreignKey: 'pupilID', as: 'payments' });
Payment.belongsTo(Pupil, { foreignKey: 'pupilID', as: 'pupil' });

// Class - Payment (One to Many)
Class.hasMany(Payment, { foreignKey: 'classID', as: 'payments' });
Payment.belongsTo(Class, { foreignKey: 'classID', as: 'class' });

// Pupil - Attendance (One to Many)
Pupil.hasMany(Attendance, { foreignKey: 'pupilID', as: 'attendance' });
Attendance.belongsTo(Pupil, { foreignKey: 'pupilID', as: 'pupil' });

module.exports = {
  sequelize,
  Teacher,
  Parent,
  Pupil,
  Class,
  PupilClass,
  Fees,
  Payment,
  Attendance
};
