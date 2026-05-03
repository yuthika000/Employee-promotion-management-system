# PROMO TRACKER

Employee Promotion Management System - A comprehensive web-based application for managing employee promotions, tracking career progression, and maintaining accurate employee data.

## 🌟 Features

- **Employee Data Management**: Migrate and synchronize employee records between source and master tables
- **Department Management**: Validate and manage department information with lookup tables
- **Designation Tracking**: Track employee positions and roles across the organization
- **Grade Management**: Monitor employee grade levels and promotion eligibility
- **Data Comparison**: Compare data between HMS2.EMP_HMS2_TABLE and PERSON_TABLE to identify inconsistencies
- **Deduplication**: Identify and resolve duplicate records within PERSON_TABLE
- **User Authentication**: Secure login/signup system using CPF_NO as the primary identifier
- **Comprehensive Reporting**: Generate detailed reports on employee data and system statistics
- **Modern UI**: Beautiful, responsive dark-themed interface with smooth animations

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+
- **Database**: Oracle Database with OCI8 extension
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: XAMPP (Apache)
- **Authentication**: PHP Sessions with bcrypt password hashing

## 📋 Prerequisites

- PHP 7.4 or higher
- Oracle Database 11g or higher
- XAMPP or similar Apache server
- OCI8 PHP extension for Oracle connectivity
- Modern web browser (Chrome, Firefox, Edge)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd HMS
   ```

2. **Configure Database Connection**
   
   Edit `config.php` with your Oracle database credentials:
   ```php
   $conn = oci_connect(
       'your_username',
       'your_password',
       'your_host:your_port/your_service_name'
   );
   ```

3. **Enable OCI8 Extension**
   
   Ensure the OCI8 extension is enabled in your `php.ini`:
   ```ini
   extension=oci8
   ```
   
   Restart Apache after making changes.

4. **Create Database Tables**
   
   Run the following SQL commands to create necessary tables:
   
   ```sql
   -- Users table for authentication
   CREATE TABLE users (
       id NUMBER GENERATED ALWAYS AS IDENTITY,
       cpf_no VARCHAR2(50) UNIQUE NOT NULL,
       email VARCHAR2(100) UNIQUE NOT NULL,
       password VARCHAR2(255) NOT NULL,
       created_at TIMESTAMP DEFAULT SYSTIMESTAMP
   );
   
   -- Department lookup table
   CREATE TABLE DEPARTMENT (
       NR NUMBER PRIMARY KEY,
       NAME_FORMAL VARCHAR2(100) NOT NULL
   );
   
   -- Designation lookup table
   CREATE TABLE DESIGNATION (
       NR NUMBER PRIMARY KEY,
       DESIGNATION VARCHAR2(100) NOT NULL
   );
   
   -- Grade lookup table
   CREATE TABLE GRADE (
       NR NUMBER PRIMARY KEY,
       GRADE_NAME VARCHAR2(50) NOT NULL
   );
   
   -- Master employee table
   CREATE TABLE PERSON_TABLE (
       SAP_ID VARCHAR2(50) PRIMARY KEY,
       NAME_FIRST VARCHAR2(100),
       NAME_LAST VARCHAR2(100),
       EMP_GRADE NUMBER,
       DESIGNATION NUMBER,
       SEL_DEPARTMENT NUMBER,
       ID_NO_NAME VARCHAR2(50),
       PHONE_1_NR VARCHAR2(20),
       GENDER CHAR(1),
       DOB DATE,
       -- Add other required fields
       FOREIGN KEY (EMP_GRADE) REFERENCES GRADE(NR),
       FOREIGN KEY (DESIGNATION) REFERENCES DESIGNATION(NR),
       FOREIGN KEY (SEL_DEPARTMENT) REFERENCES DEPARTMENT(NR)
   );
   ```

5. **Start the Server**
   
   Start XAMPP Apache server and ensure Oracle database is running.

6. **Access the Application**
   
   Open your browser and navigate to:
   ```
   http://localhost/HMS/
   ```

## 📖 Usage

### Initial Setup

1. **Create an Admin Account**
   - Navigate to `signup.php`
   - Enter your CPF_NO, email, and password
   - Click "Sign Up" to create your account

2. **Login**
   - Navigate to `login.php`
   - Enter your CPF_NO and password
   - Click "Sign In" to access the dashboard

### Main Operations

#### 1. Department Check
- Validates department names against the DEPARTMENT lookup table
- Adds missing departments with unique NR codes
- Access via: Dashboard → Department Check

#### 2. Designation Check
- Validates designations against the DESIGNATION lookup table
- Adds missing designations with unique NR codes
- Access via: Dashboard → Designation Check

#### 3. Grade Check
- Validates grade levels against the GRADE lookup table
- Adds missing grades with unique NR codes
- Access via: Dashboard → Grade Check

#### 4. Add Employee
- Inserts new employee records into HMS2.EMP_HMS2_TABLE
- Requires employee name, CPF_NO, department, designation, grade, and other details
- Access via: Dashboard → Add Employee

#### 5. Sync to PERSON_TABLE
- Migrates employee records from HMS2.EMP_HMS2_TABLE to PERSON_TABLE
- Converts text values to numeric IDs using lookup tables
- Checks for duplicates and provides detailed feedback
- Access via: Dashboard → Sync to PERSON_TABLE

#### 6. Compare Tables
- Compares data between HMS2.EMP_HMS2_TABLE and PERSON_TABLE
- Identifies mismatches in department, designation, grade, and phone number
- Allows updating PERSON_TABLE with corrected values
- Access via: Dashboard → Compare Tables

#### 7. Deduplicate Records
- Identifies duplicate records in PERSON_TABLE based on ID_NO_NAME
- Resolves inconsistencies by using HMS2.EMP_HMS2_TABLE as source
- Access via: Dashboard → Deduplicate Records

## 📁 Project Structure

```
HMS/
├── config.php                 # Database configuration
├── index.php                  # Landing page
├── login.php                  # User login
├── signup.php                 # User registration
├── logout.php                 # User logout
├── dashboard.php              # Main dashboard
├── master_table_add.php       # Data migration tool
├── compare_tables.php         # Table comparison tool
├── compare_person_table.php   # Deduplication tool
├── department_check.php      # Department validation
├── designation_check.php      # Designation validation
├── grade_check.php            # Grade validation
├── add_data.php              # Add new employee
├── check_counts.php          # Record count verification
├── internship_report.php     # Internship report generator
└── README.md                 # This file
```

## 🔐 Security Features

- **Password Hashing**: Uses bcrypt (PASSWORD_DEFAULT) for secure password storage
- **Session Management**: PHP sessions for authenticated user state
- **SQL Injection Prevention**: Prepared statements with parameter binding
- **Input Validation**: Server-side validation for all user inputs
- **Access Control**: Session-based authentication for protected pages

## 🐛 Troubleshooting

### OCI8 Extension Not Found
- Ensure OCI8 extension is enabled in `php.ini`
- Download and install Oracle Instant Client
- Restart Apache server

### Database Connection Failed
- Verify Oracle database is running
- Check connection parameters in `config.php`
- Ensure proper network connectivity to database server

### ORA-00942 Table Not Found
- Verify table names are in uppercase (Oracle default)
- Check that tables exist in the correct schema
- Use schema prefix if needed (e.g., HMS.PERSON_TABLE)

### Session Not Persisting
- Check PHP session configuration in `php.ini`
- Ensure proper write permissions for session save path
- Verify cookies are enabled in browser

## 📊 Database Schema

### Key Tables

- **users**: User authentication data
- **DEPARTMENT**: Department lookup table
- **DESIGNATION**: Designation/position lookup table
- **GRADE**: Grade/level lookup table
- **PERSON_TABLE**: Master employee table
- **HMS2.EMP_HMS2_TABLE**: Source employee data table

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is developed as part of an internship program. Please contact the project maintainers for licensing information.

## 👥 Authors

- **Intern Name** - Initial development
- **Organization** - Project guidance and supervision

## 🙏 Acknowledgments

- Oracle Corporation for database technology
- PHP community for excellent documentation
- XAMPP team for development environment

## 📞 Support

For support and queries, please contact:
- Email: support@example.com
- Project Repository: [GitHub Issues]

---

**Note**: This system is designed for employee promotion management within organizations. Ensure proper data backup before performing bulk operations like sync or truncate.
