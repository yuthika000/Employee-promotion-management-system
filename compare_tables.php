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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_person_table'])) {
    if (!$conn) {
        $error_message = "Database connection failed.";
    } else {
        $updated_count = 0;
        $added_dept = 0;
        $added_desig = 0;
        $added_grade = 0;

        // Fetch all HMS2.EMP_HMS2_TABLE records
        $emp_sql = "SELECT EMP_NAME, JOB_TEXT, POS_TEXT, ESG, CPF_NO, CELL FROM HMS2.EMP_HMS2_TABLE";
        $emp_stmt = oci_parse($conn, $emp_sql);
        $emp_records = [];
        if ($emp_stmt && oci_execute($emp_stmt)) {
            while ($row = oci_fetch_assoc($emp_stmt)) {
                $emp_records[] = $row;
            }
            oci_free_statement($emp_stmt);
        }

        // Fetch lookup tables for NR mapping
        $dept_sql = "SELECT NR, NAME_FORMAL FROM DEPARTMENT";
        $dept_stmt = oci_parse($conn, $dept_sql);
        $dept_map = [];
        if ($dept_stmt && oci_execute($dept_stmt)) {
            while ($row = oci_fetch_assoc($dept_stmt)) {
                $dept_map[strtoupper(trim($row['NAME_FORMAL']))] = $row['NR'];
            }
            oci_free_statement($dept_stmt);
        }

        $desig_sql = "SELECT NR, DESIGNATION FROM DESIGNATION";
        $desig_stmt = oci_parse($conn, $desig_sql);
        $desig_map = [];
        if ($desig_stmt && oci_execute($desig_stmt)) {
            while ($row = oci_fetch_assoc($desig_stmt)) {
                $desig_map[strtoupper(trim($row['DESIGNATION']))] = $row['NR'];
            }
            oci_free_statement($desig_stmt);
        }

        $grade_sql = "SELECT NR, GRADE_NAME FROM GRADE";
        $grade_stmt = oci_parse($conn, $grade_sql);
        $grade_map = [];
        if ($grade_stmt && oci_execute($grade_stmt)) {
            while ($row = oci_fetch_assoc($grade_stmt)) {
                $grade_map[strtoupper(trim($row['GRADE_NAME']))] = $row['NR'];
            }
            oci_free_statement($grade_stmt);
        }

        // Get next available NR for each table
        $next_dept_nr = count($dept_map) + 1;
        $next_desig_nr = count($desig_map) + 1;
        $next_grade_nr = count($grade_map) + 1;

        // Process each HMS2.EMP_HMS2_TABLE record
        foreach ($emp_records as $emp) {
            $cpf_no = trim((string)$emp['CPF_NO']);
            $cpf_normalized = preg_replace('/[^A-Z0-9]/i', '', strtoupper($cpf_no));

            // Find matching PERSON_TABLE record
            $person_sql = "SELECT SAP_ID FROM PERSON_TABLE WHERE SAP_ID = :cpf";
            $person_stmt = oci_parse($conn, $person_sql);
            oci_bind_by_name($person_stmt, ':cpf', $cpf_no);
            $person_exists = false;
            if ($person_stmt && oci_execute($person_stmt)) {
                $person_exists = oci_fetch_assoc($person_stmt);
                oci_free_statement($person_stmt);
            }

            if ($person_exists) {
                // Get or create NR for JOB_TEXT
                $job_upper = strtoupper(trim($emp['JOB_TEXT']));
                if (!isset($dept_map[$job_upper])) {
                    $insert_dept = "INSERT INTO DEPARTMENT (NR, NAME_FORMAL) VALUES (:nr, :name)";
                    $stmt = oci_parse($conn, $insert_dept);
                    oci_bind_by_name($stmt, ':nr', $next_dept_nr);
                    oci_bind_by_name($stmt, ':name', $emp['JOB_TEXT']);
                    if (oci_execute($stmt)) {
                        $dept_map[$job_upper] = $next_dept_nr;
                        $next_dept_nr++;
                        $added_dept++;
                    }
                    oci_free_statement($stmt);
                }
                $job_nr = $dept_map[$job_upper];

                // Get or create NR for POS_TEXT
                $pos_upper = strtoupper(trim($emp['POS_TEXT']));
                if (!isset($desig_map[$pos_upper])) {
                    $insert_desig = "INSERT INTO DESIGNATION (NR, DESIGNATION) VALUES (:nr, :name)";
                    $stmt = oci_parse($conn, $insert_desig);
                    oci_bind_by_name($stmt, ':nr', $next_desig_nr);
                    oci_bind_by_name($stmt, ':name', $emp['POS_TEXT']);
                    if (oci_execute($stmt)) {
                        $desig_map[$pos_upper] = $next_desig_nr;
                        $next_desig_nr++;
                        $added_desig++;
                    }
                    oci_free_statement($stmt);
                }
                $pos_nr = $desig_map[$pos_upper];

                // Get or create NR for ESG
                $esg_upper = strtoupper(trim($emp['ESG']));
                if (!isset($grade_map[$esg_upper])) {
                    $insert_grade = "INSERT INTO GRADE (NR, GRADE_NAME) VALUES (:nr, :name)";
                    $stmt = oci_parse($conn, $insert_grade);
                    oci_bind_by_name($stmt, ':nr', $next_grade_nr);
                    oci_bind_by_name($stmt, ':name', $emp['ESG']);
                    if (oci_execute($stmt)) {
                        $grade_map[$esg_upper] = $next_grade_nr;
                        $next_grade_nr++;
                        $added_grade++;
                    }
                    oci_free_statement($stmt);
                }
                $esg_nr = $grade_map[$esg_upper];

                // Update PERSON_TABLE
                $update_sql = "UPDATE PERSON_TABLE SET SEL_DEPARTMENT = :dept, DESIGNATION = :desig, EMP_GRADE = :grade, PHONE_1_NR = :phone WHERE SAP_ID = :sap";
                $update_stmt = oci_parse($conn, $update_sql);
                oci_bind_by_name($update_stmt, ':dept', $job_nr);
                oci_bind_by_name($update_stmt, ':desig', $pos_nr);
                oci_bind_by_name($update_stmt, ':grade', $esg_nr);
                oci_bind_by_name($update_stmt, ':phone', $emp['CELL']);
                oci_bind_by_name($update_stmt, ':sap', $cpf_no);
                if (oci_execute($update_stmt)) {
                    $updated_count++;
                }
                oci_free_statement($update_stmt);
            }
        }

        $success_message = "Updated $updated_count record(s) in PERSON_TABLE. Added $added_dept to DEPARTMENT, $added_desig to DESIGNATION, $added_grade to GRADE.";
    }
}

if (!$conn) {
    $error_message = "Database connection failed.";
} else {
    // Fetch HMS2.EMP_HMS2_TABLE with optional range filter
    $emp_sql = "SELECT EMP_NAME, JOB_TEXT, POS_TEXT, ESG, CPF_NO, CELL FROM HMS2.EMP_HMS2_TABLE";
    if ($range_from && $range_to) {
        $emp_sql .= " WHERE CPF_NO BETWEEN :cpf_from AND :cpf_to";
    }
    $emp_stmt = oci_parse($conn, $emp_sql);
    $emp_records = [];
    if ($range_from && $range_to) {
        oci_bind_by_name($emp_stmt, ':cpf_from', $range_from);
        oci_bind_by_name($emp_stmt, ':cpf_to', $range_to);
    }
    if ($emp_stmt && oci_execute($emp_stmt)) {
        while ($row = oci_fetch_assoc($emp_stmt)) {
            $emp_records[] = $row;
        }
        oci_free_statement($emp_stmt);
    }

    // Fetch PERSON_TABLE
    $person_sql = "SELECT NAME_FIRST, SEL_DEPARTMENT, DESIGNATION, EMP_GRADE, SAP_ID, PHONE_1_NR FROM PERSON_TABLE";
    $person_stmt = oci_parse($conn, $person_sql);
    $person_records = [];
    if ($person_stmt && oci_execute($person_stmt)) {
        while ($row = oci_fetch_assoc($person_stmt)) {
            $person_records[] = $row;
        }
        oci_free_statement($person_stmt);
    }

    // Fetch lookup tables for NR mapping
    $dept_sql = "SELECT NR, NAME_FORMAL FROM DEPARTMENT";
    $dept_stmt = oci_parse($conn, $dept_sql);
    $dept_map = [];
    if ($dept_stmt && oci_execute($dept_stmt)) {
        while ($row = oci_fetch_assoc($dept_stmt)) {
            $dept_map[strtoupper(trim($row['NAME_FORMAL']))] = $row['NR'];
        }
        oci_free_statement($dept_stmt);
    }

    $desig_sql = "SELECT NR, DESIGNATION FROM DESIGNATION";
    $desig_stmt = oci_parse($conn, $desig_sql);
    $desig_map = [];
    if ($desig_stmt && oci_execute($desig_stmt)) {
        while ($row = oci_fetch_assoc($desig_stmt)) {
            $desig_map[strtoupper(trim($row['DESIGNATION']))] = $row['NR'];
        }
        oci_free_statement($desig_stmt);
    }

    $grade_sql = "SELECT NR, GRADE_NAME FROM GRADE";
    $grade_stmt = oci_parse($conn, $grade_sql);
    $grade_map = [];
    if ($grade_stmt && oci_execute($grade_stmt)) {
        while ($row = oci_fetch_assoc($grade_stmt)) {
            $grade_map[strtoupper(trim($row['GRADE_NAME']))] = $row['NR'];
        }
        oci_free_statement($grade_stmt);
    }

    // Compare records in PHP
    foreach ($emp_records as $emp) {
        $cpf_no = trim((string)$emp['CPF_NO']);
        // Normalize CPF_NO: remove common prefixes and convert to uppercase
        $cpf_normalized = preg_replace('/[^A-Z0-9]/i', '', strtoupper($cpf_no));

        $matched_person = null;

        // Find matching PERSON_TABLE record by SAP_ID
        foreach ($person_records as $person) {
            $sap_id = trim((string)$person['SAP_ID']);
            // Normalize SAP_ID: remove common prefixes and convert to uppercase
            $sap_normalized = preg_replace('/[^A-Z0-9]/i', '', strtoupper($sap_id));

            if ($cpf_normalized == $sap_normalized) {
                $matched_person = $person;
                break;
            }
        }

        $mismatch = false;
        $dept_mismatch = false;
        $desig_mismatch = false;
        $grade_mismatch = false;
        $phone_mismatch = false;

        if (!$matched_person) {
            $mismatch = true;
        } else {
            // Convert HMS2.EMP_HMS2_TABLE text values to NR using lookup tables
            $emp_job_nr = $dept_map[strtoupper(trim($emp['JOB_TEXT']))] ?? null;
            $emp_pos_nr = $desig_map[strtoupper(trim($emp['POS_TEXT']))] ?? null;
            $emp_esg_nr = $grade_map[strtoupper(trim($emp['ESG']))] ?? null;

            // Check if HMS2.EMP_HMS2_TABLE values exist in lookup tables
            $job_not_in_lookup = !isset($dept_map[strtoupper(trim($emp['JOB_TEXT']))]);
            $pos_not_in_lookup = !isset($desig_map[strtoupper(trim($emp['POS_TEXT']))]);
            $esg_not_in_lookup = !isset($grade_map[strtoupper(trim($emp['ESG']))]);

            // Compare NR values - also flag if value doesn't exist in lookup table
            if ($job_not_in_lookup || $emp_job_nr != $matched_person['SEL_DEPARTMENT']) {
                $mismatch = true;
                $dept_mismatch = true;
            }
            if ($pos_not_in_lookup || $emp_pos_nr != $matched_person['DESIGNATION']) {
                $mismatch = true;
                $desig_mismatch = true;
            }
            if ($esg_not_in_lookup || $emp_esg_nr != $matched_person['EMP_GRADE']) {
                $mismatch = true;
                $grade_mismatch = true;
            }
            // Compare CELL vs PHONE_1_NR
            if (trim($emp['CELL']) != trim($matched_person['PHONE_1_NR'])) {
                $mismatch = true;
                $phone_mismatch = true;
            }
        }

        if ($mismatch) {
            $mismatches[] = [
                'EMP_NAME' => $emp['EMP_NAME'],
                'JOB_TEXT' => $emp['JOB_TEXT'],
                'POS_TEXT' => $emp['POS_TEXT'],
                'ESG' => $emp['ESG'],
                'CPF_NO' => $emp['CPF_NO'],
                'CELL' => $emp['CELL'],
                'NAME_FIRST' => $matched_person['NAME_FIRST'] ?? null,
                'SEL_DEPARTMENT' => $matched_person['SEL_DEPARTMENT'] ?? null,
                'DESIGNATION' => $matched_person['DESIGNATION'] ?? null,
                'EMP_GRADE' => $matched_person['EMP_GRADE'] ?? null,
                'SAP_ID' => $matched_person['SAP_ID'] ?? null,
                'PHONE_1_NR' => $matched_person['PHONE_1_NR'] ?? null,
                'MISSING_IN_PERSON' => $matched_person ? 0 : 1,
                'DEPT_MISMATCH' => $dept_mismatch ? 1 : 0,
                'DESIG_MISMATCH' => $desig_mismatch ? 1 : 0,
                'GRADE_MISMATCH' => $grade_mismatch ? 1 : 0,
                'PHONE_MISMATCH' => $phone_mismatch ? 1 : 0,
                'JOB_NOT_IN_LOOKUP' => !isset($dept_map[strtoupper(trim($emp['JOB_TEXT']))]),
                'POS_NOT_IN_LOOKUP' => !isset($desig_map[strtoupper(trim($emp['POS_TEXT']))]),
                'ESG_NOT_IN_LOOKUP' => !isset($grade_map[strtoupper(trim($emp['ESG']))])
            ];
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
    <title>Compare Tables - HMS</title>
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
            --accent-rose: #f43f5e;
            --accent-green: #10b981;
            --accent-blue: #6366f1;
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
            background: linear-gradient(135deg, var(--accent-rose), #fb7185);
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
        
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .summary-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .summary-label {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 8px;
        }
        
        .summary-value {
            color: var(--text-primary);
            font-size: 2rem;
            font-weight: 700;
        }
        
        .summary-value.mismatch { color: #f43f5e; }
        .summary-value.match { color: #34d399; }
        
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
        
        .badge-missing {
            background: rgba(244, 63, 94, 0.15);
            color: #f87171;
            border: 1px solid rgba(244, 63, 94, 0.3);
        }
        
        .badge-lookup {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .badge-match {
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
                <div class="brand-logo">⚖️</div>
                <div class="brand-text">
                    <h1>Compare Tables</h1>
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
        
        <h1 class="page-title">Compare EMP_TABLE and PERSON_TABLE</h1>
        <p class="page-subtitle">Compare data between HMS2.EMP_HMS2_TABLE and PERSON_TABLE.</p>
        
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
                        <label>From CPF_NO:</label>
                        <input type="text" name="range_from" value="<?php echo htmlspecialchars($range_from); ?>" placeholder="e.g., CPF001">
                    </div>
                    <div class="input-group">
                        <label>To CPF_NO:</label>
                        <input type="text" name="range_to" value="<?php echo htmlspecialchars($range_to); ?>" placeholder="e.g., CPF010">
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
                    <p>No mismatches found between HMS2.EMP_HMS2_TABLE and PERSON_TABLE.</p>
                </div>
            <?php else: ?>
                <div class="summary">
                    <div class="summary-card">
                        <div class="summary-label">Total Mismatches</div>
                        <div class="summary-value mismatch"><?php echo count($mismatches); ?></div>
                    </div>
                </div>

                <div class="mismatch-section">
                    <div class="section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Mismatched Records
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>CPF_NO</th>
                                    <th>ESG</th>
                                    <th>POS_TEXT</th>
                                    <th>JOB_TEXT</th>
                                    <th>CELL (EMP)</th>
                                    <th>PHONE_1_NR (PERSON)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mismatches as $row):
                                    $emp_name = $row['EMP_NAME'] ?? $row['emp_name'] ?? '';
                                    $cpf_no = $row['CPF_NO'] ?? $row['cpf_no'] ?? '';
                                    $esg = $row['ESG'] ?? $row['esg'] ?? '';
                                    $pos_text = $row['POS_TEXT'] ?? $row['pos_text'] ?? '';
                                    $job_text = $row['JOB_TEXT'] ?? $row['job_text'] ?? '';
                                    $cell = $row['CELL'] ?? '';
                                    $phone_1_nr = $row['PHONE_1_NR'] ?? '';
                                    $missing = ($row['MISSING_IN_PERSON'] ?? $row['missing_in_person'] ?? 0);
                                    $dept_mismatch = ($row['DEPT_MISMATCH'] ?? $row['dept_mismatch'] ?? 0);
                                    $desig_mismatch = ($row['DESIG_MISMATCH'] ?? $row['desig_mismatch'] ?? 0);
                                    $grade_mismatch = ($row['GRADE_MISMATCH'] ?? $row['grade_mismatch'] ?? 0);
                                    $phone_mismatch = ($row['PHONE_MISMATCH'] ?? $row['phone_mismatch'] ?? 0);
                                    $job_not_in_lookup = ($row['JOB_NOT_IN_LOOKUP'] ?? 0);
                                    $pos_not_in_lookup = ($row['POS_NOT_IN_LOOKUP'] ?? 0);
                                    $esg_not_in_lookup = ($row['ESG_NOT_IN_LOOKUP'] ?? 0);
                                ?>
                                    <tr>
                                        <td>
                                            <div class="field-value"><?php echo htmlspecialchars($emp_name); ?></div>
                                            <?php if ($missing): ?>
                                                <span class="badge badge-missing">NOT IN PERSON_TABLE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="field-value"><?php echo htmlspecialchars($cpf_no); ?></div>
                                        </td>
                                        <td>
                                            <div class="field-value <?php echo $grade_mismatch ? 'mismatch' : ''; ?>"><?php echo htmlspecialchars($esg ?: 'NULL'); ?></div>
                                            <?php if ($esg_not_in_lookup): ?>
                                                <span class="badge badge-lookup">NOT IN GRADE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="field-value <?php echo $desig_mismatch ? 'mismatch' : ''; ?>"><?php echo htmlspecialchars($pos_text ?: 'NULL'); ?></div>
                                            <?php if ($pos_not_in_lookup): ?>
                                                <span class="badge badge-lookup">NOT IN DESIGNATION</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="field-value <?php echo $dept_mismatch ? 'mismatch' : ''; ?>"><?php echo htmlspecialchars($job_text ?: 'NULL'); ?></div>
                                            <?php if ($job_not_in_lookup): ?>
                                                <span class="badge badge-lookup">NOT IN DEPARTMENT</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="field-value <?php echo $phone_mismatch ? 'mismatch' : ''; ?>"><?php echo htmlspecialchars($cell ?: 'NULL'); ?></div>
                                        </td>
                                        <td>
                                            <div class="field-value <?php echo $phone_mismatch ? 'mismatch' : ''; ?>"><?php echo htmlspecialchars($phone_1_nr ?: 'NULL'); ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($mismatches)): ?>
                <form method="POST">
                    <button type="submit" name="update_person_table" class="btn-update">
                        <svg viewBox="0 0 24 24"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                        Update PERSON_TABLE
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
