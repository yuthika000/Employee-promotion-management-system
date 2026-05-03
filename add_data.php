<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_data'])) {
    // Get form data
    $emp_name = trim($_POST['emp_name'] ?? '');
    $job_text = trim($_POST['job_text'] ?? '');
    $pos_text = trim($_POST['pos_text'] ?? '');
    $esg = trim($_POST['esg'] ?? '');
    $cpf_no = trim($_POST['cpf_no'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $ta_cpf_no = trim($_POST['ta_cpf_no'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $doj = $_POST['doj'] ?? '';
    $dor = $_POST['dor'] ?? '';
    $desig_date = $_POST['desig_date'] ?? '';
    $pr_addr_1 = trim($_POST['pr_addr_1'] ?? '');
    $pr_addr_2 = trim($_POST['pr_addr_2'] ?? '');
    $pr_city = trim($_POST['pr_city'] ?? '');
    $pr_pin_code = trim($_POST['pr_pin_code'] ?? '');
    $pr_district = trim($_POST['pr_district'] ?? '');
    $pr_state = trim($_POST['pr_state'] ?? '');
    $pr_country = trim($_POST['pr_country'] ?? '');
    $cell = trim($_POST['cell'] ?? '');
    
    // Validation
    if (empty($emp_name)) $errors['emp_name'] = "Employee name is required";
    if (empty($job_text)) $errors['job_text'] = "Job text is required";
    if (empty($pos_text)) $errors['pos_text'] = "Position text is required";
    if (empty($esg)) $errors['esg'] = "ESG is required";
    if (empty($cpf_no)) $errors['cpf_no'] = "CPF number is required";
    if (empty($dob)) $errors['dob'] = "Date of birth is required";
    if (empty($ta_cpf_no)) $errors['ta_cpf_no'] = "TA CPF number is required";
    if (empty($gender)) $errors['gender'] = "Gender is required";
    if (empty($doj)) $errors['doj'] = "Date of Joining is required";
    if (empty($dor)) $errors['dor'] = "Date of Retirement is required";
    if (empty($desig_date)) $errors['desig_date'] = "Designation Date is required";
    if (empty($pr_addr_1)) $errors['pr_addr_1'] = "Present Address Line 1 is required";
    if (empty($pr_city)) $errors['pr_city'] = "City is required";
    if (empty($pr_pin_code)) $errors['pr_pin_code'] = "PIN Code is required";
    if (empty($pr_district)) $errors['pr_district'] = "District is required";
    if (empty($pr_state)) $errors['pr_state'] = "State is required";
    if (empty($pr_country)) $errors['pr_country'] = "Country is required";
    if (empty($cell)) $errors['cell'] = "Cell number is required";
    
    // Check if CPF_NO already exists
    if (empty($errors)) {
        $check_sql = "SELECT COUNT(*) as CNT FROM HMS2.EMP_HMS2_TABLE WHERE CPF_NO = :cpf_no";
        $check_stid = oci_parse($conn, $check_sql);
        oci_bind_by_name($check_stid, ':cpf_no', $cpf_no);
        if ($check_stid && oci_execute($check_stid)) {
            $check_row = oci_fetch_assoc($check_stid);
            if ($check_row && $check_row['CNT'] > 0) {
                $errors['cpf_no'] = "CPF number already exists";
            }
            oci_free_statement($check_stid);
        }
    }
    
    // Insert if no errors
    if (empty($errors)) {
        $insert_sql = "INSERT INTO HMS2.EMP_HMS2_TABLE 
            (EMP_NAME, JOB_TEXT, POS_TEXT, ESG, CPF_NO, DOB, TA_CPF_NO, GENDER, DOJ, DOR, DESIG_DATE, PR_ADDR_1, PR_ADDR_2, PR_CITY, PR_PIN_CODE, PR_DISTRICT, PR_STATE, PR_COUNTRY, CELL) 
            VALUES 
            (:emp_name, :job_text, :pos_text, :esg, :cpf_no, TO_DATE(:dob, 'YYYY-MM-DD'), :ta_cpf_no, :gender, TO_DATE(:doj, 'YYYY-MM-DD'), TO_DATE(:dor, 'YYYY-MM-DD'), TO_DATE(:desig_date, 'YYYY-MM-DD'), :pr_addr_1, :pr_addr_2, :pr_city, :pr_pin_code, :pr_district, :pr_state, :pr_country, :cell)";
        
        $insert_stid = oci_parse($conn, $insert_sql);
        oci_bind_by_name($insert_stid, ':emp_name', $emp_name);
        oci_bind_by_name($insert_stid, ':job_text', $job_text);
        oci_bind_by_name($insert_stid, ':pos_text', $pos_text);
        oci_bind_by_name($insert_stid, ':esg', $esg);
        oci_bind_by_name($insert_stid, ':cpf_no', $cpf_no);
        oci_bind_by_name($insert_stid, ':dob', $dob);
        oci_bind_by_name($insert_stid, ':ta_cpf_no', $ta_cpf_no);
        oci_bind_by_name($insert_stid, ':gender', $gender);
        oci_bind_by_name($insert_stid, ':doj', $doj);
        oci_bind_by_name($insert_stid, ':dor', $dor);
        oci_bind_by_name($insert_stid, ':desig_date', $desig_date);
        oci_bind_by_name($insert_stid, ':pr_addr_1', $pr_addr_1);
        oci_bind_by_name($insert_stid, ':pr_addr_2', $pr_addr_2);
        oci_bind_by_name($insert_stid, ':pr_city', $pr_city);
        oci_bind_by_name($insert_stid, ':pr_pin_code', $pr_pin_code);
        oci_bind_by_name($insert_stid, ':pr_district', $pr_district);
        oci_bind_by_name($insert_stid, ':pr_state', $pr_state);
        oci_bind_by_name($insert_stid, ':pr_country', $pr_country);
        oci_bind_by_name($insert_stid, ':cell', $cell);
        
        if (oci_execute($insert_stid, OCI_COMMIT_ON_SUCCESS)) {
            $success = "Record added successfully!";
            // Clear form
            $emp_name = $job_text = $pos_text = $esg = $cpf_no = $dob = $ta_cpf_no = $gender = '';
            $doj = $dor = $desig_date = $pr_addr_1 = $pr_addr_2 = $pr_city = $pr_pin_code = $pr_district = $pr_state = $pr_country = $cell = '';
        } else {
            $e = oci_error($insert_stid);
            $error = "Error inserting record: " . $e['message'];
        }
        oci_free_statement($insert_stid);
    } else {
        $error = "Please correct the errors below.";
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - HMS</title>
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
            --accent-cyan: #06b6d4;
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
        
        .app-container {
            position: relative;
            z-index: 1;
            max-width: 1200px;
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
            text-decoration: none;
            color: var(--text-primary);
        }
        
        .brand-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent-cyan), #22d3ee);
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
        
        /* Main Content */
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
        
        /* Form Panel */
        .form-panel {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
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
        
        /* Form Sections */
        .form-section {
            margin-bottom: 28px;
        }
        
        .form-section:last-of-type {
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        
        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        
        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group.half-width {
            grid-column: span 2;
        }
        
        @media (max-width: 640px) {
            .form-group.half-width {
                grid-column: 1;
            }
        }
        
        label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        label .required {
            color: #f87171;
            margin-left: 2px;
        }
        
        input, select {
            padding: 12px 14px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            color: var(--text-primary);
            font-family: inherit;
            transition: all 0.2s ease;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.4);
            background: rgba(255, 255, 255, 0.03);
        }
        
        input::placeholder {
            color: var(--text-muted);
        }
        
        input.error, select.error {
            border-color: rgba(239, 68, 68, 0.4);
            background: rgba(239, 68, 68, 0.05);
        }
        
        .error-text {
            font-size: 0.75rem;
            color: #f87171;
            margin-top: 2px;
        }
        
        select option {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        /* Submit Button */
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
        
        .btn-submit {
            flex: 1;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--accent-cyan), #22d3ee);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(6, 182, 212, 0.3);
        }
        
        .btn-cancel {
            padding: 14px 24px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <a href="dashboard.php" class="brand">
                <div class="brand-logo">➕</div>
                <div class="brand-text">
                    <h1>Add Employee</h1>
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
        
        <!-- Page Title -->
        <h1 class="page-title">Add New Employee</h1>
        <p class="page-subtitle">Fill in the details below to add a new employee record to the system.</p>
        
        <!-- Form Panel -->
        <div class="form-panel">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Employment Information -->
                <div class="form-section">
                    <div class="section-title">Employment Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="emp_name">Employee Name <span class="required">*</span></label>
                            <input type="text" id="emp_name" name="emp_name" value="<?php echo htmlspecialchars($emp_name ?? ''); ?>" class="<?php echo isset($errors['emp_name']) ? 'error' : ''; ?>" placeholder="Enter full name" required>
                            <?php if (isset($errors['emp_name'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['emp_name']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="cpf_no">CPF Number <span class="required">*</span></label>
                            <input type="text" id="cpf_no" name="cpf_no" value="<?php echo htmlspecialchars($cpf_no ?? ''); ?>" class="<?php echo isset($errors['cpf_no']) ? 'error' : ''; ?>" placeholder="e.g., CPF001" required>
                            <?php if (isset($errors['cpf_no'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['cpf_no']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="ta_cpf_no">TA CPF Number <span class="required">*</span></label>
                            <input type="text" id="ta_cpf_no" name="ta_cpf_no" value="<?php echo htmlspecialchars($ta_cpf_no ?? ''); ?>" class="<?php echo isset($errors['ta_cpf_no']) ? 'error' : ''; ?>" placeholder="e.g., TA001" required>
                            <?php if (isset($errors['ta_cpf_no'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['ta_cpf_no']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="job_text">Department <span class="required">*</span></label>
                            <input type="text" id="job_text" name="job_text" value="<?php echo htmlspecialchars($job_text ?? ''); ?>" class="<?php echo isset($errors['job_text']) ? 'error' : ''; ?>" placeholder="e.g., Finance" required>
                            <?php if (isset($errors['job_text'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['job_text']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="pos_text">Designation <span class="required">*</span></label>
                            <input type="text" id="pos_text" name="pos_text" value="<?php echo htmlspecialchars($pos_text ?? ''); ?>" class="<?php echo isset($errors['pos_text']) ? 'error' : ''; ?>" placeholder="e.g., Manager" required>
                            <?php if (isset($errors['pos_text'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['pos_text']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="esg">Grade (ESG) <span class="required">*</span></label>
                            <input type="text" id="esg" name="esg" value="<?php echo htmlspecialchars($esg ?? ''); ?>" class="<?php echo isset($errors['esg']) ? 'error' : ''; ?>" placeholder="e.g., G1" required>
                            <?php if (isset($errors['esg'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['esg']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Personal Information -->
                <div class="form-section">
                    <div class="section-title">Personal Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="gender">Gender <span class="required">*</span></label>
                            <select id="gender" name="gender" class="<?php echo isset($errors['gender']) ? 'error' : ''; ?>" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($gender ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($gender ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                            <?php if (isset($errors['gender'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['gender']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="dob">Date of Birth <span class="required">*</span></label>
                            <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($dob ?? ''); ?>" class="<?php echo isset($errors['dob']) ? 'error' : ''; ?>" required>
                            <?php if (isset($errors['dob'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['dob']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="cell">Cell Number <span class="required">*</span></label>
                            <input type="text" id="cell" name="cell" value="<?php echo htmlspecialchars($cell ?? ''); ?>" class="<?php echo isset($errors['cell']) ? 'error' : ''; ?>" placeholder="Mobile number" required>
                            <?php if (isset($errors['cell'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['cell']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Employment Dates -->
                <div class="form-section">
                    <div class="section-title">Employment Dates</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="doj">Date of Joining <span class="required">*</span></label>
                            <input type="date" id="doj" name="doj" value="<?php echo htmlspecialchars($doj ?? ''); ?>" class="<?php echo isset($errors['doj']) ? 'error' : ''; ?>" required>
                            <?php if (isset($errors['doj'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['doj']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="dor">Date of Retirement <span class="required">*</span></label>
                            <input type="date" id="dor" name="dor" value="<?php echo htmlspecialchars($dor ?? ''); ?>" class="<?php echo isset($errors['dor']) ? 'error' : ''; ?>" required>
                            <?php if (isset($errors['dor'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['dor']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="desig_date">Designation Date <span class="required">*</span></label>
                            <input type="date" id="desig_date" name="desig_date" value="<?php echo htmlspecialchars($desig_date ?? ''); ?>" class="<?php echo isset($errors['desig_date']) ? 'error' : ''; ?>" required>
                            <?php if (isset($errors['desig_date'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['desig_date']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Address -->
                <div class="form-section">
                    <div class="section-title">Present Address</div>
                    <div class="form-grid">
                        <div class="form-group half-width">
                            <label for="pr_addr_1">Address Line 1 <span class="required">*</span></label>
                            <input type="text" id="pr_addr_1" name="pr_addr_1" value="<?php echo htmlspecialchars($pr_addr_1 ?? ''); ?>" class="<?php echo isset($errors['pr_addr_1']) ? 'error' : ''; ?>" placeholder="Street address" required>
                            <?php if (isset($errors['pr_addr_1'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['pr_addr_1']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group half-width">
                            <label for="pr_addr_2">Address Line 2</label>
                            <input type="text" id="pr_addr_2" name="pr_addr_2" value="<?php echo htmlspecialchars($pr_addr_2 ?? ''); ?>" placeholder="Apartment, suite, etc. (optional)">
                        </div>
                        
                        <div class="form-group">
                            <label for="pr_city">City <span class="required">*</span></label>
                            <input type="text" id="pr_city" name="pr_city" value="<?php echo htmlspecialchars($pr_city ?? ''); ?>" class="<?php echo isset($errors['pr_city']) ? 'error' : ''; ?>" placeholder="City name" required>
                            <?php if (isset($errors['pr_city'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['pr_city']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="pr_pin_code">PIN Code <span class="required">*</span></label>
                            <input type="text" id="pr_pin_code" name="pr_pin_code" value="<?php echo htmlspecialchars($pr_pin_code ?? ''); ?>" class="<?php echo isset($errors['pr_pin_code']) ? 'error' : ''; ?>" placeholder="6-digit PIN" required>
                            <?php if (isset($errors['pr_pin_code'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['pr_pin_code']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="pr_district">District <span class="required">*</span></label>
                            <input type="text" id="pr_district" name="pr_district" value="<?php echo htmlspecialchars($pr_district ?? ''); ?>" class="<?php echo isset($errors['pr_district']) ? 'error' : ''; ?>" placeholder="District name" required>
                            <?php if (isset($errors['pr_district'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['pr_district']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="pr_state">State <span class="required">*</span></label>
                            <input type="text" id="pr_state" name="pr_state" value="<?php echo htmlspecialchars($pr_state ?? ''); ?>" class="<?php echo isset($errors['pr_state']) ? 'error' : ''; ?>" placeholder="State name" required>
                            <?php if (isset($errors['pr_state'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['pr_state']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="pr_country">Country <span class="required">*</span></label>
                            <input type="text" id="pr_country" name="pr_country" value="<?php echo htmlspecialchars($pr_country ?? ''); ?>" class="<?php echo isset($errors['pr_country']) ? 'error' : ''; ?>" placeholder="Country name" required>
                            <?php if (isset($errors['pr_country'])): ?>
                                <div class="error-text"><?php echo htmlspecialchars($errors['pr_country']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="dashboard.php" class="btn-cancel">Cancel</a>
                    <button type="submit" name="submit_data" class="btn-submit">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
