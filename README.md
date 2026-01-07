# Lilayi Park School Management Portal

A comprehensive school management system built with Node.js, Express, MySQL, React, and Tailwind CSS.

## Features

### Core Modules
- **Dashboard**: Overview statistics and quick actions
- **Pupil Management**: Complete CRUD operations with photo upload
- **Teacher Management**: Manage teacher records and class assignments
- **Parent Management**: Parent/guardian information management
- **Class Management**: Create classes, assign teachers, enroll pupils
- **Fee Management**: Set and manage fees by class and term
- **Payment Management**: Record payments with automatic balance calculation
- **Attendance Management**: Track pupil attendance by term
- **Reports**: Comprehensive reporting for fees, payments, attendance, and enrollment

### Technical Features
- RESTful API with JWT authentication
- Auto-generated IDs (pupilID: L001, teacherID: TCH001, etc.)
- File upload for pupil passport photos
- Relational database with foreign key constraints
- Responsive design with Tailwind CSS
- Protected routes and authentication
- Input validation and error handling

## Tech Stack

### Backend
- Node.js & Express.js
- MySQL with Sequelize ORM
- JWT for authentication
- Multer for file uploads
- bcryptjs for password hashing
- dotenv for environment configuration

### Frontend
- React 18
- React Router for navigation
- Axios for API calls
- Tailwind CSS for styling

## Project Structure

```
LilayiParkSchool/
├── backend/
│   ├── config/
│   │   └── database.js          # Database configuration
│   ├── controllers/             # Request handlers
│   │   ├── pupilController.js
│   │   ├── teacherController.js
│   │   ├── parentController.js
│   │   ├── classController.js
│   │   ├── feeController.js
│   │   ├── paymentController.js
│   │   ├── attendanceController.js
│   │   ├── authController.js
│   │   └── reportController.js
│   ├── models/                  # Sequelize models
│   │   ├── Teacher.js
│   │   ├── Parent.js
│   │   ├── Pupil.js
│   │   ├── Class.js
│   │   ├── PupilClass.js
│   │   ├── Fees.js
│   │   ├── Payment.js
│   │   ├── Attendance.js
│   │   └── index.js
│   ├── routes/                  # API routes
│   │   ├── auth.js
│   │   ├── pupils.js
│   │   ├── teachers.js
│   │   ├── parents.js
│   │   ├── classes.js
│   │   ├── fees.js
│   │   ├── payments.js
│   │   ├── attendance.js
│   │   └── reports.js
│   ├── middleware/              # Custom middleware
│   │   ├── auth.js
│   │   └── upload.js
│   ├── uploads/                 # Uploaded files
│   ├── server.js               # Main server file
│   ├── package.json
│   ├── .env.example
│   └── .gitignore
├── frontend/
│   ├── public/
│   │   └── index.html
│   ├── src/
│   │   ├── components/
│   │   │   └── Layout.js
│   │   ├── pages/
│   │   │   ├── Login.js
│   │   │   ├── Dashboard.js
│   │   │   ├── Pupils/
│   │   │   ├── Teachers/
│   │   │   ├── Parents/
│   │   │   ├── Classes/
│   │   │   ├── Fees/
│   │   │   ├── Payments/
│   │   │   ├── Attendance/
│   │   │   └── Reports.js
│   │   ├── services/
│   │   │   ├── api.js
│   │   │   └── index.js
│   │   ├── App.js
│   │   ├── index.js
│   │   └── index.css
│   ├── package.json
│   ├── tailwind.config.js
│   ├── .env.example
│   └── .gitignore
├── database/
│   ├── schema.sql              # Database schema with triggers
│   └── seed.sql                # Sample data
└── README.md
```

## Setup Instructions

### Prerequisites
- Node.js (v14 or higher)
- MySQL (v8.0 or higher)
- npm or yarn

### Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE lilayi_park_school;
```

2. Import the schema:
```bash
mysql -u root -p lilayi_park_school < database/schema.sql
```

3. (Optional) Import sample data:
```bash
mysql -u root -p lilayi_park_school < database/seed.sql
```

### Backend Setup

1. Navigate to the backend directory:
```bash
cd backend
```

2. Install dependencies:
```bash
npm install
```

3. Create a `.env` file from the example:
```bash
cp .env.example .env
```

4. Update the `.env` file with your database credentials:
```env
PORT=5000
NODE_ENV=development

DB_HOST=localhost
DB_PORT=3306
DB_NAME=lilayi_park_school
DB_USER=root
DB_PASSWORD=your_password

# IMPORTANT: Change this to a strong random string in production
JWT_SECRET=your_secret_key_here_CHANGE_IN_PRODUCTION
JWT_EXPIRE=7d

MAX_FILE_SIZE=5242880
UPLOAD_PATH=./uploads
```

**Note:** The `JWT_SECRET` is required and must be set before starting the server. Generate a secure random string for production use.

5. Start the server:
```bash
# Development mode with auto-reload
npm run dev

# Production mode
npm start
```

The backend API will be running at `http://localhost:5000`

### Frontend Setup

1. Navigate to the frontend directory:
```bash
cd frontend
```

2. Install dependencies:
```bash
npm install
```

3. Create a `.env` file from the example:
```bash
cp .env.example .env
```

4. Update the `.env` file if needed:
```env
REACT_APP_API_URL=http://localhost:5000/api
```

5. Start the development server:
```bash
npm start
```

The frontend will be running at `http://localhost:3000`

## Default Login Credentials

For testing purposes, use these credentials:

- **Admin**
  - Username: `admin`
  - Password: `admin123`

- **Teacher**
  - Username: `teacher`
  - Password: `teacher123`

## API Endpoints

### Authentication
- `POST /api/auth/login` - Login
- `GET /api/auth/me` - Get current user

### Pupils
- `GET /api/pupils` - Get all pupils
- `GET /api/pupils/:id` - Get pupil by ID
- `POST /api/pupils` - Create new pupil
- `PUT /api/pupils/:id` - Update pupil
- `DELETE /api/pupils/:id` - Delete pupil

### Teachers
- `GET /api/teachers` - Get all teachers
- `GET /api/teachers/:id` - Get teacher by ID
- `POST /api/teachers` - Create new teacher
- `PUT /api/teachers/:id` - Update teacher
- `DELETE /api/teachers/:id` - Delete teacher

### Parents
- `GET /api/parents` - Get all parents
- `GET /api/parents/:id` - Get parent by ID
- `POST /api/parents` - Create new parent
- `PUT /api/parents/:id` - Update parent
- `DELETE /api/parents/:id` - Delete parent

### Classes
- `GET /api/classes` - Get all classes
- `GET /api/classes/:id` - Get class by ID
- `POST /api/classes` - Create new class
- `PUT /api/classes/:id` - Update class
- `DELETE /api/classes/:id` - Delete class
- `POST /api/classes/enroll` - Enroll pupil in class
- `DELETE /api/classes/:classID/pupils/:pupilID` - Remove pupil from class

### Fees
- `GET /api/fees` - Get all fees (supports query params: classID, term, year)
- `GET /api/fees/:id` - Get fee by ID
- `POST /api/fees` - Create new fee
- `PUT /api/fees/:id` - Update fee
- `DELETE /api/fees/:id` - Delete fee

### Payments
- `GET /api/payments` - Get all payments (supports query params: pupilID, classID)
- `GET /api/payments/outstanding` - Get outstanding balances
- `GET /api/payments/:id` - Get payment by ID
- `POST /api/payments` - Create new payment
- `PUT /api/payments/:id` - Update payment
- `DELETE /api/payments/:id` - Delete payment

### Attendance
- `GET /api/attendance` - Get all attendance (supports query params: pupilID, term, year)
- `GET /api/attendance/:id` - Get attendance by ID
- `POST /api/attendance` - Create attendance record
- `PUT /api/attendance/:id` - Update attendance
- `DELETE /api/attendance/:id` - Delete attendance

### Reports
- `GET /api/reports/dashboard` - Dashboard statistics
- `GET /api/reports/fee-payment-status` - Fee payment status
- `GET /api/reports/attendance` - Attendance report
- `GET /api/reports/enrollment` - Enrollment report
- `GET /api/reports/outstanding-balances` - Outstanding balances report

## Database Schema

### Tables
1. **Teacher** - Teacher information with auto-generated ID (TCH001)
2. **Parent** - Parent/guardian information with auto-generated ID (PAR001)
3. **Pupil** - Pupil information with auto-generated ID (L001)
4. **Class** - Class information with auto-generated ID (CLS001)
5. **Pupil_Class** - Junction table for pupil-class relationship
6. **Fees** - Fee structure by class and term
7. **Payment** - Payment records with auto-generated ID (PAY001)
8. **Attendance** - Attendance tracking by pupil, term, and year

### Key Features
- Auto-increment custom IDs using MySQL triggers
- Foreign key constraints for data integrity
- Cascading deletes where appropriate
- Indexes for improved query performance
- Timestamps for audit trail

## Development

### Building for Production

#### Backend
```bash
cd backend
npm start
```

#### Frontend
```bash
cd frontend
npm run build
```

The build folder will contain the optimized production build.

### Environment Variables

Make sure to set proper environment variables for production:
- Change `JWT_SECRET` to a strong random string
- Update database credentials
- Set `NODE_ENV` to `production`

## Security Considerations

- JWT tokens for authentication
- Password hashing with bcryptjs
- Input validation on all endpoints
- File upload restrictions (size and type)
- Protected routes requiring authentication
- SQL injection prevention via Sequelize ORM

### Production Security Recommendations

1. **Rate Limiting**: Add rate limiting middleware (e.g., express-rate-limit) to prevent API abuse
2. **HTTPS**: Always use HTTPS in production
3. **JWT Secret**: Use a strong, randomly generated JWT_SECRET (required)
4. **Database**: Use strong database passwords and limit access
5. **CORS**: Configure CORS to only allow your frontend domain
6. **Headers**: Use helmet.js for security headers
7. **Input Sanitization**: Validate and sanitize all user inputs
8. **File Uploads**: Store uploaded files outside web root and validate file types
9. **Session Management**: Implement proper session timeout and rotation
10. **Logging**: Implement comprehensive logging and monitoring

## Future Enhancements

- Email notifications for parents
- SMS integration for attendance alerts
- Report export to PDF/Excel
- Advanced analytics and charts
- Mobile app
- Multi-language support
- Role-based permissions (fine-grained)
- Exam and grading system
- Library management
- Transport management
- Rate limiting implementation
- Two-factor authentication
- Audit logging

## License

MIT

## Support

For issues and questions, please create an issue in the repository.

---

**Lilayi Park School Management Portal** - Built with ❤️ for education management
