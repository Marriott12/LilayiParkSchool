const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');

// Mock user data - In production, this should be stored in a database
// Default password is 'admin123' for admin and 'teacher123' for teacher
const users = [
  {
    id: 1,
    username: 'admin',
    password: '$2a$10$rB8C5l5Y5YC5l5Y5YC5l5OJ5YC5l5Y5YC5l5Y5YC5l5Y5YC5l5Y5YK', // admin123
    role: 'admin',
    name: 'Administrator'
  },
  {
    id: 2,
    username: 'teacher',
    password: '$2a$10$rB8C5l5Y5YC5l5Y5YC5l5OJ5YC5l5Y5YC5l5Y5YC5l5Y5YC5l5Y5YK', // teacher123
    role: 'teacher',
    name: 'Teacher User'
  }
];

// Login
const login = async (req, res) => {
  try {
    const { username, password } = req.body;

    if (!username || !password) {
      return res.status(400).json({ error: 'Username and password are required' });
    }

    // For demo purposes with hardcoded users, we'll use plain comparison
    // In production, use proper database with bcrypt.compare()
    let user = null;
    
    if (username === 'admin' && password === 'admin123') {
      user = users[0];
    } else if (username === 'teacher' && password === 'teacher123') {
      user = users[1];
    }

    if (!user) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    const token = jwt.sign(
      { id: user.id, username: user.username, role: user.role },
      process.env.JWT_SECRET,
      { expiresIn: process.env.JWT_EXPIRE || '7d' }
    );

    return res.json({
      success: true,
      token,
      user: {
        id: user.id,
        username: user.username,
        role: user.role,
        name: user.name
      }
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

// Get current user
const getCurrentUser = async (req, res) => {
  try {
    res.json({
      success: true,
      user: req.user
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

module.exports = {
  login,
  getCurrentUser
};
