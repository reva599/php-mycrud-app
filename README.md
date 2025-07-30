# PHP CRUD Blog Application - ApexPlanet Internship

A comprehensive PHP CRUD (Create, Read, Update, Delete) blog application with user authentication built as part of the ApexPlanet Internship program. This project demonstrates modern PHP development practices, MySQL database integration, user authentication, and secure web development.

## 🎯 Project Overview

This project serves as a complete learning experience covering:

- **Backend Development**: PHP 7.4+ with MySQL database and advanced security features
- **Frontend Integration**: HTML5, CSS3, Bootstrap 5, and responsive design
- **Security Implementation**: SQL injection protection, RBAC, CSRF protection, and audit logging
- **Authentication System**: Secure user registration, login, role management, and session handling
- **Development Environment**: XAMPP local server setup with security configurations
- **Version Control**: Git and GitHub integration with professional workflows
- **Code Quality**: Modern PHP practices, security best practices, and comprehensive documentation

## 🛠️ Technology Stack

- **Language**: PHP 7.4+
- **Database**: MySQL 8.0+
- **Web Server**: Apache (via XAMPP)
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Version Control**: Git
- **IDE**: Visual Studio Code
- **Package Manager**: Composer (future implementation)

## 🔐 Advanced Security Features

### **SQL Injection Protection**

- ✅ **Prepared Statements**: All database queries use MySQLi prepared statements with parameter binding
- ✅ **Input Validation**: Multi-layer validation before database operations
- ✅ **Error Handling**: Secure error handling without information disclosure
- ✅ **Query Sanitization**: Comprehensive input sanitization and type checking

### **Role-Based Access Control (RBAC)**

- ✅ **4-Tier User Hierarchy**: Subscriber → Author → Editor → Admin
- ✅ **Permission System**: Hierarchical permission checking with role inheritance
- ✅ **Access Control**: Page-level and function-level access restrictions
- ✅ **Post Ownership**: Users can only modify their own content (unless admin/editor)
- ✅ **Admin Dashboard**: Comprehensive user and role management interface

### **Authentication & Session Security**

- ✅ **Secure Password Hashing**: PHP `password_hash()` with `PASSWORD_DEFAULT`
- ✅ **Session Management**: Secure session handling with timeout and regeneration
- ✅ **Rate Limiting**: Brute force protection with login attempt tracking
- ✅ **Account Lockout**: Automatic lockout after failed login attempts
- ✅ **CSRF Protection**: Cross-site request forgery prevention with secure tokens
- ✅ **Audit Logging**: Comprehensive activity tracking for security monitoring

### **Input Validation & Sanitization**

- ✅ **XSS Prevention**: HTML entity encoding and content filtering
- ✅ **Data Validation**: Type-specific validation for all input fields
- ✅ **Content Filtering**: Detection and blocking of malicious content patterns
- ✅ **Length Validation**: Minimum and maximum length enforcement
- ✅ **Format Validation**: Email, username, and password format checking

### **Database Security**

- ✅ **Foreign Key Constraints**: Referential integrity enforcement
- ✅ **Check Constraints**: Valid role and status values only
- ✅ **Indexes**: Performance optimization for security queries
- ✅ **Audit Tables**: Login attempts, user sessions, and activity logging
- ✅ **Data Integrity**: Comprehensive data validation at database level

## 📋 Prerequisites

Before setting up this project, ensure you have:

1. **XAMPP** - Apache + MySQL + PHP bundle
2. **Git** - Version control system
3. **Visual Studio Code** - Code editor with PHP extensions
4. **GitHub Account** - For repository hosting

## 🚀 Installation & Setup

### Step 1: Install XAMPP

1. **Download XAMPP**:

   - Visit [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Download the latest version for your operating system
   - Install with default settings

2. **Start Services**:

   ```bash
   # Open XAMPP Control Panel
   # Start Apache and MySQL services
   # Verify at http://localhost/
   ```

3. **Verify Installation**:
   - Open browser and navigate to `http://localhost/`
   - You should see the XAMPP dashboard

### Step 2: Set Up Development Environment

1. **Install Visual Studio Code**:

   ```bash
   # Download from https://code.visualstudio.com/
   # Install the following extensions:
   # - PHP Intelephense
   # - PHP Debug
   # - Code Runner
   ```

2. **Configure PHP Extensions**:
   - Open VS Code
   - Go to Extensions (Ctrl+Shift+X)
   - Install recommended PHP extensions

### Step 3: Clone and Set Up Project

1. **Clone Repository**:

   ```bash
   git clone https://github.com/YOUR-USERNAME/php-mycrud-app.git
   cd php-mycrud-app
   ```

2. **Move to XAMPP Directory**:

   ```bash
   # Copy project to XAMPP htdocs folder
   # Windows: C:\xampp\htdocs\php-mycrud-app\
   # macOS: /Applications/XAMPP/htdocs/php-mycrud-app/
   # Linux: /opt/lampp/htdocs/php-mycrud-app/
   ```

3. **Access Application**:
   - Open browser and navigate to `http://localhost/php-mycrud-app/`
   - You should see the setup verification page

## 📁 Project Structure

```
php-mycrud-app/
├── index.php              # Main entry point & setup verification
├── README.md              # Project documentation
├── config/                # Configuration files (future)
│   ├── database.php       # Database configuration
│   └── app.php           # Application settings
├── src/                   # Source code (future)
│   ├── models/           # Data models
│   ├── views/            # HTML templates
│   └── controllers/      # Business logic
├── public/               # Public assets (future)
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── images/          # Image assets
├── database/             # Database files (future)
│   ├── migrations/      # Database schema
│   └── seeds/           # Sample data
└── tests/               # Unit tests (future)
    ├── unit/
    └── integration/
```

## 🔧 Configuration

### Database Setup

1. **Create Database**:

   ```sql
   -- Access phpMyAdmin at http://localhost/phpmyadmin/
   CREATE DATABASE crud_app;
   USE crud_app;
   ```

2. **Configure Connection**:
   ```php
   // config/database.php (future file)
   $db_config = [
       'host' => 'localhost',
       'username' => 'root',
       'password' => '',
       'database' => 'crud_app',
       'port' => 3306
   ];
   ```

## 🧪 Testing

### Manual Testing

1. Start XAMPP services (Apache + MySQL)
2. Navigate to `http://localhost/php-mycrud-app/`
3. Verify all status indicators are green
4. Check system information display

### Automated Testing (Future)

```bash
# PHPUnit tests will be added in future iterations
composer test
```

## 📚 Learning Objectives

This project covers:

- [x] **Environment Setup**: XAMPP installation and configuration
- [x] **Version Control**: Git repository initialization and GitHub integration
- [x] **PHP Basics**: Syntax, variables, functions, and OOP concepts
- [ ] **Database Integration**: MySQL connection and CRUD operations
- [ ] **Security**: Input validation, SQL injection prevention
- [ ] **Frontend Integration**: Responsive design and user experience
- [ ] **Error Handling**: Proper exception management
- [ ] **Code Organization**: MVC pattern implementation

## 🎥 Documentation & Recording

### Screen Recording Checklist

- [ ] XAMPP installation process
- [ ] Git repository setup and GitHub push
- [ ] VS Code configuration with PHP extensions
- [ ] Application running on localhost
- [ ] Code walkthrough and explanation

### Deliverables

1. **GitHub Repository**: Complete source code with documentation
2. **LinkedIn Video**: Screen recording of setup and demonstration
3. **ApexPlanet Submission**: Repository link and video showcase

## 🤝 Contributing

This is an educational project for the ApexPlanet Internship. Future enhancements may include:

- User authentication system
- Advanced CRUD operations
- RESTful API implementation
- Frontend framework integration
- Unit testing coverage
- Deployment automation

## 📄 License

This project is created for educational purposes as part of the ApexPlanet Internship program.

## 📞 Support

For questions or issues:

- **Internship Program**: Contact ApexPlanet mentors
- **Technical Issues**: Create GitHub issues
- **Documentation**: Refer to inline code comments

## 🏆 Acknowledgments

- **ApexPlanet**: For providing the internship opportunity
- **PHP Community**: For excellent documentation and resources
- **XAMPP Team**: For the comprehensive development environment

---

**Project Status**: ✅ Environment Setup Complete | 🚧 CRUD Development In Progress

**Last Updated**: January 2024 | **Version**: 1.0.0 | **Author**: ApexPlanet Intern
