<?php
session_start();
require_once 'config.php';

// Fetch data for the report
$emp_count = 0;
$dept_count = 0;
$grade_count = 0;

if ($conn) {
    // Get employee count
    $sql = "SELECT COUNT(*) as cnt FROM PERSON_TABLE";
    $stmt = oci_parse($conn, $sql);
    if (oci_execute($stmt)) {
        $row = oci_fetch_assoc($stmt);
        $emp_count = $row['CNT'];
    }
    oci_free_statement($stmt);
    
    // Get department count
    $sql = "SELECT COUNT(*) as cnt FROM DEPARTMENT";
    $stmt = oci_parse($conn, $sql);
    if (oci_execute($stmt)) {
        $row = oci_fetch_assoc($stmt);
        $dept_count = $row['CNT'];
    }
    oci_free_statement($stmt);
    
    // Get grade count
    $sql = "SELECT COUNT(*) as cnt FROM GRADE";
    $stmt = oci_parse($conn, $sql);
    if (oci_execute($stmt)) {
        $row = oci_fetch_assoc($stmt);
        $grade_count = $row['CNT'];
    }
    oci_free_statement($stmt);
    
    oci_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Report - PROMO TRACKER</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        
        .report-container {
            max-width: 210mm;
            margin: 20px auto;
            background: white;
            padding: 25mm 20mm;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        @media print {
            body {
                background: white;
            }
            .report-container {
                margin: 0;
                padding: 20mm;
                box-shadow: none;
                max-width: none;
            }
            .no-print {
                display: none;
            }
        }
        
        .cover-page {
            text-align: center;
            padding: 50px 0;
            border-bottom: 3px solid #333;
            margin-bottom: 30px;
        }
        
        .cover-page h1 {
            font-size: 36px;
            margin-bottom: 20px;
            color: #1a1a1a;
        }
        
        .cover-page h2 {
            font-size: 24px;
            margin-bottom: 30px;
            color: #444;
        }
        
        .cover-page .subtitle {
            font-size: 18px;
            margin-top: 40px;
            color: #666;
        }
        
        .section {
            margin-bottom: 30px;
            page-break-after: always;
        }
        
        .section:last-child {
            page-break-after: avoid;
        }
        
        h2.section-title {
            font-size: 22px;
            margin-bottom: 15px;
            color: #1a1a1a;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        
        h3.subsection-title {
            font-size: 18px;
            margin: 20px 0 10px 0;
            color: #333;
        }
        
        p {
            margin-bottom: 12px;
            text-align: justify;
            font-size: 12pt;
        }
        
        .index-list {
            list-style-type: none;
            padding-left: 0;
        }
        
        .index-list li {
            margin-bottom: 8px;
            font-size: 12pt;
        }
        
        .index-list .page-number {
            float: right;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11pt;
        }
        
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        
        .feedback-form {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #333;
        }
        
        .feedback-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .feedback-form input, 
        .feedback-form textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #333;
            font-family: inherit;
        }
        
        .references-list {
            list-style-type: decimal;
            padding-left: 20px;
        }
        
        .references-list li {
            margin-bottom: 8px;
            font-size: 12pt;
        }
        
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #333;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        
        .btn-print:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">Print Report</button>
    
    <div class="report-container">
        <!-- Cover Page -->
        <div class="cover-page">
            <h1>INTERNSHIP REPORT</h1>
            <h2>On</h2>
            <h2 style="font-size: 28px; color: #6366f1;">PROMO TRACKER</h2>
            <h2 style="font-size: 20px;">Employee Promotion Management System</h2>
            <div class="subtitle">
                <p>Submitted in partial fulfillment of the requirements for the award of degree</p>
                <p style="margin-top: 10px;">Bachelor of Technology / Master of Computer Applications</p>
            </div>
        </div>
        
        <!-- Abstract -->
        <div class="section">
            <h2 class="section-title">I. ABSTRACT</h2>
            <p>
                This internship report details the development and implementation of "PROMO TRACKER," an Employee Promotion Management System designed to streamline and automate the promotion process within organizations. The system was developed using PHP as the backend programming language, Oracle Database for data management, and modern web technologies for the user interface.
            </p>
            <p>
                The primary objective of PROMO TRACKER is to provide a comprehensive solution for managing employee promotions, tracking career progression, and maintaining accurate records of department, designation, and grade information. The system integrates data from multiple sources, including HMS2.EMP_HMS2_TABLE, and synchronizes it with the master PERSON_TABLE, ensuring data consistency across the organization.
            </p>
            <p>
                During the internship period, the system was designed, developed, tested, and deployed. Key features include employee data migration, department and designation management, grade tracking, and comprehensive reporting capabilities. The system handles <?php echo $emp_count; ?> employee records across <?php echo $dept_count; ?> departments with <?php echo $grade_count; ?> different grade levels.
            </p>
            <p>
                This report covers the system architecture, database design, implementation details, testing procedures, and future enhancements. The project demonstrates practical application of software engineering principles, database management, and web development skills in a real-world scenario.
            </p>
        </div>
        
        <!-- Index -->
        <div class="section">
            <h2 class="section-title">II. INDEX</h2>
            <ul class="index-list">
                <li>I. ABSTRACT <span class="page-number">1</span></li>
                <li>II. INDEX <span class="page-number">2</span></li>
                <li>III. CONTENTS <span class="page-number">3</span></li>
                <li>IV. CONCLUSION <span class="page-number">18</span></li>
                <li>V. FEEDBACK <span class="page-number">19</span></li>
                <li>VI. REFERENCES <span class="page-number">20</span></li>
            </ul>
        </div>
        
        <!-- Contents -->
        <div class="section">
            <h2 class="section-title">III. CONTENTS</h2>
            
            <h3 class="subsection-title">1. INTRODUCTION</h3>
            <p>
                The PROMO TRACKER system was developed to address the need for an efficient and automated employee promotion management system within organizations. Traditional methods of tracking promotions often involve manual processes, spreadsheets, and paper-based records, which are prone to errors and inefficiencies.
            </p>
            <p>
                The internship provided an opportunity to work on a real-world software development project, applying theoretical knowledge to practical scenarios. The project was completed under the guidance of industry professionals, providing valuable insights into software development lifecycle, database management, and web application development.
            </p>
            
            <h3 class="subsection-title">2. OBJECTIVES</h3>
            <p>
                The main objectives of developing PROMO TRACKER were:
            </p>
            <ul style="margin-left: 20px; margin-bottom: 15px;">
                <li>To automate the employee promotion management process</li>
                <li>To provide a centralized system for tracking employee career progression</li>
                <li>To ensure data consistency between different database tables</li>
                <li>To reduce manual errors and improve efficiency</li>
                <li>To provide comprehensive reporting and analysis capabilities</li>
            </ul>
            
            <h3 class="subsection-title">3. SYSTEM ARCHITECTURE</h3>
            <p>
                PROMO TRACKER follows a three-tier architecture:
            </p>
            <ul style="margin-left: 20px; margin-bottom: 15px;">
                <li><strong>Presentation Layer:</strong> HTML, CSS, JavaScript for user interface</li>
                <li><strong>Application Layer:</strong> PHP for business logic and server-side processing</li>
                <li><strong>Data Layer:</strong> Oracle Database for data storage and management</li>
            </ul>
            
            <h3 class="subsection-title">4. DATABASE DESIGN</h3>
            <p>
                The system uses Oracle Database with the following key tables:
            </p>
            <table>
                <tr>
                    <th>Table Name</th>
                    <th>Description</th>
                    <th>Records</th>
                </tr>
                <tr>
                    <td>PERSON_TABLE</td>
                    <td>Master employee table with promotion data</td>
                    <td><?php echo $emp_count; ?></td>
                </tr>
                <tr>
                    <td>DEPARTMENT</td>
                    <td>Department lookup table</td>
                    <td><?php echo $dept_count; ?></td>
                </tr>
                <tr>
                    <td>DESIGNATION</td>
                    <td>Designation/Position lookup table</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>GRADE</td>
                    <td>Grade/Level lookup table</td>
                    <td><?php echo $grade_count; ?></td>
                </tr>
                <tr>
                    <td>HMS2.EMP_HMS2_TABLE</td>
                    <td>Source employee data table</td>
                    <td>-</td>
                </tr>
            </table>
            
            <h3 class="subsection-title">5. KEY FEATURES</h3>
            <p>
                PROMO TRACKER includes the following key features:
            </p>
            <ul style="margin-left: 20px; margin-bottom: 15px;">
                <li><strong>Data Migration:</strong> Sync employee data from source table to master table</li>
                <li><strong>Department Management:</strong> Add and manage department information</li>
                <li><strong>Designation Management:</strong> Track employee positions and roles</li>
                <li><strong>Grade Tracking:</strong> Monitor employee grade levels and progression</li>
                <li><strong>Comparison Tool:</strong> Compare data between source and master tables</li>
                <li><strong>User Authentication:</strong> Secure login system with CPF_NO based access</li>
                <li><strong>Reporting:</strong> Generate comprehensive reports on employee data</li>
            </ul>
            
            <h3 class="subsection-title">6. IMPLEMENTATION DETAILS</h3>
            <p>
                The system was implemented using PHP with OCI8 extension for Oracle database connectivity. The development process involved:
            </p>
            <ul style="margin-left: 20px; margin-bottom: 15px;">
                <li>Database schema design and table creation</li>
                <li>PHP backend development for business logic</li>
                <li>Frontend development using HTML, CSS, and JavaScript</li>
                <li>Data migration scripts for synchronizing tables</li>
                <li>User authentication and session management</li>
                <li>Error handling and validation</li>
            </ul>
            
            <h3 class="subsection-title">7. TESTING</h3>
            <p>
                The system underwent rigorous testing including:
            </p>
            <ul style="margin-left: 20px; margin-bottom: 15px;">
                <li><strong>Unit Testing:</strong> Individual component testing</li>
                <li><strong>Integration Testing:</strong> Testing data flow between components</li>
                <li><strong>Database Testing:</strong> Verifying data integrity and consistency</li>
                <li><strong>User Interface Testing:</strong> Ensuring responsive and intuitive design</li>
            </ul>
            
            <h3 class="subsection-title">8. CHALLENGES FACED</h3>
            <p>
                During the development process, several challenges were encountered and resolved:
            </p>
            <ul style="margin-left: 20px; margin-bottom: 15px;">
                <li>Oracle table name case sensitivity issues</li>
                <li>Data type mismatches between tables</li>
                <li>Transaction management and locking issues</li>
                <li>CPF_NO to SAP_ID mapping for data synchronization</li>
                <li>Phone number (CELL to PHONE_1_NR) data migration</li>
            </ul>
            
            <h3 class="subsection-title">9. TECHNOLOGIES USED</h3>
            <table>
                <tr>
                    <th>Technology</th>
                    <th>Usage</th>
                </tr>
                <tr>
                    <td>PHP</td>
                    <td>Backend programming language</td>
                </tr>
                <tr>
                    <td>Oracle Database</td>
                    <td>Data storage and management</td>
                </tr>
                <tr>
                    <td>OCI8 Extension</td>
                    <td>Oracle database connectivity</td>
                </tr>
                <tr>
                    <td>HTML5</td>
                    <td>Structure of web pages</td>
                </tr>
                <tr>
                    <td>CSS3</td>
                    <td>Styling and layout</td>
                </tr>
                <tr>
                    <td>JavaScript</td>
                    <td>Client-side interactivity</td>
                </tr>
                <tr>
                    <td>XAMPP</td>
                    <td>Local development server</td>
                </tr>
            </table>
        </div>
        
        <!-- Conclusion -->
        <div class="section">
            <h2 class="section-title">IV. CONCLUSION</h2>
            <p>
                The development of PROMO TRACKER was a valuable learning experience that provided hands-on experience in software development, database management, and web application development. The system successfully addresses the need for an automated employee promotion management system.
            </p>
            <p>
                Throughout the internship, I gained practical knowledge of PHP programming, Oracle database management, and modern web development practices. The project demonstrated the importance of proper system architecture, database design, and testing in software development.
            </p>
            <p>
                The system is currently functional and handles <?php echo $emp_count; ?> employee records. Future enhancements could include mobile application development, advanced analytics, machine learning for promotion prediction, and integration with HR management systems.
            </p>
            <p>
                This internship has prepared me for a career in software development by providing real-world project experience and exposure to industry-standard technologies and practices.
            </p>
        </div>
        
        <!-- Feedback -->
        <div class="section">
            <h2 class="section-title">V. FEEDBACK</h2>
            
            <div class="feedback-form">
                <h3 style="margin-bottom: 15px;">Intern Feedback Form</h3>
                
                <label>Intern Name:</label>
                <input type="text" placeholder="Enter your name">
                
                <label>Company/Organization:</label>
                <input type="text" placeholder="Enter company name">
                
                <label>Internship Duration:</label>
                <input type="text" placeholder="e.g., 3 months">
                
                <label>Project Rating (1-5):</label>
                <input type="number" min="1" max="5" placeholder="Rate the project">
                
                <label>What did you learn from this internship?</label>
                <textarea rows="4" placeholder="Describe your learning experience"></textarea>
                
                <label>What challenges did you face and how did you overcome them?</label>
                <textarea rows="4" placeholder="Describe challenges and solutions"></textarea>
                
                <label>Suggestions for improvement:</label>
                <textarea rows="4" placeholder="Any suggestions for the project or internship program"></textarea>
                
                <label>Overall Experience:</label>
                <textarea rows="3" placeholder="Share your overall internship experience"></textarea>
            </div>
        </div>
        
        <!-- References -->
        <div class="section">
            <h2 class="section-title">VI. REFERENCES</h2>
            <ol class="references-list">
                <li>PHP Manual - Official PHP Documentation, php.net</li>
                <li>Oracle Database Documentation - Oracle Corporation</li>
                <li>OCI8 Extension for PHP - Oracle Corporation</li>
                <li>Modern Web Development with HTML5 and CSS3 - Various Online Resources</li>
                <li>Software Engineering: A Practitioner's Approach - Roger S. Pressman</li>
                <li>Database System Concepts - Abraham Silberschatz, Henry F. Korth, S. Sudarshan</li>
                <li>XAMPP Documentation - Apache Friends</li>
                <li>Web Application Security Best Practices - OWASP Foundation</li>
                <li>Agile Software Development Methodologies - Various Industry Standards</li>
                <li>Human-Computer Interaction Principles - ACM SIGCHI</li>
            </ol>
        </div>
    </div>
</body>
</html>
