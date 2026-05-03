<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$last_sync = date('Y-m-d H:i:s');

// Fetch actual counts from database
$emp_count = 0;
$dept_count = 0;
$desig_count = 0;
$grade_count = 0;

if ($conn) {
    // Count employees
    $emp_stmt = oci_parse($conn, "SELECT COUNT(*) as cnt FROM HMS2.EMP_HMS2_TABLE");
    if ($emp_stmt && oci_execute($emp_stmt)) {
        $row = oci_fetch_assoc($emp_stmt);
        $emp_count = $row['CNT'];
        oci_free_statement($emp_stmt);
    }
    
    // Count departments
    $dept_stmt = oci_parse($conn, "SELECT COUNT(*) as cnt FROM DEPARTMENT");
    if ($dept_stmt && oci_execute($dept_stmt)) {
        $row = oci_fetch_assoc($dept_stmt);
        $dept_count = $row['CNT'];
        oci_free_statement($dept_stmt);
    }
    
    // Count designations
    $desig_stmt = oci_parse($conn, "SELECT COUNT(*) as cnt FROM DESIGNATION");
    if ($desig_stmt && oci_execute($desig_stmt)) {
        $row = oci_fetch_assoc($desig_stmt);
        $desig_count = $row['CNT'];
        oci_free_statement($desig_stmt);
    }
    
    // Count grades
    $grade_stmt = oci_parse($conn, "SELECT COUNT(*) as cnt FROM GRADE");
    if ($grade_stmt && oci_execute($grade_stmt)) {
        $row = oci_fetch_assoc($grade_stmt);
        $grade_count = $row['CNT'];
        oci_free_statement($grade_stmt);
    }
}

if (isset($conn) && $conn) {
    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HMS Enterprise</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-primary: #0a0a1a;
            --bg-secondary: #121228;
            --bg-card: #1a1a35;
            --border: rgba(255, 255, 255, 0.08);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.6);
            --text-muted: rgba(255, 255, 255, 0.4);
            --accent-blue: #6366f1;
            --accent-green: #10b981;
            --accent-amber: #f59e0b;
            --accent-rose: #f43f5e;
            --accent-purple: #8b5cf6;
            --shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
        }
        
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        /* Animated Background */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        
        .bg-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 30% 30%, rgba(99, 102, 241, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 70% 70%, rgba(139, 92, 246, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
            animation: bgMove 30s ease-in-out infinite;
        }
        
        @keyframes bgMove {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(2%, 2%) rotate(1deg); }
            66% { transform: translate(-1%, 1%) rotate(-1deg); }
        }
        
        /* Layout */
        .app-container {
            position: relative;
            z-index: 1;
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-bottom: 24px;
            backdrop-filter: blur(20px);
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .brand-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        
        .brand-text h1 {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        .brand-text span {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .header-logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 20px;
            font-size: 0.8rem;
            color: #f87171;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        
        .header-logout-btn:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .header-logout-btn svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 6px 6px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 24px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-info .name {
            font-size: 0.85rem;
            font-weight: 600;
            display: block;
        }
        
        .user-info .role {
            font-size: 0.7rem;
            color: var(--text-muted);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        
        /* Stats Grid - Bento Style */
        .stats-section {
            margin-bottom: 32px;
        }
        
        .section-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--text-muted);
            margin-bottom: 16px;
            font-weight: 600;
        }
        
        .stats-bento {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        
        .stat-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            opacity: 0.8;
        }
        
        .stat-box:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: var(--shadow);
        }
        
        .stat-box.employees::before { background: linear-gradient(90deg, var(--accent-blue), var(--accent-purple)); }
        .stat-box.departments::before { background: linear-gradient(90deg, var(--accent-green), #34d399); }
        .stat-box.designations::before { background: linear-gradient(90deg, var(--accent-amber), #fbbf24); }
        .stat-box.grades::before { background: linear-gradient(90deg, var(--accent-rose), #fb7185); }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .stat-icon-bg {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        
        .employees .stat-icon-bg { background: rgba(99, 102, 241, 0.15); }
        .departments .stat-icon-bg { background: rgba(16, 185, 129, 0.15); }
        .designations .stat-icon-bg { background: rgba(245, 158, 11, 0.15); }
        .grades .stat-icon-bg { background: rgba(244, 63, 94, 0.15); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        
        .stat-label-text {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        .panel {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
        }
        
        .panel-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .panel-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.1));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        
        .panel-title {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .panel-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        /* Main Panel - Single Panel Layout */
        .main-panel {
            max-width: 100%;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        
        @media (max-width: 1024px) {
            .action-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 640px) {
            .action-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Action Cards */
        .action-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .action-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.25s ease;
            cursor: pointer;
        }
        
        .action-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateX(4px);
        }
        
        .action-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .action-icon svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        
        .action-icon.blue { background: rgba(99, 102, 241, 0.15); color: #818cf8; }
        .action-icon.green { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .action-icon.amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .action-icon.rose { background: rgba(244, 63, 94, 0.15); color: #fb7185; }
        .action-icon.cyan { background: rgba(6, 182, 212, 0.15); color: #67e8f9; }
        .action-icon.purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
        
        .action-content {
            flex: 1;
        }
        
        .action-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .action-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
            line-height: 1.4;
        }
        
        .action-arrow {
            width: 24px;
            height: 24px;
            opacity: 0;
            transform: translateX(-8px);
            transition: all 0.25s ease;
        }
        
        .action-card:hover .action-arrow {
            opacity: 1;
            transform: translateX(0);
        }
        
        .action-arrow svg {
            width: 100%;
            height: 100%;
            stroke: var(--text-secondary);
            fill: none;
            stroke-width: 2;
        }
        
        /* Logout Section */
        .logout-section {
            margin-top: 24px;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px;
            background: transparent;
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            color: #f87171;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
            font-family: inherit;
            cursor: pointer;
        }
        
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.5);
        }
        
        .logout-btn svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        
        /* Button Guide Section */
        .guide-section {
            margin-top: 32px;
        }
        
        .guide-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        
        .guide-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
            transition: all 0.3s ease;
        }
        
        .guide-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }
        
        .guide-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .guide-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        
        .guide-icon.blue { background: rgba(99, 102, 241, 0.15); }
        .guide-icon.green { background: rgba(16, 185, 129, 0.15); }
        .guide-icon.amber { background: rgba(245, 158, 11, 0.15); }
        .guide-icon.cyan { background: rgba(6, 182, 212, 0.15); }
        .guide-icon.purple { background: rgba(168, 85, 247, 0.15); }
        .guide-icon.rose { background: rgba(244, 63, 94, 0.15); }
        .guide-icon.red { background: rgba(239, 68, 68, 0.15); }
        
        .guide-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .guide-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .guide-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 1024px) {
            .stats-bento {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 640px) {
            .header {
                flex-direction: column;
                gap: 16px;
            }
            
            .stats-bento {
                grid-template-columns: 1fr;
            }
            
            .guide-grid {
                grid-template-columns: 1fr;
            }
            
            .app-container {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div class="brand">
                <div class="brand-logo">🏢</div>
                <div class="brand-text">
                    <h1>PROMO TRACKER</h1>
                    <span>Employee Promotion Management System</span>
                </div>
            </div>
            
            <div class="user-menu">
                <a href="logout.php" class="header-logout-btn">
                    <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Sign Out
                </a>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                        <span class="role">Administrator</span>
                    </div>
                    <div class="user-avatar">👤</div>
                </div>
            </div>
        </header>
        
        <!-- Stats Section -->
        <section class="stats-section">
            <div class="section-label">System Overview</div>
            <div class="stats-bento">
                <div class="stat-box employees">
                    <div class="stat-header">
                        <div class="stat-icon-bg">👥</div>
                    </div>
                    <div class="stat-value"><?php echo $emp_count; ?></div>
                    <div class="stat-label-text">Total Employees</div>
                </div>
                
                <div class="stat-box departments">
                    <div class="stat-header">
                        <div class="stat-icon-bg">🏢</div>
                    </div>
                    <div class="stat-value"><?php echo $dept_count; ?></div>
                    <div class="stat-label-text">Departments</div>
                </div>
                
                <div class="stat-box designations">
                    <div class="stat-header">
                        <div class="stat-icon-bg">💼</div>
                    </div>
                    <div class="stat-value"><?php echo $desig_count; ?></div>
                    <div class="stat-label-text">Designations</div>
                </div>
                
                <div class="stat-box grades">
                    <div class="stat-header">
                        <div class="stat-icon-bg">🏆</div>
                    </div>
                    <div class="stat-value"><?php echo $grade_count; ?></div>
                    <div class="stat-label-text">Grades</div>
                </div>
            </div>
        </section>
        
        <!-- Main Operations Panel -->
        <div class="section-label">Management Tools</div>
        <div class="panel main-panel">
            <div class="action-grid">
                <a href="department_check.php" class="action-card">
                    <div class="action-icon blue">
                        <svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l8-4 8 4v14M8 21v-9a2 2 0 012-2h4a2 2 0 012 2v9"/></svg>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Department Check</div>
                        <div class="action-desc">Validate and add missing departments</div>
                    </div>
                    <div class="action-arrow">
                        <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </div>
                </a>
                
                <a href="designation_check.php" class="action-card">
                    <div class="action-icon green">
                        <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Designation Check</div>
                        <div class="action-desc">Validate and add missing designations</div>
                    </div>
                    <div class="action-arrow">
                        <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </div>
                </a>
                
                <a href="grade_check.php" class="action-card">
                    <div class="action-icon amber">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/></svg>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Grade Check</div>
                        <div class="action-desc">Validate and add missing grades</div>
                    </div>
                    <div class="action-arrow">
                        <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </div>
                </a>
                
                <a href="add_data.php" class="action-card">
                    <div class="action-icon cyan">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Add Employee</div>
                        <div class="action-desc">Insert new record to EMP_HMS2_TABLE</div>
                    </div>
                    <div class="action-arrow">
                        <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </div>
                </a>
                
                <a href="master_table_add.php" class="action-card">
                    <div class="action-icon purple">
                        <svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0021 19V5"/><path d="M3 12A9 3 0 0021 12"/></svg>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Sync to PERSON_TABLE</div>
                        <div class="action-desc">Migrate records to master table</div>
                    </div>
                    <div class="action-arrow">
                        <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </div>
                </a>
                
                <a href="compare_tables.php" class="action-card">
                    <div class="action-icon rose">
                        <svg viewBox="0 0 24 24"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/></svg>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Compare Tables</div>
                        <div class="action-desc">Check data consistency between tables</div>
                    </div>
                    <div class="action-arrow">
                        <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </div>
                </a>
                
                <a href="compare_person_table.php" class="action-card">
                    <div class="action-icon blue">
                        <svg viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14h6M9 10h6M9 18h6"/></svg>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Deduplicate Records</div>
                        <div class="action-desc">Fix duplicates in PERSON_TABLE</div>
                    </div>
                    <div class="action-arrow">
                        <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Button Guide Panel -->
        <section class="guide-section">
            <div class="section-label">Button Guide</div>
            <div class="guide-grid">
                <div class="guide-card">
                    <div class="guide-header">
                        <div class="guide-icon blue">🏢</div>
                        <span class="guide-name">Department Check</span>
                    </div>
                    <p class="guide-desc">Scans employee records for department names that don't exist in the DEPARTMENT master table. Allows you to add missing departments with unique NR codes.</p>
                </div>
                
                <div class="guide-card">
                    <div class="guide-header">
                        <div class="guide-icon green">💼</div>
                        <span class="guide-name">Designation Check</span>
                    </div>
                    <p class="guide-desc">Identifies missing job designations from employee data. Adds new designations to the DESIGNATION lookup table for proper categorization.</p>
                </div>
                
                <div class="guide-card">
                    <div class="guide-header">
                        <div class="guide-icon amber">🏆</div>
                        <span class="guide-name">Grade Check</span>
                    </div>
                    <p class="guide-desc">Finds employee grades (ESG values) not present in the GRADE master table. Adds missing grades to ensure proper employee classification.</p>
                </div>
                
                <div class="guide-card">
                    <div class="guide-header">
                        <div class="guide-icon cyan">➕</div>
                        <span class="guide-name">Add Employee</span>
                    </div>
                    <p class="guide-desc">Form interface to manually insert new employee records into HMS2.EMP_HMS2_TABLE with all required fields including personal and employment details.</p>
                </div>
                
                <div class="guide-card">
                    <div class="guide-header">
                        <div class="guide-icon purple">🔄</div>
                        <span class="guide-name">Sync to PERSON_TABLE</span>
                    </div>
                    <p class="guide-desc">Migrates records from HMS2.EMP_HMS2_TABLE to HMS.PERSON_TABLE. Converts text values (JOB_TEXT, POS_TEXT, ESG) to numeric IDs using lookup tables.</p>
                </div>
                
                <div class="guide-card">
                    <div class="guide-header">
                        <div class="guide-icon rose">⚖️</div>
                        <span class="guide-name">Compare Tables</span>
                    </div>
                    <p class="guide-desc">Compares data between EMP_HMS2_TABLE and PERSON_TABLE. Shows mismatches in department, designation, and grade assignments for reconciliation.</p>
                </div>
                
                <div class="guide-card">
                    <div class="guide-header">
                        <div class="guide-icon blue">🔍</div>
                        <span class="guide-name">Deduplicate Records</span>
                    </div>
                    <p class="guide-desc">Analyzes PERSON_TABLE for duplicate entries based on ID_NO_NAME. Synchronizes duplicate records using data from EMP_HMS2_TABLE as the source.</p>
                </div>
                
                <div class="guide-card">
                    <div class="guide-header">
                        <div class="guide-icon red">🚪</div>
                        <span class="guide-name">Sign Out</span>
                    </div>
                    <p class="guide-desc">Logs out the current user session and returns to the login page. Clears session data for security.</p>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
