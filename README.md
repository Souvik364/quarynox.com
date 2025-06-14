# Exam Paper Repository

A web application for managing and downloading exam papers from various colleges, courses, and subjects.

## Features

- Browse and search exam papers by college, course, subject, year, and semester
- Admin panel for managing colleges, courses, subjects, and papers
- Upload papers with file links
- Grid and list view options
- Responsive design

## Setup Instructions

### Database Setup

1. Create a MySQL database on your InfinityFree hosting account
2. Import the `setup_database.sql` file to create the necessary tables
3. Update the database credentials in `api.php` with your InfinityFree MySQL details:
   ```php
   $db_host = 'your_infinityfree_mysql_host';
   $db_name = 'your_database_name';
   $db_user = 'your_username';
   $db_pass = 'your_password';
   ```

### File Upload

This application uses external file links (like dl.surf) for paper storage. To upload papers:

1. Upload your PDF files to a file hosting service that provides direct download links
2. Use the direct download link when adding a new paper

### Admin Access

The default admin password is set to `pravat`. You can change this in the `index.html` file by modifying the login check:

```javascript
if (adminPassword.value === 'pravat') {
    // Admin login logic
}
```

## Troubleshooting

### Papers Not Loading

If papers are not loading from the database:

1. Check your database credentials in `api.php`
2. Verify that the database tables are created correctly
3. Check the browser console for any JavaScript errors
4. Make sure the API endpoint is accessible

### File Download Issues

If file downloads are not working:

1. Ensure the file links are valid and accessible
2. Check that the links start with "http://" or "https://"
3. Verify that the file hosting service allows direct downloads

## API Endpoints

The application uses the following API endpoints:

- `get_colleges`: Get all colleges
- `get_courses`: Get all courses or courses by college ID
- `get_subjects`: Get all subjects or subjects by course ID
- `get_papers`: Get all papers with optional filters
- `add_college`: Add a new college
- `add_course`: Add a new course
- `add_subject`: Add a new subject
- `upload_paper`: Upload a new paper
- `delete_college`: Delete a college
- `delete_course`: Delete a course
- `delete_subject`: Delete a subject
- `delete_paper`: Delete a paper