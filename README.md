# Dochazka pro firmu - Company Attendance System

## Project Structure

```
├── web/                              # Web-accessible files
│   ├── scripts/                      # PHP utility scripts
│   │   ├── config.php                # Centralized database & config
│   │   └── csrf.php                  # CSRF token handling
│   │
│   ├── styles.css                    # Unified stylesheet
│   ├── index.html                    # Home page
│   ├── zaznamenat.html               # Attendance entry form (static)
│   ├── login.html                    # Legacy login page
│   ├── admin-login.html              # Redirect to login form
│   │
│   ├── zaznamenat.php                # Attendance entry processing
│   ├── zobrazit.php                  # Attendance view with pagination
│   ├── admin-login-form.php          # Admin login form (secure)
│   ├── admin-dashboard.php           # Admin dashboard
│   ├── admin-login.php               # Legacy redirect (backward compatibility)
│   ├── execute.php                   # Whitelisted SQL execution
│   └── logout.php                    # Secure logout
│
├── deploy/                           # Deployment configuration
│   └── apache.conf                   # Apache web server configuration
│
├── .github/                          # GitHub configuration
│   └── workflows/
│       └── deploy.yaml               # CI/CD deployment pipeline (Raspberry Pi)
│
├── .env                              # Database configuration (copy of .env.example)
├── .gitignore                        # Git ignore rules
└── README.md                         # This file
```

## Setup Instructions

### 1. Create .env File

Create a `.env` file in the project root (same level as the `web/` folder):

```
DB_SERVERNAME=localhost
DB_USERNAME=root
DB_PASSWORD=yourpassword
DB_NAME=company_attendance
```

### 2. Web Server Configuration

Point your web server's document root to the `web/` directory.

**PHP Development Server:**
```bash
cd web
php -S localhost:8000
```

Then visit `http://localhost:8000`

**Apache:**
Create `.htaccess` in project root to rewrite to web folder, or point DocumentRoot directly to web folder.

### 3. Database

The application will automatically create the database and tables on first run if they don't exist. The `config.php` script handles:
- Database connection management
- Automatic database creation
- Automatic table schema creation
- Error logging and handling

**Tables Created:**
- `testovaqi_table` - Main attendance records table

### 4. Web Server Configuration

**Apache:**
See `deploy/apache.conf` for the recommended Apache configuration. Point your DocumentRoot to the `web/` directory.

# Example .env

```
DB_SERVERNAME=localhost
DB_USERNAME=root
DB_PASSWORD=yourpassword
DB_NAME=company_attendance
```

## Admin Access
1. Click "Administrace" on home page
2. Login with:
   - Username: `admin`
   - Password: `admin123`
3. Execute read-only SQL queries (SELECT, SHOW, DESCRIBE)

## Database Schema

### testovaqi_table
```sql
CREATE TABLE testovaqi_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_entry (employee_id, date, time_in)
);
```