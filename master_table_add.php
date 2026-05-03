<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';
$inserted_count = 0;
$skipped_count = 0;
$failed_records = [];
$total_source_records = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['check_data'])) {
        // Check mode - show records in EMP_HMS2_TABLE but NOT in PERSON_TABLE
        $emp_sql = "SELECT EMP_NAME, JOB_TEXT, POS_TEXT, ESG, CPF_NO, DOB, TA_CPF_NO, GENDER, CELL FROM HMS2.EMP_HMS2_TABLE";
        $emp_stid = oci_parse($conn, $emp_sql);
        if ($emp_stid && oci_execute($emp_stid)) {
            $missing_records = [];
            while ($row = oci_fetch_assoc($emp_stid)) {
                $cpf_no = $row['CPF_NO'];
                // Check if exists in PERSON_TABLE
                $check_sql = "SELECT COUNT(*) as CNT FROM PERSON_TABLE WHERE SAP_ID = :sap_id";
                $check_stid = oci_parse($conn, $check_sql);
                oci_bind_by_name($check_stid, ':sap_id', $cpf_no);
                if ($check_stid && oci_execute($check_stid)) {
                    $check_row = oci_fetch_assoc($check_stid);
                    if ($check_row && $check_row['CNT'] == 0) {
                        // Record doesn't exist in PERSON_TABLE - add to missing list
                        $missing_records[] = $row;
                    }
                    oci_free_statement($check_stid);
                }
            }
            $total_missing = count($missing_records);
            if ($total_missing > 0) {
                $success = "Check Mode: Found $total_missing records in EMP_HMS2_TABLE that are NOT in PERSON_TABLE (ready for sync).";
                $_SESSION['missing_records'] = $missing_records;
            } else {
                $success = "Check Mode: All records from EMP_HMS2_TABLE are already in PERSON_TABLE. No sync needed.";
                $_SESSION['missing_records'] = [];
            }
            oci_free_statement($emp_stid);
        } else {
            $e = oci_error($emp_stid);
            $error = "Error checking data: " . $e['message'];
        }
    } elseif (isset($_POST['add_to_master'])) {
    // Fetch all records from HMS2.EMP_HMS2_TABLE
    $emp_sql = "SELECT EMP_NAME, JOB_TEXT, POS_TEXT, ESG, CPF_NO, DOB, TA_CPF_NO, GENDER,CELL FROM HMS2.EMP_HMS2_TABLE";
    $emp_stid = oci_parse($conn, $emp_sql);
    
    if (!$emp_stid) {
        $e = oci_error($conn);
        $error = "Parse Error: " . $e['message'];
    } else {
        $r = oci_execute($emp_stid);
        
        if (!$r) {
            $e = oci_error($emp_stid);
            $error = "Execute Error: " . $e['message'];
        } else {
            // Check PERSON_TABLE count before processing
            $count_sql = "SELECT COUNT(*) as CNT FROM PERSON_TABLE";
            $count_stid = oci_parse($conn, $count_sql);
            oci_execute($count_stid);
            $count_row = oci_fetch_assoc($count_stid);
            oci_free_statement($count_stid);
            
            // Fetch all records into array first to count total
            $rows = [];
            while ($row = oci_fetch_assoc($emp_stid)) {
                $rows[] = $row;
            }
            $total_source_records = count($rows);
            
            // Check if source table has records
            if ($total_source_records == 0) {
                $error = "No records found in HMS2.EMP_HMS2_TABLE. Cannot migrate empty data.";
            } else {
                // Process each record
                foreach ($rows as $row) {
                    $emp_name = $row['EMP_NAME'];
                    $job_text = $row['JOB_TEXT'];
                    $pos_text = $row['POS_TEXT'];
                    $esg = $row['ESG'];
                    $cpf_no = $row['CPF_NO'];
                    $dob = $row['DOB'];
                    $ta_cpf_no = $row['TA_CPF_NO'];
                    $gender = $row['GENDER'];
                    $cell = $row['CELL'];
                    
                    // Convert gender
                    $sex = '';
                    if (strcasecmp($gender, 'Male') == 0) {
                        $sex = 'M';
                    } elseif (strcasecmp($gender, 'Female') == 0) {
                        $sex = 'F';
                    } else {
                        $sex = substr($gender, 0, 1); // Fallback: take first character
                    }
                    
                    // Lookup DEPARTMENT.NR
                    $dept_nr = null;
                    $dept_sql = "SELECT NR FROM DEPARTMENT WHERE UPPER(TRIM(NAME_FORMAL)) = UPPER(TRIM(:job_text))";
                    $dept_stid = oci_parse($conn, $dept_sql);
                    oci_bind_by_name($dept_stid, ':job_text', $job_text);
                    if ($dept_stid && oci_execute($dept_stid)) {
                        $dept_row = oci_fetch_assoc($dept_stid);
                        if ($dept_row) {
                            $dept_nr = $dept_row['NR'];
                        }
                        oci_free_statement($dept_stid);
                    }
                    
                    // Lookup DESIGNATION.NR
                    $desig_nr = null;
                    $desig_sql = "SELECT NR FROM DESIGNATION WHERE UPPER(TRIM(DESIGNATION)) = UPPER(TRIM(:pos_text))";
                    $desig_stid = oci_parse($conn, $desig_sql);
                    oci_bind_by_name($desig_stid, ':pos_text', $pos_text);
                    if ($desig_stid && oci_execute($desig_stid)) {
                        $desig_row = oci_fetch_assoc($desig_stid);
                        if ($desig_row) {
                            $desig_nr = $desig_row['NR'];
                        }
                        oci_free_statement($desig_stid);
                    }
                    
                    // Lookup GRADE.NR
                    $grade_nr = null;
                    $grade_sql = "SELECT NR FROM GRADE WHERE UPPER(TRIM(GRADE_NAME)) = UPPER(TRIM(:esg))";
                    $grade_stid = oci_parse($conn, $grade_sql);
                    oci_bind_by_name($grade_stid, ':esg', $esg);
                    if ($grade_stid && oci_execute($grade_stid)) {
                        $grade_row = oci_fetch_assoc($grade_stid);
                        if ($grade_row) {
                            $grade_nr = $grade_row['NR'];
                        }
                        oci_free_statement($grade_stid);
                    }
                    
                    // Check if record already exists in PERSON_TABLE (based on SAP_ID)
                    $exists = false;
                    $check_sql = "SELECT COUNT(*) as CNT FROM PERSON_TABLE WHERE SAP_ID = :sap_id";
                    $check_stid = oci_parse($conn, $check_sql);
                    oci_bind_by_name($check_stid, ':sap_id', $cpf_no);
                    if ($check_stid && oci_execute($check_stid)) {
                        $check_row = oci_fetch_assoc($check_stid);
                        if ($check_row && $check_row['CNT'] > 0) {
                            $exists = true;
                        }
                        oci_free_statement($check_stid);
                    }
                    
                    // Skip if already exists
                    if ($exists) {
                        $skipped_count++;
                        continue;
                    }
                    
                    // Insert into PERSON_TABLE
                    $insert_sql = "INSERT INTO PERSON_TABLE 
                        (NAME_FIRST, SEL_DEPARTMENT, DESIGNATION, EMP_GRADE, SAP_ID, DATE_BIRTH, ID_NO_NAME, SEX, PHONE_1_NR) 
                        VALUES 
                        (:name_first, :sel_department, :designation, :emp_grade, :sap_id, :date_birth, :id_no_name, :sex, :phone_1_nr)";
                    
                    $insert_stid = oci_parse($conn, $insert_sql);
                    oci_bind_by_name($insert_stid, ':name_first', $emp_name);
                    oci_bind_by_name($insert_stid, ':sel_department', $dept_nr);
                    oci_bind_by_name($insert_stid, ':designation', $desig_nr);
                    oci_bind_by_name($insert_stid, ':emp_grade', $grade_nr);
                    oci_bind_by_name($insert_stid, ':sap_id', $cpf_no);
                    oci_bind_by_name($insert_stid, ':date_birth', $dob);
                    oci_bind_by_name($insert_stid, ':id_no_name', $ta_cpf_no);
                    oci_bind_by_name($insert_stid, ':sex', $sex);
                    oci_bind_by_name($insert_stid, ':phone_1_nr', $cell);
                    
                    if (oci_execute($insert_stid, OCI_COMMIT_ON_SUCCESS)) {
                        $inserted_count++;
                    } else {
                        $e = oci_error($insert_stid);
                        $failed_records[] = [
                            'name' => $emp_name,
                            'error' => $e['message'],
                            'dept' => $dept_nr ?? 'NULL',
                            'desig' => $desig_nr ?? 'NULL',
                            'grade' => $grade_nr ?? 'NULL'
                        ];
                    }
                    oci_free_statement($insert_stid);
                }
                
                if ($total_source_records > 0) {
                    if ($inserted_count > 0 || $skipped_count > 0) {
                        $success = "Source records: $total_source_records | Inserted: $inserted_count | Skipped (already exists): $skipped_count";
                    }
                    if (count($failed_records) > 0) {
                        $error = "Failed to insert " . count($failed_records) . " record(s).<br>Common cause: Missing DEPARTMENT/DESIGNATION/GRADE lookup values.";
                    }
                }
            }
        }
        
        oci_free_statement($emp_stid);
    }
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync to Master - HMS</title>
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
            --accent-purple: #8b5cf6;
            --accent-amber: #f59e0b;
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
            background: linear-gradient(135deg, var(--accent-purple), #a78bfa);
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
        
        .sync-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .sync-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(139, 92, 246, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sync-icon svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        
        .sync-content {
            flex: 1;
        }
        
        .sync-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .sync-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .btn-sync {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent-purple), #a78bfa);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
        }
        
        .btn-sync:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.3);
        }
        
        .btn-sync svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
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
        
        .failed-list {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .failed-item {
            padding: 8px 0;
            color: #fbbf24;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(245, 158, 11, 0.2);
        }
        
        .failed-item:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 640px) {
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
                <div class="brand-logo">🔄</div>
                <div class="brand-text">
                    <h1>Sync to Master</h1>
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
        
        <h1 class="page-title">Sync to PERSON_TABLE</h1>
        <p class="page-subtitle">Migrate records from EMP_HMS2_TABLE to the master PERSON_TABLE.</p>
        
        <div class="panel">
            <div class="sync-card">
                <div class="sync-icon">
                    <svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0021 19V5"/><path d="M3 12A9 3 0 0021 12"/></svg>
                </div>
                <div class="sync-content">
                    <div class="sync-title">Data Migration</div>
                    <div class="sync-desc">Converts JOB_TEXT, POS_TEXT, and ESG to numeric IDs using lookup tables</div>
                </div>
            </div>
            
            <form method="POST" action="">
                <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                    <button type="submit" name="check_data" class="btn-sync" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                        <svg viewBox="0 0 24 24"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                        Check Data
                    </button>
                    <button type="submit" name="add_to_master" class="btn-sync">
                        <svg viewBox="0 0 24 24"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                        Start Sync to Master Table
                    </button>
                </div>
            </form>
            
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-top: 20px;">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['missing_records']) && count($_SESSION['missing_records']) > 0): ?>
                <div class="failed-list" style="margin-top: 20px; background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.2);">
                    <h4 style="color: #60a5fa; margin-bottom: 10px; font-size: 0.95rem;">Missing Records (<?php echo count($_SESSION['missing_records']); ?>):</h4>
                    <?php foreach ($_SESSION['missing_records'] as $record): ?>
                        <div class="failed-item" style="color: #93c5fd;">
                            <strong><?php echo htmlspecialchars($record['EMP_NAME']); ?></strong> (CPF: <?php echo htmlspecialchars($record['CPF_NO']); ?>)
                            <br><small>Dept: <?php echo htmlspecialchars($record['JOB_TEXT']); ?> | Desig: <?php echo htmlspecialchars($record['POS_TEXT']); ?> | Grade: <?php echo htmlspecialchars($record['ESG']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php unset($_SESSION['missing_records']); ?>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-top: 20px;">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php echo $error; ?>
                    <?php if (count($failed_records) > 0): ?>
                        <div class="failed-list">
                            <?php foreach ($failed_records as $failed): ?>
                                <div class="failed-item">
                                    <strong><?php echo htmlspecialchars($failed['name']); ?></strong>: <?php echo htmlspecialchars($failed['error']); ?>
                                    <br><small>Dept: <?php echo $failed['dept']; ?> | Desig: <?php echo $failed['desig']; ?> | Grade: <?php echo $failed['grade']; ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
