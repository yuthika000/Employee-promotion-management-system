<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$mismatches = [];
$error_message = '';
$success_message = '';
$range_from = '';
$range_to = '';

// Handle Check Range button click
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_range'])) {
    $range_from = trim($_POST['range_from'] ?? '');
    $range_to = trim($_POST['range_to'] ?? '');
}

// Handle Clear Range button click
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_range'])) {
    $range_from = '';
    $range_to = '';
}

// Handle Update PERSON_TABLE button click
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_person'])) {
    if (!$conn) {
        $error_message = "Database connection failed.";
    } else {
        $updated_count = 0;

        // Fetch all PERSON_TABLE records
        $person_sql = "SELECT NAME_FIRST, EMP_GRADE, DESIGNATION, SEL_DEPARTMENT, ID_NO_NAME, SAP_ID FROM PERSON_TABLE";
        if ($range_from && $range_to) {
            $person_sql .= " WHERE ID_NO_NAME BETWEEN :id_from AND :id_to";
        }
        $person_stmt = oci_parse($conn, $person_sql);
        $person_records = [];
        if ($range_from && $range_to) {
            oci_bind_by_name($person_stmt, ':id_from', $range_from);
            oci_bind_by_name($person_stmt, ':id_to', $range_to);
        }
        if ($person_stmt && oci_execute($person_stmt)) {
            while ($row = oci_fetch_assoc($person_stmt)) {
                $person_records[] = $row;
            }
            oci_free_statement($person_stmt);
        }

        // Fetch HMS2.EMP_HMS2_TABLE to get CPF_NO list
        $emp_sql = "SELECT CPF_NO FROM HMS2.EMP_HMS2_TABLE";
        $emp_stmt = oci_parse($conn, $emp_sql);
        $emp_cpf_list = [];
        if ($emp_stmt && oci_execute($emp_stmt)) {
            while ($row = oci_fetch_assoc($emp_stmt)) {
                $emp_cpf_list[] = trim((string)$row['CPF_NO']);
            }
            oci_free_statement($emp_stmt);
        }

        // Group records by ID_NO_NAME
        $grouped_by_id = [];
        foreach ($person_records as $record) {
            $id_no_name = trim((string)$record['ID_NO_NAME']);
            if (!isset($grouped_by_id[$id_no_name])) {
                $grouped_by_id[$id_no_name] = [];
            }
            $grouped_by_id[$id_no_name][] = $record;
        }

        // For each ID_NO_NAME group, find the record that exists in HMS2.EMP_HMS2_TABLE and use it to update others
        foreach ($grouped_by_id as $id_no_name => $records) {
            if (count($records) > 1) {
                // Find the record that also exists in HMS2.EMP_HMS2_TABLE (SAP_ID matches CPF_NO)
                $source_record = null;
                foreach ($records as $record) {
                    if (in_array(trim((string)$record['SAP_ID']), $emp_cpf_list)) {
                        $source_record = $record;
                        break;
                    }
                }

                if ($source_record) {
                    $target_grade = $source_record['EMP_GRADE'];
                    $target_desig = $source_record['DESIGNATION'];
                    $target_dept = $source_record['SEL_DEPARTMENT'];

                    // Update all records in this group to match the source record
                    foreach ($records as $record) {
                        $sap_id = $record['SAP_ID'];
                        $update_sql = "UPDATE PERSON_TABLE SET EMP_GRADE = :grade, DESIGNATION = :desig, SEL_DEPARTMENT = :dept WHERE SAP_ID = :sap";
                        $update_stmt = oci_parse($conn, $update_sql);
                        oci_bind_by_name($update_stmt, ':grade', $target_grade);
                        oci_bind_by_name($update_stmt, ':desig', $target_desig);
                        oci_bind_by_name($update_stmt, ':dept', $target_dept);
                        oci_bind_by_name($update_stmt, ':sap', $sap_id);
                        if (oci_execute($update_stmt)) {
                            $updated_count++;
                        }
                        oci_free_statement($update_stmt);
                    }
                }
            }
        }

        $success_message = "Updated $updated_count record(s) in PERSON_TABLE using HMS2.EMP_HMS2_TABLE as source.";
    }
}

if (!$conn) {
    $error_message = "Database connection failed.";
} else {
    // Fetch HMS2.EMP_HMS2_TABLE to get CPF_NO list
    $emp_sql = "SELECT CPF_NO FROM HMS2.EMP_HMS2_TABLE";
    $emp_stmt = oci_parse($conn, $emp_sql);
    $emp_cpf_list = [];
    if ($emp_stmt && oci_execute($emp_stmt)) {
        while ($row = oci_fetch_assoc($emp_stmt)) {
            $emp_cpf_list[] = trim((string)$row['CPF_NO']);
        }
        oci_free_statement($emp_stmt);
    }

    // Fetch PERSON_TABLE with optional range filter
    $person_sql = "SELECT NAME_FIRST, EMP_GRADE, DESIGNATION, SEL_DEPARTMENT, ID_NO_NAME, SAP_ID FROM PERSON_TABLE";
    if ($range_from && $range_to) {
        $person_sql .= " WHERE ID_NO_NAME BETWEEN :id_from AND :id_to";
    }
    $person_stmt = oci_parse($conn, $person_sql);
    $person_records = [];
    if ($range_from && $range_to) {
        oci_bind_by_name($person_stmt, ':id_from', $range_from);
        oci_bind_by_name($person_stmt, ':id_to', $range_to);
    }
    if ($person_stmt && oci_execute($person_stmt)) {
        while ($row = oci_fetch_assoc($person_stmt)) {
            $person_records[] = $row;
        }
        oci_free_statement($person_stmt);
    }

    // Group records by ID_NO_NAME
    $grouped_by_id = [];
    foreach ($person_records as $record) {
        $id_no_name = trim((string)$record['ID_NO_NAME']);
        if (!isset($grouped_by_id[$id_no_name])) {
            $grouped_by_id[$id_no_name] = [];
        }
        $grouped_by_id[$id_no_name][] = $record;
    }

// Find mismatches within each ID_NO_NAME group
    $added_sap_ids = []; // Track which SAP_IDs have been added to avoid duplicates
    foreach ($grouped_by_id as $id_no_name => $records) {
        if (count($records) > 1) {
            // Compare all records in this group
            $first_record = $records[0];
            for ($i = 1; $i < count($records); $i++) {
                $current_record = $records[$i];

                // Check for mismatches
                $grade_mismatch = $first_record['EMP_GRADE'] != $current_record['EMP_GRADE'];
                $desig_mismatch = $first_record['DESIGNATION'] != $current_record['DESIGNATION'];
                $dept_mismatch = $first_record['SEL_DEPARTMENT'] != $current_record['SEL_DEPARTMENT'];

                if ($grade_mismatch || $desig_mismatch || $dept_mismatch) {
                    // Add first_record only if not already added
                    if (!isset($added_sap_ids[$first_record['SAP_ID']])) {
                        $is_from_emp = in_array(trim((string)$first_record['SAP_ID']), $emp_cpf_list);
                        $mismatches[] = array_merge($first_record, [
                            'MISMATCH_TYPE' => 'GRADE',
                            'GRADE_MISMATCH' => $grade_mismatch ? 1 : 0,
                            'DESIG_MISMATCH' => $desig_mismatch ? 1 : 0,
                            'DEPT_MISMATCH' => $dept_mismatch ? 1 : 0,
                            'FROM_HMS2.EMP_HMS2_TABLE' => $is_from_emp ? 1 : 0
                        ]);
                        $added_sap_ids[$first_record['SAP_ID']] = true;
                    }
                    // Add current_record only if not already added
                    if (!isset($added_sap_ids[$current_record['SAP_ID']])) {
                        $is_from_emp = in_array(trim((string)$current_record['SAP_ID']), $emp_cpf_list);
                        $mismatches[] = array_merge($current_record, [
                            'MISMATCH_TYPE' => 'GRADE',
                            'GRADE_MISMATCH' => $grade_mismatch ? 1 : 0,
                            'DESIG_MISMATCH' => $desig_mismatch ? 1 : 0,
                            'DEPT_MISMATCH' => $dept_mismatch ? 1 : 0,
                            'FROM_HMS2.EMP_HMS2_TABLE' => $is_from_emp ? 1 : 0
                        ]);
                        $added_sap_ids[$current_record['SAP_ID']] = true;
                    }
                }
            }
        }
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
    <title>Deduplicate Records - HMS</title>
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
            --accent-rose: #f43f5e;
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
            max-width: 1200px;
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
        
        .range-form {
            margin-bottom: 24px;
            padding: 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
        }
        
        .range-inputs {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .input-group {
            flex: 1;
            min-width: 200px;
        }
        
        .input-group label {
            display: block;
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.4);
        }
        
        .input-group input::placeholder {
            color: var(--text-muted);
        }
        
        .btn-check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 10px;
            color: #818cf8;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
        }
        
        .btn-check:hover {
            background: rgba(99, 102, 241, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-check svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        
        .btn-clear {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(244, 63, 94, 0.15);
            border: 1px solid rgba(244, 63, 94, 0.3);
            border-radius: 10px;
            color: #f87171;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
        }
        
        .btn-clear:hover {
            background: rgba(244, 63, 94, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-clear svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }
        
        .section-title svg {
            width: 22px;
            height: 22px;
            color: var(--accent-rose);
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--bg-secondary);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        thead {
            background: rgba(255, 255, 255, 0.05);
        }
        
        th {
            color: var(--text-secondary);
            font-weight: 600;
            text-align: left;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        
        td {
            color: var(--text-secondary);
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
        }
        
        tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .field-value {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .field-value.mismatch {
            background: rgba(244, 63, 94, 0.15);
            padding: 2px 8px;
            border-radius: 4px;
            color: #f87171;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            margin: 2px;
        }
        
        .badge-mismatch {
            background: rgba(244, 63, 94, 0.15);
            color: #f87171;
            border: 1px solid rgba(244, 63, 94, 0.3);
        }
        
        .badge-source {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .no-mismatch {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        
        .no-mismatch svg {
            width: 64px;
            height: 64px;
            color: var(--accent-green);
            margin-bottom: 20px;
        }
        
        .no-mismatch h3 {
            color: var(--text-primary);
            font-size: 1.25rem;
            margin-bottom: 10px;
        }
        
        .btn-update {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--accent-green), #34d399);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
            margin-top: 20px;
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        }
        
        .btn-update svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
        
        @media (max-width: 768px) {
            .range-inputs {
                flex-direction: column;
            }
            
            .input-group {
                min-width: 100%;
            }
            
            .header {
                flex-direction: column;
                gap: 16px;
            }
            
            th, td {
                padding: 10px 12px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    
    <div class="app-container">
        <header class="header">
            <a href="dashboard.php" class="brand">
                <div class="brand-logo">🔍</div>
                <div class="brand-text">
                    <h1>Deduplicate Records</h1>
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
        
        <h1 class="page-title">Compare within PERSON_TABLE</h1>
        <p class="page-subtitle">Find and fix duplicate records based on ID_NO_NAME.</p>
        
        <div class="panel">
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="range-form">
                <div class="range-inputs">
                    <div class="input-group">
                        <label>From ID_NO_NAME:</label>
                        <input type="text" name="range_from" value="<?php echo htmlspecialchars($range_from); ?>" placeholder="e.g., 1001">
                    </div>
                    <div class="input-group">
                        <label>To ID_NO_NAME:</label>
                        <input type="text" name="range_to" value="<?php echo htmlspecialchars($range_to); ?>" placeholder="e.g., 1010">
                    </div>
                    <button type="submit" name="check_range" class="btn-check">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        Check
                    </button>
                    <?php if ($range_from || $range_to): ?>
                        <button type="submit" name="clear_range" class="btn-clear">
                            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Clear
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (empty($mismatches) && !$error_message): ?>
                <div class="no-mismatch">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <h3>All Records Match!</h3>
                    <p>No mismatches found within PERSON_TABLE for the same ID_NO_NAME.</p>
                </div>
            <?php else: ?>
                <div class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    Mismatched Records (<?php echo count($mismatches); ?>)
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>NAME_FIRST</th>
                                <th>EMP_GRADE</th>
                                <th>DESIGNATION</th>
                                <th>SEL_DEPARTMENT</th>
                                <th>ID_NO_NAME</th>
                                <th>SAP_ID</th>
                                <th>Mismatches</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mismatches as $row):
                                $name_first = $row['NAME_FIRST'] ?? '';
                                $emp_grade = $row['EMP_GRADE'] ?? '';
                                $designation = $row['DESIGNATION'] ?? '';
                                $sel_department = $row['SEL_DEPARTMENT'] ?? '';
                                $id_no_name = $row['ID_NO_NAME'] ?? '';
                                $sap_id = $row['SAP_ID'] ?? '';
                                $grade_mismatch = ($row['GRADE_MISMATCH'] ?? 0);
                                $desig_mismatch = ($row['DESIG_MISMATCH'] ?? 0);
                                $dept_mismatch = ($row['DEPT_MISMATCH'] ?? 0);
                                $from_emp = ($row['FROM_HMS2.EMP_HMS2_TABLE'] ?? 0);
                            ?>
                                <tr>
                                    <td>
                                        <div class="field-value"><?php echo htmlspecialchars($name_first); ?></div>
                                        <?php if ($from_emp): ?>
                                            <span class="badge badge-source">FROM HMS2.EMP_HMS2_TABLE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="field-value <?php echo $grade_mismatch ? 'mismatch' : ''; ?>"><?php echo htmlspecialchars($emp_grade ?: 'NULL'); ?></div>
                                    </td>
                                    <td>
                                        <div class="field-value <?php echo $desig_mismatch ? 'mismatch' : ''; ?>"><?php echo htmlspecialchars($designation ?: 'NULL'); ?></div>
                                    </td>
                                    <td>
                                        <div class="field-value <?php echo $dept_mismatch ? 'mismatch' : ''; ?>"><?php echo htmlspecialchars($sel_department ?: 'NULL'); ?></div>
                                    </td>
                                    <td>
                                        <div class="field-value"><?php echo htmlspecialchars($id_no_name); ?></div>
                                    </td>
                                    <td>
                                        <div class="field-value"><?php echo htmlspecialchars($sap_id); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($grade_mismatch): ?>
                                            <span class="badge badge-mismatch">GRADE</span>
                                        <?php endif; ?>
                                        <?php if ($desig_mismatch): ?>
                                            <span class="badge badge-mismatch">DESIGNATION</span>
                                        <?php endif; ?>
                                        <?php if ($dept_mismatch): ?>
                                            <span class="badge badge-mismatch">DEPARTMENT</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($mismatches)): ?>
                <form method="POST">
                    <button type="submit" name="update_person" class="btn-update">
                        <svg viewBox="0 0 24 24"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                        Update PERSON_TABLE
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
