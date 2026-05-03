<?php
session_start();
require_once 'config.php';

$missing_departments = [];
$all_emp_departments = [];
$all_db_departments = [];
$error = '';
$add_success = '';
$added_count = 0;

// Handle Add button - insert missing departments
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_departments'])) {
    // Find missing departments first
    $sql = "SELECT DISTINCT e.JOB_TEXT 
            FROM HMS2.EMP_HMS2_TABLE e 
            LEFT JOIN DEPARTMENT d ON UPPER(TRIM(e.JOB_TEXT)) = UPPER(TRIM(d.NAME_FORMAL)) 
            WHERE d.NAME_FORMAL IS NULL 
            AND e.JOB_TEXT IS NOT NULL";
    
    $stid = oci_parse($conn, $sql);
    
    if ($stid && oci_execute($stid)) {
        $to_add = [];
        while ($row = oci_fetch_assoc($stid)) {
            $to_add[] = $row['JOB_TEXT'];
        }
        oci_free_statement($stid);
        
        // Insert each missing department with unique NR
        foreach ($to_add as $dept_name) {
            // Get next NR number
            $nr_sql = "SELECT COALESCE(MAX(NR), 0) + 1 as NEXT_NR FROM DEPARTMENT";
            $nr_stid = oci_parse($conn, $nr_sql);
            $next_nr = 1;
            if ($nr_stid && oci_execute($nr_stid)) {
                $nr_row = oci_fetch_assoc($nr_stid);
                $next_nr = $nr_row['NEXT_NR'];
                oci_free_statement($nr_stid);
            }
            
            // Insert the department
            $insert_sql = "INSERT INTO DEPARTMENT (NR, NAME_FORMAL) VALUES (:nr, :name_formal)";
            $insert_stid = oci_parse($conn, $insert_sql);
            oci_bind_by_name($insert_stid, ':nr', $next_nr);
            oci_bind_by_name($insert_stid, ':name_formal', $dept_name);
            
            if (oci_execute($insert_stid, OCI_COMMIT_ON_SUCCESS)) {
                $added_count++;
            } else {
                $e = oci_error($insert_stid);
                $error .= "Error inserting '$dept_name': " . $e['message'] . "<br>";
            }
            oci_free_statement($insert_stid);
        }
        
        if ($added_count > 0) {
            $add_success = "Successfully added $added_count department(s) to the DEPARTMENT table.";
        } else if (count($to_add) == 0) {
            $add_success = "No missing departments to add.";
        }
    } else {
        $e = oci_error($conn);
        $error = "Error finding missing departments: " . $e['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_departments'])) {
    // Get all departments from HMS2.EMP_HMS2_TABLE
    $emp_sql = "SELECT DISTINCT JOB_TEXT FROM HMS2.EMP_HMS2_TABLE WHERE JOB_TEXT IS NOT NULL";
    $emp_stid = oci_parse($conn, $emp_sql);
    if ($emp_stid && oci_execute($emp_stid)) {
        while ($row = oci_fetch_assoc($emp_stid)) {
            $all_emp_departments[] = $row['JOB_TEXT'];
        }
        oci_free_statement($emp_stid);
    }
    
    // Get all departments from DEPARTMENT table
    $dept_sql = "SELECT DISTINCT NAME_FORMAL FROM DEPARTMENT WHERE NAME_FORMAL IS NOT NULL";
    $dept_stid = oci_parse($conn, $dept_sql);
    if ($dept_stid && oci_execute($dept_stid)) {
        while ($row = oci_fetch_assoc($dept_stid)) {
            $all_db_departments[] = $row['NAME_FORMAL'];
        }
        oci_free_statement($dept_stid);
    }
    // Query to find JOB_TEXT values from HMS2.EMP_HMS2_TABLE that don't exist in DEPARTMENT.NAME_FORMAL
    $sql = "SELECT DISTINCT e.JOB_TEXT 
            FROM HMS2.EMP_HMS2_TABLE e 
            LEFT JOIN DEPARTMENT d ON UPPER(TRIM(e.JOB_TEXT)) = UPPER(TRIM(d.NAME_FORMAL)) 
            WHERE d.NAME_FORMAL IS NULL 
            AND e.JOB_TEXT IS NOT NULL";
    
    $stid = oci_parse($conn, $sql);
    
    if (!$stid) {
        $e = oci_error($conn);
        $error = "Parse Error: " . $e['message'];
    } else {
        $r = oci_execute($stid);
        
        if (!$r) {
            $e = oci_error($stid);
            $error = "Execute Error: " . $e['message'];
        } else {
            while ($row = oci_fetch_assoc($stid)) {
                $missing_departments[] = $row['JOB_TEXT'];
            }
        }
        
        oci_free_statement($stid);
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Check - HMS</title>
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
        }
        
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
        }
        
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
                radial-gradient(circle at 70% 70%, rgba(139, 92, 246, 0.08) 0%, transparent 40%);
            animation: bgMove 30s ease-in-out infinite;
        }
        
        @keyframes bgMove {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(2%, 2%) rotate(1deg); }
            66% { transform: translate(-1%, 1%) rotate(-1deg); }
        }
        
        .app-container {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
        }
        
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
            text-decoration: none;
            color: var(--text-primary);
        }
        
        .brand-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent-blue), #8b5cf6);
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
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .btn-back {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
            color: var(--text-primary);
        }
        
        .btn-back svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        
        .header-logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 10px;
            font-size: 0.85rem;
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
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }
        
        .page-subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        
        .panel {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .action-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.25s ease;
            border: none;
            color: var(--text-primary);
            font-family: inherit;
            width: 100%;
            text-align: left;
        }
        
        .action-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateX(4px);
        }
        
        .action-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .action-icon.blue { background: rgba(99, 102, 241, 0.15); }
        .action-icon.green { background: rgba(16, 185, 129, 0.15); }
        
        .action-icon svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        
        .action-content {
            flex: 1;
        }
        
        .action-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .action-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #34d399;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        .alert-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        .result-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
        
        .result-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--text-primary);
        }
        
        .department-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .department-item {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-left: 3px solid #ef4444;
            color: #f87171;
            padding: 14px 16px;
            border-radius: 10px;
            font-weight: 500;
        }
        
        .success-box {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #34d399;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            font-weight: 500;
        }
        
        @media (max-width: 640px) {
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    
    <div class="app-container">
        <header class="header">
            <a href="dashboard.php" class="brand">
                <div class="brand-logo">🏢</div>
                <div class="brand-text">
                    <h1>Department Check</h1>
                    <span>HMS Enterprise</span>
                </div>
            </a>
            
            <div class="header-actions">
                <a href="dashboard.php" class="btn-back">
                    <svg viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Back to Dashboard
                </a>
                <a href="logout.php" class="header-logout-btn">
                    <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Sign Out
                </a>
            </div>
        </header>
        
        <h1 class="page-title">Department Validation</h1>
        <p class="page-subtitle">Check and add missing departments to the master table.</p>
        
        <div class="panel">
            <form method="POST" action="">
                <div class="action-grid">
                    <button type="submit" name="check_departments" class="action-card">
                        <div class="action-icon blue">
                            <svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l8-4 8 4v14M8 21v-9a2 2 0 012-2h4a2 2 0 012 2v9"/></svg>
                        </div>
                        <div class="action-content">
                            <div class="action-title">Check Departments</div>
                            <div class="action-desc">Find missing departments</div>
                        </div>
                    </button>
                    
                    <button type="submit" name="add_departments" class="action-card">
                        <div class="action-icon green">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                        </div>
                        <div class="action-content">
                            <div class="action-title">Add Missing</div>
                            <div class="action-desc">Auto-add all missing departments</div>
                        </div>
                    </button>
                </div>
            </form>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($add_success): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <?php echo htmlspecialchars($add_success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['check_departments']) || isset($_POST['add_departments'])) && empty($error)): ?>
                <div class="result-section">
                    <?php if (count($missing_departments) > 0): ?>
                        <div class="result-title">Missing Departments (<?php echo count($missing_departments); ?>)</div>
                        <div class="department-list">
                            <?php foreach ($missing_departments as $dept): ?>
                                <div class="department-item"><?php echo htmlspecialchars($dept); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="success-box">
                            All department values in HMS2.EMP_HMS2_TABLE exist in the DEPARTMENT table!
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
