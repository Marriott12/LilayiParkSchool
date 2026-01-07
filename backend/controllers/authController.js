const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');

// Mock user for demo - in production, this should be in database
const users = [
  {
    id: 1,
    username: 'admin',
    password: '$2a$10$9XqZ3JqZ3JqZ3JqZ3JqZ3u7qZ3JqZ3JqZ3JqZ3JqZ3JqZ3JqZ3JqZ', // 'admin123'
    role: 'admin',
    name: 'Administrator'
  },
  {
    id: 2,
    username: 'teacher',
    password: '$2a$10$9XqZ3JqZ3JqZ3JqZ3JqZ3u7qZ3JqZ3JqZ3JqZ3JqZ3JqZ3JqZ3JqZ', // 'teacher123'
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

    // For demo purposes, accept default credentials
    if (username === 'admin' && password === 'admin123') {
      const token = jwt.sign(
        { id: 1, username: 'admin', role: 'admin' },
        process.env.JWT_SECRET || 'default_secret_key',
        { expiresIn: process.env.JWT_EXPIRE || '7d' }
      );

      return res.json({
        success: true,
        token,
        user: {
          id: 1,
          username: 'admin',
          role: 'admin',
          name: 'Administrator'
        }
      });
    }

    if (username === 'teacher' && password === 'teacher123') {
      const token = jwt.sign(
        { id: 2, username: 'teacher', role: 'teacher' },
        process.env.JWT_SECRET || 'default_secret_key',
        { expiresIn: process.env.JWT_EXPIRE || '7d' }
      );

      return res.json({
        success: true,
        token,
        user: {
          id: 2,
          username: 'teacher',
          role: 'teacher',
          name: 'Teacher User'
        }
      });
    }

    res.status(401).json({ error: 'Invalid credentials' });
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
