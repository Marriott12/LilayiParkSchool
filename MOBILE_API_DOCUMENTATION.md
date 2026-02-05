# Mobile API Documentation
**Lilayi Park School Management System**

## Overview
The Mobile API provides RESTful endpoints for mobile application integration. All API endpoints return JSON responses and use Bearer token authentication.

**Base URL:** `http://your-domain.com/LilayiParkSchool/api/mobile/`

---

## Authentication

### Login
**Endpoint:** `POST /api/mobile/auth.php`

**Description:** Authenticate user and receive API token

**Request Body:**
```json
{
  "username": "string",
  "password": "string"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "abc123...",
    "expiresAt": "2026-02-08 10:30:00",
    "user": {
      "userID": 1,
      "username": "john.doe",
      "email": "john@example.com",
      "role": "teacher",
      "profile": {
        "teacherID": "T001",
        "fName": "John",
        "lName": "Doe",
        ...
      }
    }
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid username or password"
}
```

### Using the Token
Include the token in the `Authorization` header for all subsequent requests:

```
Authorization: Bearer your_token_here
```

**Token Expiry:** 30 days from issuance

---

## Endpoints

### 1. Pupils

#### Get Pupils List
**Endpoint:** `GET /api/mobile/pupils.php`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (optional) - Search by name or admission number
- `class` (optional) - Filter by class ID
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 20, max: 100)

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [
      {
        "pupilID": "P001",
        "fName": "Alice",
        "lName": "Smith",
        "gender": "F",
        "dob": "2010-05-15",
        "admissionNumber": "ADM001",
        "className": "Grade 5A",
        ...
      }
    ],
    "pagination": {
      "currentPage": 1,
      "perPage": 20,
      "totalItems": 150,
      "totalPages": 8,
      "hasNext": true,
      "hasPrev": false
    }
  }
}
```

**Required Permission:** `view_pupils`

---

### 2. Attendance

#### Get Attendance
**Endpoint:** `GET /api/mobile/attendance.php`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `class` (required) - Class ID
- `date` (optional) - Date in YYYY-MM-DD format (default: today)

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "date": "2026-01-09",
    "classID": "C001",
    "attendance": [
      {
        "attendanceID": 1,
        "pupilID": "P001",
        "pupilName": "Alice Smith",
        "status": "present",
        "remarks": null
      }
    ]
  }
}
```

**Required Permission:** `view_attendance`

#### Mark Attendance
**Endpoint:** `POST /api/mobile/attendance.php`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "pupilID": "P001",
  "date": "2026-01-09",
  "status": "present",
  "remarks": "Optional notes"
}
```

**Status values:** `present`, `absent`, `late`, `excused`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Attendance marked successfully",
  "data": {
    "attendanceID": 123
  }
}
```

**Required Permission:** `mark_attendance`

---

### 3. Grades

#### Get Grades
**Endpoint:** `GET /api/mobile/grades.php`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `pupil` (optional) - Pupil ID
- `class` (optional) - Class ID
- `exam` (optional) - Examination ID
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 20)

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [
      {
        "gradeID": 1,
        "pupilID": "P001",
        "fName": "Alice",
        "lName": "Smith",
        "className": "Grade 5A",
        "subjectName": "Mathematics",
        "examName": "Mid-Term Exam",
        "term": 1,
        "academicYear": "2025/2026",
        "score": 85.5,
        "grade": "A",
        "remarks": "Excellent"
      }
    ],
    "pagination": {...}
  }
}
```

**Required Permission:** `view_grades`

---

### 4. Announcements

#### Get Announcements
**Endpoint:** `GET /api/mobile/announcements.php`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 10, max: 50)

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [
      {
        "announcementID": 1,
        "title": "School Closure Notice",
        "content": "School will be closed tomorrow...",
        "targetAudience": "all",
        "isPinned": 1,
        "status": "published",
        "createdAt": "2026-01-08 10:00:00",
        "authorName": "Admin User"
      }
    ],
    "pagination": {...}
  }
}
```

**Note:** Returns announcements relevant to the user's role

---

### 5. Timetable

#### Get Timetable
**Endpoint:** `GET /api/mobile/timetable.php`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `class` (required if teacher not provided) - Class ID
- `teacher` (required if class not provided) - Teacher ID
- `term` (optional) - Term number (default: current term)
- `year` (optional) - Academic year (default: current year)

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "term": 1,
    "academicYear": "2025/2026",
    "classID": "C001",
    "teacherID": null,
    "schedule": {
      "Monday": [
        {
          "timetableID": 1,
          "subjectName": "Mathematics",
          "teacherName": "Mr. John Doe",
          "startTime": "08:00:00",
          "endTime": "09:00:00",
          "room": "Room 101"
        }
      ],
      "Tuesday": [...],
      ...
    }
  }
}
```

**Required Permission:** `view_classes`

---

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "fieldName": "Error message"
  }
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthorized. Please login."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Internal server error"
}
```

---

## CORS Support

All endpoints support CORS (Cross-Origin Resource Sharing) with the following headers:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization`

---

## Rate Limiting

Currently, there are no rate limits enforced. It is recommended to implement rate limiting in production.

---

## Best Practices

### 1. Token Management
- Store tokens securely in your mobile app
- Refresh tokens before expiry (30 days)
- Clear tokens on logout

### 2. Pagination
- Use pagination for large datasets
- Start with reasonable per_page values (10-20)
- Respect max limits

### 3. Error Handling
- Always check the `success` field
- Handle all error codes appropriately
- Display user-friendly error messages

### 4. Performance
- Cache data when appropriate
- Use search and filter parameters to reduce payload
- Implement local caching in mobile app

---

## Example Usage (JavaScript/Fetch)

### Login
```javascript
const login = async (username, password) => {
  const response = await fetch('http://your-domain.com/api/mobile/auth.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ username, password })
  });
  
  const data = await response.json();
  
  if (data.success) {
    localStorage.setItem('token', data.data.token);
    return data.data.user;
  } else {
    throw new Error(data.message);
  }
};
```

### Get Pupils
```javascript
const getPupils = async (page = 1) => {
  const token = localStorage.getItem('token');
  
  const response = await fetch(
    `http://your-domain.com/api/mobile/pupils.php?page=${page}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    }
  );
  
  const data = await response.json();
  return data.success ? data.data : null;
};
```

### Mark Attendance
```javascript
const markAttendance = async (pupilID, date, status, remarks = '') => {
  const token = localStorage.getItem('token');
  
  const response = await fetch('http://your-domain.com/api/mobile/attendance.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ pupilID, date, status, remarks })
  });
  
  const data = await response.json();
  return data.success;
};
```

---

## Testing

### Using cURL

#### Login
```bash
curl -X POST http://your-domain.com/api/mobile/auth.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

#### Get Pupils
```bash
curl http://your-domain.com/api/mobile/pupils.php?page=1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Using Postman
1. Create a new request
2. Set method (GET/POST)
3. Enter endpoint URL
4. Add Authorization header: `Bearer {token}`
5. For POST requests, set body to JSON
6. Send request

---

## Security Considerations

1. **HTTPS Only:** Always use HTTPS in production
2. **Token Security:** Tokens are sensitive - never log or expose them
3. **Input Validation:** All inputs are validated server-side
4. **SQL Injection:** All queries use prepared statements
5. **XSS Protection:** All outputs are sanitized
6. **Permission Checks:** Every endpoint validates user permissions

---

## Support

For API issues or questions:
- Review error messages carefully
- Check permission requirements
- Verify token hasn't expired
- Consult this documentation

---

**Last Updated:** January 9, 2026
**API Version:** 1.0
**Status:** Production Ready ✅
