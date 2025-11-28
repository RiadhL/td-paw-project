# Algiers University - Student Attendance Management System

## Overview
A web-based Attendance Management System designed for Algiers University Benyoucef Benkhedda. The system provides role-based access for students, professors, and administrators to manage and track student attendance.

## Project Architecture

### Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript, jQuery 3.x
- **Backend**: PHP 8.2
- **Database**: PostgreSQL (compatible with MySQL/MariaDB syntax where possible)
- **Charts**: Chart.js for statistics visualization

### Directory Structure
```
/
├── includes/           # PHP configuration and utilities
│   ├── config.php      # Database configuration
│   ├── db_connect.php  # Database connection with try/catch
│   ├── init_db.php     # Database initialization and sample data
│   └── auth.php        # Authentication and session management
├── public/             # Web root
│   ├── css/style.css   # Main stylesheet (mobile-first)
│   ├── js/             # JavaScript files
│   │   ├── app.js      # Main application JS
│   │   ├── attendance.js # Attendance-specific jQuery features
│   │   └── validation.js # Form validation
│   ├── professor/      # Professor pages
│   ├── student/        # Student pages
│   ├── admin/          # Administrator pages
│   └── uploads/        # File uploads (justifications)
├── api/                # API endpoints
│   ├── add_student.php
│   ├── list_students.php
│   ├── update_student.php
│   ├── delete_student.php
│   ├── create_session.php
│   ├── close_session.php
│   └── export_students.php
└── logs/               # Error logs
```

### Database Schema
- **users**: Authentication and role management
- **students**: Student information
- **professors**: Professor information  
- **courses**: Course details
- **groups**: Student groups per course
- **student_courses**: Enrollment records
- **attendance_sessions**: Session records
- **attendance_records**: Individual attendance entries
- **justifications**: Absence justification requests

## User Roles

### Administrator
- View system statistics and charts
- Manage student records (CRUD)
- Import/export students (CSV/Excel format)
- Review and approve/reject justifications

### Professor
- View assigned courses and sessions
- Create and manage attendance sessions
- Mark student attendance and participation
- View attendance summaries with color-coded rows

### Student
- View enrolled courses
- Check attendance status per course
- Submit absence justifications with file upload

## Key Features

### From Tutorial 2 (jQuery)
- Attendance table with 6 sessions and participation columns
- Row highlighting based on absences (green <3, yellow 3-4, red 5+)
- Automatic message generation for attendance/participation status
- Form validation (student ID numbers only, names letters only, valid email)
- Dynamic student addition without page reload
- Report button with chart display
- jQuery hover effects and click handlers
- Search by name filter
- Sort by absences (ascending) / participation (descending)
- Highlight excellent students animation

### From Tutorial 3 (PHP)
- config.php and db_connect.php with try/catch error handling
- CRUD operations for students
- Attendance session management (create/open/close)
- JSON-compatible attendance records

## Demo Credentials
- **Admin**: admin / admin123
- **Professor**: prof_benali / prof123
- **Student**: ahmed_sara / student123

## Running the Application
The PHP built-in server runs on port 5000 serving the public directory.

## Recent Changes
- Initial implementation of complete attendance system
- Created November 2025
