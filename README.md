# PHP Full-Stack Project - Setup Guide

## 🚀 Project Overview

This is a complete full-stack PHP application with:
- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP REST API with PDO
- **Database**: MySQL
- **Server**: XAMPP

## 📁 Project Structure

```
career-guide/
├── backend/
│   └── api/
│       └── users.php          # REST API endpoints
├── frontend/
│   ├── index.html             # Main page
│   ├── css/
│   │   └── style.css          # Styling
│   └── js/
│       ├── api.js             # API service
│       └── app.js             # Application logic
├── config/
│   └── database.php           # Database connection
└── database/
    └── schema.sql             # Database schema
```

## 🔧 XAMPP Setup Instructions

### Step 1: Install XAMPP
1. Download XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
2. Install XAMPP on your system
3. Start **Apache** and **MySQL** from XAMPP Control Panel

### Step 2: Copy Project Files
1. Copy the `career-guide` folder to XAMPP's `htdocs` directory:
   - **Windows**: `C:\xampp\htdocs\`
   - **Linux**: `/opt/lampp/htdocs/` or `/home/abhishek/career-guide/career-guide`
   - **Mac**: `/Applications/XAMPP/htdocs/`

### Step 3: Create Database
1. Open **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click "SQL" tab
3. Copy and paste the contents of `database/schema.sql`
4. Click "Go" to execute the SQL

**OR** use MySQL command line:
```bash
mysql -u root -p < database/schema.sql
```

### Step 4: Configure Database Connection (if needed)
Edit `config/database.php` if your MySQL settings are different:
```php
private $host = "localhost";
private $db_name = "php_project_db";
private $username = "root";
private $password = "";  // Change if you have a password
```

### Step 5: Update API Base URL
Edit `frontend/js/api.js` and update the API_BASE_URL:
```javascript
const API_BASE_URL = 'http://localhost/career-guide/backend/api';
```

**Note**: Replace `career-guide` with your actual folder name in htdocs.

## 🌐 Running the Application

1. **Start XAMPP**:
   - Open XAMPP Control Panel
   - Start **Apache** (for PHP)
   - Start **MySQL** (for database)

2. **Access the Application**:
   - Open browser and go to: [http://localhost/career-guide/frontend/index.html](http://localhost/career-guide/frontend/index.html)

3. **Test the Application**:
   - You should see the User Management interface
   - Try adding, editing, and deleting users
   - All data is saved to MySQL database

## 🔌 API Endpoints

### Base URL
```
http://localhost/career-guide/backend/api/users.php
```

### Available Endpoints

#### 1. Get All Users
```http
GET /users.php
```
**Response**:
```json
{
  "success": true,
  "data": [...],
  "count": 3
}
```

#### 2. Get Single User
```http
GET /users.php?id=1
```
**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890"
  }
}
```

#### 3. Create User
```http
POST /users.php
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "1234567890"
}
```

#### 4. Update User
```http
PUT /users.php
Content-Type: application/json

{
  "id": 1,
  "name": "John Updated",
  "email": "john@example.com",
  "phone": "1234567890"
}
```

#### 5. Delete User
```http
DELETE /users.php
Content-Type: application/json

{
  "id": 1
}
```

## 🧪 Testing with cURL

```bash
# Get all users
curl http://localhost/career-guide/backend/api/users.php

# Create user
curl -X POST http://localhost/career-guide/backend/api/users.php \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","phone":"1234567890"}'

# Update user
curl -X PUT http://localhost/career-guide/backend/api/users.php \
  -H "Content-Type: application/json" \
  -d '{"id":1,"name":"Updated User","email":"updated@example.com","phone":"0987654321"}'

# Delete user
curl -X DELETE http://localhost/career-guide/backend/api/users.php \
  -H "Content-Type: application/json" \
  -d '{"id":1}'
```

## 🛠️ Troubleshooting

### Issue: "Connection error" message
- Make sure MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Verify database `php_project_db` exists

### Issue: "404 Not Found" for API calls
- Verify project is in `htdocs` folder
- Check `API_BASE_URL` in `frontend/js/api.js`
- Make sure Apache is running

### Issue: CORS errors
- CORS headers are already included in `users.php`
- If still having issues, check browser console for errors

### Issue: No data showing
- Open browser DevTools (F12) → Network tab
- Check if API calls are successful
- Verify database has data (check phpMyAdmin)

## 📝 Features

✅ **Full CRUD Operations**
- Create new users
- Read/List all users
- Update existing users
- Delete users

✅ **Modern UI**
- Responsive design
- Smooth animations
- Gradient backgrounds
- Glassmorphism effects

✅ **API Integration**
- RESTful API architecture
- JSON data format
- Error handling
- CORS support

✅ **Database**
- MySQL with PDO
- Prepared statements (SQL injection protection)
- Transaction support

## 🎨 Customization

### Change Colors
Edit `frontend/css/style.css` CSS variables:
```css
:root {
    --primary-color: #6366f1;
    --secondary-color: #8b5cf6;
    /* ... more colors ... */
}
```

### Add More Fields
1. Update database schema in `database/schema.sql`
2. Modify API in `backend/api/users.php`
3. Add form fields in `frontend/index.html`
4. Update JavaScript in `frontend/js/app.js`

## 📚 Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache (XAMPP)
- **API**: REST with JSON

## 🎯 Next Steps

1. Add authentication (login/register)
2. Implement pagination for large datasets
3. Add search and filtering
4. Export data to CSV/PDF
5. Add file upload functionality
6. Create admin dashboard

---

**Enjoy building with PHP! 🚀**
