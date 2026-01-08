# Academic Management System - Testing Guide

## Prerequisites
1. **Run Migration**: Visit `http://localhost/LilayiParkSchool/run_examinations_migration.php` (requires Admin login)
2. **Login**: Use admin credentials to access all features
3. **Verify Permissions**: Ensure RBAC permissions for grades and examinations are enabled

## Phase 1: Grades Management Testing

### Test 1.1: Grades List Page
**URL**: `grades_list.php`

**Test Steps**:
1. Navigate to Academic > Grades Management
2. Verify page loads with filters (Class, Subject, Term, Exam Type, Academic Year)
3. Test each filter individually:
   - Select "Grade 1" → Should show only Grade 1 students
   - Select "Mathematics" → Should show only Mathematics grades
   - Select "Term 1" → Should show only Term 1 grades
   - Select "CAT" → Should show only CAT exams
4. Verify pagination (if more than 50 records)
5. Check statistics cards show correct data:
   - Total Grades Recorded
   - Average Marks
   - Average GPA
   - Pass Rate (marks >= passing marks)
6. Click "Export CSV" → Download should start with filtered data
7. Click "Clear Filters" → All filters reset

**Expected Results**:
- ✅ Filters work independently and combined
- ✅ Statistics calculate correctly
- ✅ CSV export contains correct data
- ✅ Pagination shows correct page numbers

---

### Test 1.2: Individual Grade Entry
**URL**: `grades_form.php`

**Test Steps**:
1. Click "Record New Grade" from grades_list.php
2. Fill in form:
   - Pupil: Select any student
   - Class: Select matching class
   - Subject: Select any subject
   - Term: Select 1, 2, or 3
   - Academic Year: Select 2025/2026
   - Exam Type: Select CAT
   - Marks: Enter 25
   - Max Marks: 30
3. Click "Record Grade"
4. Verify redirect to grades_list.php with success message
5. Verify new grade appears in list with calculated grade (A, B, C, D, E, or F)

**Validation Tests**:
- Try marks > maxMarks → Should show error "Marks cannot exceed maximum marks"
- Try empty pupil → Should show error "Pupil is required"
- Try empty subject → Should show error "Subject is required"

**Expected Results**:
- ✅ Grade saved successfully
- ✅ Auto-calculated grade shows correctly (e.g., 25/30 = 83.33% = A)
- ✅ Validation prevents invalid data
- ✅ CSRF token works (no errors)

---

### Test 1.3: Bulk Grade Entry
**URL**: `grades_bulk.php`

**Test Steps**:
1. Navigate to Academic > Bulk Grade Entry
2. Select filters:
   - Class: Grade 1
   - Subject: Mathematics
   - Term: 1
   - Exam Type: MidTerm
   - Max Marks: 50
3. Click "Load Students"
4. Verify table shows all students in Grade 1
5. Enter marks for each student (e.g., 35, 40, 28, 45, 38...)
6. Verify grades auto-calculate in real-time (JavaScript)
7. Click "Save All Grades"
8. Verify success message shows "X grades recorded successfully"
9. Go to grades_list.php and verify all grades saved

**Bulk Functions**:
- Click "Fill All with Passing Marks" → All marks set to 20 (40% of 50)
- Click "Clear All" → All marks cleared

**Expected Results**:
- ✅ Students load correctly for selected class
- ✅ Real-time grade calculation works (A for ≥40, B for ≥35, etc.)
- ✅ All grades save in single transaction
- ✅ Bulk functions work correctly

---

### Test 1.4: Report Cards
**URL**: `report_cards.php`

**Test Steps**:
1. Navigate to Academic > Report Cards
2. Select term: Term 1
3. Select pupil from dropdown
4. Click "Generate Report"
5. Verify report shows:
   - **Student Information**: Name, class, admission number
   - **Grades Table**: All subjects with CAT, MidTerm, EndTerm columns
   - **Overall Average**: Calculated correctly
   - **Overall GPA**: Calculated correctly (A=4.0, B=3.0, C=2.0, D=1.0, E=0.5, F=0.0)
   - **Class Position**: Student's rank out of total students
   - **Grading Scale**: Reference table (A=80-100, B=70-79, etc.)
   - **Teacher Comments**: Empty (for manual input)
   - **Signature Blocks**: Class Teacher, Head Teacher, Parent
6. Click browser print (Ctrl+P)
7. Verify print-friendly layout (filters and buttons hidden)

**Calculation Verification**:
- If student has: Math 85 (A), English 72 (B), Science 88 (A)
- Overall Average: (85 + 72 + 88) / 3 = 81.67
- Overall GPA: (4.0 + 3.0 + 4.0) / 3 = 3.67
- Grade: A (average ≥ 80)

**Expected Results**:
- ✅ Report generates with all data
- ✅ Calculations are accurate
- ✅ Print layout is professional
- ✅ No grades show "-" for missing exams

---

## Phase 2: Examinations Management Testing

### Test 2.1: Examinations List
**URL**: `examinations_list.php`

**Test Steps**:
1. Navigate to Academic > Examinations
2. Verify "Upcoming Examinations" widget shows future exams
3. Test filters:
   - Term: Select 1 → Shows only Term 1 exams
   - Academic Year: Select 2025/2026 → Shows only current year
   - Exam Type: Select CAT → Shows only CATs
   - Status: Select Scheduled → Shows only scheduled exams
   - Search: Type "Mid" → Shows Mid-Term exam
4. Verify each exam card shows:
   - Exam name
   - Type badge
   - Term
   - Date range
   - Status badge (color-coded)
   - Scheduled classes count
5. Click "View Schedule" icon → Redirects to examinations_schedule.php
6. Click "Edit" icon → Redirects to examinations_form.php
7. Click "Delete" icon → Shows confirmation modal (only if no schedules)

**Expected Results**:
- ✅ Filters work correctly
- ✅ Upcoming widget shows next 5 exams
- ✅ Status badges are color-coded (Scheduled=blue, Ongoing=yellow, Completed=green)
- ✅ Actions work with proper permissions

---

### Test 2.2: Create Examination
**URL**: `examinations_form.php`

**Test Steps**:
1. Click "Schedule New Exam"
2. Fill in form:
   - Exam Name: "Term 1 End-Term Exam 2026"
   - Exam Type: EndTerm
   - Term: 1
   - Academic Year: 2025/2026
   - Start Date: 2026-04-20
   - End Date: 2026-04-27
   - Total Marks: 100
   - Passing Marks: 40
   - Instructions: "Bring calculator and graph paper. No mobile phones."
   - Status: Scheduled
3. Click "Create & Schedule Classes"
4. Verify redirect to examinations_schedule.php
5. Verify success message appears

**Validation Tests**:
- Try end date before start date → Error: "End date must be after start date"
- Try passing marks > total marks → Error: "Passing marks cannot exceed total marks"
- Try empty exam name → Error: "Exam name is required"

**Expected Results**:
- ✅ Exam created successfully
- ✅ Redirects to schedule page for immediate scheduling
- ✅ Validation prevents invalid data

---

### Test 2.3: Schedule Exam for Classes
**URL**: `examinations_schedule.php?examID=X`

**Test Steps**:
1. From examinations_list.php, click "View Schedule" for any exam
2. Verify statistics cards show:
   - Total Schedules
   - Classes Scheduled
   - Subjects Scheduled
   - Completed Count
3. Fill in "Schedule Exam for Class" form:
   - Class: Grade 1
   - Subject: Mathematics
   - Date: 2026-04-21 (within exam date range)
   - Start Time: 08:00
   - End Time: 10:00
   - Room: Lab 1
   - Invigilator: Select teacher
   - Max Marks: 100
   - Duration: 120 minutes
   - Special Instructions: "Section A is compulsory"
4. Click "Add Schedule"
5. Verify schedule appears in table below
6. Repeat for different class/subject/time

**Conflict Detection Tests**:
- Try scheduling same class at overlapping time → Error: "Schedule conflict detected"
- Try scheduling outside exam date range → Should validate dates
- Try end time before start time → Error: "End time must be after start time"

**Schedule Actions**:
- Click trash icon on schedule → Confirm deletion → Schedule removed
- Verify statistics update after adding/deleting schedules

**Expected Results**:
- ✅ Schedules save successfully
- ✅ Conflict detection prevents overlapping times
- ✅ Statistics update in real-time
- ✅ Delete removes schedule correctly

---

## Phase 3: Integration Testing

### Test 3.1: Grades + Examinations Flow
1. Create exam "Term 1 CAT 1" with dates Feb 10-14
2. Schedule Mathematics for Grade 1 on Feb 11, 08:00-09:00
3. Schedule English for Grade 1 on Feb 11, 10:00-11:00
4. Schedule Science for Grade 1 on Feb 12, 08:00-09:00
5. Go to grades_bulk.php
6. Enter grades for all 3 subjects for Grade 1
7. Go to report_cards.php
8. Select Grade 1 student → Verify all 3 grades appear

**Expected Results**:
- ✅ Exam schedules guide grade entry
- ✅ Report cards show all entered grades
- ✅ No conflicts or data loss

---

### Test 3.2: CSRF Token Testing
1. Open users_form.php in browser
2. Open developer tools (F12) → Network tab
3. Fill form and submit
4. Verify POST request succeeds (200 status, not 403)
5. Check response for CSRF errors → Should be none
6. Verify user created/updated successfully

**Test on All Forms**:
- users_form.php
- grades_form.php
- grades_bulk.php
- examinations_form.php
- examinations_schedule.php

**Expected Results**:
- ✅ No CSRF errors on any form
- ✅ All submissions succeed
- ✅ Redirects work properly
- ✅ Flash messages appear

---

### Test 3.3: RBAC Permissions
**Test as Teacher (Role ID 3)**:
1. Login as teacher account
2. Navigate to Grades:
   - ✅ Can view grades_list.php
   - ✅ Can record new grades
   - ✅ Can use bulk entry
   - ❌ Cannot delete grades (if configured)
3. Navigate to Examinations:
   - ✅ Can view examinations_list.php
   - ✅ Can view schedules
   - ❌ Cannot create/edit/delete exams
4. Navigate to Users:
   - ❌ Should see "Access Denied" (teachers can't manage users)

**Test as Admin (Role ID 1)**:
1. Login as admin
2. Verify full access to:
   - ✅ Grades (all CRUD operations)
   - ✅ Examinations (all CRUD operations)
   - ✅ Users (all CRUD operations)
   - ✅ All reports and exports

**Expected Results**:
- ✅ Teachers have limited access
- ✅ Admins have full access
- ✅ Access denied messages show for unauthorized pages

---

## Phase 4: Performance & Data Integrity

### Test 4.1: Large Dataset
1. Use grades_bulk.php to enter grades for full class (30-40 students)
2. Verify all grades save (check total count)
3. Go to grades_list.php → Verify pagination works
4. Filter by class → Verify loads quickly (< 2 seconds)
5. Export CSV → Verify file contains all records

**Expected Results**:
- ✅ Bulk save handles 30+ records
- ✅ Pagination prevents page overload
- ✅ Filters perform well with large datasets

---

### Test 4.2: Data Validation
1. Try entering grade with marks = 150, maxMarks = 100 → Error
2. Try entering exam with startDate = 2026-05-01, endDate = 2026-04-01 → Error
3. Try scheduling exam outside exam period → Error
4. Try creating user with existing username → Error

**Expected Results**:
- ✅ All validation rules enforce data integrity
- ✅ Error messages are clear and helpful

---

## Phase 5: Cross-Browser Testing
Test on:
- ✅ Google Chrome
- ✅ Microsoft Edge
- ✅ Mozilla Firefox

**Verify**:
- Forms submit correctly
- JavaScript real-time calculations work
- Tooltips display properly
- Modals open/close correctly
- Print layouts work

---

## Summary Checklist

### Grades Management
- [ ] grades_list.php loads and filters work
- [ ] grades_form.php saves individual grades
- [ ] grades_bulk.php saves multiple grades
- [ ] report_cards.php generates accurate reports
- [ ] CSV export downloads correct data
- [ ] Auto-grade calculation works (A-F)

### Examinations Management
- [ ] examinations_list.php shows exams with filters
- [ ] examinations_form.php creates exams
- [ ] examinations_schedule.php schedules classes
- [ ] Conflict detection prevents overlaps
- [ ] Statistics dashboard shows accurate counts
- [ ] Delete only works when no schedules exist

### Security & Permissions
- [ ] CSRF tokens work on all forms
- [ ] No ERR_CACHE_MISS errors
- [ ] RBAC permissions enforced correctly
- [ ] Session management works (no logouts)

### User Experience
- [ ] Success/error messages show clearly
- [ ] Forms validate before submission
- [ ] Redirects work after actions
- [ ] Loading indicators (if implemented)
- [ ] Tooltips provide helpful info
- [ ] Print-friendly report cards

---

## Next Phases to Implement
1. **Timetable Builder**: Visual drag-drop interface for class schedules
2. **Attendance Analytics**: Charts and trends for student attendance
3. **Communication Hub**: Messaging between teachers/parents
4. **Mobile/PWA**: Responsive mobile interface

---

## Troubleshooting

### Issue: CSRF Token Failed
**Solution**: Clear browser cache (Ctrl+Shift+Delete), refresh page

### Issue: Session Expired
**Solution**: Login again, session timeout is 30 minutes

### Issue: Grades Not Saving
**Solution**: Check browser console (F12) for JavaScript errors, verify database connection

### Issue: Migration Failed
**Solution**: Visit run_examinations_migration.php as admin, check error log

---

**Testing Complete**: All features implemented and ready for comprehensive testing!
